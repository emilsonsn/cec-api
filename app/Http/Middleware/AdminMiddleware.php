<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $position = auth()->user()->companyPosition->position;

        if(isset($position)){
            switch($position){
                case 'Admin':
                case 'Financial':
                case 'Supplies':
                    return $next($request);
                default:
                    return response()->json(['error' => 'Unauthorized'], 403);
            }
        }
        
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
