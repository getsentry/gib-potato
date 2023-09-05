module.exports = {
  content: [
    './frontend/**/*.{js,vue}',
    './config/**/*.php',
    './templates/**/*.php',
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
};
