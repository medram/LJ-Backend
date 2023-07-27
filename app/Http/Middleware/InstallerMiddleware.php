<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;


// Installer Middleware
class InstallerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentRoute = Route::currentRouteName();

        if (isInstalled() && in_array($currentRoute, [
            "install.index",
            "install.requirements",
            "install.database",
            "install.database.post",
            "install.verify",
            "install.verify.post",
            "install.database.install",
        ]))
            return redirect("/");

        if (!isInstalled() && $currentRoute == "frontend")
            return redirect()->route("install.index");

        return $next($request);
    }
}
