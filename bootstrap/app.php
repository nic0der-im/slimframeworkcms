<?php

ini_set('date.timezone', 'America/Argentina/Salta');
session_set_cookie_params(14400);
session_start();

define('VENDEDOR', 2);
define('SNIPER', 3);
setlocale(LC_TIME,"es_AR");

//ini_set('max_execution_time', 0);

use Respect\Validation\Validator as v;

require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/settings.php';

$c = new \Slim\Container($settings);

$c['notFoundHandler'] = function ($c) {
  return function ($request, $response) use ($c) {
    return $c->view->render($response->withStatus(404), 'guest_views/404.twig', [
    	'url'=>$_SERVER['REQUEST_URI']
    ]);
  };
};
/*$c['errorHandler'] = function ($c) {
    return new App\Handlers\Error($c['logger']);
};*/


$app = new \Slim\App($c);



$container = $app->getContainer();

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();

$capsule->bootEloquent();
v::with('App\\Validation\\Rules\\');

$container['db'] = function($container) use ($capsule) {
	return $capsule;
};

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    //$handler = new Monolog\Handler\StreamHandler($settings['path'], $settings['level']);
    $handler = new \Monolog\Handler\RotatingFileHandler($settings['path'], $settings['level']);
    $lineFormatter = new \Monolog\Formatter\LineFormatter(null, null, true);
		$handler->setFormatter($lineFormatter);
    $logger->pushHandler($handler);
    return $logger;
};

$container['flash'] = function($container) {
	return new \Slim\Flash\Messages;
};

$container['auth'] = function ($container) {
	return new \App\Auth\auth;
};

$container['vehiculos'] = function ($container) {
	return new \App\Funcionalidades\Vehiculos;
};

