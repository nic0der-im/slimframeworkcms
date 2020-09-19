<?php

namespace App\Controllers\Transacciones;


use App\Models\User;
use App\Models\Individuo;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\Diaria;
use App\Models\Transacciones\Obligacion;
use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TiposTransaccion;
use App\Models\Transacciones\Transaccion;
use App\Models\Transacciones\SucursalEmpleado;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class TipoTransaccionController extends Controller {
	public function obtenerCategoria($request,$response){
		$tipo = $request->getParam('tipo');
		$modo = TiposTransaccion::where(['padre'=> 0,'estado_tipo'=>1])->
		where(function($query) use ($tipo){
			$query->where('tipo',$tipo)
				->orWhere('tipo',-1);
		})->get();
		return $response->withStatus(200)->withJson($modo);
	}

	public function obtenerTipoTransaccion($request,$response){
		$id = $request->getParam('id');
		$tipoTransaccion = TiposTransaccion::where('id',$id)->first();
		return $response->withStatus(200)->withJson($tipoTransaccion);
	}

	public function agregarTipoTransaccion($request,$response){
		$tipo_transaccion_nombre = $request->getParam('tipo_transaccion_nombre');
		$tipo_transaccion_ioe = $request->getParam('tipo_transaccion_ioe');
		$tipo_transaccion_categoria = $request->getParam('tipo_transaccion_categoria');

		//GUARDAR DATOS DE LAS CAJAS SUCURSALES
		$tipo_transaccion_sucursales = $request->getParam('tipo_transaccion_sucursales');
		$sucursales = '';
		if(in_array(-1,$tipo_transaccion_sucursales) or in_array('-1',$tipo_transaccion_sucursales)){
			$sucursales = '-1';
		} else {
			foreach ($tipo_transaccion_sucursales as $clave => $valor) {
				$sucursales = $sucursales.$valor.',';
			}
			$sucursales = rtrim($sucursales,",");
		}

		TiposTransaccion::create([
			'tipo' => $tipo_transaccion_ioe,
			'padre' => $tipo_transaccion_categoria,
			'nombre_tipo' => $tipo_transaccion_nombre,
			'sucursales' => $sucursales,
		]);

		$this->flash->addMessage('info', "Se logro agregar el Tipo de Transaccion: ".$tipo_transaccion_nombre.".");
		$url = $this->router->pathFor('transacciones.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function editarTipoTransaccion($request,$response){
		$tipo_transaccion_id = $request->getParam('tipo_transaccion_id');
		$tipo_transaccion_nombre = $request->getParam('tipo_transaccion_nombre');
		$tipo_transaccion_ioe = $request->getParam('tipo_transaccion_ioe');
		$tipo_transaccion_categoria = $request->getParam('tipo_transaccion_categoria');

		//GUARDAR DATOS DE LAS CAJAS SUCURSALES
		$tipo_transaccion_sucursales = $request->getParam('tipo_transaccion_sucursales');
		$sucursales = '';
		if(in_array(-1,$tipo_transaccion_sucursales) or in_array('-1',$tipo_transaccion_sucursales)){
			$sucursales = '-1';
		} else {
			foreach ($tipo_transaccion_sucursales as $clave => $valor) {
				$sucursales = $sucursales.$valor.',';
			}
			$sucursales = rtrim($sucursales,",");
		}

		TiposTransaccion::where('id',$tipo_transaccion_id)->update([
			'tipo' => $tipo_transaccion_ioe,
			'padre' => $tipo_transaccion_categoria,
			'nombre_tipo' => $tipo_transaccion_nombre,
			'sucursales' => $sucursales,
		]);

		$this->flash->addMessage('info', "Se logro modificar el Tipo de Transaccion: ".$tipo_transaccion_nombre.".");
		$url = $this->router->pathFor('transacciones.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function eliminarTipoTransaccion($request,$response,$args){
		$tipo_transaccion_id = $args['id'];
		$tipo_transaccion = TiposTransaccion::where('id',$tipo_transaccion_id)->first();
		TiposTransaccion::where('id',$tipo_transaccion_id)->update([
			'estado_tipo' => 0,
		]);

		$this->flash->addMessage('info', "Se logro eliminar el Tipo de Transaccion: ".$tipo_transaccion->nombre_tipo.".");
		$url = $this->router->pathFor('transacciones.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}
}