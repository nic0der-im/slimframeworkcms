<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\Modulo;
use App\Models\ModuloEnlaces;


class ModulosController extends Controller {

	public function index($request, $response)
	{
		$modulos = Modulo::orderBy('orden_modulo','ASC')->get();
		return $this->container->view->render($response, 'admin_views/admin/modulos.twig', [
			'modulos'=>$modulos,
		]);
	}

	public function crearModulo($request,$response){
		$nombre_modulo = $request->getParam('nombre_modulo');
		$enlace_modulo = $request->getParam('enlace_modulo');
		$icono_modulo = $request->getParam('icono_modulo');
		$orden_modulo = $request->getParam('orden_modulo');

		Modulo::create([
			'nombre_modulo' => $nombre_modulo,
			'enlace_modulo' => $enlace_modulo,
			'icono_modulo' => $icono_modulo,
			'orden_modulo' => $orden_modulo,
		]);

		$this->flash->addMessage('primary', 'Modulo creado exitosamente.');
		$url = $this->router->pathFor('godmode.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function editarModulo($request,$response,$args){
		$id_modulo = $args['id'];
		$nombre_modulo = $request->getParam('nombre_modulo');
		$enlace_modulo = $request->getParam('enlace_modulo');
		$icono_modulo = $request->getParam('icono_modulo');
		$orden_modulo = $request->getParam('orden_modulo');

		Modulo::where('id',$id_modulo)->update([
			'nombre_modulo' => $nombre_modulo,
			'enlace_modulo' => $enlace_modulo,
			'icono_modulo' => $icono_modulo,
			'orden_modulo' => $orden_modulo,
		]);

		$this->flash->addMessage('secondary', 'Modulo editado exitosamente.');
		$url = $this->router->pathFor('godmode.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function eliminarModulo($request,$response,$args){
		$id_modulo = $args['id'];

		Modulo::where('id',$id_modulo)->delete();

		$this->flash->addMessage('secondary', 'Modulo eliminado exitosamente.');
		$url = $this->router->pathFor('godmode.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function crearEnlace($request,$response){
		$id_modulo = $request->getParam('id_modulo');
		$tipo_enlace = $request->getParam('tipo_enlace');
		$nombre = $request->getParam('nombre');
		$url_name = $request->getParam('url_name');
		$orden = $request->getParam('orden');

		ModuloEnlaces::create([
			'id_modulo' => $id_modulo,
			'tipo_enlace' => $tipo_enlace,
			'nombre' => $nombre,
			'url_name' => $url_name,
			'orden' => $orden,
		]);

		$this->flash->addMessage('primary', 'Enlace creado exitosamente.');
		$url = $this->router->pathFor('godmode.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function editarEnlace($request,$response,$args){
		$id_modulo_enlace = $args['id'];
		$id_modulo = $request->getParam('id_modulo');
		$tipo_enlace = $request->getParam('tipo_enlace');
		$nombre = $request->getParam('nombre');
		$url_name = $request->getParam('url_name');
		$orden = $request->getParam('orden');

		ModuloEnlaces::where('id',$id_modulo_enlace)->update([
			'id_modulo' => $id_modulo,
			'tipo_enlace' => $tipo_enlace,
			'nombre' => $nombre,
			'url_name' => $url_name,
			'orden' => $orden,
		]);

		$this->flash->addMessage('secondary', 'Enlace editado exitosamente.');
		$url = $this->router->pathFor('godmode.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function eliminarEnlace($request,$response,$args){
		$id_modulo_enlace = $args['id'];

		ModuloEnlaces::where('id',$id_modulo_enlace)->delete();

		$this->flash->addMessage('secondary', 'Enlace eliminado exitosamente.');
		$url = $this->router->pathFor('godmode.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}
	
}