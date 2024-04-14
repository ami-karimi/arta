<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Utility\Helper;

class TrustApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->header('API_TOKEN')){
            return \response()->json(['status' => false,'message' => 'Error'],502);
        }
        if ($request->header('API_TOKEN') !== Helper::s('api_key')){
            return \response()->json(['status' => false,'message' => '403 forbidden Role'],403);
        }
        return $next($request);
    }
}
