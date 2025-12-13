/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#111827',
        accent: '#60a5fa',
        muted: '#9ca3af',
        success: '#10b981',
        danger: '#ef4444',
      },
    },
  },
  plugins: [],
};
