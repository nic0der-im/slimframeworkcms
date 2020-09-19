<?php
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\NuevoMiddle;
use App\Middleware\SessionMiddleware;

use Aurmil\Slim\CsrfTokenToView;
use Aurmil\Slim\CsrfTokenToHeaders;


$app->get('/', 'HomeController:index')->setName('home');
$app->get('/solo', 'HomeController:index_solo')->setName('home_solo');
$app->get('/autos-usados-salta/{id}', 'HomeController:index')->setName('home.ver_pagina');

$app->get('/{titulo}_{id:[0-9]+}', 'HomeController:vervehiculo')->setName('vehicle.ver');

$app->post('/buscarpublicacion', 'HomeController:postBusqueda')->setName('publicaciones.buscar')->add(new CsrfTokenToHeaders($container->csrf));
$app->get('/preguntasfrecuentes', 'HomeController:getFAQS')->setName('faqs');
$app->get('/nosotros', 'HomeController:getNosotros')->setName('nosotros');
$app->get('/contacto', 'HomeController:getContacto')->setName('contacto');
$app->get('/promociones', 'HomeController:promociones')->setName('promociones');
$app->get('/financiacion/{id:[0-9]+}', 'HomeController:financiacion')->setName('financiacion');
$app->post('/consulta', 'HomeController:postConsulta')->setName('consulta')->add(new CsrfTokenToHeaders($container->csrf));

$app->post('/suscripcion', 'SuscripcionController:post')->setName('suscripcion');
//PARA GUARDAR EL ID DEL USUARIO EN ONESIGNAL EN LOS COOKIES
$app->get('/terminal', 'SuscripcionController:terminal')->setName('terminal');
$app->get('/terminal/asociar', 'SuscripcionController:asociarUsuario')->setName('terminal.asociar');
$app->get('/terminal/desasociar', 'SuscripcionController:desasociarUsuario')->setName('terminal.desasociar');

$app->get('/autos-usados-jujuy', 'HomeController:getUsadosJujuy')->setName('home.usados.jujuy');

/* CAMPAÑA PRECIOS MUNDIALES */

$app->get('/precios-mundiales[/]', function($request, $response, $args){
	$this->flash->addMessage('info', 'La promoción Precios Mundiales ha terminado.');
	return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('home'));
});


$app->get('/criptomonedas', 'CriptomonedasController:getIndex')->setName('criptomonedas');

/* FIN CAMPAÑAS */

$app->get('/ver-todos-los-autos[/]', function($request, $response, $args){
	$this->flash->addMessage('info', 'Bienvenido a la nueva versión de Ciro Automotores.');
	return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('home'));
});



$app->get('/img[{params:.*}]', function ($request, $response, $args) {
  $server = League\Glide\ServerFactory::create([
    'source' => '../public',
    'cache' => '../public/images/uploads',
    'response' => new League\Glide\Responses\SlimResponseFactory(),
    'watermarks' => '../public/images',
    'defaults' => [
        //'mark' => 'logo.png',
        //'markfit' => 'contain',
        //'markw' => '13w',
        //'markx' => '15w',
        //'marky' => '1w',
        //'markalpha' =>'25',
    		'q'=>70,
    		'fm'=>'pjpg',
    ],
    'presets' => [
  		'og' => [
        'w' => 300,
        'h' => 200,
        'fit' => 'strech',
        'fm'=>'pjpg',
      ],
      'small' => [
        'w' => 400,
        'h' => 250,
        'fit' => 'crop',
        'fm'=>'pjpg',
        'markpos' => 'top-left',
        'mark' => 'logo_grey.png',
        'markfit' => 'contain',
        'markw' => '13w',
        'markx' => '25',
        'marky' => '25',
        'markalpha' =>'25',
      ],
      'medium' => [
        'w' => 600,
        'h' => 400,
        'fit' => 'crop',
        'fm'=>'pjpg',
        /*
        'markpos' => 'top-right',
        'mark' => '1a.png',
        */
        'markpos' => 'top-left',
        'mark' => 'logo_grey.png',
        'markfit' => 'contain',
        'markw' => '13w',
        'markx' => '25',
        'marky' => '25',
        'markalpha' =>'25',
      ],
      'medium-rank' => [
      	'w' => 600,
        'h' => 400,
        'fit' => 'crop',
        'markpos' => 'top-right',
        'mark' => 'a1_full.png',
        'markfit' => 'contain',
        'fm'=>'pjpg',
      ],
      'medium-new' => [
      	'w' => 600,
        'h' => 400,
        'fit' => 'crop',
        'markpos' => 'top-right',
        'mark' => 'b1_full.png',
        'markfit' => 'contain',
        'fm'=>'pjpg',
      ],
      'large' => [
        'w' => 1024,
        'h' => 720,
        'fm'=>'pjpg',
      ],
      'original' => [
        //'w' => 1024,
        //'h' => 720,
        //'fit' => 'contain',
    		'mark' => 'logo.png',
        'markfit' => 'fill',
        'markh' => '7w',
        'markpos' => 'bottom-right',
        'markalpha' =>'60',
        'markpad'=>'5',
        'fm'=>'pjpg',
      ],
			'chrome_big_picture' => [
				'w' => 500,
        'h' => 500,
        'fit' => 'strech',
        'fm'=>'pjpg',
			],
    ],
    'max_image_size' => 1000*1000,
  ]);
  return $server->getImageResponse($request->getAttribute('params'), $request->getQueryParams());
})->setName('imagen');

$app->group('/inventory', function(){
	$this->get('/{url}[/]', 'RedireccionController:getRedireccion')->setName('redireccion.search');
});

$app->group('/vendetuauto', function() use ($container) {
	$this->get('[/]', 'HomeController:getVendeTuAuto')->setName('vendetuauto');
	$this->group('/vehiculo',function(){
		$this->post('/nuevo', 'HomeController:postRecibirInformacionVehiculo')->setName('vender.nuevo');
		$this->get('/{id:[0-9]+}/eliminar', 'HomeController:eliminarTercero')->setName('vender.eliminar');

		$this->get('/{id:[0-9]+}/publicacion', 'PublicacionController:obtenerPublicacion')->setName('vender.publicacion');
		$this->post('/{id:[0-9]+}/publicacion', 'PublicacionController:modificarPublicacion');

	})->add(new AuthMiddleware($container))->add(new SessionMiddleware($container));
});

