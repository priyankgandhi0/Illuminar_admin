<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = config('app.locale', 'en');

        if (in_array($locale, ['en', 'pt'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
