/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,jsx,ts,tsx}',
    ],
    theme: {
        extend: {
            // ── Neo-Brutalism Color Palette ───────────────────────
            colors: {
                mint:     '#00FF94',   // approve, accepted, success
                harsh:    '#FF6200',   // reject, defective, warning
                volt:     '#FFE600',   // pending, submitted
                brutal:   '#0066FF',   // scheduled, primary action
                lavender: '#B388FF',   // reserved
                ink:      '#0A0A0A',
                chalk:    '#FAFAFA',
                concrete: '#E5E5E5',
                ash:      '#71717A',
            },
            fontFamily: {
                sans:    ['Inter', 'system-ui', 'sans-serif'],
                display: ['Space Grotesk', 'Inter', 'system-ui', 'sans-serif'],
                mono:    ['JetBrains Mono', 'Courier New', 'monospace'],
            },
            fontSize: {
                micro: ['10px', { lineHeight: '14px', letterSpacing: '0.15em' }],
            },
            boxShadow: {
                'brutal-sm':   '3px 3px 0px 0px #0A0A0A',
                'brutal':      '5px 5px 0px 0px #0A0A0A',
                'brutal-lg':   '8px 8px 0px 0px #0A0A0A',
                'brutal-mint': '5px 5px 0px 0px #00FF94',
                'brutal-harsh':'5px 5px 0px 0px #FF6200',
            },
            borderWidth: {
                '3': '3px',
            },
        },
    },
    plugins: [],
};
