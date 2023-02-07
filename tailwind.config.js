module.exports = {
  content: [
    './frontend/**/*.{js,vue}',
    './config/**/*.php',
    './templates/**/*.php',
  ],
  theme: {
    extend: {},
  },
  plugons: [
    require('@tailwindcss/forms'),
  ],
};
