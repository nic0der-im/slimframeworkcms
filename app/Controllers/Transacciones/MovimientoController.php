<?php

namespace App\Controllers\Transacciones;

use PHPJasper\PHPJasper;
use App\Models\User;
use App\Models\Individuo;

use App\Models\Negocio\Cliente;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\Proveedor;
use App\Models\Transacciones\Movimiento;
use App\Models\Transacciones\Comprobante;
use App\Models\Transacciones\ComprobanteDetalle;
use App\Models\Transacciones\FacturaRecibo;
use App\Models\Transacciones\Banco;
use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\SucursalTransferencia;
use App\Models\Transacciones\SucursalEmpleado;
use App\Models\Transacciones\TipoComprobante;
use App\Models\Transacciones\TipoCondicionIva;
use App\Models\Transacciones\TipoDocumento;
use App\Models\Transacciones\TipoMovimiento;
use App\Models\Transacciones\TipoResponsable;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class MovimientoController extends Controller {

	/*
	public function nuevo($request,$response){

		return $this->container->view->render($response, 'admin_views/caja/comprobante/movimiento.twig',[
			'sucursal'=>auth::sucursal(),
			'bancos'=>Banco::all(),
		]);
	}
	*/

	/*
	public function agregar($request,$response,$args){
		$tipoMovimiento = intval($request->getParam('id_tipo_movimiento'));

		$cheque_numero = $request->getParam('cheque_numero');
		$cheque_banco = $request->getParam('cheque_banco');
		$id_banco = $request->getParam('id_banco');
		$cheque_cuenta = $request->getParam('cheque_cuenta');
		$cheque_fecha = $request->getParam('cheque_fecha');
		$cheque_cobrado = $request->getParam('cheque_cobrado');
		$cheque_fecha_vto = $request->getParam('cheque_fecha_vto');

		$tarjeta_numero = $request->getParam('tarjeta_numero');
		$tarjeta_titular = $request->getParam('tarjeta_titular');
		$tarjeta_cupon = $request->getParam('tarjeta_cupon');
		$tarjeta_cupon_fecha = $request->getParam('tarjeta_cupon_fecha');

		$tributo_numero = $request->getParam('tributo_numero');
		$tributo_nombre = $request->getParam('tributo_nombre');

		$movimiento_descripcion = $request->getParam('movimiento_descripcion');
		$movimiento_monto = $request->getParam('movimiento_monto');
		$movimiento_tipo = $request->getParam('movimiento_tipo');

		try{
			DB::beginTransaction();
			$dt = new \DateTime();
			$id_movimiento = 0;
			switch ($tipoMovimiento) {
				case 1://Contado
					if($chueque_cobrado) {
						$id_movimiento = Movimiento::create([
							'fecha_cobro' => date('Y-m-d'),
							'id_tipo_movimiento' => $tipoMovimiento,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe' => $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;
					}
					else
					{
						$id_movimiento = Movimiento::create([
							'id_tipo_movimiento' => $tipoMovimiento,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe' => $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;

					}
					break;
				case 2://Cheque
					if($cheque_cobrado){
						$id_movimiento = Movimiento::create([
							'cheque_numero' => $cheque_numero,
							'cheque_banco' => $cheque_banco,
							'id_banco' => $id_banco,
							'cheque_cuenta' => $cheque_cuenta,
							'fecha' => $dt->createFromFormat('d/m/Y',$cheque_fecha),
							'cheque_vto' => $dt->createFromFormat('d/m/Y',$cheque_fecha_vto),
							'fecha_cobro' => date('Y-m-d'),
							'id_tipo_movimiento' => $tipoMovimiento,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe'=> $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;
					} else {
						$id_movimiento = Movimiento::create([
							'cheque_numero' => $cheque_numero,
							'cheque_banco' => $cheque_banco,
							'id_banco' => $id_banco,
							'cheque_cuenta' => $cheque_cuenta,
							'fecha' => $dt->createFromFormat('d/m/Y',$cheque_fecha),
							'cheque_vto' => $dt->createFromFormat('d/m/Y',$cheque_fecha_vto),
							'id_tipo_movimiento' => $tipoMovimiento,
							'tarjeta_numero' => $tarjeta_numero,
							'tarjeta_titular' => $tarjeta_titular,
							'tarjeta_cupon' => $tarjeta_cupon,
							'tributo_numero' => $tributo_numero,
							'tributo_nombre' => $tributo_nombre,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe'=> $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;
					}

					break;
				case 3://Tarjeta
					if($chueque_cobrado) {
						$id_movimiento = Movimiento::create([
							'fecha' => $dt->createFromFormat('d/m/Y',$tarjeta_cupon),
							'fecha_cobro' => date('Y-m-d') ,
							'id_tipo_movimiento' => $tipoMovimiento,
							'tarjeta_numero' => $tarjeta_numero,
							'tarjeta_titular' => $tarjeta_titular,
							'tarjeta_cupon' => $tarjeta_cupon,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe'=> $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;
					}
					else
					{
						$id_movimiento = Movimiento::create([
							'fecha' => $dt->createFromFormat('d/m/Y',$tarjeta_cupon),
							'id_tipo_movimiento' => $tipoMovimiento,
							'tarjeta_numero' => $tarjeta_numero,
							'tarjeta_titular' => $tarjeta_titular,
							'tarjeta_cupon' => $tarjeta_cupon,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe'=> $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;
					}
					break;
				case 4://Tributo
					if($chueque_cobrado) {
						$id_movimiento = Movimiento::create([
							'fecha' => $dt->createFromFormat('d/m/Y',$tributo_fecha),
							'fecha_cobro' => date('Y-m-d'),
							'id_tipo_movimiento' => $tipoMovimiento,
							'tributo_numero' => $tributo_numero,
							'tributo_nombre' => $tributo_nombre,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe'=> $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;
						break;
					}
					else
					{
						$id_movimiento = Movimiento::create([
							'fecha' => $dt->createFromFormat('d/m/Y',$tributo_fecha),
							'id_tipo_movimiento' => $tipoMovimiento,
							'tributo_numero' => $tributo_numero,
							'tributo_nombre' => $tributo_nombre,
							'monto' => $movimiento_monto,
							'id_sucursal' => auth::sucursal()->id,
							'descripcion' => $movimiento_descripcion,
							'id_usuario'=>auth::empleado()->id_usuario,
							'tipo_ioe'=> $movimiento_tipo, //0: INGRESO | 1: EGRESO
						])->id;
						break;
					}
			}

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
	    DB::rollBack();
	    return $response->withRedirect($this->router->pathFor('comprobante.movimiento.nuevo'));
		}

	  $this->flash->addMessage('info', 'Recibo Generado');
		return $response->withRedirect($this->router->pathFor('comprobante.movimiento.tablero'));
	}
	*/

	public function tablero($request,$response){
		$id_sucursal = auth::sucursal()->id;
		$egresos  = Movimiento::where('estado',1)
			->where('tipo_ioe',1)
			->where('id_sucursal',$id_sucursal)
			->whereNotNull('fecha_cobro')
			->sum('monto');
		$ingresos = Movimiento::where('estado',1)
			->where('tipo_ioe',0)
			->where('id_sucursal',$id_sucursal)
			->whereNotNull('fecha_cobro')
			->sum('monto');
		$saldo = $ingresos - $egresos;
		$graficoTipo = TipoMovimiento::select(
			'nombre',
			DB::raw('count(if(tipo_ioe=0,monto,null)) as cantidad_ingresos'),
			DB::raw('sum(if(tipo_ioe=0,monto,0)) as ingresos'),
			DB::raw('count(if(tipo_ioe=1,monto,null)) as cantidad_egresos'),
			DB::raw('sum(if(tipo_ioe=1,monto,0)) as egresos'))
			->join('tra_movimientos','tra_movimientos.id_tipo_movimiento','tra_tipos_movimiento.id')
			->whereNotNull('fecha_cobro')
			->where('tra_movimientos.estado',1)
			->where('tra_movimientos.id_sucursal',$id_sucursal)
			->groupBy('tra_tipos_movimiento.id')
			->get();
		$graficoIngresos = Movimiento::select(
			DB::raw("date_format(fecha_cobro,'%d/%m/%Y') as dia"),
			DB::raw('sum(monto) as total '),
			DB::raw('sum(if(id_tipo_movimiento=1,monto,0)) as total_efectivo'),
			DB::raw('sum(if(id_tipo_movimiento=2,monto,0)) as total_cheque'),
			DB::raw('sum(if(id_tipo_movimiento=3,monto,0)) as total_tarjeta'),
			DB::raw('sum(if(id_tipo_movimiento=4,monto,0)) as total_tributo'),
			DB::raw('sum(if(id_tipo_movimiento=5,monto,0)) as total_documento'),
			DB::raw('sum(if(id_tipo_movimiento=6,monto,0)) as total_transferencia'))
			->leftJoin('tra_tipos_movimiento','tra_tipos_movimiento.id','id_tipo_movimiento')
			->where('tipo_ioe',0)
			->whereNotNull('fecha_cobro')
			->where('tra_movimientos.estado',1)
			->where('tra_movimientos.id_sucursal',$id_sucursal)
			->groupBy('dia')
			->orderBy('fecha_cobro')
			->get();
		$graficoEgresos = Movimiento::select(
			DB::raw("date_format(fecha_cobro,'%d/%m/%Y') as dia"),
			DB::raw('sum(monto) as total '),
			DB::raw('sum(if(id_tipo_movimiento=1,monto,0)) as total_efectivo'),
			DB::raw('sum(if(id_tipo_movimiento=2,monto,0)) as total_cheque'),
			DB::raw('sum(if(id_tipo_movimiento=3,monto,0)) as total_tarjeta'),
			DB::raw('sum(if(id_tipo_movimiento=4,monto,0)) as total_tributo'),
			DB::raw('sum(if(id_tipo_movimiento=5,monto,0)) as total_documento'),
			DB::raw('sum(if(id_tipo_movimiento=6,monto,0)) as total_transferencia'))
			->leftJoin('tra_tipos_movimiento','tra_tipos_movimiento.id','id_tipo_movimiento')
			->where('tipo_ioe',1)
			->whereNotNull('fecha_cobro')
			->where('tra_movimientos.estado',1)
			->where('tra_movimientos.id_sucursal',$id_sucursal)
			->groupBy('dia')
			->orderBy('fecha_cobro')
			->get();
		return $this->container->view->render($response, 'admin_views/caja/comprobante/tableroMovimiento.twig',[
			'egresos' => $egresos,
			'egresosCantidad' => Movimiento::where('estado',1)->where('tipo_ioe',1)->where('id_sucursal',$id_sucursal)->whereNotNull('fecha_cobro')->get()->count(),
			'ingresos' => $ingresos,
			'ingresosCantidad' => Movimiento::where('estado',1)->where('tipo_ioe',0)->where('id_sucursal',$id_sucursal)->whereNotNull('fecha_cobro')->get()->count(),
			'saldo' => $saldo,
			'cantidad' => Movimiento::where('estado',1)->where('id_sucursal',$id_sucursal)->whereNotNull('fecha_cobro')->get()->count(),
			'sucursal'=>auth::sucursal(),
			'graficoTipo' => $graficoTipo,
			'graficoIngresos' => $graficoIngresos,
			'graficoEgresos' => $graficoEgresos,
			'sucursales' => Sucursal::where('estado',1)->where('id','!=',$id_sucursal)->get(),
		]);
	}

	public function getAll($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$tipo_eoi = $request->getParam('tipo_eoi');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$id_sucursal = auth::sucursal()->id;
		$registros = Movimiento::with('comprobantes.factura_detalle.comprobante','comprobantes.recibo_detalle.comprobante','banco','tipo','individuo')
		->where('id_sucursal',$id_sucursal)
		->whereNotNull('fecha_cobro')
		->where('estado',1);
		if($tipo_eoi>=0){
			$registros = $registros->where('tipo_ioe',$tipo_eoi);
		}
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(fecha,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('descripcion','like','%'.$value.'%')
							->orWhere('monto','like','%'.$value.'%')
							->orWhereIn('id_usuario',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_tipo_movimiento',function($d) use ($value){
								$d->select('id')->from('tra_tipos_movimiento')->where('nombre','like','%'.$value.'%');
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

	public function eliminar($request,$response,$args){
		$id_movimiento = $args['id'];
		try{
			DB::beginTransaction();

			Movimiento::where('id',$id_movimiento)->update([
				'estado'=>0,
			]);

			FacturaRecibo::where('id_movimiento',$id_movimiento)->update([
				'estado'=>0,
			]);

			$detalles = FacturaRecibo::where('id_movimiento',$id_movimiento)->pluck('id_recibo')->toArray();
			if($detalles){
				ComprobanteDetalle::whereIn('id',$detalles)->update([
					'estado'=>0,
				]);

				$recibos = ComprobanteDetalle::where('id',$detalles)->pluck('id_comprobante')->toArray();
				if($recibos){
					Comprobante::whereIn('id',$recibos)->update([
						'estado'=>0,
					]);
				}
			}

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    DB::rollBack();
	    return $response->withStatus(409)->withJson([
				'message' => 'Ocurrio un error al borrar los datos. '.$errores,
			]);
		}
		return $response->withStatus(200)->withJson([
			'message' => 'Movimiento '.$id_movimiento.' Eliminado.',
		]);
	}

	public function reporte($request,$response,$args){
		$fecha_desde = $request->getQueryParam('fecha_desde');
		$fecha_hasta = $request->getQueryParam('fecha_hasta');
		$desde = new \DateTime();
		$hasta = new \DateTime();
		$desde->createFromFormat('d/m/Y',$fecha_desde);
		$hasta->createFromFormat('d/m/Y',$fecha_hasta);

		$filename ='Diaria-'.$desde->format('dmY').'-'.$hasta->format('dmY');
		$input = __DIR__ .'/../../../public/reportes/reporte_diaria.jasper';
		$output = __DIR__ .'/../../../public/reportes/'.$filename;
		$ext = 'pdf';
		$options = [
		    'format' => [$ext],
		    'locale' => 'es_AR',
		    'params' => [
		    	'id_usuario'=>auth::empleado()->id_usuario,
		    	'fecha_desde'=>$fecha_desde,
		    	'fecha_hasta'=>$fecha_hasta,
		    	'dir_imagen'=>  __DIR__ .'/../../../public/images/logo.png',
		    ],
		    'db_connection' => [
		        'driver' => 'mysql',
		        'username'=> $this->container['settings']['db']['username'],
						'password'=> $this->container['settings']['db']['password'],
		        'host' => $this->container['settings']['db']['host'],
		        'port' => 3306,
		        'database' => $this->container['settings']['db']['database'],
		    ]
		];
		$jasper = new PHPJasper;

		$jasper->process(
		        $input,
		        $output,
		        $options
		)->execute();

		header('Content-Description: application/pdf');
    header('Content-Type: application/pdf');
    header('Content-Disposition:; filename=' . $filename . '.' . $ext);
    readfile($output . '.' . $ext);
    unlink($output. '.'  . $ext);
    flush();
	}

	public function transferir($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_sucursal_origen = auth::sucursal()->id;
		$id_sucursal_destino = $request->getParam('id_sucursal');
		$observaciones = $request->getParam('observaciones');
		$responsable = $request->getParam('responsable');
		$total = $request->getParam('total');
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
				'observaciones' => $observaciones,
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
			$id_movimiento = Movimiento::create([
				'fecha_cobro' => date('Y-m-d'),
				'id_tipo_movimiento' => 6,
				'monto' => $total,
				'id_sucursal' => $id_sucursal_origen,
				'descripcion' => $sucursal_destino->sucursal.' ('.$sucursal_destino->descripcion.') | '.$responsable.' | '.$observaciones,
				'id_usuario'=> $id_usuario,
				'tipo_ioe' => 1, //0: INGRESO | 1: EGRESO
			])->id;
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
				'observaciones' => $observaciones,
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
				'observaciones' => $observaciones,
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
			$id_movimiento = Movimiento::create([
				'fecha_cobro' => date('Y-m-d'),
				'id_tipo_movimiento' => 6,
				'monto' => $total,
				'id_sucursal' => $id_sucursal_destino,
				'descripcion' =>  $sucursal_origen->sucursal.' ('.$sucursal_origen->descripcion.') | '.$responsable.' | '.$observaciones,
				'id_usuario'=> $id_usuario,
				'tipo_ioe' => 0, //0: INGRESO | 1: EGRESO
			])->id;
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
				'observaciones' => $observaciones,
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

	public function responsables($request,$response){
		$id_usuario = auth::empleado()->id_usuario;
		$input = $request->getParam('consulta');
		if(empty($input)){
			$consulta = SucursalTransferencia::selectRaw('distinct responsable')
				->where('estado',1)
				->where('id_usuario',$id_usuario)
				->orderBy('responsable','desc')
				->get();
		} else {
			$consulta = SucursalTransferencia::selectRaw('distinct responsable')
				->where('estado',1)
				->where('responsable','like','%'.$responsable.'%')
				->orderBy('responsable','desc')
				->get();
		}
		return $response->withStatus(200)->withJson($consulta);
	}

}
