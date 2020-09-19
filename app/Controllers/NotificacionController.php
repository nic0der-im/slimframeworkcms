<?php

namespace App\Controllers;

use Slim\Views\Twig as View;
use App\Models\Notificaciones\Notificaciones;

use App\Models\UserSession;
use App\Models\UserTerminal;

use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;
use App\Auth\auth;

class NotificacionController extends Controller {
	public function enviar($request, $response)
	{
		return $this->container->view->render($response, 'admin_views/notificaciones/enviar.twig', [
		]);
	}

	public function index($request, $response)
	{
		return $this->container->view->render($response, 'admin_views/notificaciones/index.twig', [
			'notificaciones_totales' => auth::notificaciones(0),
		]);
	}

	public function ver($request, $response, array $args)
	{
		$notificacion = Notificaciones::find($args['id']);
		$notificacion->estado = 1;
		$notificacion->save();
		return true;
	}

	public function cargar($request, $response, array $args)
	{
		 /* canales:
			| 1-> notificacion web
			| 2-> worker chrome
			| 3-> worker app
			| 4-> correo electronico
			| 5-> correo electronico alternativo
		|*/

		// notificación web -> se crea con la SQL
		Notificaciones::Create([
			'id_usuario' => $args['id'],
			'prioridad' => $args['prioridad'],
			'categoria' => $args['cat'],
			'mensaje' =>$args['mensaje']
		]);

		// worker chrome:

		return true;
	}

	public function postEnviar($request, $response, array $args)
	{
		 /* canales:
			| 1-> notificacion web
			| 2-> worker chrome
			| 3-> worker app
			| 4-> correo electronico
			| 5-> correo electronico alternativo
		|*/

		// notificación web -> se crea con la SQL
		Notificaciones::Create([
			'id_usuario' => $args['id'],
			'prioridad' => $args['prioridad'],
			'categoria' => $args['cat'],
			'mensaje' =>$args['mensaje']
		]);

		// worker chrome:

		return true;
	}

	public function asociarUsuario($request,$response){
		$id_usuario = $_SESSION['userid'];
		$latitud = $request->getQueryParam('latitud',0);
		$longitud = $request->getQueryParam('longitud',0);
		$os_user_id = $request->getQueryParam('user_id',null);
		if($os_user_id!=null){
			$terminal = UserTerminal::where('os_user_id',$os_user_id)->first();
			if($terminal){
				$terminal->estado = 1;
				$terminal->save();
			} else {
				$terminal = UserTerminal::create([
					'id_usuario' => $id_usuario,
					'latitud' => $latitud,
					'longitud' => $longitud,
					'os_user_id' => $os_user_id,
				]);
				UserSession::where('id_usuario',$id_usuario)->where('estado',1)->update([
					'id_terminal' => $terminal->id,
				]);
			}
		}
		return $response->withJson(['success'=>true]);
	}

	public function desasociarUsuario($request,$response){
		$id_usuario = $_SESSION['userid'];
		$os_user_id = $request->getQueryParam('user_id',null);
		if($os_user_id!=null){
			$terminal = UserTerminal::where('os_user_id',$os_user_id)->first();
			$terminal->estado = 0;
			$terminal->save();
		}
		return $response->withJson(['success'=>true]);
	}

	public function terminal($request,$response){
		$id_usuario = $_SESSION['userid'];
		$os_user_id = $request->getQueryParam('user_id',null);
		if($os_user_id!=null){
			$terminal = UserTerminal::where('os_user_id',$os_user_id)->first();
			if($terminal){
				UserSession::where('id_usuario',$id_usuario)
				->where('estado',1)
				->where(function($q){
					$q->where('os_user_id', '=', '')->orWhereNull('os_user_id');
				})
				->update([
					'id_terminal' => $terminal->id,
				]);
			}

		}
		return $response->withJson(['success'=>true]);
	}
}
