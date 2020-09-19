<?php

namespace App\Middleware;

use App\Models\UserSession;
use App\Models\UserActividad;

class SessionMiddleware extends Middleware
{
	public function __invoke($request, $response, $next)
	{
		$session = UserSession::where('id',$_SESSION['id_session'])->first();
		if($session->estado == 0){
			$this->container->flash->addMessage('error', 'Probablemente tiene otra session en proseso o esta Expiro. Vuelva a ingresar.');
			$this->container->auth->logout();
			return $response->withStatus(301)->withRedirect($this->container->router->pathFor('auth.signin'));
		} else {
			$route = $request->getAttribute('route');
			$pattern = $route ? $route->getPattern() : null;
			$arguments = $route ? $route->getArguments() : [];
			UserActividad::create([
				'id_session' => $session->id,
				'route' => $pattern,
				'method' => $request->getMethod(),
				'status_code' => $response->getStatusCode(),
				'arguments' => json_encode($arguments),
			]);
		}
		
		$response = $next($request, $response);
		return $response;
	}
}
