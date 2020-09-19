<?php

namespace App\Middleware;
use App\Models\Permisos;
use App\Models\ModuloEnlaces;


class AdminMiddleware extends Middleware
{
	public function __invoke($request, $response, $next)
	{


		if(!$this->container->auth->check())
		{
			$this->container->flash->addMessage('error', 'Debes iniciar sesión para realizar esta acción.');
			return $response->withRedirect($this->container->router->pathFor('auth.signin'));
		}

		if($this->container->auth->empleado()->acceso_sistema <= 1)
		{
			$this->container->flash->addMessage('error', 'No tienes autorización para ingresar a esta área.');
			return $response->withRedirect($this->container->router->pathFor('home'));
		}

		$route = $request->getAttribute('route');
		$ruta_actual = $route ? $route->getName() : null;
		if($ruta_actual != 'admin.index' && $ruta_actual != null) {
			$permisos_enlaces = Permisos::where('id_usuario', $_SESSION['userid'])->get();

			$acceso = false;
			foreach($permisos_enlaces as $pe)
			{
				$data_enlace = ModuloEnlaces::find($pe->id_enlace);
				if($data_enlace->url_name == $ruta_actual or $this->permitidos($ruta_actual))
				{
					$acceso = true;
				}
			}
			if($acceso == false) {

				$sin_acceso = ModuloEnlaces::where('url_name', $ruta_actual)->first();
				if($sin_acceso){
					$this->container->flash->addMessage('error', 'No tienes acceso a ' . $sin_acceso->nombre .'' );
				} else {
					$this->container->flash->addMessage('error', 'No tienes acceso a ' . $ruta_actual .'' );

				}
				return $response->withRedirect($this->container->router->pathFor('admin.index'));
			}
		}

		$response = $next($request, $response);
		return $response;
	}

	public function permitidos($ruta){
		$urls = [];
		$routes = $this->router->getRoutes();
		$permisos = ModuloEnlaces::all()->pluck('url_name')->toArray();
		//SEPARO DE LAS RUTAS QUE TIENEN QUE TENER PERMISOS
		//EN UN GRUPO DE RUTAS SIN PERMISOS
		foreach ($routes as $route) {
			if(!in_array($route->getName(),$permisos) ){
				array_push($urls, $route->getName());
			}
		}
		//COMPRUEBA SI LA RUTA ACTUAL ESTA EN EL GRUPO DE RUTAS SIN PERMISOS
		foreach ($urls as $url) {
			if (strpos($url, $ruta) !== false) {
					return true;
					//SI EXISTE LA RUTA EN EL GRUPO DEVUELVE VERDADERO
			}
		}
		return false;
	}
}

?>
