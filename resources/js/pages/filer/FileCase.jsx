import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { filerApi, publicApi } from '@/services/api';
import Layout, { ProtectedRoute } from '@/components/Layout';
import Button from '@/components/ui/Button';
import Input, { Select } from '@/components/ui/Input';
import toast from 'react-hot-toast';

const PARTY_TEMPLATE = { party_type: 'petitioner', name: '', address: '', is_senior_citizen: false, is_in_custody: false };

export default function FileCase() {
    const navigate = useNavigate();
    const [courts, setCourts]     = useState([]);
    const [loading, setLoading]   = useState(false);
    const [form, setForm]         = useState({
        title:       '',
        description: '',
        court_id:    '',
        case_type:   'civil',
        parties:     [
            { ...PARTY_TEMPLATE, party_type: 'petitioner' },
            { ...PARTY_TEMPLATE, party_type: 'respondent' },
        ],
    });

    useEffect(() => {
        publicApi.courts().then(r => {
            setCourts(r.data.courts ?? []);
            if (r.data.courts?.length) setForm(f => ({ ...f, court_id: r.data.courts[0].id }));
        });
    }, []);

    const setField = (key, val) => setForm(f => ({ ...f, [key]: val }));

    const setParty = (idx, key, val) =>
        setForm(f => {
            const parties = [...f.parties];
            parties[idx] = { ...parties[idx], [key]: val };
            return { ...f, parties };
        });

    const addParty = () =>
        setForm(f => ({ ...f, parties: [...f.parties, { ...PARTY_TEMPLATE, party_type: 'respondent' }] }));

    const removeParty = (idx) =>
        setForm(f => ({ ...f, parties: f.parties.filter((_, i) => i !== idx) }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (form.parties.length < 2) { toast.error('At least two parties are required.'); return; }
        setLoading(true);
        try {
            const { data } = await filerApi.createCase(form);
            toast.success('Case created as draft. Upload documents next.');
            navigate(`/filer/cases/${data.case.id}`);
        } catch (err) {
            const errors = err.response?.data?.errors;
            if (errors) {
                Object.values(errors).flat().forEach(msg => toast.error(msg));
            } else {
                toast.error(err.response?.data?.message ?? 'Failed to create case.');
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <ProtectedRoute role="filer">
            <Layout>
                <div className="max-w-3xl">
                    {/* Page header */}
                    <div className="mb-8">
                        <p className="text-[10px] font-black uppercase tracking-widest text-ash mb-1">
                            Filer Portal
                        </p>
                        <h1 className="text-3xl font-black text-ink">File New Case</h1>
                        <p className="text-sm text-ash font-bold mt-1">
                            Creates a draft. You can upload documents before submitting to registry.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Basic info */}
                        <section className="border-[3px] border-ink bg-white shadow-brutal">
                            <div className="bg-ink px-5 py-3">
                                <p className="text-[10px] font-black uppercase tracking-widest text-mint">Step 1</p>
                                <h2 className="font-black text-white">Case Details</h2>
                            </div>
                            <div className="p-5 space-y-4">
                                <Input
                                    label="Case Title"
                                    placeholder="e.g. ABC Enterprises vs. State of Delhi"
                                    value={form.title}
                                    onChange={e => setField('title', e.target.value)}
                                    required
                                />
                                <div className="flex flex-col gap-1.5">
                                    <label className="text-[10px] font-black uppercase tracking-widest text-ash">
                                        Brief Description / Facts
                                    </label>
                                    <textarea
                                        rows={4}
                                        required
                                        placeholder="Briefly describe the nature of the case and the relief sought…"
                                        value={form.description}
                                        onChange={e => setField('description', e.target.value)}
                                        className="w-full px-3 py-2.5 border-2 border-ink text-sm font-bold resize-none focus:outline-none focus:shadow-brutal transition-shadow duration-100"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <Select
                                        label="Court"
                                        value={form.court_id}
                                        onChange={e => setField('court_id', e.target.value)}
                                        required
                                    >
                                        {courts.map(c => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </Select>
                                    <Select
                                        label="Case Type"
                                        value={form.case_type}
                                        onChange={e => setField('case_type', e.target.value)}
                                    >
                                        {['civil', 'criminal', 'family', 'commercial', 'writ'].map(t => (
                                            <option key={t} value={t} className="capitalize">{t.charAt(0).toUpperCase() + t.slice(1)}</option>
                                        ))}
                                    </Select>
                                </div>
                            </div>
                        </section>

                        {/* Parties */}
                        <section className="border-[3px] border-ink bg-white shadow-brutal">
                            <div className="bg-ink px-5 py-3 flex items-center justify-between">
                                <div>
                                    <p className="text-[10px] font-black uppercase tracking-widest text-volt">Step 2</p>
                                    <h2 className="font-black text-white">Parties</h2>
                                </div>
                                <button
                                    type="button"
                                    onClick={addParty}
                                    className="px-3 py-1.5 bg-volt text-ink border-2 border-ink text-[10px] font-black uppercase tracking-widest shadow-brutal-sm hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-75"
                                >
                                    + Add Party
                                </button>
                            </div>
                            <div className="p-5 space-y-5">
                                {form.parties.map((party, idx) => (
                                    <div key={idx} className="border-2 border-ink p-4 relative">
                                        <div className="flex items-center justify-between mb-4">
                                            <span className="text-[10px] font-black uppercase tracking-widest text-ash">
                                                Party {idx + 1}
                                            </span>
                                            {form.parties.length > 2 && (
                                                <button
                                                    type="button"
                                                    onClick={() => removeParty(idx)}
                                                    className="text-[10px] font-black text-harsh uppercase tracking-widest hover:underline"
                                                >
                                                    Remove
                                                </button>
                                            )}
                                        </div>
                                        <div className="grid grid-cols-2 gap-3 mb-3">
                                            <Select
                                                label="Party Type"
                                                value={party.party_type}
                                                onChange={e => setParty(idx, 'party_type', e.target.value)}
                                            >
                                                <option value="petitioner">Petitioner / Plaintiff</option>
                                                <option value="respondent">Respondent / Defendant</option>
                                                <option value="intervenor">Intervenor</option>
                                            </Select>
                                            <Input
                                                label="Full Name"
                                                placeholder="Full legal name"
                                                value={party.name}
                                                onChange={e => setParty(idx, 'name', e.target.value)}
                                                required
                                            />
                                        </div>
                                        <Input
                                            label="Address"
                                            placeholder="Complete address for service of notice"
                                            value={party.address}
                                            onChange={e => setParty(idx, 'address', e.target.value)}
                                            required
                                        />
                                        <div className="flex gap-6 mt-3">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={party.is_senior_citizen}
                                                    onChange={e => setParty(idx, 'is_senior_citizen', e.target.checked)}
                                                    className="w-4 h-4 border-2 border-ink accent-mint"
                                                />
                                                <span className="text-[10px] font-black uppercase tracking-widest text-ash">
                                                    Senior Citizen
                                                </span>
                                            </label>
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={party.is_in_custody}
                                                    onChange={e => setParty(idx, 'is_in_custody', e.target.checked)}
                                                    className="w-4 h-4 border-2 border-ink accent-harsh"
                                                />
                                                <span className="text-[10px] font-black uppercase tracking-widest text-ash">
                                                    In Custody
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        {/* Submit */}
                        <div className="flex items-center gap-4 pb-8">
                            <Button
                                type="submit"
                                variant="primary"
                                disabled={loading}
                                className="px-8"
                            >
                                {loading ? 'Creating Draft…' : 'Create Draft Case →'}
                            </Button>
                            <button
                                type="button"
                                onClick={() => navigate('/filer')}
                                className="text-sm font-black text-ash uppercase tracking-widest hover:text-ink transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </Layout>
        </ProtectedRoute>
    );
}
