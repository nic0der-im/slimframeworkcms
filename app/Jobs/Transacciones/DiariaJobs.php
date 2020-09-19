<?php
namespace App\Jobs\Transacciones;

use App\Jobs\Jobs;

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

class DiariaJobs extends Jobs {

	public function call_abrir(){
		$logger = $this->logger;
		return $this->scheduler->call(function() use ($logger){
			//////////////////////////      INICIO DEL JOBS
			$sucursales = Sucursal::where('estado',1)->get();
			$logger->info('DiariaJobs call_abrir cantidad '.$sucursales->count());
			foreach ($sucursales as $item) {
				$ultimo = Diaria::where('id_sucursal',$item->id)->orderBy('created_at','desc')->first();
				if($ultimo){
					$fecha_inicio = $ultimo->fecha_cierre;
					if ($fecha_inicio != null) {
						$primer = Movimiento::where('id_sucursal',$item->id)
							->where('updated_at','>',$fecha_inicio)
							->whereNotNull('fecha_cobro')
							->orderBy('updated_at','asc')
							->first();
						if($primer){
							$fecha_inicio = $primer->updated_at;
							Diaria::create([
								'id_sucursal' => $item->id,
								'fecha_inicio' => $fecha_inicio,
								'saldo_anterior' => $ultimo->saldo,
							]);
							$logger->info('DiariaJobs call_abrir abierta para '.$item->sucursal.' '.$item->descripcion);
						}
					}
				} else {
					$primer = Movimiento::where('id_sucursal',$item->id)
						->orderBy('updated_at','asc')
						->first();
					if ($primer) {
						$fecha_inicio = $primer->created_at;
						Diaria::create([
							'id_sucursal' => $item->id,
							'fecha_inicio' => $fecha_inicio,
						]);
						$logger->info('DiariaJobs call_abrir abierta para '.$item->sucursal.' '.$item->descripcion);
					}
				}
			}

			return true;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}

	public function call_cerrar(){
		$logger = $this->logger;
		return $this->scheduler->call(function() use ($logger){
			//////////////////////////      INICIO DEL JOBS
			$diarias = Diaria::whereNull('fecha_cierre')->get();
			$logger->info('DiariaJobs call_cerrar cantidad '.$diarias->count());
			foreach ($diarias as $item) {
				$dt = new \DateTime;
				$egresos  = Movimiento::where('estado',1)
					->where('updated_at','>=',$item->fecha_inicio)
					->where('tipo_ioe',1)
					->where('id_sucursal',$item->id_sucursal)
					->whereNotNull('fecha_cobro')
					->sum('monto');
				$ingresos = Movimiento::where('estado',1)
					->where('updated_at','>=',$item->fecha_inicio)
					->where('tipo_ioe',0)
					->where('id_sucursal',$item->id_sucursal)
					->whereNotNull('fecha_cobro')
					->sum('monto');
				$saldo = ($item->saldo_anterior + $ingresos) - $egresos;
				Diaria::where('id',$item->id)->update([
					'fecha_cierre' => $dt->format('Y-m-d H:i:s'),
					'total_ingreso' => $ingresos,
					'total_egreso' => $egresos,
					'saldo' => $saldo,
				]);
			}

			return true;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}
}


