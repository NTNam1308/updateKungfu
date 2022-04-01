<?php

namespace App\Http\Middleware;

use Closure;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        
        // User can get his info
        // if ($request->route()->parameter('user') == $user->id && ($request->route()->uri == 'api/users/{user}' || $request->route()->uri == 'api/users/{user}/edit')) {
        //     return $next($request);
        // }
        // TODO: fast way to handle moderator, need to improve later
        // if ($user->menuroles == 'user,moderator' && (substr($request->route()->uri, 0, 15) == 'api/kungfu-news' || substr($request->route()->uri, 0, 17) == 'api/category-news')) {
        //     return $next($request);
        // }
        if(empty($user) || !$user->hasRole('admin')){
            return response()->json(['message' => 'Unauthenticated. Admin role required'], 401);
        }
        return $next($request);
    }
}
