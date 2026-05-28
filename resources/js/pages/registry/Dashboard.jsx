import { useEffect, useState } from 'react';
import { registryApi } from '@/services/api';
import Layout, { ProtectedRoute } from '@/components/Layout';
import StatusBadge from '@/components/ui/StatusBadge';
import Button from '@/components/ui/Button';
import Input, { Select } from '@/components/ui/Input';
import toast from 'react-hot-toast';

// ─── Constants ────────────────────────────────────────────────
const DEFECT_OPTIONS = [
    { code: 'MISSING_AFFIDAVIT',    label: 'Affidavit of verification missing' },
    { code: 'INCOMPLETE_PARTIES',   label: 'Party details incomplete' },
    { code: 'MISSING_DOCUMENTS',    label: 'Required documents not attached' },
    { code: 'INCOMPLETE_ADDRESS',   label: 'Address of party incomplete' },
    { code: 'COURT_FEE_PENDING',    label: 'Court fee not paid / receipt missing' },
    { code: 'SIGNATURE_MISSING',    label: 'Petitioner / advocate signature missing' },
    { code: 'WRONG_JURISDICTION',   label: "Case does not fall in this court's jurisdiction" },
];

// ─── PDF Viewer ───────────────────────────────────────────────
function PdfViewer({ caseId, doc, onClose }) {
    const [blobUrl, setBlobUrl] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let url;
        registryApi.getDocumentBlob(caseId, doc.doc_id)
            .then(r => {
                url = URL.createObjectURL(r.data);
                setBlobUrl(url);
            })
            .catch(() => toast.error('Could not load document.'))
            .finally(() => setLoading(false));
        return () => { if (url) URL.revokeObjectURL(url); };
    }, [caseId, doc.doc_id]);

    return (
        <div className="fixed inset-0 z-[60] bg-ink/90 flex flex-col">
            <div className="flex items-center justify-between bg-ink px-5 py-3 border-b-[3px] border-volt shrink-0">
                <div>
                    <p className="text-[10px] font-black uppercase tracking-widest text-volt">Document</p>
                    <p className="text-sm font-black text-white">{doc.label}</p>
                </div>
                <div className="flex gap-3">
                    {blobUrl && (
                        <a href={blobUrl} target="_blank" rel="noreferrer"
                            className="px-3 py-1.5 bg-volt text-ink border-2 border-volt text-[10px] font-black uppercase tracking-widest hover:bg-white transition-colors">
                            Open in Tab ↗
                        </a>
                    )}
                    <button onClick={onClose}
                        className="px-3 py-1.5 bg-harsh text-white border-2 border-harsh text-[10px] font-black uppercase tracking-widest">
                        Close ✕
                    </button>
                </div>
            </div>
            {loading ? (
                <div className="flex-1 flex items-center justify-center">
                    <p className="text-white font-black text-sm">Loading document…</p>
                </div>
            ) : blobUrl ? (
                <iframe src={blobUrl} className="flex-1 w-full bg-white" title={doc.label} />
            ) : (
                <div className="flex-1 flex items-center justify-center">
                    <p className="text-white font-black text-sm">Failed to load document.</p>
                </div>
            )}
        </div>
    );
}

