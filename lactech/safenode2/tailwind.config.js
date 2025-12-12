/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './pages/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
    './app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      colors: {
        dark: {
          950: '#030303',
          900: '#050505',
          850: '#080808',
          800: '#0a0a0a',
          700: '#0f0f0f',
          600: '#141414',
          500: '#1a1a1a',
          400: '#222222',
        },
      },
    },
  },
  plugins: [],
}





