<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIndexPhp
{
    /**
     * Permanently redirect any "/index.php…" URL to the same path without the
     * front controller (query string kept), so "/index.php" never serves a
     * duplicate of the home page or any other page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = (string) parse_url($request->getRequestUri(), PHP_URL_PATH);

        if (! $request->isMethodSafe() || preg_match('#^/index\.php(?=/|$)#', $path) !== 1) {
            return $next($request);
        }

        $clean = substr($path, strlen('/index.php')) ?: '/';
        $query = $request->getQueryString();

        return redirect()->to($request->getSchemeAndHttpHost().$clean.($query !== null ? '?'.$query : ''), 301);
    }
}
