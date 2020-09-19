<?php

namespace App\Controllers;


use Slim\Views\Twig as View;
use App\Models\Publicacion;
use App\Models\PublicacionVista;
use App\Models\Consulta;
use App\Models\Suscripcion;
use App\Models\User;

use App\Models\Vehiculos\Marca;
use App\Models\Vehiculos\Transmision;
use App\Models\Vehiculos\TiposMotor;
use App\Models\Vehiculos\EstadosVeh;
use App\Models\Vehiculos\ImagenesVeh;

use App\Models\Extras\Provincia;
use App\Models\Extras\Ubicacion;
use App\Models\Extras\Localidad;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\VehiculoHistorial;
use App\Models\Vehiculos\Usado;
use App\Models\Vehiculos\UsadoTercero;

use App\Models\Vehiculos\Creditos;
use App\Models\Vehiculos\CreditosFijos;


use App\Models\Individuo;

use App\Models\Pagina\Pagina;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

use App\Models\Redireccion;

use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;
use App\Auth\auth;

use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;

class HomeController extends Controller
{
	public function notfound($request, $response, array $args)
	{
		return $this->container->view->render($response->withStatus(404), 'guest_views/404.twig',[]);
	}

	public function enconstruccion($request, $response, array $args)
	{
		return $this->container->view->render($response->withStatus(301), 'guest_views/enconstruccion.twig',[]);
	}

	public function getUsadosJujuy($request, $response, array $args) {

		$dt = new \DateTime();
		$rankSemana = PublicacionVista::select('publicacion_vista.id_publicacion',DB::raw('count(distinct ip) as rank'))
			->where('publicacion_vista.created_at','>',$dt->modify('-1 Week'))
			->join('publicaciones','publicacion_vista.id_publicacion','=','publicaciones.id')
			->where('publicaciones.mostrar', 1)
			->groupBy('id_publicacion')
			->orderBy('rank','desc')
			->limit(3)
			->get();
		$in = '(0,';
		foreach ($rankSemana as $value) {
			$in = $in . $value->id_publicacion . ',';
		}
		$in = rtrim($in,',').')';

		$publicaciones = Publicacion::select('*',DB::raw('IF(id in '.$in.',1,0) as rankSemana'))
			->where('mostrar', 1)
			->orderBy('vistas','desc')
			->get();


		return $this->container->view->render($response, 'guest_views/usados-jujuy.twig',[
			'publicaciones'=>$publicaciones
		]);
	}

	public function promociones($request, $response, array $args)
	{
		return $this->container->view->render($response, 'guest_views/promociones.twig',[]);
	}

	public function financiacion($request, $response, array $args)
	{
		$id_vehiculo = $args["id"];
		$vehiculo = Vehiculo::find($id_vehiculo);
		$usado = Usado::where('id_vehiculo', $id_vehiculo)->first();

		$params = array();
		if($usado) { $params['usado'] = $usado; }
		$params['vehiculo'] = $vehiculo;
		$params['vehiculo']['marca'] = Marca::find($vehiculo->id_marca)->nombre;
		$params['vehiculo']['tipo_motor'] = TiposMotor::find($vehiculo->id_tipo_motor)->nombre;
		$params['vehiculo']['transmision'] = Transmision::find($vehiculo->id_transmision)->nombre;
		$params['vehiculo']['localidad'] = Localidad::find($vehiculo->id_localidad)->nombre;
		$params['vehiculo']['estado'] = EstadosVeh::find($vehiculo->estado_vehiculo)->nombre;

		$imagenes = ImagenesVeh::where('id_vehiculo', $id_vehiculo)->get();
		if($imagenes->count() > 0)
		{
			$params['images'] = $imagenes;
			$params['rechazadas'] = 0;
			$real = array();
			foreach($params['images'] as $imagen) {
				if($imagen['estado'] == 2) { $params['rechazadas']++; continue; }
				$url = '../public'.$imagen['archivo'];
				if(!file_exists($url)) {
					ImagenesVeh::find($imagen['id'])->delete();
				}
				else {
					$real[] = array('archivo'=>$imagen['archivo']);
				}
			}
			if(count($real) > 0) {
				$params['images'] = $real;
			}
			else {
				unset($params['images']);
			}
		}

		return $this->container->view->render($response, 'guest_views/financiacion.twig',[
			'params'=>$params,
		]);
	}

