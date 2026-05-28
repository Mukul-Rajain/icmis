import { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { filerApi } from '@/services/api';
import api from '@/services/api';
import Layout, { ProtectedRoute } from '@/components/Layout';
import StatusBadge from '@/components/ui/StatusBadge';
import Button from '@/components/ui/Button';
import Input from '@/components/ui/Input';
import toast from 'react-hot-toast';

// ── PDF Viewer Modal ──────────────────────────────────────────
function PdfModal({ url, label, onClose }) {
    return (
        <div className="fixed inset-0 z-50 bg-ink/80 flex flex-col">
            {/* Toolbar */}
            <div className="flex items-center justify-between bg-ink px-5 py-3 border-b-[3px] border-mint shrink-0">
                <div>
                    <p className="text-[10px] font-black uppercase tracking-widest text-mint">Document Viewer</p>
                    <p className="text-sm font-black text-white">{label}</p>
                </div>
                <div className="flex items-center gap-3">
                    <a
                        href={url}
                        target="_blank"
                        rel="noreferrer"
                        className="px-3 py-1.5 bg-mint text-ink border-2 border-mint text-[10px] font-black uppercase tracking-widest hover:bg-white transition-colors"
                    >
                        Open in New Tab ↗
                    </a>
                    <button
                        onClick={onClose}
                        className="px-3 py-1.5 bg-harsh text-white border-2 border-harsh text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-harsh transition-colors"
                    >
                        Close ✕
                    </button>
                </div>
            </div>
            {/* PDF iframe */}
            <iframe
                src={url}
                className="flex-1 w-full bg-white"
                title={label}
            />
        </div>
    );
}

// ── Upload Document Modal ─────────────────────────────────────
function UploadModal({ caseId, onClose, onDone }) {
    const [label, setLabel]     = useState('');
    const [file, setFile]       = useState(null);
    const [loading, setLoading] = useState(false);
    const inputRef = useRef();

    const submit = async (e) => {
        e.preventDefault();
        if (!file) { toast.error('Select a file first.'); return; }
        setLoading(true);
        const fd = new FormData();
        fd.append('label', label);
        fd.append('file', file);
        try {
            await filerApi.uploadDocument(caseId, fd);
            toast.success('Document uploaded.');
            onDone();
        } catch (err) {
            toast.error(err.response?.data?.message ?? 'Upload failed.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-ink/60 flex items-center justify-center z-50 p-4">
            <div className="w-full max-w-md border-[3px] border-ink bg-white shadow-brutal-lg">
                <div className="bg-ink px-6 py-4">
                    <h2 className="text-lg font-black text-white">Attach Document</h2>
                    <p className="text-[10px] text-ash font-bold mt-0.5">PDF, DOC, DOCX, JPG, PNG — max 10 MB</p>
                </div>
                <form onSubmit={submit} className="p-6 space-y-4">
                    <Input
                        label="Document Label"
                        placeholder="e.g. Plaint, Vakalatnama, Affidavit…"
                        value={label}
                        onChange={e => setLabel(e.target.value)}
                        required
                    />
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[10px] font-black uppercase tracking-widest text-ash">File</label>
                        <div
                            className="border-2 border-ink border-dashed p-6 text-center cursor-pointer hover:bg-chalk transition-colors"
                            onClick={() => inputRef.current?.click()}
                        >
                            {file ? (
                                <p className="text-sm font-black text-ink">{file.name}</p>
                            ) : (
                                <p className="text-sm font-bold text-ash">Click to select file</p>
                            )}
                            <input
                                ref={inputRef}
                                type="file"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                className="hidden"
                                onChange={e => setFile(e.target.files[0] ?? null)}
                            />
                        </div>
                    </div>
                    <div className="flex gap-3 pt-1">
                        <Button variant="primary" type="submit" disabled={loading} className="flex-1">
                            {loading ? 'Uploading…' : 'Upload Document'}
                        </Button>
                        <Button variant="ghost" type="button" onClick={onClose} className="flex-1">
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ── Main Component ────────────────────────────────────────────
export default function CaseDetail() {
    const { id }                        = useParams();
    const navigate                      = useNavigate();
    const [caseData, setCaseData]       = useState(null);
    const [loading, setLoading]         = useState(true);
    const [submitting, setSubmitting]   = useState(false);
    const [uploadOpen, setUploadOpen]   = useState(false);
    const [pdfViewer, setPdfViewer]     = useState(null); // { url, label }

    const load = () => {
        setLoading(true);
        filerApi.getCase(id)
            .then(r => setCaseData(r.data.case))
            .catch(() => { toast.error('Case not found.'); navigate('/filer'); })
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, [id]);

    const handleSubmit = async () => {
        setSubmitting(true);
        try {
            await filerApi.submitCase(id);
            toast.success('Case submitted to registry for scrutiny.');
            load();
        } catch (err) {
            toast.error(err.response?.data?.message ?? 'Submission failed.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleDeleteDoc = async (docId) => {
        if (!confirm('Remove this document?')) return;
        try {
            await filerApi.deleteDocument(id, docId);
            toast.success('Document removed.');
            load();
        } catch (err) {
            toast.error(err.response?.data?.message ?? 'Failed to remove document.');
        }
    };

    const openPdf = async (doc) => {
        toast('Loading document…', { duration: 1500 });
        try {
            const resp = await api.get(
                `/cases/${id}/documents/${doc.doc_id}/view`,
                { responseType: 'blob' }
            );
            const objectUrl = URL.createObjectURL(resp.data);
            setPdfViewer({ url: objectUrl, label: doc.label });
        } catch {
            toast.error('Could not load document.');
        }
    };

    if (loading) {
        return (
            <ProtectedRoute role="filer">
                <Layout>
                    <div className="text-sm font-bold text-ash">Loading case…</div>
                </Layout>
            </ProtectedRoute>
        );
    }

    if (!caseData) return null;

    const c = caseData;
    const canEdit   = ['draft', 'defective'].includes(c.status);
    const canSubmit = c.status === 'draft' && (c.documents ?? []).length > 0;

    return (
        <ProtectedRoute role="filer">
            <Layout>
                {/* Header */}
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <button
                            onClick={() => navigate('/filer')}
                            className="text-[10px] font-black uppercase tracking-widest text-ash hover:text-ink mb-2 block"
                        >
                            ← Back to My Cases
                        </button>
                        <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash">
                            {c.case_number ?? 'Draft — Not Yet Filed'}
                        </p>
                        <h1 className="text-2xl font-black text-ink leading-tight mt-1 max-w-2xl">
                            {c.title}
                        </h1>
                    </div>
                    <div className="flex flex-col items-end gap-2">
                        <StatusBadge status={c.status} />
                        {canSubmit && (
                            <Button
                                variant="primary"
                                onClick={handleSubmit}
                                disabled={submitting}
                            >
                                {submitting ? 'Submitting…' : 'Submit to Registry →'}
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid lg:grid-cols-3 gap-6">
                    {/* Left: main info */}
                    <div className="lg:col-span-2 space-y-5">

                        {/* Defect flags */}
                        {c.status === 'defective' && c.scrutiny?.defect_flags?.length > 0 && (
                            <div className="border-[3px] border-harsh bg-orange-50 p-5 shadow-brutal">
                                <p className="text-[10px] font-black uppercase tracking-widest text-harsh mb-3">
                                    Registry Defects — Action Required
                                </p>
                                {c.scrutiny.rejection_note && (
                                    <p className="text-sm font-bold text-ink mb-3 italic">
                                        "{c.scrutiny.rejection_note}"
                                    </p>
                                )}
                                <ul className="space-y-2">
                                    {c.scrutiny.defect_flags.map((f, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm font-bold text-ink">
                                            <span className="mt-1.5 h-2 w-2 shrink-0 bg-harsh border border-ink" />
                                            {f.label}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {/* Description */}
                        <div className="border-[3px] border-ink bg-white p-5 shadow-brutal">
                            <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-2">
                                Description / Facts
                            </p>
                            <p className="text-sm font-bold text-ink leading-relaxed whitespace-pre-line">
                                {c.description}
                            </p>
                        </div>

                        {/* Parties */}
                        <div className="border-[3px] border-ink bg-white shadow-brutal">
                            <div className="bg-ink px-5 py-3">
                                <h2 className="font-black text-white text-sm uppercase tracking-widest">
                                    Parties
                                </h2>
                            </div>
                            <div className="divide-y-2 divide-ink">
                                {(c.parties ?? []).map((p, i) => (
                                    <div key={i} className="px-5 py-3 flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-[10px] font-black uppercase tracking-widest text-ash">
                                                {p.party_type}
                                            </p>
                                            <p className="font-black text-ink text-sm">{p.name}</p>
                                            <p className="text-xs font-bold text-ash mt-0.5">{p.address}</p>
                                        </div>
                                        <div className="flex gap-2 shrink-0">
                                            {p.is_senior_citizen && (
                                                <span className="px-2 py-0.5 bg-volt text-ink border border-ink text-[9px] font-black uppercase tracking-widest">
                                                    Senior
                                                </span>
                                            )}
                                            {p.is_in_custody && (
                                                <span className="px-2 py-0.5 bg-harsh text-white border border-ink text-[9px] font-black uppercase tracking-widest">
                                                    Custody
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Documents */}
                        <div className="border-[3px] border-ink bg-white shadow-brutal">
                            <div className="bg-ink px-5 py-3 flex items-center justify-between">
                                <h2 className="font-black text-white text-sm uppercase tracking-widest">
                                    Documents ({(c.documents ?? []).length})
                                </h2>
                                {canEdit && (
                                    <button
                                        onClick={() => setUploadOpen(true)}
                                        className="px-3 py-1.5 bg-mint text-ink border-2 border-mint text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75"
                                    >
                                        + Attach Document
                                    </button>
                                )}
                            </div>

                            {(c.documents ?? []).length === 0 ? (
                                <div className="p-8 text-center">
                                    <p className="text-sm font-bold text-ash">No documents attached yet.</p>
                                    {c.status === 'draft' && (
                                        <p className="text-xs font-bold text-ash mt-1">
                                            You must attach at least one document before submitting.
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <div className="divide-y-2 divide-ink">
                                    {(c.documents ?? []).map((doc, i) => (
                                        <div key={doc.doc_id ?? i} className="px-5 py-3 flex items-center justify-between gap-4">
                                            <div className="flex items-center gap-3 min-w-0">
                                                {/* PDF icon */}
                                                <div className="w-10 h-10 shrink-0 bg-harsh border-2 border-ink flex items-center justify-center shadow-brutal-sm">
                                                    <span className="text-[9px] font-black text-white uppercase">
                                                        {(doc.mime_type ?? '').includes('pdf') ? 'PDF' : 'DOC'}
                                                    </span>
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="font-black text-ink text-sm truncate">{doc.label}</p>
                                                    <p className="text-[10px] font-bold text-ash uppercase tracking-widest">
                                                        {doc.is_verified ? '✓ Verified' : 'Pending Verification'}
                                                        {doc.uploaded_at && ` · ${new Date(doc.uploaded_at).toLocaleDateString('en-IN')}`}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex gap-2 shrink-0">
                                                <button
                                                    onClick={() => openPdf(doc)}
                                                    className="px-3 py-1.5 border-2 border-ink text-[10px] font-black uppercase tracking-widest bg-brutal text-white shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75"
                                                >
                                                    View
                                                </button>
                                                {canEdit && (
                                                    <button
                                                        onClick={() => handleDeleteDoc(doc.doc_id)}
                                                        className="px-3 py-1.5 border-2 border-harsh text-[10px] font-black uppercase tracking-widest bg-white text-harsh hover:bg-harsh hover:text-white transition-colors duration-75"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right: sidebar */}
                    <div className="space-y-5">
                        {/* Case metadata */}
                        <div className="border-[3px] border-ink bg-white shadow-brutal">
                            <div className="bg-ink px-5 py-3">
                                <h2 className="font-black text-white text-sm uppercase tracking-widest">
                                    Case Info
                                </h2>
                            </div>
                            <div className="divide-y-2 divide-ink">
                                {[
                                    ['Type',        (c.case_type ?? '—').charAt(0).toUpperCase() + (c.case_type ?? '—').slice(1)],
                                    ['Track',       c.track ? c.track.charAt(0).toUpperCase() + c.track.slice(1) : '—'],
                                    ['Filed',       c.filing_date ? new Date(c.filing_date).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'],
                                    ['Next Hearing', c.next_hearing_date ? new Date(c.next_hearing_date).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' }) : '—'],
                                    ['Hearings',    c.hearing_count ?? 0],
                                    ['Adjournments', c.adjournment_count ?? 0],
                                ].map(([label, value]) => (
                                    <div key={label} className="px-5 py-2.5 flex justify-between">
                                        <span className="text-[10px] font-black uppercase tracking-widest text-ash">{label}</span>
                                        <span className="text-sm font-black text-ink">{value}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Upcoming hearing */}
                        {c.next_hearing_date && (
                            <div className="border-[3px] border-ink bg-mint p-5 shadow-brutal">
                                <p className="text-[10px] font-black uppercase tracking-widest text-ink mb-1">
                                    Next Hearing
                                </p>
                                <p className="text-2xl font-black text-ink">
                                    {new Date(c.next_hearing_date).toLocaleDateString('en-IN', {
                                        day: '2-digit', month: 'short', year: 'numeric',
                                    })}
                                </p>
                            </div>
                        )}

                        {/* Submit reminder */}
                        {c.status === 'draft' && (c.documents ?? []).length === 0 && (
                            <div className="border-[3px] border-ink bg-volt p-5 shadow-brutal">
                                <p className="text-[10px] font-black uppercase tracking-widest text-ink mb-1">
                                    Action Required
                                </p>
                                <p className="text-sm font-black text-ink">
                                    Attach at least one document to enable submission.
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Modals */}
                {uploadOpen && (
                    <UploadModal
                        caseId={id}
                        onClose={() => setUploadOpen(false)}
                        onDone={() => { setUploadOpen(false); load(); }}
                    />
                )}
                {pdfViewer && (
                    <PdfModal
                        url={pdfViewer.url}
                        label={pdfViewer.label}
                        onClose={() => {
                            URL.revokeObjectURL(pdfViewer.url);
                            setPdfViewer(null);
                        }}
                    />
                )}
            </Layout>
        </ProtectedRoute>
    );
}