$app->group('/compra-tu-auto',function() use ($container){
	$this->get('[/]', function ($request, $response, $args) {
		return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('home'));
	})->setName('compratuauto');

	$this->group('/{titulo}_{id:[0-9]+}',function() use ($container){
		$this->get('/primer-paso','ComprarController:getUsados_PrimerPaso')->setName('compratuauto.primerpaso');
		$this->post('/primer-paso','ComprarController:postUsados_PrimerPaso')->add(new CsrfTokenToHeaders($container->csrf));
	});

});

$app->group('/compra-tu-0km', function() use ($container){
	$this->group('/{titulo}', function() use ($container) {
		$this->get('/primer-paso','ComprarController:get0km_PrimerPaso')->setName('compratu0km.primerpaso');
		$this->post('/primer-paso','ComprarController:post0km_PrimerPaso')->add(new CsrfTokenToHeaders($container->csrf));
	});
});

$app->post('/subir-imagen', 'VehicleController:postImageUpload')->setName('upload.images');
$app->group('/vehiculos', function(){
	$this->get('/{titulo}_{id:[0-9]+}', 'HomeController:vervehiculo')->setName('vehicle.ver');
	$this->get('/filtrar/', function ($request, $response, $args) {
		return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('home'));
	});
	$this->get('/filtrar/{params:.*}/', 'HomeController:filtrarvehiculos')->setName('vehicle.filter');
	$this->get('/filtrar/{params:.*}/pagina/{id}', 'HomeController:filtrarvehiculos')->setName('vehicle.filter_pagina');
});


$app->group('/autos-usados-salta', function(){
	$this->get('/entre/{desde:[0-9]+}-{hasta:[0-9]+}', 'HomeController:filtrarprecio')->setName('vehicle.filter.precio');
	$this->get('/entre/{desde:[0-9]+}-{hasta:[0-9]+}/pagina/{id}', 'HomeController:filtrarprecio')->setName('vehicle.filter.precio_pagina');
});

// RUTA VIEJA. SE APLICA REDIRECCIÓN A LA RUTA NUEVA
$app->group('/marca', function(){
	$this->get('/{id:[0-9]+}_{titulo}', function ($request, $response, $args) {
		return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('paginas.marca', ['titulo' => $args['titulo']]));
	});
});

$app->group('/{titulo}-{id:[0-9]+}', function(){
	$this->get('[/]', function ($request, $response, $args) {
		return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('paginas.marca', ['titulo' => $args['titulo']]));
	});

	$this->get('/landing[/]', 'HomeController:verlanding')->setName('landing.ver');
});

// NUEVA RUTA 0KM
$app->group('/0km/marca/{titulo}', function(){
	$this->get('[/]', 'HomeController:verpaginamarca')->setName('paginas.marca');
});

$app->get('/0km/{titulo:.*}', 'HomeController:verpagina')->setName('paginas.ver');

$app->group('', function() {
	$this->get('/registro[/{return_to:.*}]', 'AuthController:getSignUp')->setName('auth.signup');
	$this->post('/registro', 'AuthController:postSignUp');

	$this->get('/iniciar-sesion[/{return_to:.*}]', 'AuthController:getSignIn')->setName('auth.signin');
	$this->post('/iniciar-sesion', 'AuthController:postSignIn');
	$this->post('/asociar-facebook', 'AuthController:asociar-facebook')->setName('asociar.facebook');

	$this->post('/facebook-login', 'AuthController:postFacebookRequest')->setName('auth.facebook');
})->add(new GuestMiddleware($container));


$app->group('/recuperar',function(){
	$this->get('/email','AuthController:recuperar')->setName('recuperar');
	$this->post('/email','AuthController:recuperarEmail');
	$this->get('/cambiar/{token}','AuthController:recuperarValidar')->setName('recuperar.cambiar');
	$this->post('/cambiar/{token}','AuthController:recuperarCambiar')->setName('recuperar.cambiar');
})->add(new CsrfTokenToHeaders($container->csrf));

$app->group('', function() {
	$this->get('/bienvenido', 'AuthController:FirstWelcome')->setName('auth.firstwelcome');
	$this->get('/cerrar-sesion', 'AuthController:getSignOut')->setName('auth.signout');

})->add(new AuthMiddleware($container))->add(new SessionMiddleware($container));

$app->get('/api-precios-ciro-automotores', 'RecolectorController:getApi')->setName('precios.api');

