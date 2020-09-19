<?php

namespace App\Controllers\Transacciones;


use App\Models\User;
use App\Models\Individuo;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\Proveedor;
use App\Models\Transacciones\TipoProveedor;
use App\Models\Extras\Provincia;

use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TipoDocumento;
use App\Models\Transacciones\TipoResponsable;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class ProveedorController extends Controller {

	public function todos($request,$response){
		$input = $request->getParam('consulta');
		if(empty($input)){
			$consulta = Proveedor::with('tipoDocumento','provincia')
				->where('estado',1)
				->where('id_tipo_proveedor',2)
				->orderBy('razon_social','desc')
				->limit(10)
				->get();
		} else {
			$consulta = Proveedor::with('tipoDocumento','provincia')
				->where('estado',1)
				->where(function($query) use ($input){
					$query->where('razon_social','like','%'.$input.'%')
					->orWhere('documento','like','%'.$input.'%');
				})
				->orderBy('razon_social','asc')
				->limit(10)
				->get();
		}
		return $response->withStatus(200)->withJson($consulta);
	}

	public function index($request,$response){
		return $this->container->view->render($response, 'admin_views/caja/tableroProveedor.twig', [

		]);
	}

	public function getCargar($request,$response){
		return $this->container->view->render($response, 'admin_views/caja/proveedor.twig', [
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'provincias' => Provincia::all(),
			'tipoResponsable' => TipoResponsable::where('estado',1)->get(),
			'tipoProveedor' => TipoProveedor::where('estado',1)->get(),
		]);
	}

	public function postCargar($request,$response){
		$validation = $this->validator->validate($request, [
			'documento'=>v::numeric(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('proveedor.cargar'));
		}

		$razon_social = $request->getParam('razon_social');
		$id_tipo_proveedor = $request->getParam('id_tipo_proveedor');
		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$documento = $request->getParam('documento');
		$telefono = preg_replace('/[^0-9]/', '', $request->getParam('telefono') );
		$celular = preg_replace('/[^0-9]/', '', $request->getParam('celular') );
		$email = $request->getParam('email');
		$domicilio = $request->getParam('domicilio');
		$numero = $request->getParam('numero');
		$piso = $request->getParam('piso');
		$domicilio_observaciones = $request->getParam('domicilio_observaciones');
		$localidad = $request->getParam('localidad');
		$id_ciudad = $request->getParam('id_ciudad');
		$cp = $request->getParam('cp');
		$id_provincia = $request->getParam('id_provincia');
		$observaciones = $request->getParam('observaciones');

		$dt = new \DateTime();

		Proveedor::create([
			'razon_social' => $razon_social,
			'id_tipo_proveedor' => $id_tipo_proveedor,
			'id_tipo_documento' => $id_tipo_documento,
			'documento' => $documento,
			'telefono' => $telefono,
			'celular' => $celular,
			'email' => $email,
			'domicilio' => $domicilio,
			'numero' => $numero,
			'piso' => $piso,
			'domicilio_observaciones' => $domicilio_observaciones,
			'localidad' => $localidad,
			'cp' => $cp,
			'id_provincia' => $id_provincia,
			'observaciones' => $observaciones,
			'id_usuario' => auth::empleado()->id_usuario ,
			'id_agencia' => auth::empleado()->id_agencia ,
		]);

		$this->flash->addMessage('info', 'El Proveedor fue creado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('proveedor.index'));
	}

	public function getEditar($request,$response,$args){
		$id_proveedor = $args['id'];
		$return_to = $request->getQueryParam('return_to',null);
		$proveedor = Proveedor::where('id',$id_proveedor)->first();

		return $this->container->view->render($response, 'admin_views/caja/proveedor.twig',[
			'old'=>$proveedor,
			'return_to'=>$return_to,
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'provincias' => Provincia::all(),
			'tipoResponsable' => TipoResponsable::where('estado',1)->get(),
			'tipoProveedor' => TipoProveedor::where('estado',1)->get(),
		]); 
	}

	public function getCuenta($request,$response,$args){
		$id_proveedor = $args['id'];

		$proveedor = Proveedor::where('id',$id_proveedor)->first();

		return $this->container->view->render($response, 'admin_views/caja/cuenta/cuenta.twig',[
			'proveedor'=>$proveedor,
		]); 
	}

	public function postEditar($request,$response,$args){
		$id_proveedor = $args['id'];

		$validation = $this->validator->validate($request, [
			'documento'=>v::numeric(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('proveedor.editar'),['id'=>$id_proveedor]);
		}

		$return_to = $request->getParam('return_to',null);

		$razon_social = $request->getParam('razon_social');
		$id_tipo_proveedor = $request->getParam('id_tipo_proveedor');
		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$documento = $request->getParam('documento');
		$telefono = preg_replace('/[^0-9]/', '', $request->getParam('telefono') );
		$celular = preg_replace('/[^0-9]/', '', $request->getParam('celular') );
		$email = $request->getParam('email');
		$domicilio = $request->getParam('domicilio');
		$numero = $request->getParam('numero');
		$piso = $request->getParam('piso');
		$domicilio_observaciones = $request->getParam('domicilio_observaciones');
		$localidad = $request->getParam('localidad');
		$id_ciudad = $request->getParam('id_ciudad');
		$cp = $request->getParam('cp');
		$id_provincia = $request->getParam('id_provincia');
		$observaciones = $request->getParam('observaciones');

		$dt = new \DateTime();

		Proveedor::where('id',$id_proveedor)->update([
			'razon_social' => $razon_social,
			'id_tipo_proveedor' => $id_tipo_proveedor,
			'id_tipo_documento' => $id_tipo_documento,
			'documento' => $documento,
			'telefono' => $telefono,
			'celular' => $celular,
			'email' => $email,
			'domicilio' => $domicilio,
			'numero' => $numero,
			'piso' => $piso,
			'domicilio_observaciones' => $domicilio_observaciones,
			'localidad' => $localidad,
			'cp' => $cp,
			'id_provincia' => $id_provincia,
			'observaciones' => $observaciones,
		]);

		if(!is_null($return_to)){
			$this->flash->addMessage('info', 'El Proveedor fue editado Exitosamente.');
			return $response->withRedirect('/'.$return_to);
		}

		$this->flash->addMessage('info', 'El Proveedor fue editado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('proveedor.index'));
	}

	public function getEliminar($request,$response,$args){
		$id_proveedor = $args['id'];
		$proveedor = Proveedor::where('id',$id_proveedor)->first();
		Proveedor::where('id',$id_proveedor)->update([
			'estado' => 0,
		]);

		return $response->withStatus(200)->withJson([
			'message' => 'El Proveedor: '.$proveedor->razon_social.' fue eliminado con exito.',
		]); 
	}


	public function getAll($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Proveedor::with('tipoDocumento','provincia','individuo')
		->where('estado',1);
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('razon_social','like','%'.$value.'%')
							->orWhere('documento','like','%'.$value.'%')
							->orWhere('telefono','like','%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('domicilio','like','%'.$value.'%')
							->orWhere('domicilio_observaciones','like','%'.$value.'%')
							->orWhereIn('id_tipo_documento',function($d) use ($value){
								$d->select('id')->from('tra_tipos_documento')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_provincia',function($d) use ($value){
								$d->select('id')->from('provincias')->where('nombre','like','%'.$value.'%');
							});
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
}