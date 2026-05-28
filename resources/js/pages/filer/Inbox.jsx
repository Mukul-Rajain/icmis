import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { filerApi } from '@/services/api';
import Layout, { ProtectedRoute } from '@/components/Layout';
import StatusBadge from '@/components/ui/StatusBadge';
import toast from 'react-hot-toast';

function InboxCard({ c }) {
    const isDefective = c.status === 'defective';
    return (
        <div className={`border-[3px] border-ink bg-white p-5 shadow-brutal ${isDefective ? 'border-l-8 border-l-harsh' : 'border-l-8 border-l-mint'}`}>
            <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
                <div>
                    <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash">
                        {c.case_number ?? 'Draft'}
                    </p>
                    <p className="font-black text-base text-ink mt-0.5">{c.title}</p>
                </div>
                <StatusBadge status={c.status} />
            </div>

            {isDefective && c.scrutiny?.defect_flags?.length > 0 && (
                <div className="border-2 border-harsh bg-orange-50 px-3 py-2 mb-3">
                    <p className="text-[10px] font-black uppercase tracking-widest text-harsh mb-1">
                        Defects Raised
                    </p>
                    <ul className="space-y-0.5">
                        {c.scrutiny.defect_flags.map((f, i) => (
                            <li key={i} className="text-xs font-bold text-ink flex items-start gap-2">
                                <span className="mt-1 h-1.5 w-1.5 shrink-0 bg-harsh border border-ink" />
                                {f.label}
                            </li>
                        ))}
                    </ul>
                    {c.scrutiny.rejection_note && (
                        <p className="text-xs font-bold text-ink/70 mt-2 italic">
                            "{c.scrutiny.rejection_note}"
                        </p>
                    )}
                </div>
            )}

            {!isDefective && c.next_hearing_date && (
                <div className="bg-mint border-2 border-ink px-3 py-2 mb-3 inline-block">
                    <p className="text-[10px] font-black uppercase tracking-widest text-ink">
                        Hearing Scheduled
                    </p>
                    <p className="text-sm font-black text-ink">
                        {new Date(c.next_hearing_date).toLocaleDateString('en-IN', {
                            weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
                        })}
                    </p>
                </div>
            )}

            <Link
                to={`/filer/cases/${c.id}`}
                className="inline-block px-4 py-1.5 border-2 border-ink text-[10px] font-black uppercase tracking-widest bg-ink text-white shadow-brutal-sm hover:translate-x-[3px] hover:translate-y-[3px] hover:shadow-none transition-all duration-75"
            >
                {isDefective ? 'Fix & Resubmit →' : 'View Case →'}
            </Link>
        </div>
    );
}

export default function Inbox() {
    const [data, setData]       = useState({ defective: [], accepted: [] });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        filerApi.inbox()
            .then(r => setData(r.data))
            .catch(() => toast.error('Failed to load inbox.'))
            .finally(() => setLoading(false));
    }, []);

    const total = data.defective.length + data.accepted.length;

    return (
        <ProtectedRoute role="filer">
            <Layout>
                <div className="mb-6">
                    <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-1">Filer Portal</p>
                    <h1 className="text-3xl font-black text-ink">Inbox</h1>
                    <p className="text-sm font-bold text-ash mt-1">
                        Registry decisions and hearing confirmations for your cases.
                    </p>
                </div>

                {loading ? (
                    <p className="text-sm font-bold text-ash">Loading…</p>
                ) : total === 0 ? (
                    <div className="border-[3px] border-ink bg-white p-10 text-center shadow-brutal">
                        <p className="font-black text-ink text-lg">Inbox is empty.</p>
                        <p className="text-sm text-ash mt-1">No defects or new hearing dates to report.</p>
                    </div>
                ) : (
                    <div className="space-y-8">
                        {data.defective.length > 0 && (
                            <section>
                                <div className="flex items-center gap-3 mb-4">
                                    <h2 className="font-black text-lg text-ink uppercase tracking-wide">
                                        Action Required
                                    </h2>
                                    <span className="px-2 py-0.5 bg-harsh text-white border-2 border-ink text-[10px] font-black">
                                        {data.defective.length}
                                    </span>
                                </div>
                                <div className="space-y-4">
                                    {data.defective.map(c => <InboxCard key={c.id} c={c} />)}
                                </div>
                            </section>
                        )}

                        {data.accepted.length > 0 && (
                            <section>
                                <div className="flex items-center gap-3 mb-4">
                                    <h2 className="font-black text-lg text-ink uppercase tracking-wide">
                                        Recently Accepted / Scheduled
                                    </h2>
                                    <span className="px-2 py-0.5 bg-mint text-ink border-2 border-ink text-[10px] font-black">
                                        {data.accepted.length}
                                    </span>
                                </div>
                                <div className="space-y-4">
                                    {data.accepted.map(c => <InboxCard key={c.id} c={c} />)}
                                </div>
                            </section>
                        )}
                    </div>
                )}
            </Layout>
        </ProtectedRoute>
    );
}
