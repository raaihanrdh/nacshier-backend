<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Use Laravel's built-in CORS middleware instead
        // This middleware is kept for backward compatibility but should be removed
        // CORS is now handled by config/cors.php and HandleCors middleware
        
        $response = $next($request);
        
        // Only add CORS headers if not already set by HandleCors middleware
        // In production, rely on config/cors.php instead
        if (config('app.env') !== 'production') {
            $allowedOrigin = config('cors.allowed_origins')[0] ?? '*';
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With,Content-Type,X-Token-Auth,Authorization,Accept');
        }
        
        return $response;
    }
}

