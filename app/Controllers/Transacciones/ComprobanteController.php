<?php

namespace App\Controllers\Transacciones;
require (getcwd().'/../afip/Afip.php');

use \Datetime;
use App\Funcionalidades\WSS;

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
use App\Models\Transacciones\SucursalTransferencia;
use App\Models\Transacciones\SucursalEmpleado;
use App\Models\Transacciones\TipoComprobante;
use App\Models\Transacciones\TipoCondicionIva;
use App\Models\Transacciones\TipoDocumento;
use App\Models\Transacciones\TipoMovimiento;
use App\Models\Transacciones\TipoResponsable;
use App\Models\Transacciones\Obligacion;
use App\Models\Transacciones\TiposTransaccion;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

use PHPJasper\PHPJasper;

class ComprobanteController extends Controller {

	public function index($request,$response){

		return $this->container->view->render($response, 'admin_views/caja/comprobante/index.twig',[
		]);
	}

	public function informeindex($request,$response){
		$pdo = $this->db->connection()->getPdo();
		$stock = $pdo->prepare('
			select
			mes,
			nuevos,
			nuevos_salta,
			nuevos_oran,
			vendidos,
			vendidos_salta,
			vendidos_oran,
			(vendidos/(( @sum + @sum + nuevos - vendidos)/2)) as indice_rotacion,
			COALESCE((( ( @sum + @sum + nuevos - vendidos)/2)/(vendidos/30)),0) as permanencia_stock,
			((@sum + @sum +nuevos - vendidos)/2) as promedio_stock,
			(@sum := @sum + nuevos - vendidos) as stock,
			(vendidos/30) as medio_diario
			from
			(
			select
			mes,
			sum(COALESCE(nuevos,0)) as nuevos,
			sum(COALESCE(nuevos_salta,0)) as nuevos_salta,
			sum(COALESCE(nuevos_oran,0)) as nuevos_oran,
			sum(COALESCE(vendidos,0)) as vendidos,
			sum(COALESCE(vendidos_salta,0)) as vendidos_salta,
			sum(COALESCE(vendidos_oran,0)) as vendidos_oran
			from (
			select
			DATE_FORMAT(created_at,"%m/%Y") as mes,
			count(id) as nuevos ,
			sum(IF(id_localidad=1,1,0)) nuevos_salta,
			sum(IF(id_localidad=2,1,0)) nuevos_oran,
			0 as vendidos,
			0 as vendidos_salta,
			0 as vendidos_oran
			from vehiculos
			where
			eliminado = 0
			and estado_vehiculo = 2
			group by mes
			union all
			select
			DATE_FORMAT(updated_at,"%m/%Y") as mes,
			0 as nuevos,
			0 as nuevos_salta,
			0 as nuevos_oran,
			count(id) as vendidos ,
			sum(IF(id_localidad=1,1,0)) vendidos_salta,
			sum(IF(id_localidad=2,1,0)) vendidos_oran
			from vehiculos
			where
			id_estado = 3
			and eliminado = 0
			and estado_vehiculo = 2
			group by mes)
			final
			group by mes
			order by str_to_date(mes,"%m/%Y") asc
			) promedios
			cross join (select @sum := 0) params;
			');
		$stock->execute();
		$stock = $stock->fetchAll(\PDO::FETCH_ASSOC);

		return $this->container->view->render($response, 'admin_views/caja/informes/informes_index.twig',[	
			'sucursales' => Sucursal::all(),
			'stock'=>$stock,
		]);
	}

	public function informecuenta($request,$response){
		return $this->container->view->render($response, 'admin_views/caja/informes/mayores.twig',[	
			'sucursales' => Sucursal::all(),
		]);
	}

	public function informecuentaconsulta($request,$response, array $args){
		$mes = "MONTH(tra_comprobantes.created_at) = " . $args["mes"];
		if($args["sucursal"] == 99)
		{
			$cuentas = DB::table('tra_comprobantes')
			->selectRaw('MONTH(tra_comprobantes.created_at) as mes, COUNT(tra_comprobantes.id) as cantidad, nombre_tipo, SUM(case when tra_comprobantes.tipo=1 then tra_comprobantes.total else 0 end) as ingreso ,SUM(case when tra_comprobantes.tipo=2 then tra_comprobantes.total else 0 end) as egreso, tra_comprobantes.id_tipo_transaccion as id_tra, tra_tipos_transaccion.codigo as codigo_tra, tra_comprobantes.periodo as periodo')
			->leftJoin('tra_tipos_transaccion', 'tra_comprobantes.id_tipo_transaccion', '=', 'tra_tipos_transaccion.id')
			->join('tra_comprobante_detalles', 'tra_comprobantes.id', '=', 'tra_comprobante_detalles.id_comprobante')
			->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_factura')
			->whereRaw($mes)
			->where('tra_comprobantes.estado', '=', 1)
			->where('tra_factura_recibo.estado', '=', 1)
			->where('tra_comprobante_detalles.estado', '=', 1)
			->groupBy(DB::raw('tra_tipos_transaccion.id'))
			->orderBy('tra_tipos_transaccion.id', 'ASC')
			->get();
		} 
		else 
		{ 
			$cuentas = DB::table('tra_comprobantes')
			->selectRaw('MONTH(tra_comprobantes.created_at) as mes, COUNT(tra_comprobantes.id) as cantidad, nombre_tipo, SUM(case when tra_comprobantes.tipo=1 then tra_comprobantes.total else 0 end) as ingreso ,SUM(case when tra_comprobantes.tipo=2 then tra_comprobantes.total else 0 end) as egreso, tra_comprobantes.id_tipo_transaccion as id_tra, tra_tipos_transaccion.codigo as codigo_tra, tra_comprobantes.periodo as periodo')
			->leftJoin('tra_tipos_transaccion', 'tra_comprobantes.id_tipo_transaccion', '=', 'tra_tipos_transaccion.id')
			->join('tra_comprobante_detalles', 'tra_comprobantes.id', '=', 'tra_comprobante_detalles.id_comprobante')
			->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_factura')
			->where('tra_comprobantes.id_sucursal', '=', $args["sucursal"])	
			->whereRaw($mes)
			->where('tra_comprobantes.estado', '=', 1)
			->where('tra_factura_recibo.estado', '=', 1)
			->where('tra_comprobante_detalles.estado', '=', 1)
			->groupBy(DB::raw('tra_tipos_transaccion.id'))
			->orderBy('tra_tipos_transaccion.id', 'ASC')
			->get();
		}
		return $response->withStatus(200)->withJson([
			'data' => $cuentas,
		]);
	}

	public function informecuentatransacciones($request,$response, array $args){
		$mes = "MONTH(tra_comprobantes.created_at) = " . $args["mes"];
		if($args["sucursal"] == 99)
		{
			$transacciones = DB::table('tra_comprobantes')
			->select('tra_comprobantes.created_at as fecha', 'razon_social', 'observaciones', 'tipo', 'tra_comprobantes.total', 'tra_comprobante_detalles.descripcion as detalle', 'tra_comprobantes.periodo as periodo')
			->join('tra_comprobante_detalles', 'tra_comprobantes.id', '=', 'tra_comprobante_detalles.id_comprobante')
			->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_factura')
			->groupBy('tra_comprobantes.id')
			->where('id_tipo_transaccion', '=', $args['cuenta'])
			->where('tra_comprobantes.estado', '=', 1)
			->where('tra_factura_recibo.estado', '=', 1)
			->where('tra_comprobante_detalles.estado', '=', 1)
			->whereRaw($mes)
			->get();
		}
		else
		{
			$transacciones = DB::table('tra_comprobantes')
			->select('tra_comprobantes.created_at as fecha', 'razon_social', 'observaciones', 'tipo', 'tra_comprobantes.total', 'tra_comprobante_detalles.descripcion as detalle', 'tra_comprobantes.periodo as periodo')
			->join('tra_comprobante_detalles', 'tra_comprobantes.id', '=', 'tra_comprobante_detalles.id_comprobante')
			->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_factura')
			->groupBy('tra_comprobantes.id')
			->where('id_tipo_transaccion', '=', $args['cuenta'])
			->where('tra_comprobantes.estado', '=', 1)
			->where('tra_factura_recibo.estado', '=', 1)
			->where('tra_comprobante_detalles.estado', '=', 1)
			
			->whereRaw($mes)
			->where('id_sucursal', '=', $args['sucursal'])
			->get();
		}

		return $response->withStatus(200)->withJson([
			'data' => $transacciones,
		]);
	}

	public function informefinalCONSULTA($request,$response, array $args)
	{
		$semana = "WEEKOFYEAR(created_at) = ". $args["semana"];  
		$tabla = $args["tabla"];

		if($tabla == 1)
		{
			$tipos = DB::table('tra_comprobantes')
			->selectRaw('id_tipo_transaccion, DAYOFWEEK(created_at) as dia_semana, SUM(total) as total, COUNT(*) as cantidad')
			->where('id_sucursal', '=', 1)
			->where('tipo', '=', 1)
			->whereRaw($semana)
			->groupBy(DB::raw('DAYOFWEEK(created_at) ,tra_comprobantes.id_tipo_transaccion'))
			->get();

			$suma = array( 
				1 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				2 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				3 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				4 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				5 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				6 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				7 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				8 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				9 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0)
			);
			$temp = 1;

			foreach ($tipos as $tipo) 
			{
				for($dia_semana = 1; $dia_semana < 8; $dia_semana++)
				{
					if($tipo->dia_semana == $dia_semana)
					{
						if($tipo->id_tipo_transaccion == 11) // Ventas 0KM 0101 - 11
						{ 
							$suma[1][$dia_semana] += intval($tipo->total); 
							$suma[1][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 12) // Ventas usados 0102 - 12 
						{ 
							$suma[2][$dia_semana] += intval($tipo->total); 
							$suma[2][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 13) // Cobros documentos 0103 - 13
						{
							$suma[3][$dia_semana] += intval($tipo->total); 
							$suma[3][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 14) // Cobros documentos 0104 - 14
						{
							$suma[4][$dia_semana] += intval($tipo->total); 
							$suma[4][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 15) // Otros Ingresos 0105 - 15
						{
							$suma[5][$dia_semana] += intval($tipo->total); 
							$suma[5][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 16) // Devolución 0106 - 16
						{
							$suma[7][$dia_semana] += intval($tipo->total); 
							$suma[7][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 17) // Devolución 0107 - 17
						{
							$suma[8][$dia_semana] += intval($tipo->total); 
							$suma[8][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
					}
				}
			}
			$registros = array();
			array_push($registros, 
				array("nombre"=> "Ventas 0KM 0101", "dias"=> $suma[1]),
				array("nombre"=> "Ventas usados 0102", "dias"=> $suma[2]),
				array("nombre"=> "Cobros documentos 0103", "dias"=> $suma[3]),
				array("nombre"=> "Cobros documentos 0104", "dias"=> $suma[4]),
				array("nombre"=> "Otros Ingresos 0105","dias"=> $suma[5]),
				array("nombre"=> "Ingresos por ventas", "dias"=> $suma[6]),
				array("nombre"=> "Devolucion 0106", "dias"=> $suma[7]),
				array("nombre"=> "Devolucion 0107","dias"=> $suma[8]),
				array("nombre"=> "TOTAL INGRESOS SALTA", "dias"=> $suma[9])
			); 

			return $response->withStatus(200)->withJson([
				'data' => $registros,
			]);
		}
		else if($tabla == 2)
		{
			$tipos = DB::table('tra_comprobantes')
			->selectRaw('id_tipo_transaccion, DAYOFWEEK(created_at) as dia_semana, SUM(total) as total, COUNT(*) as cantidad')
			->where('id_sucursal', '=', 2)
			->where('tipo', '=', 1)
			->whereRaw($semana)
			->groupBy(DB::raw('DAYOFWEEK(created_at) ,tra_comprobantes.id_tipo_transaccion'))
			->get();

			$suma = array( 
				1 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				2 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				3 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				4 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				5 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				6 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				7 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				8 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				9 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				10 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				11 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
			);
			$temp = 1;

			foreach ($tipos as $tipo) 
			{
				for($dia_semana = 1; $dia_semana < 8; $dia_semana++)
				{
					if($tipo->dia_semana == $dia_semana)
					{
						if($tipo->id_tipo_transaccion == 11) // Ventas 0KM 0101 - 11
						{ 
							$suma[1][$dia_semana] += intval($tipo->total); 
							$suma[1][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 12) // Ventas usados 0102 - 12 
						{ 
							$suma[2][$dia_semana] += intval($tipo->total); 
							$suma[2][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 13) // Cobros documentos 0103 - 13
						{
							$suma[3][$dia_semana] += intval($tipo->total); 
							$suma[3][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 14) // Cobros documentos 0104 - 14
						{
							$suma[4][$dia_semana] += intval($tipo->total); 
							$suma[4][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 15) // Otros Ingresos 0105 - 15
						{
							$suma[5][$dia_semana] += intval($tipo->total); 
							$suma[5][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 16) // Devolución 0106 - 16
						{
							$suma[7][$dia_semana] += intval($tipo->total); 
							$suma[7][8] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 17) // Devolución 0107 - 17
						{
							$suma[8][$dia_semana] += intval($tipo->total); 
							$suma[8][8] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 10) // Movimientos QL 1001 - 17
						{
							$suma[10][$dia_semana] += intval($tipo->total); 
							$suma[10][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
					}
				}
			}
			$registros = array();
			array_push($registros, 
				array("nombre"=> "Ventas 0KM 0101", "dias"=> $suma[1]),
				array("nombre"=> "Ventas usados 0102", "dias"=> $suma[2]),
				array("nombre"=> "Cobros documentos 0103", "dias"=> $suma[3]),
				array("nombre"=> "Cobros documentos 0104", "dias"=> $suma[4]),
				array("nombre"=> "Otros Ingresos 0105","dias"=> $suma[5]),
				array("nombre"=> "Ingresos por ventas", "dias"=> $suma[6]),
				array("nombre"=> "Devolucion 0106", "dias"=> $suma[7]),
				array("nombre"=> "Devolucion 0107","dias"=> $suma[8]),
				array("nombre"=> "TOTAL INGRESOS ORÁN", "dias"=> $suma[9]),
				array("nombre"=> "Movimientos QL 1001", "dias"=> $suma[10]),
				array("nombre"=> "TOTAL INGRESOS", "dias"=> $suma[9])
			); 

			return $response->withStatus(200)->withJson([
				'data' => $registros,
			]);
		}
		else if($tabla == 3)
		{
			$tipos = DB::table('tra_comprobantes')
			->selectRaw('id_tipo_transaccion, DAYOFWEEK(created_at) as dia_semana, SUM(total) as total, COUNT(*) as cantidad')
			->where('id_sucursal', '=', 1)
			->where('tipo', '=', 2)
			->whereRaw($semana)
			->groupBy(DB::raw('DAYOFWEEK(created_at) ,tra_comprobantes.id_tipo_transaccion'))
			->get();

			$suma = array( 
				1 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				2 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				3 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				4 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				5 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				6 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				7 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				8 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				9 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0)
			);
			$temp = 1;

			foreach ($tipos as $tipo) 
			{
				for($dia_semana = 1; $dia_semana < 8; $dia_semana++)
				{
					if($tipo->dia_semana == $dia_semana)
					{
						if($tipo->id_tipo_transaccion == 11) // Ventas 0KM 0101 - 11
						{ 
							$suma[1][$dia_semana] += intval($tipo->total); 
							$suma[1][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 12) // Ventas usados 0102 - 12 
						{ 
							$suma[2][$dia_semana] += intval($tipo->total); 
							$suma[2][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 13) // Cobros documentos 0103 - 13
						{
							$suma[3][$dia_semana] += intval($tipo->total); 
							$suma[3][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 14) // Cobros documentos 0104 - 14
						{
							$suma[4][$dia_semana] += intval($tipo->total); 
							$suma[4][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 15) // Otros Ingresos 0105 - 15
						{
							$suma[5][$dia_semana] += intval($tipo->total); 
							$suma[5][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 16) // Devolución 0106 - 16
						{
							$suma[7][$dia_semana] += intval($tipo->total); 
							$suma[7][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 17) // Devolución 0107 - 17
						{
							$suma[8][$dia_semana] += intval($tipo->total); 
							$suma[8][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
					}
				}
			}
			$registros = array();
			array_push($registros, 
				array("nombre"=> "Ventas 0KM 0101", "dias"=> $suma[1]),
				array("nombre"=> "Ventas usados 0102", "dias"=> $suma[2]),
				array("nombre"=> "Cobros documentos 0103", "dias"=> $suma[3]),
				array("nombre"=> "Cobros documentos 0104", "dias"=> $suma[4]),
				array("nombre"=> "Otros Ingresos 0105","dias"=> $suma[5]),
				array("nombre"=> "Ingresos por ventas", "dias"=> $suma[6]),
				array("nombre"=> "Devolucion 0106", "dias"=> $suma[7]),
				array("nombre"=> "Devolucion 0107","dias"=> $suma[8]),
				array("nombre"=> "TOTAL INGRESOS SALTA", "dias"=> $suma[9])
			); 

			return $response->withStatus(200)->withJson([
				'data' => $registros,
			]);
		}
		else if($tabla == 4)
		{
			$tipos = DB::table('tra_comprobantes')
			->selectRaw('id_tipo_transaccion, DAYOFWEEK(created_at) as dia_semana, SUM(total) as total, COUNT(*) as cantidad')
			->where('id_sucursal', '=', 2)
			->whereRaw($semana)
			->groupBy(DB::raw('DAYOFWEEK(created_at) ,tra_comprobantes.id_tipo_transaccion'))
			->get();

			$suma = array( 
				1 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				2 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				3 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				4 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				5 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				6 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				7 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				8 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				9 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0)
			);
			$temp = 1;

			foreach ($tipos as $tipo) 
			{
				for($dia_semana = 1; $dia_semana < 8; $dia_semana++)
				{
					if($tipo->dia_semana == $dia_semana)
					{
						if($tipo->id_tipo_transaccion == 11) // Ventas 0KM 0101 - 11
						{ 
							$suma[1][$dia_semana] += intval($tipo->total); 
							$suma[1][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 12) // Ventas usados 0102 - 12 
						{ 
							$suma[2][$dia_semana] += intval($tipo->total); 
							$suma[2][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 13) // Cobros documentos 0103 - 13
						{
							$suma[3][$dia_semana] += intval($tipo->total); 
							$suma[3][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 14) // Cobros documentos 0104 - 14
						{
							$suma[4][$dia_semana] += intval($tipo->total); 
							$suma[4][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 15) // Otros Ingresos 0105 - 15
						{
							$suma[5][$dia_semana] += intval($tipo->total); 
							$suma[5][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 16) // Devolución 0106 - 16
						{
							$suma[7][$dia_semana] += intval($tipo->total); 
							$suma[7][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 17) // Devolución 0107 - 17
						{
							$suma[8][$dia_semana] += intval($tipo->total); 
							$suma[8][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
					}
				}
			}
			$registros = array();
			array_push($registros, 
				array("nombre"=> "Ventas 0KM 0101", "dias"=> $suma[1]),
				array("nombre"=> "Ventas usados 0102", "dias"=> $suma[2]),
				array("nombre"=> "Cobros documentos 0103", "dias"=> $suma[3]),
				array("nombre"=> "Cobros documentos 0104", "dias"=> $suma[4]),
				array("nombre"=> "Otros Ingresos 0105","dias"=> $suma[5]),
				array("nombre"=> "Ingresos por ventas", "dias"=> $suma[6]),
				array("nombre"=> "Devolucion 0106", "dias"=> $suma[7]),
				array("nombre"=> "Devolucion 0107","dias"=> $suma[8]),
				array("nombre"=> "TOTAL INGRESOS SALTA", "dias"=> $suma[9])
			); 

			return $response->withStatus(200)->withJson([
				'data' => $registros,
			]);
		}
		else if($tabla == 5)
		{
			$tipos = DB::table('tra_comprobantes')
			->selectRaw('id_tipo_transaccion, DAYOFWEEK(created_at) as dia_semana, SUM(total) as total, COUNT(*) as cantidad')
			->where('id_sucursal', '=', 2)
			->whereRaw($semana)
			->groupBy(DB::raw('DAYOFWEEK(created_at) ,tra_comprobantes.id_tipo_transaccion'))
			->get();

			$suma = array( 
				1 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				2 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				3 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				4 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				5 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				6 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				7 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				8 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				9 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0)
			);
			$temp = 1;

			foreach ($tipos as $tipo) 
			{
				for($dia_semana = 1; $dia_semana < 8; $dia_semana++)
				{
					if($tipo->dia_semana == $dia_semana)
					{
						if($tipo->id_tipo_transaccion == 11) // Ventas 0KM 0101 - 11
						{ 
							$suma[1][$dia_semana] += intval($tipo->total); 
							$suma[1][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 12) // Ventas usados 0102 - 12 
						{ 
							$suma[2][$dia_semana] += intval($tipo->total); 
							$suma[2][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 13) // Cobros documentos 0103 - 13
						{
							$suma[3][$dia_semana] += intval($tipo->total); 
							$suma[3][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 14) // Cobros documentos 0104 - 14
						{
							$suma[4][$dia_semana] += intval($tipo->total); 
							$suma[4][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 15) // Otros Ingresos 0105 - 15
						{
							$suma[5][$dia_semana] += intval($tipo->total); 
							$suma[5][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 16) // Devolución 0106 - 16
						{
							$suma[7][$dia_semana] += intval($tipo->total); 
							$suma[7][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 17) // Devolución 0107 - 17
						{
							$suma[8][$dia_semana] += intval($tipo->total); 
							$suma[8][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
					}
				}
			}
			$registros = array();
			array_push($registros, 
				array("nombre"=> "Ventas 0KM 0101", "dias"=> $suma[1]),
				array("nombre"=> "Ventas usados 0102", "dias"=> $suma[2]),
				array("nombre"=> "Cobros documentos 0103", "dias"=> $suma[3]),
				array("nombre"=> "Cobros documentos 0104", "dias"=> $suma[4]),
				array("nombre"=> "Otros Ingresos 0105","dias"=> $suma[5]),
				array("nombre"=> "Ingresos por ventas", "dias"=> $suma[6]),
				array("nombre"=> "Devolucion 0106", "dias"=> $suma[7]),
				array("nombre"=> "Devolucion 0107","dias"=> $suma[8]),
				array("nombre"=> "TOTAL INGRESOS SALTA", "dias"=> $suma[9])
			); 

			return $response->withStatus(200)->withJson([
				'data' => $registros,
			]);
		}
		else if($tabla == 6)
		{
			$tipos = DB::table('tra_comprobantes')
			->selectRaw('id_tipo_transaccion, DAYOFWEEK(created_at) as dia_semana, SUM(total) as total, COUNT(*) as cantidad')
			->where('id_sucursal', '=', 2)
			->whereRaw($semana)
			->groupBy(DB::raw('DAYOFWEEK(created_at) ,tra_comprobantes.id_tipo_transaccion'))
			->get();

			$suma = array( 
				1 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				2 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				3 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				4 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				5 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				6 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				7 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				8 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				9 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0)
			);
			$temp = 1;

			foreach ($tipos as $tipo) 
			{
				for($dia_semana = 1; $dia_semana < 8; $dia_semana++)
				{
					if($tipo->dia_semana == $dia_semana)
					{
						if($tipo->id_tipo_transaccion == 11) // Ventas 0KM 0101 - 11
						{ 
							$suma[1][$dia_semana] += intval($tipo->total); 
							$suma[1][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 12) // Ventas usados 0102 - 12 
						{ 
							$suma[2][$dia_semana] += intval($tipo->total); 
							$suma[2][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 13) // Cobros documentos 0103 - 13
						{
							$suma[3][$dia_semana] += intval($tipo->total); 
							$suma[3][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 14) // Cobros documentos 0104 - 14
						{
							$suma[4][$dia_semana] += intval($tipo->total); 
							$suma[4][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 15) // Otros Ingresos 0105 - 15
						{
							$suma[5][$dia_semana] += intval($tipo->total); 
							$suma[5][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 16) // Devolución 0106 - 16
						{
							$suma[7][$dia_semana] += intval($tipo->total); 
							$suma[7][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 17) // Devolución 0107 - 17
						{
							$suma[8][$dia_semana] += intval($tipo->total); 
							$suma[8][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
					}
				}
			}
			$registros = array();
			array_push($registros, 
				array("nombre"=> "Ventas 0KM 0101", "dias"=> $suma[1]),
				array("nombre"=> "Ventas usados 0102", "dias"=> $suma[2]),
				array("nombre"=> "Cobros documentos 0103", "dias"=> $suma[3]),
				array("nombre"=> "Cobros documentos 0104", "dias"=> $suma[4]),
				array("nombre"=> "Otros Ingresos 0105","dias"=> $suma[5]),
				array("nombre"=> "Ingresos por ventas", "dias"=> $suma[6]),
				array("nombre"=> "Devolucion 0106", "dias"=> $suma[7]),
				array("nombre"=> "Devolucion 0107","dias"=> $suma[8]),
				array("nombre"=> "TOTAL INGRESOS SALTA", "dias"=> $suma[9])
			); 

			return $response->withStatus(200)->withJson([
				'data' => $registros,
			]);	
		}
		else if($tabla == 7	)
		{
			$tipos = DB::table('tra_comprobantes')
			->selectRaw('id_tipo_transaccion, DAYOFWEEK(created_at) as dia_semana, SUM(total) as total, COUNT(*) as cantidad')
			->where('id_sucursal', '=', 2)
			->whereRaw($semana)
			->groupBy(DB::raw('DAYOFWEEK(created_at) ,tra_comprobantes.id_tipo_transaccion'))
			->get();

			$suma = array( 
				1 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				2 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				3 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				4 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				5 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				6 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0), 
				7 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				8 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0),
				9 => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0, 8=>0)
			);
			$temp = 1;

			foreach ($tipos as $tipo) 
			{
				for($dia_semana = 1; $dia_semana < 8; $dia_semana++)
				{
					if($tipo->dia_semana == $dia_semana)
					{
						if($tipo->id_tipo_transaccion == 11) // Ventas 0KM 0101 - 11
						{ 
							$suma[1][$dia_semana] += intval($tipo->total); 
							$suma[1][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 12) // Ventas usados 0102 - 12 
						{ 
							$suma[2][$dia_semana] += intval($tipo->total); 
							$suma[2][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 13) // Cobros documentos 0103 - 13
						{
							$suma[3][$dia_semana] += intval($tipo->total); 
							$suma[3][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 14) // Cobros documentos 0104 - 14
						{
							$suma[4][$dia_semana] += intval($tipo->total); 
							$suma[4][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 15) // Otros Ingresos 0105 - 15
						{
							$suma[5][$dia_semana] += intval($tipo->total); 
							$suma[5][8] += intval($tipo->total);
							$suma[6][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][8] += intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 16) // Devolución 0106 - 16
						{
							$suma[7][$dia_semana] += intval($tipo->total); 
							$suma[7][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
						else if($tipo->id_tipo_transaccion == 17) // Devolución 0107 - 17
						{
							$suma[8][$dia_semana] += intval($tipo->total); 
							$suma[8][8] += intval($tipo->total);
							$suma[9][$dia_semana] += intval($tipo->total);
							$suma[9][$dia_semana] -= intval($tipo->total);
							$suma[9][8] -= intval($tipo->total);
						} 
					}
				}
			}
			$registros = array();
			array_push($registros, 
				array("nombre"=> "Ventas 0KM 0101", "dias"=> $suma[1]),
				array("nombre"=> "Ventas usados 0102", "dias"=> $suma[2]),
				array("nombre"=> "Cobros documentos 0103", "dias"=> $suma[3]),
				array("nombre"=> "Cobros documentos 0104", "dias"=> $suma[4]),
				array("nombre"=> "Otros Ingresos 0105","dias"=> $suma[5]),
				array("nombre"=> "Ingresos por ventas", "dias"=> $suma[6]),
				array("nombre"=> "Devolucion 0106", "dias"=> $suma[7]),
				array("nombre"=> "Devolucion 0107","dias"=> $suma[8]),
				array("nombre"=> "TOTAL INGRESOS SALTA", "dias"=> $suma[9])
			); 

			return $response->withStatus(200)->withJson([
				'data' => $registros,
			]);
		}
	}

	public function informefinal($request,$response, array $args)
	{
		return $this->container->view->render($response, 'admin_views/caja/informes/presupuesto_caja.twig',[
			'nro_semana'=>$args["semana"],
		]);
	}
	
	public function nuevoFacturaVenta($request,$response){
		$id_sucursal = auth::sucursal()->id;
		$tiposTransaccionIngreso = DB::table('tra_tipos_transaccion as t1')->
	    select(DB::raw('concat(t2.nombre_tipo," ",t1.nombre_tipo," [",t1.codigo,"]") as nombre, t1.id as id, t1.tipo as tipo, t1.factura as factura'))->
	    join('tra_tipos_transaccion AS t2', 't2.id', '=', 't1.padre')->
	    where('t1.estado_tipo', 1)->
	    whereIn('t1.tipo',[-1,0])->
	    where(function($query) use ($id_sucursal){
					$query->where('t1.sucursales',$id_sucursal)
						->orWhere('t1.sucursales','like','%'.$id_sucursal.'%')
						->orWhere('t1.sucursales',-1);
				})->
	    orderBy('t2.nombre_tipo','asc')->
	    get();
		return $this->container->view->render($response, 'admin_views/caja/comprobante/facturaVenta.twig',[
			'tipoComprobante'=>TipoComprobante::where('estado',1)->get(),
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'tipoResponsable'=>TipoResponsable::where('estado',1)->get(),
			'tipoCondicionIva'=> TipoCondicionIva::where('estado',1)->get(),
			'sucursal'=>auth::sucursal(),
			'tiposTransaccionIngreso'=>$tiposTransaccionIngreso,
		]);
	}

	public function nuevoFacturaCompra($request,$response){
		$id_sucursal = auth::sucursal()->id;
		$tiposTransaccionEgreso = DB::table('tra_tipos_transaccion  as t1')->
	    select(DB::raw('concat(t2.nombre_tipo," ",t1.nombre_tipo," [",t1.codigo,"]") as nombre, t1.id as id, t1.tipo as tipo'))->
	    join('tra_tipos_transaccion AS t2', 't2.id', '=', 't1.padre')->
	    where('t1.estado_tipo', 1)->
	    whereIn('t1.tipo',[-1,1])->
	    where(function($query) use ($id_sucursal){
					$query->where('t1.sucursales',$id_sucursal)
						->orWhere('t1.sucursales','like','%'.$id_sucursal.'%')
						->orWhere('t1.sucursales',-1);
				})->
    	orderBy('t2.nombre_tipo','asc')->
    	get();
		return $this->container->view->render($response, 'admin_views/caja/comprobante/facturaCompra.twig',[
			'tipoComprobante'=>TipoComprobante::where('estado',1)->get(),
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'tipoResponsable'=>TipoResponsable::where('estado',1)->get(),
			'tipoCondicionIva'=> TipoCondicionIva::where('estado',1)->get(),
			'sucursal'=>auth::sucursal(),
			'tiposTransaccionEgreso'=>$tiposTransaccionEgreso,
		]);
	}


	public function nuevaFacturaCuentaCorriente($request,$response, $args)
	{
		$tipo_cuenta = $request->getQueryParam('tipo_cuenta',0);
		$id_sucursal = auth::sucursal()->id;
		$cuenta = Cuenta::find($args['id']);
		if($tipo_cuenta>0){
			$cuenta->tipo_cuenta = $tipo_cuenta;
		}
		if($cuenta->tipo_cuenta==1){
			$tiposTransaccion = DB::table('tra_tipos_transaccion as t1')->
		    select(DB::raw('concat(t2.nombre_tipo,"",t1.nombre_tipo) as nombre, t1.id as id, t1.tipo as tipo, t1.factura as factura'))->
		    join('tra_tipos_transaccion AS t2', 't2.id', '=', 't1.padre')->
		    where('t1.estado_tipo', 1)->
		    whereIn('t1.tipo',[-1,0])->
		    where(function($query) use ($id_sucursal){
						$query->where('t1.sucursales',$id_sucursal)
							->orWhere('t1.sucursales','like','%'.$id_sucursal.'%')
							->orWhere('t1.sucursales',-1);
					})->
		    orderBy('t2.nombre_tipo','asc')->
		    get();
	  } else if($cuenta->tipo_cuenta==2){
	  	$tiposTransaccion = DB::table('tra_tipos_transaccion  as t1')->
		    select(DB::raw('concat(t2.nombre_tipo,"",t1.nombre_tipo) as nombre, t1.id as id, t1.tipo as tipo'))->
		    join('tra_tipos_transaccion AS t2', 't2.id', '=', 't1.padre')->
		    where('t1.estado_tipo', 1)->
		    whereIn('t1.tipo',[-1,1])->
		    where(function($query) use ($id_sucursal){
						$query->where('t1.sucursales',$id_sucursal)
							->orWhere('t1.sucursales','like','%'.$id_sucursal.'%')
							->orWhere('t1.sucursales',-1);
					})->
	    	orderBy('t2.nombre_tipo','asc')->
	    	get();
	  }

		return $this->container->view->render($response, 'admin_views/caja/cuenta/crear_factura.twig',[
			'tipo_cuenta' => $tipo_cuenta,
			'cuenta'=> $cuenta,
			'tipoComprobante'=>TipoComprobante::where('estado',1)->get(),
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'tipoResponsable'=>TipoResponsable::where('estado',1)->get(),
			'tipoCondicionIva'=> TipoCondicionIva::where('estado',1)->get(),
			'sucursal'=>auth::sucursal(),
			'tiposTransaccion'=>$tiposTransaccion,
		]);
	}

	public function tableroMovimientosCuentaCorriente($request,$response, $args){
		$id_sucursal = auth::sucursal()->id;
		$errores = ComprobanteError::where('estado',1)
			->whereHas('comprobante',function($q)use($id_sucursal){
				$q->where('id_sucursal',$id_sucursal);
			})
			->orderBy('created_at','desc')
			->get()
			->unique('id_comprobante');

		/*{{ (item.total-item.nota_credito)|number_format(2, ',', '.') }}
		{{item.pagado|number_format(2, ',', '.') }}
		{{(item.total-item.pagado) |number_format(2, ',', '.') }}*/

		$registros = Comprobante::where('cuentacorriente', $args['id'])->get();
		$importe = 0;
		$balance = 0;
		$saldo = 0;

		foreach($registros as $factura)
		{
			if($factura->id_tipo_comprobante == 1)
			{
				$importe += $factura->total - $factura->nota_credito;
				$balance += $factura->pagado;
				$saldo += $factura->total - $factura->pagado;
			}
		}
		$comprobantes = $registros->count();

		return $this->container->view->render($response, 'admin_views/caja/cuenta/tablero_movimientos.twig',[
			'cuenta'=> Cuenta::find($args['id']),
			'sucursal'=>auth::sucursal(),
			'errores' => $errores,
			'registros' => $registros,
			'importe' => $importe,
			'balance'=> $balance,
			'saldo' => $saldo,
			'comprobantes' => $comprobantes,
		]);
	}

	public function getAmount($money){
    $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
    $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

    $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

    $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
    $removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);

    return (float) str_replace(',', '.', $removedThousendSeparator);
	}

	public function agregarFacturaVenta($request,$response){
		$validation = $this->validator->validate($request, [
			'factura_tipo_comprobante'=>v::numeric(),
			'factura_fecha'=>v::date('d/m/Y'),
			'factura_fecha_vto'=>v::date('d/m/Y'),
			'factura_documento'=>v::numeric(),
			'factura_cliente_apellido'=>v::alpha('ñÑ'),
			'factura_detalle_descripcion'=>v::arrayVal()->each(v::alnum('ñÑ')),
			'factura_detalle_precio'=>v::arrayVal()->each(v::numeric()),
			'factura_detalle_bonificado'=>v::arrayVal()->each(v::numeric()),
			'factura_detalle_subtotal'=>v::arrayVal()->each(v::numeric()),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('comprobante.venta.nuevo'));
		}

		$factura_tipo_comprobante = $request->getParam('factura_tipo_comprobante');
		$factura_tipo_responsable = $request->getParam('factura_tipo_responsable');
		$factura_fecha = $request->getParam('factura_fecha');
		$factura_fecha_vto = $request->getParam('factura_fecha_vto');
		$factura_observaciones = $request->getParam('factura_observaciones');

		$id_tipo_transaccion = $request->getParam('id_tipo_transaccion');
		$facturar = $request->getParam('facturar');
		$factura_periodo = $request->getParam('factura_periodo');

		$facturar_total = $request->getParam('facturar_total');

		$factura_tipo_documento = $request->getParam('factura_tipo_documento');
		$factura_documento = $request->getParam('factura_documento');
		$factura_cliente_nombre = $request->getParam('factura_cliente_nombre');
		$factura_cliente_apellido = $request->getParam('factura_cliente_apellido');
		$factura_cliente_domicilio = $request->getParam('factura_cliente_domicilio');
		$factura_cliente_email = $request->getParam('factura_cliente_email');

		$factura_detalle_codigo = $request->getParam('factura_detalle_codigo');
		$factura_detalle_descripcion = $request->getParam('factura_detalle_descripcion');
		$factura_detalle_precio = $request->getParam('factura_detalle_precio');
		$factura_detalle_iva = $request->getParam('factura_detalle_iva');
		$factura_detalle_cantidad = $request->getParam('factura_detalle_cantidad');
		$factura_detalle_impuesto = $request->getParam('factura_detalle_impuesto');
		$factura_detalle_bonificado = $request->getParam('factura_detalle_bonificado');
		$factura_detalle_subtotal = $request->getParam('factura_detalle_subtotal');

		$id_cliente = $request->getParam('id_cliente');

		try {
			$factura_bonificado = $this->getAmount($request->getParam('factura_bonificado'));
			$factura_impuesto = $this->getAmount($request->getParam('factura_impuesto'));
			$factura_nogravado = $this->getAmount($request->getParam('factura_nogravado'));
			$factura_exento = $this->getAmount($request->getParam('factura_exento'));
			$factura_importe = $this->getAmount($request->getParam('factura_importe'));
			DB::beginTransaction();

			if($id_cliente==0){
				$cliente = Cliente::create([
					'nombre' => $factura_cliente_nombre,
					'apellido' => $factura_cliente_apellido,
					'id_tipo_documento'=> $factura_tipo_documento,
					'documento' => $factura_documento,
					'domicilio' => $factura_cliente_domicilio,
					'email' => $factura_cliente_email,
					'id_usuario' => auth::empleado()->id_usuario,
					'id_sucursal' => auth::sucursal()->id,
					'id_tipo_responsable' => $factura_tipo_responsable,
				]);

				$id_cliente = $cliente->id;
			} else if($id_cliente>0) {
				Cliente::where('id',$id_cliente)->update([
					'nombre' => $factura_cliente_nombre,
					'apellido' => $factura_cliente_apellido,
					'id_tipo_documento'=> $factura_tipo_documento,
					'documento' => $factura_documento,
					'domicilio' => $factura_cliente_domicilio,
					'email' => $factura_cliente_email,
					'id_tipo_responsable' => $factura_tipo_responsable,
				]);
			} else {
				$id_cliente = 0;
				$factura_cliente_apellido = 'VARIOS';
				$factura_cliente_nombre = '';
				$factura_cliente_domicilio = '';
			}
			$dt = new \DateTime();
			$comprobante = Comprobante::create([
				'id_cliente' => $id_cliente,
				'id_tipo_transaccion' => $id_tipo_transaccion,
				'id_tipo_comprobante' => $factura_tipo_comprobante,
				'id_tipo_responsable' => $factura_tipo_responsable,
				'facturar' => $facturar,
				'facturar_total' => $facturar_total,
				'id_tipo_documento' => $factura_tipo_documento,
				'documento_numero' => $factura_documento,
				'razon_social' => $factura_cliente_apellido.' '.$factura_cliente_nombre,
				'domicilio' => $factura_cliente_domicilio,
				'email' => $factura_cliente_email,
				'fecha' => $dt->createFromFormat('d/m/Y',$factura_fecha),
				'fecha_vto' => $dt->createFromFormat('d/m/Y',$factura_fecha_vto),
				'observaciones' => $factura_observaciones,
				'total' => $factura_importe,
				'bonificacion' => $factura_bonificado,
				'impuesto' => $factura_impuesto,
				'nogravado' => $factura_nogravado,
				'exento' => $factura_exento,
				'id_usuario' => auth::empleado()->id_usuario,
				'id_sucursal' => auth::sucursal()->id,
				'tipo' => 1, //1 Venta 2 Compra
				'periodo' => $factura_periodo,
			]);

			for($i=0;$i<count($factura_detalle_descripcion);$i++) {
				ComprobanteDetalle::create([
					'id_comprobante' => $comprobante->id,
					'id_tipo_condicion_iva' => $factura_detalle_iva[$i],
					'codigo' => $factura_detalle_codigo[$i],
					'descripcion' => $factura_detalle_descripcion[$i],
					'importe' => $factura_detalle_precio[$i],
					'cantidad' => $factura_detalle_cantidad[$i],
					'impuesto' => $factura_detalle_impuesto[$i],
					'bonificado' => $factura_detalle_bonificado[$i],
					'subtotal' => $factura_detalle_subtotal[$i],
					'id_usuario' => auth::empleado()->id_usuario,
					'orden' => $i,
				]);
			}

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    DB::rollBack();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
			return $response->withRedirect($this->router->pathFor('comprobante.venta.nuevo'));
		}
		$this->flash->addMessage('info', 'La factura de venta fue creada Exitosamente.');
		return $response->withRedirect($this->router->pathFor('comprobante.venta.tablero'));
	}

	public function agregarFacturaCompra($request,$response, $args){
		$validation = $this->validator->validate($request, [
			'factura_tipo_comprobante'=>v::numeric(),
			'factura_fecha'=>v::date('d/m/Y'),
			'factura_fecha_vto'=>v::date('d/m/Y'),
			'documento'=>v::numeric(),
			'razon_social'=>v::alpha('ñÑ'),
			'factura_detalle_descripcion'=>v::arrayVal()->each(v::alnum('ñÑ')),
			'factura_detalle_precio'=>v::arrayVal()->each(v::numeric()),
			'factura_detalle_bonificado'=>v::arrayVal()->each(v::numeric()),
			'factura_detalle_subtotal'=>v::arrayVal()->each(v::numeric()),
		]);


		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('comprobante.compra.nuevo'));
		}

		$factura_tipo_comprobante = $request->getParam('factura_tipo_comprobante');
		$factura_tipo_responsable = $request->getParam('factura_tipo_responsable');
		$facturar_numero = $request->getParam('facturar_numero');
		$factura_fecha = $request->getParam('factura_fecha');
		$factura_fecha_vto = $request->getParam('factura_fecha_vto');
		$factura_observaciones = $request->getParam('factura_observaciones');

		$facturar_total = $request->getParam('facturar_total');

		$id_tipo_transaccion = $request->getParam('id_tipo_transaccion');
		$facturar = $request->getParam('facturar');
		$facturar_total = $request->getParam('facturar_total');

		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$documento = $request->getParam('documento');
		$razon_social = $request->getParam('razon_social');
		$domicilio = $request->getParam('domicilio');
		$email = $request->getParam('email');


		$factura_periodo = $request->getParam('factura_periodo');

		$factura_detalle_codigo = $request->getParam('factura_detalle_codigo');
		$factura_detalle_descripcion = $request->getParam('factura_detalle_descripcion');
		$factura_detalle_precio = $request->getParam('factura_detalle_precio');
		$factura_detalle_iva = $request->getParam('factura_detalle_iva');
		$factura_detalle_cantidad = $request->getParam('factura_detalle_cantidad');
		$factura_detalle_impuesto = $request->getParam('factura_detalle_impuesto');
		$factura_detalle_bonificado = $request->getParam('factura_detalle_bonificado');
		$factura_detalle_subtotal = $request->getParam('factura_detalle_subtotal');

		$id_proveedor = $request->getParam('id_proveedor');

		try {
			$factura_bonificado = $this->getAmount($request->getParam('factura_bonificado'));
			$factura_impuesto = $this->getAmount($request->getParam('factura_impuesto'));
			$factura_nogravado = $this->getAmount($request->getParam('factura_nogravado'));
			$factura_exento = $this->getAmount($request->getParam('factura_exento'));
			$factura_importe = $this->getAmount($request->getParam('factura_importe'));
			DB::beginTransaction();

			if($id_proveedor==0){
				$id_proveedor = Proveedor::create([
					'razon_social' => $razon_social,
					'id_tipo_documento'=> $id_tipo_documento,
					'id_tipo_responsable' => $factura_tipo_responsable,
					'documento' => $documento,
					'domicilio' => $domicilio,
					'email' => $email,
					'id_usuario' => auth::empleado()->id_usuario,
					'id_sucursal' => auth::sucursal()->id,
				])->id;

			} else if($id_proveedor>0) {
				Proveedor::where('id',$id_proveedor)->update([
					'razon_social' => $razon_social,
					'id_tipo_documento'=> $id_tipo_documento,
					'id_tipo_responsable' => $factura_tipo_responsable,
					'documento' => $documento,
					'domicilio' => $domicilio,
					'email' => $email,
				]);
			}
			else
			{
				$id_proveedor = 0;
				$domicilio = '';
				$email = '';
			}
			$dt = new \DateTime();
			$comprobante = Comprobante::create([
				'id_proveedor' => $id_proveedor,
				'id_tipo_transaccion' => $id_tipo_transaccion,
				'id_tipo_comprobante' => $factura_tipo_comprobante,
				'id_tipo_responsable' => $factura_tipo_responsable,
				'numero' => $facturar_numero,
				'facturar' => $facturar,
				'facturar_total' => $facturar_total,
				'id_tipo_documento' => $id_tipo_documento,
				'documento_numero' => $documento,
				'razon_social' => $razon_social,
				'domicilio' => $domicilio,
				'email' => $email,
				'fecha' => $dt->createFromFormat('d/m/Y',$factura_fecha),
				'fecha_vto' => $dt->createFromFormat('d/m/Y',$factura_fecha_vto),
				'observaciones' => $factura_observaciones,
				'total' => $factura_importe,
				'bonificacion' => $factura_bonificado,
				'impuesto' => $factura_impuesto,
				'nogravado' => $factura_nogravado,
				'exento' => $factura_exento,
				'id_usuario' => auth::empleado()->id_usuario,
				'id_sucursal' => auth::sucursal()->id,
				'tipo' => 2, //1 Venta 2 Compra
				'periodo' => $factura_periodo,
			]);

			for($i=0;$i<count($factura_detalle_descripcion);$i++) {
				ComprobanteDetalle::create([
					'id_comprobante' => $comprobante->id,
					'id_tipo_condicion_iva' => $factura_detalle_iva[$i],
					'codigo' => $factura_detalle_codigo[$i],
					'descripcion' => $factura_detalle_descripcion[$i],
					'importe' => $factura_detalle_precio[$i],
					'cantidad' => $factura_detalle_cantidad[$i],
					'impuesto' => $factura_detalle_impuesto[$i],
					'bonificado' => $factura_detalle_bonificado[$i],
					'subtotal' => $factura_detalle_subtotal[$i],
					'id_usuario' => auth::empleado()->id_usuario,
					'orden' => $i,
				]);
			}

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    DB::rollBack();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
			return $response->withRedirect($this->router->pathFor('comprobante.compra.nuevo'));
		}
		$this->flash->addMessage('info', 'La factura de venta fue creada Exitosamente.');
		return $response->withRedirect($this->router->pathFor('comprobante.compra.tablero'));
	}

	public function agregarFacturaCuentaCorriente($request,$response, $args){
		$id_cuentacorriente = $args['id'];
		$validation = $this->validator->validate($request, [
			'factura_tipo_comprobante'=>v::numeric(),
			'factura_fecha'=>v::date('d/m/Y'),
			'factura_fecha_vto'=>v::date('d/m/Y'),
			'factura_detalle_descripcion'=>v::arrayVal()->each(v::alnum('ñÑ')),
			'factura_detalle_precio'=>v::arrayVal()->each(v::numeric()),
			'factura_detalle_bonificado'=>v::arrayVal()->each(v::numeric()),
			'factura_detalle_subtotal'=>v::arrayVal()->each(v::numeric()),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('comprobante.venta.nuevo'));
		}

		$cuentacorriente = Cuenta::where('id',$id_cuentacorriente)->first();
		$id_cliente = 0;
		$id_proveedor = 0;
		$tipo = 1;
		switch ($cuentacorriente->tipo_cuenta) {
			case 1: //CLIENTE
				$cliente = Cliente::where('id',$cuentacorriente->id_provedor_cliente)->first();
				$id_cliente = $cliente->id;
				$razon_social = $cliente->apellido .' '. $cliente->nombre;
				$documento = $cliente->documento;
				$id_tipo_documento = $cliente->id_tipo_documento;
				$tipo = 1;
				break;
			case 2: //PROVEEDOR
				$proveedor = Proveedor::where('id',$cuentacorriente->id_provedor_cliente)->first();
				$id_proveedor = $proveedor->id;
				$razon_social = $proveedor->razon_social;
				$documento = $proveedor->documento;
				$id_tipo_documento = $proveedor->id_tipo_documento;
				$tipo = 2;
				break;
			case 3: //BANCO
				$razon_social = $cuentacorriente->nombre_cuenta;
				$documento = 11111111;
				$id_tipo_documento = 99;
				break;
		}
		$tipo_cuenta = $request->getParam('tipo_cuenta',0);
		if($tipo_cuenta>0){
			$tipo = $tipo_cuenta;
		}

		$factura_tipo_comprobante = $request->getParam('factura_tipo_comprobante');
		$factura_fecha = $request->getParam('factura_fecha');
		$factura_fecha_vto = $request->getParam('factura_fecha_vto');
		$factura_observaciones = $request->getParam('factura_observaciones');
		$factura_periodo = $request->getParam('factura_periodo');

		$id_tipo_transaccion = $request->getParam('id_tipo_transaccion');
		$facturar = $request->getParam('facturar');
		$facturar_total = $request->getParam('facturar_total');
		$factura_tipo_responsable = $request->getParam('factura_tipo_responsable');

		$factura_detalle_codigo = $request->getParam('factura_detalle_codigo');
		$factura_detalle_descripcion = $request->getParam('factura_detalle_descripcion');
		$factura_detalle_precio = $request->getParam('factura_detalle_precio');
		$factura_detalle_iva = $request->getParam('factura_detalle_iva');
		$factura_detalle_cantidad = $request->getParam('factura_detalle_cantidad');
		$factura_detalle_impuesto = $request->getParam('factura_detalle_impuesto');
		$factura_detalle_bonificado = $request->getParam('factura_detalle_bonificado');
		$factura_detalle_subtotal = $request->getParam('factura_detalle_subtotal');

		try
		{
			$factura_bonificado = $this->getAmount($request->getParam('factura_bonificado'));
			$factura_impuesto = $this->getAmount($request->getParam('factura_impuesto'));
			$factura_nogravado = $this->getAmount($request->getParam('factura_nogravado'));
			$factura_exento = $this->getAmount($request->getParam('factura_exento'));
			$factura_importe = $this->getAmount($request->getParam('factura_importe'));
			DB::beginTransaction();

			$dt = new \DateTime();
			$comprobante = Comprobante::create([
				'id_cliente'=>$id_cliente,
				'id_proveedor'=>$id_proveedor,
				'id_tipo_transaccion' => $id_tipo_transaccion,
				'id_tipo_comprobante' => $factura_tipo_comprobante,
				'id_tipo_documento' => $id_tipo_documento,
				'id_tipo_responsable' => $factura_tipo_responsable,
				'documento_numero' => $documento,
				'razon_social' => $razon_social,
				'facturar' => $facturar,
				'facturar_total' => $facturar_total,
				'fecha' => $dt->createFromFormat('d/m/Y',$factura_fecha),
				'fecha_vto' => $dt->createFromFormat('d/m/Y',$factura_fecha_vto),
				'observaciones' => $factura_observaciones,
				'total' => $factura_importe,
				'bonificacion' => $factura_bonificado,
				'impuesto' => $factura_impuesto,
				'nogravado' => $factura_nogravado,
				'exento' => $factura_exento,
				'id_usuario' => auth::empleado()->id_usuario,
				'id_sucursal' => auth::sucursal()->id,
				'tipo' => $tipo, //1 Venta 2 Compra
				'estado' => 0,
				'cuentacorriente'=> $id_cuentacorriente,
				'periodo' => $factura_periodo,
			]);

			for($i=0;$i<count($factura_detalle_descripcion);$i++)
			{
				ComprobanteDetalle::create([
					'id_comprobante' => $comprobante->id,
					'id_tipo_condicion_iva' => $factura_detalle_iva[$i],
					'codigo' => $factura_detalle_codigo[$i],
					'descripcion' => $factura_detalle_descripcion[$i],
					'importe' => $factura_detalle_precio[$i],
					'cantidad' => $factura_detalle_cantidad[$i],
					'impuesto' => $factura_detalle_impuesto[$i],
					'bonificado' => $factura_detalle_bonificado[$i],
					'subtotal' => $factura_detalle_subtotal[$i],
					'id_usuario' => auth::empleado()->id_usuario,
					'orden' => $i,
				]);
			}

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    DB::rollBack();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
			return $response->withRedirect($this->router->pathFor('cuenta.crearmovimiento'));
		}
		$this->flash->addMessage('info', 'El Comprobante fue creado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('cuenta.movimientos', ['id' => $id_cuentacorriente]));
	}

	public function tableroVenta($request,$response){
		$id_sucursal = auth::sucursal()->id;
		$errores = ComprobanteError::where('estado',1)
			->whereHas('comprobante',function($q)use($id_sucursal){
				$q->where('id_sucursal',$id_sucursal);
			})
			->orderBy('created_at','desc')
			->get()
			->unique('id_comprobante');
		$clientesPago = Comprobante::where('estado',1)->where('tipo',1)->where('id_sucursal',$id_sucursal)->groupBy('id_cliente')->get()->count();
		$recibo_detalle = FacturaRecibo::where('estado',1)->pluck('id_recibo')->toArray();
		$recibos = ComprobanteDetalle::where('estado',1)->whereIn('id',$recibo_detalle)->pluck('id_comprobante')->toArray();
		$pagos = Comprobante::where('estado',1)->whereIn('id',$recibos)->where('tipo',1)->where('id_sucursal',$id_sucursal)->distinct('id');
		$ventas = Comprobante::where('estado',1)->whereNotIn('id',$pagos->pluck('id')->toArray())->where('tipo',1)->where('id_sucursal',$id_sucursal)->distinct('id');

		$dt = new \DateTime;
		$graficoVenta = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereNotIn('id',$pagos->pluck('id')->toArray())
			->where('tipo',1)
			->where('id_sucursal',$id_sucursal)
			->where('created_at','>=',$dt->modify('-3 week'))
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();

		$graficoPago = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereIn('id',$recibos)
			->where('tipo',1)
			->where('id_sucursal',$id_sucursal)
			->where('created_at','>=',$dt->modify('-3 week'))
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();
		return $this->container->view->render($response, 'admin_views/caja/comprobante/tableroVenta.twig',[
			'facturasCantidad' => $ventas->get()->count(),
			'facturasTotal' => $ventas->sum('total'),
			'graficoVenta' => $graficoVenta,
			'recibosCantidad' => $pagos->get()->count(),
			'recibosTotal' => $pagos->sum('total'),
			'graficoPago' => $graficoPago,
			'sucursal'=>auth::sucursal(),
			'clientesPago' => $clientesPago,
			'errores' => $errores,
		]);
	}

	public function tableroCompra($request,$response){
		$id_sucursal = auth::sucursal()->id;
		$proveedoresPago = Comprobante::where('estado',1)->where('tipo',2)->where('id_sucursal',$id_sucursal)->groupBy('id_proveedor')->get()->count();
		$recibo_detalle = FacturaRecibo::where('estado',1)->pluck('id_recibo')->toArray();
		$recibos = ComprobanteDetalle::where('estado',1)->whereIn('id',$recibo_detalle)->pluck('id_comprobante')->toArray();
		$pagos = Comprobante::where('estado',1)->whereIn('id',$recibos)->where('tipo',2)->where('id_sucursal',$id_sucursal)->distinct('id');
		$compras = Comprobante::where('estado',1)->whereNotIn('id',$pagos->pluck('id')->toArray())->where('tipo',2)->where('id_sucursal',$id_sucursal)->distinct('id');

		$dt = new \DateTime;
		$graficoCompra = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereNotIn('id',$pagos->pluck('id')->toArray())
			->where('tipo',2)
			->where('id_sucursal',$id_sucursal)
			->where('created_at','>=',$dt->modify('-3 week'))
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();

		$graficoPago = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereIn('id',$recibos)
			->where('tipo',2)
			->where('id_sucursal',$id_sucursal)
			->where('created_at','>=',$dt->modify('-3 week'))
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();
		return $this->container->view->render($response, 'admin_views/caja/comprobante/tableroCompra.twig',[
			'facturasCantidad' => $compras->get()->count(),
			'facturasTotal' => $compras->sum('total'),
			'graficoCompra' => $graficoCompra,
			'recibosCantidad' => $pagos->get()->count(),
			'recibosTotal' => $pagos->sum('total'),
			'graficoPago' => $graficoPago,
			'sucursal'=>auth::sucursal(),
			'proveedoresPago' => $proveedoresPago,
		]);
	}

	public function getFacturasVenta($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$recibo = $request->getParam('recibo',1);

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$id_sucursal = auth::sucursal()->id;
		$registros = Comprobante::with(
			'detalles.facturas.factura_detalle.comprobante',
			'detalles.facturas.movimiento.tipo',
			'detalles.recibos.recibo_detalle.comprobante',
			'detalles.recibos.movimiento.tipo',
			'tipoTransaccion',
			'individuo',
			'cliente',
			'tipoComprobante',
			'tipoResponsable',
			'tipoDocumento',
			'errores')
		->where('estado',1)
		->where('id_sucursal',$id_sucursal)
		->where('tipo',1);

		switch ($recibo) {
			case 0:
				$recibos = ComprobanteDetalle::where('tra_comprobante_detalles.estado',1)
					->rightJoin('tra_factura_recibo','tra_comprobante_detalles.id','=','tra_factura_recibo.id_recibo')
					->groupBy('tra_comprobante_detalles.id_comprobante')->pluck('tra_comprobante_detalles.id_comprobante')->toArray();
				$registros = $registros->whereNotIn('id',$recibos);
				break;
			case 2:
				$pdo = $this->db->connection()->getPdo();
				// right join tra_tipos_transaccion ttt on com.id_tipo_transaccion = ttt.id
				$facturas = $pdo->prepare('select com.id as id, sum(com2.total)-com.total as saldo
					from tra_comprobantes com
					right join tra_comprobante_detalles cde on com.id = cde.id_comprobante
					right join tra_factura_recibo tfr on cde.id = tfr.id_factura
					right join tra_comprobante_detalles cde2 on tfr.id_recibo = cde2.id
					right join tra_comprobantes com2 on cde2.id_comprobante = com2.id
					where com.estado = 1
					and cde.estado = 1
					and com.tipo = 1
					and com2.estado = 1
					and cde.id = tfr.id_factura
					and cde.id != tfr.id_recibo
					group by com.id
					having saldo < 0
					union
					select distinct com.id as id,0 as saldo
					from tra_comprobantes com
					right join tra_comprobante_detalles cde on com.id = cde.id_comprobante
					where com.estado = 1
					and cde.estado = 1
					and cde.id not in (select distinct id_factura from tra_factura_recibo where estado = 1 )
					and cde.id not in (select distinct id_recibo from tra_factura_recibo where estado = 1 )
					and com.tipo = 1');
				$facturas->execute();
				$facturas = $facturas->fetchAll(\PDO::FETCH_ASSOC);
				$filtro = [];
				foreach($facturas as $factura ) {
					array_push($filtro,$factura['id']);
				}
				$registros = $registros->whereIn('id',$filtro);
				break;
			case 3:
				$pdo = $this->db->connection()->getPdo();
				$facturas = $pdo->prepare('select com.id as id, sum(com2.total)-com.total as saldo
					from tra_comprobantes com
					right join tra_comprobante_detalles cde on com.id = cde.id_comprobante
					right join tra_factura_recibo tfr on cde.id = tfr.id_factura
					right join tra_comprobante_detalles cde2 on tfr.id_recibo = cde2.id
					right join tra_comprobantes com2 on cde2.id_comprobante = com2.id
					where com.estado = 1
					and cde.estado = 1
					and com.tipo = 1
					and com2.estado = 1
					and cde.id = tfr.id_factura
					and cde.id != tfr.id_recibo
					group by com.id
					having saldo >= 0');
				$facturas->execute();
				$facturas = $facturas->fetchAll(\PDO::FETCH_ASSOC);
				$filtro = [];
				foreach($facturas as $factura ) {
					array_push($filtro,$factura['id']);
				}
				$registros = $registros->whereIn('id',$filtro);
				break;
			default:
				break;
		}

		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(fecha,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere(DB::raw("DATE_FORMAT(fecha_vto,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('razon_social','like','%'.$value.'%')
							->orWhere('domicilio','like','%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhere('total','like','%'.$value.'%')
							->orWhereIn('id_usuario',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_cliente',function($d) use ($value){
								$d->select('id')->from('clientes')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_tipo_comprobante',function($d) use ($value){
								$d->select('id')->from('tra_tipos_comprobante')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('id_tipo_transaccion',function($d) use ($value){
								$d->select('id')->from('tra_tipos_transaccion')->where('nombre_tipo','like','%'.$value.'%');
							})
							->orWhereIn('id_tipo_responsable',function($d) use ($value){
								$d->select('id')->from('tra_tipos_responsable')->where('nombre','like','%'.$value.'%');
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

	public function getFacturasCuentaCorriente($request,$response, $args){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$recibo = $request->getParam('recibo',1);

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$id_sucursal = auth::sucursal()->id;
		$registros = Comprobante::with(
			'detalles.facturas.factura_detalle.comprobante',
			'detalles.facturas.movimiento.tipo',
			'detalles.recibos.recibo_detalle.comprobante',
			'detalles.recibos.movimiento.tipo',
			'individuo',
			'cliente',
			'tipoComprobante',
			'tipoResponsable',
			'tipoDocumento',
			'errores')
		->where('cuentacorriente', $args['id']);

		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(fecha,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere(DB::raw("DATE_FORMAT(fecha_vto,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('razon_social','like','%'.$value.'%')
							->orWhere('domicilio','like','%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhere('total','like','%'.$value.'%')
							->orWhereIn('id_usuario',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_cliente',function($d) use ($value){
								$d->select('id')->from('clientes')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_tipo_comprobante',function($d) use ($value){
								$d->select('id')->from('tra_tipos_comprobante')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('id_tipo_responsable',function($d) use ($value){
								$d->select('id')->from('tra_tipos_responsable')->where('nombre','like','%'.$value.'%');
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

	public function getFacturasCompra($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$recibo = $request->getParam('recibo',1);

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$id_sucursal = auth::sucursal()->id;
		$registros = Comprobante::with(
			'detalles.facturas.factura_detalle.comprobante',
			'detalles.facturas.movimiento.tipo',
			'detalles.recibos.recibo_detalle.comprobante',
			'detalles.recibos.movimiento.tipo',
			'tipoTransaccion',
			'individuo',
			'cliente',
			'tipoComprobante',
			'tipoResponsable',
			'tipoDocumento')
		->where('estado',1)
		->where('id_sucursal',$id_sucursal)
		->where('tipo',2);

		if($recibo==0){
			$recibos = ComprobanteDetalle::where('tra_comprobante_detalles.estado',1)
				->rightJoin('tra_factura_recibo','tra_comprobante_detalles.id','=','tra_factura_recibo.id_recibo')
				->groupBy('tra_comprobante_detalles.id_comprobante')->pluck('tra_comprobante_detalles.id_comprobante')->toArray();
			$registros = $registros->whereNotIn('id',$recibos);
		}
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(fecha,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere(DB::raw("DATE_FORMAT(fecha_vto,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('razon_social','like','%'.$value.'%')
							->orWhere('domicilio','like','%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhere('total','like','%'.$value.'%')
							->orWhereIn('id_usuario',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_cliente',function($d) use ($value){
								$d->select('id')->from('clientes')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_tipo_comprobante',function($d) use ($value){
								$d->select('id')->from('tra_tipos_comprobante')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('id_tipo_transaccion',function($d) use ($value){
								$d->select('id')->from('tra_tipos_transaccion')->where('nombre_tipo','like','%'.$value.'%');
							})
							->orWhereIn('id_tipo_responsable',function($d) use ($value){
								$d->select('id')->from('tra_tipos_responsable')->where('nombre','like','%'.$value.'%');
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

	public function cobrarFactura($request,$response,$args)
	{
		$facturas = Comprobante::where('id',$args['id'])->where('estado',1)->get();
		$detalles = ComprobanteDetalle::where('estado',1)->where('id_comprobante',$args['id'])->pluck('id')->toArray();
		$recibos = Comprobante::whereIn('id',function($subQuery) use ($detalles){
			$subQuery->select('id_comprobante')->from('tra_comprobante_detalles')
				->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_recibo')
				->whereIn('tra_factura_recibo.id_factura',$detalles);
		})->get();

		if($facturas->count()>0){
			$recibo = $request->getQueryParam('recibo',0);
			return $this->container->view->render($response, 'admin_views/caja/comprobante/movimiento.twig',[
				'sucursal' => Sucursal::where('id',$facturas[0]->id_sucursal)->first(),
				'bancos' => Banco::all(),
				'facturas'=> $facturas,
				'recibos' => $recibos,
				'recibo' => $recibo,
			]);
		} else {
			$this->flash->addMessage('error', 'No existe la factura seleccionada.');
			return $response->withRedirect($this->router->pathFor('comprobante.venta.tablero'));
		}
	}

	public function cobrarFacturaEfectivo($request,$response,$args)
	{
		$id_usuario = auth::empleado()->id_usuario;
		$id_factura = $args['id'];
		$tipo_factura = 0;

		try
		{
			DB::beginTransaction();
			$factura = Comprobante::where('id',$id_factura)->first();
			$id_sucursal = $factura->id_sucursal;
			$descripcion_tmp = "Cancelado directamente";
			if($factura->cuentacorriente != 0)
			{
				$factura->estado = 1;
				$factura->save();
			}
			$dt = new \DateTime();
			$id_movimiento = 0;
			$tipo_ioe = $factura->tipo - 1; //0: INGRESO | 1: EGRESO
			$tipo_factura = $factura->tipo - 1;

			$id_movimiento = Movimiento::create([
				'fecha_cobro' => date('Y-m-d'),
				'id_tipo_movimiento' => 1,
				'monto' => $factura->total,
				'id_sucursal' => $id_sucursal,
				'descripcion' => $descripcion_tmp,
				'id_usuario'=> $id_usuario,
				'tipo_ioe' => $tipo_ioe, //0: INGRESO | 1: EGRESO
			])->id;

			$comprobante = Comprobante::create([
				'id_cliente' => $factura->id_cliente,
				'id_tipo_comprobante' => 99,//RECIBO X
				'id_tipo_responsable' => $factura->id_tipo_responsable,
				'id_tipo_condicion_iva' => $factura->id_tipo_condicion_iva,
				'id_tipo_documento' => $factura->id_tipo_documento,
				'documento_numero' => $factura->documento_numero,
				'razon_social' => $factura->razon_social,
				'domicilio' => $factura->domicilio,
				'email' => $factura->email,
				'fecha' => date('Y-m-d'),
				'observaciones' => $descripcion_tmp,
				'total' =>  $factura->total,
				'bonificacion' => 0,
				'impuesto' => 0,
				'id_usuario' => $id_usuario,
				'id_sucursal' => $id_sucursal,
				'tipo' => $factura->tipo, //1 Venta 2 Compra
				'cuentacorriente' => $factura->cuentacorriente,
			]);

			$detalle = ComprobanteDetalle::create([
				'id_comprobante' => $comprobante->id,
				'id_tipo_condicion_iva' => 3,
				'codigo' => '',
				'descripcion' => $factura->id.' - '.$factura->tipoComprobante->nombre.' Total: $'.$factura->total,
				'importe' =>  $factura->total,
				'cantidad' => 1,
				'bonificado' => 0,
				'subtotal' =>  $factura->total,
				'id_usuario' => $id_usuario,
				'orden' => 1,
			]);
			foreach($factura->detalles as $value) 
			{
				FacturaRecibo::create([
					'id_factura'=> $value->id,
					'id_recibo'=> $detalle->id,
					'id_movimiento'=> $id_movimiento,
					'id_usuario' => $id_usuario,
					'cantidad' => 1,
				]);
			}
			DB::commit();
		}
		catch (\PDOException $e)
		{
			$errores = $e->getMessage();
	    	$this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
	    	DB::rollBack();
	    	return $response->withRedirect($this->router->pathFor('comprobante.factura.cobrar',[
				'id'=>$id_factura,
			]));
		}
	  	$this->flash->addMessage('info', 'Factura pagada en efectivo.');
	  	if($tipo_factura == 0)
		{
			return $response->withRedirect($this->router->pathFor('comprobante.venta.tablero'));
		}
		else
		{
			return $response->withRedirect($this->router->pathFor('comprobante.compra.tablero'));
		}
	}



	public function cobrarFacturaCuentaCorriente($request,$response,$args)
	{
		$facturas = Comprobante::where('id',$args['id'])->get();
		$detalles = ComprobanteDetalle::where('estado',1)->where('id_comprobante',$args['id'])->pluck('id')->toArray();
		$recibos = Comprobante::whereIn('id',function($subQuery) use ($detalles){
			$subQuery->select('id_comprobante')->from('tra_comprobante_detalles')
				->join('tra_factura_recibo', 'tra_comprobante_detalles.id', '=', 'tra_factura_recibo.id_recibo')
				->whereIn('tra_factura_recibo.id_factura',$detalles);
		})->get();

		if($facturas->count()>0)
		{
			$recibo = $request->getQueryParam('recibo',0);
			return $this->container->view->render($response, 'admin_views/caja/comprobante/movimiento.twig',[
				'sucursal' => Sucursal::where('id',$facturas[0]->id_sucursal)->first(),
				'bancos' => Banco::all(),
				'facturas'=> $facturas,
				'recibos' => $recibos,
				'recibo' => $recibo,
			]);
		}
		else
		{
			$this->flash->addMessage('error', 'No existe la factura seleccionada.');
			return $response->withRedirect($this->router->pathFor('comprobante.venta.tablero'));
		}
	}

	public function agregarMovimientoFactura($request,$response,$args)
	{
		$id_usuario = auth::empleado()->id_usuario;
		$tipoMovimiento = $request->getParam('id_tipo_movimiento');
		$id_factura = $args['id'];

		$cheque_numero = $request->getParam('cheque_numero');
		$cheque_banco = $request->getParam('cheque_banco');
		$id_banco = $request->getParam('id_banco');
		$cheque_cuenta = $request->getParam('cheque_cuenta');
		$cheque_fecha = $request->getParam('cheque_fecha');
		$cheque_cobrado = $request->getParam('cheque_cobrado');
		$cheque_fecha_vto = $request->getParam('cheque_fecha_vto');
		$cheque_receptor = $request->getParam('cheque_receptor');

		$tarjeta_tipo = $request->getParam('tarjeta_tipo');
		$tarjeta_numero = $request->getParam('tarjeta_numero');
		$tarjeta_titular = $request->getParam('tarjeta_titular');
		$tarjeta_cupon = $request->getParam('tarjeta_cupon');
		$tarjeta_cupon_fecha = $request->getParam('tarjeta_cupon_fecha');
		$tarjeta_cuotas = $request->getParam('tarjeta_cuotas');
		$tarjeta_adicional = $request->getParam('tarjeta_adicional');

		$tributo_numero = $request->getParam('tributo_numero');
		$tributo_nombre = $request->getParam('tributo_nombre');

		$documento_emisor = $request->getParam('documento_emisor');
		$documento_receptor = $request->getParam('documento_receptor');
		$documento_fecha_emision = $request->getParam('documento_fecha_emision');
		$documento_fecha_cobro = $request->getParam('documento_fecha_cobro');
		$documento_dias = $request->getParam('documento_dias');
		$documento_cantidad = $request->getParam('documento_cantidad');
		$documento_monto = $request->getParam('documento_monto');
		$documento_periodo = $request->getParam('documento_periodo');

		$movimiento_descripcion = $request->getParam('movimiento_descripcion');
		$movimiento_monto = $request->getParam('movimiento_monto');

		try
		{
			DB::beginTransaction();
			$factura = Comprobante::where('id',$id_factura)->first();
			$id_sucursal = $factura->id_sucursal;
			if($factura->cuentacorriente != 0){
				$factura->estado = 1;
				$factura->save();
			}
			if($movimiento_monto>$factura->total - $factura->pagado){
	    	$this->flash->addMessage('error', 'El monto del Recibo a generar NO debe ser mayor al monto restante de la factura.');
			}
			$dt = new \DateTime();
			$id_movimiento = 0;
			$tipo_ioe = $factura->tipo - 1; //0: INGRESO | 1: EGRESO
			switch ($tipoMovimiento) {
				case 1://Contado
					$id_movimiento = Movimiento::create([
						'fecha_cobro' => date('Y-m-d'),
						'id_tipo_movimiento' => $tipoMovimiento,
						'monto' => $movimiento_monto,
						'id_sucursal' => $id_sucursal,
						'descripcion' => $movimiento_descripcion,
						'id_usuario'=> $id_usuario,
						'tipo_ioe' => $tipo_ioe, //0: INGRESO | 1: EGRESO
					])->id;
					break;
				case 2://Cheque
					$id_movimiento = Movimiento::create([
						'cheque_numero' => $cheque_numero,
						'cheque_banco' => $cheque_banco,
						'id_banco' => $id_banco,
						'cheque_cuenta' => $cheque_cuenta,
						'fecha' => $dt->createFromFormat('d/m/Y',$cheque_fecha),
						'cheque_vto' => $dt->createFromFormat('d/m/Y',$cheque_fecha_vto),
						'id_tipo_movimiento' => $tipoMovimiento,
						'monto' => $movimiento_monto,
						'id_sucursal' => $id_sucursal,
						'descripcion' => $movimiento_descripcion,
						'id_usuario'=> $id_usuario,
						'tipo_ioe'=> $tipo_ioe, //0: INGRESO | 1: EGRESO
						'estado' => 0
					])->id;

					if($tipo_ioe == 0){

						$obligacion = Obligacion::create([
							'id_sucursal' => $id_sucursal,
							'id_movimiento' => $id_movimiento,
							'emisor' => $cheque_cuenta,
							'receptor' => $cheque_receptor,
							'importe' => $movimiento_monto,
							'fecha_inicio' => $dt->createFromFormat('d/m/Y',$cheque_fecha),
							'fecha_aproxfin' => $dt->createFromFormat('d/m/Y',$cheque_fecha_vto),
							'id_usuario'=> $id_usuario,
						]);
		  			$this->flash->addMessage('warning', 'Los CHEQUES generan OBLIGACIONES. Por favor verifique los mismos.');
		  		}
		  		else
		  		{
		  			Movimiento::where('id',$id_movimiento)->update(['fecha_cobro' => date('Y-m-d')]);
		  		}
				break;
				case 3://Tarjeta
					$id_movimiento = Movimiento::create([
						'fecha' => $dt->createFromFormat('d/m/Y',$tarjeta_cupon),
						'fecha_cobro' => date('Y-m-d') ,
						'id_tipo_movimiento' => $tipoMovimiento,
						'tarjeta_numero' => $tarjeta_numero,
						'tarjeta_titular' => $tarjeta_titular,
						'tarjeta_cupon' => $tarjeta_cupon,
						'tarjeta_adicional' => $tarjeta_adicional,
						'tarjeta_cuota' => $tarjeta_cuotas,
						'monto' => $movimiento_monto,
						'id_sucursal' => $id_sucursal,
						'descripcion' => $movimiento_descripcion,
						'id_usuario'=> $id_usuario,
						'tipo_ioe'=> $tipo_ioe, //0: INGRESO | 1: EGRESO
					])->id;
					if($tipo_ioe == 0){
						$total = $movimiento_monto;
						if($tarjeta_tipo==0){
							$observaciones = 'Tarjeta Credito '.$movimiento_descripcion;
						} else {
							$observaciones = 'Tarjeta Debito '.$movimiento_descripcion;
						}
						$id_sucursal_origen = $id_sucursal;
						$id_sucursal_destino = 4; //Banco Rio
						$sucursal_origen = Sucursal::where('id',$id_sucursal_origen)->first();
						$sucursal_destino = Sucursal::where('id',$id_sucursal_destino)->first();
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
						$origen_id_movimiento = Movimiento::create([
							'fecha_cobro' => date('Y-m-d'),
							'id_tipo_movimiento' => 6,
							'monto' => $total,
							'id_sucursal' => $id_sucursal_origen,
							'descripcion' => $observaciones,
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
							'id_movimiento'=> $origen_id_movimiento,
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
						$destino_id_movimiento = Movimiento::create([
							'fecha_cobro' => date('Y-m-d'),
							'id_tipo_movimiento' => 6,
							'monto' => $total,
							'id_sucursal' => $id_sucursal_destino,
							'descripcion' => $observaciones,
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
							'id_movimiento'=> $destino_id_movimiento,
							'id_usuario' => $id_usuario,
							'cantidad' => 1,
						]);

						SucursalTransferencia::create([
							'id_sucursal_origen'=>$id_sucursal_origen,
							'id_sucursal_destino'=>$id_sucursal_destino,
							'id_comprobante_origen'=>$id_comprobante_origen,
							'id_comprobante_destino'=>$id_comprobante_destino,
							'total'=>$total,
							'responsable' => 'INMEDIATO',
							'observaciones' => $observaciones,
							'id_usuario' => $id_usuario,
						]);

						$this->flash->addMessage('warning', 'Los ingresos por TARJETAS van a crear COMPROBANTES NEGATIVOS en esta Sucursal y COMPROBANTES POSITIVOS en la Caja del BANCO.');
					}
					break;
				case 4://Tributo
					$id_movimiento = Movimiento::create([
						'fecha' => $dt->createFromFormat('d/m/Y',$tributo_fecha),
						'fecha_cobro' => date('Y-m-d'),
						'id_tipo_movimiento' => $tipoMovimiento,
						'tributo_numero' => $tributo_numero,
						'tributo_nombre' => $tributo_nombre,
						'monto' => $movimiento_monto,
						'id_sucursal' => $id_sucursal,
						'descripcion' => $movimiento_descripcion,
						'id_usuario'=> $id_usuario,
						'tipo_ioe'=> $tipo_ioe, //0: INGRESO | 1: EGRESO
					])->id;
					break;
				case 5://Documento
					$inicial = $dt->createFromFormat('d/m/Y',$documento_fecha_emision);
					$final = $dt->createFromFormat('d/m/Y',$documento_fecha_cobro);
					for ($i=0; $i < $documento_cantidad; $i++) {
						$documento_descripcion = $movimiento_descripcion . ' '.($i+1).'/'.$documento_cantidad;
						$id_movimiento = Movimiento::create([
							'fecha' => $inicial,
							'cheque_vto' => $final,
							'id_tipo_movimiento' => $tipoMovimiento,
							'documento_emisor' => $documento_emisor,
							'documento_receptor' => $documento_receptor,
							'monto' => $documento_monto,
							'id_sucursal' => $id_sucursal,
							'descripcion' => $documento_descripcion,
							'id_usuario'=> $id_usuario,
							'tipo_ioe'=> $tipo_ioe, //0: INGRESO | 1: EGRESO
							'estado' => 0,
						])->id;
						$obligacion = Obligacion::create([
							'id_sucursal' => $id_sucursal,
							'id_movimiento' => $id_movimiento,
							'emisor' => $documento_emisor,
							'receptor' => $documento_receptor,
							'importe' => $documento_monto,
							'fecha_inicio' => $inicial,
							'fecha_aproxfin' => $final,
							'id_usuario'=> $id_usuario,
						]);
						switch ($documento_periodo) {
							case 1: //SEMANAL
								$inicial = $inicial->modify('+1 Week');
								$final = $final->modify('+1 Week');
								break;
							case 2; //MENSUAL
								$inicial = $inicial->modify('+1 Month');
								$final = $final->modify('+1 Month');
								break;
						}

						$comprobante = Comprobante::create([
							'id_cliente' => $factura->id_cliente,
							'id_tipo_comprobante' => 99,//RECIBO X
							'id_tipo_responsable' => $factura->id_tipo_responsable,
							'id_tipo_condicion_iva' => $factura->id_tipo_condicion_iva,
							'id_tipo_documento' => $factura->id_tipo_documento,
							'documento_numero' => $factura->documento_numero,
							'razon_social' => $factura->razon_social,
							'domicilio' => $factura->domicilio,
							'email' => $factura->email,
							'fecha' => date('Y-m-d'),
							'observaciones' => $documento_descripcion,
							'total' => $documento_monto,
							'bonificacion' => 0,
							'impuesto' => 0,
							'id_usuario' => $id_usuario,
							'id_sucursal' => $id_sucursal,
							'tipo' => $factura->tipo, //1 Venta 2 Compra
							'cuentacorriente' => $factura->cuentacorriente,
						]);

						$detalle = ComprobanteDetalle::create([
							'id_comprobante' => $comprobante->id,
							'codigo' => '',
							'descripcion' => $factura->id.' - '.$factura->tipoComprobante->nombre.' Total: $'.$factura->total,
							'importe' => $documento_monto,
							'cantidad' => 1,
							'bonificado' => 0,
							'subtotal' => $documento_monto,
							'id_usuario' => $id_usuario,
							'orden' => 1,
						]);
						foreach ( $factura->detalles as $value) {
							FacturaRecibo::create([
								'id_factura'=> $value->id,
								'id_recibo'=> $detalle->id,
								'id_movimiento'=> $id_movimiento,
								'id_usuario' => $id_usuario,
								'cantidad' => 1,
							]);
						}
					}


					if($tipo_ioe == 0){

		  			$this->flash->addMessage('warning', 'Los DOCUMENTOS generan OBLIGACIONES. Por favor verifique los mismos.');
		  		}
				break;
			}
			switch ($tipoMovimiento) {
				case 1:
				case 2:
				case 3:
					$comprobante = Comprobante::create([
						'id_cliente' => $factura->id_cliente,
						'id_tipo_comprobante' => 99,//RECIBO X
						'id_tipo_responsable' => $factura->id_tipo_responsable,
						'id_tipo_condicion_iva' => $factura->id_tipo_condicion_iva,
						'id_tipo_documento' => $factura->id_tipo_documento,
						'documento_numero' => $factura->documento_numero,
						'razon_social' => $factura->razon_social,
						'domicilio' => $factura->domicilio,
						'email' => $factura->email,
						'fecha' => date('Y-m-d'),
						'observaciones' => $movimiento_descripcion,
						'total' => $movimiento_monto,
						'bonificacion' => 0,
						'impuesto' => 0,
						'id_usuario' => $id_usuario,
						'id_sucursal' => $id_sucursal,
						'tipo' => $factura->tipo, //1 Venta 2 Compra
						'cuentacorriente' => $factura->cuentacorriente,
					]);

					$detalle = ComprobanteDetalle::create([
						'id_comprobante' => $comprobante->id,
						'id_tipo_condicion_iva' => 3,
						'codigo' => '',
						'descripcion' => $factura->id.' - '.$factura->tipoComprobante->nombre.' Total: $'.$factura->total,
						'importe' => $movimiento_monto,
						'cantidad' => 1,
						'bonificado' => 0,
						'subtotal' => $movimiento_monto,
						'id_usuario' => $id_usuario,
						'orden' => 1,
					]);
					foreach ( $factura->detalles as $value) {
						FacturaRecibo::create([
							'id_factura'=> $value->id,
							'id_recibo'=> $detalle->id,
							'id_movimiento'=> $id_movimiento,
							'id_usuario' => $id_usuario,
							'cantidad' => 1,
						]);
					}
					break;
				case 4:
					$comprobante = Comprobante::create([
						'id_cliente' => $factura->id_cliente,
						'id_tipo_comprobante' => 99,//RECIBO X
						'id_tipo_responsable' => $factura->id_tipo_responsable,
						'id_tipo_condicion_iva' => $factura->id_tipo_condicion_iva,
						'id_tipo_documento' => $factura->id_tipo_documento,
						'documento_numero' => $factura->documento_numero,
						'razon_social' => $factura->razon_social,
						'domicilio' => $factura->domicilio,
						'email' => $factura->email,
						'fecha' => date('Y-m-d'),
						'observaciones' => $movimiento_descripcion,
						'total' => 0,
						'bonificacion' => 0,
						'impuesto' => $movimiento_monto,
						'id_usuario' => $id_usuario,
						'id_sucursal' => $id_sucursal,
						'tipo' => $factura->tipo, //1 Venta 2 Compra
						'cuentacorriente' => $factura->cuentacorriente,
					]);

					$detalle = ComprobanteDetalle::create([
						'id_comprobante' => $comprobante->id,
						'codigo' => '',
						'descripcion' =>  $factura->id.' - Recibo Tributo.',
						'importe' => $movimiento_monto,
						'cantidad' => 1,
						'bonificado' => 0,
						'subtotal' => $movimiento_monto,
						'id_usuario' => $id_usuario,
						'orden' => 1,
					]);

					foreach ( $factura->detalles as $value) {
						FacturaRecibo::create([
							'id_factura'=> $value->id,
							'id_recibo'=> $detalle->id,
							'id_movimiento'=> $id_movimiento,
							'id_usuario' => $id_usuario,
							'cantidad' => 1,
						]);
					}
					break;
				case 6: //NOTA DE CREDITO
					if($factura->id_tipo_comprobante == 1){
						$id_tipo_comprobante = 3; // A
					} else if($factura->id_tipo_comprobante == 6){
						$id_tipo_comprobante = 8; // B
					} else if($factura->id_tipo_comprobante == 11){
						$id_tipo_comprobante = 13; // C
					}
					$comprobante = Comprobante::create([
						'id_cliente' => $factura->id_cliente,
						'id_tipo_comprobante' => $id_tipo_comprobante,
						'id_tipo_responsable' => $factura->id_tipo_responsable,
						'id_tipo_condicion_iva' => $factura->id_tipo_condicion_iva,
						'id_tipo_documento' => $factura->id_tipo_documento,
						'documento_numero' => $factura->documento_numero,
						'razon_social' => $factura->razon_social,
						'domicilio' => $factura->domicilio,
						'email' => $factura->email,
						'fecha' => date('Y-m-d'),
						'observaciones' => $movimiento_descripcion,
						'total' => $movimiento_monto,
						'bonificacion' => 0,
						'impuesto' => 0,
						'id_usuario' => $id_usuario,
						'id_sucursal' => $id_sucursal,
						'tipo' => $factura->tipo, //1 Venta 2 Compra
						'cuentacorriente' => $factura->cuentacorriente,
					]);

					$detalle = ComprobanteDetalle::create([
						'id_comprobante' => $comprobante->id,
						'id_tipo_condicion_iva' => 3,
						'codigo' => '',
						'descripcion' => $factura->id.' - '.$factura->tipoComprobante->nombre.' Total: $'.$factura->total,
						'importe' => $movimiento_monto,
						'cantidad' => 1,
						'bonificado' => 0,
						'subtotal' => $movimiento_monto,
						'id_usuario' => $id_usuario,
						'orden' => 1,
					]);
					foreach ( $factura->detalles as $value) {
						FacturaRecibo::create([
							'id_factura'=> $value->id,
							'id_recibo'=> $detalle->id,
							'id_movimiento'=> $id_movimiento,
							'id_usuario' => $id_usuario,
							'cantidad' => 1,
						]);
					}
					break;
			}
			DB::commit();
		}
		catch (\PDOException $e)
		{
			$errores = $e->getMessage();
	    	$this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
	    	DB::rollBack();
	    	return $response->withRedirect($this->router->pathFor('comprobante.factura.cobrar',[
				'id'=>$id_factura,
			]));
		}
	  	$this->flash->addMessage('info', 'Recibo Generado');
		return $response->withRedirect($this->router->pathFor('comprobante.factura.cobrar',[
			'id'=>$id_factura,
		]));
	}

	public function eliminarMovimientoFactura($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_recibo = $args['id_recibo'];
		$id_factura = $args['id_factura'];
		$comprobante = Comprobante::where('id',$id_recibo)->where('id_usuario',$id_usuario)->first();
		if(!$comprobante){
			return $response->withStatus(409)->withJson([
				'message' => 'No puedes eliminar un Recibo que no lo Generaste. ',
			]);
		}
		try{
			DB::beginTransaction();
			Comprobante::where('id',$id_recibo)->update([
				'estado'=>0,
			]);
			ComprobanteDetalle::where('id_comprobante',$id_recibo)->update([
				'estado'=>0,
			]);

			$detalles = ComprobanteDetalle::where('id_comprobante',$id_recibo)->pluck('id')->toArray();
			FacturaRecibo::whereIn('id_recibo',$detalles)->update([
				'estado'=>0,
			]);

			$movimientos = FacturaRecibo::whereIn('id_recibo',$detalles)->pluck('id_movimiento')->toArray();
			Movimiento::whereIn('id',$movimientos)->update([
				'estado'=>0,
			]);

			Obligacion::whereIn('id_movimiento',$movimientos)->update([
				'estado'=>0,
				'id_usuario' => $id_usuario,
			]);

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    $this->flash->addMessage('error', 'Ocurrio un error al borrar los datos. '.$errores);
	    DB::rollBack();
			return $response->withStatus(409)->withJson([
				'message' => 'Ocurrio un error al borrar los datos. '.$errores,
			]);
		}
		return $response->withStatus(200)->withJson([
			'message' => 'Recibo '.$id_recibo.' Eliminado.',
		]);
	}

	public function eliminarFactura($request,$response,$args){
		$id_factura = $args['id'];
		$comprobante = Comprobante::where('id',$id_factura)->first();
		$tipo = $comprobante->tipo;
		if($comprobante->cae!=null or $comprobante->cae != ''){
		  $this->flash->addMessage('error', 'La factura posee CAE por lo que no puede ser eliminada.');
		} else {
			try{
				DB::beginTransaction();
				Comprobante::where('id',$id_factura)->update([
					'estado'=>0,
				]);
				ComprobanteDetalle::where('id_comprobante',$id_factura)->update([
					'estado'=>0,
				]);

				$detalles = ComprobanteDetalle::where('id_comprobante',$id_factura)->pluck('id')->toArray();
				FacturaRecibo::whereIn('id_factura',$detalles)->update([
					'estado'=>0,
				]);

				$movimientos = FacturaRecibo::whereIn('id_factura',$detalles)->pluck('id_movimiento')->toArray();
				Movimiento::whereIn('id',$movimientos)->update([
					'estado'=>0,
				]);

				Obligacion::whereIn('id_movimiento',$movimientos)->update([
					'estado'=>0,
					'id_usuario' => $id_usuario,
				]);

				$detalles = FacturaRecibo::whereIn('id_factura',$detalles)->pluck('id_recibo')->toArray();
				if($detalles){
					ComprobanteDetalle::where('id',$detalles)->update([
					'estado'=>0,
					]);

					$recibos = ComprobanteDetalle::whereIn('id',$detalles)->pluck('id_comprobante')->toArray();
					Comprobante::whereIn('id',$recibos)->update([
						'estado'=>0,
					]);
				}
				DB::commit();
			} catch (\PDOException $e) {
				$errores = $e->getMessage();
		    $this->flash->addMessage('error', 'Ocurrio un error al eliminar la Factura. '.$errores);
		    DB::rollBack();
		    return $response->withRedirect($this->router->pathFor('comprobante.factura.cobrar',[
					'id'=>$id_factura,
				]));
			}
			$this->flash->addMessage('info', 'Factura '.$id_factura.' Eliminado.');
		}
		if($tipo){
			return $response->withRedirect($this->router->pathFor('comprobante.venta.tablero'));
		} else {
			return $response->withRedirect($this->router->pathFor('comprobante.compra.tablero'));
		}
	}

	public function pagarMovimientoFactura($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_recibo = $args['id_recibo'];
		$id_factura = $args['id_factura'];
		try{
			DB::beginTransaction();

			$detalles = ComprobanteDetalle::where('id_comprobante',$id_recibo)->pluck('id')->toArray();
			$movimientos = FacturaRecibo::whereIn('id_recibo',$detalles)->pluck('id_movimiento')->toArray();
			Movimiento::whereIn('id',$movimientos)->whereNull('fecha_cobro')->update([
				'fecha_cobro' => date('Y-m-d'),
				'estado' => 1,
			]);

			Obligacion::whereIn('id_movimiento',$movimientos)->update([
				'fecha_fin' => date('Y-m-d'),
				'id_usuario' => $id_usuario,
				'estado'=> 2,
			]);

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
	    DB::rollBack();
	    return $response->withRedirect($this->router->pathFor('comprobante.factura.cobrar',[
				'id'=>$id_factura,
			]));
		}
		$this->flash->addMessage('info', 'Recibo pagado.');
		return $response->withRedirect($this->router->pathFor('comprobante.factura.cobrar',[
			'id'=>$id_factura,
		]));
	}

	public function consultaPadron($request,$response,$args){
		$afip = new \Afip(WSS::cert());
		$cuit = $request->getQueryParam('cuit');
		$taxpayer_details = $afip->RegisterScopeFour->GetTaxpayerDetails($cuit); //Devuelve los datos del contribuyente correspondiente al identificador 20111111111
		//$taxpayer_details = $afip->RegisterScopeFour->GetServerStatus();
		return $response->withStatus(200)->withJson($taxpayer_details);
	}

	public function errorComprobante($request,$response,$args){
		$id_comprobante = $args['id'];
		Comprobante::where('id',$id_comprobante)->update([
			'reproceso' => 0,
			'id_usuario' => auth::individuo()->id_usuario,
		]);

		return $response->withRedirect($this->router->pathFor('comprobante.venta.tablero'));
	}

	public function imprimir($request,$response,$args){
		$id_comprobante = $args['id'];
		$comprobante = Comprobante::where('id',$id_comprobante)->first();

		$input = __DIR__ .'/../../../public/reportes/comprobante.jasper';
		$output = __DIR__ .'/../../../public/reportes/'.uniqid();
		$ext = 'pdf';
		$options = [
		    'format' => [$ext],
		    'locale' => 'es_AR',
		    'params' => [
		    	'id_comprobante'=>$id_comprobante,
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

		$filename =$id_comprobante.'-Comprobante-'.str_replace(' ', '_', $comprobante->razon_social).'-'.$comprobante->documento_numero;
		header('Content-Description: application/pdf');
    header('Content-Type: application/pdf');
    header('Content-Disposition:; filename=' . $filename . '.' . $ext);
    readfile($output . '.' . $ext);
    unlink($output. '.'  . $ext);
    flush();

	}

	public function estadisticas($request,$response){
		$id_sucursal = auth::sucursal()->id;
		$recibo_detalle = FacturaRecibo::where('estado',1)->pluck('id_recibo')->toArray();
		$recibos = ComprobanteDetalle::where('estado',1)->whereIn('id',$recibo_detalle)->pluck('id_comprobante')->toArray();
		$pagosVenta = Comprobante::where('estado',1)->whereIn('id',$recibos)->where('tipo',1)->where('id_sucursal',$id_sucursal)->distinct('id');
		$ventas = Comprobante::where('estado',1)->whereNotIn('id',$pagosVenta->pluck('id')->toArray())->where('tipo',1)->where('id_sucursal',$id_sucursal)->distinct('id');

		$graficoTipoComprobanteVenta = TipoComprobante::select(
				'tra_comprobantes.id',
				DB::raw("IF(tra_tipos_comprobante.id=99,'RECIBO VENTA',tra_tipos_comprobante.nombre) as nombre"),
				DB::raw('sum(tra_comprobantes.total) as total '))
			->join('tra_comprobantes','tra_comprobantes.id_tipo_comprobante','tra_tipos_comprobante.id')
			->where('tra_comprobantes.estado',1)
			->whereNotIn('tra_comprobantes.id',$pagosVenta->pluck('id')->toArray())
			->where('tra_comprobantes.tipo',1)
			->where('tra_comprobantes.id_sucursal',$id_sucursal)
			->distinct('tra_comprobantes.id')
			->groupBy('tra_tipos_comprobante.id')
			->get();

		$graficoTipoTransaccionVenta = Comprobante::select(
				'tra_comprobantes.id_tipo_transaccion',
				DB::raw("IFNULL(tra_tipos_transaccion.nombre_tipo,'otros') as nombre"),
				DB::raw('sum(tra_comprobantes.total) as total '))
			->leftJoin('tra_tipos_transaccion','tra_comprobantes.id_tipo_transaccion','tra_tipos_transaccion.id')
			->where('tra_comprobantes.estado',1)
			->whereNotIn('tra_comprobantes.id',$pagosVenta->pluck('id')->toArray())
			->where('tra_comprobantes.tipo',1)
			->where('tra_comprobantes.id_sucursal',$id_sucursal)
			->distinct('tra_comprobantes.id')
			->groupBy('tra_comprobantes.id_tipo_transaccion')
			->get();

		$graficoVenta = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereNotIn('id',$pagosVenta->pluck('id')->toArray())
			->where('tipo',1)
			->where('id_sucursal',$id_sucursal)
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();

		$graficoPagoVenta = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereIn('id',$recibos)
			->where('tipo',1)
			->where('id_sucursal',$id_sucursal)
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();

		$proveedoresPago = Comprobante::where('estado',1)->where('tipo',2)->where('id_sucursal',$id_sucursal)->groupBy('id_proveedor')->get()->count();
		$recibo_detalle = FacturaRecibo::where('estado',1)->pluck('id_recibo')->toArray();
		$recibos = ComprobanteDetalle::where('estado',1)->whereIn('id',$recibo_detalle)->pluck('id_comprobante')->toArray();
		$pagosCompra = Comprobante::where('estado',1)->whereIn('id',$recibos)->where('tipo',2)->where('id_sucursal',$id_sucursal)->distinct('id');
		$compras = Comprobante::where('estado',1)->whereNotIn('id',$pagosCompra->pluck('id')->toArray())->where('tipo',2)->where('id_sucursal',$id_sucursal)->distinct('id');

		$graficoTipoComprobanteCompra = TipoComprobante::select(
				'tra_comprobantes.id',
				DB::raw("IF(tra_tipos_comprobante.id=99,'RECIBO VENTA',tra_tipos_comprobante.nombre) as nombre"),
				DB::raw('sum(tra_comprobantes.total) as total '))
			->join('tra_comprobantes','tra_comprobantes.id_tipo_comprobante','tra_tipos_comprobante.id')
			->where('tra_comprobantes.estado',1)
			->whereNotIn('tra_comprobantes.id',$pagosCompra->pluck('id')->toArray())
			->where('tra_comprobantes.tipo',2)
			->where('tra_comprobantes.id_sucursal',$id_sucursal)
			->distinct('tra_comprobantes.id')
			->groupBy('tra_tipos_comprobante.id')
			->get();

		$graficoTipoTransaccionCompra = Comprobante::select(
				'tra_comprobantes.id_tipo_transaccion',
				DB::raw("IFNULL(tra_tipos_transaccion.nombre_tipo,'otros') as nombre"),
				DB::raw('sum(tra_comprobantes.total) as total '))
			->leftJoin('tra_tipos_transaccion','tra_comprobantes.id_tipo_transaccion','tra_tipos_transaccion.id')
			->where('tra_comprobantes.estado',1)
			->whereNotIn('tra_comprobantes.id',$pagosCompra->pluck('id')->toArray())
			->where('tra_comprobantes.tipo',2)
			->where('tra_comprobantes.id_sucursal',$id_sucursal)
			->distinct('tra_comprobantes.id')
			->groupBy('tra_comprobantes.id_tipo_transaccion')
			->get();

		$graficoCompra = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereNotIn('id',$pagosCompra->pluck('id')->toArray())
			->where('tipo',2)
			->where('id_sucursal',$id_sucursal)
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();

		$graficoPagoCompra = Comprobante::select(DB::raw('@rownum := @rownum + 1 as id'),DB::raw('date_format(created_at,"%d/%m/%Y") as dia'),DB::raw('sum(total) as total'))
			->join(DB::raw('(select @rownum := 0) r'),DB::raw('true'),DB::raw('true'))
			->where('estado',1)
			->whereIn('id',$recibos)
			->where('tipo',2)
			->where('id_sucursal',$id_sucursal)
			->distinct('id')
			->groupBy('dia')
			->orderBy('created_at','asc')
			->get();

		return $this->container->view->render($response, 'admin_views/caja/estadisticas.twig',[
			'sucursal'=>auth::sucursal(),
			'graficoTipoComprobanteVenta' => $graficoTipoComprobanteVenta,
			'graficoTipoTransaccionVenta' => $graficoTipoTransaccionVenta,
			'facturasCantidadVenta' => $ventas->get()->count(),
			'facturasTotalVenta' => $ventas->sum('total'),
			'recibosCantidadVenta' => $pagosVenta->get()->count(),
			'recibosTotalVenta' => $pagosVenta->sum('total'),
			'graficoVenta' => $graficoVenta,
			'graficoPagoVenta' => $graficoPagoVenta,
			'graficoTipoComprobanteCompra' => $graficoTipoComprobanteCompra,
			'graficoTipoTransaccionCompra' => $graficoTipoTransaccionCompra,
			'facturasCantidadCompra' => $compras->get()->count(),
			'facturasTotalCompra' => $compras->sum('total'),
			'recibosCantidadCompra' => $pagosCompra->get()->count(),
			'recibosTotalCompra' => $pagosCompra->sum('total'),
			'graficoCompra' => $graficoCompra,
			'graficoPagoCompra' => $graficoPagoCompra,
		]);
	}

}
