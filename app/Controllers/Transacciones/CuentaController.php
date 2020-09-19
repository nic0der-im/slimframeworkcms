<?php

namespace App\Controllers\Transacciones;

use App\Models\User;
use App\Models\Individuo;

use App\Models\Negocio\Cliente;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Caja\Cuenta;

use App\Models\Transacciones\Proveedor;
use App\Models\Transacciones\Movimiento;
use App\Models\Transacciones\Comprobante;
use App\Models\Transacciones\ComprobanteDetalle;
use App\Models\Transacciones\ComprobanteError;
use App\Models\Transacciones\FacturaRecibo;
use App\Models\Transacciones\Banco;
use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\SucursalEmpleado;
use App\Models\Transacciones\TipoComprobante;
use App\Models\Transacciones\TipoCondicionIva;
use App\Models\Transacciones\TipoDocumento;
use App\Models\Transacciones\TipoMovimiento;
use App\Models\Transacciones\TipoResponsable;
use App\Models\Transacciones\Obligacion;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class CuentaController extends Controller {
	public function index($request,$response){

		return $this->container->view->render($response, 'admin_views/caja/cuenta/index.twig',[
			'cuentas'=> Cuenta::where('estado', 1)->get(),
			'canceladas'=> Cuenta::where('estado', 0)->count(),
			'abiertas'=> Cuenta::where('estado', 1)->count(),
			'transferidas'=> Cuenta::where('estado', 2)->count(),
		]);
	}

	public function crear($request,$response){
		return $this->container->view->render($response, 'admin_views/caja/cuenta/crear.twig',[
			'tipoComprobante'=>TipoComprobante::where('estado',1)->get(),
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'tipoResponsable'=>TipoResponsable::where('estado',1)->get(),
			'tipoCondicionIva'=> TipoCondicionIva::where('estado',1)->get(),
			'sucursal'=>auth::sucursal(),
		]);
	}

	public function crearpost($request, $response) {
		$id_sucursal = auth::sucursal()->id;

		/*
		$validation = $this->validator->validate($request, [
			'cuenta_nombre'=>v::alpha('nÑ'),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('cuenta.crear'));
		}
		*/
		
		$cliente_tipo_documento = $request->getParam('cliente_tipo_documento');
		$cliente_documento = $request->getParam('cliente_documento');
		$cliente_apellido = $request->getParam('cliente_apellido');
		$cliente_nombre = $request->getParam('cliente_nombre');
		$cliente_domicilio = $request->getParam('cliente_domicilio');
		$cliente_email = $request->getParam('cliente_email');

		$proveedor_tipo_documento = $request->getParam('proveedor_tipo_documento');
		$proveedor_documento = $request->getParam('proveedor_documento');
		$proveedor_razon_social = $request->getParam('proveedor_razon_social');
		$proveedor_domicilio = $request->getParam('proveedor_domicilio');
		$proveedor_email = $request->getParam('proveedor_email');

		$id_cliente = $request->getParam('id_cliente');
		$id_proveedor = $request->getParam('id_proveedor');
		$id_tipo = $request->getParam('id_tipo');

		$cuenta_nombre = $request->getParam('cuenta_nombre');

		try{
			DB::beginTransaction();

			switch ($id_tipo) {
				case 1: //CLIENTE
					if($id_cliente==0){
						$cliente = Cliente::create([
							'nombre' => $cliente_nombre,
							'apellido' => $cliente_apellido,
							'id_tipo_documento'=> $cliente_tipo_documento,
							'documento' => $cliente_documento,
							'domicilio' => $cliente_domicilio,
							'email' => $cliente_email,
							'id_usuario' => auth::empleado()->id_usuario,
							'id_sucursal' => auth::sucursal()->id,
						]);

						$id_cliente = $cliente->id;
					} else if($id_cliente>0) {
						Cliente::where('id',$id_cliente)->update([
							'nombre' => $cliente_nombre,
							'apellido' => $cliente_apellido,
							'id_tipo_documento'=> $cliente_tipo_documento,
							'documento' => $cliente_documento,
							'domicilio' => $cliente_domicilio,
							'email' => $cliente_email,
						]);
					} else {
						$id_cliente = 0;
					}
					$id_provedor_cliente = $id_cliente;
					break;
				case 2: //PROVEEDOR
					if($id_proveedor==0){
						$id_proveedor = Proveedor::create([
							'razon_social' => $proveedor_razon_social,
							'id_tipo_documento'=> $proveedor_tipo_documento,
							'documento' => $proveedor_documento,
							'domicilio' => $proveedor_domicilio,
							'email' => $proveedor_email,
							'id_usuario' => auth::empleado()->id_usuario,
							'id_sucursal' => auth::sucursal()->id,
						])->id;
					} else if($id_proveedor>0) {
						Proveedor::where('id',$id_proveedor)->update([
							'razon_social' => $proveedor_razon_social,
							'id_tipo_documento'=> $proveedor_tipo_documento,
							'documento' => $proveedor_documento,
							'domicilio' => $proveedor_domicilio,
							'email' => $proveedor_email,
						]);
					} else {
						$id_proveedor = 0;
					}
					$id_provedor_cliente = $id_proveedor;
					break;
				default:
					$id_provedor_cliente = 0;
					break;
			}
			Cuenta::create([
				'nombre_cuenta'=> $cuenta_nombre,
				'tipo_cuenta' => $id_tipo,
				'id_provedor_cliente' => $id_provedor_cliente,
				'id_sucursal'=> $id_sucursal,
			]);

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
   	 	DB::rollBack();
    	$this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
			return $response->withRedirect($this->router->pathFor('cuenta.crear'));
		}
		$this->flash->addMessage('info', 'La cuenta corriente se creo exitosamente.');
		return $response->withRedirect($this->router->pathFor('cuenta.index'));
	}

	public function resumen($request,$response, $args){
		$facturas = Comprobante::where('id',$args['id'])->where('estado',1)->get();
		$detalles = ComprobanteDetalle::where('estado',1)->where('id_comprobante',$args['id'])->pluck('id')->toArray();
		$recibos = Comprobante::whereIn('id',function($subQuery) use ($detalles){
			$subQuery->select('id_comprobante')->from('tra_comprobante_detalles')
				->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_recibo')
				->whereIn('tra_factura_recibo.id_factura',$detalles);
		})->get();
		
		return $this->container->view->render($response, 'admin_views/caja/cuenta/cuenta.twig',[
			'sucursal' => auth::sucursal(),
			'bancos' => Banco::all(),
			'facturas'=> $facturas,
			'recibos' => $recibos,
			'recibo' => $recibo,
			'cuentas'=> Cuenta::find($args['id']),
		]);
	}


	public function getEliminar($request, $response, array $args)
	{
		$cuenta = Cuenta::find($args['id']);
		$cuenta->estado = 0;
		$cuenta->save();
		
		$this->flash->addMessage('error', 'Cuenta cerrada satisfactoriamente, un administrador puede darla de alta de vuelta.');
		$url = $this->router->pathFor('cuenta.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}
	
}