	public function index($request, $response, array $args){

		$pagina_numero = isset($args['id']) ? $args['id'] : 1;

		$dt = new \DateTime();
		$rankSemana = PublicacionVista::select('publicacion_vista.id_publicacion',DB::raw('count(distinct ip) as rank'))
			->whereRaw('publicacion_vista.created_at >= curdate() - INTERVAL DAYOFWEEK(curdate())+6 DAY')
			->whereRaw('publicacion_vista.created_at < curdate() - INTERVAL DAYOFWEEK(curdate())-1 DAY')
			->join('publicaciones','publicacion_vista.id_publicacion','=','publicaciones.id')
			->where('publicaciones.mostrar', 1)
			->groupBy('id_publicacion')
			->orderBy('rank','desc')
			->limit(3)
			->get();
		$in = '(0,';
		foreach ($rankSemana as $value) {
			$in = $in . $value->id_publicacion . ',';
		}
		$in = rtrim($in,',').')';

		if($pagina_numero <> 1) {
			$offset = 9 * ($pagina_numero-1);
		}
		else {
			$offset = 0;
		}


		$publicaciones = Publicacion::select('*',DB::raw('IF(id in '.$in.',1,0) as rankSemana'))
			->where('mostrar', 1)
			->orderBy('vistas','desc')
			->get();

		$years = array();
		foreach($publicaciones as $publicacion) {
			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->year, $years)) {
				$years[$v->year] = 1;
			}
			else {
				$years[$v->year]++;
			}
		}
		krsort($years);

		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();

		$extra_modules = array(
			array("module"=>"search_mobile", "css"=>array("/dist/css/search_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/search_mobile.twig", "icon"=>"fa fa-search", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Buscar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileSearch")))),
			/*array("module"=>"filter_mobile", "css"=>array("/dist/css/filter_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/filter_mobile.twig", "icon"=>"fa fa-filter", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Filtrar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileFilter")))),*/
		);

		$vistos = json_encode([]);
		$cookie = json_decode(FigRequestCookies::get($request, 'vistos',$vistos)->getValue(),true);
		if(!empty($cookie)){
			if(array_key_exists('id',$cookie[0])){ //VERIFICA SI ES LA SEGUNDA VERSION DE COOKIES
				usort($cookie, function($a, $b) {
				    return (\DateTime::__set_state($a['date'])) <=>  (\DateTime::__set_state($b['date']));
				});
				$columna = array_column($cookie, 'id');
				$columna = array_reverse($columna);
				$field = '';
				foreach ($columna as $value) {
					$field = $field .','.$value;
				}
				$vistos = Publicacion::whereIn('id',$columna)
					->where('mostrar', 1)
					->orderByRaw('FIELD(id'.$field.')')
					->get();
				$columna = $vistos->pluck('id')->toArray();
				$lista = [];
				foreach ($cookie as $value) {
					if (in_array($value['id'],$columna)) {
						array_push($lista,$value);
					}
				}
			} else { //SI NO LO ES ENTONCES ELIMINA
				$lista = [];
				$vistos = [];
			}
			$modify = function (SetCookie $setCookie) use ($lista) {
		    return $setCookie->withValue(json_encode($lista))
				->withExpires(strtotime("+1 year"));
			};
			$response = FigResponseCookies::modify($response, 'vistos', $modify);
		} else {
			$vistos = [];
		}

		$nuevos_usados = Publicacion::where('mostrar',1)
			->whereRaw('created_at >= curdate() - INTERVAL DAYOFWEEK(curdate())+6 DAY')
			->whereRaw('created_at < curdate() - INTERVAL DAYOFWEEK(curdate())-1 DAY')
			->get();
		$field = '';
		$columna = [];

		if(count($nuevos_usados) > 0)
		{
			foreach ($rankSemana as $value) 
			{
			$field = $field .','.$value->id_publicacion;
			array_push($columna,$value->id_publicacion);
			}

			$rank_semanal = Publicacion::whereIn('id',$columna)
			->where('mostrar', 1)
			->orderByRaw('FIELD(id'.$field.')')
			->get();
		}
		else
		{
			$rank_semanal = [];
		}

		return $this->container->view->render($response, 'guest_views/home_fixed.twig', [
			'publicaciones'=>$publicaciones,
			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
			'years'=>$years,
			'localidades'=>Localidad::orderBy('nombre', 'asc')->get(),
			'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
			'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
			'test'=>$args,
			'pagina_numero'=>$pagina_numero,
			'paginasMarca'=> $paginaMarcas,
			'extra_modules'=> $extra_modules,
			'vistos' => $vistos,
			'nuevos_usados' => $nuevos_usados,
			'rank_semanal' => $rank_semanal,
		]);
	}

	public function index_solo($request, $response, array $args)
	{

		$pagina_numero = isset($args['id']) ? $args['id'] : 1;

		$dt = new \DateTime();
		$rankSemana = PublicacionVista::select('publicacion_vista.id_publicacion',DB::raw('count(distinct ip) as rank'))
			->where('publicacion_vista.created_at','>',$dt->modify('-1 Week'))
			->join('publicaciones','publicacion_vista.id_publicacion','=','publicaciones.id')
			->where('publicaciones.mostrar', 1)
			->groupBy('id_publicacion')
			->orderBy('rank','desc')
			->limit(3)
			->get();
		$in = '(0,';
		foreach ($rankSemana as $value) {
			$in = $in . $value->id_publicacion . ',';
		}
		$in = rtrim($in,',').')';

		if($pagina_numero <> 1) {
			$offset = 9 * ($pagina_numero-1);
		}
		else {
			$offset = 0;
		}


		$publicaciones = Publicacion::select('*',DB::raw('IF(id in '.$in.',1,0) as rankSemana'))
			->where('mostrar', 1)
			->orderBy('vistas','desc')
			->get();


		// Buscar modelos
		$modelos = array();
		foreach($publicaciones as $publicacion) {

			if(!isset($publicacion->vehiculo->getUsado)) {
				$pubs_0km[] = $publicacion;
			}

			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->modelo, $modelos)) {
				$modelos[$v->modelo] = 1;
			}
			else {
				$modelos[$v->modelo]++;
			}
		}

		$years = array();
		foreach($publicaciones as $publicacion) {
			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->year, $years)) {
				$years[$v->year] = 1;
			}
			else {
				$years[$v->year]++;
			}
		}
		krsort($years);

		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();

		$extra_modules = array(
			array("module"=>"search_mobile", "css"=>array("/dist/css/search_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/search_mobile.twig", "icon"=>"fa fa-search", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Buscar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileSearch")))),
			/*array("module"=>"filter_mobile", "css"=>array("/dist/css/filter_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/filter_mobile.twig", "icon"=>"fa fa-filter", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Filtrar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileFilter")))),*/
		);

		return $this->container->view->render($response, 'guest_views/home_fixed_solo.twig', [
			'publicaciones'=>$publicaciones,
			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
			'modelos'=>$modelos,
			'years'=>$years,
			'localidades'=>Localidad::orderBy('nombre', 'asc')->get(),
			'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
			'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
			'test'=>$args,
			'pagina_numero'=>$pagina_numero,
			'paginasMarca'=> $paginaMarcas,
			'extra_modules'=> $extra_modules
		]);
	}

	public function postConsulta($request, $response) {
		$telefono = preg_replace('/[^0-9]/', '', $request->getParam('consulta_telefono') );
		$email = $request->getParam('consulta_email');
		$url = $request->getParam('consulta_url');
		$id_usuario = $request->getParam('consulta_userid');
		$consulta = Consulta::create([
			'id_usuario'=> $id_usuario,
			'nombre'=>$request->getParam('consulta_nombre'),
			'apellido'=>$request->getParam('consulta_apellido'),
			'texto'=>$request->getParam('consulta_texto'),
			'telefono'=>$telefono,
			'url'=>$url,
			'email'=>$email,
		]);
		if($id_usuario<=0){
			$factory = new \RandomLib\Factory;
			$generator = $factory->getMediumStrengthGenerator();
			Suscripcion::Create([
				'email'=> $email,
				'origen' => $url,
				'token' => $generator->generateString(128,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
			]);
		}

		if($request->isXhr()){
			return $response->withJson([
				'success'=>true,
			]);
		} else {
			$this->flash->addMessage('info', 'Tu consulta ha sido enviada a nuestros asesores. Se comunicarán contigo en las próximas horas.');
			return $response->withRedirect($this->router->pathFor('home'));
		}
	}

	public function postBusqueda($request, $response) {
		if($request->isXhr()) {

			$marcas = Marca::orderBy('nombre', 'ASC')->get();
			$vehiculos = Vehiculo::orderBy('vistas', 'desc')->get();

			$input = $request->getParam('sbrq');

			$keywords = array();
			foreach($marcas as $marca) {
				$nombre_marca = $marca->nombre;
				if($marca->tienevehiculos()) {
					array_push($keywords, array('t'=>0,'marca'=>$nombre_marca));
				}
			}
			foreach($vehiculos as $kv=>$v) {
				if(!$v->EstaPublicado()) { unset($vehiculos[$kv]); continue; }
				array_push($keywords, array('t'=>1,'marca'=>$v->getMarca->nombre, 'modelo'=>$v->modelo, 'year'=>$v->year));
			}

			$resultados = array();
			$sfs = new \wataridori\SFS\SimpleFuzzySearch($keywords, ['marca', 'modelo', 'year'], $input);
			$tmp_res = $sfs->search();
			if(is_array($tmp_res)) {
				// El primer resultado del array es siempre el más específico.

				foreach($tmp_res as $sres) {
					$marca = $sres[0]['marca'];

					$tmp_marca = r_sinacentos($marca);
					$tmp_marca = strtolower($tmp_marca);
					$tmp_marca = str_replace(' ', '-', $tmp_marca);
					$url_modelo = $tmp_marca;

					$url_marca = strtolower($marca);

					$nr = array('marca'=>$marca);
					$url_params = 'marca/'.$url_marca;


					if(isset($sres[0]['modelo'])) {
						$modelo = $sres[0]['modelo'];
						$tmp_modelo = r_sinacentos($modelo);
						$tmp_modelo = strtolower($tmp_modelo);
						$tmp_modelo = str_replace(' ', '-', $tmp_modelo);
						$url_modelo = $tmp_modelo;

						$nr['modelo'] = ' ' . $modelo;
						$url_params.="/modelo/".$url_modelo;
					} else { $nr['modelo'] = ''; }

					if(isset($sres[0]['year'])) {
						$year = $sres[0]['year'];



						$nr['year'] = $year . ' ';
						$url_params.="/year/".$year;
					} else { $nr['year'] = ''; }

					$nr['url'] = $this->container->router->pathFor('vehicle.filter', ['params'=>$url_params]);


					/*if(isset($sres[0]['modelo'])) {

						$modelo = $sres[0]['modelo'];
						$tmp_modelo = r_sinacentos($modelo);
						$tmp_modelo = strtolower($tmp_modelo);
						$tmp_modelo = str_replace(' ', '-', $tmp_modelo);
						$url_modelo = $tmp_modelo;

						$url = $this->container->router->pathFor('vehicle.filter', ['params'=>'marca/'.$url_marca.'/modelo/'.$url_modelo]);
						$nr = array('marca'=>$marca, 'modelo'=>' ' . $modelo, 'url'=>$url);
					}
					else {
						$url = $this->container->router->pathFor('vehicle.filter', ['params'=>'marca/'.$url_marca]);
						$nr = array('marca'=>$marca, 'modelo'=>'', 'url'=>$url);
					}*/
					$resultados[] = $nr;
				}
			}

			return $response->withJson([
				'success'=>true,
				'res'=>$resultados,
			]);
		}
	}

	public function postTest($request, $response, $args) {

		if ($request->isXhr()) {

			$individuo = Individuo::where('nombre', 'like', '%'.$request->getParam('nombre').'%')->get();
			return $response->withJson(['success' => true, 'individuos'=>$individuo, 'request'=>$request->getParams()]);
		} else {
			return $response->withRedirect('/');
		}
	}

	public function vervehiculo($request, $response, array $args){
		$id_publicacion = $args['id'];
		$publicacion = Publicacion::where('id', $id_publicacion)->first();

		// No existe la publicacion
		if(!$publicacion)
		{
			$redireccion = Redireccion::where('url', '=', $id_publicacion)->first();
			if($redireccion)
			{
				return $response->withRedirect($this->router->pathFor($redireccion->destino));
			}
			else
			{
				return $this->container->view->render($response, 'guest_views/vehiculos/404.twig',[]);
			}
		}

		/* Si el vehículo se vendió, redirigir a una página en la que se muestre un mensaje comunicando lo sucedido y ofrezca vehículos similares. */

		if($publicacion->mostrar == 2) {

		}

		// Buscar vehículos similares.

		$vehiculos_similares = array();

		// Similares en precio

		$precio = $publicacion->vehiculo->precio_venta;
		$presub = $precio * 0.85;
		$presup = $precio * 1.15;

		$miny = $publicacion->vehiculo->year - 2;
		$maxy = $publicacion->vehiculo->year + 2;

		$similares = Vehiculo::where('id','<>', $publicacion->vehiculo->id)
		->where(function($q) use ($publicacion, $presub, $presup, $miny, $maxy){
			$q->whereBetween('precio_venta', [$presub, $presup]);
			$q->orWhere('id_marca', '=', $publicacion->vehiculo->id_marca);
			$q->orwhereBetween('year', [$miny, $maxy]);
		})
		->has('getPublicacion')
		->get()->pluck('id')->toArray();

		$similares = Publicacion::whereIn('id_vehiculo',$similares)->where('mostrar', 1)->take(5)->get();

		$publicacion->vistas = $publicacion->vistas + 1;
		$publicacion->save();
		$vista = PublicacionVista::create([
			'id_publicacion' => $publicacion->id,
			'ip' => $request->getAttribute('ip_address'),
		]);
		$cookie = FigRequestCookies::get($request, 'osud',"")->getValue();
		if(!empty($cookie)){
			$terminal = UserTerminal::where('os_user_id',$cookie)->first();
			PublicacionVista::where('id',$vista->id)->update([
				'id_terminal' => $terminal->id,
			]);
		}

		// Sistema de calculo de créditos:
		$financiacion = Creditos::where('year', $publicacion->vehiculo->year)->first();
		$credito = $publicacion->vehiculo->precio_lista * ( $financiacion->porcentaje / 100 );
		$entrega_minima = "hola";

		$fijos = CreditosFijos::orderBy('id', 'DESC')->first();
		$entrega_minima = ( $publicacion->vehiculo->precio_venta - $credito) + ( $publicacion->vehiculo->precio_venta  * ($fijos->transferencia / 100)) + $fijos->otorgamiento + ($credito * ($fijos->prenda / 100));

		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();

		$vistos = json_encode([]);
		$vistos = json_decode(FigRequestCookies::get($request, 'vistos',$vistos)->getValue(),true);
		if(!empty($vistos)){
			if(array_key_exists('id',$vistos[0])){ //VERIFICA SI ES LA SEGUNDA VERSION DE COOKIES
				if(!in_array($id_publicacion, array_column($vistos, 'id'))){
					array_push($vistos,[
						'id' => $id_publicacion,
						'date' => new \DateTime('now'),
						'count' => 1,
					]);
				} else {
					$index = array_search($id_publicacion, array_column($vistos, 'id'));
					$vistos[$index]['date'] = new \DateTime('now');
					$vistos[$index]['count'] = $vistos[$index]['count'] + 1;
				}
			} else { //SI NO LO ES ENTONCES ELIMINA
				$vistos = [];
				array_push($vistos,[
					'id' => $id_publicacion,
					'date' => new \DateTime('now'),
					'count' => 1,
				]);
			}
			$modify = function (SetCookie $setCookie) use ($vistos) {
		    return $setCookie->withValue(json_encode($vistos))
				->withExpires(strtotime("+1 year"));
			};
			$response = FigResponseCookies::modify($response, 'vistos', $modify);
		} else {
			array_push($vistos,[
				'id' => $id_publicacion,
				'date' => new \DateTime('now'),
				'count' => 1,
			]);
			$response = FigResponseCookies::set($response, SetCookie::create('vistos')
				->withValue(json_encode($vistos))
				->withExpires(strtotime("+1 year"))
			);
		}

		return $this->container->view->render($response, 'guest_views/vehiculos/ver.twig',[
			'publicacion'=>$publicacion,
			'entrega_minima'=>$entrega_minima,
			'similares'=>$similares,
			'credito'=>$credito,
			'entrega_minima'=>$entrega_minima,
			'paginasMarca' => $paginaMarcas,
		]);
	}

	public function verpagina($request, $response, array $args){

		$pagina = Pagina::where('url_name', $args['titulo'])->first();
		if($pagina != null)
		{
			$pagina->contador = $pagina->contador + 1;
			$pagina->save();

			return $this->container->view->render($response, 'guest_views/0km/base.twig',[
				'pagina'=>$pagina,
			]);
		}
		return $this->container->view->render($response, 'guest_views/404.twig',[]);
	}

	public function verlanding($request, $response, array $args){
		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
		$query->where('mostrar',1);})->get();
		return $this->container->view->render($response, 'guest_views/0km/landing.twig',[
			'paginas'=>$sugeridos,
			'pagina'=>$pagica,
			'paginasMarca'=> $paginaMarcas,
		]);
	}
	/************verpaginamarca*******************/
	public function verpaginamarca($request, $response, array $args){

		$marca = Marca::where('url_name', $args['titulo'])->first();

		if($marca) {
			return $this->container->view->render($response, 'guest_views/0km/marca.twig',[
				'marca'=>$marca,
			]);
		}

		else {
			return $response->withRedirect('/pagina-no-encontrada', 303);
		}
	}
	/*******************************/

	public function filtrarprecio($request, $response, array $args)
	{

		$pagina_numero = isset($args['id']) ? $args['id'] : 1;

		if($pagina_numero <> 1) {
			$offset = 9 * ($pagina_numero-1);
		}
		else {
			$offset = 0;
		}

		$publicaciones = Publicacion::where('mostrar', 1)->get();

		foreach($publicaciones as $key=>$publicacion) {
			if($publicacion->vehiculo->precio_venta >= $args['desde'] && $publicacion->vehiculo->precio_venta <= $args['hasta'])
			{

			}
			else {
				unset($publicaciones[$key]);
			}
		}

		// Buscar modelos
		$modelos = array();
		foreach($publicaciones as $publicacion) {

			if(!isset($publicacion->vehiculo->getUsado)) {
				$pubs_0km[] = $publicacion;
			}

			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->modelo, $modelos)) {
				$modelos[$v->modelo] = 1;
			}
			else {
				$modelos[$v->modelo]++;
			}
		}

		$years = array();
		foreach($publicaciones as $publicacion) {
			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->year, $years)) {
				$years[$v->year] = 1;
			}
			else {
				$years[$v->year]++;
			}
		}
		krsort($years);

		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();

		$extra_modules = array(
			array("module"=>"search_mobile", "css"=>array("/dist/css/search_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/search_mobile.twig", "icon"=>"fa fa-search", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Buscar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileSearch")))),
			/*array("module"=>"filter_mobile", "css"=>array("/dist/css/filter_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/filter_mobile.twig", "icon"=>"fa fa-filter", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Filtrar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileFilter")))),*/
		);

		return $this->container->view->render($response, 'guest_views/vehiculos/filtrar_precio.twig', [
			'publicaciones'=>$publicaciones,

			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
			'modelos'=>$modelos,
			'years'=>$years,
			'localidades'=>Localidad::orderBy('nombre', 'asc')->get(),
			'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
			'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
			'args'=>$args,
			'pagina_numero'=>$pagina_numero,
			'paginasMarca'=> $paginaMarcas,
			'extra_modules'=>$extra_modules

		]);

	}

	public function filtrarvehiculos($request, $response, array $args){

		$pagina_numero = isset($args['id']) ? $args['id'] : 1;
		if($pagina_numero <> 1) {
			$offset = 9 * ($pagina_numero-1);
		}
		else {
			$offset = 0;
		}

		$parametros = str_replace(array('/marca/', 'marca/', '/marca'), '#!#marca#@#', $args['params']);
		$parametros = str_replace(array('/ubicacion/', 'ubicacion/', '/ubicacion'), '#!#ubicacion#@#', $parametros);
		$parametros = str_replace(array('/year/', 'year/', '/year'), '#!#year#@#', $parametros);
		$parametros = str_replace(array('/transmision/', 'transmision/', '/transmision'), '#!#transmision#@#', $parametros);
		$parametros = str_replace(array('/motor/', 'motor/', '/motor'), '#!#motor#@#', $parametros);
		$parametros = str_replace(array('/modelo/', 'modelo/', '/modelo'), '#!#modelo#@#', $parametros);
		$parametros = str_replace(array('/precio/', 'precio/', '/precio'), '#!#precio#@#', $parametros);

		$tags = explode('#!#', $parametros);
		foreach($tags as $tag) {
			$tmp_tag = explode('#@#', $tag);
			if(isset($tmp_tag[1])) {
				$filtred_tags[$tmp_tag[0]] = str_replace('-', ' ', $tmp_tag[1]);
			}
		}

		$unset = 0;
		$filter_warning_message = null;
		$filter_info_message = null;

		$search_keys = array();
		foreach($filtred_tags as $fkey=>$tag) {
			$aux = false;
			switch($fkey) {
				case 'ubicacion': {
					$tmp = Localidad::where('nombre', 'like', '%'.$tag.'%')->first();
					$search_for = 'id_localidad'; $return = 'id'; $show = 'nombre';
					break;
				}
				case 'marca': {
					$tmp = Marca::where('nombre', 'like', '%'.$tag.'%')->first();
					$search_for = 'id_marca';	$return = 'id';	$show = 'nombre';
					break;
				}
				case 'transmision': {
					$tmp = Transmision::where('nombre', 'like', '%'.$tag.'%')->first();
					$search_for = 'id_transmision'; $return = 'id'; $show = 'nombre';
					break;
				}
				case 'motor': {
					$tmp = TiposMotor::where('nombre', 'like', '%'.$tag.'%')->first();
					$search_for = 'id_tipo_motor'; $return = 'id'; $show = 'nombre';
					break;
				}
				case 'modelo': {
					$tmp = Vehiculo::where('modelo', 'like', '%'.$tag.'%')->orderBy('modelo')->first();
					$search_for = 'modelo'; $return = 'modelo'; $show = 'modelo';
					break;
				}
				case 'year': {
					$tmp = Vehiculo::where('year', 'like', '%'.$tag.'%')->first();
					$search_for = 'year'; $return = 'year'; $show = 'year';
					break;
				}
				case 'precio': {
					list($minimo,$maximo) = explode(' ', $tag);

					//$publicaciones = Publicacion::where('mostrar',1)->pluck('id_vehiculo')->toArray();
					//$vehiculos = Vehiculo::whereIn('id',$publicaciones);
					if($minimo>0 and $maximo>0){
						//$tmp = $vehiculos->where('precio_venta','>=',$minimo)->where('precio_venta','<=',$maximo)->get();
						$show = '$'.$minimo.' a $'.$maximo;
					} else if ($minimo == 0) {
						//$tmp = $vehiculos->where('precio_venta','<=',$maximo)->get();
						$show = 'Hasta $'.$maximo;
					} else if ($maximo == 0) {
						//$tmp = $vehiculos->where('precio_venta','>=',$minimo)->get();
						$show = 'Desde $'.$minimo;
					}

					$aux = true;
					$tmp = new \Illuminate\Database\Eloquent\Collection;
					$search_for = 'precio_venta'; $return = $tag;
					break;
				}
			}
			if($tmp or $aux) {
				$tmp->search_for = $search_for;
				$tmp->return = $return;
				if($aux){
					$tmp->show = $show;
				} else {
					$tmp->show = $tmp->$show;
				}
				$filtred_tags[$fkey] = $tmp;
				$search_keys[$search_for] = $tmp;
			}	else {
				$unset++;
			}
		}
		if($unset > 0) {
			$filter_warning_message = 'Algunos filtros no son válidos.';
		} else {
			$filter_info_message = 'Se aplicaron los filtros correctamente.';
		}

		$dt = new \DateTime();
		$rankSemana = PublicacionVista::select('publicacion_vista.id_publicacion',DB::raw('count(distinct ip) as rank'))
			->where('publicacion_vista.created_at','>',$dt->modify('-1 Week'))
			->join('publicaciones','publicacion_vista.id_publicacion','=','publicaciones.id')
			->where('publicaciones.mostrar', 1)
			->groupBy('id_publicacion')
			->orderBy('rank','desc')
			->limit(3)
			->get();
		$in = '(0,';
		foreach ($rankSemana as $value) {
			$in = $in . $value->id_publicacion . ',';
		}
		$in = rtrim($in,',').')';
		$publicaciones = Publicacion::select('*',DB::raw('IF(id in '.$in.',1,0) as rankSemana'))
			->where('mostrar', 1)
			->orderBy('vistas','desc')
			->get();

		$vehiculos = [];
		$real_publicaciones = array();
		foreach ($publicaciones as $publicacion) {
			$vehiculo = $publicacion->vehiculo;
			$filter = true;
			foreach($search_keys as $skey=>$sk){
				if($skey == 'precio_venta'){
					list($minimo,$maximo) = explode(' ', $sk->return);
					if($minimo>0 and $maximo>0){
						if($vehiculo->precio_venta < doubleval($minimo) or $vehiculo->precio_venta > doubleval($maximo) ){
							$filter = false;
						}
					} else if ($minimo == 0) {
						if($vehiculo->precio_venta > doubleval($maximo) ){
							$filter = false;
						}
					} else if ($maximo == 0) {
						if($vehiculo->precio_venta < doubleval($minimo) ){
							$filter = false;
						}
					}

				} else {
					$ret = $sk->return;
					if($vehiculo->$skey != $sk->$ret) {
						$filter = false;
					}
				}

			}
			if($filter) {
				$real_publicaciones[] = $publicacion;
				array_push($vehiculos,$publicacion->id_vehiculo);
			}
		}
		unset($search_keys['precio_venta']);


		$modelos = array();
		foreach($real_publicaciones as $publicacion) {
			$v = $publicacion->vehiculo;
			if(!array_key_exists($v->modelo, $modelos)) {
				$modelos[$v->modelo] = 1;
			}
			else {
				$modelos[$v->modelo]++;
			}
		}

		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();
		if(count($real_publicaciones)==1){


			$titulo = $real_publicaciones[0]->titulo;

			$titulo = trim($titulo);
			$titulo = str_replace(
					array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
					array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
					$titulo
			);
			$titulo = str_replace(
					array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
					array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
					$titulo
			);
			$titulo = str_replace(
					array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
					array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
					$titulo
			);
			$titulo = str_replace(
					array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
					array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
					$titulo
			);
			$titulo = str_replace(
					array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
					array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
					$titulo
			);
			$titulo = str_replace(
					array('ñ', 'Ñ', 'ç', 'Ç'),
					array('n', 'N', 'c', 'C'),
					$titulo
			);

			$titulo = strtolower($titulo);
			$titulo = str_replace(' ', '-', $titulo);

			return $response->withRedirect($this->router->pathFor('vehicle.ver',[
					'id' => $real_publicaciones[0]->id,
					'titulo' => $titulo,
					'paginasMarca'=> $paginaMarcas,
				])
			);
		} else if(count($real_publicaciones)==0) {
			$this->flash->addMessage('info', 'No se encontraron vehiculos similares, vuelva a realizar la busqueda.');
			return $response->withRedirect($this->router->pathFor('home'));
		} else {
			$marcas = Marca::select('veh_listado-marcas.nombre',DB::raw('count(distinct vehiculos.id ) as cantidad'))
				->join('vehiculos','veh_listado-marcas.id','vehiculos.id_marca')
				->whereIn('vehiculos.id',$vehiculos)
				->groupBy('veh_listado-marcas.id')
				->orderBy('veh_listado-marcas.nombre', 'asc')
				->get();
			$localidades = Localidad::select('localidades.nombre',DB::raw('count(distinct vehiculos.id ) as cantidad'))
				->join('vehiculos','localidades.id','vehiculos.id_localidad')
				->whereIn('vehiculos.id',$vehiculos)
				->groupBy('localidades.id')
				->orderBy('localidades.nombre', 'asc')
				->get();
			$transmisiones = Transmision::select('veh_tipos-transmision.nombre',DB::raw('count(distinct vehiculos.id ) as cantidad'))
				->join('vehiculos','veh_tipos-transmision.id','vehiculos.id_transmision')
				->whereIn('vehiculos.id',$vehiculos)
				->groupBy('veh_tipos-transmision.id')
				->orderBy('veh_tipos-transmision.nombre', 'asc')
				->get();
			$motores = TiposMotor::select('veh_tipos-motor.nombre',DB::raw('count(distinct vehiculos.id ) as cantidad'))
				->join('vehiculos','veh_tipos-motor.id','vehiculos.id_tipo_motor')
				->whereIn('vehiculos.id',$vehiculos)
				->groupBy('veh_tipos-motor.id')
				->orderBy('veh_tipos-motor.nombre', 'asc')
				->get();
			$years = Vehiculo::select('year',DB::raw('count(id) as cantidad'))
				->whereIn('id',$vehiculos)
				->groupBy('year')
				->orderBy('year','desc')
				->get();

			$extra_modules = array(
				array("module"=>"search_mobile", "css"=>array("/dist/css/search_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/search_mobile.twig", "icon"=>"fa fa-search", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Buscar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileSearch")))),
				/*array("module"=>"filter_mobile", "css"=>array("/dist/css/filter_mobile.min.css"), "settings"=>array("section"=>"nav-mobile-buttons", "view"=>"guest_views/modules/filter_mobile.twig", "icon"=>"fa fa-filter", "attrs"=>array(array("attr_name"=>"title", "attr_value"=>"Filtrar"), array("attr_name"=>"class", "attr_value"=>"nav-link openMobileFilter")))),*/
			);

			return $this->container->view->render($response, 'guest_views/vehiculos/filtrar.twig',[
				'publicaciones'=>$real_publicaciones,
				'marcas'=>$marcas,
				'localidades'=>$localidades,
				'transmisiones'=>$transmisiones,
				'motores'=>$motores,
				'years'=>$years,
				'modelos'=>$modelos,
				'filtros'=>$filtred_tags,
				'filtros_originales'=>$args['params'],
				'filter_warning_message'=>$filter_warning_message,
				'filter_info_message'=>$filter_info_message,
				'search_keys'=>$search_keys,
				'pagina_numero'=>$pagina_numero,
				'paginasMarca'=> $paginaMarcas,
				'extra_modules'=>$extra_modules
			]);
		}


	}


	public function getFAQS($request, $response) {
		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();
		return $this->container->view->render($response, 'guest_views/faqs/faqs.twig',[

			'paginasMarca'=> $paginaMarcas,
		]);
	}

	public function getNosotros($request, $response) {
		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();
		return $this->container->view->render($response, 'guest_views/nosotros/nosotros.twig',[

			'paginasMarca'=> $paginaMarcas,
		]);
	}

	public function getContacto($request, $response) {
		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();
		return $this->container->view->render($response, 'guest_views/contacto/contacto.twig',[

			'paginasMarca'=> $paginaMarcas,
		]);
	}

	public function getVendeTuAuto($request, $response) {
		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();
		if($this->container->auth->check()){
			$id_usuario = auth::individuo()->id_usuario;
			$vehiculos = Vehiculo::leftJoin('usados_terceros','vehiculos.id','=','usados_terceros.id_vehiculo')->where('eliminado',0)->where('id_usuario',$id_usuario)->get();
			return $this->container->view->render($response, 'guest_views/vehiculos/vender.twig', [
				'marcas'=>Marca::orderBy('nombre', 'asc')->get(),

				'localidades'=>Localidad::orderBy('nombre', 'asc')->get(),
				'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
				'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
				'provincias'=>Provincia::orderBy('nombre', 'asc')->get(),
				'ubicaciones'=>Ubicacion::orderBy('nombre', 'asc')->get(),
				'estados'=>EstadosVeh::orderBy('nombre', 'asc')->get(),
				'paginasMarca'=> $paginaMarcas,
				'vehiculos' => $vehiculos,
			]);
		} else {
			$this->flash->addMessage('info', 'Para publicar tu vehículo, es necesario que inicies sesión.');
			return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('auth.signin',['return_to'=>'vendetuauto']));
		}

	}

	public function compress_image($source_url, $destination_url, $quality)
	{

		$info = getimagesize($source_url);
		ini_set('gd.jpeg_ignore_warning', 1);
		if ($info['mime'] == 'image/jpeg') {
			$image = @imagecreatefromjpeg($source_url);
			if(!$image) {
				$image = imagecreatefromstring(file_get_contents($source_url));
			}
		}


		elseif ($info['mime'] == 'image/gif')
		$image = imagecreatefromgif($source_url);

		elseif ($info['mime'] == 'image/png')
		$image = imagecreatefrompng($source_url);

		imagejpeg($image, $destination_url, $quality);
		return $destination_url;

	}

	public function postRecibirInformacionVehiculo($request, $response) {
		if($request->isXhr()){

			$vehiculo = Vehiculo::Create([
				'id_marca'=>$request->getParam('form_sell_car_info_marca'),
				'modelo'=>$request->getParam('form_sell_car_info_modelo'),
				'year'=>$request->getParam('form_sell_car_info_year'),
				'motor'=>$request->getParam('form_sell_car_info_motor'),
				'id_tipo_motor'=>$request->getParam('form_sell_car_info_tipomotor'),
				'id_transmision'=>$request->getParam('form_sell_car_info_transmision'),
				'cantidad_puertas'=>$request->getParam('form_sell_car_info_cantidadpuertas'),
				'id_localidad'=>$request->getParam('form_sell_car_info_localidad'),
				'id_ubicacion'=>-1,
				'entrega_minima'=>-1,
				'precio_venta'=>$request->getParam('form_sell_car_info_precioventa'),
				'estado_vehiculo'=>3,
				'id_usuario' => auth::individuo()->id_usuario,
			]);

			$dominio_vehiculo = str_replace(array(' ', '-'), '', trim($request->getParam('form_sell_car_info_dominio')));

			$usado = UsadoTercero::Create([
				'dominio'=>$dominio_vehiculo,
				'id_vehiculo'=>$vehiculo->id,
				'kilometraje'=>$request->getParam('form_sell_car_info_kilometraje'),
				'id_owner'=>$_SESSION['userid'],
				'observaciones'=>$request->getParam('form_sell_car_info_observaciones'),
				'color'=>$request->getParam('form_sell_car_info_color'),
			]);


			$error = false;
			$err_desc = "";
			$files = array();

			$uploaddir = './images/uploads/';
			$factory = new \RandomLib\Factory;
			$generator = $factory->getMediumStrengthGenerator();
			$uuid = $generator->generateString(64,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
			$a = 1;
			foreach($_FILES as $file)
			{
				$extension = pathinfo(parse_url($file['name'])['path'], PATHINFO_EXTENSION);

				$nuevo_nombre = $uuid.'-'.$a.'.jpg';

				$fichero = $uploaddir.basename($nuevo_nombre);
				while(file_exists($fichero)) {
					$uuid = $generator->generateString(64,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
					$nuevo_nombre = $uuid.'-'.$a.'.jpg';
					$fichero = $uploaddir.basename($nuevo_nombre);
				}

				$moved = move_uploaded_file($file['tmp_name'], $uploaddir.basename($nuevo_nombre));
				if($moved)
				{
					$this->compress_image($uploaddir.basename($nuevo_nombre), $uploaddir.basename($nuevo_nombre), 80);
					$files[] = $uploaddir .$nuevo_nombre;
				}
				else
				{
					$error = true;
					$err_desc = $file['error'];
				}
				$a++;
			}
			$data = ($error) ? array('success' => false, 'error_desc'=>$err_desc) : array('success' => true, 'files' => $files);

			if($data['success'] == true) {
				$archivos = $data['files'];
				$orden = ImagenesVeh::where('id_vehiculo',$id_vehiculo)->get()->count();
				if(is_array($archivos)) {
					foreach ($archivos as $key=>$value) {
						$orden = $orden + 1;
						$archivo = str_replace('./', '/', $value);
						ImagenesVeh::Create([
							'id_vehiculo'=>$vehiculo->id,
							'archivo'=>$archivo,
							'orden'=>$orden,
							'id_usuario'=>$_SESSION['userid']
						]);
					}
				}
				else {
					$archivo = str_replace('./', '/', $archivos);
					ImagenesVeh::Create([
						'id_vehiculo'=>$vehiculo->id,
						'archivo'=>$archivo,
						'orden'=>$orden+1,
						'id_usuario'=>$_SESSION['userid']
					]);
				}

				return $response->withJson([
					'success'=>true
				]);
			}
		}

		return $response->withJson([
			'success'=>false,
		]);
	}

	public function get0km($request,$response){
		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();

		return $response->withStatus(200)->withJson($paginaMarcas);
	}

	public function eliminarTercero($request,$response,$args){
		$id_vehiculo = $args['id'];
		$id_usuario = auth::individuo()->id_usuario;
		$vehiculo = Vehiculo::where('eliminado',0)->where('id_usuario',$id_usuario)->where('id',$id_vehiculo)->get();
		if($vehiculo){
			Vehiculo::where('id',$id_vehiculo)->update([
				'eliminado' => 1,
			]);

			Publicacion::where('id_vehiculo', $id_vehiculo)
			->where('mostrar',1)
			->update(['mostrar' => 0]);

			VehiculoHistorial::Create([
				'id_vehiculo' => $args['id'],
				'id_usuario' => $_SESSION['userid'],
				'descripcion' => 'Vehiculo Tercero Eliminado',
				'id_estado' => 4,
			]);

			$this->flash->addMessage('info', 'Vehiculo Eliminado con Exito. La publicacion asosiada tambien fue dado de baja, si es que esta en curso.');
		} else {
			$this->flash->addMessage('info', 'Vehiculo no encontrado.');
		}
		return $response->withRedirect($this->router->pathFor('vendetuauto'));
	}

}
