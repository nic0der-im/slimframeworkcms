<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\Extras\Provincia;

use App\Models\Publicacion;
use App\Models\Vehiculos\Marca;
use App\Models\Vehiculos\Transmision;
use App\Models\Vehiculos\TiposMotor;

use App\Models\SP_Extras\Agencia;
use App\Models\Prospecto;
use App\Models\Prospectos\ProspectoHistorial;
use App\Models\SP_Extras\Vendedor;
use App\Models\Individuo;
use App\Models\Empleado;
use App\Models\Consulta;
use Illuminate\Database\Capsule\Manager as DB;

use App\Models\Prospectos\Caracteristica;
use App\Models\Prospectos\Fuentes;
use App\Models\Prospectos\ProspectoCaracteristica;

use App\Auth\auth;

class ProspectosController extends Controller {

	public function index($request, $response)
	{
		// ---------------------------------------------
		$mis_prospectos = Prospecto::where([['id_vendedor', '=', $_SESSION['userid']], ['estado','=','0']])->orderBy('created_at', 'asc')->count();
		$indecisos = Prospecto::where('estado','=','8')->orderBy('created_at', 'asc')->count();
		$nuevos_prospectos = Prospecto::with('getHistorial')->where([['id_vendedor', '=', '0'], ['estado','<','5']])->count();
		$perdidos = Prospecto::where('estado', '=', '3')->count();
		// ----------------------------------------------
		$proximas_citas = Prospecto::with('getSniperNombre','getVendedorNombre','prospectoEstado','caracteristicas.caracter','agencia')
			->where('id_vendedor', $_SESSION['userid'])
			->where('estado','=',2)
			->whereIn('id_agencia',[0,auth::empleado()->id_agencia])
			->limit(15)
			->get();
		// ----------------------------------------------
		$enProceso = Prospecto::where('id_vendedor',$_SESSION['userid'])->whereIn('estado',[5,6])->get();


		return $this->container->view->render($response, 'admin_views/prospectos/index.twig', [
			'mis_prospectos'=>$mis_prospectos,
			'nuevos_prospectos'=>$nuevos_prospectos,
			'indecisos'=>$indecisos,
			'perdidos'=>$perdidos,
			'mis_citas'=>10,
			// ------------------------
			'proximas_citas'=>$proximas_citas,
			// ------------------------
			'id_usuario'=>$_SESSION['userid'],
			'enProceso'=>$enProceso,
		]);
	}


	public function getListado($request, $response, array $args)
	{
		// ---------------------------------------------
		$prospectos = Prospecto::where([['id_vendedor', '=', $_SESSION['userid']], ['estado','=','0']])->orderBy('created_at', 'asc')->get();
		$llamar_tarde = Prospecto::with('getHistorial')->where([['id_vendedor', '=', $_SESSION['userid']], ['estado','=','1']])->get();
		$citas = Prospecto::where([['id_vendedor', '=', $_SESSION['userid']], ['estado','=','2']])->orderBy('updated_at', 'asc')->get();
		$viejos = Prospecto::with('getHistorial')->where('id_vendedor', '=', $_SESSION['userid']) 
		->where(function($q) { 
			$q->where('estado', '=', '3');
			$q->orWhere('estado', '=', '4');
		})
		->orderBy('updated_at', 'desc')
		->get();

		$indecisos = Prospecto::with('getHistorial')->where('id_vendedor', '=', $_SESSION['userid']) 
		->where(function($q) { 
			$q->where('estado', '=', '8');
		})
		->orderBy('updated_at', 'desc')
		->get();

		
		$enProceso = Prospecto::where('id_vendedor',$_SESSION['userid'])->whereIn('estado',[5,6])->get();

		return $this->container->view->render($response, 'admin_views/prospectos/listado.twig', [
			'index'=>intval($args["lista"]),
			'prospectos'=>$prospectos,
			'prospectos_tarde'=>$llamar_tarde,
			'citas'=>$citas,
			'prospectos_viejos'=>$viejos,
			'id_usuario'=>$_SESSION['userid'],
			'enProceso'=>$enProceso,
			'prospectos_indecisos'=>$indecisos
		]);
	}

