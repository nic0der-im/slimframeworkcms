<?php
session_start();
setlocale(LC_ALL,"es_MX");
ini_set('date.timezone', 'America/Argentina/Salta');

require_once __DIR__.'/../vendor/autoload.php';

use GO\Scheduler;

$settings = require __DIR__ . '/settings.php';

$c = new \Slim\Container($settings);
$app = new \Slim\App($c);
$container = $app->getContainer();
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['db'] = function($container) use ($capsule) {
	return $capsule;
};
$container['logger'] = function($container) {
	$logger = new Monolog\Logger('ciro-cron');
	$logger->pushProcessor(new Monolog\Processor\UidProcessor());
	$handler = new \Monolog\Handler\RotatingFileHandler(
		isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/cron.log',
		\Monolog\Logger::DEBUG
	);
	$lineFormatter = new \Monolog\Formatter\LineFormatter(null, null, true);
	$handler->setFormatter($lineFormatter);
	$logger->pushHandler($handler);
	return $logger;
};

$logger = $container->logger;

$scheduler = new Scheduler();

$container['scheduler'] = function($container) use ($scheduler) {
	return $scheduler;
};

$publicacionJobs = new App\Jobs\PublicacionJobs($container);
$publicacionJobs->call_controlar_renobacion()->before(function () use ($logger) {
    $logger->info("CRON PUBLICACION RENOBACION START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON PUBLICACION RENOBACION COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  ->hourly();
  //->everyMinute();

$comprobanteJobs = new App\Jobs\Transacciones\ComprobanteJobs($container);
$comprobanteJobs->call_facturar()->before(function () use ($logger) {
    $logger->info("CRON FACTURAR START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON FACTURAR COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  ->hourly();
  //->everyMinute();

$diariaJobs = new App\Jobs\Transacciones\DiariaJobs($container);
$diariaJobs->call_abrir()->before(function () use ($logger) {
    $logger->info("CRON DIARIA START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON DIARIA COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  //->everyMinute();
  ->at('*/5 8-22 * * 1,2,3,4,5');
$diariaJobs->call_abrir()->before(function () use ($logger) {
    $logger->info("CRON DIARIA START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON DIARIA COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  //->everyMinute();
  ->at('*/5 8-13 * * 6');
$diariaJobs->call_cerrar()->before(function () use ($logger) {
    $logger->info("CRON DIARIA START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON DIARIA COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  ->at('0 23 * * 1,2,3,4,5');
$diariaJobs->call_cerrar()->before(function () use ($logger) {
    $logger->info("CRON DIARIA START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON DIARIA COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  ->at('0 14 * * 6');
$suscripcionJobs = new App\Jobs\SuscripcionJobs($container);
$suscripcionJobs->call_enviar()->before(function () use ($logger) {
    $logger->info("CRON SUSCRIPCION START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON SUSCRIPCION COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  //->everyMinute();
  ->at('*/5 * * * *');

$suscripcionJobs->call_todos()->before(function () use ($logger) {
    $logger->info("CRON SUSCRIPCION START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON SUSCRIPCION COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  //->everyMinute();
  ->at('*/25 * * * *');

$suscripcionJobs->call_notificar_nuevos()->before(function () use ($logger) {
    $logger->info("CRON SUSCRIPCION START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON SUSCRIPCION COMPLET " . time(), [
      'output' => $output,
    ]);
  })
	//->everyMinute();
  ->at('00 10 * * 1');

$backupJobs = new App\Jobs\BackupJobs($container);
$backupJobs->call_db()->before(function () use ($logger) {
    $logger->info("CRON BACKUP START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON BACKUP COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  //->everyMinute();
  ->at('0 23 * * 6');

$reporteJobs = new App\Jobs\Transacciones\ReporteJobs($container);
$reporteJobs->call_semanal()->before(function () use ($logger) {
    $logger->info("CRON REPORTE START " . time());
  })
  ->then(function ($output) use ($logger) {
    $logger->info("CRON REPORTE COMPLET " . time(), [
      'output' => $output,
    ]);
  })
  //->everyMinute();
  ->at('0 22 * * 6');

$scheduler->run();
