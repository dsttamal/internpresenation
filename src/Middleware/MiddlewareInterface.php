<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Middleware Interface
 * 
 * All middleware classes must implement this interface.
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
