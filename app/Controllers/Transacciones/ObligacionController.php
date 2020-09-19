<?php

namespace App\Controllers\Transacciones;

use \Datetime;

use App\Models\User;
use App\Models\Individuo;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\Comprobante;
use App\Models\Transacciones\ComprobanteDetalle;
use App\Models\Transacciones\FacturaRecibo;
use App\Models\Transacciones\Obligacion;
use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TiposTransaccion;
use App\Models\Transacciones\SucursalEmpleado;
use App\Models\Transacciones\SucursalTransferencia;
use App\Models\Transacciones\Banco;
use App\Models\Transacciones\Movimiento;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class ObligacionController extends Controller {

	public function index($request,$response){
		$sucursalEmpleado = SucursalEmpleado::where(['id_usuario'=>auth::individuo()->id_usuario,'estado'=>1])->get(['id_sucursal']);

		$bancos = Banco::all();

		$id_sucursal = auth::sucursal()->id;
		$chequeProximas = Obligacion::where([
			'estado'=>1,
			'id_sucursal'=>$id_sucursal])
		->whereDate('fecha_aproxfin','>=',date('Y-m-d'))
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',2);
		})
		->orderBy('fecha_aproxfin','desc')
		->get();
		$chequeVencidas = Obligacion::where([
			'estado'=>1,
			'id_sucursal'=>$id_sucursal])
		->whereDate('fecha_aproxfin','<',date('Y-m-d'))
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',2);
		})
		->orderBy('fecha_aproxfin','desc')
		->get();
		$chequeCumplidas = Obligacion::where([
			'estado'=>2,
			'id_sucursal'=>$id_sucursal])
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',2)
			->where('estado',1);
		})
		->orderBy('fecha_aproxfin','desc')
		->get();
		$chequeAnuladas = Obligacion::where([
			'estado'=>0,
			'id_sucursal'=>$id_sucursal])
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',2);
		})
		->orderBy('fecha_aproxfin','desc')
		->get();

		$documentoProximas = Obligacion::where([
			'estado'=>1,
			'id_sucursal'=>$id_sucursal])
		->whereDate('fecha_aproxfin','>=',date('Y-m-d'))
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',5);
		})
		->orderBy('fecha_aproxfin','desc')
		->get();
		$documentoVencidas = Obligacion::where([
			'estado'=>1,
			'id_sucursal'=>$id_sucursal])
		->whereDate('fecha_aproxfin','<',date('Y-m-d'))
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',5);
		})
		->orderBy('fecha_aproxfin','desc')
		->get();
		$documentoCumplidas = Obligacion::where([
			'estado'=>2,
			'id_sucursal'=>$id_sucursal])
		->orderBy('fecha_aproxfin','desc')
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',5)
			->where('estado',1);
		})
		->get();
		$documentoAnuladas = Obligacion::where([
			'estado'=>0,
			'id_sucursal'=>$id_sucursal])
		->orderBy('fecha_aproxfin','desc')
		->whereHas('movimiento', function($q){
	    $q->where('id_tipo_movimiento',5);
		})
		->get();

		return $this->container->view->render($response, 'admin_views/caja/obligacion/index.twig',[
			'sucursales' => Sucursal::where('estado',1)->where('id','!=',$id_sucursal)->get(),
			'sucursal'=> auth::sucursal(),
			'chequeProximas'=>$chequeProximas,
			'chequeVencidas' => $chequeVencidas,
			'chequeCumplidas' => $chequeCumplidas,
			'chequeAnuladas' => $chequeAnuladas,
			'documentoProximas' => $documentoProximas,
			'documentoVencidas' => $documentoVencidas,
			'documentoCumplidas' => $documentoCumplidas,
			'documentoAnuladas' => $documentoAnuladas,
		]);
	}

	public function estadoCheque($request, $response){

		$id = $request->getParam('id');
		$estado = $request->getParam('estado');

		$obligacion = Obligacion::where('id',$id)->first();

		Obligacion::where('id',$id)->update([
			'estado'=> $estado,
			'id_usuario' => auth::empleado()->id_usuario,
			'fecha_fin' => date('Y-m-d'),
		]);

		if($estado==0){
			$this->flash->addMessage('info', "Se marco como anulado el cheque. El movimiento no es eliminado.");
			Movimiento::where('id',$obligacion->id_movimiento)->update([
				'fecha_cobro' => null,
				'estado' => 0,
			]);
		} elseif($estado==2){
			$this->flash->addMessage('info', "Se marco como cobrado el cheque.");
			Movimiento::where('id',$obligacion->id_movimiento)->update([
				'fecha_cobro' => date('Y-m-d'),
				'estado' => 1,
			]);
		} elseif ($estado==3){
			$this->flash->addMessage('info', "Se marco como pagado el cheque.");
			Movimiento::where('id',$obligacion->id_movimiento)->update([
				'fecha_cobro' => date('Y-m-d'),
				'estado' => 1,
			]);
		}
		$url = $this->router->pathFor('obligacion.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function estadoDocumento($request, $response){

		$id = $request->getParam('id');
		$estado = $request->getParam('estado');

		$obligacion = Obligacion::where('id',$id)->first();

		Obligacion::where('id',$id)->update([
			'estado'=> $estado,
			'id_usuario' => auth::empleado()->id_usuario,
			'fecha_fin' => date('Y-m-d'),
		]);

		if($estado==0){
			$this->flash->addMessage('info', "Se marco como anulado el documento.");
			Movimiento::where('id',$obligacion->id_movimiento)->update([
				'fecha_cobro' => null,
			]);
		} elseif($estado==2){
			$this->flash->addMessage('info', "Se marco como cobrado el documento.");
			Movimiento::where('id',$obligacion->id_movimiento)->update([
				'fecha_cobro' => date('Y-m-d'),
			]);
		} elseif ($estado==3){
			$this->flash->addMessage('info', "Se marco como pagado el documento.");
			Movimiento::where('id',$obligacion->id_movimiento)->update([
				'fecha_cobro' => date('Y-m-d'),
			]);
		}
		$url = $this->router->pathFor('obligacion.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function transferir($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_sucursal_destino = $request->getParam('id_sucursal');
		$observaciones = $request->getParam('observaciones');
		$responsable = $request->getParam('responsable');
		$id_obligacion = $args['id'];
		$obligacion = Obligacion::where('id',$id_obligacion)->first();
		$id_sucursal_origen = $obligacion->id_sucursal;
		$id_movimiento = $obligacion->id_movimiento;
		$movimiento = Movimiento::where('id',$id_movimiento)->first();

		$total = $movimiento->monto;
		$sucursal_origen = Sucursal::where('id',$id_sucursal_origen)->first();
		$sucursal_destino = Sucursal::where('id',$id_sucursal_destino)->first();
		try {
			DB::beginTransaction();
			$factura = Comprobante::create([
				'id_cliente' => 0,
				'id_proveedor' => 0,
				'id_tipo_transaccion' => 0,
				'id_tipo_comprobante' => 99,//RECIBO X
				'id_tipo_responsable' => 5,
				'id_tipo_condicion_iva' => 1,
				'id_tipo_documento' => 99,
				'documento_numero' => 11111111,
				'razon_social' => $sucursal_destino->sucursal.' '.$sucursal_destino->descripcion,
				'domicilio' => '',
				'email' => '',
				'fecha' => date('Y-m-d'),
				'observaciones' => 'Transferencia '.$observaciones,
				'total' => $total,
				'bonificacion' => 0,
				'impuesto' => 0,
				'id_usuario' => $id_usuario,
				'id_sucursal' => $id_sucursal_origen,
				'tipo' => 2, //1 Venta 2 Compra
				'cuentacorriente' => 0,
			]);
			$id_comprobante_origen = $factura->id;
			$factura_detalle = ComprobanteDetalle::create([
				'id_comprobante' => $factura->id,
				'codigo' => '',
				'descripcion' => $factura->id.' - '.$factura->tipoComprobante->nombre.' Total: $'.$factura->total,
				'importe' => $total,
				'cantidad' => 1,
				'bonificado' => 0,
				'subtotal' => $total,
				'id_usuario' => $id_usuario,
				'orden' => 1,
			]);
			$nuevo = $movimiento->replicate();
			$nuevo->id_sucursal = $id_sucursal_origen;
			$nuevo->id_usuario = $id_usuario;
			$nuevo->tipo_ioe = 1; //0: INGRESO | 1: EGRESO
			$nuevo->save();
			$id_movimiento = $nuevo->id;

			$recibo = Comprobante::create([
				'id_cliente' => 0,
				'id_proveedor' => 0,
				'id_tipo_comprobante' => 99,//RECIBO X
				'id_tipo_responsable' => $factura->id_tipo_responsable,
				'id_tipo_condicion_iva' => $factura->id_tipo_condicion_iva,
				'id_tipo_documento' => $factura->id_tipo_documento,
				'documento_numero' => $factura->documento_numero,
				'razon_social' => $factura->razon_social,
				'domicilio' => $factura->domicilio,
				'email' => $factura->email,
				'fecha' => date('Y-m-d'),
				'observaciones' => 'Transferencia '.$observaciones,
				'total' => $total,
				'bonificacion' => 0,
				'impuesto' => 0,
				'id_usuario' => $id_usuario,
				'id_sucursal' => $id_sucursal_origen,
				'tipo' => $factura->tipo, //1 Venta 2 Compra
				'cuentacorriente' => $factura->cuentacorriente,
			]);
			$recibo_detalle = ComprobanteDetalle::create([
				'id_comprobante' => $recibo->id,
				'codigo' => '',
				'descripcion' => $recibo->id.' - '.$recibo->tipoComprobante->nombre.' Total: $'.$recibo->total,
				'importe' => $total,
				'cantidad' => 1,
				'bonificado' => 0,
				'subtotal' => $total,
				'id_usuario' => $id_usuario,
				'orden' => 1,
			]);
			FacturaRecibo::create([
				'id_factura'=> $factura_detalle->id,
				'id_recibo'=> $recibo_detalle->id,
				'id_movimiento'=> $id_movimiento,
				'id_usuario' => $id_usuario,
				'cantidad' => 1,
			]);

			$factura = Comprobante::create([
				'id_cliente' => 0,
				'id_proveedor' => 0,
				'id_tipo_transaccion' => 0,
				'id_tipo_comprobante' => 99,//RECIBO X
				'id_tipo_responsable' => 5,
				'id_tipo_condicion_iva' => 1,
				'id_tipo_documento' => 99,
				'documento_numero' => 11111111,
				'razon_social' => $sucursal_origen->sucursal.' '.$sucursal_origen->descripcion,
				'domicilio' => '',
				'email' => '',
				'fecha' => date('Y-m-d'),
				'observaciones' => 'Transferencia '.$observaciones,
				'total' => $total,
				'bonificacion' => 0,
				'impuesto' => 0,
				'id_usuario' => $id_usuario,
				'id_sucursal' => $id_sucursal_destino,
				'tipo' => 1, //1 Venta 2 Compra
				'cuentacorriente' => 0,
			]);
			$id_comprobante_destino= $factura->id;
			$factura_detalle = ComprobanteDetalle::create([
				'id_comprobante' => $factura->id,
				'codigo' => '',
				'descripcion' => $factura->id.' - '.$factura->tipoComprobante->nombre.' Total: $'.$factura->total,
				'importe' => $total,
				'cantidad' => 1,
				'bonificado' => 0,
				'subtotal' => $total,
				'id_usuario' => $id_usuario,
				'orden' => 1,
			]);
			$nuevo = $movimiento->replicate();
			$nuevo->id_sucursal = $id_sucursal_destino;
			$nuevo->id_usuario = $id_usuario;
			$nuevo->tipo_ioe = 0; //0: INGRESO | 1: EGRESO
			$nuevo->save();
			$id_movimiento = $nuevo->id;

			$recibo = Comprobante::create([
				'id_cliente' => 0,
				'id_proveedor' => 0,
				'id_tipo_comprobante' => 99,//RECIBO X
				'id_tipo_responsable' => $factura->id_tipo_responsable,
				'id_tipo_condicion_iva' => $factura->id_tipo_condicion_iva,
				'id_tipo_documento' => $factura->id_tipo_documento,
				'documento_numero' => $factura->documento_numero,
				'razon_social' => $factura->razon_social,
				'domicilio' => $factura->domicilio,
				'email' => $factura->email,
				'fecha' => date('Y-m-d'),
				'observaciones' => 'Transferencia '.$observaciones,
				'total' => $total,
				'bonificacion' => 0,
				'impuesto' => 0,
				'id_usuario' => $id_usuario,
				'id_sucursal' => $id_sucursal_destino,
				'tipo' => $factura->tipo, //1 Venta 2 Compra
				'cuentacorriente' => $factura->cuentacorriente,
			]);
			$recibo_detalle = ComprobanteDetalle::create([
				'id_comprobante' => $recibo->id,
				'codigo' => '',
				'descripcion' => $recibo->id.' - '.$recibo->tipoComprobante->nombre.' Total: $'.$recibo->total,
				'importe' => $total,
				'cantidad' => 1,
				'bonificado' => 0,
				'subtotal' => $total,
				'id_usuario' => $id_usuario,
				'orden' => 1,
			]);
			FacturaRecibo::create([
				'id_factura'=> $factura_detalle->id,
				'id_recibo'=> $recibo_detalle->id,
				'id_movimiento'=> $id_movimiento,
				'id_usuario' => $id_usuario,
				'cantidad' => 1,
			]);

			SucursalTransferencia::create([
				'id_sucursal_origen'=>$id_sucursal_origen,
				'id_sucursal_destino'=>$id_sucursal_destino,
				'id_comprobante_origen'=>$id_comprobante_origen,
				'id_comprobante_destino'=>$id_comprobante_destino,
				'total'=>$total,
				'responsable' => $responsable,
				'observaciones' => $observaciones,
				'id_usuario' => $id_usuario,
			]);

			$nuevo = $obligacion->replicate();
			$nuevo->id_usuario = $id_usuario;
			$nuevo->id_sucursal = $id_sucursal_destino;
			$nuevo->save();
			$obligacion->estado = 0;
			$obligacion->save();
			DB::commit();
		} catch (\Exception $e) {
			$errores = $e->getMessage();
	    DB::rollBack();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
			return $response->withRedirect($this->router->pathFor('comprobante.movimiento.tablero'));
		}
		$this->flash->addMessage('info', 'La Transferencia fue realizada Exitosamente.');
		return $response->withRedirect($this->router->pathFor('comprobante.movimiento.tablero'));
	}

}
