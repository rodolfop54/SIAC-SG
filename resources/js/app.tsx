import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-500.css';
import '@fontsource/inter/latin-600.css';
import '@fontsource/inter/latin-700.css';
import '../css/app.css';
const pages = import.meta.glob('./pages/*.tsx', { eager: true });
// History restores cached props; conversational actions must use the live session.
router.on('navigate', ({ detail }) => {
  if (detail.type === 'history' && detail.page.component === 'Bot') {
    router.reload({ only: ['messages', 'patient', 'onboarding', 'current', 'auth'] });
  }
});
createInertiaApp({
  title: (title) => `${title} · SIAC-SG`,
  resolve: (name) => (pages[`./pages/${name}.tsx`] as { default: React.ComponentType }).default,
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: { color: '#166534' },
});
