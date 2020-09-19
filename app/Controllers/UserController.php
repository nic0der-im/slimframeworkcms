<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\User;
use App\Models\UserSession;
use App\Models\UserActividad;


class UserController extends Controller {

	public function index($request, $response){
		$users = User::orderBy('created_at','DESC')->get();
		return $this->container->view->render($response, 'admin_views/admin/tableroUsuario.twig', [
			'users'=>$users,
		]);
	}

	public function getAll($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$recibo = $request->getParam('recibo',1);

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = User::with(
			'sessiones');

		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('domicilio','like','%'.$value.'%');
					});
				}
			}
		}

		$recordsFiltered = $registros->count();
		$registros = $registros->orderBy($orderColumna,$orderTipo);
		if($length>0){
			$registros = $registros->limit($length);
			$registros = $registros->offset($start)->get();
		} else {
			$registros = $registros->get();
		}

		return $response->withStatus(200)->withJson([
			'draw' => intval($draw),
			'recordsTotal' => intval($recordsTotal),
			'recordsFiltered' => intval($recordsFiltered),
			'data' => $registros,
		]);
	}

	public function getSesiones($request,$response,$args){
		$id_usuario = $args['id'];

		$actividades = UserSession::with('actividades')->where('id_usuario',$id_usuario)->get();
		return $response->withStatus(200)->withJson($actividades); 
	}

	public function getActividades($request,$response,$args){
		$id_session = $args['id'];

		$sesiones = UserActividad::where('id_session',$id_session)->get();
		return $response->withStatus(200)->withJson($sesiones); 
	}

}