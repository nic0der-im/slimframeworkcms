<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Funcionalidades\OneSignal;

use App\Models\Negocio\Cliente;
use App\Models\Negocio\Datero;
use App\Models\Negocio\CarpetaCliente;
use App\Models\Negocio\CarpetaVehiculo;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\VehiculoHistorial;
use App\Models\Vehiculos\Usado;
use App\Models\Vehiculos\UsadoTercero;

use App\Models\Vehiculos\Marca;
use App\Models\Vehiculos\Transmision;
use App\Models\Vehiculos\TiposMotor;
use App\Models\Vehiculos\EstadosVeh;
use App\Models\Vehiculos\ImagenesVeh;
use App\Models\Vehiculos\TipoImagenVeh;

use App\Models\Vehiculos\Creditos;
use App\Models\Vehiculos\CreditosFijos;
use App\Models\Vehiculos\TiposEstado;

use App\Models\Keywords\Keywords;

use App\Models\Transacciones\TipoDocumento;
use App\Models\Notificaciones\Notificaciones;

use App\Models\Extras\Provincia;
use App\Models\Extras\Ubicacion;
use App\Models\Extras\Localidad;
use App\Models\Publicacion;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;
use App\Auth\auth;

class VehicleController extends Controller {
	public function index($request, $response, array $args)
	{
		//$vehiculos = Vehiculo::orderBy('created_at')->get();
		$vehiculos = Vehiculo::leftJoin('usados', function($join) {
      	$join->on('id', '=', 'usados.id_vehiculo');
    		})->where('eliminado','=',0)
    		->whereDoesntHave('getEstadoLista',function($subQuery) {
	    $subQuery->where("estado", 0);
	})
    ->orderBy('vehiculos.created_at')->get();

		foreach($vehiculos as $key=>$vehiculo){

			$imagenes = $vehiculo->getFotos;

			foreach($imagenes as $imagen){

				if($imagen->estado == 2) { continue; }
				$url = '../public'.$imagen->archivo;
				if(!file_exists($url)) {
					ImagenesVeh::find($imagen->id)->delete();
				}
				else {
					unset($imagenes[$key]);
				}
			}
		}

		$vista = 1;
		if(!is_null($request->getQueryParam('vista'))){
			$vista = $request->getQueryParam('vista');
		}

		$client = new \GuzzleHttp\Client();
		$res = $client->request('GET', 'https://www.bancogalicia.com/cotizacion/cotizar?currencyId=02&quoteType=SU&quoteId=999', []);
		$json = json_decode($res->getBody(), true);
		$precio = $json["sell"];
		if($precio == NULL)
		{
			$precio = 0;
		}

		return $this->container->view->render($response, 'admin_views/vehicles/index.twig',[
			'vehiculos'=>$vehiculos,
			'vista'=>$vista,
			'dolar_precio'=> $precio,
			'dolar_historico'=>41.60,
		]);
	}

	public function keywords($request, $response, $args)
	{
		$vehiculo = Vehiculo::find($args['id']);
		$usado = Usado::where('id_vehiculo', $args['id'])->first();

		$params = array();
		if($usado) { $params['usado'] = $usado; }
		$params['vehiculo'] = $vehiculo;
		$params['vehiculo']['marca'] = Marca::find($vehiculo->id_marca)->nombre;
		$params['vehiculo']['tipo_motor'] = TiposMotor::find($vehiculo->id_tipo_motor)->nombre;
		$params['vehiculo']['transmision'] = Transmision::find($vehiculo->id_transmision)->nombre;
		$params['vehiculo']['localidad'] = Localidad::find($vehiculo->id_localidad)->nombre;
		$params['vehiculo']['estado'] = EstadosVeh::find($vehiculo->estado_vehiculo)->nombre;

		return $this->container->view->render($response, 'admin_views/vehicles/keywords.twig', [
			'params'=>$params,
			'keywords'=>Keywords::all(),
		]);
	}

	public function financiar($request, $response, $args)
	{
		$vehiculo = Vehiculo::find($args['id']);
		$usado = Usado::where('id_vehiculo', $args['id'])->first();

		$params = array();
		if($usado) { $params['usado'] = $usado; }
		$params['vehiculo'] = $vehiculo;
		$params['vehiculo']['marca'] = Marca::find($vehiculo->id_marca)->nombre;
		$params['vehiculo']['tipo_motor'] = TiposMotor::find($vehiculo->id_tipo_motor)->nombre;
		$params['vehiculo']['transmision'] = Transmision::find($vehiculo->id_transmision)->nombre;
		$params['vehiculo']['localidad'] = Localidad::find($vehiculo->id_localidad)->nombre;
		$params['vehiculo']['estado'] = EstadosVeh::find($vehiculo->estado_vehiculo)->nombre;

		$imagenes = ImagenesVeh::where('id_vehiculo', $args['id'])->get();
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

		return $this->container->view->render($response, 'admin_views/vehicles/financiar.twig', [
			'params'=>$params,
			'keywords'=>Keywords::all(),
		]);
	}




	public function tercerosIndex($request, $response)
	{
		$usados = UsadoTercero::where('borrado', 0)->orderBy('created_at','ASC')->get();
		return $this->container->view->render($response, 'admin_views/vehicles/terceros.twig', [
			'usados'=>$usados,
		]);
	}

