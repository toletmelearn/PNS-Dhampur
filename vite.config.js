import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import legacy from '@vitejs/plugin-legacy';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';
    
    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css', 
                    'resources/js/app.js',
                    'resources/js/dashboard.js',
                    'resources/js/student-management.js',
                    'resources/js/attendance.js',
                    'resources/js/exam-management.js',
                    'resources/js/reports.js'
                ],
                refresh: true,
            }),
            // Legacy browser support for production
            ...(isProduction ? [
                legacy({
                    targets: ['defaults', 'not IE 11'],
                    additionalLegacyPolyfills: ['regenerator-runtime/runtime']
                })
            ] : []),
            // Bundle analyzer for production builds
            ...(mode === 'analyze' ? [
                visualizer({
                    filename: 'dist/stats.html',
                    open: true,
                    gzipSize: true,
                    brotliSize: true,
                })
            ] : [])
        ],
        build: {
            // Production optimizations
            ...(isProduction && {
                minify: 'terser',
                terserOptions: {
                    compress: {
                        drop_console: true,
                        drop_debugger: true,
                        pure_funcs: ['console.log', 'console.info'],
                        passes: 2,
                    },
                    mangle: {
                        safari10: true,
                    },
                    format: {
                        comments: false,
                    },
                },
                rollupOptions: {
                    output: {
                        manualChunks: {
                            // Core vendor libraries
                            vendor: ['axios', 'lodash'],
                            // UI libraries
                            ui: ['bootstrap', 'jquery'],
                            // Chart libraries (if used)
                            charts: ['chart.js'],
                            // Utility libraries
                            utils: ['moment', 'sweetalert2'],
                        },
                        // Optimize chunk file names
                        chunkFileNames: 'assets/js/[name]-[hash].js',
                        entryFileNames: 'assets/js/[name]-[hash].js',
                        assetFileNames: (assetInfo) => {
                            const info = assetInfo.name.split('.');
                            const ext = info[info.length - 1];
                            if (/\.(css)$/.test(assetInfo.name)) {
                                return 'assets/css/[name]-[hash].[ext]';
                            }
                            if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name)) {
                                return 'assets/images/[name]-[hash].[ext]';
                            }
                            if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
                                return 'assets/fonts/[name]-[hash].[ext]';
                            }
                            return 'assets/[ext]/[name]-[hash].[ext]';
                        },
                    },
                    // External dependencies (CDN)
                    external: isProduction ? [] : [],
                },
                chunkSizeWarningLimit: 500,
                assetsInlineLimit: 2048, // Inline assets smaller than 2KB
                // Enable source maps for production debugging
                sourcemap: false,
                // CSS code splitting
                cssCodeSplit: true,
                // Optimize CSS
                cssMinify: true,
            }),
        },
        server: {
            hmr: {
                host: 'localhost',
                port: 5173,
            },
            // Enable compression in dev mode
            middlewareMode: false,
        },
        // Enhanced asset optimization
        assetsInclude: [
            '**/*.woff', 
            '**/*.woff2', 
            '**/*.ttf', 
            '**/*.eot',
            '**/*.pdf',
            '**/*.doc',
            '**/*.docx'
        ],
        // CSS preprocessing optimizations
        css: {
            preprocessorOptions: {
                scss: {
                    additionalData: `@import "resources/sass/variables.scss";`
                }
            },
            // PostCSS optimizations
            postcss: {
                plugins: isProduction ? [
                    require('autoprefixer'),
                    require('cssnano')({
                        preset: ['default', {
                            discardComments: { removeAll: true },
                            normalizeWhitespace: true,
                            minifySelectors: true,
                        }]
                    })
                ] : []
            }
        },
        // Dependency optimization
        optimizeDeps: {
            include: [
                'axios',
                'lodash',
                'bootstrap',
                'jquery'
            ],
            exclude: [
                // Exclude large libraries that should be loaded on demand
            ]
        },
        // Performance optimizations
        esbuild: {
            // Tree shaking optimizations
            treeShaking: true,
            // Remove unused imports
            ignoreAnnotations: false,
        },
        // Experimental features for better performance
        experimental: {
            renderBuiltUrl(filename, { hostType }) {
                if (hostType === 'js') {
                    return { js: `/${filename}` };
                } else {
                    return { relative: true };
                }
            }
        }
    };
});
