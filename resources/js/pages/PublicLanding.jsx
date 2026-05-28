import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { publicApi } from '@/services/api';

function StatCard({ label, value, accent }) {
    return (
        <div className={`border-[3px] border-ink p-6 shadow-brutal bg-white`}>
            <div className={`h-2 ${accent} -mx-6 -mt-6 mb-5`} />
            <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash mb-2">{label}</p>
            <p className="text-5xl font-black text-ink leading-none tabular-nums">
                {value?.toLocaleString('en-IN') ?? '—'}
            </p>
        </div>
    );
}

function LeaderboardRow({ rank, name, designation, rate, disposed, total }) {
    const medalColor = rank === 1 ? 'bg-volt' : rank === 2 ? 'bg-concrete' : rank === 3 ? 'bg-harsh' : 'bg-white';
    return (
        <div className="flex items-center gap-4 border-b-2 border-concrete py-3 last:border-none">
            <div className={`w-8 h-8 shrink-0 border-2 border-ink ${medalColor} flex items-center justify-center`}>
                <span className="text-xs font-black text-ink">{rank}</span>
            </div>
            <div className="flex-1 min-w-0">
                <p className="font-black text-sm text-ink truncate">{name}</p>
                <p className="text-[10px] text-ash font-bold">{designation}</p>
            </div>
            <div className="text-right shrink-0">
                <p className="font-black text-lg text-ink">{rate}%</p>
                <p className="text-[10px] text-ash font-bold">{disposed}/{total} disposed</p>
            </div>
        </div>
    );
}

