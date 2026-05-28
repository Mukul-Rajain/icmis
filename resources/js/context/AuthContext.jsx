import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { authApi } from '@/services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user,    setUser]    = useState(() => {
        const saved = localStorage.getItem('icmis_user');
        return saved ? JSON.parse(saved) : null;
    });
    const [loading, setLoading] = useState(false);

    const login = useCallback(async (email, password) => {
        setLoading(true);
        try {
            const { data } = await authApi.login({ email, password });
            localStorage.setItem('icmis_token', data.token);
            localStorage.setItem('icmis_user',  JSON.stringify(data.user));
            setUser(data.user);
            return data.user;
        } finally {
            setLoading(false);
        }
    }, []);

    const logout = useCallback(async () => {
        try { await authApi.logout(); } catch {}
        localStorage.removeItem('icmis_token');
        localStorage.removeItem('icmis_user');
        setUser(null);
    }, []);

    return (
        <AuthContext.Provider value={{ user, loading, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
    return ctx;
}