// ─── Approve Modal ────────────────────────────────────────────
function ApproveModal({ c, onClose, onDone }) {
    const [judges, setJudges] = useState([]);
    const [form, setForm] = useState({
        case_type: c.case_type ?? 'civil',
        track: 'regular',
        assigned_judge_id: '',
        priority_score: '',
    });
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        registryApi.judges().then(r => {
            const list = r.data.judges ?? [];
            setJudges(list);
            if (list.length) setForm(f => ({ ...f, assigned_judge_id: list[0].id }));
        });
    }, []);

    const submit = async (e) => {
        e.preventDefault();
        if (!form.assigned_judge_id) { toast.error('Assign a judge.'); return; }
        setLoading(true);
        try {
            const { data } = await registryApi.approve(c.id, {
                case_type:         form.case_type,
                track:             form.track,
                assigned_judge_id: form.assigned_judge_id,
                priority_score:    form.priority_score || undefined,
            });
            toast.success(`Case approved. Hearing: ${data.next_hearing_date}`);
            onDone();
        } catch (err) {
            toast.error(err.response?.data?.message ?? 'Approval failed.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-ink/70 flex items-center justify-center z-[70] p-4">
            <div className="w-full max-w-lg border-[3px] border-ink bg-white shadow-brutal-lg">
                <div className="bg-mint px-6 py-4 border-b-[3px] border-ink">
                    <h2 className="text-lg font-black text-ink">Approve & Schedule</h2>
                    <p className="text-[10px] font-bold text-ink/60 mt-0.5 truncate">{c.title}</p>
                </div>
                <form onSubmit={submit} className="p-6 space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <Select label="Case Type" value={form.case_type}
                            onChange={e => setForm(f => ({ ...f, case_type: e.target.value }))}>
                            {['civil', 'criminal', 'family', 'commercial', 'writ'].map(t => (
                                <option key={t} value={t} className="capitalize">{t.charAt(0).toUpperCase() + t.slice(1)}</option>
                            ))}
                        </Select>
                        <Select label="Track" value={form.track}
                            onChange={e => setForm(f => ({ ...f, track: e.target.value }))}>
                            <option value="regular">Regular (60 days)</option>
                            <option value="fast">Fast Track (30 days)</option>
                            <option value="urgent">Urgent (7 days)</option>
                        </Select>
                    </div>

                    <Select label="Assign Judge" value={form.assigned_judge_id}
                        onChange={e => setForm(f => ({ ...f, assigned_judge_id: e.target.value }))}
                        required>
                        {judges.length === 0
                            ? <option value="">Loading judges…</option>
                            : judges.map(j => (
                                <option key={j.id} value={j.id}>
                                    {j.name}{j.designation ? ` — ${j.designation}` : ''}
                                </option>
                            ))
                        }
                    </Select>

                    <Input
                        label="Priority Score (optional, 0–100)"
                        type="number" min="0" max="100"
                        placeholder="Auto-computed if left blank"
                        value={form.priority_score}
                        onChange={e => setForm(f => ({ ...f, priority_score: e.target.value }))}
                    />
                    <div className="flex gap-3 pt-1">
                        <Button variant="mint" type="submit" disabled={loading || !judges.length} className="flex-1">
                            {loading ? 'Approving…' : '✓ Approve & Schedule'}
                        </Button>
                        <Button variant="ghost" type="button" onClick={onClose} className="flex-1">Cancel</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ─── Reject Modal ─────────────────────────────────────────────
function RejectModal({ c, onClose, onDone }) {
    const [selected, setSelected] = useState([]);
    const [note, setNote]         = useState('');
    const [loading, setLoading]   = useState(false);

    const toggle = (code) => setSelected(prev =>
        prev.includes(code) ? prev.filter(x => x !== code) : [...prev, code]
    );

    const submit = async (e) => {
        e.preventDefault();
        if (selected.length === 0) { toast.error('Select at least one defect.'); return; }
        setLoading(true);
        try {
            const flags = selected.map(code => ({
                code, label: DEFECT_OPTIONS.find(o => o.code === code)?.label ?? code,
            }));
            await registryApi.reject(c.id, { defect_flags: flags, rejection_note: note });
            toast.success('Case returned with defect flags.');
            onDone();
        } catch (err) {
            toast.error(err.response?.data?.message ?? 'Rejection failed.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-ink/70 flex items-center justify-center z-[70] p-4">
            <div className="w-full max-w-lg border-[3px] border-ink bg-white shadow-brutal-lg">
                <div className="bg-harsh px-6 py-4 border-b-[3px] border-ink">
                    <h2 className="text-lg font-black text-white">Return as Defective</h2>
                    <p className="text-[10px] font-bold text-orange-200 mt-0.5 truncate">{c.title}</p>
                </div>
                <form onSubmit={submit} className="p-6 space-y-4">
                    <div>
                        <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-3">
                            Defects Found (select all that apply)
                        </p>
                        <div className="space-y-2">
                            {DEFECT_OPTIONS.map(({ code, label }) => (
                                <label key={code} className="flex items-start gap-3 cursor-pointer group"
                                    onClick={() => toggle(code)}>
                                    <div className={`mt-0.5 w-4 h-4 shrink-0 border-2 border-ink flex items-center justify-center transition-colors
                                        ${selected.includes(code) ? 'bg-harsh' : 'bg-white group-hover:bg-concrete'}`}>
                                        {selected.includes(code) && <span className="text-white text-[10px] font-black">✓</span>}
                                    </div>
                                    <span className="text-sm font-bold text-ink leading-snug">{label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[10px] font-black uppercase tracking-widest text-ash">
                            Note to Filer (optional)
                        </label>
                        <textarea rows={3} value={note} onChange={e => setNote(e.target.value)}
                            className="border-2 border-ink px-3 py-2 text-sm font-bold resize-none focus:outline-none focus:shadow-brutal" />
                    </div>
                    <div className="flex gap-3 pt-1">
                        <Button variant="harsh" type="submit" disabled={loading} className="flex-1">
                            {loading ? 'Returning…' : '✕ Return with Defects'}
                        </Button>
                        <Button variant="ghost" type="button" onClick={onClose} className="flex-1">Cancel</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ─── Case Review Panel ────────────────────────────────────────
function CaseReviewPanel({ caseId, onClose, onApprove, onReject }) {
    const [caseData, setCaseData] = useState(null);
    const [loading, setLoading]   = useState(true);
    const [tab, setTab]           = useState('details'); // 'details' | 'documents'
    const [pdfDoc, setPdfDoc]     = useState(null);

    useEffect(() => {
        registryApi.getCase(caseId)
            .then(r => setCaseData(r.data.case))
            .catch(() => { toast.error('Could not load case.'); onClose(); })
            .finally(() => setLoading(false));
    }, [caseId]);

    if (loading) return (
        <div className="fixed inset-0 z-50 bg-ink/60 flex items-center justify-center">
            <div className="bg-white border-[3px] border-ink px-8 py-6 shadow-brutal">
                <p className="font-black text-ink text-sm uppercase tracking-widest">Loading case…</p>
            </div>
        </div>
    );

    if (!caseData) return null;
    const c = caseData;
    const docs = c.documents ?? [];

    return (
        <>
            {/* Backdrop */}
            <div className="fixed inset-0 z-50 bg-ink/50" onClick={onClose} />

            {/* Panel */}
            <div className="fixed inset-y-0 right-0 z-50 w-full max-w-2xl flex flex-col bg-white border-l-[3px] border-ink shadow-brutal-lg">

                {/* Header */}
                <div className="bg-ink px-6 py-4 shrink-0 border-b-[3px] border-ink">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <p className="text-[10px] font-black uppercase tracking-widest text-ash">
                                {c.case_number ?? 'Awaiting Case No.'}
                            </p>
                            <h2 className="text-lg font-black text-white leading-tight mt-0.5 line-clamp-2">
                                {c.title}
                            </h2>
                        </div>
                        <button onClick={onClose}
                            className="shrink-0 mt-1 px-3 py-1.5 border-2 border-ink bg-white text-ink text-[10px] font-black uppercase tracking-widest hover:bg-chalk transition-colors">
                            Close ✕
                        </button>
                    </div>

                    {/* Tabs */}
                    <div className="flex gap-1 mt-4">
                        {[['details', 'Case Details'], ['documents', `Documents (${docs.length})`]].map(([key, label]) => (
                            <button key={key} onClick={() => setTab(key)}
                                className={`px-4 py-1.5 text-[10px] font-black uppercase tracking-widest border-2 transition-all duration-75
                                    ${tab === key
                                        ? 'bg-volt text-ink border-volt'
                                        : 'bg-transparent text-ash border-transparent hover:border-ash'
                                    }`}>
                                {label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Body */}
                <div className="flex-1 overflow-y-auto">
                    {tab === 'details' && (
                        <div className="p-6 space-y-5">
                            {/* Meta */}
                            <div className="grid grid-cols-2 gap-3">
                                {[
                                    ['Status', <StatusBadge status={c.status} />],
                                    ['Type', <span className="font-black text-ink capitalize">{c.case_type ?? '—'}</span>],
                                    ['Filed', <span className="font-black text-ink">{c.filing_date ? new Date(c.filing_date + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'}</span>],
                                    ['Priority', c.is_high_priority
                                        ? <span className="px-2 py-0.5 bg-harsh text-white border border-ink text-[9px] font-black uppercase">High</span>
                                        : <span className="font-black text-ash">Normal</span>],
                                ].map(([label, val]) => (
                                    <div key={label} className="border-2 border-ink p-3">
                                        <p className="text-[9px] font-black uppercase tracking-widest text-ash mb-1">{label}</p>
                                        {val}
                                    </div>
                                ))}
                            </div>

                            {/* Description */}
                            <div className="border-[3px] border-ink p-4">
                                <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-2">
                                    Description / Facts
                                </p>
                                <p className="text-sm font-bold text-ink leading-relaxed whitespace-pre-line">
                                    {c.description || '—'}
                                </p>
                            </div>

                            {/* Parties */}
                            <div className="border-[3px] border-ink">
                                <div className="bg-ink px-4 py-2">
                                    <p className="text-[10px] font-black uppercase tracking-widest text-chalk">
                                        Parties ({(c.parties ?? []).length})
                                    </p>
                                </div>
                                <div className="divide-y-2 divide-ink">
                                    {(c.parties ?? []).map((p, i) => (
                                        <div key={i} className="px-4 py-3 flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-[9px] font-black uppercase tracking-widest text-ash">
                                                    {p.party_type}
                                                </p>
                                                <p className="font-black text-ink text-sm">{p.name}</p>
                                                <p className="text-xs font-bold text-ash mt-0.5 leading-snug">{p.address}</p>
                                            </div>
                                            <div className="flex flex-col gap-1 items-end shrink-0">
                                                {p.is_senior_citizen && (
                                                    <span className="px-1.5 py-0.5 bg-volt text-ink border border-ink text-[8px] font-black uppercase tracking-widest">Senior</span>
                                                )}
                                                {p.is_in_custody && (
                                                    <span className="px-1.5 py-0.5 bg-harsh text-white border border-ink text-[8px] font-black uppercase tracking-widest">Custody</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {tab === 'documents' && (
                        <div className="p-6">
                            {docs.length === 0 ? (
                                <div className="border-[3px] border-ink p-8 text-center">
                                    <p className="font-black text-ink">No documents attached.</p>
                                    <p className="text-sm text-ash mt-1 font-bold">
                                        This may be a defect — filer submitted without any documents.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {docs.map((doc, i) => (
                                        <div key={doc.doc_id ?? i}
                                            className="border-[3px] border-ink bg-white p-4 flex items-center justify-between gap-4 shadow-brutal-sm">
                                            <div className="flex items-center gap-3 min-w-0">
                                                <div className="w-10 h-10 shrink-0 bg-harsh border-2 border-ink flex items-center justify-center shadow-brutal-sm">
                                                    <span className="text-[9px] font-black text-white uppercase">
                                                        {(doc.mime_type ?? '').includes('pdf') ? 'PDF' : 'DOC'}
                                                    </span>
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="font-black text-ink text-sm truncate">{doc.label}</p>
                                                    <p className="text-[10px] font-bold text-ash uppercase tracking-widest">
                                                        {doc.is_verified ? '✓ Verified' : 'Unverified'}
                                                        {doc.uploaded_at && ` · ${new Date(doc.uploaded_at).toLocaleDateString('en-IN')}`}
                                                    </p>
                                                </div>
                                            </div>
                                            <button
                                                onClick={() => setPdfDoc(doc)}
                                                className="shrink-0 px-4 py-2 border-2 border-ink text-[10px] font-black uppercase tracking-widest bg-brutal text-white shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75">
                                                View PDF
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Footer — action buttons */}
                <div className="shrink-0 border-t-[3px] border-ink p-4 bg-chalk flex gap-3">
                    <button
                        onClick={() => onApprove(c)}
                        className="flex-1 py-3 border-2 border-ink bg-mint text-ink text-[11px] font-black uppercase tracking-widest shadow-brutal hover:translate-x-[3px] hover:translate-y-[3px] hover:shadow-none transition-all duration-75">
                        ✓ Approve & Schedule
                    </button>
                    <button
                        onClick={() => onReject(c)}
                        className="flex-1 py-3 border-2 border-ink bg-harsh text-white text-[11px] font-black uppercase tracking-widest shadow-brutal hover:translate-x-[3px] hover:translate-y-[3px] hover:shadow-none transition-all duration-75">
                        ✕ Return Defective
                    </button>
                </div>
            </div>

            {/* PDF Viewer (above panel) */}
            {pdfDoc && (
                <PdfViewer caseId={caseId} doc={pdfDoc} onClose={() => setPdfDoc(null)} />
            )}
        </>
    );
}

// ─── Queue Row ────────────────────────────────────────────────
function QueueRow({ c, onReview, onApprove, onReject }) {
    const age = c.filing_date
        ? Math.floor((Date.now() - new Date(c.filing_date + 'T00:00:00')) / 86400000)
        : null;

    return (
        <div className="border-[3px] border-ink bg-white p-5 shadow-brutal-sm hover:shadow-brutal transition-shadow duration-100">
            <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
                <div className="min-w-0">
                    <p className="text-[10px] font-black uppercase tracking-widest text-ash">
                        {c.case_number ?? 'Pending No.'}
                    </p>
                    <h3 className="font-black text-sm text-ink mt-0.5 line-clamp-2">{c.title}</h3>
                </div>
                <StatusBadge status={c.status} />
            </div>

            <div className="flex flex-wrap gap-4 text-xs mb-4">
                <div>
                    <p className="text-[9px] font-black uppercase tracking-widest text-ash">Type</p>
                    <p className="font-bold text-ink capitalize">{c.case_type ?? '—'}</p>
                </div>
                <div>
                    <p className="text-[9px] font-black uppercase tracking-widest text-ash">Filed</p>
                    <p className="font-bold text-ink">{age !== null ? `${age}d ago` : '—'}</p>
                </div>
                <div>
                    <p className="text-[9px] font-black uppercase tracking-widest text-ash">Docs</p>
                    <p className={`font-bold ${(c.documents?.length ?? 0) === 0 ? 'text-harsh' : 'text-ink'}`}>
                        {c.documents?.length ?? 0}
                    </p>
                </div>
                <div>
                    <p className="text-[9px] font-black uppercase tracking-widest text-ash">Parties</p>
                    <p className="font-bold text-ink">{c.parties?.length ?? 0}</p>
                </div>
            </div>

            <div className="flex gap-2">
                <button
                    onClick={() => onReview(c.id)}
                    className="flex-1 py-2 border-2 border-ink bg-volt text-ink text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75">
                    Review Case →
                </button>
                <button
                    onClick={() => onApprove(c)}
                    className="py-2 px-4 border-2 border-ink bg-mint text-ink text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75">
                    ✓
                </button>
                <button
                    onClick={() => onReject(c)}
                    className="py-2 px-4 border-2 border-ink bg-harsh text-white text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75">
                    ✕
                </button>
            </div>
            <p className="text-[9px] font-bold text-ash mt-2">✓ = Quick approve &nbsp;·&nbsp; ✕ = Quick reject &nbsp;·&nbsp; Review = Full case view</p>
        </div>
    );
}

// ─── Main Dashboard ───────────────────────────────────────────
export default function RegistryDashboard() {
    const [queue,         setQueue]         = useState([]);
    const [loading,       setLoading]       = useState(true);
    const [reviewId,      setReviewId]      = useState(null);
    const [approveTarget, setApproveTarget] = useState(null);
    const [rejectTarget,  setRejectTarget]  = useState(null);

    const load = () => {
        setLoading(true);
        registryApi.queue()
            .then(r => setQueue(r.data.data ?? r.data))
            .catch(() => toast.error('Failed to load queue.'))
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, []);

    const handleDone = () => {
        setApproveTarget(null);
        setRejectTarget(null);
        setReviewId(null);
        load();
    };

    return (
        <ProtectedRoute role="registry">
            <Layout>
                <div className="space-y-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p className="text-[10px] font-black uppercase tracking-widest text-ash">Registry</p>
                            <h1 className="text-3xl font-black text-ink">Scrutiny Queue</h1>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="px-4 py-2 border-2 border-ink bg-volt text-ink text-xs font-black uppercase tracking-widest shadow-brutal-sm">
                                {queue.length} Pending
                            </span>
                            <button onClick={load}
                                className="px-4 py-2 border-2 border-ink bg-white text-ink text-xs font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all">
                                Refresh
                            </button>
                        </div>
                    </div>

                    {loading ? (
                        <p className="text-sm font-bold text-ash">Loading queue…</p>
                    ) : queue.length === 0 ? (
                        <div className="border-[3px] border-ink bg-white p-10 text-center shadow-brutal">
                            <p className="font-black text-ink text-lg">Queue is clear.</p>
                            <p className="text-sm text-ash mt-1">No cases awaiting scrutiny.</p>
                        </div>
                    ) : (
                        <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                            {queue.map((c) => (
                                <QueueRow
                                    key={c.id}
                                    c={c}
                                    onReview={setReviewId}
                                    onApprove={setApproveTarget}
                                    onReject={setRejectTarget}
                                />
                            ))}
                        </div>
                    )}
                </div>

                {reviewId && (
                    <CaseReviewPanel
                        caseId={reviewId}
                        onClose={() => setReviewId(null)}
                        onApprove={(c) => { setApproveTarget(c); }}
                        onReject={(c) => { setRejectTarget(c); }}
                    />
                )}

                {approveTarget && (
                    <ApproveModal c={approveTarget} onClose={() => setApproveTarget(null)} onDone={handleDone} />
                )}
                {rejectTarget && (
                    <RejectModal c={rejectTarget} onClose={() => setRejectTarget(null)} onDone={handleDone} />
                )}
            </Layout>
        </ProtectedRoute>
    );
}
