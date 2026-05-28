import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

// Attach Bearer token from localStorage on every request
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('icmis_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

// On 401, clear storage and redirect to login
api.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err.response?.status === 401) {
            localStorage.removeItem('icmis_token');
            localStorage.removeItem('icmis_user');
            window.location.href = '/login';
        }
        return Promise.reject(err);
    }
);

// ── Auth ──────────────────────────────────────────────────────
export const authApi = {
    login:    (data)  => api.post('/auth/login', data),
    register: (data)  => api.post('/auth/register', data),
    logout:   ()      => api.post('/auth/logout'),
    me:       ()      => api.get('/auth/me'),
};

// ── Public ────────────────────────────────────────────────────
export const publicApi = {
    stats:      ()           => api.get('/public/stats'),
    leaderboard:()           => api.get('/public/leaderboard'),
    caseStatus: (caseNumber) => api.get('/public/case-status', { params: { case_number: caseNumber } }),
    courts:     ()           => api.get('/public/courts'),
};

// ── Filer ─────────────────────────────────────────────────────
export const filerApi = {
    listCases:       (params) => api.get('/cases', { params }),
    getCase:         (id)     => api.get(`/cases/${id}`),
    createCase:      (data)   => api.post('/cases', data),
    updateCase:      (id, data) => api.patch(`/cases/${id}`, data),
    submitCase:      (id)     => api.post(`/cases/${id}/submit`),
    uploadDocument:  (id, formData) => api.post(`/cases/${id}/documents`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    }),
    deleteDocument:  (id, docId)   => api.delete(`/cases/${id}/documents/${docId}`),
    documentViewUrl: (id, docId)   => `/api/cases/${id}/documents/${docId}/view`,
    requestMention:  (id, data)    => api.post(`/cases/${id}/mention`, data),
    inbox:           ()            => api.get('/filer/inbox'),
    schedule:        ()            => api.get('/filer/schedule'),
};

// ── Registry ──────────────────────────────────────────────────
export const registryApi = {
    queue:           (params)      => api.get('/registry/queue', { params }),
    getCase:         (id)          => api.get(`/registry/queue/${id}`),
    judges:          ()            => api.get('/registry/judges'),
    approve:         (id, data)    => api.patch(`/registry/cases/${id}/approve`, data),
    reject:          (id, data)    => api.patch(`/registry/cases/${id}/reject`, data),
    getDocumentBlob: (id, docId)   => api.get(`/registry/cases/${id}/documents/${docId}/view`, { responseType: 'blob' }),
};

// ── Judge ─────────────────────────────────────────────────────
export const judgeApi = {
    causeList:       ()            => api.get('/judge/causelist'),
    reorder:         (orderedIds)  => api.patch('/judge/causelist/reorder', { ordered_ids: orderedIds }),
    togglePriority:  (id)          => api.patch(`/judge/cases/${id}/priority`),
    preview:         (id)          => api.get(`/judge/cases/${id}/preview`),
    getDocumentBlob: (id, docId)   => api.get(`/judge/cases/${id}/documents/${docId}/view`, { responseType: 'blob' }),
    recordHearing:  (id, data)     => api.post(`/judge/hearings/${id}/record`, data),
    mentionQueue:   ()             => api.get('/judge/mentions'),
    acceptMention:  (caseId, reqId, data) => api.patch(`/judge/mentions/${caseId}/${reqId}/accept`, data),
    rejectMention:  (caseId, reqId, data) => api.patch(`/judge/mentions/${caseId}/${reqId}/reject`, data),
};

export default api;
