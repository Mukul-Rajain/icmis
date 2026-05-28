const VARIANTS = {
    primary:  'bg-ink text-white border-ink shadow-brutal hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none',
    mint:     'bg-mint text-ink border-ink shadow-brutal hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none',
    harsh:    'bg-harsh text-white border-ink shadow-brutal hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none',
    ghost:    'bg-white text-ink border-ink shadow-brutal-sm hover:translate-x-[3px] hover:translate-y-[3px] hover:shadow-none',
    volt:     'bg-volt text-ink border-ink shadow-brutal hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none',
};

export default function Button({ variant = 'primary', className = '', children, ...props }) {
    return (
        <button
            className={`
                inline-flex items-center justify-center gap-2
                px-5 py-2.5
                border-2 font-black text-xs uppercase tracking-widest
                transition-all duration-100 ease-out
                disabled:opacity-40 disabled:cursor-not-allowed
                ${VARIANTS[variant] ?? VARIANTS.primary}
                ${className}
            `}
            {...props}
        >
            {children}
        </button>
    );
}
