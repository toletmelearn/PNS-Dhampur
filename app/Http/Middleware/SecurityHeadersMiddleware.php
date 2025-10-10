<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only add headers to HTML responses
        if ($this->shouldAddHeaders($response)) {
            $this->addSecurityHeaders($response, $request);
        }

        return $response;
    }

    /**
     * Determine if security headers should be added to the response
     */
    private function shouldAddHeaders($response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        
        return str_contains($contentType, 'text/html') || 
               empty($contentType) || 
               $response instanceof \Illuminate\Http\RedirectResponse;
    }

    /**
     * Add security headers to the response
     */
    private function addSecurityHeaders($response, $request): void
    {
        // Get security configuration
        $headers = config('security.headers', []);
        $cspConfig = config('security.csp', []);

        // XSS Protection
        $response->headers->set('X-XSS-Protection', $headers['x_xss_protection'] ?? '1; mode=block');

        // Content Type Options
        $response->headers->set('X-Content-Type-Options', $headers['x_content_type_options'] ?? 'nosniff');

        // Frame Options
        $response->headers->set('X-Frame-Options', $headers['x_frame_options'] ?? 'DENY');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', $headers['referrer_policy'] ?? 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy', $headers['permissions_policy'] ?? 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy
        if ($cspConfig['enabled'] ?? true) {
            $csp = $this->buildContentSecurityPolicy($cspConfig);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Strict Transport Security (HTTPS only)
        if ($this->isHttps($request)) {
            $response->headers->set('Strict-Transport-Security', 
                $headers['strict_transport_security'] ?? 'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        // Cache control for sensitive pages
        if ($this->isSensitivePage($request)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }

    /**
     * Build Content Security Policy header
     */
    private function buildContentSecurityPolicy($cspConfig): string
    {
        $defaultCsp = "default-src 'self'; " .
                     "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                     "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
                     "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
                     "img-src 'self' data: https:; " .
                     "connect-src 'self'; " .
                     "frame-ancestors 'none'; " .
                     "base-uri 'self'; " .
                     "form-action 'self';";

        // Build custom CSP if configured
        if (!empty($cspConfig)) {
            $directives = [];
            
            if (isset($cspConfig['default_src'])) {
                $directives[] = "default-src {$cspConfig['default_src']}";
            }
            if (isset($cspConfig['script_src'])) {
                $directives[] = "script-src {$cspConfig['script_src']}";
            }
            if (isset($cspConfig['style_src'])) {
                $directives[] = "style-src {$cspConfig['style_src']}";
            }
            if (isset($cspConfig['img_src'])) {
                $directives[] = "img-src {$cspConfig['img_src']}";
            }
            if (isset($cspConfig['font_src'])) {
                $directives[] = "font-src {$cspConfig['font_src']}";
            }
            if (isset($cspConfig['connect_src'])) {
                $directives[] = "connect-src {$cspConfig['connect_src']}";
            }

            if (!empty($directives)) {
                return implode('; ', $directives);
            }
        }

        return $defaultCsp;
    }

    /**
     * Check if the current request is over HTTPS
     */
    private function isHttps($request): bool
    {
        return $request->secure() || 
               $request->header('X-Forwarded-Proto') === 'https' ||
               config('app.force_https', false) ||
               app()->environment('production');
    }

    /**
     * Check if the current page contains sensitive information
     */
    private function isSensitivePage($request): bool
    {
        $sensitiveRoutes = [
            'login', 'register', 'password', 'admin', 'profile', 
            'settings', 'salary', 'fees', 'results', 'attendance'
        ];

        $currentRoute = $request->route()?->getName() ?? '';
        $currentPath = $request->path();

        foreach ($sensitiveRoutes as $route) {
            if (str_contains($currentRoute, $route) || str_contains($currentPath, $route)) {
                return true;
            }
        }

        return false;
    }
}