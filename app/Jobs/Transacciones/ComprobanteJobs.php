<?php
namespace App\Jobs\Transacciones;
require (__DIR__.'/../../../afip/Afip.php');

use App\Jobs\Jobs;
use App\Funcionalidades\WSS;

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

class ComprobanteJobs extends Jobs {

	public function call_facturar(){
		$logger = $this->logger;
		return $this->scheduler->call(function() use ($logger){
			//////////////////////////      INICIO DEL JOBS
			$produccion = true;
			$afip = new \Afip(WSS::cert($produccion));
			$hasta = new \DateTime();
			$hasta->modify('+5 day');
			$desde = new \DateTime();
			$desde->modify('-5 day');
			$comprobantes = Comprobante::whereNull('cae')
				->where('tipo',1)
				->whereBetween('fecha',[$desde->format('Y-m-d'),$hasta->format('Y-m-d')])
				->where('estado',1)
				->where('reproceso',0)
				->whereIn('id_tipo_comprobante',[
					1, //FACTURA A
					6,  //FACTURA B
					11, //FACTURA C
					3, //NOTA DE CREDITO A
					8, // NOTA DE CREDITO B
					13, // NOTA DE CREDITO C
				])
				->orderBy('fecha','asc')
				->get();
			$logger->info('ComprobanteJobs cantidad '.$comprobantes->count());
			$dt = new \DateTime();
			foreach ($comprobantes as $comprobante) {
				if($comprobante->id_tipo_responsable==5 and $comprobante->id_tipo_comprobante != 1){ //CONSUMIDOR FINAL
					$DocTipo = 99;
					$DocNro = 0;
				} else {
					$DocTipo = $comprobante->id_tipo_documento;
					$DocNro = $comprobante->documento_numero;
				}
				if($comprobante->id_tipo_comprobante == 11 ){
					$impTotConc = 0;
				} else {
					$impTotConc = $comprobante->nogravado;
				}
				$fecha = $dt->createFromFormat('Y-m-d', $comprobante->fecha );
				$impNeto = $comprobante->total - $comprobante->impuesto;
				$iva = [];
				if($impNeto>0 ){
					$detalles = ComprobanteDetalle::where('id_comprobante',$comprobante->id)->where('estado',1)->get();
					foreach ($detalles as $detalle) {
						array_push($iva,[
							'Id' => $detalle->id_tipo_condicion_iva,
							'BaseImp' => $detalle->subtotal - $detalle->impuesto,
							'Importe' => $detalle->impuesto ,
						]);
					}
				}
				try {
					ComprobanteError::where('id_comprobante',$comprobante->id_tipo_comprobante)->update([
						'estado' => 0,
					]);
					$sucursal = Sucursal::where('id',$comprobante->id_sucursal)->first();
					$ptovta = $sucursal->punto_venta;
					$numeroComprobante = $afip->ElectronicBilling->GetLastVoucher($ptovta,$comprobante->id_tipo_comprobante) + 1;
					$data = [
						'CantReg' 	=> 1,  // Cantidad de comprobantes a registrar
						'PtoVta' 	=> $ptovta,  // Punto de venta
						'CbteTipo' 	=> $comprobante->id_tipo_comprobante,  // Tipo de comprobante (ver tipos disponibles)
						'Concepto' 	=> 1,  // Concepto del Comprobante: (1)Productos, (2)Servicios, (3)Productos y Servicios
						'DocTipo' 	=> $DocTipo, // Tipo de documento del comprador (99 consumidor final, ver tipos disponibles)
						'DocNro' 	=> $DocNro,  // Número de documento del comprador (0 consumidor final)
						'CbteDesde' 	=> $numeroComprobante,  // Número de comprobante o numero del primer comprobante en caso de ser mas de uno
						'CbteHasta' 	=> $numeroComprobante,  // Número de comprobante o numero del último comprobante en caso de ser mas de uno
						'CbteFch' 	=> intval($fecha->format('Ymd')), // (Opcional) Fecha del comprobante (yyyymmdd) o fecha actual si es nulo
						'ImpTotal' 	=> $comprobante->total, // Importe total del comprobante
						'ImpTotConc' 	=> $impTotConc,   // Importe neto no gravado. (0 factura C)
						'ImpNeto' 	=> $comprobante->total - $comprobante->impuesto - $impTotConc, // Importe neto gravado
						'ImpOpEx' 	=> $comprobante->exento,   // Importe exento de IVA
						'ImpIVA' 	=> $comprobante->impuesto,  //Importe total de IVA
						'ImpTrib' 	=> 0,   //Importe total de tributos
						'MonId' 	=> 'PES', //Tipo de moneda usada en el comprobante (ver tipos disponibles)('PES' para pesos argentinos)
						'MonCotiz' 	=> 1,     // Cotización de la moneda usada (1 para pesos argentinos)
						];
					if($comprobante->id_tipo_comprobante != 11){
						$data['Iva'] = $iva;
					}
					$logger->info('ComprobanteJobs Consultando ',$data);
					$res = $afip->ElectronicBilling->CreateVoucher($data);
					$logger->info('ComprobanteJobs '.$comprobante->id.' cae '.$res['CAE'].' cae_vto '.$res['CAEFchVto']);
					Comprobante::where('id',$comprobante->id)->update([
						'cae' => $res['CAE'], //CAE asignado el comprobante
						'cae_vto' => $res['CAEFchVto'], //Fecha de vencimiento del CAE (yyyy-mm-dd)
						'ptovta'=> $ptovta,
						'cbtedesde' => $numeroComprobante,
						'cbtehasta' => $numeroComprobante,
						'produccion' => $produccion,
					]);
				} catch (\Exception $e) {
					Comprobante::where('id',$comprobante->id)->update([
						'reproceso' => 1,
						'produccion' => $produccion,
					]);
					ComprobanteError::create([
						'id_comprobante' => $comprobante->id,
						'descripcion' => $e->getMessage(),
					]);
					$logger->error('ComprobanteJobs '.$comprobante->id.'. '.$e->getMessage());
				}

			}
			return true;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}
}
