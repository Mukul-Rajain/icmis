import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import Button from '@/components/ui/Button';
import Input from '@/components/ui/Input';
import toast from 'react-hot-toast';

export default function Login() {
    const { login, loading } = useAuth();
    const navigate           = useNavigate();
    const [form, setForm]    = useState({ email: '', password: '' });
    const [error, setError]  = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        try {
            const user = await login(form.email, form.password);
            toast.success(`Welcome back, ${user.name.split(' ')[0]}.`);
            // Route to role-specific dashboard
            navigate(`/${user.role}`);
        } catch (err) {
            const msg = err.response?.data?.message
                ?? err.response?.data?.errors?.email?.[0]
                ?? 'Invalid credentials.';
            setError(msg);
        }
    };

    // Quick-fill demo credentials
    const fill = (email) => setForm({ email, password: 'password' });

    return (
        <div className="min-h-screen bg-chalk flex flex-col items-center justify-center p-4 font-sans">
            {/* Header strip */}
            <div className="w-full max-w-md mb-6">
                <Link to="/" className="text-[11px] font-black uppercase tracking-widest text-ash hover:text-ink">
                    ← Back to Portal
                </Link>
            </div>

            <div className="w-full max-w-md border-[3px] border-ink bg-white shadow-brutal-lg">
                {/* Title bar */}
                <div className="bg-ink px-6 py-4">
                    <p className="text-[10px] font-black uppercase tracking-[0.2em] text-ash mb-1">
                        ICMIS — Court Login
                    </p>
                    <h1 className="text-2xl font-black text-white leading-tight">
                        Sign In to Dashboard
                    </h1>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-5">
                    <Input
                        label="Email Address"
                        type="email"
                        placeholder="judge1@icmis.test"
                        value={form.email}
                        onChange={(e) => setForm(f => ({ ...f, email: e.target.value }))}
                        required
                    />
                    <Input
                        label="Password"
                        type="password"
                        placeholder="password"
                        value={form.password}
                        onChange={(e) => setForm(f => ({ ...f, password: e.target.value }))}
                        required
                    />

                    {error && (
                        <div className="border-2 border-harsh bg-orange-50 px-4 py-2.5">
                            <p className="text-xs font-bold text-harsh">{error}</p>
                        </div>
                    )}

                    <Button variant="primary" type="submit" disabled={loading} className="w-full">
                        {loading ? 'Signing In…' : 'Sign In →'}
                    </Button>
                </form>

                {/* Demo logins */}
                <div className="border-t-[3px] border-ink px-6 py-5 bg-chalk">
                    <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-3">
                        Demo Quick-Fill
                    </p>
                    <div className="grid grid-cols-2 gap-2">
                        {[
                            ['Judge',    'judge1@icmis.test',   'bg-brutal text-white'],
                            ['Registry', 'registry@icmis.test', 'bg-volt text-ink'],
                            ['Lawyer',   'lawyer@icmis.test',   'bg-mint text-ink'],
                            ['Litigant', 'litigant@icmis.test', 'bg-concrete text-ink'],
                        ].map(([role, email, cls]) => (
                            <button
                                key={email}
                                type="button"
                                onClick={() => fill(email)}
                                className={`px-3 py-2 border-2 border-ink text-[10px] font-black uppercase tracking-wider shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75 ${cls}`}
                            >
                                {role}
                            </button>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
