import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: parseInt(process.env.VITE_PORT || '3001'),
    strictPort: false,
    proxy: {
      '/api': {
        target: process.env.VITE_API_URL || 'http://localhost:7849',
        changeOrigin: true,
      }
    }
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
  },
  // Use .env file, Vite will automatically load it
  // To prevent .env.local from being loaded, we'll handle it in start.sh
  envDir: '.',
  envPrefix: 'VITE_',
})

