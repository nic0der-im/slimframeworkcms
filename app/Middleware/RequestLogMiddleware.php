<?php

namespace App\Middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class RequestLogMiddleware extends Middleware {

	public function __invoke(Request $request,Response $response, $next){
		$route = $request->getAttribute('route');
		$pattern = $route ? $route->getPattern() : null;
		$arguments = $route ? $route->getArguments() : null;
    $this->logger->info($request->getMethod() . ' ' . $pattern, [$arguments]);
    $response = $next($request, $response);
    //$this->logger->info($response->getStatusCode() . ' ' . $response->getReasonPhrase(), [(string)$response->getBody()]);
    $this->logger->info($response->getStatusCode() . ' ' . $response->getReasonPhrase());

    return $response;
	}
}