/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './*.php',
    './includes/**/*.php',
    './admin/**/*.php',
    './js/**/*.js'
  ],
  theme: {
    extend: {
      fontFamily: {
        mono: ['"JetBrains Mono"', '"IBM Plex Mono"', 'Courier', 'monospace'],
        blog: ['"JetBrains Mono"', 'Courier', 'monospace'],
        sans: ['"Inter"', 'system-ui', '-apple-system', 'sans-serif'],
        serif: ['"Newsreader"', '"Playfair Display"', 'Georgia', 'serif'],
      },
      colors: {
        paper: {
          50: '#fbfbf9',
          100: '#f6f6f2',
          200: '#eaeae3',
          300: '#d7d7cd',
          800: '#1b1d22',
          900: '#121418',
          950: '#0a0b0d',
        },
        ink: {
          50: '#f4f4f5',
          100: '#e4e4e7',
          400: '#a1a1aa',
          500: '#71717a',
          700: '#3f3f46',
          900: '#18181b',
          950: '#09090b',
        },
        accent: {
          DEFAULT: '#c25e38',
          hover: '#a84e2c',
          light: '#f5ebe6',
          dark: '#e06d45',
        }
      },
      maxWidth: {
        'reading': '68ch',
      },
      animation: {
        'typewriter': 'typewriter 2.5s steps(40, end) 1s 1 normal both, blinkTextCursor 750ms steps(40) infinite normal',
        'fade-in': 'fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(6px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        }
      }
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}
