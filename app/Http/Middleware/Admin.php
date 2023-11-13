<?php

namespace App\Http\Middleware;

use App\Traits\Response as TraitsResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    use TraitsResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->user()->hasRole('admin'))
        {
            return $next($request);
        }
            return $this->dataResponse(null,__('user is not allowed to perform this action'),403);
    }
}
