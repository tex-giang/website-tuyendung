/**
 * Tailwind CSS cho website TEX-GIANG (PHP).
 * Quét class trong PHP view và JS sinh markup động.
 * Build: npm run build:css  → public/assets/css/utilities.css
 */
module.exports = {
  content: [
    './app/Views/**/*.php',
    './public/assets/js/**/*.js',
    './public/index.php',
  ],
  theme: {
    extend: {
      colors: {
        ink: '#161616',
        body: '#2B2B2B',
        muted: '#4F4F4F',
        accent: { DEFAULT: '#087A55', strong: '#06603F', soft: '#F1F7F4' },
        line: '#E8E8E8',
        surface: '#F6F7F9',
      },
      maxWidth: { container: '1200px', wide: '1360px' },
      borderRadius: { card: '12px', media: '20px' },
      boxShadow: { float: '0 6px 20px rgba(0, 0, 0, .06)' },
      fontFamily: {
        sans: ['"Be Vietnam Pro"', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
      },
    },
  },
};
