<?php

namespace App\Middleware;
use App\Models\Redireccion;
use App\Models\Publicacion;


class RedireccionMiddleware extends Middleware
{
	public function __invoke($request, $response, $next)
	{	
		// Obtiene la URL
		$url = $request->getUri();

		$this->container->flash->addMessage('error', 'test: ' . $url);

		//Reemplaza los guiones por espacios para buscar en los titulos de las publicaciones
		$url_wd = str_replace('-', ' ', $url);

		// Busca la URL (sin guiones) en el listado de publicaciones
		$publicacion = Publicacion::where('titulo', 'LIKE', $url_wd)->get();

		// Si encuentra la publicacion, se redirecciona al usuario a la misma		
		if($publicacion->count()) {
			
			$publicacion = $publicacion->first();

			$titulo = trim($publicacion->titulo);
			$titulo = str_replace(' ', '-', $titulo);
			$titulo = strtolower($titulo);

			$url_redireccion = $this->router->pathFor('vehicle.ver', ['titulo'=>$titulo, 'id'=>$publicacion->id]);
		}
		// Si no se encuentra la publicación, se busca en el listado de redirecciones
		else 
		{
			// Busca la URL (con guiones) en el listado de redirecciones
			$redireccion = Redireccion::where('url', '=', $url)->first();
			// Si encuentra la redirección, la hace efectiva
			if($redireccion)
			{
				$url_redireccion = $this->router->pathFor($redireccion->destino);
			}
			// Si no se encuentra la redireccion, se muestra un error de 404
			else 
			{
			}
		}*
		$response = $next($request, $response);
		return $response;
	}
}

?>