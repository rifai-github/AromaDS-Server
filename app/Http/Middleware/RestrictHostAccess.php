<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject requests that reach the ERP through a hostname instead of the
 * server IP.
 *
 * Background: "www.wappin.id" (a third party's domain) has an A record
 * pointing at the production IP, so the ERP was being served — and indexed
 * by Google — under a domain nobody on the team registered. nginx is the
 * first line of defence, but its config does not travel with a deploy, so
 * this middleware enforces the same rule from inside the application.
 *
 * Bare IPs, localhost and *.test always pass, so local development, the
 * mobile app and internal health checks are never locked out. Additional
 * hostnames can be whitelisted through APP_ALLOWED_HOSTS once the client
 * puts a real domain in front of the ERP.
 */
class RestrictHostAccess
{
    /**
     * Hostnames that must keep working regardless of configuration.
     *
     * @var list<string>
     */
    private const ALWAYS_ALLOWED = ['localhost'];

    /**
     * Suffixes reserved for local development, never resolvable publicly.
     *
     * @var list<string>
     */
    private const ALWAYS_ALLOWED_SUFFIXES = ['.localhost', '.test'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('features.block_hostname_access', true)) {
            return $next($request);
        }

        if ($this->isAllowed($this->resolveHost($request))) {
            return $next($request);
        }

        // A bare text response on purpose: the app's own 404 view would still
        // leak the ERP's branding to whoever crawled the stray domain.
        return response('Not Found', Response::HTTP_NOT_FOUND, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }

    /**
     * Read the request host, treating a malformed one as not allowed.
     */
    private function resolveHost(Request $request): string
    {
        try {
            return $request->getHost();
        } catch (SuspiciousOperationException) {
            return '';
        }
    }

    private function isAllowed(string $host): bool
    {
        $host = strtolower(trim($host));

        if ($host === '') {
            return false;
        }

        // The intended way in: an IP literal. getHost() keeps IPv6 brackets.
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (in_array($host, self::ALWAYS_ALLOWED, true)) {
            return true;
        }

        foreach (self::ALWAYS_ALLOWED_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return in_array($host, $this->configuredHosts(), true);
    }

    /**
     * Extra hostnames allowed through APP_ALLOWED_HOSTS (comma separated).
     *
     * @return list<string>
     */
    private function configuredHosts(): array
    {
        $configured = config('features.allowed_hosts');

        if (! is_string($configured) || trim($configured) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $host): string => strtolower(trim($host)),
            explode(',', $configured)
        )));
    }
}
