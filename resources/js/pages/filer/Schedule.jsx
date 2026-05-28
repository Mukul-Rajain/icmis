import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { filerApi } from '@/services/api';
import Layout, { ProtectedRoute } from '@/components/Layout';
import StatusBadge from '@/components/ui/StatusBadge';
import toast from 'react-hot-toast';

function groupByDate(cases) {
    const map = {};
    cases.forEach(c => {
        const d = c.next_hearing_date
            ? new Date(c.next_hearing_date).toDateString()
            : 'Unscheduled';
        if (!map[d]) map[d] = [];
        map[d].push(c);
    });
    return map;
}

function HearingCard({ c }) {
    const date = c.next_hearing_date ? new Date(c.next_hearing_date) : null;
    const isToday = date && date.toDateString() === new Date().toDateString();
    const isTomorrow = date && (() => {
        const t = new Date(); t.setDate(t.getDate() + 1);
        return date.toDateString() === t.toDateString();
    })();

    return (
        <div className={`border-[3px] border-ink bg-white p-4 shadow-brutal flex items-start justify-between gap-4
            ${isToday ? 'bg-volt' : ''}`}>
            <div className="flex items-start gap-4">
                {/* Time block */}
                {date && (
                    <div className="shrink-0 text-center border-2 border-ink w-14 bg-white">
                        <div className="bg-ink text-white text-[9px] font-black uppercase tracking-widest py-0.5">
                            {date.toLocaleDateString('en-IN', { month: 'short' })}
                        </div>
                        <div className="text-2xl font-black text-ink leading-tight py-1">
                            {date.getDate().toString().padStart(2, '0')}
                        </div>
                    </div>
                )}
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash">
                            {c.case_number ?? 'Draft'}
                        </p>
                        {isToday && (
                            <span className="px-1.5 py-0.5 bg-harsh text-white text-[8px] font-black uppercase tracking-widest border border-ink">
                                Today
                            </span>
                        )}
                        {isTomorrow && (
                            <span className="px-1.5 py-0.5 bg-brutal text-white text-[8px] font-black uppercase tracking-widest border border-ink">
                                Tomorrow
                            </span>
                        )}
                    </div>
                    <p className="font-black text-sm text-ink">{c.title}</p>
                    <div className="flex items-center gap-3 mt-1">
                        <StatusBadge status={c.status} />
                        {c.is_high_priority && (
                            <span className="px-1.5 py-0.5 bg-harsh text-white border border-ink text-[8px] font-black uppercase tracking-widest">
                                Priority
                            </span>
                        )}
                    </div>
                </div>
            </div>
            <Link
                to={`/filer/cases/${c.id}`}
                className="shrink-0 px-3 py-1.5 border-2 border-ink text-[10px] font-black uppercase tracking-widest bg-ink text-white shadow-brutal-sm hover:translate-x-[3px] hover:translate-y-[3px] hover:shadow-none transition-all duration-75"
            >
                View
            </Link>
        </div>
    );
}

export default function Schedule() {
    const [cases, setCases]     = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        filerApi.schedule()
            .then(r => setCases(r.data.schedule ?? []))
            .catch(() => toast.error('Failed to load schedule.'))
            .finally(() => setLoading(false));
    }, []);

    const grouped = groupByDate(cases);
    const dates   = Object.keys(grouped);

    return (
        <ProtectedRoute role="filer">
            <Layout>
                <div className="mb-6">
                    <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-1">Filer Portal</p>
                    <h1 className="text-3xl font-black text-ink">Hearing Schedule</h1>
                    <p className="text-sm font-bold text-ash mt-1">
                        All upcoming hearings for your cases, sorted by date.
                    </p>
                </div>

                {loading ? (
                    <p className="text-sm font-bold text-ash">Loading…</p>
                ) : cases.length === 0 ? (
                    <div className="border-[3px] border-ink bg-white p-10 text-center shadow-brutal">
                        <p className="font-black text-ink text-lg">No hearings scheduled.</p>
                        <p className="text-sm text-ash mt-1">
                            Hearings appear here once the registry accepts and schedules your cases.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-8 max-w-3xl">
                        {dates.map(dateStr => {
                            const date = new Date(dateStr);
                            const isValid = !isNaN(date);
                            return (
                                <section key={dateStr}>
                                    <div className="flex items-center gap-4 mb-3">
                                        <h2 className="font-black text-sm uppercase tracking-widest text-ink">
                                            {isValid
                                                ? date.toLocaleDateString('en-IN', {
                                                    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
                                                })
                                                : dateStr}
                                        </h2>
                                        <div className="flex-1 h-[2px] bg-ink" />
                                        <span className="text-[10px] font-black text-ash uppercase tracking-widest">
                                            {grouped[dateStr].length} case{grouped[dateStr].length !== 1 ? 's' : ''}
                                        </span>
                                    </div>
                                    <div className="space-y-3">
                                        {grouped[dateStr].map(c => <HearingCard key={c.id} c={c} />)}
                                    </div>
                                </section>
                            );
                        })}
                    </div>
                )}
            </Layout>
        </ProtectedRoute>
    );
}