	public function getEliminar($request, $response, array $args)
	{
		$return_to = $request->getQueryParam('return_to',null);
		$this->flash->addMessage('error', 'Se mandó el prospecto al archivo.');
		$prospecto = Prospecto::find($args['id']);
		$prospecto->estado = 3;
		$prospecto->save();
		if($return_to!=null){
			$url = $this->router->pathFor('prospectos.todos');
			return $response->withStatus(302)->withHeader('Location', $url);
		}
		$url = $this->router->pathFor('prospectos.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getAprobar($request,$response, $args)
	{
		$this->flash->addMessage('error', 'El prospecto se marcó como completado.');

		$prospecto = Prospecto::find($args['id']);
		$prospecto->estado = 4;
		$prospecto->save();
		$url = $this->router->pathFor('prospectos.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getSumar($request,$response, $args)
	{

		$this->flash->addMessage('info', 'Se le sumó una venta de vehículo usado al vendedor.');
		
		$vendedor = Empleado::where('id_usuario','=',$args['id'])->first();
		$vendedor->ventas_usados = $vendedor->ventas_usados + 1;
		$vendedor->save();
		$url = $this->router->pathFor('prospectos.rendimiento');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getRestar($request,$response, $args)
	{

		$this->flash->addMessage('info', 'Se le restó una venta de vehículo usado al vendedor.');

		$vendedor = Empleado::where('id_usuario','=',$args['id'])->first();
		$vendedor->ventas_usados = $vendedor->ventas_usados - 1;
		$vendedor->save();
		$url = $this->router->pathFor('prospectos.rendimiento');
		return $response->withStatus(302)->withHeader('Location', $url);

	}

	public function getSumar0km($request,$response, $args)
	{

		$this->flash->addMessage('info', 'Se le sumó una venta de vehículo 0km al vendedor.');
		
		$vendedor = Empleado::where('id_usuario','=',$args['id'])->first();
		$vendedor->ventas_0km = $vendedor->ventas_usados + 1;
		$vendedor->save();
		$url = $this->router->pathFor('prospectos.rendimiento');
		return $response->withStatus(302)->withHeader('Location', $url);

	}

	public function getRestar0km($request,$response, $args)
	{

		$this->flash->addMessage('info', 'Se le restó una venta de vehículo 0km al vendedor.');

		$vendedor = Empleado::where('id_usuario','=',$args['id'])->first();
		$vendedor->ventas_0km = $vendedor->ventas_usados + 1;
		$vendedor->save();
		$url = $this->router->pathFor('prospectos.rendimiento');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getTodos($request, $response)
	{
		$revision = Prospecto::with([
			'getHistorial'=>function($query){
				$query->where('estado',7);
			},
			])
			->where('estado',7)
			->orderBy('updated_at', 'desc')
			->get();
		$agencias = Empleado::where('id_puesto','2')->orderBy('id_agencia','ASC')->get();

		return $this->container->view->render($response, 'admin_views/prospectos/vistageneral.twig', [
			'agencias'=>$agencias,
			'revision'=>$revision,
		]);
	}

	public function getRendimiento($request, $response)
	{
		$prospectos_dia = Prospecto::orderBy('updated_at', 'desc')->whereDate('updated_at', '=', date('Y-m-d'))->get();
		$prospectos = Prospecto::orderBy('created_at', 'desc')->get();
		$prospectos_mes = Prospecto::whereMonth('created_at', '=', date('m'))->orderBy('created_at', 'desc')->get();
		$prospectos_mes_count = Prospecto::whereMonth('created_at', '=', date('m'))->count();
		$vendedores = Empleado::where('id_puesto','2')->orderBy('id_agencia','ASC')->get();
		$mastarde = Prospecto::whereMonth('created_at', '=', date('m'))->where('estado', '=', '1')->count();
		$mastarde_anterior = Prospecto::whereMonth('created_at', '=', (date('m')-1))->where('estado', '=', '1')->count();
		$citaspactadas = Prospecto::whereMonth('created_at', '=', date('m'))->where('estado', '=', '2')->count();
		$citaspactadas_anterior = Prospecto::whereMonth('created_at', '=', (date('m')-1))->where('estado', '=', '2')->count();
		$perdidos = Prospecto::whereMonth('created_at', '=', date('m'))->where('estado', '=', '3')->count();
		$perdidos_anterior = Prospecto::whereMonth('created_at', '=', (date('m')-1))->where('estado', '=', '3')->count();
		$concretados = Prospecto::whereMonth('created_at', '=', date('m'))->where('estado', '=', '4')->count();
		$concretados_anterior = Prospecto::whereMonth('created_at', '=', (date('m')-1))->where('estado', '=', '4')->count();

		$amchart = Prospecto::select( DB::raw('date(created_at) as fecha'),DB::raw(
			'count(id) as total,
			sum(case when estado = 3 then 1 else 0 end) as concretados,
			sum(case when estado = 4 then 1 else 0 end) as perdidos'))
		->groupBy(DB::raw('date(created_at)'))
		->get();

		$consultas = Consulta::whereMonth('created_at', '=', date('m'))->where('prospectado','=','1')->count();
		$consultas_anterior = Consulta::whereMonth('created_at', '=', (date('m')-1))->where('prospectado','=','1')->count();

		$agencias = Empleado::where('id_puesto','2')->orderBy('id_agencia','ASC')->get();

		return $this->container->view->render($response, 'admin_views/prospectos/rendimiento.twig', [
			'prospectos'=>$prospectos,
			'prospectos_mes'=>$prospectos_mes,
			'prospectos_mes_count'=>$prospectos_mes_count,
			'vendedores'=>$vendedores,
			'mastarde'=>$mastarde,
			'mastarde_anterior'=>$mastarde_anterior,
			'citaspactadas'=>$citaspactadas,
			'citaspactadas_anterior'=>$citaspactadas_anterior,
			'perdidos'=>$perdidos,
			'perdidos_anterior'=>$perdidos_anterior,
			'concretados'=>$concretados,
			'concretados_anterior'=>$concretados_anterior,
			'amchart'=>$amchart,
			'consultas'=>$consultas,
			'consultas_anterior'=>$consultas_anterior,
			'agencias'=>$agencias,
			'prospectos_dia'=>$prospectos_dia,
		]);
	}


	public function postTransferir($request, $response)
	{
		$id_vendedor = $request->getParam('np_vendedor');

		$prospecto = Prospecto::find($request->getParam('mpss_pb_id_prospecto'));
		$vendedor_viejo = $prospecto->id_vendedor;
		switch ($id_vendedor) {
			case 'SALTA':
				$prospecto->id_vendedor = 0;
				$prospecto->id_agencia = 1;
				break;
			case 'ORAN':
				$prospecto->id_vendedor = 0;
				$prospecto->id_agencia = 2;
				break;
			case 0:
				$prospecto->id_vendedor = 0;
				$prospecto->id_agencia = 0;
				break;
			default:
				# code...
				$prospecto->id_vendedor = $id_vendedor;
				break;
		}
		$vendedor_nuevo = $request->getParam('np_vendedor');
		$prospecto->estado = 0;
		$prospecto->save();

		if($vendedor_viejo>0){
			$vendedor_viejo = Individuo::where('id_usuario',$vendedor_viejo)->first();
			$vendedor_viejo = 'De '.$vendedor_viejo->apellido.' '.$vendedor_viejo->nombre;
		} else {
			$vendedor_viejo = 'Desde Liberado';
		}
		if($vendedor_nuevo>0){
			$vendedor_nuevo = Individuo::where('id_usuario',$vendedor_nuevo)->first();
			$vendedor_nuevo = 'Para '.$vendedor_nuevo->apellido.' '.$vendedor_nuevo->nombre;
		} else {
			$vendedor_nuevo = ' a Liberado';
		}

		$dt = new \DateTime();
		ProspectoHistorial::create([
			'id_prospecto'=>$prospecto->id,
			'id_vendedor' => auth::empleado()->id_usuario,
			'observaciones'=>'Transferencia: '.$vendedor_viejo.' '.$vendedor_nuevo,
			'estado'=>5,
			'valor_estado'=> $dt->format('Y-m-d H:i:s'),
		]);
		
		$url = $this->router->pathFor('prospectos.todos');
		if($request->getParam('desde_rendimiento') == 1)
		{
			$url = $this->router->pathFor('prospectos.rendimiento');
		}
		$this->flash->addMessage('info', 'El prospecto se transfirió correctamente');
		return $response->withStatus(302)->withHeader('Location', $url);
	}
	
	public function getCarga($request, $response, array $args)
	{
		$fuentes = Fuentes::all();
		$agencias = Empleado::where('id_puesto','2')->orderBy('id_agencia','ASC')->get();
		$totales = Empleado::select('empleados.id_usuario',DB::raw("count(prospectos.id) as cantidad"))->leftjoin('prospectos',function($join){
			$join->on('empleados.id_usuario', '=', 'prospectos.id_vendedor')->whereRaw('Date(prospectos.created_at)=curdate()');
		})->where('empleados.id_puesto',2)->groupBy('empleados.id_usuario')->orderBy('cantidad','asc')->get();
		$lista = [];
		foreach ($totales as $item) 
		{
			$lista[$item->id_usuario] = str_pad($item->cantidad, 2, "0", STR_PAD_LEFT);
		}
		$publicaciones = Publicacion::where('mostrar', 1)->orderBy('created_at', 'DESC')->get();
		$years = array();
		foreach($publicaciones as $publicacion) 
		{
			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->year, $years)) {
				$years[$v->year] = 1;
			}
			else {
				$years[$v->year]++;	
			}
		}
		krsort($years);
		if(isset($args['consulta'])) 
		{
			return $this->container->view->render($response, 'admin_views/prospectos/nuevo.twig', [
				'agencias'=>$agencias,
				'consulta'=>Consulta::find($args['consulta']),
				'lista'=>$lista,
				'years'=>$years,
				'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
				'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
				'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
				'provincias' => Provincia::all(),
				'fuentes' => $fuentes,
			]);
		} 
		else 
		{
			return $this->container->view->render($response, 'admin_views/prospectos/nuevo.twig', [
				'agencias'=>$agencias,
				'lista'=>$lista,
				'years'=>$years,
				'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
				'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
				'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
				'provincias' => Provincia::all(),
				'fuentes' => $fuentes,
			]);
		}
	}

	public function getInfo($request, $response, array $args) {
		$id = $request->getParam('id_prospecto');
		$prospecto = Prospecto::with('getSniperNombre','getVendedorNombre','prospectoEstado','getHistorial','getHistorial.individuo','caracteristicas','caracteristicas.caracter')->find($id);
		if($prospecto){
			$individuo = auth::individuo();
			$empleado = auth::empleado();
			$dt = new \DateTime();
			$observaciones = 'Visto: por '.$individuo->apellido.' '.$individuo->nombre;
			$estado = 5;
			if($prospecto->id_vendedor==$individuo->id_usuario and $empleado->id_puesto == 2 and $prospecto->estado !=5 ){
				$estado = 6;
				Prospecto::where('id',$id)->update([
					'estado'=> $estado,
				]);
				$observaciones = 'Visto: Inicio por '.$individuo->apellido.' '.$individuo->nombre;
			}
			ProspectoHistorial::create([
				'id_prospecto'=>$prospecto->id,
				'id_vendedor' => $individuo->id_usuario,
				'observaciones'=>$observaciones,
				'estado'=> $estado,
				'valor_estado'=> $dt->format('Y-m-d H:i:s'),
			]);
			return $response->withStatus(200)->withJson($prospecto); 
		} else {
			return $response->withStatus(404);
		}
	}

	public function getUltimoProspecto($request, $response){

		$prospecto = Prospecto::where([['id_vendedor', '=', $_SESSION['userid']], ['estado','=','0']])->orderBy('created_at', 'asc')->first();
		return $this->container->view->render($response, 'admin_views/prospectos/ultimo.twig', [
			'prospecto'=>$prospecto
		]);
	}

	public function postCarga($request, $response) {
		$nombre = $request->getParam('np_nombre');
		$apellido = $request->getParam('np_apellido');
		$telefono = preg_replace('/[^0-9]/', '', $request->getParam('np_telefono') );
		$localidad = $request->getParam('np_localidad');
		$observaciones = $request->getParam('np_observaciones');

		$years = $request->getParam('years');
		$motores = $request->getParam('motores');
		$marcas = $request->getParam('marcas');
		$transmisiones = $request->getParam('transmisiones');
		
		$id_vendedor = $request->getParam('np_vendedor');

		$prospecto_caliente = $request->getParam('prospectoCaliente');

		if(is_numeric($id_vendedor)) {
			if($id_vendedor>0){
				$prospecto = Prospecto::Create([
					'id_sniper'=>$_SESSION['userid'],
					'id_vendedor'=> $id_vendedor,
					'id_agencia' => Empleado::where('id_usuario',$id_vendedor)->first()->id_agencia,
					'nombre'=> $nombre,
					'apellido'=> $apellido,
					'telefono'=> $telefono,
					'localidad'=> $localidad,
					'observaciones'=> $observaciones,
					''
				]);
			} else {
				$prospecto = Prospecto::Create([
					'id_sniper'=>$_SESSION['userid'],
					'id_vendedor'=> 0,
					'id_agencia' =>0,
					'nombre'=> $nombre,
					'apellido'=> $apellido,
					'telefono'=> $telefono,
					'localidad'=> $localidad,
					'observaciones'=> $observaciones,
				]);
			}
			
		} 
		else 
		{
			switch ($id_vendedor) {
				case 'SALTA':
					$prospecto = Prospecto::Create([
						'id_sniper'=>$_SESSION['userid'],
						'id_vendedor'=> 0,
						'id_agencia' => 1,
						'nombre'=> $nombre,
						'apellido'=> $apellido,
						'telefono'=> $telefono,
						'localidad'=> $localidad,
						'observaciones'=> $observaciones,
					]);
					break;
				case 'ORAN':
					$prospecto = Prospecto::Create([
						'id_sniper'=>$_SESSION['userid'],
						'id_vendedor'=> 0,
						'id_agencia' => 2,
						'nombre'=> $nombre,
						'apellido'=> $apellido,
						'telefono'=> $telefono,
						'localidad'=> $localidad,
						'observaciones'=> $observaciones,
					]);
					break;
			}
		}
		if(count($marcas)>0){
			foreach ($marcas as $value) {
				$id = Marca::where('nombre','like','%'.$value.'%')->first()->id;
				ProspectoCaracteristica::Create([
					'id_prospecto' => $prospecto->id,
					'id_caracteristica' => 1,
					'caracteristica' => $id,
				]);
			}
		}
		if(count($motores)>0){
			foreach ($motores as $value) {
				$id = TiposMotor::where('nombre','like','%'.$value.'%')->first()->id;
				ProspectoCaracteristica::Create([
					'id_prospecto' => $prospecto->id,
					'id_caracteristica' => 2,
					'caracteristica' => $id,
				]);
			}
		}
		if(count($transmisiones)>0){
			foreach ($transmisiones as $value) {
				$id = Transmision::where('nombre','like','%'.$value.'%')->first()->id;
				ProspectoCaracteristica::Create([
					'id_prospecto' => $prospecto->id,
					'id_caracteristica' => 3,
					'caracteristica' => $id,
				]);
			}
		}
		if(count($years)>0){
			foreach ($years as $value) {
				ProspectoCaracteristica::Create([
					'id_prospecto' => $prospecto->id,
					'id_caracteristica' => 4,
					'caracteristica' => $value,
				]);
			}
		}

		$url = $this->router->pathFor('prospectos.cargar');

		if($request->getParam('consulta_id') != null)
		{
			$consulta = Consulta::find($request->getParam('consulta_id'));
			$consulta->prospectado = 1;
			$consulta->save();
			$url = $this->router->pathFor('cliente.consultas');
		}

		$this->flash->addMessage('info', 'Prospecto cargado exitosamente.');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getEditar($request,$response,$args){
		$id_prospecto = $args['id'];
		$prospecto = Prospecto::where('id',$id_prospecto)->first();
		$agencias = Empleado::where('id_puesto','2')->orderBy('id_agencia','ASC')->get();
		$totales = Empleado::select('empleados.id_usuario',DB::raw("count(prospectos.id) as cantidad"))->leftjoin('prospectos',function($join){
			$join->on('empleados.id_usuario', '=', 'prospectos.id_vendedor')->whereRaw('Date(prospectos.created_at)=curdate()');
		})->where('empleados.id_puesto',2)->groupBy('empleados.id_usuario')->orderBy('cantidad','asc')->get();
		$lista = [];
		foreach ($totales as $item) {
			$lista[$item->id_usuario] = str_pad($item->cantidad, 2, "0", STR_PAD_LEFT);
		}
		$publicaciones = Publicacion::where('mostrar', 1)->orderBy('created_at', 'DESC')->get();
		$years = array();
		foreach($publicaciones as $publicacion) 
		{
			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->year, $years)) {
				$years[$v->year] = 1;
			}
			else {
				$years[$v->year]++;	
			}
		}
		krsort($years);
		return $this->container->view->render($response, 'admin_views/prospectos/nuevo.twig', [
			'old' => $prospecto,
			'agencias'=>$agencias,
			'lista'=>$lista,
			'years'=>$years,
			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
			'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
			'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
			'provincias' => Provincia::all(),
		]);
	}

	public function postEditarRevision($request,$response,$args){
		$id_prospecto = $args['id'];
		$nombre = $request->getParam('np_nombre');
		$apellido = $request->getParam('np_apellido');
		$telefono = preg_replace('/[^0-9]/', '', $request->getParam('np_telefono') );
		$localidad = $request->getParam('np_localidad');
		$observaciones = $request->getParam('np_observaciones');

		$years = $request->getParam('years');
		$motores = $request->getParam('motores');
		$marcas = $request->getParam('marcas');
		$transmisiones = $request->getParam('transmisiones');
		
		$id_vendedor = $request->getParam('np_vendedor');
		if(is_numeric($id_vendedor)) {
			if($id_vendedor>0){
				$prospecto = Prospecto::where('id',$id_prospecto)->update([
					'id_vendedor'=> $id_vendedor,
					'id_agencia' => Empleado::where('id_usuario',$id_vendedor)->first()->id_agencia,
					'estado' => 0,
					'nombre'=> $nombre,
					'apellido'=> $apellido,
					'telefono'=> $telefono,
					'localidad'=> $localidad,
					'observaciones'=> $observaciones,
				]);
			} else {
				$prospecto = Prospecto::where('id',$id_prospecto)->update([
					'id_vendedor'=> 0,
					'id_agencia' =>0,
					'estado' => 0,
					'nombre'=> $nombre,
					'apellido'=> $apellido,
					'telefono'=> $telefono,
					'localidad'=> $localidad,
					'observaciones'=> $observaciones,
				]);
			}
			
		} else {
			switch ($id_vendedor) {
				case 'SALTA':
					$prospecto = Prospecto::where('id',$id_prospecto)->update([
						'id_vendedor'=> 0,
						'id_agencia' => 1,
						'estado' => 0,
						'nombre'=> $nombre,
						'apellido'=> $apellido,
						'telefono'=> $telefono,
						'localidad'=> $localidad,
						'observaciones'=> $observaciones,
					]);
					break;
				case 'ORAN':
					$prospecto = Prospecto::where('id',$id_prospecto)->update([
						'id_vendedor'=> 0,
						'id_agencia' => 2,
						'estado' => 0,
						'nombre'=> $nombre,
						'apellido'=> $apellido,
						'telefono'=> $telefono,
						'localidad'=> $localidad,
						'observaciones'=> $observaciones,
					]);
					break;
			}
		}

		$url = $this->router->pathFor('prospectos.todos');
		$this->flash->addMessage('info', 'Prospecto editado exitosamente.');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function postEditar($request, $response) {
		if($request->isXhr()){

			$id_prospecto = $request->getParam('mpss_pb_id_prospecto');
			$duracion_llamada = $request->getParam('mpss_pb_time');
			
			$observaciones = $request->getParam('mpss_pb_ta_observaciones');

			$estado = $request->getParam('mpss_pb_estado');
			$valor_estado = $request->getParam('mpss_pb_valor_estado');

			$contesto = $request->getParam('mpss_pb_llamada');

			// Si no contestó la llamada
			if($contesto == 1) {
				$estado = 1;
				$valor_estado = new \DateTime();
				$valor_estado->add(new \DateInterval('PT900S'));
				$valor_estado = $valor_estado->format('Y-m-d H:i:s');
				$observaciones = 'No contestó. Llamar en 15 minutos';
			}
			// Si contestó la llamada
			else {
				if($estado == 1) {
					$hora_estado = $request->getParam('mpss_pb_hora_estado');
					$fecha_estado = $request->getParam('mpss_pb_fecha_estado');
					$dt = new \DateTime();
					$fecha = $dt->createFromFormat('Y-m-d H:i', $fecha_estado.' '. $hora_estado );
					$valor_estado = $fecha->format('Y-m-d H:i:s');

				} else if ($estado == 2) {
					$valor_estado_2 = $request->getParam('mpss_pb_valor_estado_2');
					$fcompleta = $valor_estado . ' ' . $valor_estado_2;
					$dt = new \DateTime();
					$fecha = $dt->createFromFormat('Y-m-d H:i', $fcompleta);

					$valor_estado = $fecha->format('Y-m-d H:i:s');
				}
			}

			$nuevo_registro = ProspectoHistorial::create([
				'id_prospecto'=>$id_prospecto,
				'id_vendedor' => auth::empleado()->id_usuario,
				'observaciones'=>$observaciones,
				'duracion_llamada'=>$duracion_llamada,
				'estado'=>$estado,
				'valor_estado'=>$valor_estado,
			]);

			$prospecto = Prospecto::find($id_prospecto);
			if($prospecto->estado == 5 and ( $estado == 1 or $estado == 0 ) ){
				$prospecto->id_vendedor = 0;
			}
			$prospecto->estado = $estado;
			$prospecto->save();

			return $response->withJson([
				'success'=>true,
			]);
		}
	}


	public function postLlameProspecto($request, $response) {
		$this->flash->addMessage('info', 'Se marcó el prospecto como llamado.');

		$prospecto = Prospecto::find($request->getParam('id_prospecto'));
		$prospecto->estado = 1;
		$prospecto->fecha_hora_llamado = date("Y-m-d H:i:s");
		$prospecto->save();

		echo json_encode(array("success"=>true));
	}

	public function postCambiarObservacion($request, $response) {
		
		$this->flash->addMessage('info', 'La observación se cambió correctamente.');

		$prospecto = Prospecto::find($request->getParam('id_prospecto'));
		$prospecto->observaciones = $request->getParam('observacion');		
		$prospecto->save();

		echo json_encode(array("success"=>true));

	}

	public function get($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Prospecto::with('getSniperNombre','getVendedorNombre','prospectoEstado','caracteristicas','caracteristicas.caracter','agencia');
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('nombre','like','%'.$value.'%')
							->orWhere('apellido','like','%'.$value.'%')
							->orWhere('localidad','like','%'.$value.'%')
							->orWhere(DB::raw('digits(telefono)'),'like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhereIn('id_sniper',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_vendedor',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_agencia',function($d) use ($value){
								$d->select('id')->from('sp_agencias')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('estado',function($d) use ($value){
								$d->select('id')->from('prospecto_tipo_estados')->where('nombre','like','%'.$value.'%');
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

	public function getLibres($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Prospecto::with('getSniperNombre','getVendedorNombre','prospectoEstado','caracteristicas.caracter','agencia')
		->where('id_vendedor',0)
		->where('estado','<',5)
		->whereIn('id_agencia',[0,auth::empleado()->id_agencia]);
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('nombre','like','%'.$value.'%')
							->orWhere('apellido','like','%'.$value.'%')
							->orWhere('localidad','like','%'.$value.'%')
							->orWhere('telefono','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhereIn('id_sniper',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_agencia',function($d) use ($value){
								$d->select('id')->from('sp_agencias')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('estado',function($d) use ($value){
								$d->select('id')->from('prospecto_tipo_estados')->where('nombre','like','%'.$value.'%');
							});
					});
				}
			}
		}

		$recordsFiltered = $registros->count();
		$registros = $registros->orderBy('updated_at','asc');
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

	public function getUrgentes($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Prospecto::with('getSniperNombre','getVendedorNombre','prospectoEstado','caracteristicas.caracter','agencia')
		->where('id_vendedor',0)
		->where('estado','<',5)
		->where('caliente', '=', 1)
		->whereIn('id_agencia',[0,auth::empleado()->id_agencia])
		->limit(10)
		->orderBy('updated_at', 'desc');
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('nombre','like','%'.$value.'%')
							->orWhere('apellido','like','%'.$value.'%')
							->orWhere('localidad','like','%'.$value.'%')
							->orWhere('telefono','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhereIn('id_sniper',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')->where(function($q) use ($value){
									$q->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhereIn('id_agencia',function($d) use ($value){
								$d->select('id')->from('sp_agencias')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('estado',function($d) use ($value){
								$d->select('id')->from('prospecto_tipo_estados')->where('nombre','like','%'.$value.'%');
							});
					});
				}
			}
		}

		$recordsFiltered = $registros->count();
		$registros = $registros->orderBy('updated_at','asc');
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

	public function getTransferir($request, $response, array $args){
		$prospecto = Prospecto::where('id_vendedor',auth::empleado()->id_usuario)->whereIn('estado',[5,6])->first();
		if($prospecto){
			return $response->withStatus(401);
		}

		$prospecto = Prospecto::where('id',$args['id'])->where('id_vendedor',0)->where('estado','<',5)->first();
		if($prospecto){
			$prospecto->id_vendedor = auth::empleado()->id_usuario;
			$prospecto->estado = 5;
			$prospecto->save();
			$prospecto = Prospecto::with([
				'getSniperNombre'=>function($query){},
				'getVendedorNombre'=>function($query){},
				'prospectoEstado'=>function($query){},
				'getHistorial'=>function($query){
					$query->whereNotIn('estado',[5]);
				},
				'getHistorial.individuo'=>function($query){},
				'caracteristicas'=>function($query){},
				'caracteristicas.caracter'=>function($query){},
				])
				->where('id',$args['id'])
				->first();
			$dt = new \DateTime();
			ProspectoHistorial::create([
				'id_prospecto'=>$prospecto->id,
				'id_vendedor' => auth::empleado()->id_usuario,
				'observaciones'=>'Transferencia: Posesion del Prospecto. Por '.auth::individuo()->apellido.' '.auth::individuo()->nombre,
				'estado'=>5,
				'valor_estado'=> $dt->format('Y-m-d H:i:s'),
			]);
			return $response->withStatus(200)->withJson($prospecto); 
		} else {
			return $response->withStatus(404);
		}
	}

	public function getLiberar($request, $response, array $args){
		$id = $args['id'];
		$prospecto = Prospecto::where('id',$id)->whereIn('estado',[0,1,5,6])->first();
		if($prospecto)
		{
			$individuo = auth::individuo();
			$estado = 5;
			if($prospecto->estado == 6){
				$estado = 6;
				$observaciones = 'Visto: Fin por '.$individuo->apellido.' '.$individuo->nombre;
			} else {
				$prospecto->id_vendedor = 0;
				$observaciones = 'Transferencia: Renuncia del Prospecto. Por '.$individuo->apellido.' '.$individuo->nombre;
			}
			$ultimo = ProspectoHistorial::where('id_prospecto',$id)->where('estado','<',5)->orderBy('created_at', 'desc')->first();
			if($ultimo){
				$prospecto->estado = $ultimo->estado;
			} else {
				$prospecto->estado = 0;
			}
			$prospecto->save();
			$prospecto = Prospecto::with('getSniperNombre','getVendedorNombre','prospectoEstado','getHistorial','getHistorial.individuo','caracteristicas','caracteristicas.caracter')->where('id',$id)->first();
			$dt = new \DateTime();
			ProspectoHistorial::create([
				'id_prospecto'=>$id,
				'id_vendedor' => $individuo->id_usuario,
				'observaciones'=> $observaciones,
				'estado'=>$estado,
				'valor_estado'=> $dt->format('Y-m-d H:i:s'),
			]);
			return $response->withStatus(200)->withJson($prospecto); 
		} 
		else 
		{
			return $response->withStatus(400)->withJson($prospecto); 
		}
	}

	public function postRevisar($request,$response){
		$id_prospecto = $request->getParam('revision_id_prospecto');
		$descripcion = $request->getParam('revision_descripcion');
		$individuo = auth::individuo();
		Prospecto::where('id',$id_prospecto)->update([
			'estado' => 7,
		]);
		$dt = new \DateTime();
		ProspectoHistorial::create([
			'id_prospecto'=>$id_prospecto,
			'id_vendedor' => $individuo->id_usuario,
			'observaciones'=> $descripcion,
			'estado' => 7,
			'valor_estado'=> $dt->format('Y-m-d H:i:s'),
		]);

		$url = $this->router->pathFor('prospectos.index');
		$this->flash->addMessage('info', 'Prospecto enviado a Revision con exito.');
		return $response->withStatus(302)->withHeader('Location', $url);
	}


	public function revisarTelefono($request, $response, array $args) {
		$telefonos = Prospecto::where('telefono', 'like', '%'.$args["tlf"].'%')->get();
		return $response->withJson($telefonos);
	}
}