$container['view'] = function($container) {
	$view = new \Slim\Views\Twig(__DIR__.'/../resources/views', [
		'cache'=>false,
	]);

	$view->addExtension(new \Slim\Views\TwigExtension(
		$container->router,
		$container->request->getUri()
	));
  $view->addExtension(new Twig_Extensions_Extension_Intl());
	//$view->addExtension(new Twig_Extensions_Extension_Date());
  $timeago = new Twig_SimpleFilter('timeago', function ($datetime) {
    $time = time() - strtotime($datetime);
    $units = array (
      31536000 => 'año',
      2592000 => 'mes',
      604800 => 'semana',
      86400 => 'dia',
      3600 => 'hora',
      60 => 'minuto',
      1 => 'segundo'
    );
    foreach ($units as $unit => $val) {
      if ($time < $unit) continue;
      $numberOfUnits = floor($time / $unit);
      return ($val == 'segundo')? 'instantes' :
             (($numberOfUnits>1) ? $numberOfUnits : '1 ')
             .' '.$val.(($numberOfUnits>1) ? (($val=='mes')?'es':'s') : '');
    }
  });
  $view->getEnvironment()->addFilter($timeago);

	$sinacentos = new Twig_Function('sinacentos', function ($string) {
		return r_sinacentos($string);
	});

	$view->getEnvironment()->addFunction($sinacentos);

	$customurlfiltroborrar = new Twig_Function('customurlfiltroborrar', function ($url, $search) {
		$firststep = explode($search, $url);
		$secondstep = explode('/', $firststep[1]);
		$thirdstep = $search.r_sinacentos($secondstep[0]).'/';
		$fourthstep = str_replace($thirdstep, '/', $url);
		if(strpos($search, 'marca')>0){
			$search = '/modelo/';
			$firststep = explode($search, $fourthstep);
			if(isset($firststep[1])){
				$secondstep = explode('/', $firststep[1]);
				$thirdstep = $search.r_sinacentos($secondstep[0]).'/';
				$fourthstep = str_replace($thirdstep, '/', $fourthstep);
			}
		}
		return $fourthstep;
	});

	$view->getEnvironment()->addFunction($customurlfiltroborrar);


	$customurlfiltro = new Twig_Function('customurlfiltro', function ($path, $category, $string) {
		$url = $path.$category;
		$string = r_sinacentos($string);
		$string = strtolower($string);
		$string = str_replace(' ', '-', $string);
		$url .= '/' . $string.'/';

		$parametros = str_replace(array('/marca/', 'marca/', '/marca'), '#!#marca#@#', $url);
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
				$filtred_tags[$tmp_tag[0]] = $tmp_tag[1];
			}
		}
		$salida = 'vehiculos/filtrar';
		if(isset($filtred_tags['marca'])){
			$salida = $salida.'/marca/'.str_replace('/', '', $filtred_tags['marca']);
		}
		if(isset($filtred_tags['ubicacion'])){
			$salida = $salida.'/ubicacion/'.str_replace('/', '', $filtred_tags['ubicacion']);
		}
		if(isset($filtred_tags['year'])){
			$salida = $salida.'/year/'.str_replace('/', '', $filtred_tags['year']);
		}
		if(isset($filtred_tags['transmision'])){
			$salida = $salida.'/transmision/'.str_replace('/', '', $filtred_tags['transmision']);
		}
		if(isset($filtred_tags['motor'])){
			$salida = $salida.'/motor/'.str_replace('/', '', $filtred_tags['motor']);
		}
		if(isset($filtred_tags['modelo'])){
			$salida = $salida.'/modelo/'.str_replace('/', '', $filtred_tags['modelo']);
		}
		if(isset($filtred_tags['precio'])){
			$salida = $salida.'/precio/'.str_replace('/', '', $filtred_tags['precio']);
		}
		return $salida.'/';
	});

	$view->getEnvironment()->addFunction($customurlfiltro);

	$view->getEnvironment()->addGlobal("production", $container['settings']['production']);

	$view->getEnvironment()->addGlobal("static_assets", '//'.$_SERVER['HTTP_HOST']);


	$view->getEnvironment()->addGlobal("current_path", $container["request"]->getUri()->getPath());

	$current_path = $container["request"]->getUri()->getPath();
	$current_path = substr($current_path, 1);

	$view->getEnvironment()->addGlobal("current_path_wb", $current_path);

	$view->getEnvironment()->addGlobal('auth',[
		'check' => $container->auth->check(),
		'user' => $container->auth->user(),
		'individuo' => $container->auth->individuo(),
		'empleado' => $container->auth->empleado(),
		'permisos' => $container->auth->getPermisos(),
		'permisosEnlaces' => $container->auth->getPermisosEnlaces(),
    'modulos' => $container->auth->getModulos(),
    'sucursal' => $container->auth->sucursal(),
    'sucursales' => $container->auth->sucursales(),
		'notificaciones' => $container->auth->notificaciones(),
	]);

	$view->getEnvironment()->addGlobal('vehiculos',[
		'marcas'=> $container->vehiculos->listar_marcas()
	]);

	$view->getEnvironment()->addGlobal('flash', $container->flash);

	$remove_accent = new Twig_SimpleFilter('remove_accent', function ($string) {
    return strtr(utf8_decode($string), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
	});
	$view->getEnvironment()->addFilter($remove_accent); // add this

	return $view;
};

$container['validator'] = function($container) {
	return new App\Validation\Validator;
};

$container['HomeController'] = function($container) {
	return new \App\Controllers\HomeController($container);
};

$container['ComprarController'] = function($container) {
	return new \App\Controllers\Home\ComprarController($container);
};
$container['SuscripcionController'] = function($container) {
	return new \App\Controllers\Home\SuscripcionController($container);
};

$container['AuthController'] = function($container) {
	return new \App\Controllers\Auth\AuthController($container);
};

$container['csrf'] = function ($container) {
	return new \Slim\Csrf\Guard;
};

function r_sinacentos($string) {
	$string = trim($string);
	$string = str_replace(
			array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
			array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
			$string
	);
	$string = str_replace(
			array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
			array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
			$string
	);
	$string = str_replace(
			array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
			array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
			$string
	);
	$string = str_replace(
			array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
			array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
			$string
	);
	$string = str_replace(
			array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
			array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
			$string
	);
	$string = str_replace(
			array('ñ', 'Ñ', 'ç', 'Ç'),
			array('n', 'N', 'c', 'C'),
			$string
	);
	return $string;
};

// CONTROLADORES PROPIOS


$container['AdminController'] = function($container) {
	return new \App\Controllers\AdminController($container);
};

$container['UserController'] = function($container) {
	return new \App\Controllers\UserController($container);
};

$container['VehicleController'] = function($container) {
	return new \App\Controllers\VehicleController($container);
};

$container['ProspectosController'] = function($container) {
	return new \App\Controllers\ProspectosController($container);
};

