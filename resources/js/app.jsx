import './bootstrap';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';

import { AuthProvider, useAuth } from '@/context/AuthContext';

// Pages
import PublicLanding     from '@/pages/PublicLanding';
import Login             from '@/pages/Login';
import FilerDashboard    from '@/pages/filer/Dashboard';
import FileCase          from '@/pages/filer/FileCase';
import CaseDetail        from '@/pages/filer/CaseDetail';
import FilerInbox        from '@/pages/filer/Inbox';
import FilerSchedule     from '@/pages/filer/Schedule';
import RegistryDashboard from '@/pages/registry/Dashboard';
import JudgeDashboard    from '@/pages/judge/Dashboard';

// Redirect authenticated users to their role dashboard
function RoleRedirect() {
    const { user } = useAuth();
    if (!user) return <Navigate to="/login" replace />;
    return <Navigate to={`/${user.role}`} replace />;
}

function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Toaster
                    position="top-right"
                    toastOptions={{
                        style: {
                            border: '2px solid #0A0A0A',
                            borderRadius: 0,
                            fontFamily: 'Inter, sans-serif',
                            fontWeight: 700,
                            fontSize: '13px',
                            boxShadow: '4px 4px 0px 0px #0A0A0A',
                        },
                        success: { style: { background: '#00FF94', color: '#0A0A0A' } },
                        error:   { style: { background: '#FF6200', color: '#fff' } },
                    }}
                />
                <Routes>
                    <Route path="/"           element={<PublicLanding />} />
                    <Route path="/login"      element={<Login />} />
                    <Route path="/filer"                element={<FilerDashboard />} />
                    <Route path="/filer/file"           element={<FileCase />} />
                    <Route path="/filer/inbox"          element={<FilerInbox />} />
                    <Route path="/filer/schedule"       element={<FilerSchedule />} />
                    <Route path="/filer/cases/:id"      element={<CaseDetail />} />
                    <Route path="/registry"             element={<RegistryDashboard />} />
                    <Route path="/judge"                element={<JudgeDashboard />} />
                    <Route path="/judge/*"              element={<JudgeDashboard />} />
                    <Route path="/registry/*"           element={<RegistryDashboard />} />
                    <Route path="*"           element={<RoleRedirect />} />
                </Routes>
            </BrowserRouter>
        </AuthProvider>
    );
}

const root = createRoot(document.getElementById('app'));
root.render(<App />);
