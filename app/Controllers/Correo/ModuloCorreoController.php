<?php

namespace App\Controllers\Correo;

use App\Models\Correo\ModuloCorreo;
use App\Models\ModuloEnlaces;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class ModuloCorreoController extends Controller {

	public function index($request,$response){
		$disponibles = ['cliente.datero.index','cajas.reporte.salta','cajas.reporte.oran'];
		$enlaces =  ModuloEnlaces::where(function($q) use ($disponibles){
			for ($i=0; $i < count($disponibles) ; $i++) { 
				$q->orwhere('url_name', 'like',  '%' . $disponibles[$i] .'%');
			}
		})->get();
		$correos = ModuloCorreo::where('estado',1)->get();
		return $this->container->view->render($response, 'admin_views/notificaciones/tableroNotificacionCorreo.twig',[
			'correos' => $correos,
			'enlaces' => $enlaces,
		]);
	}

	public function post($request,$response){
		$id_enlace = $request->getParam('id_enlace');
		$email = $request->getParam('email');

		$enlace = ModuloEnlaces::where('id',$id_enlace)->first();
		ModuloCorreo::create([
			'id_enlace' => $enlace->id,
			'url_name' => $enlace->url_name,
			'email' => $email,
		]);

		$this->flash->addMessage('info', 'Correo agregado.');
		return $response->withRedirect($this->router->pathFor('correo.modulo'));
	}

	public function eliminar($request,$response,$args){

		ModuloCorreo::where('id',$args['id'])->update([
			'estado' => 0,
		]);

		$this->flash->addMessage('info', 'Correo Eliminado.');
		return $response->withRedirect($this->router->pathFor('correo.modulo'));
	}
}