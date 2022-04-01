<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class MultiDevice
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
        $user = User::where('email', auth()->user()->email)->select('last_session','access_token_mobile','limited')->first();

        if ($user->limited == 2) {
            return $next($request);
        }
        
        if (
                !(
                    ( isset($user->last_session) && strlen($user->last_session) > 0 && $request['token'] == $user->last_session) ||
                    ( isset($user->access_token_mobile) && strlen($user->access_token_mobile) > 0 && $request['token'] == $user->access_token_mobile )
                ) 
            ) 
        {
            return response()->json(['message' => 'Tài khoản đã đăng nhập nơi khác', 'error_string' => 'LIMIT_DEVICES'], 423);
        }
        return $next($request);
        
    }
}
