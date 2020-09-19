<?php
namespace App\Jobs\Transacciones;

use App\Jobs\Jobs;

use App\Funcionalidades\Correo;

use App\Models\Caja\Cuenta;

use App\Models\Correo\ModuloCorreo;

use App\Models\Transacciones\Diaria;
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

use Illuminate\Database\Capsule\Manager as DB;
use App\Auth\auth;

class ReporteJobs extends Jobs {

	public function call_semanal(){
		$logger = $this->logger;
		return $this->scheduler->call(function() use ($logger){
			//////////////////////////      INICIO DEL JOBS

	    $correos = ModuloCorreo::where('estado',1)->where('url_name','like','cajas.reporte%')->get();
	    foreach ($correos as $correo) {
	    	$id_sucursal = 0;
	    	if (stristr($correo->url_name, 'salta')) {
	    		$id_sucursal = 1;
				} else if (stristr($correo->url_name, 'oran')) {
	    		$id_sucursal = 2;
				}
				$sucursal = Sucursal::where('id',$id_sucursal)->first();
				if($sucursal){
					$logger->info('call_semanal preparando para '.$correo->email.' con sucursal '.$sucursal->sucursal);
					try{
						$cuentas = Cuenta::whereIn('id_sucursal',[$id_sucursal,0])->get();
						$tbody='';
						if(count($cuentas)>0){
							foreach ($cuentas as $cuenta) {
								$tbody = $tbody."
								<tr>
									<th style='text-align: left;'>".$cuenta->nombre_cuenta."</th>
									<th>".($cuenta->id_vehiculo>0?$cuenta->vehiculo->modelo:'')."</th>
									<th>".$cuenta->total."</th>
									<th>".$cuenta->pagado."</th>
									<th>".($cuenta->total - $cuenta->pagado)."</th>
								</tr>
								";
							}
							$tablaCuenta = "
								<p><h3>Cuentas Corrientes Abiertas:</h3></p>
								<table>
									<thead>
										<tr>
											<th>Nombre</th>
											<th>Vehiculo</th>
											<th>Total</th>
											<th>Pagado</th>
											<th>Saldo</th>
										</tr>
									</thead>
									<tbody>
									".$tbody."
									</tbody>
								</table>
							";
						} else {
							$tablaCuenta = "
								<p><h3>Cuentas Corrientes Abiertas:</h3></p>
								<p>No hay cuentas abiertas.</p>
							";
						}

						$dt = new \DateTime();
						date_modify($dt,'-1 Week');
						$diaria = Diaria::where('created_at','>=',$dt)->orderBy('created_at','asc')->first();
						if($diaria){
							$movimientosIngresos = Movimiento::select(
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
								->where('tra_movimientos.fecha_cobro','>=',$diaria->fecha_inicio)
								->first();
							$movimientosEgresos = Movimiento::select(
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
								->where('tra_movimientos.fecha_cobro','>=',$diaria->fecha_inicio)
								->first();
							$tbody = '';
							if($movimientosIngresos){
								$tbody = "
								<tr>
									<td>Ingresos</td>
									<td>".$movimientosIngresos->total_efectivo."</td>
									<td>".$movimientosIngresos->total_cheque."</td>
									<td>".$movimientosIngresos->total_tarjeta."</td>
									<td>".$movimientosIngresos->total_tributo."</td>
									<td>".$movimientosIngresos->total_documento."</td>
									<td>".$movimientosIngresos->total_transferencia."</td>
									<td>".$movimientosIngresos->total."</td>
								</tr>
							";
							} else {
								$tbody = "
								<tr>
									<td>Ingresos</td>
									<td>0</td>
									<td>0</td>
									<td>0</td>
									<td>0</td>
									<td>0</td>
									<td>0</td>
									<td>0</td>
								</tr>
								";
							} 
							if($movimientosEgresos){
								$tbody = $tbody."
									<tr>
										<td>Egresos</td>
										<td>".$movimientosEgresos->total_efectivo."</td>
										<td>".$movimientosEgresos->total_cheque."</td>
										<td>".$movimientosEgresos->total_tarjeta."</td>
										<td>".$movimientosEgresos->total_tributo."</td>
										<td>".$movimientosEgresos->total_documento."</td>
										<td>".$movimientosEgresos->total_transferencia."</td>
										<td>".$movimientosEgresos->total."</td>
									</tr>
								";
							} else {
								$tbody = $tbody."
									<tr>
										<td>Egresos</td>
										<td>0</td>
										<td>0</td>
										<td>0</td>
										<td>0</td>
										<td>0</td>
										<td>0</td>
										<td>0</td>
									</tr>
								";
							}
							
							$tablaMovimiento = "
								<p><h3>Movimientos generados de la Semana</h3></p>
								<table>
									<thead>
										<tr>
											<th>Tipo</th>
											<th>Cheque</th>
											<th>Efectivo</th>
											<th>Tarjeta</th>
											<th>Cheque</th>
											<th>Documento</th>
											<th>Transferencia</th>
											<th>Total</th>
										</tr>
									</thead>
									<tbody>
									".$tbody."
									</tbody>
								</table>
							";
						} else {
							$tablaMovimiento = "
								<p><h3>Movimientos generados de la Semana</h3></p>
								<p>No hay movimientos.</p>
							";
						}
					
						$recibo_detalle = FacturaRecibo::where('estado',1)->pluck('id_recibo')->toArray();
						$recibos = ComprobanteDetalle::where('estado',1)->whereIn('id',$recibo_detalle)->pluck('id_comprobante')->toArray();
						$pagosVenta = Comprobante::where('estado',1)->whereIn('id',$recibos)->where('tipo',1)->where('id_sucursal',$id_sucursal)->distinct('id');
						$ventas = Comprobante::where('estado',1)->whereNotIn('id',$pagosVenta->pluck('id')->toArray())->where('tipo',1)->where('id_sucursal',$id_sucursal)->distinct('id');

						$graficoTipoTransaccionVenta = Comprobante::select(
								'tra_comprobantes.id_tipo_transaccion',
								DB::raw("IFNULL(tra_tipos_transaccion.nombre_tipo,'otros') as nombre"),
								DB::raw('sum(tra_comprobantes.total) as total '))
							->leftJoin('tra_tipos_transaccion','tra_comprobantes.id_tipo_transaccion','tra_tipos_transaccion.id')
							->where('tra_comprobantes.estado',1)
							->whereNotIn('tra_comprobantes.id',$pagosVenta->pluck('id')->toArray())
							->where('tra_comprobantes.tipo',1)
							->where('tra_comprobantes.id_sucursal',$id_sucursal)
							->where('created_at','>=',$dt)
							->distinct('tra_comprobantes.id')
							->groupBy('tra_comprobantes.id_tipo_transaccion')
							->get();
						$tbody = '';
						foreach ($graficoTipoTransaccionVenta as $value) {
							$tbody = $tbody."
								<tr>
									<td style='text-align: left;'>".$value->nombre."</td>
									<td>".$value->total."</td>
								</tr>
							";
						}
						$tablaComprobanteIngresos = "
						<table>
							<caption><h5>Ingresos</h5></caption>
							<thead>
								<tr>
									<th>Actividad</th>
									<th>Monto</th>
								</tr>
							</thead>
							<tbody>".$tbody."</tbody>
						</table>
						";

						$proveedoresPago = Comprobante::where('estado',1)->where('tipo',2)->where('id_sucursal',$id_sucursal)->groupBy('id_proveedor')->get()->count();
						$recibo_detalle = FacturaRecibo::where('estado',1)->pluck('id_recibo')->toArray();
						$recibos = ComprobanteDetalle::where('estado',1)->whereIn('id',$recibo_detalle)->pluck('id_comprobante')->toArray();
						$pagosCompra = Comprobante::where('estado',1)->whereIn('id',$recibos)->where('tipo',2)->where('id_sucursal',$id_sucursal)->distinct('id');
						$compras = Comprobante::where('estado',1)->whereNotIn('id',$pagosCompra->pluck('id')->toArray())->where('tipo',2)->where('id_sucursal',$id_sucursal)->distinct('id');

						$graficoTipoTransaccionCompra = Comprobante::select(
								'tra_comprobantes.id_tipo_transaccion',
								DB::raw("IFNULL(tra_tipos_transaccion.nombre_tipo,'otros') as nombre"),
								DB::raw('sum(tra_comprobantes.total) as total '))
							->leftJoin('tra_tipos_transaccion','tra_comprobantes.id_tipo_transaccion','tra_tipos_transaccion.id')
							->where('tra_comprobantes.estado',1)
							->whereNotIn('tra_comprobantes.id',$pagosCompra->pluck('id')->toArray())
							->where('tra_comprobantes.tipo',2)
							->where('tra_comprobantes.id_sucursal',$id_sucursal)
							->where('created_at','>=',$dt)
							->distinct('tra_comprobantes.id')
							->groupBy('tra_comprobantes.id_tipo_transaccion')
							->get();

						$tbody = '';
						foreach ($graficoTipoTransaccionCompra as $value) {
							$tbody = $tbody."
								<tr>
									<td style='text-align: left;'>".$value->nombre."</td>
									<td>".$value->total."</td>
								</tr>
							";
						}
						$tablaComprobanteEgresos = "
							<table>
								<caption><h5>Egresos</h5></caption>
								<thead>
									<tr>
										<th>Actividad</th>
										<th>Monto</th>
									</tr>
								</thead>
								<tbody>".$tbody."</tbody>
							</table>
							";
						$body="
							<h2>Resumen Semanal</h2>
							".$tablaCuenta."
							".$tablaMovimiento."
							<p><h3>Comprobantes registrados de la Semana</h3></p>
							".$tablaComprobanteIngresos."
							".$tablaComprobanteEgresos."
						";
						$mail = Correo::ciro();                           
				    $mail->setFrom('ciro.noreply@gmail.com', 'no-replay');
			    	$mail->addAddress($correo->email);
				    $mail->isHTML(true);
				    $mail->Subject = $sucursal->sucursal.' ('.$sucursal->descripcion.') Semanal';
				    $mail->Body    = $body;
				    $mail->send();
						$logger->info('call_semanal enviado a '.$correo->email);
					} catch (\PHPMailer\PHPMailer\Exception $e) {
						$logger->info('call_semanal error a '.$mail->ErrorInfo);
					} catch (\Exception $e){
						$logger->info('call_semanal error a '.$e->getMessage());
					}
				}
	    }
			
			return true;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}

}


