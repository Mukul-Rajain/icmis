const CONFIG = {
    draft:          { label: 'Draft',           classes: 'bg-concrete text-ink border-ink' },
    submitted:      { label: 'Submitted',        classes: 'bg-volt text-ink border-ink' },
    under_scrutiny: { label: 'Under Scrutiny',   classes: 'bg-brutal text-white border-ink' },
    defective:      { label: 'Defective',        classes: 'bg-harsh text-white border-ink' },
    accepted:       { label: 'Accepted',         classes: 'bg-mint text-ink border-ink' },
    scheduled:      { label: 'Scheduled',        classes: 'bg-mint text-ink border-ink' },
    adjourned:      { label: 'Adjourned',        classes: 'bg-volt text-ink border-ink' },
    heard:          { label: 'Heard',            classes: 'bg-brutal text-white border-ink' },
    reserved:       { label: 'Reserved',         classes: 'bg-lavender text-ink border-ink' },
    disposed:       { label: 'Disposed',         classes: 'bg-ink text-white border-ink' },
};

export default function StatusBadge({ status, size = 'sm' }) {
    const cfg = CONFIG[status] ?? CONFIG.draft;
    const pad = size === 'lg' ? 'px-4 py-1.5 text-xs' : 'px-2.5 py-0.5 text-[10px]';
    return (
        <span className={`
            inline-block border-2 font-black uppercase tracking-widest
            shadow-brutal-sm ${cfg.classes} ${pad}
        `}>
            {cfg.label}
        </span>
    );
}
