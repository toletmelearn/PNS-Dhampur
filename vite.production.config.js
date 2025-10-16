import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
      ],
      refresh: true,
    }),
  ],
  
  // Production optimization settings
  build: {
    outDir: 'public/build',
    assetsDir: 'assets',
    sourcemap: false,
    minify: 'esbuild',
    
    // Enable brotli compression
    brotliSize: false,
    
    // Chunk optimization
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: [
            'vue',
            'axios',
            'lodash',
          ],
          charts: [
            'chart.js',
            'vue-chartjs',
          ],
        },
        // Cache busting with content hash
        chunkFileNames: 'assets/[name]-[hash].js',
        entryFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
    
    // Enable gzip compression
    reportCompressedSize: true,
  },
  
  // Server configuration for production
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
  },
  
  // CSS optimization
  css: {
    devSourcemap: false,
    postcss: './postcss.config.js',
  },
  
  // Resolve aliases for better tree shaking
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources'),
      '~': resolve(__dirname, './node_modules'),
    },
  },
  
  // Environment variables
  define: {
    'process.env': process.env,
  },
  
  // Performance optimization
  optimizeDeps: {
    include: ['axios', 'lodash', 'vue'],
    exclude: [],
  },
})