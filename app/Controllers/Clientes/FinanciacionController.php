<?php

namespace App\Controllers;

use Slim\Views\Twig as View;


use App\Models\User;
use App\Models\Individuo;
use App\Models\Consulta;

use App\Models\Negocio\Cliente;
use App\Models\Negocio\Datero;
use App\Models\Negocio\CarpetaCliente;

use App\Models\Extras\Provincia;
use App\Models\Extras\Ciudad;
use App\Models\Extras\Profesion;
use App\Models\Extras\ActividadEconomica;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\VehiculoHistorial;
use App\Models\Vehiculos\Usado;
use App\Models\Vehiculos\Marca;

use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TipoDocumento;
use App\Models\Transacciones\TipoResponsable;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

class ClientController extends Controller {

	public function index($request, $response)
	{
		$consultas = Consulta::orderBy('id','ASC')->get();
		return $this->container->view->render($response, 'admin_views/clientes/index.twig', [
			'consultas'=>$consultas,
		]);
	}

	public function consultas($request, $response)
	{
		$consultas = Consulta::orderBy('created_at','DESC')->get();
		return $this->container->view->render($response, 'admin_views/clientes/consultas.twig', [
			'consultas'=>$consultas,
		]);
	}

	public function getCargar($request, $response){
		$id_carpeta = $request->getQueryParam('id_carpeta',null);
		$return_to = null;
		if(!is_null($id_carpeta)){
			$return_to = $this->router->pathFor('carpeta',['id'=>$id_carpeta]);
		}
		return $this->container->view->render($response, 'admin_views/clientes/nuevo-cliente.twig',[
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'provincias' => Provincia::all(),
			'tipoResponsable' => TipoResponsable::where('estado',1)->get(),
			'id_carpeta' => $id_carpeta,
			'return_to'=>$return_to,
		]);
	}

