<?php

namespace App\Controllers;

use Slim\Views\Twig as View;
use App\Models\Precios\PreciosVehiculos;
use App\Models\Precios\PreciosVehiculosHistorial;
use App\Models\Precios\PreciosVehiculosMarcas;
use App\Models\Precios\PreciosNuevos;
use App\Models\Vehiculos\CreditosFijos;
use App\Models\Vehiculos\Vehiculo;

class RecolectorController extends Controller {


	public function index($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/precios/db_precios.twig', [
		]);
	}

	public function getApi($request, $response, array $args) {

		$search_terms = $request->getParam('q');


		$vehiculos = PreciosVehiculos::select();
		$vehiculos->where('nombre', 'LIKE', '%'.$search_terms.'%');

		$year = date('Y');
		$vehiculos->whereBetween('anio', [$year, $year++]);

		return $response->withStatus(200)->withJson([
			'args' => $request->getParams(),
			'search_terms'=> $search_terms,
			'items' => $vehiculos->get(),
		]); 
	}

	public function preciosnuevos($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/precios/nuevos.twig', [
			'nuevos'=>PreciosNuevos::all(),
			'fijos'=>CreditosFijos::orderBy('id', 'DESC')->first(),
		]);
	}

	public function verFinanciacion0km($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/precios/0km.twig', [
			'nuevo'=>PreciosNuevos::find($args['id']),
			'fijos'=>CreditosFijos::orderBy('id', 'DESC')->first(),
		]);
	}

	public function editarFinanciacion0km($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/precios/0km_editar.twig', [
			'nuevo'=>PreciosNuevos::find($args['id']),
		]);
	}

	public function verFinanciacion0km_print($request, $response, array $args) {
		if(isset($args['bonificacion']))
		{
			$bonificacion = $args['bonificacion'];
		}
		else
		{
			$bonificacion = 0;
		}
		
		return $this->container->view->render($response, 'admin_views/precios/0km_print.twig', [
			'nuevo'=>PreciosNuevos::find($args['id']),
			'fijos'=>CreditosFijos::orderBy('id', 'DESC')->first(),
			'bonificacion'=>$bonificacion,
		]);
	}

	public function recolector($request, $response, array $args) 
	{
		set_time_limit(2000);

		$marcas = PreciosVehiculosMarcas::all();
		foreach($marcas as $marca_v)
		{
			for($i = 0; $i <= 15; $i++) 
			{
				$marca = $marca_v->id;
				$anio = (2018 - $i);


				$client = new \GuzzleHttp\Client();
				$res = $client->request('POST', 'http://www.santanderrioseguros.com.ar/agents/common/optionsLovs.jsp?tpLov=cdModelo', [
				    'form_params' => [
				        'cdMarca' => $marca,
				        'anio' => $anio,
				    ]
				]);

				$string = $res->getBody()->getContents();
				$array = explode("\n", $string);
				$array_final = array();

				foreach($array as $elemento)
				{
					if(strlen($elemento) > 1)
					{
						$cadena = str_replace("P-UP", "PICKUP", $elemento);
						$array = explode("-", $cadena);
						$elemento = str_replace("</option>", "",  $array[0]);
						$elemento = str_replace('<option value=', "", $elemento);
						$final = explode(">", $elemento);
						sscanf($final[0], "'%d|%d'", $id, $precio);

						echo $id . "|" . $marca . "|" . $final[1] . " | " . $anio . " | $" . $precio;

						$existe = PreciosVehiculos::where('anio',$anio)
							->where('id_rio',$id)
							->where('marca', $marca)
							->get();

						if($existe->count() > 0)
						{
							/* se usa para arreglar el bug de la s10, soluciona2
							$modelo = $existe->first();
							if($modelo->nombre == "P")
							{
								$modelo->update(['nombre' => $final[1]]);
								echo "se actualizo una VW s10 a ".$final[1];
							}*/
							echo "| existe en DB | id ". $existe->first()->id ." <br>";
							// almacenar los precios en el historial
							PreciosVehiculosHistorial::Create([
								'id_vehiculo'=>$existe->first()->id,
								'mes'=>date('Y-m-d'),
								'precio'=>$precio,
							]);
						}
						else
						{
							echo "| NUEVO - Creado <br>";
							// Almacenar los precios en la base de datos:
							$vehiculo = PreciosVehiculos::Create([ 
								'id_rio'=>$id,
								'marca'=>$marca,
								'nombre'=>$final[1],
								'anio'=>$anio,
							])->id;

							PreciosVehiculosHistorial::Create([
								'id_vehiculo'=>$vehiculo,
								'mes'=>date('Y-m-d'),
								'precio'=>$precio,
							]);
						}
					}
				}
			}
		}

		// actualización de vehículos existentes
		$vehiculos = Vehiculo::where('id_precios_vehiculos', '!=', 0)->get();
		foreach ($vehiculos as $vehiculo) 
		{
			$precio_final = PreciosVehiculosHistorial::where('id_vehiculo', $vehiculo->id_precios_vehiculos)->orderBy('mes', 'DESC')->first();
			$vehiculo->precio_venta = $precio_final;
			$vehiculo->precio_lista = ($precio_final + 10000); 
		}
		return 1;
	}

	public function verhistorial($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/precios/historial.twig', [
			'vehiculo'=>PreciosVehiculos::find($args['id']),
			'historial'=>PreciosVehiculosHistorial::where('id_vehiculo', $args['id'])->get(),
		]);
	}


	public function listado($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = PreciosVehiculos::with('historial','nombremarca');
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) 
			{
				if(strlen($value)>1)
				{
					$registros = $registros->where(function($query) use  ($value) {
						$query->where('nombre','like','%'.$value.'%')
						->orWhere('anio','like','%'.$value.'%')
						->orWhere('id','like','%'.$value.'%')
						->orWhereIn('marca', function($query) use ($value) {
							$query->select('id')->from('precios_vehiculos_marcas')->where('nombre','like','%'.$value.'%');
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
			'order' => $order,
			'search' => $search,
			'columns' => $columns,
		]); 
	}

}