export default function PublicLanding() {
    const [stats,       setStats]       = useState(null);
    const [leaderboard, setLeaderboard] = useState([]);
    const [caseQuery,   setCaseQuery]   = useState('');
    const [caseResult,  setCaseResult]  = useState(null);
    const [searching,   setSearching]   = useState(false);

    useEffect(() => {
        publicApi.stats().then(r => setStats(r.data)).catch(() => {});
        publicApi.leaderboard().then(r => setLeaderboard(r.data.leaderboard ?? [])).catch(() => {});
    }, []);

    const lookupCase = async (e) => {
        e.preventDefault();
        if (!caseQuery.trim()) return;
        setSearching(true);
        setCaseResult(null);
        try {
            const { data } = await publicApi.caseStatus(caseQuery.trim());
            setCaseResult(data);
        } catch (err) {
            setCaseResult({ error: err.response?.data?.message ?? 'Case not found.' });
        } finally {
            setSearching(false);
        }
    };

    return (
        <div className="min-h-screen bg-chalk font-sans">
            {/* ── Hero ── */}
            <header className="border-b-[3px] border-ink bg-ink text-white">
                <div className="max-w-7xl mx-auto px-4 py-12">
                    <p className="text-[11px] font-black uppercase tracking-[0.3em] text-ash mb-3">
                        Government of India — Judiciary
                    </p>
                    <h1 className="text-5xl md:text-6xl font-black leading-none mb-4">
                        ICMIS
                    </h1>
                    <p className="text-lg font-bold text-concrete max-w-xl mb-8">
                        Integrated Case Management Information System — District Court, Delhi
                    </p>
                    <div className="flex flex-wrap gap-3">
                        {[
                            ['Login as Judge',    '/login', 'bg-brutal text-white border-brutal'],
                            ['Login as Registry', '/login', 'bg-volt text-ink border-volt'],
                            ['Login as Lawyer',   '/login', 'bg-mint text-ink border-mint'],
                        ].map(([label, to, cls]) => (
                            <Link
                                key={label}
                                to={to}
                                className={`px-5 py-2.5 border-2 font-black text-xs uppercase tracking-widest shadow-brutal hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none transition-all duration-100 ${cls}`}
                            >
                                {label}
                            </Link>
                        ))}
                    </div>
                </div>
            </header>

            <div className="max-w-7xl mx-auto px-4 py-10 space-y-10">

                {/* ── Live Stats ── */}
                <section>
                    <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash mb-4">
                        Live Stats — Last 30 Days
                    </p>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <StatCard label="Cases Filed"       value={stats?.filed}           accent="bg-brutal" />
                        <StatCard label="Cases Resolved"    value={stats?.resolved}         accent="bg-mint" />
                        <StatCard label="Total Backlog"     value={stats?.backlog}          accent="bg-harsh" />
                        <StatCard label="Resolution Rate"   value={stats ? `${stats.resolution_rate}%` : null} accent="bg-volt" />
                    </div>
                </section>

                {/* ── Case Status Lookup ── */}
                <section className="border-[3px] border-ink bg-white shadow-brutal p-6">
                    <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash mb-1">
                        Transparency Portal
                    </p>
                    <h2 className="text-2xl font-black text-ink mb-4">Track Your Case</h2>
                    <form onSubmit={lookupCase} className="flex gap-3 flex-wrap">
                        <input
                            type="text"
                            placeholder="DLH-DC-01/civil/2026/00001"
                            value={caseQuery}
                            onChange={(e) => setCaseQuery(e.target.value)}
                            className="flex-1 min-w-[240px] px-4 py-2.5 border-2 border-ink text-sm font-bold focus:outline-none focus:shadow-brutal"
                        />
                        <button
                            type="submit"
                            disabled={searching}
                            className="px-5 py-2.5 bg-ink text-white border-2 border-ink text-xs font-black uppercase tracking-widest shadow-brutal hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none transition-all duration-100"
                        >
                            {searching ? 'Searching…' : 'Search →'}
                        </button>
                    </form>

                    {caseResult && !caseResult.error && (
                        <div className="mt-5 border-2 border-ink p-4 bg-chalk">
                            <div className="flex flex-wrap items-center justify-between gap-3 mb-3">
                                <span className="font-black text-sm text-ink">{caseResult.case_number}</span>
                                <span className={`px-2.5 py-0.5 border-2 border-ink text-[10px] font-black uppercase tracking-widest ${caseResult.status === 'disposed' ? 'bg-ink text-white' : 'bg-mint text-ink'}`}>
                                    {caseResult.status?.replace('_', ' ')}
                                </span>
                            </div>
                            <p className="font-bold text-sm text-ink mb-2">{caseResult.title}</p>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                {[
                                    ['Type',          caseResult.case_type],
                                    ['Filed',         caseResult.filing_date],
                                    ['Next Hearing',  caseResult.next_hearing_date ?? '—'],
                                    ['Hearings',      caseResult.hearing_count],
                                ].map(([k, v]) => (
                                    <div key={k}>
                                        <p className="font-black text-ash uppercase tracking-wide text-[10px]">{k}</p>
                                        <p className="font-bold text-ink capitalize">{v}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                    {caseResult?.error && (
                        <p className="mt-4 text-xs font-bold text-harsh border-2 border-harsh px-4 py-2 bg-orange-50">
                            {caseResult.error}
                        </p>
                    )}
                </section>

                {/* ── Leaderboard ── */}
                <section>
                    <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash mb-1">
                        Performance Leaderboard
                    </p>
                    <h2 className="text-2xl font-black text-ink mb-4">Highest Judicial Disposal Rates</h2>
                    <div className="border-[3px] border-ink bg-white shadow-brutal p-6 max-w-2xl">
                        {leaderboard.length === 0 && (
                            <p className="text-sm text-ash font-bold">No data yet.</p>
                        )}
                        {leaderboard.slice(0, 10).map((row, i) => (
                            <LeaderboardRow
                                key={i}
                                rank={i + 1}
                                name={row.judge_name}
                                designation={row.designation ?? 'Judge'}
                                rate={row.disposal_rate}
                                disposed={row.cases_disposed}
                                total={row.cases_total}
                            />
                        ))}
                    </div>
                </section>
            </div>
        </div>
    );
}
