import React from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';

const NAV_LINKS = {
    filer:    [
        { to: '/filer',          label: 'My Cases' },
        { to: '/filer/inbox',    label: 'Inbox' },
        { to: '/filer/schedule', label: 'Schedule' },
        { to: '/filer/file',     label: '+ File New Case' },
    ],
    registry: [
        { to: '/registry',       label: 'Scrutiny Queue' },
    ],
    judge:    [
        { to: '/judge',          label: 'Cause List' },
        { to: '/judge/mentions', label: 'Mentions' },
    ],
};

const ROLE_COLOR = {
    filer:    'bg-mint',
    registry: 'bg-volt',
    judge:    'bg-brutal',
};

export default function Layout({ children }) {
    const { user, logout } = useAuth();
    const navigate         = useNavigate();
    const location         = useLocation();

    const links    = NAV_LINKS[user?.role] ?? [];
    const roleColor = ROLE_COLOR[user?.role] ?? 'bg-concrete';

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    return (
        <div className="min-h-screen bg-chalk font-sans">
            {/* ── Top Bar ── */}
            <header className="border-b-[3px] border-ink bg-white">
                <div className="max-w-7xl mx-auto px-4 flex items-center justify-between h-14">
                    {/* Logo */}
                    <Link to="/" className="flex items-center gap-3">
                        <div className={`w-8 h-8 border-2 border-ink ${roleColor} shadow-brutal-sm`} />
                        <span className="font-black text-sm uppercase tracking-widest text-ink">ICMIS</span>
                    </Link>

                    {/* Nav links */}
                    <nav className="hidden md:flex items-center gap-1">
                        {links.map(({ to, label }) => {
                            const active = location.pathname === to;
                            return (
                                <Link
                                    key={to}
                                    to={to}
                                    className={`
                                        px-3 py-1.5 text-[11px] font-black uppercase tracking-widest
                                        border-2 transition-all duration-75
                                        ${active
                                            ? 'bg-ink text-white border-ink'
                                            : 'bg-white text-ink border-transparent hover:border-ink'
                                        }
                                    `}
                                >
                                    {label}
                                </Link>
                            );
                        })}
                    </nav>

                    {/* User + Logout */}
                    <div className="flex items-center gap-3">
                        <div className="text-right hidden sm:block">
                            <p className="text-xs font-black text-ink leading-none">{user?.name}</p>
                            <p className="text-[10px] font-bold text-ash uppercase tracking-wider">
                                {user?.role}
                            </p>
                        </div>
                        <button
                            onClick={handleLogout}
                            className="px-3 py-1.5 border-2 border-ink text-[10px] font-black uppercase tracking-widest bg-white hover:bg-ink hover:text-white transition-colors duration-75"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            {/* ── Page Content ── */}
            <main className="max-w-7xl mx-auto px-4 py-6">
                {children}
            </main>
        </div>
    );
}

export function ProtectedRoute({ role, children }) {
    const { user } = useAuth();
    const navigate  = useNavigate();

    React.useEffect(() => {
        if (!user) { navigate('/login'); return; }
        if (role && user.role !== role) { navigate(`/${user.role}`); }
    }, [user, role, navigate]);

    if (!user) return null;
    if (role && user.role !== role) return null;

    return children;
}
