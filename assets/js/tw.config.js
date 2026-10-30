/**
 * TEX-GIANG RECRUITMENT — CẤU HÌNH TAILWIND DÙNG CHUNG
 * Nạp sau CDN Tailwind, trước mọi style khác:
 *   <script src="https://cdn.tailwindcss.com"></script>
 *   <script src="assets/js/tw.config.js"></script>
 * Token màu / bo góc / khung trang khớp với assets/css/app.css.
 */
tailwind.config = {
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
