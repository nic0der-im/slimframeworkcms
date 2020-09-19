<?php

namespace App\Controllers\Transacciones;


use App\Models\User;
use App\Models\Individuo;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\Diaria;
use App\Models\Transacciones\Modo;
use App\Models\Transacciones\Obligacion;
use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TiposTransaccion;
use App\Models\Transacciones\SucursalEmpleado;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class ModoController extends Controller {
	public function agregarMetodoPago($request,$response){

		$metodo_pago_nombre = $request->getParam('metodo_pago_nombre');
		$motodo_pago_ioe = $request->getParam('motodo_pago_ioe');

		//GUARDAR DATOS DE LAS CAJAS SUCURSALES
		$metodo_pago_sucursales = $request->getParam('metodo_pago_sucursales');
		$sucursales = '';
		if(in_array(-1,$metodo_pago_sucursales) or in_array('-1',$metodo_pago_sucursales)){
			$sucursales = '-1';
		} else {
			foreach ($metodo_pago_sucursales as $clave => $valor) {
				$sucursales = $sucursales.$valor.',';
			}
			$sucursales = rtrim($sucursales,",");
		}

		Modo::create([
			'tipo' => $motodo_pago_ioe,
			'sucursales' => $sucursales,
			'modo' => $metodo_pago_nombre,
		]);

		$this->flash->addMessage('info', "Se logro agregar el nuevo Metodo de Pago: ".$metodo_pago_nombre.".");
		$url = $this->router->pathFor('transacciones.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function obtenerMetodoPago($request,$response){
		$id = $request->getParam('id');
		$modo = Modo::where('id',$id)->first();
		return $response->withStatus(200)->withJson($modo);
	}

	public function editarMetodoPago($request,$response){

		$motodo_pago_id = $request->getParam('motodo_pago_id');
		$metodo_pago_nombre = $request->getParam('metodo_pago_nombre');
		$motodo_pago_ioe = $request->getParam('motodo_pago_ioe');

		//GUARDAR DATOS DE LAS CAJAS SUCURSALES
		$metodo_pago_sucursales = $request->getParam('metodo_pago_sucursales');
		$sucursales = '';
		if(in_array(-1,$metodo_pago_sucursales) or in_array('-1',$metodo_pago_sucursales)){
			$sucursales = '-1';
		} else {
			foreach ($metodo_pago_sucursales as $clave => $valor) {
				$sucursales = $sucursales.$valor.',';
			}
			$sucursales = rtrim($sucursales,",");
		}

		Modo::where('id',$motodo_pago_id)->update([
			'tipo' => $motodo_pago_ioe,
			'sucursales' => $sucursales,
			'modo' => $metodo_pago_nombre,
		]);

		$this->flash->addMessage('info', "Se logro modificar el Metodo de Pago: ".$metodo_pago_nombre.".");
		$url = $this->router->pathFor('transacciones.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}
}