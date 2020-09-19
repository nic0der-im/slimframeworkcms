<?php

namespace App\Controllers\Negocio;

use Slim\Views\Twig as View;

use App\Funcionalidades\OneSignal;

use App\Funcionalidades\Correo;
use App\Funcionalidades\Auxiliar;
use App\Controllers\Controller;

use PHPJasper\PHPJasper;
use App\Models\Correo\ModuloCorreo;

use App\Models\Negocio\Cliente;
use App\Models\Negocio\Datero;
use App\Models\Negocio\DateroHistorial;
use App\Models\Negocio\DateroEstado;
use App\Models\Negocio\Carpeta;
use App\Models\Negocio\CarpetaCliente;
use App\Models\Negocio\CarpetaVehiculo;

use App\Models\Extras\Provincia;
use App\Models\Extras\Ciudad;
use App\Models\Extras\Profesion;
use App\Models\Extras\ActividadEconomica;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\VehiculoHistorial;
use App\Models\Vehiculos\Usado;
use App\Models\Vehiculos\UsadoTercero;
use App\Models\Vehiculos\Marca;

use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TipoDocumento;
use App\Models\Notificaciones\Notificaciones;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class DateroController extends Controller {

	public function index($request, $response)
	{
		$usado = Usado::pluck('id_vehiculo')->all();
		$usadoTercero = UsadoTercero::pluck('id_vehiculo')->all();
		$vehiculos = Vehiculo::whereNotIn('id',$usado)->whereNotIn('id',$usadoTercero)->get();
		$estados = DateroEstado::selectRaw('id as value, nombre as text')
			->where('estado',1)
			->get();
		return $this->container->view->render($response, 'admin_views/clientes/tableroDateros.twig', [
			'vehiculos' => $vehiculos,
			'estados' => $estados,
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

		$registros = Datero::with(
			'marca',
			'tipoDocumento',
			'provincia',
			'laboralProvincia',
			'individuo',
			'conyugueTipoDocumento',
			'conyugueLaboralProvincia',
			'actividadEconomica',
			'conyugueActividadEconomica',
			'tipoEstado',
			'usado')
			->where('estado',1);
		if(auth::empleado()->id_puesto == 2){
			$registros = $registros->where('id_usuario',auth::empleado()->id_usuario);
		}
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('nombre','like','%'.$value.'%')
							->orWhere('apellido','like','%'.$value.'%')
							->orWhere('documento','like','%'.$value.'%')
							->orWhere('vehiculo_modelo','like','%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('domicilio','like','%'.$value.'%')
							->orWhere('domicilio_observaciones','like','%'.$value.'%')
							->orWhereIn('id_marca',function($d) use ($value){
								$d->select('id')->from('veh_listado-marcas')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('id_tipo_estado',function($d) use ($value){
								$d->select('id')->from('datero_tipos_estado')->where('nombre','like','%'.$value.'%');
							})
							->orWhereIn('id_tipo_estado',function($d) use ($value){
								$d->select('id')->from('datero_tipos_estado')
									->where('nombre','like','%'.$value.'%')
									->where('estado',1);
							})
							->orWhereIn('id_usuario',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')
									->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
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

	public function getCargar($request,$response,$args){
		$id_cliente = $request->getQueryParam('id_cliente',0);
		$old = null;
		if($id_cliente>0){
			$cliente = Cliente::selectRaw('
				id as id_cliente,
				nombre,
				apellido,
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
				$old = $cliente->toArray();
			}
		}
		$id_vehiculo = $request->getQueryParam('id_vehiculo',0);
		if($id_vehiculo>0){
			$vehiculo = Vehiculo::selectRaw('
				id as id_vehiculo,
				id_marca,
				modelo as vehiculo_modelo,
				precio_venta as vehiculo_precio,
				entrega_minima as vehiculo_entrega_minima,
				year as vehiculo_año,
				"USADO" as vehiculo_calidad
				')->where('id',$id_vehiculo)->first();
			if($vehiculo){
				if(is_null($old)){
					$old = $vehiculo->toArray();
				} else {
					$old = array_merge($old,$vehiculo->toArray());
				}
			}
		}
		$id_carpeta = $request->getQueryParam('id_carpeta',null);
		if(!is_null($id_carpeta)){
			$old['id_carpeta'] = $id_carpeta;
		}
		return $this->container->view->render($response, 'admin_views/clientes/datero.twig',[
			'old'=>$old,
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'provincias' => Provincia::all(),
			'profesiones' => Profesion::where('estado',1)->get(),
			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
		]);
	}

	public function postCargar($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
			/*
		$validation = $this->validator->validate($request, [
			'nombre'=>v::alpha(),
			'apellido'=>v::alpha(),
			'documento'=>v::numeric(),
			'email'=>v::email(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('cliente.datero.cargar'));
		}
		*/

		$id_ciudad = $request->getParam('id_ciudad');
		$id_cliente = $request->getParam('id_cliente');
		$id_cliente_conyugue = $request->getParam('id_cliente_conyugue',0);
		$id_vehiculo = $request->getParam('id_vehiculo',0);
		$id_carpeta = $request->getParam('id_carpeta',0);

		$sexo = $request->getParam('sexo');
		$apellido = $request->getParam('apellido');
		$nombre = $request->getParam('nombre');
		$estado_civil = $request->getParam('estado_civil');
		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$documento = $request->getParam('documento');
		$fecha_nacimiento = $request->getParam('fecha_nacimiento');
		$nacionalidad = $request->getParam('nacionalidad');
		$lugar_nacimiento = $request->getParam('lugar_nacimiento');
		$padre = $request->getParam('padre');
		$madre = $request->getParam('madre');
		$cargo = $request->getParam('cargo');
		$vivienda = $request->getParam('vivienda');
		$gasto_mensual = $request->getParam('gasto_mensual');
		$estudios = $request->getParam('estudios');
		$telefono = preg_replace('/[^0-9]/', '', $request->getParam('telefono') );
		$celular = preg_replace('/[^0-9]/', '', $request->getParam('celular') );
		$email = $request->getParam('email');
		$domicilio = $request->getParam('domicilio');
		$numero = $request->getParam('numero');
		$piso = $request->getParam('piso');
		$dpto = $request->getParam('dpto');
		$domicilio_observaciones = $request->getParam('domicilio_observaciones');
		$id_provincia = $request->getParam('id_provincia');
		$localidad = $request->getParam('localidad');
		$cp = $request->getParam('cp');

		$id_actividad_economica = $request->getParam('id_actividad_economica');
		$profesion = $request->getParam('profesion');
		$empresa = $request->getParam('empresa');
		$sector_economico = $request->getParam('sector_economico');
		$ramo = $request->getParam('ramo');
		$relacion_dependencia = $request->getParam('relacion_dependencia');
		$plan_sueldo = $request->getParam('plan_sueldo');
		$posicion = $request->getParam('posicion');
		$ocupacion = $request->getParam('ocupacion');
		$antiguedad = $request->getParam('antiguedad');
		$antiguedad_anterior = $request->getParam('antiguedad_anterior');
		$ingresos = $request->getParam('ingresos');
		$ingresos_otros = $request->getParam('ingresos_otros');

		$laboral_telefono = preg_replace('/[^0-9]/', '', $request->getParam('laboral_telefono') );
		$laboral_celular = preg_replace('/[^0-9]/', '', $request->getParam('laboral_celular') );
		$laboral_domicilio = $request->getParam('laboral_domicilio');
		$laboral_numero = $request->getParam('laboral_numero');
		$laboral_piso = $request->getParam('laboral_piso');
		$laboral_dpto = $request->getParam('laboral_dpto');
		$laboral_domicilio_observaciones = $request->getParam('laboral_domicilio_observaciones');
		$laboral_id_provincia = $request->getParam('laboral_id_provincia');
		$laboral_localidad = $request->getParam('laboral_localidad');
		$laboral_cp = $request->getParam('laboral_cp');

		$conyugue_sexo = $request->getParam('conyugue_sexo');
		$conyugue_apellido = $request->getParam('conyugue_apellido','');
		$conyugue_nombre = $request->getParam('conyugue_nombre','');
		$conyugue_cotitular = $request->getParam('conyugue_cotitular','');
		$conyugue_id_tipo_documento = $request->getParam('conyugue_id_tipo_documento','');
		$conyugue_documento = $request->getParam('conyugue_documento','');
		$conyugue_fecha_nacimiento = $request->getParam('conyugue_fecha_nacimiento','');
		$conyugue_nacionalidad = $request->getParam('conyugue_nacionalidad');
		$conyugue_lugar_nacimiento = $request->getParam('conyugue_lugar_nacimiento');
		$conyugue_padre = $request->getParam('conyugue_padre');
		$conyugue_madre = $request->getParam('conyugue_madre');
		$conyugue_cargo = $request->getParam('conyugue_cargo');

		$conyugue_id_actividad_economica = $request->getParam('conyugue_id_actividad_economica');
		$conyugue_profesion = $request->getParam('conyugue_profesion');
		$conyugue_empresa = $request->getParam('conyugue_empresa');
		$conyugue_sector_economico = $request->getParam('conyugue_sector_economico');
		$conyugue_ramo = $request->getParam('conyugue_ramo');
		$conyugue_relacion_dependencia = $request->getParam('conyugue_relacion_dependencia');
		$conyugue_plan_sueldo = $request->getParam('conyugue_plan_sueldo');
		$conyugue_posicion = $request->getParam('conyugue_posicion');
		$conyugue_ocupacion = $request->getParam('conyugue_ocupacion');
		$conyugue_antiguedad = $request->getParam('conyugue_antiguedad');
		$conyugue_antiguedad_anterior = $request->getParam('conyugue_antiguedad_anterior');
		$conyugue_ingresos = $request->getParam('conyugue_ingresos');
		$conyugue_ingresos_otros = $request->getParam('conyugue_ingresos_otros');

		$conyugue_laboral_telefono = preg_replace('/[^0-9]/', '', $request->getParam('conyugue_laboral_telefono') );
		$conyugue_laboral_celular = preg_replace('/[^0-9]/', '', $request->getParam('conyugue_laboral_celular') );
		$conyugue_laboral_domicilio = $request->getParam('conyugue_laboral_domicilio');
		$conyugue_laboral_numero = $request->getParam('conyugue_laboral_numero');
		$conyugue_laboral_piso = $request->getParam('conyugue_laboral_piso');
		$conyugue_laboral_dpto = $request->getParam('conyugue_laboral_dpto');
		$conyugue_laboral_domicilio_observaciones = $request->getParam('conyugue_laboral_domicilio_observaciones');
		$conyugue_laboral_id_provincia = $request->getParam('conyugue_laboral_id_provincia');
		$conyugue_laboral_localidad = $request->getParam('conyugue_laboral_localidad');
		$conyugue_laboral_cp = $request->getParam('conyugue_laboral_cp');

		$vehiculo_calidad = $request->getParam('vehiculo_calidad');
		$vehiculo_gnc = $request->getParam('vehiculo_gnc');
		$vehiculo_año = $request->getParam('vehiculo_año');
		$vehiculo_uso = $request->getParam('vehiculo_uso');
		$vehiculo_prima = $request->getParam('vehiculo_prima');
		$id_marca = $request->getParam('id_marca');
		$vehiculo_modelo = $request->getParam('vehiculo_modelo');
		$vehiculo_precio = $request->getParam('vehiculo_precio');
		$vehiculo_entrega_minima = $request->getParam('vehiculo_entrega_minima');
		$vehiculo_seguro = $request->getParam('vehiculo_seguro');
		$vehiculo_seguro_producto = $request->getParam('vehiculo_seguro_producto');
		$vehiculo_tasa = $request->getParam('vehiculo_tasa');
		$vehiculo_credito = $request->getParam('vehiculo_credito');
		$banco = $request->getParam('banco');

		$presupuesto_seña = $request->getParam('presupuesto_seña');
		$presupuesto_financiar = $request->getParam('presupuesto_financiar');
		$presupuesto_vehiculo_valor = $request->getParam('presupuesto_vehiculo_valor');
		$presupuesto_porcentaje = $request->getParam('presupuesto_porcentaje');
		$presupuesto_plazos = $request->getParam('presupuesto_plazos');
		$presupuesto_tasa = $request->getParam('presupuesto_tasa');
		$presupuesto_capital_intereses = Auxiliar::money_to_float($request->getParam('presupuesto_capital_intereses'));
		$presupuesto_iva = Auxiliar::money_to_float($request->getParam('presupuesto_iva'));
		$presupuesto_cuota = Auxiliar::money_to_float($request->getParam('presupuesto_cuota'));

		$observaciones = $request->getParam('observaciones');

		try {
			DB::beginTransaction();
			$dt = new \DateTime();

			if($id_cliente>0){
				Cliente::where('id',$id_cliente)->update([
					'nombre' => $nombre,
					'apellido' => $apellido,
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
			} else {
				$id_cliente = Cliente::create([
					'nombre' => $nombre,
					'apellido' => $apellido,
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
					'observaciones' => $observaciones,
					'id_usuario' => $id_usuario ,
					'id_agencia' => auth::empleado()->id_agencia ,
				])->id;
			}

			if($conyugue_apellido != '' and $conyugue_nombre != '' and $conyugue_documento!= '' ){
				$id_cliente_conyugue = Cliente::create([
					'nombre' => $conyugue_nombre,
					'apellido' => $conyugue_apellido,
					'id_tipo_documento' => $conyugue_id_tipo_documento,
					'documento' => $conyugue_documento,
					'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$conyugue_fecha_nacimiento),
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
					'observaciones' => $observaciones,
					'id_usuario' => $id_usuario ,
					'id_agencia' => auth::empleado()->id_agencia ,
				])->id;
			}

			if($id_vehiculo == 0 ){
				$id_vehiculo = Vehiculo::Create([
					'id_marca'=>$id_marca,
					'modelo'=>$vehiculo_modelo,
					'year'=>$vehiculo_año,
					'id_localidad'=> auth::individuo()->id_localidad != null ?auth::individuo()->id_localidad:0,
					'entrega_minima'=>$vehiculo_entrega_minima,
					'precio_venta'=>$vehiculo_precio,
					'id_usuario'=> $id_usuario,
				])->id;
			} else {
				VehiculoHistorial::Create([
					'id_vehiculo' => $id_vehiculo,
					'id_usuario' => $id_usuario,
					'descripcion' => 'Datero creado con un precio: '.$vehiculo_precio,
					'id_estado' => 4,
				]);
			}

			$datero = Datero::create([
				'id_carpeta' => $id_carpeta,
				'id_cliente' => $id_cliente,
				'id_cliente_conyugue' => $id_cliente_conyugue,
				'id_vehiculo' => $id_vehiculo,
				'id_marca' => $id_marca,
				'observaciones' => $observaciones,

				'sexo' => $sexo,
				'apellido' => $apellido,
				'nombre' => $nombre,
				'estado_civil' => $estado_civil,
				'id_tipo_documento' => $id_tipo_documento,
				'documento' => $documento,
				'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento),
				'nacionalidad' => $nacionalidad,
				'lugar_nacimiento' => $lugar_nacimiento,
				'padre'=> $padre,
				'madre' => $madre,
				'cargo' => $cargo,
				'vivienda' => $vivienda,
				'gasto_mensual' => $gasto_mensual,
				'estudios' => $estudios,
				'telefono' => $telefono,
				'celular' => $celular,
				'email' => $email,
				'domicilio' => $domicilio,
				'numero' => $numero,
				'piso' => $piso,
				'dpto' => $dpto,
				'domicilio_observaciones' => $domicilio_observaciones,
				'id_provincia' => $id_provincia,
				'localidad' => $localidad,
				'cp' => $cp,

				'id_actividad_economica' => $id_actividad_economica,
				'profesion' => $profesion,
				'empresa' => $empresa,
				'sector_economico' => $sector_economico,
				'ramo' => $ramo,
				'relacion_dependencia' => $relacion_dependencia,
				'plan_sueldo' => $plan_sueldo,
				'posicion' => $posicion,
				'ocupacion' => $ocupacion,
				'antiguedad' => $antiguedad,
				'antiguedad_anterior' => $antiguedad_anterior,
				'ingresos' => $ingresos,
				'ingresos_otros' => $ingresos_otros,

				'laboral_telefono' => $laboral_telefono,
				'laboral_celular' => $laboral_celular,
				'laboral_domicilio' => $laboral_domicilio,
				'laboral_numero' => $laboral_numero,
				'laboral_piso' => $laboral_piso,
				'laboral_dpto' => $laboral_dpto,
				'laboral_domicilio_observaciones' => $laboral_domicilio_observaciones,
				'laboral_id_provincia' => $laboral_id_provincia,
				'laboral_localidad' => $laboral_localidad,
				'laboral_cp' => $laboral_cp,

				'conyugue_sexo' => $conyugue_sexo,
				'conyugue_apellido' => $conyugue_apellido,
				'conyugue_nombre' => $conyugue_nombre,
				'conyugue_cotitular' => $conyugue_cotitular,
				'conyugue_id_tipo_documento' => $conyugue_id_tipo_documento,
				'conyugue_documento' => $conyugue_documento,
				'conyugue_fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$conyugue_fecha_nacimiento),
				'conyugue_nacionalidad' => $conyugue_nacionalidad,
				'conyugue_lugar_nacimiento' => $conyugue_lugar_nacimiento,
				'conyugue_padre' => $conyugue_padre,
				'conyugue_madre' => $conyugue_madre,
				'conyugue_cargo' => $conyugue_cargo,

				'conyugue_id_actividad_economica' => $conyugue_id_actividad_economica,
				'conyugue_profesion' => $conyugue_profesion,
				'conyugue_empresa' => $conyugue_empresa,
				'conyugue_sector_economico' => $conyugue_sector_economico,
				'conyugue_ramo' => $conyugue_ramo,
				'conyugue_relacion_dependencia' => $conyugue_relacion_dependencia,
				'conyugue_plan_sueldo' => $conyugue_plan_sueldo,
				'conyugue_posicion' => $conyugue_posicion,
				'conyugue_ocupacion' => $conyugue_ocupacion,
				'conyugue_antiguedad' => $conyugue_antiguedad,
				'conyugue_antiguedad_anterior' => $conyugue_antiguedad_anterior,
				'conyugue_ingresos' => $conyugue_ingresos,
				'conyugue_ingresos_otros' => $conyugue_ingresos_otros,

				'conyugue_laboral_telefono' => $conyugue_laboral_telefono,
				'conyugue_laboral_celular' => $conyugue_laboral_celular,
				'conyugue_laboral_domicilio' => $conyugue_laboral_domicilio,
				'conyugue_laboral_numero' => $conyugue_laboral_numero,
				'conyugue_laboral_piso' => $conyugue_laboral_piso,
				'conyugue_laboral_dpto' => $conyugue_laboral_dpto,
				'conyugue_laboral_domicilio_observaciones' => $conyugue_laboral_domicilio_observaciones,
				'conyugue_laboral_id_provincia' => $conyugue_laboral_id_provincia,
				'conyugue_laboral_localidad' => $conyugue_laboral_localidad,
				'conyugue_laboral_cp' => $conyugue_laboral_cp,

				'vehiculo_calidad' => $vehiculo_calidad,
				'vehiculo_gnc' => $vehiculo_gnc,
				'vehiculo_año' => $vehiculo_año,
				'vehiculo_uso' => $vehiculo_uso,
				'vehiculo_prima' => $vehiculo_prima,
				'vehiculo_modelo' => $vehiculo_modelo,
				'vehiculo_precio' => $vehiculo_precio,
				'vehiculo_entrega_minima' => $vehiculo_entrega_minima,
				'vehiculo_seguro' => $vehiculo_seguro,
				'vehiculo_seguro_producto' => $vehiculo_seguro_producto,
				'vehiculo_credito' => $vehiculo_credito,
				'vehiculo_tasa' => $vehiculo_tasa,
				'banco' => $banco,

				'presupuesto_seña' => $presupuesto_seña,
				'presupuesto_financiar' => $presupuesto_financiar,
				'presupuesto_vehiculo_valor' => $presupuesto_vehiculo_valor,
				'presupuesto_porcentaje' => $presupuesto_porcentaje,
				'presupuesto_plazos' => $presupuesto_plazos,
				'presupuesto_tasa' => $presupuesto_tasa,
				'presupuesto_capital_intereses' => $presupuesto_capital_intereses,
				'presupuesto_iva' => $presupuesto_iva,
				'presupuesto_cuota' => $presupuesto_cuota,
				'id_usuario' => $id_usuario,
			]);

			$api = OneSignal::api();
			try{
				$title = 'Datero 📝 Nuevo '.$datero->marca->nombre.' '.$datero->vehiculo_modelo;
				$titulo = 'Datero Nuevo '.$datero->marca->nombre.' '.$datero->vehiculo_modelo;
				$message = 'Titular: '.$datero->apellido.' '.$datero->nombre.' PV:'.$datero->vehiculo_precio.' Click para mas informacion.';
				$url = $request->getUri()->getBaseUrl().$this->router->pathFor('cliente.datero.ver',
							[
								'id'=>$datero->id,
							]
						);
				$res = $api->post('notifications',[
					'json'=> [
						"app_id" => "78034361-ab14-4018-a430-6be0744c770a",
						"filters" => [
							[
								"field" => "tag",
								"key" => "sector",
								"relation" => "=",
								"value" => "administracion",
							],
						],
						'headings' => ['en'=>$title,'es'=>$title],
						"data"=> ["modulo"=> "datero","estado"=> "nuevo"],
						'url'=> $url,
						"contents"=> ["en"=> $message, 'es'=>$message],
						]
				]);
				$json = json_decode($res->getBody(), true);
				Notificaciones::create([
					'prioridad' => 2,
					'id_puesto' => 5,
					'id_usuario' => $id_usuario,
					'mensaje' => $message,
					'titulo' => $titulo,
					'url' => $url,
				]);
				$this->flash->addMessage('warning', 'Notificacion enviada. '.$json['id'].'. Recipientes '.$json['recipients']);
			} catch (\Exception $e){
				$this->flash->addMessage('warning', 'La notificacion no pudo ser enviada: '.$e->getMessage());
			}

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
			$this->flash->addMessage('error', 'Error en el sistema. '.$errores);
			return $response->withRedirect($this->router->pathFor('cliente.datero.cargar'));
			DB::rollBack();
		}

		try {
			$datero = Datero::where('id',$datero->id)->first();
			$filename ='Datero-'.str_replace(' ', '_', $datero->apellido).'_'.str_replace(' ', '_', $datero->nombre).'-'.$datero->documento;
			$input = __DIR__ .'/../../../public/reportes/reporte_datero.jasper';
			$output = __DIR__ .'/../../../public/reportes/'.$filename;
			$ext = 'pdf';
			$options = [
			    'format' => [$ext],
			    'locale' => 'es_AR',
			    'params' => [
			    	'id_datero'=>$datero->id,
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

			$mail = Correo::ciro();

	    $mail->setFrom('ciro.noreply@gmail.com', 'no-replay');

	    $notificar = ModuloCorreo::where('estado',1)->where('url_name','cliente.datero.index')->get()->unique('email');
	    foreach ($notificar as $value) {
	    	$mail->addAddress($value->email);
	    }
	    //$mail->addAddress('maxilozano16@gmail.com');
	    //$mail->addAddress('pariettidaniela89@gmail.com');
	    //$mail->addReplyTo(auth::user()->email, auth::individuo()->apellido .' '.auth::individuo()->nombre);

	    $mail->isHTML(true);
	    $mail->Subject = 'Datero '.$datero->apellido.' '.$datero->nombre;
	    $mail->Body    = 'Datero creado a partir del sistema de Ciro Automotores.<br>Por el empleado '.auth::individuo()->apellido.' '.auth::individuo()->nombre.' ';
	    $mail->addAttachment($output. '.'  . $ext);

	    $mail->send();
    	unlink($output. '.'  . $ext);

    	$mensajeCorreo = 'Correo enviado.';
		} catch (Exception $e) {
			$mensajeCorreo = ' ';
	    $this->flash->addMessage('error', 'El mensaje no pudo enviarse. '.$mail->ErrorInfo);
		}

		if($id_carpeta>0){
			CarpetaCliente::create([
				'id_cliente' => $id_cliente,
				'id_carpeta' => $id_carpeta,
				'id_usuario' => $id_usuario,
			]);
			if($id_cliente_conyugue>0){
				CarpetaCliente::create([
					'id_cliente' => $id_cliente_conyugue,
					'id_carpeta' => $id_carpeta,
					'id_usuario' => $id_usuario,
				]);
			}
			Carpeta::where('id',$id_carpeta)->update([
				'id_vehiculo' => $id_vehiculo,
				'id_cliente' => $id_cliente,
				'id_datero' => $datero->id,
			]);
			$this->flash->addMessage('info', 'El Datero fue creado Exitosamente.');
			return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$id_carpeta]));
		}

		$this->flash->addMessage('info', 'El Datero fue creado Exitosamente. '.$mensajeCorreo);
		return $response->withRedirect($this->router->pathFor('cliente.datero.index'));
	}

	public function getEditar($request,$response,$args){
		$id_cliente = $args['id'];
		$return_to = $request->getQueryParam('return_to',null);
		$old = Datero::where('id',$id_cliente)->first();

		return $this->container->view->render($response, 'admin_views/clientes/datero.twig',[
			'old'=>$old,
			'return_to'=>$return_to,
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'provincias' => Provincia::all(),
			'profesiones' => Profesion::where('estado',1)->get(),
			'marcas'=>Marca::orderBy('nombre', 'asc')->get(),
			'datero_actividad_economica' => ActividadEconomica::where('id',$old->id_actividad_economica)->first(),
			'datero_conyugue_actividad_economica' => ActividadEconomica::where('id',$old->conyugue_id_actividad_economica)->first(),
		]);
	}

	public function postEditar($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_datero = $args['id'];
		$validation = $this->validator->validate($request, [
			'nombre'=>v::alpha(),
			'apellido'=>v::alpha(),
			'documento'=>v::numeric(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('cliente.datero.editar',['id'=>$id_datero]));
		}

		$id_ciudad = $request->getParam('id_ciudad');
		$id_cliente = $request->getParam('id_cliente');
		$id_cliente_conyugue = $request->getParam('id_cliente_conyugue');
		$id_vehiculo = $request->getParam('id_vehiculo',0);
		$id_carpeta = $request->getParam('id_carpeta',0);
		$return_to = $request->getParam('return_to',null);

		$sexo = $request->getParam('sexo');
		$apellido = $request->getParam('apellido');
		$nombre = $request->getParam('nombre');
		$estado_civil = $request->getParam('estado_civil');
		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$documento = $request->getParam('documento');
		$fecha_nacimiento = $request->getParam('fecha_nacimiento');
		$nacionalidad = $request->getParam('nacionalidad');
		$lugar_nacimiento = $request->getParam('lugar_nacimiento');
		$padre = $request->getParam('padre');
		$madre = $request->getParam('madre');
		$cargo = $request->getParam('cargo');
		$vivienda = $request->getParam('vivienda');
		$gasto_mensual = $request->getParam('gasto_mensual');
		$estudios = $request->getParam('estudios');
		$telefono = preg_replace('/[^0-9]/', '', $request->getParam('telefono') );
		$celular = preg_replace('/[^0-9]/', '', $request->getParam('celular') );
		$email = $request->getParam('email');
		$domicilio = $request->getParam('domicilio');
		$numero = $request->getParam('numero');
		$piso = $request->getParam('piso');
		$dpto = $request->getParam('dpto');
		$domicilio_observaciones = $request->getParam('domicilio_observaciones');
		$id_provincia = $request->getParam('id_provincia');
		$localidad = $request->getParam('localidad');
		$cp = $request->getParam('cp');

		$id_actividad_economica = $request->getParam('id_actividad_economica');
		$profesion = $request->getParam('profesion');
		$empresa = $request->getParam('empresa');
		$sector_economico = $request->getParam('sector_economico');
		$ramo = $request->getParam('ramo');
		$relacion_dependencia = $request->getParam('relacion_dependencia');
		$plan_sueldo = $request->getParam('plan_sueldo');
		$posicion = $request->getParam('posicion');
		$ocupacion = $request->getParam('ocupacion');
		$antiguedad = $request->getParam('antiguedad');
		$antiguedad_anterior = $request->getParam('antiguedad_anterior');
		$ingresos = $request->getParam('ingresos');
		$ingresos_otros = $request->getParam('ingresos_otros');

		$laboral_telefono = preg_replace('/[^0-9]/', '', $request->getParam('laboral_telefono') );
		$laboral_celular = preg_replace('/[^0-9]/', '', $request->getParam('laboral_celular') );
		$laboral_domicilio = $request->getParam('laboral_domicilio');
		$laboral_numero = $request->getParam('laboral_numero');
		$laboral_piso = $request->getParam('laboral_piso');
		$laboral_dpto = $request->getParam('laboral_dpto');
		$laboral_domicilio_observaciones = $request->getParam('laboral_domicilio_observaciones');
		$laboral_id_provincia = $request->getParam('laboral_id_provincia');
		$laboral_localidad = $request->getParam('laboral_localidad');
		$laboral_cp = $request->getParam('laboral_cp');

		$conyugue_sexo = $request->getParam('conyugue_sexo');
		$conyugue_apellido = $request->getParam('conyugue_apellido');
		$conyugue_nombre = $request->getParam('conyugue_nombre');
		$conyugue_cotitular = $request->getParam('conyugue_cotitular');
		$conyugue_id_tipo_documento = $request->getParam('conyugue_id_tipo_documento');
		$conyugue_documento = $request->getParam('conyugue_documento');
		$conyugue_fecha_nacimiento = $request->getParam('conyugue_fecha_nacimiento');
		$conyugue_nacionalidad = $request->getParam('conyugue_nacionalidad');
		$conyugue_lugar_nacimiento = $request->getParam('conyugue_lugar_nacimiento');
		$conyugue_padre = $request->getParam('conyugue_padre');
		$conyugue_madre = $request->getParam('conyugue_madre');
		$conyugue_cargo = $request->getParam('conyugue_cargo');

		$conyugue_id_actividad_economica = $request->getParam('conyugue_id_actividad_economica');
		$conyugue_profesion = $request->getParam('conyugue_profesion');
		$conyugue_empresa = $request->getParam('conyugue_empresa');
		$conyugue_sector_economico = $request->getParam('conyugue_sector_economico');
		$conyugue_ramo = $request->getParam('conyugue_ramo');
		$conyugue_relacion_dependencia = $request->getParam('conyugue_relacion_dependencia');
		$conyugue_plan_sueldo = $request->getParam('conyugue_plan_sueldo');
		$conyugue_posicion = $request->getParam('conyugue_posicion');
		$conyugue_ocupacion = $request->getParam('conyugue_ocupacion');
		$conyugue_antiguedad = $request->getParam('conyugue_antiguedad');
		$conyugue_antiguedad_anterior = $request->getParam('conyugue_antiguedad_anterior');
		$conyugue_ingresos = $request->getParam('conyugue_ingresos');
		$conyugue_ingresos_otros = $request->getParam('conyugue_ingresos_otros');

		$conyugue_laboral_telefono = preg_replace('/[^0-9]/', '', $request->getParam('conyugue_laboral_telefono') );
		$conyugue_laboral_celular = preg_replace('/[^0-9]/', '', $request->getParam('conyugue_laboral_celular') );
		$conyugue_laboral_domicilio = $request->getParam('conyugue_laboral_domicilio');
		$conyugue_laboral_numero = $request->getParam('conyugue_laboral_numero');
		$conyugue_laboral_piso = $request->getParam('conyugue_laboral_piso');
		$conyugue_laboral_dpto = $request->getParam('conyugue_laboral_dpto');
		$conyugue_laboral_domicilio_observaciones = $request->getParam('conyugue_laboral_domicilio_observaciones');
		$conyugue_laboral_id_provincia = $request->getParam('conyugue_laboral_id_provincia');
		$conyugue_laboral_localidad = $request->getParam('conyugue_laboral_localidad');
		$conyugue_laboral_cp = $request->getParam('conyugue_laboral_cp');

		$vehiculo_calidad = $request->getParam('vehiculo_calidad');
		$vehiculo_gnc = $request->getParam('vehiculo_gnc');
		$vehiculo_año = $request->getParam('vehiculo_año');
		$vehiculo_uso = $request->getParam('vehiculo_uso');
		$vehiculo_prima = $request->getParam('vehiculo_prima');
		$id_marca = $request->getParam('id_marca');
		$vehiculo_modelo = $request->getParam('vehiculo_modelo');
		$vehiculo_precio = $request->getParam('vehiculo_precio');
		$vehiculo_entrega_minima = $request->getParam('vehiculo_entrega_minima');
		$vehiculo_seguro = $request->getParam('vehiculo_seguro');
		$vehiculo_seguro_producto = $request->getParam('vehiculo_seguro_producto');
		$vehiculo_credito = $request->getParam('vehiculo_credito');
		$vehiculo_tasa = $request->getParam('vehiculo_tasa');
		$banco = $request->getParam('banco');

		$presupuesto_seña = $request->getParam('presupuesto_seña');
		$presupuesto_financiar = $request->getParam('presupuesto_financiar');
		$presupuesto_vehiculo_valor = $request->getParam('presupuesto_vehiculo_valor');
		$presupuesto_porcentaje = $request->getParam('presupuesto_porcentaje');
		$presupuesto_plazos = $request->getParam('presupuesto_plazos');
		$presupuesto_tasa = $request->getParam('presupuesto_tasa');
		$presupuesto_capital_intereses = Auxiliar::money_to_float($request->getParam('presupuesto_capital_intereses'));
		$presupuesto_iva = Auxiliar::money_to_float($request->getParam('presupuesto_iva'));
		$presupuesto_cuota = Auxiliar::money_to_float($request->getParam('presupuesto_cuota'));

		$observaciones = $request->getParam('observaciones');

		try {
			DB::beginTransaction();
			$dt = new \DateTime();

			if($id_cliente>0){
				Cliente::where('id',$id_cliente)->update([
					'nombre' => $nombre,
					'apellido' => $apellido,
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
			} else {
				$id_cliente = Cliente::create([
					'nombre' => $nombre,
					'apellido' => $apellido,
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
					'observaciones' => $observaciones,
					'id_usuario' =>  $id_usuario,
					'id_agencia' => auth::empleado()->id_agencia ,
				])->id;
			}

			if($id_cliente_conyugue == 0 and $conyugue_apellido != '' and $conyugue_nombre != '' and $conyugue_documento!= '' ){
				$id_cliente_conyugue = Cliente::create([
					'nombre' => $conyugue_nombre,
					'apellido' => $conyugue_apellido,
					'id_tipo_documento' => $conyugue_id_tipo_documento,
					'documento' => $conyugue_documento,
					'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$conyugue_fecha_nacimiento),
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
					'observaciones' => $observaciones,
					'id_usuario' => $id_usuario ,
					'id_agencia' => auth::empleado()->id_agencia ,
				])->id;
				if($id_carpeta>0){
					CarpetaCliente::create([
						'id_cliente' => $id_cliente_conyugue,
						'id_carpeta' => $id_carpeta,
						'id_usuario' => $id_usuario,
					]);
				}
			} else if($id_cliente_conyugue>0) {
				Cliente::where('id',$id_cliente_conyugue)->update([
					'nombre' => $conyugue_nombre,
					'apellido' => $conyugue_apellido,
					'id_tipo_documento' => $conyugue_id_tipo_documento,
					'documento' => $conyugue_documento,
					'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$conyugue_fecha_nacimiento),
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
					'observaciones' => $observaciones,
				]);
			}

			if( strpos( $string, "NUEVO" )===0 ){
				$id_vehiculo = Datero::where('id',$id_datero)->first()->id_vehiculo;
				Vehiculo::where('id',$id_vehiculo)->update([
					'id_marca' => $id_marca,
					'modelo' => $vehiculo_modelo,
					'year' => $vehiculo_año,
					'entrega_minima' => $vehiculo_entrega_minima,
					'precio_venta' => $vehiculo_precio,
				]);

				VehiculoHistorial::Create([
					'id_vehiculo' => $id_vehiculo,
					'id_usuario' => $id_usuario,
					'descripcion' => 'Modificado desde el Datero.',
					'id_estado' => 4,
				]);
			} else {
				VehiculoHistorial::Create([
					'id_vehiculo' => $id_vehiculo,
					'id_usuario' => $id_usuario,
					'descripcion' => 'Datero con un precio de '.$vehiculo_precio,
					'id_estado' => 4,
				]);
			}

			Datero::where('id',$id_datero)->update([
				'id_cliente' => $id_cliente,
				'id_vehiculo' => $id_vehiculo,
				'id_marca' => $id_marca,
				'observaciones' => $observaciones,

				'sexo' => $sexo,
				'apellido' => $apellido,
				'nombre' => $nombre,
				'estado_civil' => $estado_civil,
				'id_tipo_documento' => $id_tipo_documento,
				'documento' => $documento,
				'fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$fecha_nacimiento),
				'nacionalidad' => $nacionalidad,
				'lugar_nacimiento' => $lugar_nacimiento,
				'padre'=> $padre,
				'madre' => $madre,
				'cargo' => $cargo,
				'vivienda' => $vivienda,
				'gasto_mensual' => $gasto_mensual,
				'estudios' => $estudios,
				'telefono' => $telefono,
				'celular' => $celular,
				'email' => $email,
				'domicilio' => $domicilio,
				'numero' => $numero,
				'piso' => $piso,
				'dpto' => $dpto,
				'domicilio_observaciones' => $domicilio_observaciones,
				'id_provincia' => $id_provincia,
				'localidad' => $localidad,
				'cp' => $cp,

				'id_actividad_economica' => $id_actividad_economica,
				'profesion' => $profesion,
				'empresa' => $empresa,
				'sector_economico' => $sector_economico,
				'ramo' => $ramo,
				'relacion_dependencia' => $relacion_dependencia,
				'plan_sueldo' => $plan_sueldo,
				'posicion' => $posicion,
				'ocupacion' => $ocupacion,
				'antiguedad' => $antiguedad,
				'antiguedad_anterior' => $antiguedad_anterior,
				'ingresos' => $ingresos,
				'ingresos_otros' => $ingresos_otros,

				'laboral_telefono' => $laboral_telefono,
				'laboral_celular' => $laboral_celular,
				'laboral_domicilio' => $laboral_domicilio,
				'laboral_numero' => $laboral_numero,
				'laboral_piso' => $laboral_piso,
				'laboral_dpto' => $laboral_dpto,
				'laboral_domicilio_observaciones' => $laboral_domicilio_observaciones,
				'laboral_id_provincia' => $laboral_id_provincia,
				'laboral_localidad' => $laboral_localidad,
				'laboral_cp' => $laboral_cp,

				'conyugue_sexo' => $conyugue_sexo,
				'conyugue_apellido' => $conyugue_apellido,
				'conyugue_nombre' => $conyugue_nombre,
				'conyugue_cotitular' => $conyugue_cotitular,
				'conyugue_id_tipo_documento' => $conyugue_id_tipo_documento,
				'conyugue_documento' => $conyugue_documento,
				'conyugue_fecha_nacimiento' => $dt->createFromFormat('d/m/Y',$conyugue_fecha_nacimiento),
				'conyugue_nacionalidad' => $conyugue_nacionalidad,
				'conyugue_lugar_nacimiento' => $conyugue_lugar_nacimiento,
				'conyugue_padre' => $conyugue_padre,
				'conyugue_madre' => $conyugue_madre,
				'conyugue_cargo' => $conyugue_cargo,

				'conyugue_id_actividad_economica' => $conyugue_id_actividad_economica,
				'conyugue_profesion' => $conyugue_profesion,
				'conyugue_empresa' => $conyugue_empresa,
				'conyugue_sector_economico' => $conyugue_sector_economico,
				'conyugue_ramo' => $conyugue_ramo,
				'conyugue_relacion_dependencia' => $conyugue_relacion_dependencia,
				'conyugue_plan_sueldo' => $conyugue_plan_sueldo,
				'conyugue_posicion' => $conyugue_posicion,
				'conyugue_ocupacion' => $conyugue_ocupacion,
				'conyugue_antiguedad' => $conyugue_antiguedad,
				'conyugue_antiguedad_anterior' => $conyugue_antiguedad_anterior,
				'conyugue_ingresos' => $conyugue_ingresos,
				'conyugue_ingresos_otros' => $conyugue_ingresos_otros,

				'conyugue_laboral_telefono' => $conyugue_laboral_telefono,
				'conyugue_laboral_celular' => $conyugue_laboral_celular,
				'conyugue_laboral_domicilio' => $conyugue_laboral_domicilio,
				'conyugue_laboral_numero' => $conyugue_laboral_numero,
				'conyugue_laboral_piso' => $conyugue_laboral_piso,
				'conyugue_laboral_dpto' => $conyugue_laboral_dpto,
				'conyugue_laboral_domicilio_observaciones' => $conyugue_laboral_domicilio_observaciones,
				'conyugue_laboral_id_provincia' => $conyugue_laboral_id_provincia,
				'conyugue_laboral_localidad' => $conyugue_laboral_localidad,
				'conyugue_laboral_cp' => $conyugue_laboral_cp,

				'vehiculo_calidad' => $vehiculo_calidad,
				'vehiculo_gnc' => $vehiculo_gnc,
				'vehiculo_año' => $vehiculo_año,
				'vehiculo_uso' => $vehiculo_uso,
				'vehiculo_prima' => $vehiculo_prima,
				'vehiculo_modelo' => $vehiculo_modelo,
				'vehiculo_precio' => $vehiculo_precio,
				'vehiculo_entrega_minima' => $vehiculo_entrega_minima,
				'vehiculo_seguro' => $vehiculo_seguro,
				'vehiculo_seguro_producto' => $vehiculo_seguro_producto,
				'vehiculo_credito' => $vehiculo_credito,
				'vehiculo_tasa' => $vehiculo_tasa,
				'banco' => $banco,

				'presupuesto_seña' => $presupuesto_seña,
				'presupuesto_financiar' => $presupuesto_financiar,
				'presupuesto_vehiculo_valor' => $presupuesto_vehiculo_valor,
				'presupuesto_porcentaje' => $presupuesto_porcentaje,
				'presupuesto_plazos' => $presupuesto_plazos,
				'presupuesto_tasa' => $presupuesto_tasa,
				'presupuesto_capital_intereses' => $presupuesto_capital_intereses,
				'presupuesto_iva' => $presupuesto_iva,
				'presupuesto_cuota' => $presupuesto_cuota,
			]);

			DateroHistorial::create([
	    	'id_datero' => $id_datero,
	    	'id_usuario' => $id_usuario,
	    	'id_tipo_estado' => 3,// MODIFICADO
	    	'descripcion' => 'Modificado desde el Editor',
	    ]);

			DB::commit();
		} catch (\PDOException $e) {
			$errores = $e->getMessage();
			$this->flash->addMessage('error', 'Error en el sistema. '.$errores);
			return $response->withRedirect($this->router->pathFor('cliente.datero.cargar',['id'=>$id_datero]));
			DB::rollBack();
		}

		if(!is_null($return_to)){
			$this->flash->addMessage('info', 'El Datero fue editado Exitosamente.');
			return $response->withRedirect('/'.$return_to);
		}

		$this->flash->addMessage('info', 'El Datero fue editado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('cliente.datero.index'));
	}

	public function getVer($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_datero = $args['id'];
		$datero = Datero::where('id',$id_datero)->first();
		DateroHistorial::create([
    	'id_datero' => $id_datero,
    	'id_usuario' => $id_usuario,
    	'id_tipo_estado' => 4,// VISTO
    	'descripcion' => 'Ver en el sistema.',
    ]);
		$historial = DateroHistorial::where('id_datero',$id_datero)->orderBy('id','desc')->get();
		return $this->container->view->render($response, 'admin_views/clientes/verDatero.twig', [
			'datero' => $datero,
			'historial' => $historial,
		]);
	}

	public function getCambiarEstado($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_datero = $args['id'];
		$id_tipo_estado = $args['id_tipo_estado'];
		$old = Datero::where('id',$id_datero)->first()->id_tipo_estado;
		Datero::where('id',$id_datero)->update([
			'id_tipo_estado' => $id_tipo_estado,
		]);
		$old = DateroEstado::where('id',$old)->first();
		$new = DateroEstado::where('id',$id_tipo_estado)->first();
		$return_to = $request->getQueryParam('return_to',null);
		$this->flash->addMessage('info', 'El Estado del Datero fue cambiado de '.$old->nombre.' a '.$new->nombre.'.');
		if(is_null($return_to)){
			DateroHistorial::create([
				'id_datero' => $id_datero,
				'id_usuario' => $id_usuario,
				'id_tipo_estado' => $id_tipo_estado,// MODIFICADO
				'descripcion' => 'Modificado desde el Tablero',
			]);
			return $response->withRedirect($this->router->pathFor('cliente.datero.index'));
		} else {
			DateroHistorial::create([
				'id_datero' => $id_datero,
				'id_usuario' => $id_usuario,
				'id_tipo_estado' => $id_tipo_estado,// MODIFICADO
				'descripcion' => 'Modificado desde: '.$return_to,
			]);
			return $response->withStatus(302)->withRedirect('/'.$return_to);
		}
	}

	public function getEliminar($request,$response,$args){
		$id_datero = $args['id'];
		$id_usuario = $_SESSION['userid'];
		$datero = Datero::where('id',$id_datero)->first();
		VehiculoHistorial::Create([
			'id_vehiculo' => $datero->id_vehiculo,
			'id_usuario' => $id_usuario,
			'descripcion' => 'Datero Eliminado.',
			'id_estado' => 4,
		]);
		DateroHistorial::create([
			'id_datero' => $id_datero,
			'id_usuario' => $id_usuario,
			'id_tipo_estado' => 3,// MODIFICADO
			'descripcion' => 'Modificado desde el Tablero',
		]);
		Datero::where('id',$id_datero)->update([
			'estado' => 0,
		]);

		return $response->withStatus(200)->withJson([
			'message' => 'El Datero para '.$datero->apellido.' '.$datero->nombre.' fue eliminado con exito.',
		]);
	}

	public function imprimir($request,$response,$args){
		if(isset($_SESSION['userid'])){
			$id_usuario = auth::empleado()->id_usuario;
		} else {
			$id_usuario = 0;
		}
		$id_datero = $args['id'];
		$datero = Datero::where('id',$id_datero)->first();

		$input = __DIR__ .'/../../../public/reportes/reporte_datero.jasper';
		$output = __DIR__ .'/../../../public/reportes/'.uniqid();
		$ext = 'pdf';
		$options = [
		    'format' => [$ext],
		    'locale' => 'es_AR',
		    'params' => [
		    	'id_datero'=>$id_datero,
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

		$filename ='Datero-'.str_replace(' ', '_', $datero->apellido).'_'.str_replace(' ', '_', $datero->nombre).'-'.$datero->documento;
		header('Content-Description: application/pdf');
    header('Content-Type: application/pdf');
    header('Content-Disposition:; filename=' . $filename . '.' . $ext);
    readfile($output . '.' . $ext);
    unlink($output. '.'  . $ext);
    flush();

    DateroHistorial::create([
    	'id_datero' => $id_datero,
    	'id_usuario' => $id_usuario,
    	'id_tipo_estado' => 4,// VISTO
    	'descripcion' => 'Mostrar PDF',
    ]);
	}
}
