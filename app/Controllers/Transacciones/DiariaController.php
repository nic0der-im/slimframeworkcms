<?php

namespace App\Controllers\Transacciones;

use \Datetime;

use App\Models\User;
use App\Models\Individuo;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\Comprobante;
use App\Models\Transacciones\Diaria;
use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TiposTransaccion;
use App\Models\Transacciones\SucursalEmpleado;
use App\Models\Transacciones\Movimiento;
use App\Models\Transacciones\FacturaRecibo;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class DiariaController extends Controller {

	public function index($request,$response){
		$id_sucursal = auth::sucursal()->id;
		$diarias = Diaria::where([
			'id_sucursal'=>$id_sucursal,
		])->orderBy('id','desc')->get();

		return $this->container->view->render($response, 'admin_views/caja/transaccion/diaria.twig',[
			'sucursal'=>auth::sucursal(),
			'diarias'=>$diarias,
		]);
	}

	public function getDiaria($request, $response, $args)
	{
		$id_sucursal = auth::sucursal()->id;
		$diaria = Diaria::where('id',$args['id'])->first();

    	$transaccionIngresos = Movimiento::where([
			'id_sucursal' => $id_sucursal,
			'tipo_ioe' => 0,
		])->where('created_at','>=',$diaria->fecha_inicio);
    
	    if($diaria->fecha_cierre != null)
	    {
	    	$transaccionIngresos = $transaccionIngresos->where('created_at','<=',$diaria->fecha_cierre);
	    }
		$transaccionIngresos = $transaccionIngresos->orderBy('created_at','desc')->get();

		$transaccionEgresos = Movimiento::where([
			'id_sucursal' => $id_sucursal,
			'tipo_ioe' => 1,
		])->where('created_at','>=',$diaria->fecha_inicio);
	    if($diaria->fecha_cierre != null)
	    {
	    	$transaccionEgresos = $transaccionEgresos->where('created_at','<=',$diaria->fecha_cierre);
	    }
		$transaccionEgresos = $transaccionEgresos->orderBy('created_at','desc')->get();

		return $this->container->view->render($response, 'admin_views/caja/transaccion/index.twig',[
			'transaccionIngresos'=>$transaccionIngresos,
			'transaccionEgresos'=>$transaccionEgresos,
			'sucursal'=>auth::sucursal(),
			'diaria'=>$diaria,
		]);
	}

	public function getReporteExcel($request,$response){
		$sucursal = auth::sucursal();

		//$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('reportes/lista_vehiculos.xls');
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		//$spreadsheet->setActiveSheetIndex(0);
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
		$worksheet = $spreadsheet->getActiveSheet();

		$worksheet->setTitle('Diarias');

		$periodo = $request->getQueryParam('periodo','7/2018');
		$diarias = Diaria::where(DB::raw("DATE_FORMAT(created_at,'%m/%Y')"),'like',$periodo)
			->where('id_sucursal',$sucursal->id)
			->orderBy('created_at','asc')
			->get();

    $row = 1;

    $styleTitulo = [
	    'font' => [
        'bold' => true,
	    ],
	    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
	    ],
	    'borders' => [
        'top' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
        'bottom' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
        'right' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
        'left' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
	    ],
	    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startColor' => [
          'argb' => 'FFA0A0A0',
        ],
        'endColor' => [
          'argb' => 'FFFFFFFF',
        ],
	    ],
		];

		$styleLateral = [
			'borders' => [
        'left' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
	    ],
		];

		$styleBarra = [
			'borders' => [
        'bottom' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
          'color' => [
	        	'rgb' => 'FF0000',
	        ],
        ],
      ],
		];

		$styleImporte = [
			'borders' => [
        'left' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
      	],
        'right' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
      	],
    	],
    	'fill' => [
      	'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => [
        	'rgb' => '88F0B6',
        ],
	    ],
	    'numberFormat' =>[
	    	'formatCode' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
	    ],
		];

		$styleTotales = [
			'font' => [
        'bold' => true,
	    ],
	    'borders' => [
        'top' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
        'bottom' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
        'right' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
        'left' => [
          'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        ],
	    ],
    	'fill' => [
      	'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => [
        	'rgb' => 'FDFF76',
        ],
	    ],
	    'numberFormat' =>[
	    	'formatCode' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
	    ],
		];

		$worksheet->getStyle('A1:I1')->applyFromArray($styleTitulo);
		$worksheet->getColumnDimension('A')->setWidth(10);
		$worksheet->getCellByColumnAndRow(1, $row)->setValue('Fecha');
		$worksheet->getColumnDimension('B')->setWidth(20);
		$worksheet->getCellByColumnAndRow(2, $row)->setValue('Actividad');
		$worksheet->getColumnDimension('C')->setWidth(11);
		$worksheet->getCellByColumnAndRow(3, $row)->setValue('Tipo Ingreso');
		$worksheet->getColumnDimension('D')->setWidth(10);
		$worksheet->getCellByColumnAndRow(4, $row)->setValue('Fecha');
		$worksheet->getColumnDimension('E')->setWidth(4);
		$worksheet->getCellByColumnAndRow(5, $row)->setValue('Pto.');
		$worksheet->getColumnDimension('F')->setWidth(4);
		$worksheet->getCellByColumnAndRow(6, $row)->setValue('Nro.');
		$worksheet->getColumnDimension('G')->setWidth(20);
		$worksheet->getCellByColumnAndRow(7, $row)->setValue('Apellido y Nombre');
		$worksheet->getColumnDimension('H')->setWidth(30);
		$worksheet->getCellByColumnAndRow(8, $row)->setValue('Descripcion');
		$worksheet->getCellByColumnAndRow(9, $row)->setValue('Importe');

		$worksheet->getStyle('M1:U1')->applyFromArray($styleTitulo);
		$worksheet->getColumnDimension('M')->setWidth(10);
		$worksheet->getCellByColumnAndRow(13, $row)->setValue('Fecha');
		$worksheet->getColumnDimension('N')->setWidth(20);
		$worksheet->getCellByColumnAndRow(14, $row)->setValue('Actividad');
		$worksheet->getColumnDimension('O')->setWidth(11);
		$worksheet->getCellByColumnAndRow(15, $row)->setValue('Tipo Ingreso');
		$worksheet->getColumnDimension('P')->setWidth(10);
		$worksheet->getCellByColumnAndRow(16, $row)->setValue('Fecha');
		$worksheet->getColumnDimension('Q')->setWidth(4);
		$worksheet->getCellByColumnAndRow(17, $row)->setValue('Pto.');
		$worksheet->getColumnDimension('R')->setWidth(4);
		$worksheet->getCellByColumnAndRow(18, $row)->setValue('Nro.');
		$worksheet->getColumnDimension('S')->setWidth(20);
		$worksheet->getCellByColumnAndRow(19, $row)->setValue('Apellido y Nombre');
		$worksheet->getColumnDimension('T')->setWidth(30);
		$worksheet->getCellByColumnAndRow(20, $row)->setValue('Descripcion');
		$worksheet->getCellByColumnAndRow(21, $row)->setValue('Importe');

	  $row++;
		$rowIngreso=$row;
		$rowEgreso=$row;
		$rowIngresoDiaria=0;
		$rowEgresoDiaria=0;
		$primeraFila = true;
		$saldoAnteriorIngreso = 0;
		$saldoAnteriorEgreso = 0;
		$factura_recibo = FacturaRecibo::where('estado',1)->pluck('id_movimiento')->toArray();
    foreach ($diarias as $diaria) {
    	if($diaria->fecha_cierre == null){
		  	$movimientos = Movimiento::where('estado',1)
					->where('updated_at','>=',$diaria->fecha_inicio)
					->where('id_sucursal',$diaria->id_sucursal)
					->where('estado',1)
					->whereNotNull('fecha_cobro')
					->whereIn('id',$factura_recibo)
					->get();
	  	} else {
	  		$movimientos = Movimiento::where('estado',1)
					->where('updated_at','>=',$diaria->fecha_inicio)
					->where('updated_at','<=',$diaria->fecha_cierre)
					->where('id_sucursal',$diaria->id_sucursal)
					->where('estado',1)
					->whereNotNull('fecha_cobro')
					->whereIn('id',$factura_recibo)
					->get();
	  	}
	  	foreach ($movimientos as $movimiento) {
	  		$comprobante = Comprobante::join('tra_comprobante_detalles','tra_comprobantes.id','tra_comprobante_detalles.id_comprobante')
	  		->join('tra_factura_recibo','tra_comprobante_detalles.id','tra_factura_recibo.id_factura')
	  		->where('tra_factura_recibo.id_movimiento',$movimiento->id)->first();
	  		if($movimiento->tipo_ioe==0){ //INGRESOS
	    		$worksheet->getCellByColumnAndRow(1, $rowIngreso)->setValue($diaria->created_at->format('d/m/Y'));
	    		if($comprobante->id_tipo_transaccion>0){
	    			$worksheet->getCellByColumnAndRow(2, $rowIngreso)->setValue($comprobante->tipoTransaccion->nombre_tipo);
	    		} else {
	    			$worksheet->getCellByColumnAndRow(2, $rowIngreso)->setValue('VARIOS');
	    		}
	    		$worksheet->getCellByColumnAndRow(3, $rowIngreso)->setValue($movimiento->tipo->nombre);
	    		$worksheet->getCellByColumnAndRow(4, $rowIngreso)->setValue($movimiento->updated_at->format('d/m H:m'));
	    		$worksheet->getCellByColumnAndRow(5, $rowIngreso)->setValue($movimiento->sucursal->punto_venta);
	    		$worksheet->getCellByColumnAndRow(6, $rowIngreso)->setValue($movimiento->id);
    			$worksheet->getCellByColumnAndRow(7, $rowIngreso)->setValue($comprobante->razon_social);
					$detalles = count($movimiento->comprobantes()->first()->factura_detalle()->get());
					if( $detalles >1 ){
						$worksheet->getCellByColumnAndRow(8, $rowIngreso)->setValue($movimiento->descripcion);
					} else {
						$worksheet->getCellByColumnAndRow(8, $rowIngreso)->setValue($movimiento->comprobantes()->first()->factura_detalle()->first()->descripcion);
					}
    			$worksheet->getCellByColumnAndRow(9, $rowIngreso)->setValue($movimiento->monto);

	  			$rowIngresoDiaria++;
	  			$rowIngreso++;
	  		} else {//EGRESOS
					$worksheet->getCellByColumnAndRow(13, $rowEgreso)->setValue($diaria->created_at->format('d/m/Y'));
	    		if($comprobante->id_tipo_transaccion>0){
	    			$worksheet->getCellByColumnAndRow(14, $rowEgreso)->setValue($comprobante->tipoTransaccion->nombre_tipo);
	    		} else {
	    			$worksheet->getCellByColumnAndRow(14, $rowEgreso)->setValue('VARIOS');
	    		}
	    		$worksheet->getCellByColumnAndRow(15, $rowEgreso)->setValue($movimiento->tipo->nombre);
	    		$worksheet->getCellByColumnAndRow(16, $rowEgreso)->setValue($movimiento->updated_at->format('d/m H:m'));
	    		$worksheet->getCellByColumnAndRow(17, $rowEgreso)->setValue($movimiento->sucursal->punto_venta);
	    		$worksheet->getCellByColumnAndRow(18, $rowEgreso)->setValue($movimiento->id);
    			$worksheet->getCellByColumnAndRow(19, $rowEgreso)->setValue($comprobante->razon_social);
					$detalles = count($movimiento->comprobantes()->first()->factura_detalle()->get());
					if( $detalles >1 ){
						$worksheet->getCellByColumnAndRow(20, $rowIngreso)->setValue($movimiento->descripcion);
					} else {
    				$worksheet->getCellByColumnAndRow(20, $rowEgreso)->setValue($movimiento->comprobantes()->first()->factura_detalle()->first()->descripcion);
					}
    			$worksheet->getCellByColumnAndRow(21, $rowEgreso)->setValue($movimiento->monto);

	  			$rowEgresoDiaria++;
	  			$rowEgreso++;
	  		}
	  	}
	  	if($rowIngresoDiaria<5 and $rowEgresoDiaria<5){
	  		$diferenciaRowIngreso = 0;
	  		if($rowIngresoDiaria<5){
	  			$diferenciaRowIngreso = 5 - $rowIngresoDiaria;
	  			for ($i=$rowIngreso; $i < $rowIngreso + $diferenciaRowIngreso ; $i++) {
	    			$worksheet->getCellByColumnAndRow(1, $i)->setValue($diaria->created_at->format('d/m/Y'));
	  			}
	  		}
	  		$diferenciaRowEgreso = 0;
	  		if($rowEgresoDiaria<5){
	  			$diferenciaRowEgreso = 5 - $rowEgresoDiaria;
	  			for ($i=$rowEgreso; $i < $rowEgreso + $diferenciaRowEgreso ; $i++) {
	    			$worksheet->getCellByColumnAndRow(13, $i)->setValue($diaria->created_at->format('d/m/Y'));
	  			}
	  		}
	  		if($diferenciaRowIngreso>$diferenciaRowEgreso){
	  			$rowIngreso = $rowIngreso + $diferenciaRowIngreso;
	  		} else {
	  			$rowEgreso = $rowEgreso + $diferenciaRowEgreso;
	  		}
	  	}
	  	if($rowIngreso<$rowEgreso){
	  		for ($i=$rowIngreso; $i < $rowEgreso ; $i++) {
    			$worksheet->getCellByColumnAndRow(1, $i)->setValue($diaria->created_at->format('d/m/Y'));
  			}
	  	} else if($rowEgreso<$rowIngreso){
	  		for ($i=$rowEgreso; $i < $rowIngreso ; $i++) {
    			$worksheet->getCellByColumnAndRow(13, $i)->setValue($diaria->created_at->format('d/m/Y'));
  			}
	  	}

			if($primeraFila){
				$primeraFila = false;
				$saldoAnteriorIngreso = $diaria->saldo_anterior;
			}
	    $row = $rowIngreso<$rowEgreso?$rowEgreso:$rowIngreso;

			$worksheet->getStyle('J'.($row - 3).':J'.($row - 1))->applyFromArray($styleTitulo);
			$worksheet->getStyle('K'.($row - 3).':K'.($row - 1))->applyFromArray($styleTotales);
	    $worksheet->getCellByColumnAndRow(10, $row - 3)->setValue('Total');
	    $worksheet->getCellByColumnAndRow(10, $row - 2)->setValue('Saldo');
	    $worksheet->getCellByColumnAndRow(10, $row - 1)->setValue('Suma');
	    $worksheet->getCellByColumnAndRow(11, $row - 3)->setValue($diaria->total_ingreso);
	    $worksheet->getCellByColumnAndRow(11, $row - 2)->setValue($saldoAnteriorIngreso);
	    $suma = $diaria->total_ingreso + $saldoAnteriorIngreso;
	    $worksheet->getCellByColumnAndRow(11, $row - 1)->setValue($suma);

	    $saldoAnteriorEgreso = abs($suma)-$diaria->total_egreso;
			$worksheet->getStyle('V'.($row - 3).':V'.($row - 1))->applyFromArray($styleTitulo);
			$worksheet->getStyle('W'.($row - 3).':W'.($row - 1))->applyFromArray($styleTotales);
	    $worksheet->getCellByColumnAndRow(22, $row - 3)->setValue('Total');
	    $worksheet->getCellByColumnAndRow(22, $row - 2)->setValue('Saldo');
	    $worksheet->getCellByColumnAndRow(22, $row - 1)->setValue('Suma');
	    $worksheet->getCellByColumnAndRow(23, $row - 3)->setValue($diaria->total_egreso);
	    $worksheet->getCellByColumnAndRow(23, $row - 2)->setValue($saldoAnteriorEgreso);
	    $worksheet->getCellByColumnAndRow(23, $row - 1)->setValue($diaria->total_egreso + $saldoAnteriorEgreso);


			$worksheet->getStyle('A'.($row - 1).':K'.($row - 1))->applyFromArray($styleBarra);
			$worksheet->getStyle('M'.($row - 1).':W'.($row - 1))->applyFromArray($styleBarra);
	    $saldoAnteriorIngreso = abs($saldoAnteriorEgreso);
	    $rowIngreso=$row;
			$rowEgreso=$row;
			$rowIngresoDiaria=0;
			$rowEgresoDiaria=0;
		}
		$worksheet->getStyle('I2:I'.($row - 1))->applyFromArray($styleImporte);
		$worksheet->getStyle('U2:U'.($row - 1))->applyFromArray($styleImporte);

		$worksheet->getStyle('A2:A'.($row - 1))->applyFromArray($styleLateral);
		$worksheet->getStyle('M2:M'.($row - 1))->applyFromArray($styleLateral);

		$output = str_replace(" ", "_", $sucursal->sucursal).'-'.str_replace(" ", "_", $sucursal->descripcion).'-'.str_replace("/", "_", $periodo);
		header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=Diarias-'.$output.'.xls');
    header('Cache-Control: max-age=0');

    $writer->save("php://output");
	}
}
