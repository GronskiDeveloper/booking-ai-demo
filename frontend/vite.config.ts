import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Frontend na :5173 (Vite default), backend PHP na :8000 (php -S).
// Proxy: /api/* → :8000 (żeby cały ruch przechodził przez to samo origin
// w dev, uniknąć CORS-a w developerce).
export default defineConfig({
  server: {
    port: 5173,
    open: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
      },
    },
  },
  plugins: [react()],
});