	public function postCargar($request,$response){
		$id_usuario = auth::empleado()->id_usuario;
		$validation = $this->validator->validate($request, [
			'nombre'=>v::alpha('nÑ'),
			'apellido'=>v::alpha('nÑ'),
			'documento'=>v::numeric(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('cliente.nuevo-cliente'));
		}

		$id_carpeta = $request->getParam('id_carpeta',0);
		$nombre = $request->getParam('nombre');
		$apellido = $request->getParam('apellido');
		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$id_tipo_responsable = $request->getParam('id_tipo_responsable');
		$documento = $request->getParam('documento');
		$fecha_nacimiento = $request->getParam('fecha_nacimiento');
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

		$cliente = Cliente::create([
			'nombre' => $nombre,
			'apellido' => $apellido,
			'id_tipo_documento' => $id_tipo_documento,
			'id_tipo_responsable' => $id_tipo_responsable,
			'documento' => $documento,
			'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento),
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
			'id_usuario' =>  $id_usuario,
			'id_agencia' => auth::empleado()->id_agencia ,
		]);

		if($id_carpeta>0){
			CarpetaCliente::create([
				'id_cliente' => $cliente->id,
				'id_carpeta'=> $id_carpeta,
				'id_usuario' => $id_usuario,
			]);
			$this->flash->addMessage('info', 'El cliente fue creado Exitosamente.');
			return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$id_carpeta]));
		}

		$this->flash->addMessage('info', 'El cliente fue creado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('cliente.index'));
	}

	public function getEditar($request,$response,$args){
		$id_cliente = $args['id'];
		$return_to = $request->getQueryParam('return_to',null);
		$cliente = Cliente::where('id',$id_cliente)->first();

		return $this->container->view->render($response, 'admin_views/clientes/nuevo-cliente.twig',[
			'old'=>$cliente,
			'return_to'=>$return_to,
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'provincias' => Provincia::all(),
			'tipoResponsable' => TipoResponsable::where('estado',1)->get(),
		]); 
	}

	public function postEditar($request,$response,$args){
		$id_cliente = $args['id'];
		$return_to = $request->getParam('return_to',null);

		$validation = $this->validator->validate($request, [
			'nombre'=>v::alpha('nÑ'),
			'apellido'=>v::alpha('nÑ'),
			'documento'=>v::numeric(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('cliente.editar',
				['id'=>$id_cliente],
				['return_to'=>$return_to]
			));
		}


		$nombre = $request->getParam('nombre');
		$apellido = $request->getParam('apellido');
		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$id_tipo_responsable = $request->getParam('id_tipo_responsable');
		$documento = $request->getParam('documento');
		$fecha_nacimiento = $request->getParam('fecha_nacimiento');
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

		Cliente::where('id',$id_cliente)->update([
			'nombre' => $nombre,
			'apellido' => $apellido,
			'id_tipo_documento' => $id_tipo_documento,
			'id_tipo_responsable' => $id_tipo_responsable,
			'documento' => $documento,
			'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento),
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
			$this->flash->addMessage('info', 'El cliente fue editado Exitosamente.');
			return $response->withRedirect('/'.$return_to);
		}

		$this->flash->addMessage('info', 'El cliente fue editado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('cliente.index'));
	}

	public function getEliminar($request,$response,$args){
		$id_cliente = $args['id'];
		$cliente = Cliente::where('id',$id_cliente)->first();
		Cliente::where('id',$id_cliente)->update([
			'estado' => 0,
		]);

		return $response->withStatus(200)->withJson([
			'message' => 'El cliente '.$cliente->apellido.' '.$cliente->nombre.' fue eliminado con exito.',
		]); 
	}

	public function todos($request,$response){
		$input = $request->getParam('consulta');
		if(empty($input)){
			$consulta = Cliente::with('tipoDocumento','provincia')
				->where('estado',1)
				->where('id_usuario',auth::empleado()->id_usuario)
				->orderBy('apellido','desc')
				->limit(10)
				->get();
		} else {
			$consulta = Cliente::with('tipoDocumento','provincia')
				->where('estado',1)
				->where(function($query) use ($input){
					$query->where('apellido','like','%'.$input.'%')
					->orWhere('nombre','like','%'.$input.'%')
					->orWhere('documento','like','%'.$input.'%');
				})
				->orderBy('apellido','asc')
				->limit(10)
				->get();
		}
		return $response->withStatus(200)->withJson($consulta);
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

		$registros = Cliente::with('tipoDocumento','provincia','individuo')
		->where('estado',1);
		if(auth::empleado()->id_puesto == 2){
			$registros = $registros->where('id_usuario',auth::empleado()->id_usuario);
		}
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere(DB::raw("DATE_FORMAT(fecha_nacimiento,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('nombre','like','%'.$value.'%')
							->orWhere('apellido','like','%'.$value.'%')
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

	public function getCiudad($request,$response){
		$input = $request->getParam('consulta');
		$id_provincia = $request->getParam('id_provincia');
		if(empty($input)){
			$consulta = Ciudad::with('provincia')->where('id_provincia',$id_provincia)->get();
		} else {
			$consulta = Ciudad::with('provincia')
				->where('id_provincia',$id_provincia)
				->where(function($query) use ($input) {
			    $query->where('nombre','like','%'.$input.'%')
						->orWhere('cp','like','%'.$input.'%');
			  })
				->get();
		}
		return $response->withStatus(200)->withJson($consulta);
	}

	public function getActividadEconomica($request,$response){
		$input = $request->getParam('consulta');
		$term = $request->getParam('term');
		if(isset($term) and $term!=null){
			$input = $term;
		}
		if(empty($input)){
			$consulta = ActividadEconomica::all();
		} else {
			$consulta = ActividadEconomica::where('nombre','like','%'.$input.'%')
				->orWhere('descripcion','like','%'.$input.'%')
				->orWhere('codigo','like','%'.$input.'%')
				->get();
		}
		return $response->withStatus(200)->withJson($consulta);
	}

	public function getTablaActividadEconomica($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = ActividadEconomica::query();
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where('nombre','like','%'.$value.'%')
						->orWhere('descripcion','like','%'.$value.'%')
						->orWhere('codigo','like','%'.$value.'%');
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