export default function Input({ label, error, className = '', ...props }) {
    return (
        <div className="flex flex-col gap-1.5">
            {label && (
                <label className="text-[10px] font-black uppercase tracking-[0.15em] text-ash">
                    {label}
                </label>
            )}
            <input
                className={`
                    w-full px-3 py-2.5
                    border-2 border-ink
                    bg-white text-ink text-sm font-bold
                    placeholder:text-concrete placeholder:font-normal
                    focus:outline-none focus:shadow-brutal
                    transition-shadow duration-100
                    ${error ? 'border-harsh shadow-brutal-harsh' : ''}
                    ${className}
                `}
                {...props}
            />
            {error && (
                <p className="text-[10px] font-bold text-harsh uppercase tracking-wide">{error}</p>
            )}
        </div>
    );
}

export function Select({ label, error, className = '', children, ...props }) {
    return (
        <div className="flex flex-col gap-1.5">
            {label && (
                <label className="text-[10px] font-black uppercase tracking-[0.15em] text-ash">
                    {label}
                </label>
            )}
            <select
                className={`
                    w-full px-3 py-2.5
                    border-2 border-ink
                    bg-white text-ink text-sm font-bold
                    focus:outline-none focus:shadow-brutal
                    transition-shadow duration-100
                    ${error ? 'border-harsh' : ''}
                    ${className}
                `}
                {...props}
            >
                {children}
            </select>
        </div>
    );
}