//////////////////////////NEGOCIO////////////////////////////////

$container['ClientController'] = function($container) {
	return new \App\Controllers\ClientController($container);
};

$container['DateroController'] = function($container) {
	return new \App\Controllers\Negocio\DateroController($container);
};

$container['CarpetaController'] = function($container) {
	return new \App\Controllers\Negocio\CarpetaController($container);
};

$container['FormularioController'] = function($container) {
	return new \App\Controllers\Negocio\FormularioController($container);
};
//FIN NEGOCIO
$container['PublicacionController'] = function($container) {
	return new \App\Controllers\PublicacionController($container);
};

$container['EmpleadosController'] = function($container) {
	return new \App\Controllers\EmpleadosController($container);
};

$container['BugsController'] = function($container) {
	return new \App\Controllers\BugsController($container);
};

$container['ModulosController'] = function($container) {
	return new \App\Controllers\ModulosController($container);
};

$container['PageController'] = function($container) {
	return new \App\Controllers\PageController($container);
};

$container['RedireccionController'] = function($container) {
	return new \App\Controllers\RedireccionController($container);
};

/////////////////////CAJA///////////////////////////////////

$container['ComprobanteController'] = function($container) {
	return new \App\Controllers\Transacciones\ComprobanteController($container);
};

$container['MovimientoController'] = function($container) {
	return new \App\Controllers\Transacciones\MovimientoController($container);
};

$container['ProveedorController'] = function($container) {
	return new \App\Controllers\Transacciones\ProveedorController($container);
};

$container['TransaccionController'] = function($container) {
	return new \App\Controllers\Transacciones\TransaccionController($container);
};

$container['DiariaController'] = function($container) {
	return new \App\Controllers\Transacciones\DiariaController($container);
};

$container['ModoController'] = function($container) {
	return new \App\Controllers\Transacciones\ModoController($container);
};

$container['TipoTransaccionController'] = function($container) {
	return new \App\Controllers\Transacciones\TipoTransaccionController($container);
};

$container['ObligacionController'] = function($container) {
	return new \App\Controllers\Transacciones\ObligacionController($container);
};

$container['CorreoController'] = function($container) {
	return new \App\Controllers\Correo\CorreoController($container);
};

$container['VirtualController'] = function($container) {
	return new \App\Controllers\Correo\VirtualController($container);
};

$container['SuscripcionController'] = function($container) {
	return new \App\Controllers\Correo\SuscripcionController($container);
};

$container['NotificacionController'] = function($container) {
	return new \App\Controllers\NotificacionController($container);
};

$container['AyudaController'] = function($container) {
	return new \App\Controllers\AyudaController($container);
};

$container['TicketController'] = function($container) {
	return new \App\Controllers\TicketController($container);
};

$container['PerfilController'] = function($container) {
	return new \App\Controllers\PerfilController($container);
};

$container['CRMController'] = function($container) {
	return new \App\Controllers\CRMController($container);
};

$container['ModuloCorreoController'] = function($container) {
	return new \App\Controllers\Correo\ModuloCorreoController($container);
};

$container['RecolectorController'] = function($container) {
	return new \App\Controllers\RecolectorController($container);
};

$container['CuentaController'] = function($container) {
	return new \App\Controllers\Transacciones\CuentaController($container);
};

/* CAMPAÑAS */

// PRECIOS MUNDIALES

$container['PreciosMundialesController'] = function($container) {
	return new \App\Controllers\Campains\PreciosMundiales\PreciosMundialesController($container);
};

$container['CriptomonedasController'] = function($container) {
	return new \App\Controllers\Campains\Criptomonedas\CriptomonedasController($container);
};

// MIDDLEWAREs
$app->add(new \App\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \App\Middleware\OldInputMiddleware($container));
$app->add(new \App\Middleware\CsrfViewMiddleware($container));
$app->add(new \App\Middleware\RequestLogMiddleware($container));
$checkProxyHeaders = true; // Note: Never trust the IP address for security processes!
$trustedProxies = ['10.0.0.1', '10.0.0.2']; // Note: Never trust the IP address for security processes!
$app->add(new RKA\Middleware\IpAddress($checkProxyHeaders, $trustedProxies));

$app->add($container->csrf);

require __DIR__ . '/../app/routes.php';

?>
