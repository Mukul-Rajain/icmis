import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { filerApi } from '@/services/api';
import Layout from '@/components/Layout';
import { ProtectedRoute } from '@/components/Layout';
import StatusBadge from '@/components/ui/StatusBadge';
import Button from '@/components/ui/Button';
import echo from '@/services/echo';
import { useAuth } from '@/context/AuthContext';
import toast from 'react-hot-toast';

function CaseCard({ c, onMention }) {
    return (
        <div className="border-[3px] border-ink bg-white p-5 shadow-brutal hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-brutal-lg transition-all duration-100">
            <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
                <div>
                    <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash">Case No.</p>
                    <p className="font-black text-base text-ink">{c.case_number ?? 'DRAFT'}</p>
                </div>
                <div className="flex flex-col items-end gap-1.5">
                    <StatusBadge status={c.status} />
                    {c.is_high_priority && (
                        <span className="px-2 py-0.5 bg-harsh text-white border-2 border-ink text-[9px] font-black uppercase tracking-widest">
                            HIGH PRIORITY
                        </span>
                    )}
                </div>
            </div>

            <h3 className="font-bold text-sm text-ink mb-3 line-clamp-2">{c.title}</h3>

            {/* Defect flags */}
            {c.status === 'defective' && c.scrutiny?.defect_flags?.length > 0 && (
                <div className="border-2 border-harsh bg-orange-50 p-3 mb-3">
                    <p className="text-[10px] font-black uppercase tracking-widest text-harsh mb-1.5">Defects</p>
                    <ul className="space-y-1">
                        {c.scrutiny.defect_flags.map((f, i) => (
                            <li key={i} className="text-xs font-bold text-ink flex items-start gap-2">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 bg-harsh border border-ink" />
                                {f.label}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <div className="h-[2px] bg-concrete mb-3" />

            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex gap-4 text-xs">
                    {c.next_hearing_date && (
                        <div>
                            <p className="font-black text-ash uppercase tracking-wide text-[9px]">Next Hearing</p>
                            <p className="font-black text-ink">
                                {new Date(c.next_hearing_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })}
                            </p>
                        </div>
                    )}
                    <div>
                        <p className="font-black text-ash uppercase tracking-wide text-[9px]">Track</p>
                        <p className="font-bold text-ink capitalize">{c.track ?? '—'}</p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Link
                        to={`/filer/cases/${c.id}`}
                        className="px-3 py-1.5 border-2 border-ink text-[10px] font-black uppercase tracking-widest bg-ink text-white shadow-brutal-sm hover:translate-x-[3px] hover:translate-y-[3px] hover:shadow-none transition-all duration-75"
                    >
                        View
                    </Link>
                    {['accepted', 'scheduled', 'adjourned'].includes(c.status) && (
                        <button
                            onClick={() => onMention(c)}
                            className="px-3 py-1.5 border-2 border-ink text-[10px] font-black uppercase tracking-widest bg-mint text-ink shadow-brutal-sm hover:translate-x-[3px] hover:translate-y-[3px] hover:shadow-none transition-all duration-75"
                        >
                            Mention
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

function MentionModal({ caseItem, onClose, onSubmit }) {
    const [form, setForm] = useState({ reason: '', urgency: 'urgent' });
    const [loading, setLoading] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            await onSubmit(caseItem.id, form);
            toast.success('Mentioning request submitted.');
            onClose();
        } catch {
            toast.error('Failed to submit request.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-ink/60 flex items-center justify-center z-50 p-4">
            <div className="w-full max-w-md border-[3px] border-ink bg-white shadow-brutal-lg">
                <div className="bg-harsh px-6 py-4">
                    <h2 className="text-lg font-black text-white">Request Emergency Mention</h2>
                    <p className="text-[10px] text-orange-200 font-bold mt-1">{caseItem.case_number}</p>
                </div>
                <form onSubmit={submit} className="p-6 space-y-4">
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[10px] font-black uppercase tracking-widest text-ash">Urgency</label>
                        <select
                            value={form.urgency}
                            onChange={(e) => setForm(f => ({ ...f, urgency: e.target.value }))}
                            className="border-2 border-ink px-3 py-2.5 text-sm font-bold focus:outline-none focus:shadow-brutal"
                        >
                            <option value="urgent">Urgent</option>
                            <option value="very_urgent">Very Urgent</option>
                        </select>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <label className="text-[10px] font-black uppercase tracking-widest text-ash">Reason</label>
                        <textarea
                            value={form.reason}
                            onChange={(e) => setForm(f => ({ ...f, reason: e.target.value }))}
                            rows={4}
                            required
                            placeholder="State the grounds for emergency hearing…"
                            className="border-2 border-ink px-3 py-2.5 text-sm font-bold resize-none focus:outline-none focus:shadow-brutal"
                        />
                    </div>
                    <div className="flex gap-3 pt-1">
                        <Button variant="harsh" type="submit" disabled={loading} className="flex-1">
                            {loading ? 'Submitting…' : 'Submit Request'}
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

export default function FilerDashboard() {
    const { user }              = useAuth();
    const [cases,  setCases]    = useState([]);
    const [filter, setFilter]   = useState('all');
    const [loading,setLoading]  = useState(true);
    const [mentionTarget, setMentionTarget] = useState(null);

    const load = () => {
        setLoading(true);
        const params = filter !== 'all' ? { status: filter } : {};
        filerApi.listCases(params)
            .then(r => setCases(r.data.data ?? r.data))
            .catch(() => toast.error('Failed to load cases.'))
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, [filter]);

    // Real-time: listen for schedule changes and defect notifications
    useEffect(() => {
        if (!user?._id && !user?.id) return;
        const uid = user.id ?? user._id;

        const ch = echo.channel(`filer.${uid}`);
        ch.listen('.case.scheduled', (e) => {
            toast.success(e.message);
            load();
        });
        ch.listen('.case.defective', (e) => {
            toast.error(e.message);
            load();
        });
        ch.listen('.mention.accepted', (e) => {
            toast.success(e.message);
            load();
        });
        ch.listen('.mention.rejected', (e) => {
            toast(e.message, { icon: '⚖️' });
        });

        return () => echo.leaveChannel(`filer.${uid}`);
    }, [user]);

    const STATUS_FILTERS = ['all', 'draft', 'submitted', 'under_scrutiny', 'defective', 'scheduled', 'adjourned'];

    return (
        <ProtectedRoute role="filer">
            <Layout>
                <div className="space-y-6">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p className="text-[10px] font-black uppercase tracking-widest text-ash">Filer Portal</p>
                            <h1 className="text-3xl font-black text-ink">My Cases</h1>
                        </div>
                        <Link
                            to="/filer/file"
                            className="px-5 py-2.5 bg-mint text-ink border-2 border-ink font-black text-xs uppercase tracking-widest shadow-brutal hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none transition-all duration-100"
                        >
                            + File New Case
                        </Link>
                    </div>

                    {/* Status filter tabs */}
                    <div className="flex flex-wrap gap-2">
                        {STATUS_FILTERS.map((s) => (
                            <button
                                key={s}
                                onClick={() => setFilter(s)}
                                className={`px-3 py-1 border-2 text-[10px] font-black uppercase tracking-widest transition-all duration-75
                                    ${filter === s ? 'bg-ink text-white border-ink' : 'bg-white text-ink border-concrete hover:border-ink'}`}
                            >
                                {s === 'all' ? 'All' : s.replace('_', ' ')}
                            </button>
                        ))}
                    </div>

                    {/* Case grid */}
                    {loading ? (
                        <div className="text-sm font-bold text-ash">Loading…</div>
                    ) : cases.length === 0 ? (
                        <div className="border-[3px] border-ink bg-white p-10 text-center shadow-brutal">
                            <p className="font-black text-ink text-lg mb-2">No cases found.</p>
                            <p className="text-sm text-ash">
                                {filter === 'all' ? 'File your first case to get started.' : `No cases with status "${filter}".`}
                            </p>
                        </div>
                    ) : (
                        <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                            {cases.map((c) => (
                                <CaseCard key={c.id} c={c} onMention={setMentionTarget} />
                            ))}
                        </div>
                    )}
                </div>

                {mentionTarget && (
                    <MentionModal
                        caseItem={mentionTarget}
                        onClose={() => setMentionTarget(null)}
                        onSubmit={filerApi.requestMention}
                    />
                )}
            </Layout>
        </ProtectedRoute>
    );
}
