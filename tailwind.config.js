/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',

  content: [
    './app/Views/**/*.php',
    './public/assets/app/js/**/*.js',
    './public/assets/js/**/*.js',
    './src/**/*.{js,jsx,ts,tsx}',
  ],

  theme: {
    extend: {
      fontFamily: {
        sans:  ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        brand: ['Outfit', 'Plus Jakarta Sans', 'sans-serif'],
      },
      colors: {
        // Palet dinamis: nilai aktual diisi lewat CSS variable oleh
        // App\Libraries\Theme (fitur Theme Customizer di Pengaturan).
        primary: {
          50:  'rgb(var(--primary-50-rgb) / <alpha-value>)',
          100: 'rgb(var(--primary-100-rgb) / <alpha-value>)',
          200: 'rgb(var(--primary-200-rgb) / <alpha-value>)',
          300: 'rgb(var(--primary-300-rgb) / <alpha-value>)',
          400: 'rgb(var(--primary-400-rgb) / <alpha-value>)',
          500: 'rgb(var(--primary-500-rgb) / <alpha-value>)',
          600: 'rgb(var(--primary-600-rgb) / <alpha-value>)',
          700: 'rgb(var(--primary-700-rgb) / <alpha-value>)',
          800: 'rgb(var(--primary-800-rgb) / <alpha-value>)',
          900: 'rgb(var(--primary-900-rgb) / <alpha-value>)',
          950: '#172554',
        },
        brand: {
          50:  'rgb(var(--brand-50-rgb) / <alpha-value>)',
          100: 'rgb(var(--brand-100-rgb) / <alpha-value>)',
          200: 'rgb(var(--brand-200-rgb) / <alpha-value>)',
          300: 'rgb(var(--brand-300-rgb) / <alpha-value>)',
          400: 'rgb(var(--brand-400-rgb) / <alpha-value>)',
          500: 'rgb(var(--brand-500-rgb) / <alpha-value>)',
          600: 'rgb(var(--brand-600-rgb) / <alpha-value>)',
          700: 'rgb(var(--brand-700-rgb) / <alpha-value>)',
          800: 'rgb(var(--brand-800-rgb) / <alpha-value>)',
          900: 'rgb(var(--brand-900-rgb) / <alpha-value>)',
          950: '#4c0519',
        },
        // Alias: utility rose/blue/violet yang sudah dipakai di view ikut tema.
        rose: {
          50:  'rgb(var(--brand-50-rgb) / <alpha-value>)',
          100: 'rgb(var(--brand-100-rgb) / <alpha-value>)',
          200: 'rgb(var(--brand-200-rgb) / <alpha-value>)',
          300: 'rgb(var(--brand-300-rgb) / <alpha-value>)',
          400: 'rgb(var(--brand-400-rgb) / <alpha-value>)',
          500: 'rgb(var(--brand-500-rgb) / <alpha-value>)',
          600: 'rgb(var(--brand-600-rgb) / <alpha-value>)',
          700: 'rgb(var(--brand-700-rgb) / <alpha-value>)',
          800: 'rgb(var(--brand-800-rgb) / <alpha-value>)',
          900: 'rgb(var(--brand-900-rgb) / <alpha-value>)',
          950: '#4c0519',
        },
        blue: {
          50:  'rgb(var(--primary-50-rgb) / <alpha-value>)',
          100: 'rgb(var(--primary-100-rgb) / <alpha-value>)',
          200: 'rgb(var(--primary-200-rgb) / <alpha-value>)',
          300: 'rgb(var(--primary-300-rgb) / <alpha-value>)',
          400: 'rgb(var(--primary-400-rgb) / <alpha-value>)',
          500: 'rgb(var(--primary-500-rgb) / <alpha-value>)',
          600: 'rgb(var(--primary-600-rgb) / <alpha-value>)',
          700: 'rgb(var(--primary-700-rgb) / <alpha-value>)',
          800: 'rgb(var(--primary-800-rgb) / <alpha-value>)',
          900: 'rgb(var(--primary-900-rgb) / <alpha-value>)',
          950: '#172554',
        },
        violet: {
          50:  'rgb(var(--accent-50-rgb) / <alpha-value>)',
          100: 'rgb(var(--accent-100-rgb) / <alpha-value>)',
          200: 'rgb(var(--accent-200-rgb) / <alpha-value>)',
          300: 'rgb(var(--accent-300-rgb) / <alpha-value>)',
          400: 'rgb(var(--accent-400-rgb) / <alpha-value>)',
          500: 'rgb(var(--accent-500-rgb) / <alpha-value>)',
          600: 'rgb(var(--accent-600-rgb) / <alpha-value>)',
          700: 'rgb(var(--accent-700-rgb) / <alpha-value>)',
          800: 'rgb(var(--accent-800-rgb) / <alpha-value>)',
          900: 'rgb(var(--accent-900-rgb) / <alpha-value>)',
          950: '#2e1065',
        },
        // indigo/purple sering dipakai berdampingan dengan blue/violet pada
        // gradient; ikut memakai variabel agar gradien tetap senada.
        indigo: {
          50:  'rgb(var(--indigo-50-rgb) / <alpha-value>)',
          100: 'rgb(var(--indigo-100-rgb) / <alpha-value>)',
          200: 'rgb(var(--indigo-200-rgb) / <alpha-value>)',
          300: 'rgb(var(--indigo-300-rgb) / <alpha-value>)',
          400: 'rgb(var(--indigo-400-rgb) / <alpha-value>)',
          500: 'rgb(var(--indigo-500-rgb) / <alpha-value>)',
          600: 'rgb(var(--indigo-600-rgb) / <alpha-value>)',
          700: 'rgb(var(--indigo-700-rgb) / <alpha-value>)',
          800: 'rgb(var(--indigo-800-rgb) / <alpha-value>)',
          900: 'rgb(var(--indigo-900-rgb) / <alpha-value>)',
          950: '#1e1b4b',
        },
        purple: {
          50:  'rgb(var(--purple-50-rgb) / <alpha-value>)',
          100: 'rgb(var(--purple-100-rgb) / <alpha-value>)',
          200: 'rgb(var(--purple-200-rgb) / <alpha-value>)',
          300: 'rgb(var(--purple-300-rgb) / <alpha-value>)',
          400: 'rgb(var(--purple-400-rgb) / <alpha-value>)',
          500: 'rgb(var(--purple-500-rgb) / <alpha-value>)',
          600: 'rgb(var(--purple-600-rgb) / <alpha-value>)',
          700: 'rgb(var(--purple-700-rgb) / <alpha-value>)',
          800: 'rgb(var(--purple-800-rgb) / <alpha-value>)',
          900: 'rgb(var(--purple-900-rgb) / <alpha-value>)',
          950: '#3b0764',
        },
      },
      borderRadius: {
        '4xl': '1.75rem',
        '5xl': '2rem',
      },
      animation: {
        'fade-in-up': 'fadeInUp 0.4s ease-out',
        'slide-in':   'slideIn 0.3s ease-out',
      },
      keyframes: {
        fadeInUp: {
          '0%':   { opacity: '0', transform: 'translateY(16px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideIn: {
          '0%':   { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(0)' },
        },
      },
    },
  },

  plugins: [
    require('@tailwindcss/typography'),
  ],
};
