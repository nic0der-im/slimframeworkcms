<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\User;
use App\Models\Empleado;
use App\Models\Individuo;
use App\Controllers\Controller;
use App\Models\SP_Extras\Agencia;
use App\Models\SP_Extras\Puesto;
use App\Models\SP_Extras\Ubicacion;
use Respect\Validation\Validator as v;
use App\Models\Modulo;
use App\Models\ModuloEnlaces;
use App\Models\Permisos;

use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\SucursalEmpleado;

use App\Auth\auth;

class EmpleadosController extends Controller {

	public function index($request, $response, array $args)
	{
		$empleados = Empleado::where('id_puesto', "<>", '11')->orderBy('id_agencia','DESC')->get();
		return $this->container->view->render($response, 'admin_views/empleados/index.twig', [
			'empleados'=>$empleados
		]);
	}

	public function getCargar($request, $response)
	{
		$agencias = Agencia::get();
		$puestos = Puesto::get();
		return $this->container->view->render($response, 'admin_views/empleados/cargar.twig', [
			'agencias'=>$agencias,
			'puestos'=>$puestos
		]);
	}

	public function getEditar($request, $response, array $args)
	{
		$empleado = Empleado::where('id', $args['id'])->first();
		return $this->container->view->render($response, 'admin_views/empleados/editar.twig', [
			'empleado'=>$empleado,
			'agencias'=>Agencia::get(),
			'puestos'=>Puesto::get(),
			'ubicaciones'=>Ubicacion::get(),
			'modulos'=>Modulo::orderBy('id','ASC')->get(),
			'permisos'=>Permisos::where('id_usuario', $empleado->id_usuario)->get(),
			'sucursales'=>Sucursal::get(),
			'sucursalesEmpleado'=> SucursalEmpleado::where('id_usuario',$empleado->id_usuario)->where('estado',1)->pluck('id_sucursal')->toArray(),
		]);

	}

	public function postEditar($request,$response, $args)
	{
		// guardar datos del empleado (basicos):
		$empleado = Empleado::find($request->getParam('empleado_editar_id'));
		$empleado->id_agencia = $request->getParam('empleado-editar-sucursal');
		$empleado->id_puesto = $request->getParam('empleado-editar-puesto');
		$empleado->id_ubicacion = $request->getParam('empleado-editar-ubicacion');
		$empleado->save();

		$individuo = Individuo::where('id_usuario',$request->getParam('empleado_editar_id_usuario'))->first();
		$individuo->telefono =  $request->getParam('empleado_editar_telefono');
		$individuo->nombre =  $request->getParam('empleado_editar_nombre');
		$individuo->apellido =  $request->getParam('empleado_editar_apellido');
		$individuo->save();

		$user = User::find($request->getParam('empleado_editar_id_usuario'));
		$user->email = $request->getParam('empleado_editar_email');
		$user->save();

		// obtener permisos y guardar o eliminar:
		$enlaces = ModuloEnlaces::orderBy('id','ASC')->get();
		//$array = array();
		foreach ($enlaces as $e)
		{
			$checkbox = $request->getParam('empleado_checkbox'.$e->id);
			$count = Permisos::where('id_usuario',$request->getParam('empleado_editar_id_usuario'))
				->where('id_enlace',$e->id)
				->count();

			if($count == 1)
			{
				// si encuentra el permiso en la DB y el permiso fue dado de baja en su respectivo checkbox:
				if(!isset($checkbox))
				{
					$permiso_a_eliminar =  Permisos::where('id_usuario',$request->getParam('empleado_editar_id_usuario'))
						->where('id_enlace',$e->id)
						->first();
					Permisos::destroy($permiso_a_eliminar->id);
				}
			}
			else
			{
				// si el permiso no esta en la DB pero se encuentra checkeado, por consiguiente se crea.
				if(isset($checkbox))
				{
					Permisos::Create([
						'id_enlace'=>$e->id,
						'id_usuario'=>$request->getParam('empleado_editar_id_usuario'),
					]);
				}
			}
			// array debug:
			//$array[] = array("id" => $e->id, "value" => $checkbox, "count" => $count);
		}

		//GUARDAR DATOS DE LAS CAJAS SUCURSALES
		$empleado_editar_sucursales = $request->getParam('empleado_editar_sucursales');
		$sucursalesEmpleado = SucursalEmpleado::where('id_usuario',$request->getParam('empleado_editar_id_usuario'))->where('estado',1)->pluck('id_sucursal')->toArray();
		foreach ($empleado_editar_sucursales as $clave => $valor) {
	    if(!in_array($valor,$sucursalesEmpleado)){
	    	SucursalEmpleado::Create([
	    		'id_sucursal' => $valor,
	    		'id_usuario' =>$request->getParam('empleado_editar_id_usuario'),
	    		'usuario_alta' => auth::individuo()->nombre.' '.auth::individuo()->apellido,
	    	]);
	    }
		}

		foreach ($sucursalesEmpleado as $valor) {
	    if(!in_array($valor,$empleado_editar_sucursales)){
	    	$sucursales = SucursalEmpleado::where('id_usuario',$request->getParam('empleado_editar_id_usuario'))
	    		->where('id_sucursal',$valor)
	    		->where('estado',1)
	    		->update(['estado'=>0]);
	    }
		}

		$url = $this->router->pathFor('empleados.index');
		// Flash
		$this->flash->addMessage('info', "El empleado '". $empleado->individuo->nombre." ". $empleado->individuo->apellido . "' fue editado exitosamente.");
		return $response->withStatus(302)->withHeader('Location', $url);

	}

	public function getEliminar($request, $response, array $args)
	{
		$empleado = Empleado::destroy($args['id']);

		$this->flash->addMessage('error', 'Empleado eliminado exitosamente.');

		$url = $this->router->pathFor('empleados.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function postCrear($request,$response)
	{
		$sucursal = $request->getParam('empleado-crear-sucursal');
		$puesto = $request->getParam('empleado-crear-puesto');
		$acceso = $request->getParam('empleado-crear-nivel_acceso');

		$validation = $this->validator->validate($request, [
			'car_nombre'=>v::notEmpty()->alpha(),
			'car_apellido'=>v::notEmpty()->alpha(),
			'car_email'=>v::email()->EmailAvailable(),
			'car_phone'=>v::notEmpty()->noWhitespace(),
			'car_password'=>v::notEmpty()->noWhitespace(),
		]);

		if($validation->failed())
		{
			$this->flash->addMessage('error', "No completó todos los campos correctamente, revise de vuelta los datos.");
			$url = $this->router->pathFor('empleados.cargar');
			return $response->withStatus(302)->withHeader('Location', $url);
		}

		$user = User::Create([
			'email'=>$request->getParam('car_email'),
			'password'=>password_hash($request->getParam('car_password'), PASSWORD_DEFAULT)
		]);


		$individuo = Individuo::Create([
			'id_usuario'=>$user->id,
			'nombre'=>$request->getParam('car_nombre'),
			'apellido'=>$request->getParam('car_apellido'),
			'telefono'=>$request->getParam('car_phone'),
		]);

		$empleado = Empleado::Create([
			'id_usuario'=>$user->id,
			'id_agencia'=>$sucursal,
			'id_puesto'=>$puesto,
			'acceso_sistema'=>$acceso,
		]);

		$this->flash->addMessage('info', "Empleado ID " . $user->id . " creado exitosamente.");

		$url = $this->router->pathFor('empleados.index');
		return $response->withStatus(302)->withHeader('Location', $url);

	}
}
