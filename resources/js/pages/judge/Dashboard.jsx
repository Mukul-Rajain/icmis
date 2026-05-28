import { useEffect, useState, useCallback } from 'react';
import { useLocation } from 'react-router-dom';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragOverlay,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { judgeApi } from '@/services/api';
import Layout, { ProtectedRoute } from '@/components/Layout';
import StatusBadge from '@/components/ui/StatusBadge';
import Button from '@/components/ui/Button';
import echo from '@/services/echo';
import { useAuth } from '@/context/AuthContext';
import toast from 'react-hot-toast';

// ── PDF Viewer ─────────────────────────────────────────────────
function PdfViewer({ caseId, doc, onClose }) {
    const [blobUrl, setBlobUrl] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let url;
        judgeApi.getDocumentBlob(caseId, doc.doc_id)
            .then(r => { url = URL.createObjectURL(r.data); setBlobUrl(url); })
            .catch(() => toast.error('Could not load document.'))
            .finally(() => setLoading(false));
        return () => { if (url) URL.revokeObjectURL(url); };
    }, [caseId, doc.doc_id]);

    return (
        <div className="fixed inset-0 z-[70] bg-ink/90 flex flex-col">
            <div className="flex items-center justify-between bg-ink px-5 py-3 border-b-[3px] border-brutal shrink-0">
                <div>
                    <p className="text-[10px] font-black uppercase tracking-widest text-brutal">Document</p>
                    <p className="text-sm font-black text-white">{doc.label}</p>
                </div>
                <div className="flex gap-3">
                    {blobUrl && (
                        <a href={blobUrl} target="_blank" rel="noreferrer"
                            className="px-3 py-1.5 bg-brutal text-white border-2 border-brutal text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-brutal transition-colors">
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
                    <p className="text-white font-black text-sm animate-pulse">Loading document…</p>
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

// ── Case Preview Panel ─────────────────────────────────────────
function CasePreviewPanel({ caseId, onClose }) {
    const [data,    setData]    = useState(null);
    const [loading, setLoading] = useState(true);
    const [tab,     setTab]     = useState('details');
    const [pdfDoc,  setPdfDoc]  = useState(null);

    useEffect(() => {
        judgeApi.preview(caseId)
            .then(r => setData(r.data))
            .catch(() => { toast.error('Could not load case.'); onClose(); })
            .finally(() => setLoading(false));
    }, [caseId]);

    if (loading) return (
        <div className="fixed inset-0 z-50 bg-ink/60 flex items-center justify-center">
            <div className="bg-white border-[3px] border-ink px-8 py-6 shadow-brutal">
                <p className="font-black text-ink text-sm uppercase tracking-widest animate-pulse">Loading case…</p>
            </div>
        </div>
    );

    if (!data) return null;
    const c = data;
    const docs     = c.documents ?? [];
    const parties  = c.parties   ?? [];
    const hearings = c.hearings  ?? [];

    const TABS = [
        ['details',   'Case Details'],
        ['documents', `Documents (${docs.length})`],
        ['history',   `Hearing History (${hearings.length})`],
    ];

    return (
        <>
            <div className="fixed inset-0 z-50 bg-ink/50" onClick={onClose} />

            <div className="fixed inset-y-0 right-0 z-50 w-full max-w-2xl flex flex-col bg-white border-l-[3px] border-ink shadow-brutal-lg">

                {/* Header */}
                <div className="bg-ink px-6 py-4 shrink-0">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <div className="flex items-center gap-2 mb-1">
                                <p className="text-[10px] font-black uppercase tracking-widest text-ash">
                                    {c.case_number}
                                </p>
                                {c.is_high_priority && (
                                    <span className="px-2 py-0.5 bg-harsh text-white border border-harsh text-[9px] font-black uppercase tracking-widest">
                                        HIGH PRIORITY
                                    </span>
                                )}
                                {c.is_overdue && (
                                    <span className="px-2 py-0.5 bg-harsh text-white border border-harsh text-[9px] font-black uppercase tracking-widest animate-pulse">
                                        OVERDUE
                                    </span>
                                )}
                            </div>
                            <h2 className="text-lg font-black text-white leading-tight line-clamp-2">{c.title}</h2>
                            <div className="flex flex-wrap items-center gap-3 mt-2">
                                <span className="text-[10px] font-black text-ash uppercase tracking-widest">
                                    {c.case_type} · {c.track} track
                                </span>
                                <span className="text-[10px] font-black text-ash">
                                    Age: {c.age_in_days} days
                                </span>
                                <span className="text-[10px] font-black text-ash">
                                    Score: {c.priority_score}
                                </span>
                            </div>
                        </div>
                        <button onClick={onClose}
                            className="shrink-0 mt-1 px-3 py-1.5 border-2 border-ink bg-white text-ink text-[10px] font-black uppercase tracking-widest hover:bg-chalk transition-colors">
                            Close ✕
                        </button>
                    </div>

                    {/* Tabs */}
                    <div className="flex gap-1 mt-4">
                        {TABS.map(([key, label]) => (
                            <button key={key} onClick={() => setTab(key)}
                                className={`px-4 py-1.5 text-[10px] font-black uppercase tracking-widest border-2 transition-all duration-75
                                    ${tab === key
                                        ? 'bg-brutal text-white border-brutal'
                                        : 'bg-transparent text-ash border-transparent hover:border-ash'
                                    }`}>
                                {label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Body */}
                <div className="flex-1 overflow-y-auto">

                    {/* ── Details tab ── */}
                    {tab === 'details' && (
                        <div className="p-6 space-y-5">
                            {/* Meta grid */}
                            <div className="grid grid-cols-3 gap-2">
                                {[
                                    ['Status',       c.status?.replace('_', ' ')],
                                    ['Type',         c.case_type],
                                    ['Track',        c.track],
                                    ['Filed',        c.filing_date ? new Date(c.filing_date + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'],
                                    ['Hearings',     c.hearing_count ?? 0],
                                    ['Adjournments', c.adjournment_count ?? 0],
                                ].map(([label, val]) => (
                                    <div key={label} className="border-2 border-ink p-2.5">
                                        <p className="text-[9px] font-black uppercase tracking-widest text-ash mb-1">{label}</p>
                                        <p className="text-sm font-black text-ink capitalize">{val ?? '—'}</p>
                                    </div>
                                ))}
                            </div>

                            {/* Description */}
                            <div className="border-[3px] border-ink p-4">
                                <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-3">
                                    Description / Facts
                                </p>
                                <p className="text-sm font-bold text-ink leading-relaxed whitespace-pre-line">
                                    {c.description || '—'}
                                </p>
                            </div>

                            {/* Parties */}
                            <div className="border-[3px] border-ink">
                                <div className="bg-ink px-4 py-2.5">
                                    <p className="text-[10px] font-black uppercase tracking-widest text-chalk">
                                        Parties ({parties.length})
                                    </p>
                                </div>
                                <div className="divide-y-2 divide-ink">
                                    {parties.map((p, i) => (
                                        <div key={i} className="px-4 py-3 flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-[9px] font-black uppercase tracking-widest text-ash mb-0.5">
                                                    {p.party_type}
                                                </p>
                                                <p className="font-black text-ink text-sm">{p.name}</p>
                                                <p className="text-xs font-bold text-ash mt-0.5 leading-snug">{p.address}</p>
                                            </div>
                                            <div className="flex gap-1.5 shrink-0 flex-col items-end">
                                                {p.is_senior_citizen && (
                                                    <span className="px-2 py-0.5 bg-volt text-ink border border-ink text-[8px] font-black uppercase">Senior</span>
                                                )}
                                                {p.is_in_custody && (
                                                    <span className="px-2 py-0.5 bg-harsh text-white border border-ink text-[8px] font-black uppercase">Custody</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Lawyers */}
                            {(c.lawyers ?? []).length > 0 && (
                                <div className="border-[3px] border-ink">
                                    <div className="bg-ink px-4 py-2.5">
                                        <p className="text-[10px] font-black uppercase tracking-widest text-chalk">
                                            Lawyers on Record
                                        </p>
                                    </div>
                                    <div className="divide-y-2 divide-ink">
                                        {c.lawyers.map((l, i) => (
                                            <div key={i} className="px-4 py-3 flex items-center justify-between">
                                                <div>
                                                    <p className="text-[9px] font-black uppercase tracking-widest text-ash mb-0.5">
                                                        Representing: {l.representing}
                                                    </p>
                                                    <p className="font-black text-sm text-ink">
                                                        {l.user_id ?? 'On Record'}
                                                        {l.is_lead && <span className="ml-2 text-[9px] font-black text-ash uppercase">(Lead)</span>}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* ── Documents tab ── */}
                    {tab === 'documents' && (
                        <div className="p-6">
                            {docs.length === 0 ? (
                                <div className="border-[3px] border-ink p-8 text-center">
                                    <p className="font-black text-ink">No documents attached.</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {docs.map((doc, i) => (
                                        <div key={doc.doc_id ?? i}
                                            className="border-[3px] border-ink bg-white p-4 flex items-center justify-between gap-4 shadow-brutal-sm">
                                            <div className="flex items-center gap-3 min-w-0">
                                                <div className="w-10 h-10 shrink-0 bg-brutal border-2 border-ink flex items-center justify-center shadow-brutal-sm">
                                                    <span className="text-[9px] font-black text-white uppercase">
                                                        {(doc.mime_type ?? '').includes('pdf') ? 'PDF' : 'DOC'}
                                                    </span>
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="font-black text-ink text-sm truncate">{doc.label}</p>
                                                    <p className="text-[10px] font-bold text-ash uppercase tracking-widest mt-0.5">
                                                        {doc.is_verified ? '✓ Verified' : 'Unverified'}
                                                        {doc.uploaded_at && ` · ${new Date(doc.uploaded_at).toLocaleDateString('en-IN')}`}
                                                    </p>
                                                </div>
                                            </div>
                                            <button
                                                onClick={() => setPdfDoc(doc)}
                                                className="shrink-0 px-4 py-2 border-2 border-ink bg-brutal text-white text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75">
                                                Read ↗
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* ── History tab ── */}
                    {tab === 'history' && (
                        <div className="p-6">
                            {hearings.length === 0 ? (
                                <div className="border-[3px] border-ink p-8 text-center">
                                    <p className="font-black text-ink">No hearings recorded yet.</p>
                                    <p className="text-sm text-ash mt-1 font-bold">First hearing is today.</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {[...hearings].reverse().map((h, i) => (
                                        <div key={h.hearing_id ?? i} className="border-[3px] border-ink p-4 bg-white shadow-brutal-sm">
                                            <div className="flex items-start justify-between gap-3 mb-2">
                                                <div>
                                                    <p className="text-[9px] font-black uppercase tracking-widest text-ash">
                                                        {h.scheduled_at ? new Date(h.scheduled_at).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'}
                                                    </p>
                                                    <p className="font-black text-ink capitalize">{h.outcome?.replace('_', ' ') ?? '—'}</p>
                                                </div>
                                                <span className={`px-2 py-0.5 border-2 border-ink text-[9px] font-black uppercase tracking-widest
                                                    ${h.outcome === 'disposed' ? 'bg-ink text-white'
                                                    : h.outcome === 'adjourned' ? 'bg-volt text-ink'
                                                    : 'bg-mint text-ink'}`}>
                                                    {h.outcome?.replace('_', ' ') ?? '—'}
                                                </span>
                                            </div>
                                            {h.order_text && (
                                                <p className="text-sm font-bold text-ink leading-relaxed border-t-2 border-ink pt-2 mt-2">
                                                    {h.order_text}
                                                </p>
                                            )}
                                            {h.adjourned_to && (
                                                <p className="text-xs font-bold text-ash mt-1">
                                                    Adjourned to: {new Date(h.adjourned_to).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                    {h.adjournment_reason && ` — ${h.adjournment_reason}`}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {pdfDoc && (
                <PdfViewer caseId={caseId} doc={pdfDoc} onClose={() => setPdfDoc(null)} />
            )}
        </>
    );
}

// ── Sortable cause list row ────────────────────────────────────
function SortableRow({ c, onTogglePriority, onOpenMention, onPreview, isDragging }) {
    const { attributes, listeners, setNodeRef, transform, transition } = useSortable({ id: c.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition: transition ?? undefined,
        willChange: isDragging ? 'transform' : undefined,
        zIndex: isDragging ? 1 : undefined,
    };

    // Prevent dnd-kit's document-level PointerSensor from swallowing clicks on action buttons
    const noGrab = (e) => e.stopPropagation();

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`border-[3px] bg-white p-4 shadow-brutal-sm flex items-center gap-3
                ${c.is_high_priority ? 'border-harsh border-l-[6px]' : 'border-ink'}
                ${isDragging ? 'opacity-40' : 'opacity-100'}
            `}
        >
            {/* Drag handle — only this element is grabbable */}
            <div
                {...attributes}
                {...listeners}
                style={{ touchAction: 'none' }}
                className="cursor-grab active:cursor-grabbing text-ash hover:text-ink p-1 shrink-0 select-none"
                role="button"
                aria-label="Drag to reorder"
            >
                <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                    <circle cx="4" cy="3" r="1.2"/><circle cx="10" cy="3" r="1.2"/>
                    <circle cx="4" cy="7" r="1.2"/><circle cx="10" cy="7" r="1.2"/>
                    <circle cx="4" cy="11" r="1.2"/><circle cx="10" cy="11" r="1.2"/>
                </svg>
            </div>

            {/* Position badge */}
            <div className="w-7 h-7 border-2 border-ink bg-concrete flex items-center justify-center shrink-0">
                <span className="text-[10px] font-black text-ink">{c.cause_list_position ?? '—'}</span>
            </div>

            {/* Case info */}
            <div className="flex-1 min-w-0">
                <div className="flex flex-wrap items-center gap-2 mb-1">
                    <span className="text-[10px] font-black text-ash">{c.case_number}</span>
                    <StatusBadge status={c.status} />
                    {c.is_high_priority && (
                        <span className="px-1.5 py-0.5 bg-harsh text-white border border-ink text-[9px] font-black uppercase">HP</span>
                    )}
                    {c.pending_mention && (
                        <span className="px-1.5 py-0.5 bg-volt text-ink border border-ink text-[9px] font-black uppercase animate-pulse">
                            Mention ⚡
                        </span>
                    )}
                </div>
                <p className="font-bold text-sm text-ink line-clamp-1">{c.title}</p>
                <p className="text-[10px] text-ash font-bold mt-0.5">
                    {c.case_type} · {c.track} · Age: {c.age_in_days}d
                    {c.is_overdue && <span className="text-harsh"> · OVERDUE</span>}
                </p>
            </div>

            {/* Actions — stopPropagation on pointerDown so dnd-kit ignores these */}
            <div className="flex gap-2 shrink-0">
                <button
                    onPointerDown={noGrab}
                    onClick={() => onPreview(c.id)}
                    title="Read case details"
                    className="px-3 h-8 border-2 border-ink bg-brutal text-white text-[10px] font-black uppercase tracking-widest hover:bg-ink transition-colors duration-75"
                >
                    Read ↗
                </button>
                <button
                    onPointerDown={noGrab}
                    onClick={() => onTogglePriority(c.id)}
                    title={c.is_high_priority ? 'Remove priority' : 'Mark high priority'}
                    className={`w-8 h-8 border-2 border-ink flex items-center justify-center text-sm font-black transition-colors duration-75
                        ${c.is_high_priority ? 'bg-harsh text-white' : 'bg-white text-ink hover:bg-concrete'}`}
                >
                    ★
                </button>
                <button
                    onPointerDown={noGrab}
                    onClick={() => onOpenMention(c)}
                    title="View mentions"
                    className="w-8 h-8 border-2 border-ink bg-white text-ink flex items-center justify-center text-sm hover:bg-concrete transition-colors duration-75"
                >
                    ⚖
                </button>
            </div>
        </div>
    );
}

// ── Mention queue panel ────────────────────────────────────────
function MentionsPanel({ mentions, onAccept, onReject }) {
    if (mentions.length === 0) {
        return (
            <div className="border-[3px] border-ink bg-white p-6 shadow-brutal text-center">
                <p className="font-black text-ink">No pending mentions.</p>
                <p className="text-xs text-ash mt-1">Filer emergency requests will appear here.</p>
            </div>
        );
    }
    return (
        <div className="space-y-3">
            {mentions.map((m) => (
                <div
                    key={m.request.request_id}
                    className={`border-[3px] border-ink p-4 shadow-brutal-sm ${m.request.urgency === 'very_urgent' ? 'bg-harsh/10' : 'bg-white'}`}
                >
                    <div className="flex flex-wrap items-start justify-between gap-2 mb-2">
                        <div>
                            <span className={`px-2 py-0.5 border-2 border-ink text-[9px] font-black uppercase tracking-widest
                                ${m.request.urgency === 'very_urgent' ? 'bg-harsh text-white' : 'bg-volt text-ink'}`}>
                                {m.request.urgency.replace('_', ' ')}
                            </span>
                        </div>
                        <span className="text-[10px] font-bold text-ash">{m.case_number}</span>
                    </div>
                    <p className="font-bold text-sm text-ink mb-1 line-clamp-1">{m.case_title}</p>
                    <p className="text-xs text-ash font-bold mb-3 line-clamp-2">{m.request.reason}</p>
                    <div className="flex gap-2">
                        <button
                            onClick={() => onAccept(m)}
                            className="flex-1 py-1.5 border-2 border-ink bg-mint text-ink text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all"
                        >
                            Accept
                        </button>
                        <button
                            onClick={() => onReject(m)}
                            className="flex-1 py-1.5 border-2 border-ink bg-harsh text-white text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            ))}
        </div>
    );
}

// ── Accept / Reject mention modals ─────────────────────────────
function AcceptMentionModal({ mention, onClose, onDone }) {
    const [form, setForm] = useState({ hearing_date: '', judge_note: '' });
    const [loading, setLoading] = useState(false);
    const submit = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            await judgeApi.acceptMention(mention.case_id, mention.request.request_id, form);
            toast.success('Mention accepted. Filer notified.');
            onDone();
        } catch { toast.error('Failed to accept.'); }
        finally { setLoading(false); }
    };
    return (
        <div className="fixed inset-0 bg-ink/60 flex items-center justify-center z-50 p-4">
            <div className="w-full max-w-md border-[3px] border-ink bg-white shadow-brutal-lg">
                <div className="bg-mint border-b-[3px] border-ink px-6 py-4">
                    <h2 className="text-lg font-black text-ink">Accept Mention</h2>
                </div>
                <form onSubmit={submit} className="p-6 space-y-4">
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[10px] font-black uppercase tracking-widest text-ash">New Hearing Date</label>
                        <input type="datetime-local" required value={form.hearing_date}
                            onChange={e => setForm(f=>({...f, hearing_date: e.target.value}))}
                            className="border-2 border-ink px-3 py-2.5 text-sm font-bold focus:outline-none focus:shadow-brutal" />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[10px] font-black uppercase tracking-widest text-ash">Note to Filer (optional)</label>
                        <textarea rows={3} value={form.judge_note} onChange={e => setForm(f=>({...f, judge_note: e.target.value}))}
                            className="border-2 border-ink px-3 py-2 text-sm font-bold resize-none focus:outline-none focus:shadow-brutal" />
                    </div>
                    <div className="flex gap-3">
                        <Button variant="mint" type="submit" disabled={loading} className="flex-1">
                            {loading ? 'Saving…' : '✓ Confirm Accept'}
                        </Button>
                        <Button variant="ghost" type="button" onClick={onClose} className="flex-1">Cancel</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function RejectMentionModal({ mention, onClose, onDone }) {
    const [note, setNote] = useState('');
    const [loading, setLoading] = useState(false);
    const submit = async (e) => {
        e.preventDefault();
        if (!note.trim()) { toast.error('Provide a reason for rejection.'); return; }
        setLoading(true);
        try {
            await judgeApi.rejectMention(mention.case_id, mention.request.request_id, { judge_note: note });
            toast.success('Mention rejected. Filer notified.');
            onDone();
        } catch { toast.error('Failed to reject.'); }
        finally { setLoading(false); }
    };
    return (
        <div className="fixed inset-0 bg-ink/60 flex items-center justify-center z-50 p-4">
            <div className="w-full max-w-md border-[3px] border-ink bg-white shadow-brutal-lg">
                <div className="bg-harsh border-b-[3px] border-ink px-6 py-4">
                    <h2 className="text-lg font-black text-white">Reject Mention</h2>
                </div>
                <form onSubmit={submit} className="p-6 space-y-4">
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[10px] font-black uppercase tracking-widest text-ash">Reason for Rejection</label>
                        <textarea rows={4} required value={note} onChange={e => setNote(e.target.value)}
                            placeholder="State why the emergency mention is not warranted…"
                            className="border-2 border-ink px-3 py-2 text-sm font-bold resize-none focus:outline-none focus:shadow-brutal" />
                    </div>
                    <div className="flex gap-3">
                        <Button variant="harsh" type="submit" disabled={loading} className="flex-1">
                            {loading ? 'Saving…' : '✕ Confirm Reject'}
                        </Button>
                        <Button variant="ghost" type="button" onClick={onClose} className="flex-1">Cancel</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ── Main Judge Dashboard ───────────────────────────────────────
export default function JudgeDashboard() {
    const { user }   = useAuth();
    const location   = useLocation();
    const [cases,    setCases]    = useState([]);
    const [mentions, setMentions] = useState([]);
    const [activeId, setActiveId] = useState(null);
    const [tab,      setTab]      = useState(
        location.pathname.includes('mentions') ? 'mentions' : 'causelist'
    );
    const [acceptM,  setAcceptM]  = useState(null);
    const [rejectM,  setRejectM]  = useState(null);
    const [previewId, setPreviewId] = useState(null);

    // Sync tab when the nav link changes the URL
    useEffect(() => {
        setTab(location.pathname.includes('mentions') ? 'mentions' : 'causelist');
    }, [location.pathname]);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
    );

    const [dailyLimit, setDailyLimit] = useState(20);

    const loadCauseList = useCallback(() => {
        judgeApi.causeList()
            .then(r => {
                setCases(r.data.cases ?? []);
                setDailyLimit(r.data.daily_limit ?? 20);
            })
            .catch(() => toast.error('Failed to load cause list.'));
    }, []);

    const loadMentions = useCallback(() => {
        judgeApi.mentionQueue()
            .then(r => setMentions(r.data.mentions ?? []))
            .catch(() => {});
    }, []);

    useEffect(() => { loadCauseList(); loadMentions(); }, []);

    // Real-time: sync when another filer watches this court's causelist
    useEffect(() => {
        const courtId = user?.judge_profile?.court_id;
        if (!courtId) return;
        const ch = echo.channel(`court.${courtId}`);
        ch.listen('.causelist.updated', () => { loadCauseList(); });
        return () => echo.leaveChannel(`court.${courtId}`);
    }, [user]);

    const handleDragEnd = async ({ active, over }) => {
        setActiveId(null);
        if (!over || active.id === over.id) return;

        const oldIndex = cases.findIndex(c => c.id === active.id);
        const newIndex = cases.findIndex(c => c.id === over.id);
        const reordered = arrayMove(cases, oldIndex, newIndex);
        // Apply new order and update position badges immediately
        setCases(reordered.map((c, i) => ({ ...c, cause_list_position: i + 1 })));

        try {
            await judgeApi.reorder(reordered.map(c => c.id));
            toast.success('Order saved.', { duration: 1500 });
            loadCauseList(); // sync server-assigned positions
        } catch {
            toast.error('Could not save order.');
            loadCauseList();
        }
    };

    const togglePriority = async (id) => {
        // Optimistic: flip star and re-sort locally (HP cases float to top)
        const previous = cases.find(c => c.id === id)?.is_high_priority;
        setCases(prev => {
            const updated = prev.map(c =>
                c.id === id ? { ...c, is_high_priority: !c.is_high_priority } : c
            );
            // Mirror backend sort: HP desc, then existing position asc
            return [
                ...updated.filter(c => c.is_high_priority),
                ...updated.filter(c => !c.is_high_priority),
            ].map((c, i) => ({ ...c, cause_list_position: i + 1 }));
        });
        try {
            const { data } = await judgeApi.togglePriority(id);
            // Reconcile flag with server truth, then reload for authoritative order
            setCases(prev => prev.map(c =>
                c.id === id ? { ...c, is_high_priority: data.is_high_priority } : c
            ));
            loadCauseList();
        } catch {
            toast.error('Failed to update priority.');
            setCases(prev => prev.map(c =>
                c.id === id ? { ...c, is_high_priority: previous } : c
            ));
        }
    };

    const handleMentionDone = () => {
        setAcceptM(null);
        setRejectM(null);
        loadMentions();
        loadCauseList();
    };

    const today = new Date().toLocaleDateString('en-IN', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
    const pendingCount = mentions.length;

    return (
        <ProtectedRoute role="judge">
            <Layout>
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="text-[10px] font-black uppercase tracking-widest text-ash">Judge's Bench</p>
                            <h1 className="text-3xl font-black text-ink">{user?.name}</h1>
                            <p className="text-sm font-bold text-ash mt-1">{today}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            {pendingCount > 0 && (
                                <span className="px-4 py-2 border-2 border-ink bg-volt text-ink text-xs font-black uppercase shadow-brutal-sm">
                                    {pendingCount} Mention{pendingCount > 1 ? 's' : ''} Pending
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Tab switcher */}
                    <div className="flex gap-0 border-[3px] border-ink w-fit">
                        {[['causelist', `Cause List (${cases.length})`], ['mentions', `Mentions (${pendingCount})`]].map(([key, label]) => (
                            <button
                                key={key}
                                onClick={() => setTab(key)}
                                className={`px-5 py-2.5 text-[10px] font-black uppercase tracking-widest border-r-[3px] border-ink last:border-r-0 transition-colors duration-75
                                    ${tab === key ? 'bg-ink text-white' : 'bg-white text-ink hover:bg-concrete'}`}
                            >
                                {label}
                            </button>
                        ))}
                    </div>

                    {/* ── Cause List Tab ── */}
                    {tab === 'causelist' && (
                        <div>
                            <div className="flex items-center justify-between mb-3">
                                <p className="text-xs font-bold text-ash">
                                    Drag rows to reorder. Changes broadcast to filers in real-time.
                                </p>
                                <span className={`px-3 py-1 border-2 border-ink text-[10px] font-black uppercase tracking-widest
                                    ${cases.length >= dailyLimit ? 'bg-harsh text-white' : 'bg-chalk text-ash'}`}>
                                    {cases.length} / {dailyLimit} daily limit
                                </span>
                            </div>
                            {cases.length === 0 ? (
                                <div className="border-[3px] border-ink bg-white p-10 text-center shadow-brutal">
                                    <p className="font-black text-ink text-lg">No hearings scheduled today.</p>
                                </div>
                            ) : (
                                <DndContext
                                    sensors={sensors}
                                    collisionDetection={closestCenter}
                                    onDragStart={({ active }) => setActiveId(active.id)}
                                    onDragEnd={handleDragEnd}
                                >
                                    <SortableContext items={cases.map(c => c.id)} strategy={verticalListSortingStrategy}>
                                        <div className="space-y-2">
                                            {cases.map((c) => (
                                                <SortableRow
                                                    key={c.id}
                                                    c={c}
                                                    onTogglePriority={togglePriority}
                                                    onOpenMention={() => setTab('mentions')}
                                                    onPreview={setPreviewId}
                                                    isDragging={activeId === c.id}
                                                />
                                            ))}
                                        </div>
                                    </SortableContext>
                                    <DragOverlay>
                                        {activeId ? (
                                            <div className="border-[3px] border-ink bg-volt p-4 shadow-brutal-lg opacity-90">
                                                <p className="font-black text-sm text-ink">
                                                    {cases.find(c => c.id === activeId)?.title ?? ''}
                                                </p>
                                            </div>
                                        ) : null}
                                    </DragOverlay>
                                </DndContext>
                            )}
                        </div>
                    )}

                    {/* ── Mentions Tab ── */}
                    {tab === 'mentions' && (
                        <div className="max-w-2xl">
                            <MentionsPanel
                                mentions={mentions}
                                onAccept={setAcceptM}
                                onReject={setRejectM}
                            />
                        </div>
                    )}
                </div>

                {acceptM && <AcceptMentionModal mention={acceptM} onClose={() => setAcceptM(null)} onDone={handleMentionDone} />}
                {rejectM && <RejectMentionModal mention={rejectM} onClose={() => setRejectM(null)} onDone={handleMentionDone} />}
                {previewId && <CasePreviewPanel caseId={previewId} onClose={() => setPreviewId(null)} />}
            </Layout>
        </ProtectedRoute>
    );
}
