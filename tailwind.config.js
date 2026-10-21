/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './404.php',
    './archive.php',
    './footer.php',
    './functions.php',
    './header.php',
    './index.php',
    './page.php',
    './single.php',
    './inc/**/*.php',
    './page-templates/**/*.php',
    './template-parts/**/*.php',
  ],
  theme: {
    fontFamily: {
      primary: ['Inter', 'sans-serif'],   // replace per project
      secondary: ['Inter', 'sans-serif'], // replace per project
      // Montela Map test: FreightBig Pro (Adobe Fonts) falls back to Georgia until a kit is added
      'montela-serif': ['freight-big-pro', 'Georgia', 'serif'],
      'montela-sans': ['Roboto', 'sans-serif'],
    },

    extend: {
      letterSpacing: {
        // wide: '.038em',
        // wider: '.06em',
      },
      colors: {
        // project colors
        primary: '#00CCCC',
        secondary: '#12232B',
        // Montela Map test colors
        'montela-sage': '#8F9B87',
        'montela-brown': '#4E3C2A',
        'montela-cream': '#F8F8F2',
        // dig.id colors (admin/login branding)
        'digid-turquoise': '#00CCCC',
        'digid-light-grey': '#F8F8F8',
        'digid-pink': '#EA526C',
        'digid-black': '#12232B',
        'digid-grey': '#5B7376',
      },
      transitionTimingFunction: {
        'out-expo': 'cubic-bezier(0.16, 1, 0.3, 1)',
      },
      gridTemplateRows: {
        masonry: 'masonry',
      },
    },
  },
  plugins: [],
};
