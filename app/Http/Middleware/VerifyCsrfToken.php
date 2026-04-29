<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Closure;
use Illuminate\Http\Request;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'logout',
    ];
    
    /**
     * Get the CSRF token from the request.
     * Override to handle JSON requests properly by reading from JSON body.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        // For JSON requests, Laravel doesn't automatically parse JSON body into input()
        // So we need to manually check JSON body first if Content-Type is application/json
        if ($request->isJson() && $request->getContent()) {
            try {
                $json = json_decode($request->getContent(), true);
                if (is_array($json) && isset($json['_token']) && !empty($json['_token'])) {
                    return $json['_token'];
                }
            } catch (\Exception $e) {
                // Ignore JSON parse errors, fall through to default behavior
            }
        }
        
        // Use parent implementation which checks input('_token') and header('X-CSRF-TOKEN')
        return parent::getTokenFromRequest($request);
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = parent::handle($request, $next);
        
        // Add CSRF token to response headers for AJAX requests to prevent "page expired" errors
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            $response->headers->set('X-CSRF-TOKEN', $request->session()->token());
        }
        
        return $response;
    }
}