$app->group('/administracion', function() use ($container) {
	$this->get('[/]', 'AdminController:index')->setName('admin.index');

	$this->get('/busqueda[/{mensaje}]', 'AdminController:busqueda')->setName('admin.busqueda');


	$this->group('/vehiculos', function() {
		$this->get('/preciousd', 'VehicleController:cotizacionenpesos')->setName('vehicle.usd');
		$this->get('[/listado-vehiculos]', 'VehicleController:index')->setName('vehicle.index');
		$this->post('/importar', 'VehicleController:postImportarExcel')->setName('vehicle.importar');
		$this->get('/exportar', 'VehicleController:getExportarExcel')->setName('vehicle.exportar');
		$this->get('/obtener/disponibles', 'VehicleController:getDisponibles')->setName('vehicle.disponibles');

		$this->get('/terceros', 'VehicleController:tercerosIndex')->setName('vehicle.terceros');
		$this->get('/terceros/borrar/{id:[0-9]+}', 'VehicleController:tercerosBorrar')->setName('vehicle.terceros.borrar');
		$this->get('/terceros/publicacion/{id:[0-9]+}', 'PublicacionController:obtenerRenobacion')->setName('vehicle.terceros.publicacion');
		$this->post('/terceros/publicacion/{id:[0-9]+}', 'PublicacionController:modificarRenobacion');
		$this->get('/terceros/publicacion/{id:[0-9]+}/eliminar', 'PublicacionController:eliminarRenobacion')->setName('vehicle.terceros.eliminar');
		$this->post('/terceros/publicacion/{id:[0-9]+}/renobar', 'PublicacionController:renobar')->setName('vehicle.terceros.renobar');

		$this->get('/obtener', 'VehicleController:getAll')->setName('vehicle.todos');
		$this->get('/obtener/vendidos', 'VehicleController:getVendidos')->setName('vehicle.vendidos');
		$this->get('/calcular/{venta:[0-9]+}/{lista:[0-9]+}/{year:[0-9]+}', 'VehicleController:getCalculo')->setName('vehicle.calculo');

		// Rutas para la carga de vehículos 0km
		/*$this->get('/cargar-0km', 'VehicleController:getCargar0km')->setName('vehicle.cargar0km');
		$this->post('/cargar-0km', 'VehicleController:postCargar0km');*/

		// Rutas para la carga de vehículos usados
		$this->get('/cargar-usado', 'VehicleController:getCargar')->setName('vehicle.cargar');
		$this->get('/estado/{id:[0-9]+}', 'VehicleController:cambiarEstado')->setName('vehicle.estado');
		$this->get('/eliminar/{id:[0-9]+}', 'VehicleController:eliminar')->setName('vehicle.eliminar');
		$this->post('/cargar-usado', 'VehicleController:postCargar');
		//
		$this->get('/estadisticas', 'VehicleController:estadisticas')->setName('vehicle.estadisticas');

		$this->group('/{id:[0-9]+}', function(){
			$this->get('[/]', 'VehicleController:getCar')->setName('vehicle.getCar');
			$this->get('/subir-fotos', 'VehicleController:getPhotoUploader')->setName('vehicle.photoupload');
			$this->post('/subir-fotos', 'VehicleController:postPhotoUploader');
			$this->get('/editar', 'VehicleController:getEditor')->setName('vehicle.modify');
			$this->post('/editar', 'VehicleController:postEditor')->setName('vehicle.modifypost');
			$this->get('/editar/precio', 'VehicleController:modificarPrecioVenta')->setName('vehicle.precioventa');
			$this->get('/historial', 'VehicleController:historial')->setName('vehicle.historial');
			$this->get('/dateros', 'VehicleController:dateros')->setName('vehicle.dateros');
			$this->get('/gestionar-fotos', 'VehicleController:getGestionFotos')->setName('vehicle.gestionarfotos');
			$this->get('/gestionar-fotos/{id_imagen}/tipo', 'VehicleController:cambiarTipoFoto')->setName('vehicle.gestionarfotos.tipo');
			$this->get('/gestionar-fotos/eliminar', 'VehicleController:getPhotoEliminar')->setName('vehicle.gestionarfotos.eliminar');
			$this->post('/gestionar-fotos/orden', 'VehicleController:postFotoOrden')->setName('vehicle.gestionarfotos.orden');
			$this->get('/keywords', 'VehicleController:keywords')->setName('vehicle.keywords');
			$this->get('/financiar', 'VehicleController:financiar')->setName('vehicle.financiar');

			$this->get('/formularios','FormularioController:vehiculo')->setName('vehicle.formulario');
			$this->post('/formularios/asociar','FormularioController:asociar')->setName('vehicle.formulario.asociar');
			$this->get('/formularios/{id_formulario}/asociado','FormularioController:getVehiculoFormulario')->setName('vehicle.formulario.asociado');
			$this->get('/formularios/{id_formulario}/eliminar','FormularioController:deleteVehiculoFormulario')->setName('vehicle.formulario.eliminar');
		});

		$this->group('/formularios',function(){
			$this->get('/tablero','FormularioController:tablero')->setName('formulario.tablero');
			$this->post('/','FormularioController:post')->setName('formulario');
			$this->get('/{id:[0-9]+}/obtener','FormularioController:get')->setName('formulario.obtener');
			$this->get('/{id:[0-9]+}/eliminar','FormularioController:delete')->setName('formulario.eliminar');
			$this->post('/orden','FormularioController:postOrden')->setName('formulario.orden');
		});
	});

	$this->group('/promociones', function() {
		$this->get('[/]','PromocionesController:index')->setName('proomociones.index');
	});

	$this->group('/precios', function() {
		$this->get('[/]', 'RecolectorController:index')->setName('precios.index');
		$this->get('/listado', 'RecolectorController:listado')->setName('precios.listado');
		$this->get('/historial/{id:[0-9]+}', 'RecolectorController:verhistorial')->setName('precios.historial');
		$this->get('/recolector', 'RecolectorController:recolector')->setName('precios.recolector');
		$this->group('/0km', function(){
			$this->get('[/]', 'RecolectorController:preciosnuevos')->setName('precios.0km');
			$this->get('/{id:[0-9]+}', 'RecolectorController:verFinanciacion0km')->setName('precios.0km.ver');
			$this->get('/{id:[0-9]+}/print[/{bonificacion:[0-9]+}]', 'RecolectorController:verFinanciacion0km_print')->setName('precios.0km.ver.print');
			$this->get('/{id:[0-9]+}/editar', 'RecolectorController:editarFinanciacion0km')->setName('precios.0km.editar');
			$this->post('/{id:[0-9]+}/edit', 'RecolectorController:editarFinanciacion0km')->setName('precios.0km.editar.post');
		});
	});

	$this->group('/0km', function() {
		$this->get('[/]', 'RecolectorController:index')->setName('0km.index');
	});

	$this->group('/empleados', function() {
		$this->get('[/listado-empleados]', 'EmpleadosController:index')->setName('empleados.index');
		$this->get('/cargar-empleado', 'EmpleadosController:getCargar')->setName('empleados.cargar');
		$this->get('/editar-empleado/empid_{id:[0-9]+}', 'EmpleadosController:getEditar')->setName('empleados.editar');
		$this->get('/eliminar-empleado/empid_{id:[0-9]+}', 'EmpleadosController:getEliminar')->setName('empleados.eliminar');
		$this->post('/cargar-empleado', 'EmpleadosController:postCrear');
		$this->post('/editar-empleado', 'EmpleadosController:postEditar')->setName('empleados.editarpost');

		$this->get('/correos', 'VirtualController:index')->setName('correovirtual.index');
		$this->get('/{id:[0-9]+}/correos', 'VirtualController:agregarCorreo')->setName('empleados.correo.agregar');
		$this->post('/correos', 'VirtualController:agregarUsuarioVirtual')->setName('correovirtual.agregar');
		$this->get('/{id:[0-9]+}/correos/eliminar', 'VirtualController:eliminarUsuarioVirtual')->setName('correovirtual.eliminar');
		$this->get('/{id:[0-9]+}/correos/habilitar', 'VirtualController:habilitarUsuarioVirtual')->setName('correovirtual.habilitar');
		$this->post('/correos/alias', 'VirtualController:agregarAlias')->setName('correovirtual.alias');
		$this->get('/correos/{id:[0-9]+}/alias/eliminar', 'VirtualController:eliminarAlias')->setName('correovirtual.alias.eliminar');

	});

	$this->group('/prospectos', function(){
		$this->get('/listado-prospectos[/{lista}]', 'ProspectosController:getListado')->setName('prospectos.listado.index');
		$this->get('[/]', 'ProspectosController:index')->setName('prospectos.index');
		$this->get('/cargar-prospecto[/{consulta}]', 'ProspectosController:getCarga')->setName('prospectos.cargar');
		$this->get('/ultimo-prospecto', 'ProspectosController:getUltimoProspecto')->setName('prospectos.ultimo');
		$this->get('/todos-prospecto', 'ProspectosController:getTodos')->setName('prospectos.todos');
		$this->get('/rendimiento-prospecto', 'ProspectosController:getRendimiento')->setName('prospectos.rendimiento');
		$this->post('/cargar-prospecto', 'ProspectosController:postCarga')->setName('prospectos.cargar.post');
		$this->get('/{id:[0-9]+}/editar', 'ProspectosController:getEditar')->setName('prospectos.editar');
		$this->post('/{id:[0-9]+}/editar', 'ProspectosController:postEditarRevision');
		$this->post('/yallame', 'ProspectosController:postLlameProspecto')->setName('prospecto.yallame');
		$this->post('/cambiarobservacion', 'ProspectosController:postCambiarObservacion')->setName('prospecto.cambiarobservacion');
		$this->post('/editar-prospecto', 'ProspectosController:postEditar')->setName('prospecto.update');

		//API Prospectos
		$this->get('/listado', 'ProspectosController:get')->setName('prospectos.listado');
		$this->get('/listadoLibres', 'ProspectosController:getLibres')->setName('prospectos.listadoLibres');
		$this->get('/listadoUrgentes', 'ProspectosController:getUrgentes')->setName('prospectos.listadoUrgentes');
		$this->get('/getinfo', 'ProspectosController:getInfo')->setName('prospecto.getInfo');
		$this->post('/push/{', 'ProspectosController:postPush')->setName('prospecto.push');

		// Botones:
		$this->get('/{id:[0-9]+}/aprobar', 'ProspectosController:getAprobar')->setName('prospectos.aprobar');
		$this->post('/prospecto-transferir', 'ProspectosController:postTransferir')->setName('prospectos.transferir');
		$this->get('/prospecto-transferir/{id:[0-9]+}', 'ProspectosController:getTransferir')->setName('prospectos.transferirme');
		$this->get('/prospecto-liberar/{id:[0-9]+}', 'ProspectosController:getLiberar')->setName('prospectos.liberarme');
		$this->get('/{id:[0-9]+}/eliminar/', 'ProspectosController:getEliminar')->setName('prospectos.eliminar');
		$this->post('/prospecto/revisar', 'ProspectosController:postRevisar')->setName('prospectos.revisar');

		// botones ventas:
		$this->get('/sumar/{id:[0-9]+}', 'ProspectosController:getSumar')->setName('prospectos.sumar');
		$this->get('/restar/{id:[0-9]+}', 'ProspectosController:getRestar')->setName('prospectos.restar');
		$this->get('/sumar0km/{id:[0-9]+}', 'ProspectosController:getSumar0km')->setName('prospectos.sumar0km');
		$this->get('/restar0km/{id:[0-9]+}', 'ProspectosController:getRestar0km')->setName('prospectos.restar0km');

		// revisar si el telefono existe
		$this->get('/revisartelefono[/{tlf}]', 'ProspectosController:revisarTelefono')->setName('prospectos.revisartelefono');
	});


	$this->group('/clientes', function() {
		$this->get('[/listado-clientes]', 'ClientController:index')->setName('cliente.index');
		$this->get('/consultas', 'ClientController:consultas')->setName('cliente.consultas');
		$this->get('/nuevo', 'ClientController:getCargar')->setName('cliente.nuevo-cliente');
		$this->post('/nuevo', 'ClientController:postCargar');
		$this->get('/{id:[0-9]+}/ver', 'ClientController:getVer')->setName('cliente.ver');
		$this->get('/{id:[0-9]+}/editar', 'ClientController:getEditar')->setName('cliente.editar');
		$this->post('/{id:[0-9]+}/editar', 'ClientController:postEditar');
		$this->get('/{id:[0-9]+}/eliminar', 'ClientController:getEliminar')->setName('cliente.eliminar');

		$this->get('/todos', 'ClientController:todos')->setName('cliente.todos');
		$this->get('/consultar', 'ClientController:getAll')->setName('cliente.consultar');

		$this->get('/ciudades', 'ClientController:getCiudad')->setName('cliente.ciudad.consultar');
		$this->get('/actividades', 'ClientController:getActividadEconomica')->setName('cliente.actividad.consultar');
		$this->get('/actividades/tabla', 'ClientController:getTablaActividadEconomica')->setName('cliente.actividad.tabla');


		$this->get('/datero/tablero', 'DateroController:index')->setName('cliente.datero.index');
		$this->get('/datero/consultar', 'DateroController:getAll')->setName('cliente.datero.consultar');
		$this->get('/datero/cargar', 'DateroController:getCargar')->setName('cliente.datero.cargar');
		$this->post('/datero/cargar', 'DateroController:postCargar');
		$this->get('/datero/{id:[0-9]+}/editar', 'DateroController:getEditar')->setName('cliente.datero.editar');
		$this->post('/datero/{id:[0-9]+}/editar', 'DateroController:postEditar');
		$this->get('/datero/{id:[0-9]+}/ver', 'DateroController:getVer')->setName('cliente.datero.ver');
		$this->get('/datero/{id:[0-9]+}/estado/{id_tipo_estado}', 'DateroController:getCambiarEstado')->setName('cliente.datero.estado');
		$this->get('/datero/{id:[0-9]+}/eliminar', 'DateroController:getEliminar')->setName('cliente.datero.eliminar');
		$this->get('/datero/{id:[0-9]+}/imprimir', 'DateroController:imprimir')->setName('cliente.datero.imprimir');

		$this->group('/suscripcion', function(){
			$this->get('/index','SuscripcionController:index')->setName('suscripcion.index');
			$this->get('/consultar','SuscripcionController:getAll')->setName('suscripcion.consultar');
			$this->get('/{id:[0-9]+}/eliminar','SuscripcionController:eliminar')->setName('suscripcion.eliminar');
			$this->get('/consultar-campañas','SuscripcionController:consultarCampañas')->setName('suscripcion.campaña.consultar');
			$this->post('/campania','SuscripcionController:enviarCampaña')->setName('suscripcion.campaña');

			$this->get('/consultar-paginas','SuscripcionController:getConsultas')->setName('suscripcion.paginas.consultar');

		});
	});

	$this->group('/publicaciones', function() {
		$this->get('[/listado-publicaciones]', 'PublicacionController:index')->setName('publicaciones.index');
		$this->get('/crear-publicacion/vehid_{id:[0-9]+}', 'PublicacionController:getCrear')->setName('publicaciones.crear');
		$this->get('/editar-publicacion/pubid_{id:[0-9]+}', 'PublicacionController:getEditar')->setName('publicaciones.editar');
		$this->get('/eliminar-publicacion/pubid_{id:[0-9]+}', 'PublicacionController:getEliminar')->setName('publicaciones.eliminar');
		$this->post('/editar-publicacion', 'PublicacionController:postEditar')->setName('publicaciones.editarpost');
		$this->post('/crear-publicacion/vehid_{id:[0-9]+}', 'PublicacionController:postCrear');
		$this->post('/crear-generico/', 'PublicacionController:crearGenericos')->setName('publicaciones.creargenerico');
	});

	$this->group('/paginas', function() use ($container) {

		$this->get('/crear', 'PageController:getCrear')->setName('paginas.crear');
		$this->post('/crear', 'PageController:postCrear')->add(new CsrfTokenToHeaders($container->csrf));

		/* ICONOS */
		$this->get('/cargar-icono', 'PageController:getCargarIcono')->setName('paginas.cargar.icono');
		$this->post('/cargar-icono', 'PageController:postCargarIcono');
		$this->get('/listado-iconos', 'PageController:getListadoIconos')->setName('paginas.listado.icono');

		$this->get('[/listado-paginas]', 'PageController:getListado0km')->setName('paginas.index');
		/*$this->get('/crear', 'PageController:getCrear');																		->setName('paginas.crear');
		$this->post('/crear', 'PageController:postCrear');*/
		$this->get('/{id:[0-9]+}/preview', 'PageController:getPreview')->setName('paginas.preview');
		$this->get('/preview', 'PageController:getPreviewActual')->setName('paginas.preview_simple');

		$this->get('/{id:[0-9]+}/editar', 'PageController:getEditar')->setName('paginas.editar');
		$this->post('/{id:[0-9]+}/editar', 'PageController:postEditar')->add(new CsrfTokenToHeaders($container->csrf));
		$this->get('/{id:[0-9]+}/eliminar', 'PageController:getEliminar')									->setName('paginas.eliminar');
		$this->post('/{id:[0-9]+}/imagen', 'PageController:postImagen')										->setName('paginas.imagen');

		$this->get('/{id:[0-9]+}/mostrar', 'PageController:mostrar')										  ->setName('paginas.mostrar');
		$this->get('/{id:[0-9]+}/ocultar', 'PageController:ocultar')										  ->setName('paginas.ocultar');

		$this->group('/{id_pagina:[0-9]+}/bloques', function() {
			$this->get('/','PageController:getBloques')																      ->setName('paginas.bloques');
			$this->get('/{id:[0-9]+}/editar', 'PageController:getBloquesEditar')			      ->setName('paginas.bloques.editar');
			$this->post('/{id:[0-9]+}/editar', 'PageController:postBloquesEditar');
			$this->get('/{id:[0-9]+}/eliminar', 'PageController:getBloquesEliminar')	      ->setName('paginas.bloques.eliminar');
			$this->get('/crear', 'PageController:getCrearBloque')					                  ->setName('paginas.bloques.crear');
			$this->post('/crear', 'PageController:postCrearBloque');
			$this->post('/orden', 'PageController:postBloquesOrden')										    ->setName('paginas.bloques.orden');
		});

		$this->group('/fotos', function() {
			$this->get('/pagid_{id:[0-9]+}','PageController:getFotos')										->setName('paginas.fotos');
			$this->get('/fotid_{id:[0-9]+}/editar', 'PageController:getFotosEditar')			->setName('paginas.fotos.editar');
			$this->post('/editar-bloque', 'PageController:postFotosEditar')								->setName('paginas.fotos.editarpost');
			$this->get('/fotid_{id:[0-9]+}/eliminar', 'PageController:getFotosEliminar')	->setName('paginas.fotos.eliminar');
			$this->get('/crear/pagid_{id:[0-9]+}', 'PageController:getCrearFoto')				  ->setName('paginas.fotos.crear');
			$this->post('/crearpost/', 'PageController:postCrearFoto')										->setName('paginas.fotos.crearpost');
		});

	});

	$this->group('/cajas',function(){
		//COMPROBANTE
		$this->group('/comprobante',function(){

			//INFORMES:
			$this->get('/informe-index', 'ComprobanteController:informeindex')->setName('informe.index');
			$this->get('/informe-final/{semana:[0-9]+}', 'ComprobanteController:informefinal')->setName('informe.final');
			$this->get('/informe-final-api/{semana:[0-9]+}/{tabla:[0-9]+}', 'ComprobanteController:informefinalCONSULTA')->setName('informe.final.consulta');
			$this->get('/informe-cuenta', 'ComprobanteController:informecuenta')->setName('informe.cuenta');
			$this->get('/informe-cuenta-consulta/{mes:[0-9]+}/{sucursal:[0-9]+}', 'ComprobanteController:informecuentaconsulta')->setName('informe.cuenta.consulta');
			$this->get('/informe-cuenta-transacciones/{mes:[0-9]+}/{sucursal:[0-9]+}/{cuenta:[0-9]+}', 'ComprobanteController:informecuentatransacciones')->setName('informe.cuenta.transacciones');

			//VENTAS
			$this->get('/venta','ComprobanteController:nuevoFacturaVenta')										->setName('comprobante.venta.nuevo');
			$this->post('/venta','ComprobanteController:agregarFacturaVenta')									->setName('comprobante.venta.agregar');
			$this->get('/tablero-venta','ComprobanteController:tableroVenta')							  		->setName('comprobante.venta.tablero');
			$this->get('/consulta-venta','ComprobanteController:getFacturasVenta')								->setName('comprobante.venta.consultar');
			//COMPRAS
			$this->get('/compra','ComprobanteController:nuevoFacturaCompra')									->setName('comprobante.compra.nuevo');
			$this->post('/compra','ComprobanteController:agregarFacturaCompra')									->setName('comprobante.compra.agregar');
			$this->get('/tablero-compra','ComprobanteController:tableroCompra')							  		->setName('comprobante.compra.tablero');
			$this->get('/consulta-compra','ComprobanteController:getFacturasCompra')							->setName('comprobante.compra.consultar');

			$this->get('/factura/{id:[0-9]+}/cobrar','ComprobanteController:cobrarFactura')						->setName('comprobante.factura.cobrar');
			$this->get('/factura/{id:[0-9]+}/cobrarEFECTIVO','ComprobanteController:cobrarFacturaEfectivo')		->setName('comprobante.factura.cobrar.efectivo');
			$this->get('/factura/{id:[0-9]+}/cobrarCC','ComprobanteController:cobrarFacturaCuentaCorriente')	->setName('comprobante.factura.cobrar.cc');
			$this->post('/factura/{id:[0-9]+}/cobrar','ComprobanteController:agregarMovimientoFactura');
			$this->get('/factura/{id_factura:[0-9]+}/recibo/{id_recibo:[0-9]+}/eliminar','ComprobanteController:eliminarMovimientoFactura')->setName('comprobante.factura.recibo.eliminar');
			$this->get('/factura/{id_factura:[0-9]+}/recibo/{id_recibo:[0-9]+}/pagar','ComprobanteController:pagarMovimientoFactura')->setName('comprobante.factura.recibo.pagar');
			$this->get('/factura/{id:[0-9]+}/eliminar','ComprobanteController:eliminarFactura')->setName('comprobante.factura.eliminar');

			$this->get('/padron','ComprobanteController:consultaPadron')->setName('comprobante.padron');
			$this->get('/factura/{id:[0-9]+}/error','ComprobanteController:errorComprobante')->setName('comprobante.error');
			$this->get('/factura/{id:[0-9]+}/imprimir','ComprobanteController:imprimir')->setName('comprobante.imprimir');

			$this->get('/estadisticas','ComprobanteController:estadisticas')->setName('comprobante.estadisticas');

		});


		$this->group('/proveedores',function(){
			$this->get('/todos', 'ProveedorController:todos')->setName('proveedor.todos');
			$this->get('/tablero', 'ProveedorController:index')->setName('proveedor.index');
			$this->get('/consultar', 'ProveedorController:getAll')->setName('proveedor.consultar');
			$this->get('/cargar', 'ProveedorController:getCargar')->setName('proveedor.cargar');
			$this->post('/cargar', 'ProveedorController:postCargar');
			$this->get('/{id:[0-9]+}/editar', 'ProveedorController:getEditar')->setName('proveedor.editar');
			$this->get('/{id:[0-9]+}/cuenta', 'ProveedorController:getCuenta')->setName('proveedor.cuenta');
			$this->post('/{id:[0-9]+}/editar', 'ProveedorController:postEditar');
			$this->get('/{id:[0-9]+}/eliminar', 'ProveedorController:getEliminar')->setName('proveedor.eliminar');
		});

		$this->group('/cuentas',function(){
			$this->get('[/]', 'CuentaController:index')->setName('cuenta.index');
			$this->get('/crear', 'CuentaController:crear')->setName('cuenta.crear');
			$this->post('/crear', 'CuentaController:crearpost')->setName('cuenta.crear.post');
			$this->get('/{id:[0-9]+}/cerrar', 'CuentaController:getEliminar')->setName('cuenta.cerrar');
			$this->get('/{id:[0-9]+}/movimientos', 'ComprobanteController:tableroMovimientosCuentaCorriente')->setName('cuenta.movimientos');
			$this->get('/{id:[0-9]+}/movimiento', 'ComprobanteController:nuevaFacturaCuentaCorriente')->setName('cuenta.crearmovimiento');
			$this->get('/{id:[0-9]+}/cancelar', 'CuentaController:cancelar')->setName('cuenta.cancelar');
			$this->get('/{id:[0-9]+}/consulta','ComprobanteController:getFacturasCuentaCorriente')->setName('cuenta.facturas.consultar');
			$this->post('/{id:[0-9]+}/crearfactura','ComprobanteController:agregarFacturaCuentaCorriente')->setName('cuenta.factura.agregar');
		});

		//MOVIMIENTO
		$this->group('/movimiento',function(){
			//$this->get('/nuevo','MovimientoController:nuevo')											          ->setName('comprobante.movimiento.nuevo');
			//$this->post('/nuevo','MovimientoController:agregar')											      ->setName('comprobante.movimiento.agregar');
			$this->get('/tablero','MovimientoController:tablero')											      ->setName('comprobante.movimiento.tablero');
			$this->get('/consulta','MovimientoController:getAll')													  ->setName('comprobante.movimiento.consultar');
			$this->get('/{id:[0-9]+}/eliminar','MovimientoController:eliminar')							->setName('comprobante.movimiento.eliminar');
			$this->post('/transferir','MovimientoController:transferir')										->setName('comprobante.movimiento.transferir');
			$this->get('/responsables','MovimientoController:responsables')									->setName('comprobante.movimiento.responsables');

			$this->get('/reporte','MovimientoController:reporte')														->setName('comprobante.movimiento.reporte');
		});

		//TRANSACCIONES
		$this->get('/transacciones','TransaccionController:index')												->setName('transacciones.index');

		$this->post('/transacciones/modo','ModoController:agregarMetodoPago')					    ->setName('modo.crear');
		$this->get('/transacciones/modo','ModoController:obtenerMetodoPago')					    ->setName('modo.obtener');
		$this->post('/transacciones/modo/editar','ModoController:editarMetodoPago')	      ->setName('modo.editar');

		$this->get('/transacciones/categoria','TipoTransaccionController:obtenerCategoria')->setName('categoria.obtener');
		$this->post('/transacciones/tipostransaccion','TipoTransaccionController:agregarTipoTransaccion')->setName('tipostransaccion.crear');
		$this->get('/transacciones/tipostransaccion','TipoTransaccionController:obtenerTipoTransaccion')->setName('tipostransaccion.obtener');
		$this->post('/transacciones/tipostransaccion/editar','TipoTransaccionController:editarTipoTransaccion')->setName('tipostransaccion.editar');
		$this->get('/transacciones/tipostransaccion/{id:[0-9]+}/eliminar','TipoTransaccionController:eliminarTipoTransaccion')->setName('tipostransaccion.eliminar');

		$this->post('/sucursal','TransaccionController:sucursal')													->setName('caja.sucursal');

		$this->get('/diaria','DiariaController:index')						  											->setName('diaria.index');
		$this->get('/diaria/{id:[0-9]+}','DiariaController:getDiaria')						  							->setName('diaria.ver');
		$this->get('/diaria/reporte','DiariaController:getReporteExcel')						  		->setName('diaria.reporte');

		//OBLIGACIONES
		$this->get('/obligaciones','ObligacionController:index')													->setName('obligacion.index');
		$this->get('/obligaciones/cheque/estado','ObligacionController:estadoCheque')			->setName('cheque.estado');
		$this->get('/obligaciones/documento/estado','ObligacionController:estadoDocumento')->setName('documento.estado');
		$this->post('/obligaciones/{id:[0-9]+}/transferir','ObligacionController:transferir')->setName('obligacion.transferir');
	});

	$this->group('/carpetas',function(){
		$this->get('/tablero','CarpetaController:tablero')																	->setName('carpeta.tablero');
		$this->get('/getAll','CarpetaController:getAll')																		->setName('carpeta.consultar');
		$this->get('/{id:[0-9]+}','CarpetaController:get')																	->setName('carpeta');
		$this->post('/nuevo','CarpetaController:post')																			->setName('carpeta.nuevo');
		$this->get('/{id_datero:[0-9]+}/nuevo','CarpetaController:desdeDatero')						  ->setName('cliente.datero.carpeta.nuevo');
		$this->get('/{id:[0-9]+}/cliente/{id_cliente}/vincular','CarpetaController:vincularCliente')->setName('carpeta.cliente.vincular');
		$this->get('/{id:[0-9]+}/cliente/{id_cliente}/desvincular','CarpetaController:desvincularCliente')->setName('carpeta.cliente.desvincular');
		$this->get('/{id:[0-9]+}/titular/{id_cliente}','CarpetaController:titular')					->setName('carpeta.titular');
		$this->get('/{id:[0-9]+}/cuentacorriente','CarpetaController:crearCuenta')					->setName('carpeta.cuenta');
		$this->get('/{id:[0-9]+}/cerrar','CarpetaController:cerrar')												->setName('carpeta.cerrar');
		$this->get('/{id:[0-9]+}/aprobar','CarpetaController:aprobar')											->setName('carpeta.aprobar');

	});

	$this->group('/correo',function(){
		$this->get('/index','CorreoController:index')																				->setName('correo');
		$this->post('/enviar','CorreoController:post')																			->setName('correo.enviar');
		$this->post('/login','CorreoController:login')																			->setName('correo.login');
		$this->get('/logout','CorreoController:logout')																			->setName('correo.logout');

		$this->get('/','CorreoController:getAll')																						->setName('correo.getAll');
		$this->get('/{id:[0-9]+}','CorreoController:get')																		->setName('correo.get');
		$this->get('/eliminar/{id:[0-9]+}','CorreoController:eliminar')									  	->setName('correo.eliminar');

		$this->get('/carpetas','CorreoController:getAllMailBoxes')													->setName('correo.folder');
		$this->get('/carpetas/cambiar/{nombre}','CorreoController:cambiarMailbox')					->setName('correo.folder.cambiar');
		$this->get('/carpetas/crear/{nombre}','CorreoController:crearMailBox')							->setName('correo.folder.crear');

	});

	$this->group('/godmode', function() {
		$this->get('[/]', 'ModulosController:index')->setName('godmode.index');

		$this->group('/modulo',function(){
			$this->post('/crear','ModulosController:crearModulo')											      	->setName('godmode.modulo.crear');
			$this->post('/{id:[0-9]+}/editar','ModulosController:editarModulo')								->setName('godmode.modulo.editar');
			$this->post('/{id:[0-9]+}/eliminar','ModulosController:eliminarModulo')						->setName('godmode.modulo.eliminar');

		});
		$this->group('/enlace',function(){
			$this->post('/crear','ModulosController:crearEnlace')											      	->setName('godmode.enlace.crear');
			$this->post('/{id:[0-9]+}/editar','ModulosController:editarEnlace')								->setName('godmode.enlace.editar');
			$this->post('/{id:[0-9]+}/eliminar','ModulosController:eliminarEnlace')						->setName('godmode.enlace.eliminar');

		});

		$this->get('/usuarios','UserController:index')																			->setName('godmode.user');
		$this->get('/usuarios/{id:[0-9]+}/sesiones','UserController:getSesiones')						->setName('godmode.session');
		$this->get('/usuarios/{id:[0-9]+}/actividades','UserController:getActividades')			->setName('godmode.actividad');

		$this->group('/rendimiento',function(){
			$this->get('[/]','AdminController:rendimiento')															->setName('godmode.rendimiento');
			$this->get('/desarrollo-a','AdminController:rendimiento_desarrollo')										->setName('godmode.rendimiento.desarrollo.activar');
			$this->get('/desarrollo-d','AdminController:rendimiento_desarrollo_desactivar')							->setName('godmode.rendimiento.desarrollo.desactivar');
			$this->get('/twig','AdminController:rendimiento_desarrollo_desactivar')							->setName('godmode.rendimiento.desarrollo.twig');
		});
	});

	$this->group('/tickets', function() {
		$this->get('[/]', 'TicketController:index')->setName('tickets.index');
		$this->get('/{id:[0-9]+}/', 'TicketController:verTicket')->setName('tickets.ver');
		$this->get('/departamento','TicketController:indexDepartamento')->setName('tickets.departamento');
		$this->post('/crear', 'TicketController:crearTicket')->setName('tickets.crear');
		$this->post('/responder/{id:[0-9]+}', 'TicketController:responderTicket')->setName('tickets.responder');
		$this->get('/estado/{id:[0-9]+}/{estado:[0-9]+}', 'TicketController:estadoTicket')->setName('tickets.estado');
		$this->get('/prioridad/{id:[0-9]+}/{prioridad:[0-9]+}', 'TicketController:prioridadTicket')->setName('tickets.prioridad');
		$this->get('/archivar/{id:[0-9]+}/', 'TicketController:archivarTicket')->setName('tickets.archivar');
	});

	$this->group('/notificaciones', function() {
		$this->get('[/]', 'NotificacionController:index')->setName('notificaciones.index');
		$this->post('/enviar', 'NotificacionController:postEnviar')->setName('notificaciones.enviar');
		$this->get('/visto/{id:[0-9]+}', 'NotificacionController:ver')->setName('notificaciones.visto');
		$this->get('/cargar/{id:[0-9]+}/{prioridad:[0-9]+}/{cat:[0-9]+}/{mensaje}', 'NotificacionController:cargar')->setName('notificaciones.cargar');
		$this->get('/asociar', 'NotificacionController:asociarUsuario')->setName('notificaciones.asociar');
		$this->get('/desasociar', 'NotificacionController:desasociarUsuario')->setName('notificaciones.desasociar');

		$this->get('/correos', 'ModuloCorreoController:index')->setName('correo.modulo');
		$this->post('/correos', 'ModuloCorreoController:post');
		$this->get('/correos/{id:[0-9]+}/eliminar', 'ModuloCorreoController:eliminar')->setName('correo.modulo.eliminar');

	});


	$this->group('/crm', function() {
		$this->get('[/]', 'NotificacionController:index')->setName('crm.index');
		$this->group('/facebook', function() {
			$this->get('/respondedor', 'CRMController:facebook_respondedor')->setName('crm.facebook.respondedor');
			$this->get('/respondedor/obtener', 'CRMController:facebook_respondedor_post')->setName('crm.facebook.respondedor.obtener');
		});

	});

	$this->group('/perfil', function() {
		$this->get('[/]', 'PerfilController:index')->setName('perfil.index');
		$this->get('/cambiarfoto/{id:[0-9]+}/{foto}', 'PerfilController:cambiarfoto')->setName('perfil.cambiarfoto');
		$this->post('/contrasenia', 'PerfilController:cambiarContraseña')->setName('perfil.contraseña');
		$this->post('/datos', 'PerfilController:cambiarDatos')->setName('perfil.datos');

	});

	$this->group('/bugs', function() {
		$this->get('[/listado-bugs]', 'BugsController:index')->setName('bugs.index');
		$this->get('/bugs-aprobar/bugid_{id:[0-9]+}', 'BugsController:getAprobar')->setName('bugs.aprobar');
		$this->get('/bugs-eliminar/bugid_{id:[0-9]+}', 'BugsController:getEliminar')->setName('bugs.eliminar');
		$this->post('/bugs-crear', 'BugsController:postCrear')->setName('bugs.crear');
	});

	$this->group('/modulos', function() {
		$this->get('[/listado-modulos]', 'ModulosController:index')->setName('modulos.index');
	});

	$this->group('/ayuda', function() {
		$this->get('[/]', 'AyudaController:index')->setName('ayuda.index');
		$this->get('/{id:[0-9]+}/', 'AyudaController:verManual')->setName('ayuda.ver');
		$this->get('/crear', 'AyudaController:crearManual')->setName('ayuda.crear');
		$this->get('/editar/{id:[0-9]+}', 'AyudaController:editarManual')->setName('ayuda.editar');
		$this->post('/editar/{id:[0-9]+}', 'AyudaController:editarManualPost')->setName('ayuda.editarpost');
		$this->get('/eliminar/{id:[0-9]+}', 'AyudaController:eliminarManual')->setName('ayuda.eliminar');
		$this->post('/crear', 'AyudaController:crearManualPost')->setName('ayuda.crearpost');
	});

})->add(new AdminMiddleware($container))->add(new SessionMiddleware($container));

?>
