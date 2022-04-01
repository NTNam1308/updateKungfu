<?php

namespace App\Http\Middleware;

use Closure;

class Moderator
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
        if ($request->route()->parameter('user') == $user->id && ($request->route()->uri == 'api/users/{user}' || $request->route()->uri == 'api/users/{user}/edit')) {
            return $next($request);
        }
        
        // TODO: fast way to handle moderator, need to improve later
        if ($user->hasRole('coworker') && (substr($request->route()->uri, 0, 15) == 'api/kungfu-news' || substr($request->route()->uri, 0, 17) == 'api/category-news')) {
            return $next($request);
        }

        if(!empty($user) && (strpos($user->menuroles, 'admin') !== false || strpos($user->menuroles, 'moderator') !== false)){
            $request -> merge(['activater' => $user->name]);
            return $next($request);
            
        }
    
        return response()->json(['message' => $request->route()->parameter('user')], 401);
    }
}
