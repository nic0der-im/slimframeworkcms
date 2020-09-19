<?php

namespace App\Controllers\Home;
use App\Controllers\Controller;

use Slim\Views\Twig as View;
use App\Models\Publicacion;
use App\Models\PublicacionVista;
use App\Models\Consulta;
use App\Models\Suscripcion;
use App\Models\User;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\Marca;
use App\Models\Vehiculos\EstadosVeh;
use App\Models\Precios\PreciosVehiculosHistorial;
use App\Models\Precios\PreciosVehiculos;

use App\Models\Extras\Provincia;
use App\Models\Extras\Ubicacion;
use App\Models\Extras\Localidad;

use App\Models\Vehiculos\Creditos;
use App\Models\Vehiculos\CreditosFijos;

use App\Models\Pagina\Pagina;
use App\Models\Pagina\Bloque;

use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;
use App\Auth\auth;

class ComprarController extends Controller {

  // Usados

  public function getUsados_PrimerPaso($request, $response, array $args) {

    $publicacion = Publicacion::find($args['id']);

    if($publicacion) {
      
      return $this->container->view->render($response, 'guest_views/vehiculos/compra-tu-auto/first.twig', [       
        'publicacion'=>$publicacion,        
      ]);

    }
    else {
      return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('home'));
    }

  }

  public function postUsados_PrimerPaso($request, $response) {

    $compra_data = json_decode($request->getParam('compra_data'));

    $consulta = 'Hola, mi nombre es ' . strtoupper($request->getParam('cta_nombre')) . ' '. strtoupper($request->getParam('cta_apellido')) . ' de ' . strtoupper($request->getParam('cta_ciudad')); 
    $consulta .= '<br>Quiero comprar un ' . strtoupper($compra_data->nombre_version) . ' valuado al día de hoy en $ ' . $compra_data->precio_vehiculo;

    if($compra_data->check_efectivo) {
      $consulta .= '<br>Dispongo de $ ' . $compra_data->efectivo . ' en efectivo para entregar.';
    }

    if($compra_data->check_usado) {
      $consulta .= '<br>Quiero entregar un ' . strtoupper($compra_data->marca_usado) . ' ' . strtoupper($compra_data->modelo_usado) . ' ' . strtoupper($compra_data->version_usado) . ' AÑO ' . strtoupper($compra_data->year_usado);
      $consulta .= ' tasado automaticamente por el sistema usando los precios de ACARA en: $' . $compra_data->usado;
    }

    $consulta .= '<br>Quiero financiar el resto ($' . $compra_data->credito . ') en hasta 60 cuotas.';

    $consulta = Consulta::create([
      'id_usuario'=> -1,
      'nombre'=>$request->getParam('cta_nombre'),
      'apellido'=>$request->getParam('cta_apellido'),
      'texto'=>$consulta,
      'telefono'=>$request->getParam('cta_telefono'),
      'url'=>$request->getParam('url'),
      'email'=>'',
    ]);

    $success = false;

    if($consulta) {
      $success = true;
    }

    return $response->withJson([
      'success'=>$success,      
    ]);
  }

  // 0KM

  public function get0km_PrimerPaso($request, $response, array $args) {

    $pagina = Pagina::where('url_name', $args['titulo'])->first();

    if($pagina) {

      $versiones = Bloque::where('id_pagina', $pagina->id)->where('tipo', 'versiones')->first();

      return $this->container->view->render($response, 'guest_views/vehiculos/compra-tu-0km/first.twig', [       
        'pagina'=>$pagina,
        'versiones'=>$versiones,
      ]);  
    }
    else {
      return $response->withStatus(301)->withHeader('Location', $this->router->pathFor('home'));
    }

  }

  public function post0km_PrimerPaso($request, $response) {

    $compra_data = json_decode($request->getParam('compra_data'));

    $consulta = 'Hola, mi nombre es ' . strtoupper($request->getParam('cta_nombre')) . ' '. strtoupper($request->getParam('cta_apellido')) . ' de ' . strtoupper($request->getParam('cta_ciudad')); 
    $consulta .= '<br>Quiero comprar una ' . strtoupper($compra_data->nombre_version) . ' valuada al día de hoy en $ ' . $compra_data->precio_vehiculo;

    if($compra_data->check_efectivo) {
      $consulta .= '<br>Dispongo de $ ' . $compra_data->efectivo . ' en efectivo para entregar.';
    }

    if($compra_data->check_usado) {
      $consulta .= '<br>Quiero entregar un ' . strtoupper($compra_data->marca_usado) . ' ' . strtoupper($compra_data->modelo_usado) . ' ' . strtoupper($compra_data->version_usado) . ' AÑO ' . strtoupper($compra_data->year_usado);
      $consulta .= ' tasado automaticamente por el sistema usando los precios de ACARA en: $' . $compra_data->usado;
    }

    $consulta .= '<br>Quiero financiar el resto ($' . $compra_data->credito . ') en hasta 60 cuotas.';

    $consulta = Consulta::create([
      'id_usuario'=> -1,
      'nombre'=>$request->getParam('cta_nombre'),
      'apellido'=>$request->getParam('cta_apellido'),
      'texto'=>$consulta,
      'telefono'=>$request->getParam('cta_telefono'),
      'url'=>'',
      'email'=>'',
    ]);

    $success = false;

    if($consulta) {
      $success = true;
    }

    return $response->withJson([
      'success'=>$success,      
    ]);
  }
}
