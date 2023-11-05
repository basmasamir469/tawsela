<?php

namespace App\Http\Middleware;

use App\Traits\Response as TraitsResponse;
use App\Traits\SendNotification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAccountOpened
{
    use TraitsResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user()->account_status == 1)
        {
            return $next($request);
        }
        return $this->dataResponse(null,__('please pay your amounts duo first'),422);
    }
}