	public function tercerosBorrar($request, $response, array $args)
	{
		$usado = UsadoTercero::where('id_vehiculo', $args['id'])->update(['borrado' => 1]);

		$vehiculo = Vehiculo::find($args['id']);
		$vehiculo->eliminado = 1;
		$vehiculo->save();

		$imagenes = ImagenesVeh::where('id_vehiculo',$vehiculo->id)->where('estado',1)->get();
		foreach ($imagenes as $item) {
			if(file_exists($item->archivo)){
		    unlink($item->archivo);
			}
			ImagenesVeh::where('id',$item->id)->update([
				'estado'=>0,
			]);
		}
		Publicacion::where('id_vehiculo', '=', $args['id'])->delete();

		$this->flash->addMessage('info', 'El vehículo de tercero fue borrado correctamente.');
		$url = $this->router->pathFor('vehicle.terceros');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getCargar($request, $response)
	{
		if($request->getQueryParam('error',0)>0){
			return $this->container->view->render($response, 'admin_views/vehicles/cargar.twig', [
				'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
				'localidades'=>Localidad::orderBy('nombre', 'asc')->get(),
				'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
				'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
				'provincias'=>Provincia::orderBy('nombre', 'asc')->get(),
				'ubicaciones'=>Ubicacion::orderBy('nombre', 'asc')->get(),
				'estados'=>EstadosVeh::orderBy('nombre', 'asc')->get(),
				'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
				'return_to' => $request->getQueryParam('return_to',null),
			]);
		}
		$return_to = $request->getQueryParam('return_to',null);
		$id_cliente = $request->getQueryParam('id_cliente',0);
		$old = null;
		if($id_cliente>0){
			$cliente = Cliente::selectRaw('
				id as id_cliente,
				CONCAT(nombre," ",apellido) as exowner,
				id_tipo_documento,
				documento,
				fecha_nacimiento,
				telefono,
				celular,
				email,
				domicilio,
				numero,
				piso,
				domicilio_observaciones,
				localidad,
				id_provincia,
				cp,
				observaciones
				')->where('id',$id_cliente)->where('estado',1)->first();
			if($cliente){
				$old = $cliente;
				$id_carpeta = $request->getQueryParam('id_carpeta',0);
				if(!is_null($id_carpeta)){
					$old->id_carpeta = $id_carpeta;
				}
			}
		}
		return $this->container->view->render($response, 'admin_views/vehicles/cargar.twig', [
			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
			'localidades'=>Localidad::orderBy('nombre', 'asc')->get(),
			'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
			'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
			'provincias'=>Provincia::orderBy('nombre', 'asc')->get(),
			'ubicaciones'=>Ubicacion::orderBy('nombre', 'asc')->get(),
			'estados'=>EstadosVeh::orderBy('nombre', 'asc')->get(),
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'old' => $old,
			'return_to' => $return_to,
		]);
	}

	public function postCargar($request, $response) {
		$id_usuario = $_SESSION['userid'];
		$return_to = $request->getParam('return_to',null);
		$id_carpeta = $request->getParam('id_carpeta',0);
		$id_cliente = $request->getParam('id_cliente',0);
		$id_precios_vehiculos = $request->getParam('id_precios_vehiculos',0);
		$validation = $this->validator->validate($request, [
			'modelo'=>v::alnum('.'),
			'year'=>v::date('Y'),
			'dominio'=>v::alnum(),
			'kilometraje'=>v::numeric(),
			'exowner'=>v::alpha(),
			'motor'=>v::numeric(),
			'cantidad_puertas'=>v::numeric(),
			'precio_revista'=>v::numeric(),
			'precio_toma'=>v::numeric(),
			'precio_reparacion_estimado'=>v::numeric(),
			'precio_venta'=>v::numeric(),
			'precio_lista'=>v::numeric(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('vehicle.cargar',
				[],
				[
					'error'=>1,
					'return_to'=>$return_to,
				]
			));
		}
		try 
		{
			$dt = new \Datetime();
			DB::beginTransaction();
			$vehiculo = Vehiculo::Create([
				'id_marca'=>$request->getParam('id_marca'),
				'modelo'=>$request->getParam('modelo'),
				'year'=>$request->getParam('year'),
				'motor'=>$request->getParam('motor'),
				'id_tipo_motor'=>$request->getParam('id_tipo_motor'),
				'id_transmision'=>$request->getParam('id_transmision'),
				'cantidad_puertas'=>$request->getParam('cantidad_puertas'),
				'id_localidad'=>$request->getParam('id_localidad'),
				'id_ubicacion'=>$request->getParam('id_ubicacion'),
				'entrega_minima'=>$request->getParam('entrega_minima'),
				'precio_venta'=>$request->getParam('precio_venta'),
				'precio_lista'=>$request->getParam('precio_lista'),
				'estado_vehiculo'=>$request->getParam('estado_vehiculo'),
				'id_usuario'=>$id_usuario,
				'id_precios_vehiculos'=>$id_precios_vehiculos,
			]);

			$id_tipo_documento = $request->getParam('id_tipo_documento');
			$id_tipo_responsable = $request->getParam('id_tipo_responsable');
			$documento = $request->getParam('documento');
			$fecha_nacimiento = $request->getParam('fecha_nacimiento');
			$telefono = preg_replace('/[^0-9]/', '', $request->getParam('telefono',0) );
			$celular = preg_replace('/[^0-9]/', '', $request->getParam('celular',0) );
			$email = $request->getParam('email');
			$domicilio = $request->getParam('domicilio');
			$numero = $request->getParam('numero');
			$piso = $request->getParam('piso');
			$dpto  = $request->getParam('dpto');
			$domicilio_observaciones = $request->getParam('domicilio_observaciones');
			$localidad = $request->getParam('localidad');
			$id_ciudad = $request->getParam('id_ciudad');
			$cp = $request->getParam('cp');
			$id_provincia = $request->getParam('id_provincia');

			$usado = Usado::Create([
				'dominio'=>$request->getParam('dominio'),
				'id_vehiculo'=>$vehiculo->id,
				'kilometraje'=>$request->getParam('kilometraje'),
				'exowner'=>$request->getParam('exowner'),
				'observaciones'=>$request->getParam('observaciones'),
				'color'=>$request->getParam('color'),
				'precio_revista'=>$request->getParam('precio_revista'),
				'precio_toma'=>$request->getParam('precio_toma'),
				'precio_reparacion_estimado'=>$request->getParam('precio_reparacion_estimado'),
				'email' =>$email ,
				'id_tipo_documento' =>$id_tipo_documento ,
				'documento' =>$documento ,
				'telefono' =>$telefono ,
				'celular' =>$celular ,
				'domicilio' =>$domicilio ,
				'numero' =>$numero ,
				'piso' =>$piso,
				'dpto' =>$dpto ,
				'id_provincia' =>$id_provincia ,
				'localidad' =>$localidad ,
				'cp' =>$cp ,
				'domicilio_observaciones' =>$domicilio_observaciones ,
				'id_proveedor' =>$request->getParam('id_proveedor') ,
				'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento) ,
				'sexo' =>$request->getParam('sexo') ,
				'id_cliente'=>$id_cliente,
			]);

			if($id_cliente>0){
				Cliente::where('id',$id_cliente)->update([
					'id_tipo_documento' => $id_tipo_documento,
					'documento' => $documento,
					'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento),
					'telefono' => $telefono,
					'celular' => $celular,
					'email' => $email,
					'domicilio' => $domicilio,
					'numero' => $numero,
					'piso' => $piso,
					'domicilio_observaciones' => $domicilio_observaciones,
					'localidad' => $localidad,
					'cp' => $cp,
					'id_provincia' => $id_provincia,
				]);
			}

			if($id_carpeta>0){
				CarpetaVehiculo::create([
					'id_vehiculo' => $vehiculo->id,
					'id_carpeta' => $id_carpeta,
					'id_usuario' => $id_usuario,
				]);
			} else {
				$api = OneSignal::api();
				try{
					$title = 'Usado 🚘 Nuevo '.$vehiculo->getMarca->nombre;
					$titulo = 'Usado Nuevo '.$vehiculo->getMarca->nombre;
					$message = $vehiculo->modelo.' '.$vehiculo->year.' '.$usado->dominio.' KM:'.$usado->kilometraje.' PV:'.$vehiculo->precio_venta.' Click para mas informacion.';
					$url = $request->getUri()->getBaseUrl().$this->router->pathFor('vehicle.getCar',
								[
									'id'=>$vehiculo->id,
								]
							);
					$res = $api->post('notifications',[
						'json'=> [
							"app_id" => "78034361-ab14-4018-a430-6be0744c770a",
							//"include_player_ids"=> ["588ec321-3e30-4f62-9d30-d088bccd5d3b"],
							"filters" => [
								[
									"field" => "tag",
									"key" => "sector",
									"relation" => "=",
									"value" => "administracion",
								],
							],
							'headings' => ['en'=>$title,'es'=>$title],
							"data"=> ["modulo"=> "vehiculo","estado"=> "nuevo"],
							'url'=> $url,
							"contents"=> ["en"=> $message, 'es'=>$message],
							]
					]);
					$json = json_decode($res->getBody(), true);
					Notificaciones::create([
						'prioridad' => 1,
						'mensaje' => $message,
						'titulo' => $titulo,
						'url' => $url,
					]);
					$this->flash->addMessage('warning', 'Notificacion enviada. '.$json['id'].'. Recipientes '.$json['recipients']);
				} catch (\Exception $e){
					$this->flash->addMessage('warning', 'La notificacion no pudo ser enviada: '.$e->getMessage());
				}
			}
			VehiculoHistorial::create([
				'id_estado' => '1',
				'descripcion' => 'Nuevo vehículo cargado',
				'id_usuario' => $id_usuario,
				'id_vehiculo' => $vehiculo->id
			]);
			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    DB::rollBack();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
			return $response->withRedirect($this->router->pathFor('vehicle.cargar',
				[],
				[
					'id_carpeta'=>$id_carpeta,
					'id_cliente'=>$id_cliente,
					'return_to'=>$return_to,
				]
			));
		}

		$this->flash->addMessage('info', 'Vehículo cargado exitosamente.');
		if(!is_null($return_to)){
			return $response->withRedirect('/'.$return_to);
		}
		return $response->withRedirect($this->router->pathFor('vehicle.index'));

	}

	public function getCar($request, $response, array $args)
	{
		$id_vehiculo = $args['id'];
		$vehiculo = Vehiculo::find($id_vehiculo);
		$historial = VehiculoHistorial::where('id_vehiculo',$id_vehiculo)->where('estado',1)->orderBy('id','desc')->get();
		$dateros = Datero::where('id_vehiculo',$id_vehiculo)->orderBy('created_at','desc')->get();
		$publicaciones = Publicacion::where('id_vehiculo',$id_vehiculo)->orderBy('id','desc')->get();
		return $this->container->view->render($response, 'admin_views/vehicles/view.twig', [
			'vehiculo'=>$vehiculo,
			'historial' => $historial,
			'dateros' => $dateros,
			'publicaciones' => $publicaciones,
		]);
	}

	public function getPhotoUploader($request, $response, array $args) {

		$vehiculo = Vehiculo::find($args['id']);
		$usado = Usado::where('id_vehiculo', $args['id'])->first();

		$params = array();

		if($usado) {
			$params['usado'] = $usado;
		}

		$params['vehiculo'] = $vehiculo;

		$params['vehiculo']['marca'] = Marca::find($vehiculo->id_marca)->nombre;
		$params['vehiculo']['tipo_motor'] = TiposMotor::find($vehiculo->id_tipo_motor)->nombre;
		$params['vehiculo']['transmision'] = Transmision::find($vehiculo->id_transmision)->nombre;
		$params['vehiculo']['localidad'] = Localidad::find($vehiculo->id_localidad)->nombre;
		$params['vehiculo']['estado'] = EstadosVeh::find($vehiculo->estado_vehiculo)->nombre;

		return $this->container->view->render($response, 'admin_views/vehicles/upload-photo.twig', [
			'params'=>$params
		]);
	}

	public function postPhotoUploader($request, $response, $args) {

		$id_vehiculo = $args['id'];

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

		$count = 0;
		if($data['success'] == true) {
			$archivos = $data['files'];
			$orden = ImagenesVeh::where('id_vehiculo',$id_vehiculo)->get()->count();
			if(is_array($archivos)) {
				foreach ($archivos as $key=>$value) {
					$orden = $orden + 1;
					$archivo = str_replace('./', '/', $value);
					ImagenesVeh::Create([
						'id_vehiculo'=>$id_vehiculo,
						'archivo'=>$archivo,
						'orden'=>$orden,
						'id_usuario'=>$_SESSION['userid']
					]);
					$count = $count + 1;
				}
			} else {
				$count = 1;
				$archivo = str_replace('./', '/', $archivos);
				ImagenesVeh::Create([
					'id_vehiculo'=>$id_vehiculo,
					'archivo'=>$archivo,
					'orden'=>$orden+1,
					'id_usuario'=>$_SESSION['userid']
				]);
			}
			VehiculoHistorial::Create([
				'id_vehiculo' => $id_vehiculo,
				'id_usuario' => $_SESSION['userid'],
				'descripcion' => 'Se han agregado '.$count.' fotos nuevas.',
				'id_estado' => 4,
			]);
			$this->flash->addMessage('info', 'Todas las fotos han sido cargadas exitosamente.');
		} else {
			$this->flash->addMessage('error', 'A ocurrido un problema.');
		}

		echo json_encode(array("success"=>true));
	}





	public function getEditor($request, $response, $args) {
		$return_to = $request->getQueryParam('return_to',null);
		$old = Vehiculo::select(
				'vehiculos.id as id_vehiculo',
				DB::raw('IFNULL(vehiculos.id_marca,"") as id_marca'),
				DB::raw('IFNULL(vehiculos.modelo,"") as modelo'),
				DB::raw('IFNULL(vehiculos.year,"") as year'),
				DB::raw('IFNULL(vehiculos.motor,"") as motor'),
				DB::raw('IFNULL(vehiculos.id_tipo_motor,"") as id_tipo_motor'),
				DB::raw('IFNULL(vehiculos.id_transmision,"") as id_transmision'),
				DB::raw('IFNULL(vehiculos.cantidad_puertas,"") as cantidad_puertas'),
				DB::raw('IFNULL(vehiculos.id_localidad,"") as id_localidad'),
				DB::raw('IFNULL(vehiculos.id_ubicacion,"") as id_ubicacion'),
				DB::raw('IFNULL(vehiculos.entrega_minima,"") as entrega_minima'),
				DB::raw('IFNULL(vehiculos.precio_venta,"") as precio_venta'),
				DB::raw('IFNULL(vehiculos.precio_lista,"") as precio_lista'),
				DB::raw('IFNULL(vehiculos.estado_vehiculo,"") as estado_vehiculo'),
				DB::raw('IFNULL(vehiculos.id_precios_vehiculos,"") as id_precios_vehiculos'),
				DB::raw('IFNULL(usados.dominio,"") as dominio'),
				DB::raw('IFNULL(usados.id_vehiculo,"") as id_vehiculo'),
				DB::raw('IFNULL(usados.kilometraje,"") as kilometraje'),
				DB::raw('IFNULL(usados.exowner,"") as exowner'),
				DB::raw('IFNULL(usados.observaciones,"") as observaciones'),
				DB::raw('IFNULL(usados.color,"") as color'),
				DB::raw('IFNULL(usados.precio_revista,"") as precio_revista'),
				DB::raw('IFNULL(usados.precio_toma,"") as precio_toma'),
				DB::raw('IFNULL(usados.precio_reparacion_estimado,"") as precio_reparacion_estimado'),
				DB::raw('IFNULL(usados.email,"") as email'),
				DB::raw('IFNULL(usados.id_tipo_documento,"") as id_tipo_documento'),
				DB::raw('IFNULL(usados.documento,"") as documento'),
				DB::raw('IFNULL(usados.telefono,"") as telefono'),
				DB::raw('IFNULL(usados.celular,"") as celular'),
				DB::raw('IFNULL(usados.domicilio,"") as domicilio'),
				DB::raw('IFNULL(usados.numero,"") as numero'),
				DB::raw('IFNULL(usados.piso,"") as piso'),
				DB::raw('IFNULL(usados.dpto,"") as dpto'),
				DB::raw('IFNULL(usados.id_provincia,"") as id_provincia'),
				DB::raw('IFNULL(usados.localidad,"") as localidad'),
				DB::raw('IFNULL(usados.cp,"") as cp'),
				DB::raw('IFNULL(usados.domicilio_observaciones,"") as domicilio_observaciones'),
				DB::raw('IFNULL(usados.id_proveedor,"") as id_proveedor'),
				DB::raw('DATE_FORMAT(usados.fecha_nacimiento, \'%d/%m/%Y\') as fecha_nacimiento') ,
				DB::raw('IFNULL(usados.sexo,"") as sexo'),
				DB::raw('IFNULL(tra_proveedores.razon_social,"") as proveedor')
			)
			->leftJoin('usados','vehiculos.id','=','usados.id_vehiculo')
			->leftJoin('tra_proveedores','usados.id_proveedor','=','tra_proveedores.id')
			->where('vehiculos.id',$args['id'])
			->first();
		return $this->container->view->render($response, 'admin_views/vehicles/cargar.twig', [
			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
			'localidades'=>Localidad::orderBy('nombre', 'asc')->get(),
			'transmisiones'=>Transmision::orderBy('nombre', 'asc')->get(),
			'motores'=>TiposMotor::orderBy('nombre', 'asc')->get(),
			'provincias'=>Provincia::orderBy('nombre', 'asc')->get(),
			'ubicaciones'=>Ubicacion::orderBy('nombre', 'asc')->get(),
			'estados'=>EstadosVeh::orderBy('nombre', 'asc')->get(),
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'old' => $old,
			'return_to' => $return_to,
		]);
	}




	public function postEditor($request, $response,$args) {
		$return_to = $request->getParam('return_to',null);
		$id_vehiculo = $args['id'];
		$validation = $this->validator->validate($request, [
			'modelo'=>v::alnum('.'),
			'year'=>v::date('Y'),
			'dominio'=>v::alnum(),
			'kilometraje'=>v::numeric(),
			'exowner'=>v::alpha(),
			'motor'=>v::numeric(),
			'cantidad_puertas'=>v::numeric(),
			'precio_revista'=>v::numeric(),
			'precio_toma'=>v::numeric(),
			'precio_reparacion_estimado'=>v::numeric(),
			'precio_venta'=>v::numeric(),
			'precio_lista'=>v::numeric(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('vehicle.modify',
				[
					'id'=>$id_vehiculo
				],[
					'return_to'=>$return_to,
				]
			));
		}
		try {
			$dt = new \Datetime();
			DB::beginTransaction();

			if($request->getParam('id_precios_vehiculos') != null)
			{
				Vehiculo::where('id',$id_vehiculo)->update([
					'id_marca'=>$request->getParam('id_marca'),
					'modelo'=>$request->getParam('modelo'),
					'year'=>$request->getParam('year'),
					'motor'=>$request->getParam('motor'),
					'id_tipo_motor'=>$request->getParam('id_tipo_motor'),
					'id_transmision'=>$request->getParam('id_transmision'),
					'cantidad_puertas'=>$request->getParam('cantidad_puertas'),
					'id_localidad'=>$request->getParam('id_localidad'),
					'id_ubicacion'=>$request->getParam('id_ubicacion'),
					'entrega_minima'=>$request->getParam('entrega_minima'),
					'precio_venta'=>$request->getParam('precio_venta'),
					'precio_lista'=>$request->getParam('precio_lista'),
					'estado_vehiculo'=>$request->getParam('estado_vehiculo'),
					'id_precios_vehiculos'=>$request->getParam('id_precios_vehiculos')
				]);
			}
			else
			{
				Vehiculo::where('id',$id_vehiculo)->update([
					'id_marca'=>$request->getParam('id_marca'),
					'modelo'=>$request->getParam('modelo'),
					'year'=>$request->getParam('year'),
					'motor'=>$request->getParam('motor'),
					'id_tipo_motor'=>$request->getParam('id_tipo_motor'),
					'id_transmision'=>$request->getParam('id_transmision'),
					'cantidad_puertas'=>$request->getParam('cantidad_puertas'),
					'id_localidad'=>$request->getParam('id_localidad'),
					'id_ubicacion'=>$request->getParam('id_ubicacion'),
					'entrega_minima'=>$request->getParam('entrega_minima'),
					'precio_venta'=>$request->getParam('precio_venta'),
					'precio_lista'=>$request->getParam('precio_lista'),
					'estado_vehiculo'=>$request->getParam('estado_vehiculo'),
				]);
			}

			$id_tipo_documento = $request->getParam('id_tipo_documento');
			$id_tipo_responsable = $request->getParam('id_tipo_responsable');
			$documento = $request->getParam('documento');
			$fecha_nacimiento = $request->getParam('fecha_nacimiento');
			$telefono = preg_replace('/[^0-9]/', '', $request->getParam('telefono') );
			$celular = preg_replace('/[^0-9]/', '', $request->getParam('celular') );
			$email = $request->getParam('email');
			$domicilio = $request->getParam('domicilio');
			$numero = $request->getParam('numero');
			$piso = $request->getParam('piso');
			$dpto  = $request->getParam('dpto');
			$domicilio_observaciones = $request->getParam('domicilio_observaciones');
			$localidad = $request->getParam('localidad');
			$id_ciudad = $request->getParam('id_ciudad');
			$cp = $request->getParam('cp');
			$id_provincia = $request->getParam('id_provincia');

			Usado::where('id_vehiculo',$id_vehiculo)->update([
				'dominio'=>$request->getParam('dominio'),
				'kilometraje'=>$request->getParam('kilometraje'),
				'exowner'=>$request->getParam('exowner'),
				'observaciones'=>$request->getParam('observaciones'),
				'color'=>$request->getParam('color'),
				'precio_revista'=>$request->getParam('precio_revista'),
				'precio_toma'=>$request->getParam('precio_toma'),
				'precio_reparacion_estimado'=>$request->getParam('precio_reparacion_estimado'),
				'email' =>$email ,
				'id_tipo_documento' =>$id_tipo_documento ,
				'documento' =>$documento ,
				'telefono' =>$telefono ,
				'celular' =>$celular ,
				'domicilio' =>$domicilio ,
				'numero' =>$numero ,
				'piso' =>$piso,
				'dpto' =>$dpto ,
				'id_provincia' =>$id_provincia ,
				'localidad' =>$localidad ,
				'cp' =>$cp ,
				'domicilio_observaciones' =>$domicilio_observaciones ,
				'id_proveedor' =>$request->getParam('id_proveedor') ,
				'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento) ,
				'sexo' =>$request->getParam('sexo') ,
			]);

			if($id_cliente>0){

				Cliente::where('id',$id_cliente)->update([
					'id_tipo_documento' => $id_tipo_documento,
					'documento' => $documento,
					'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento),
					'telefono' => $telefono,
					'celular' => $celular,
					'email' => $email,
					'domicilio' => $domicilio,
					'numero' => $numero,
					'piso' => $piso,
					'domicilio_observaciones' => $domicilio_observaciones,
					'localidad' => $localidad,
					'cp' => $cp,
					'id_provincia' => $id_provincia,
				]);
			}

			VehiculoHistorial::Create([
				'id_vehiculo' => $id_vehiculo,
				'id_usuario' => $_SESSION['userid'],
				'descripcion' => 'Modificado desde el Editor del Vehiculo.',
				'id_estado' => 4,
			]);
			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
	    DB::rollBack();
	    $this->flash->addMessage('error', 'Ocurrio un error al registrar los datos. '.$errores);
			return $response->withRedirect($this->router->pathFor('vehicle.modify',
				[
					'id'=>$id_vehiculo
				],[
					'return_to'=>$return_to,
				]
			));
		}

		$this->flash->addMessage('info', 'Vehículo editado exitosamente.'.$request->getParam('id_precios_vehiculos').'id_rio');
		if(!is_null($return_to)){
			return $response->withRedirect('/'.$return_to);
		}
		return $response->withRedirect($this->router->pathFor('vehicle.index'));
	}


	public function getGestionFotos($request, $response, array $args) {
		$vehiculo = Vehiculo::find($args['id']);
		$usado = Usado::where('id_vehiculo', $args['id'])->first();

		$imagenes = ImagenesVeh::where('id_vehiculo', $vehiculo->id)->orderBy('orden','asc')->get();

		return $this->container->view->render($response, 'admin_views/vehicles/photoadmin.twig', [
			'imagenes'=>$imagenes,
			'vehiculo'=>$vehiculo,
			'usado'=>$usado,
			'tiposImagen'=>TipoImagenVeh::where('estado',1)->get(),
		]);
	}

	public function cambiarTipoFoto($request,$response,$args){
		$tipo = $request->getQueryParam('tipo');
		$imagen = ImagenesVeh::where('id', $args['id_imagen'])->update([
			'id_tipo_imagen'=>$tipo,
		]);
		return $response->withStatus(200);
	}

	public function postFotoOrden($request,$response,$args){
		$orden = $request->getParam('orden');
		$iterador = explode(';', $orden);
		$nuevoOrden = 1;

		foreach ($iterador as $clave => $valor) {
			list($id,$orden)=explode(',', $valor);
			ImagenesVeh::where('id',$id)->update([
				'orden'=>$nuevoOrden,
			]);
			$nuevoOrden = $nuevoOrden + 1;
		}

		$this->flash->addMessage('info', 'Fotos ordenadas exitosamente.');
		$url = $this->router->pathFor('vehicle.gestionarfotos', ['id' => $args['id']]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function cambiarEstado($request, $response,$args){
		$id_estado = $request->getQueryParam('id_estado');
		$id_datero = $request->getQueryParam('id_datero',0);
		$observaciones = $request->getQueryParam('observaciones','');
		$id_vehiculo = $args['id'];
		$vehiculo = Vehiculo::find($id_vehiculo);
		$estadoPrevio = $vehiculo->id_estado;
		$vehiculo->id_estado = $id_estado;
		$vehiculo->save();

		if($estadoPrevio == 1){
			Publicacion::where('id_vehiculo', $args['id'])
			->update(['mostrar' => 0]);
		}
		$estadoPrevio = TiposEstado::find($estadoPrevio);
		$estado = TiposEstado::find($id_estado);
		$descripcion = 'Estado cambiado de '.$estadoPrevio->nombre.' a '.$estado->nombre;

		if($id_estado == 2 && $id_datero>0){
			$datero = Datero::where('id',$id_datero)->first();
			$descripcion = 'Datero ID['.$id_datero.'] Titular:'.$datero->apellido.' '.$datero->nombre;
		} else if($id_estado == 3 && !empty($observaciones) ){
			$descripcion = $observaciones;
		}

		VehiculoHistorial::Create([
			'id_vehiculo' => $id_vehiculo,
			'id_usuario' => $_SESSION['userid'],
			'descripcion' => $descripcion,
			'id_estado' => $id_estado,
		]);

		return $response->withRedirect($this->router->pathFor(
			'vehicle.index',
			$args,
			[
				'vista'=> $request->getQueryParam('vista')
			])
		);
	}

	public function eliminar($request, $response, array $args)
	{
		$vehiculo = Vehiculo::find($args['id']);
		$vehiculo->eliminado = 1;
		$vehiculo->save();

		Publicacion::where('id_vehiculo', $args['id'])
		->update(['mostrar' => 0]);

		VehiculoHistorial::Create([
			'id_vehiculo' => $args['id'],
			'id_usuario' => $_SESSION['userid'],
			'descripcion' => 'Vehiculo Eliminado',
			'id_estado' => 4,
		]);

		return $response->withRedirect($this->router->pathFor(
			'vehicle.index',
			$args,
			[
				'vista'=> $request->getQueryParam('vista')
			])
		);
	}

	public function getCalculo($request,$response, array $args){
		// Sistema de calculo de créditos:
		$financiacion = Creditos::where('year', $args['year'])->first();
		$credito = $args['lista'] * ( $financiacion->porcentaje / 100 );

		$fijos = CreditosFijos::orderBy('id', 'DESC')->first();
		$entrega_minima = ( $args['venta'] - $credito) + ( $args['venta'] * ($fijos->transferencia / 100)) + $fijos->otorgamiento + ($credito * ($fijos->prenda / 100));


		return $response->withStatus(200)->withJson([
			'credito' => $credito,
			'entrega_minima' => $entrega_minima,
		]);
	}

	public function getPhotoEliminar($request, $response, array $args)
	{
		$foto = ImagenesVeh::find($args['id']);
		$url = $this->router->pathFor('vehicle.gestionarfotos',[
			'id'=>$foto->id_vehiculo
		]);
		unlink(getcwd().$foto->archivo);
		$foto->delete();
		$this->flash->addMessage('info', "Se eliminó la foto.");
		return $response->withRedirect($url);
	}

	public function postImportarExcel($request,$response, array $args){

		$inputFileName = $request->getParam('excel_file');
		$files = $request->getUploadedFiles();
		$excel_file = $files['excel_file'];
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excel_file->file);
		$worksheet = $spreadsheet->getActiveSheet();

		$highestRow = $worksheet->getHighestRow();
		$highestColumn = $worksheet->getHighestColumn();
		$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

		$modificados = 0;
		$agregados = 0;
		$errores = '';
		for ($row = 2; $row <= $highestRow; ++$row) {
			$id = $worksheet->getCellByColumnAndRow(1, $row)->getValue();

			$dominio = $worksheet->getCellByColumnAndRow(2, $row)->getValue();

			$marca = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
			$id_marca = Marca::where('nombre','like','%'.$marca.'%')->first()->id;

			$modelo = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
			$year = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
			$kilometraje = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
			$color = $worksheet->getCellByColumnAndRow(7, $row)->getValue();

			$localidad = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
			$id_localidad = Localidad::where('nombre','like','%'.$localidad.'%')->first()->id;

			$precio_venta = $worksheet->getCellByColumnAndRow(9, $row)->getValue();
			$precio_lista = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
			$entrega_minima = $worksheet->getCellByColumnAndRow(11, $row)->getValue();
			$motor = $worksheet->getCellByColumnAndRow(12, $row)->getValue();

			$tipo_motor = $worksheet->getCellByColumnAndRow(13, $row)->getValue();
			$id_tipo_motor = TiposMotor::where('nombre','like','%'.$tipo_motor.'%')->first()->id;

			$transmision = $worksheet->getCellByColumnAndRow(14, $row)->getValue();
			$id_transmision = Transmision::where('nombre','like','%'.$transmision.'%')->first()->id;

			$cantidad_puertas = $worksheet->getCellByColumnAndRow(15, $row)->getValue();

			$ubicacion = $worksheet->getCellByColumnAndRow(16, $row)->getValue();
			$id_ubicacion = Ubicacion::where('nombre','like','%'.$ubicacion.'%')->first()->id;

			$estado_vehiculo = $worksheet->getCellByColumnAndRow(17, $row)->getValue();
			$id_estado_vehiculo = EstadosVeh::where('nombre','like','%'.$estado_vehiculo.'%')->first()->id;

			$exowner = $worksheet->getCellByColumnAndRow(18, $row)->getValue();
			$precio_revista = $worksheet->getCellByColumnAndRow(19, $row)->getValue();
			$precio_toma = $worksheet->getCellByColumnAndRow(20, $row)->getValue();
			$precio_reparacion_estimado = $worksheet->getCellByColumnAndRow(21, $row)->getValue();
			$observaciones = $worksheet->getCellByColumnAndRow(22, $row)->getValue();

			if (preg_match('/^([A-ZÑ]{3}\s\d{3}|[A-ZÑ]{2}\s\d{3}\s[A-ZÑ]{2})$/', $dominio)){
				try {
					$usado = Usado::where('dominio','like',$dominio)->first();
					if($usado){
						$id = $usado->id_vehiculo;
					}
    			DB::beginTransaction();
					if(strlen($id)>0){
						Vehiculo::where('id',$id)->update([
							'id_marca' => $id_marca,
							'modelo' => $modelo,
							'year' => $year,
							'motor' => $motor,
							'id_tipo_motor' => $id_tipo_motor,
							'id_transmision' => $id_transmision,
							'cantidad_puertas' => $cantidad_puertas,
							'id_localidad' => $id_localidad,
							'id_ubicacion' => $id_ubicacion,
							'entrega_minima' => $entrega_minima,
							'precio_venta' => $precio_venta,
							'precio_lista' => $precio_lista,
							'estado_vehiculo' => $id_estado_vehiculo,
						]);

						$afectado = Usado::where('id_vehiculo', '=', $id)->update([
							'dominio' => $dominio,
							'kilometraje' => $kilometraje,
							'exowner' => $exowner,
							'observaciones' => $observaciones,
							'color' => $color,
							'precio_revista' => $precio_revista,
							'precio_toma' => $precio_toma,
							'precio_reparacion_estimado' => $precio_reparacion_estimado,
						]);
						if (!$afectado) {
							Usado::create([
								'dominio' => $dominio,
								'id_vehiculo'=>$id,
								'kilometraje' => $kilometraje,
								'exowner' => $exowner,
								'observaciones' => $observaciones,
								'color' => $color,
								'precio_revista' => $precio_revista,
								'precio_toma' => $precio_toma,
								'precio_reparacion_estimado' => $precio_reparacion_estimado,
							]);
						}

						VehiculoHistorial::Create([
							'id_vehiculo' => $id,
							'id_usuario' => $_SESSION['userid'],
							'descripcion' => 'Modificado desde la Importacion Excel.',
							'id_estado' => 4,
						]);

						$modificados++;
					} else {
						$id = Vehiculo::create([
							'id_marca' => $id_marca,
							'modelo' => $modelo,
							'year' => $year,
							'motor' => $motor,
							'id_tipo_motor' => $id_tipo_motor,
							'id_transmision' => $id_transmision,
							'cantidad_puertas' => $cantidad_puertas,
							'id_localidad' => $id_localidad,
							'id_ubicacion' => $id_ubicacion,
							'entrega_minima' => $entrega_minima,
							'precio_venta' => $precio_venta,
							'precio_lista' => $precio_lista,
							'estado_vehiculo' => $id_estado_vehiculo,
							'id_usuario'=>$_SESSION['userid'],
						])->id;

						Usado::create([
							'dominio' => $dominio,
							'id_vehiculo'=>$id,
							'kilometraje' => $kilometraje,
							'exowner' => $exowner,
							'observaciones' => $observaciones,
							'color' => $color,
							'precio_revista' => $precio_revista,
							'precio_toma' => $precio_toma,
							'precio_reparacion_estimado' => $precio_reparacion_estimado,
						]);
						$agregados++;
					}
				DB::commit();
				} catch (\PDOException $e) {
					$errores = $errores . ' Fila '.$row.',';
			    DB::rollBack();
				}
			} else {
				$errores = $errores . ' Dominio '.$dominio.': mal formato,';
			}

		}

		$this->flash->addMessage('info', 'Se logragron modificar '.$modificados.' y agregar '.$agregados.' vehiculos.');
		if(strlen($errores)>0){
			$this->flash->addMessage('error', 'Ocurrieron errores en: '.$errores.' por favor vuelva a exportar el archivo Excel y revisar.');
		}

		return $response->withRedirect($this->router->pathFor(
			'vehicle.index',
			$args,
			[
				'vista'=> 1
			])
		);
	}

	public function getExportarExcel($request,$response){

		//$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('reportes/lista_vehiculos.xls');
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		//$spreadsheet->setActiveSheetIndex(0);
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
		$worksheet = $spreadsheet->getActiveSheet();
		$worksheet->getColumnDimension('A')->setVisible(false);
		$worksheet->getColumnDimension('C')->setAutoSize(true);
		$worksheet->getColumnDimension('D')->setAutoSize(true);
		$worksheet->getColumnDimension('R')->setAutoSize(true);
		$worksheet->getColumnDimension('V')->setAutoSize(true);
		$worksheet->setTitle('Listado');

		$vehiculos = Vehiculo::leftJoin('usados', function($join) {
      $join->on('id', '=', 'usados.id_vehiculo');
	    })
			->where('eliminado','=',0)
			->where('id_estado','!=',3)
			->whereDoesntHave('getEstadoLista',function($subQuery) {
		    $subQuery->where("estado", 0);
		  })
	    ->orderBy('vehiculos.created_at','asc')->get();

    $highestColumnIndex = 16;

    $row = 1;

		$worksheet->getCellByColumnAndRow(1, $row)->setValue('ID');
		$worksheet->getCellByColumnAndRow(2, $row)->setValue('DOMINIO');
		$worksheet->getCellByColumnAndRow(3, $row)->setValue('MARCA');
		$worksheet->getCellByColumnAndRow(4, $row)->setValue('MODELO');
		$worksheet->getCellByColumnAndRow(5, $row)->setValue('AÑO');
		$worksheet->getCellByColumnAndRow(6, $row)->setValue('KM');
		$worksheet->getCellByColumnAndRow(7, $row)->setValue('COLOR');
		$worksheet->getCellByColumnAndRow(8, $row)->setValue('LOCALIDAD');
		$worksheet->getCellByColumnAndRow(9, $row)->setValue('PRECIO VENTA');
		$worksheet->getCellByColumnAndRow(10, $row)->setValue('PRECIO LISTA');
		$worksheet->getCellByColumnAndRow(11, $row)->setValue('ENTREGA MINIMA');
		$worksheet->getCellByColumnAndRow(12, $row)->setValue('CILINDRADA');
		$worksheet->getCellByColumnAndRow(13, $row)->setValue('TIPO MOTOR');
  	$worksheet->getCellByColumnAndRow(14, $row)->setValue('TRANSMICION');
  	$worksheet->getCellByColumnAndRow(15, $row)->setValue('PUERTAS');
  	$worksheet->getCellByColumnAndRow(16, $row)->setValue('UBICACION');
  	$worksheet->getCellByColumnAndRow(17, $row)->setValue('ESTADO VEH');
  	$worksheet->getCellByColumnAndRow(18, $row)->setValue('EXDUEÑO');
  	$worksheet->getCellByColumnAndRow(19, $row)->setValue('PRECIO REVISTA');
  	$worksheet->getCellByColumnAndRow(20, $row)->setValue('PRECIO TOMA');
  	$worksheet->getCellByColumnAndRow(21, $row)->setValue('PRECIO REPARACION');
  	$worksheet->getCellByColumnAndRow(22, $row)->setValue('OBSERVACIONES');


	  $row++;
		$marcas = Marca::pluck('nombre')->toArray();
		$localidades = Localidad::pluck('nombre')->toArray();
		$tipoMotores = TiposMotor::pluck('nombre')->toArray();
		$transmisiones = Transmision::pluck('nombre')->toArray();
		$ubicaciones = Ubicacion::pluck('nombre')->toArray();
		$estados_vehiculo = EstadosVeh::where('estado',1)->pluck('nombre')->toArray();

    foreach ($vehiculos as $item) {
	    $worksheet->getCellByColumnAndRow(1, $row)->setValue($item->id);
	    $worksheet->getCellByColumnAndRow(2, $row)->setValue($item->dominio);

	    $cell = $worksheet->getCellByColumnAndRow(3, $row);
	    $cell->setValue($item->getMarca->nombre);
	    $validation = $cell->getDataValidation();
			$validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
			$validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
			$validation->setAllowBlank(false);
			$validation->setShowInputMessage(true);
			$validation->setShowErrorMessage(true);
			$validation->setShowDropDown(true);
			$validation->setErrorTitle('Valor incorrecto');
			$validation->setError('El valor no se encuentra en la lista.');
			$validation->setPromptTitle('Elije de la lista');
			$validation->setPrompt('Por favor selecciona un valor de la lista.');
			$validation->setFormula1('"'.implode(',', $marcas).'"');

	    $worksheet->getCellByColumnAndRow(4, $row)->setValue($item->modelo);

	    $worksheet->getCellByColumnAndRow(5, $row)->setValue($item->year);
			if(isset($item->getUsado)){
				$worksheet->getCellByColumnAndRow(6, $row)->setValue($item->getUsado->kilometraje);
			}
	    $worksheet->getCellByColumnAndRow(7, $row)->setValue($item->color);

	    $cell = $worksheet->getCellByColumnAndRow(8, $row);
	    $cell->setValue($item->getLocalidad->nombre);
	    $validation = $cell->getDataValidation();
			$validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
			$validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
			$validation->setAllowBlank(false);
			$validation->setShowInputMessage(true);
			$validation->setShowErrorMessage(true);
			$validation->setShowDropDown(true);
			$validation->setErrorTitle('Valor incorrecto');
			$validation->setError('El valor no se encuentra en la lista.');
			$validation->setPromptTitle('Elije de la lista');
			$validation->setPrompt('Por favor selecciona un valor de la lista.');
			$validation->setFormula1('"'.implode(',', $localidades).'"');

	    $worksheet->getCellByColumnAndRow(9, $row)->setValue($item->precio_venta);
	    $worksheet->getCellByColumnAndRow(10, $row)->setValue($item->precio_lista);
	    $worksheet->getCellByColumnAndRow(11, $row)->setValue($item->entrega_minima);
	    $worksheet->getCellByColumnAndRow(12, $row)->setValue($item->motor);

	    $cell = $worksheet->getCellByColumnAndRow(13, $row);
	    $cell->setValue($item->getTipoMotor->nombre);
	    $validation = $cell->getDataValidation();
			$validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
			$validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
			$validation->setAllowBlank(false);
			$validation->setShowInputMessage(true);
			$validation->setShowErrorMessage(true);
			$validation->setShowDropDown(true);
			$validation->setErrorTitle('Valor incorrecto');
			$validation->setError('El valor no se encuentra en la lista.');
			$validation->setPromptTitle('Elije de la lista');
			$validation->setPrompt('Por favor selecciona un valor de la lista.');
			$validation->setFormula1('"'.implode(',', $tipoMotores).'"');

	    $cell = $worksheet->getCellByColumnAndRow(14, $row);
	    $cell->setValue($item->getTransmision->nombre);
	    $validation = $cell->getDataValidation();
			$validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
			$validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
			$validation->setAllowBlank(false);
			$validation->setShowInputMessage(true);
			$validation->setShowErrorMessage(true);
			$validation->setShowDropDown(true);
			$validation->setErrorTitle('Valor incorrecto');
			$validation->setError('El valor no se encuentra en la lista.');
			$validation->setPromptTitle('Elije de la lista');
			$validation->setPrompt('Por favor selecciona un valor de la lista.');
			$validation->setFormula1('"'.implode(',', $transmisiones).'"');

	    $worksheet->getCellByColumnAndRow(15, $row)->setValue($item->cantidad_puertas);

	    $cell = $worksheet->getCellByColumnAndRow(16, $row);
	    $cell->setValue($item->getUbicacion->nombre);
	    $validation = $cell->getDataValidation();
			$validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
			$validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
			$validation->setAllowBlank(false);
			$validation->setShowInputMessage(true);
			$validation->setShowErrorMessage(true);
			$validation->setShowDropDown(true);
			$validation->setErrorTitle('Valor incorrecto');
			$validation->setError('El valor no se encuentra en la lista.');
			$validation->setPromptTitle('Elije de la lista');
			$validation->setPrompt('Por favor selecciona un valor de la lista.');
			$validation->setFormula1('"'.implode(',', $ubicaciones).'"');

			$cell = $worksheet->getCellByColumnAndRow(17, $row);
	    $cell->setValue($item->getEstadoLista->nombre);
	    $validation = $cell->getDataValidation();
			$validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
			$validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
			$validation->setAllowBlank(false);
			$validation->setShowInputMessage(true);
			$validation->setShowErrorMessage(true);
			$validation->setShowDropDown(true);
			$validation->setErrorTitle('Valor incorrecto');
			$validation->setError('El valor no se encuentra en la lista.');
			$validation->setPromptTitle('Elije de la lista');
			$validation->setPrompt('Por favor selecciona un valor de la lista.');
			$validation->setFormula1('"'.implode(',', $estados_vehiculo).'"');

			if(isset($item->getUsado)){
		    $worksheet->getCellByColumnAndRow(18, $row)->setValue($item->getUsado->exowner);
		    $worksheet->getCellByColumnAndRow(19, $row)->setValue($item->getUsado->precio_revista);
		    $worksheet->getCellByColumnAndRow(20, $row)->setValue($item->getUsado->precio_toma);
		    $worksheet->getCellByColumnAndRow(21, $row)->setValue($item->getUsado->precio_reparacion_estimado);
		    $worksheet->getCellByColumnAndRow(22, $row)->setValue($item->getUsado->observaciones);
		  }

	    $row++;
		}


		//$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xls");
		header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=listado_vehiculos.xls');
    header('Cache-Control: max-age=0');

		//$filePath = '/reportes/' . rand(0, getrandmax()) . rand(0, getrandmax()) . ".xls";
    $writer->save("php://output");
    //$writer->save($filePath);
    //readfile($filePath);
    //unlink($filePath);
	}

	public function getDisponibles($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Vehiculo::with('getUbicacion','getLocalidad','getTransmision','getTipoMotor','getEstadoLista','getTiposEstado','getUsado','getMarca','getFotos','individuo','historial','historial.individuo')
		->has('getUsado')
		->where('id_estado','!=',3)
		->where('eliminado',0)
		->whereDoesntHave('getEstadoLista',function($subQuery) {
	    $subQuery->where("estado", 0);
	  });

	  $codigo = $request->getQueryParam('codigo',null);
	  if(!is_null($codigo))
	  {
	  	switch ($codigo) {
	  		case 0: //TODOS
	  			break;
	  		case 1: //DISPONIBLE
	  			$veh_imagenes = ImagenesVeh::select('id_vehiculo')->distinct('id_vehiculo')->pluck('id_vehiculo');
	  			$registros = $registros->whereIn('id',$veh_imagenes)
	  			->where('id_estado',1);
	  			break;
  			case 2: //SIN FOTOS
  				$veh_imagenes = ImagenesVeh::select('id_vehiculo')->distinct('id_vehiculo')->pluck('id_vehiculo');
	  			$registros = $registros->whereNotIn('id',$veh_imagenes);
	  			break;
  			case 3: //SEÑADO
  				$registros = $registros->where('id_estado',2);
	  			break;
	  	}
	  }
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where('modelo','like','%'.$value.'%')
						->orWhere(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
						->orWhere('year','like','%'.$value.'%')
						->orWhere('motor','like','%'.$value.'%')
						->orWhere('cantidad_puertas','like','%'.$value.'%')
						->orWhere('entrega_minima','like','%'.$value.'%')
						->orWhere('precio_venta','like','%'.$value.'%')
						->orWhere('precio_lista','like','%'.$value.'%')
						->orWhereIn('id_marca',function($d) use ($value){
							$d->select('id')->from('veh_listado-marcas')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_localidad',function($d) use ($value){
							$d->select('id')->from('localidades')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_ubicacion',function($d) use ($value){
							$d->select('id')->from('ubicaciones')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_tipo_motor',function($d) use ($value){
							$d->select('id')->from('veh_tipos-motor')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_transmision',function($d) use ($value){
							$d->select('id')->from('veh_tipos-transmision')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_estado',function($d) use ($value){
							$d->select('id')->from('veh_tipos-estado')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('estado_vehiculo',function($d) use ($value){
							$d->select('id')->from('veh_lista-estados')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id',function($d) use ($value){
							$d->select('id_vehiculo')->from('usados')
							->where('kilometraje','like','%'.$value.'%')
							->orWhere('dominio','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhere('color','like','%'.$value.'%')
							->orWhere('exowner','like','%'.$value.'%')
							->orWhere('precio_revista','like','%'.$value.'%')
							->orWhere('precio_toma','like','%'.$value.'%')
							->orWhere('precio_reparacion_estimado','like','%'.$value.'%');
						});
					});
				}
			}
		}
		$recordsFiltered = $registros->count();

		$orden = $request->getQueryParam('orden',null);
		if(!is_null($orden) and $orderColumna == 'created_at')
		{
			switch ($orden) {
	  		case 0: //DISPONIBLE
	  			$registros = $registros->orderBy('created_at',$orderTipo);
	  			break;
  			case 1: //SIN FOTOS
  				$registros = $registros->orderBy('updated_at',$orderTipo);
	  			break;
  			default:
  				$registros = $registros->orderBy($orderColumna,$orderTipo);
	  			break;
	  	}
		} else {
			$registros = $registros->orderBy($orderColumna,$orderTipo);
		}

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

public function getVendidos($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Vehiculo::with('getUbicacion','getLocalidad','getTransmision','getTipoMotor','getEstadoLista','getTiposEstado','getUsado','getMarca','getFotos','individuo','historial','historial.individuo')
		->has('getUsado')
		->where('id_estado','!=',1)
		->where('id_estado','!=',2)
		->where('eliminado',0)
		->whereDoesntHave('getEstadoLista',function($subQuery) {
	    $subQuery->where("estado", 0);
	  });
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where('modelo','like','%'.$value.'%')
						->orWhere(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
						->orWhere('year','like','%'.$value.'%')
						->orWhere('motor','like','%'.$value.'%')
						->orWhere('cantidad_puertas','like','%'.$value.'%')
						->orWhere('entrega_minima','like','%'.$value.'%')
						->orWhere('precio_venta','like','%'.$value.'%')
						->orWhere('precio_lista','like','%'.$value.'%')
						->orWhereIn('id_marca',function($d) use ($value){
							$d->select('id')->from('veh_listado-marcas')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_localidad',function($d) use ($value){
							$d->select('id')->from('localidades')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_ubicacion',function($d) use ($value){
							$d->select('id')->from('ubicaciones')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_tipo_motor',function($d) use ($value){
							$d->select('id')->from('veh_tipos-motor')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_transmision',function($d) use ($value){
							$d->select('id')->from('veh_tipos-transmision')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_estado',function($d) use ($value){
							$d->select('id')->from('veh_tipos-estado')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('estado_vehiculo',function($d) use ($value){
							$d->select('id')->from('veh_lista-estados')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id',function($d) use ($value){
							$d->select('id_vehiculo')->from('usados')
							->where('kilometraje','like','%'.$value.'%')
							->orWhere('dominio','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhere('color','like','%'.$value.'%')
							->orWhere('exowner','like','%'.$value.'%')
							->orWhere('precio_revista','like','%'.$value.'%')
							->orWhere('precio_toma','like','%'.$value.'%')
							->orWhere('precio_reparacion_estimado','like','%'.$value.'%');
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

	public function getAll($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Vehiculo::with('getUbicacion','getLocalidad','getTransmision','getTipoMotor','getEstadoLista','getTiposEstado','getUsado','getMarca','getFotos','individuo','historial','historial.individuo')
		->where('eliminado',0)
		->whereDoesntHave('getEstadoLista',function($subQuery) {
	    $subQuery->where("estado", 0);
	  });
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where('modelo','like','%'.$value.'%')
						->orWhere(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
						->orWhere('year','like','%'.$value.'%')
						->orWhere('motor','like','%'.$value.'%')
						->orWhere('cantidad_puertas','like','%'.$value.'%')
						->orWhere('entrega_minima','like','%'.$value.'%')
						->orWhere('precio_venta','like','%'.$value.'%')
						->orWhere('precio_lista','like','%'.$value.'%')
						->orWhereIn('id_marca',function($d) use ($value){
							$d->select('id')->from('veh_listado-marcas')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_localidad',function($d) use ($value){
							$d->select('id')->from('localidades')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_ubicacion',function($d) use ($value){
							$d->select('id')->from('ubicaciones')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_tipo_motor',function($d) use ($value){
							$d->select('id')->from('veh_tipos-motor')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_transmision',function($d) use ($value){
							$d->select('id')->from('veh_tipos-transmision')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id_estado',function($d) use ($value){
							$d->select('id')->from('veh_tipos-estado')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('estado_vehiculo',function($d) use ($value){
							$d->select('id')->from('veh_lista-estados')->where('nombre','like','%'.$value.'%');
						})
						->orWhereIn('id',function($d) use ($value){
							$d->select('id_vehiculo')->from('usados')
							->where('kilometraje','like','%'.$value.'%')
							->orWhere('dominio','like','%'.$value.'%')
							->orWhere('observaciones','like','%'.$value.'%')
							->orWhere('color','like','%'.$value.'%')
							->orWhere('exowner','like','%'.$value.'%')
							->orWhere('precio_revista','like','%'.$value.'%')
							->orWhere('precio_toma','like','%'.$value.'%')
							->orWhere('precio_reparacion_estimado','like','%'.$value.'%');
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

	public function modificarPrecioVenta($request,$response,$args){
		$id_vehiculo = $args['id'];
		$precio_venta = $request->getQueryParam('precio_venta');

		if(is_numeric($precio_venta)){
			$precio_anterior = Vehiculo::where('id',$id_vehiculo)->first()->precio_venta;
			VehiculoHistorial::Create([
				'id_vehiculo' => $id_vehiculo,
				'id_usuario' => $_SESSION['userid'],
				'descripcion' => 'Precio Modificado desde la Tabla de Vehiculos. '.$precio_anterior.' -> '.$precio_venta,
				'id_estado' => 4,
			]);
			Vehiculo::where('id',$id_vehiculo)->update([
				'precio_venta' => $precio_venta,
			]);
			return $response->withStatus(200);
		} else {
			return $response->withStatus(400);
		}
	}

	public function historial($request,$response,$args){
		$id_vehiculo = $args['id'];
		$historial = VehiculoHistorial::with('individuo')->where('id_vehiculo',$id_vehiculo)->orderBy('created_at','desc')->limit(15)->get();
		return $response->withStatus(200)->withJson($historial);
	}

	public function dateros($request,$response,$args){
		$id_vehiculo = $args['id'];
		$dateros = Datero::with('tipoEstado')->where('id_vehiculo',$id_vehiculo)->where('estado',1)->get();
		return $response->withStatus(200)->withJson($dateros);
	}

	public function estadisticas($request,$response,$args){
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
		return $this->container->view->render($response, 'admin_views/vehicles/estadisticas.twig',[
			'stock' => $stock,
			'stock' => $stock,
		]);
	}

	public function compress_image($source_url, $destination_url, $quality){

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
}
