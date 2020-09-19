<?php

namespace App\Controllers\Negocio;

use Slim\Views\Twig as View;

use App\Funcionalidades\Correo;
use App\Controllers\Controller;

use PHPJasper\PHPJasper;
use App\Models\Correo\ModuloCorreo;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\VehiculoHistorial;
use App\Models\Vehiculos\Usado;
use App\Models\Vehiculos\VehiculoFormulario;
use App\Models\Vehiculos\TipoFormulario;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

class FormularioController extends Controller {

  public function vehiculo($request,$response,$args){
    $id_vehiculo = $args['id'];
    $return_to = $request->getQueryParam('return_to',null);
    $tipo = $request->getQueryParam('tipo',1);
    $vehiculo = Vehiculo::where('id',$id_vehiculo)->first();
    $tipos = TipoFormulario::with([
      'formularios'=>function($q) use ($id_vehiculo) {
        $q->where('id_vehiculo',$id_vehiculo)->where('estado',1);
      }
      ])
      ->whereIn('tipo',[0,$tipo])
      ->where('estado',1)
      ->get();
    return $this->container->view->render($response, 'admin_views/vehicles/formulario.twig',[
			'tipos' => $tipos,
      'vehiculo' => $vehiculo,
      'return_to' => $return_to,
      'tipo'=>$tipo,
		]);
  }

  public function asociar($request,$response,$args){
    $id_vehiculo = $args['id'];
    $id = $request->getParam('id');
    $id_formulario = $request->getParam('id_formulario');
    $fecha = $request->getParam('fecha');
    $fecha_vto = $request->getParam('fecha_vto');
    $responsable = $request->getParam('responsable');
    $arancel = $request->getParam('arancel');
    $observaciones = $request->getParam('observaciones');
    $id_usuario = auth::empleado()->id_usuario;
    $dt = new \DateTime;
    $descripcion = '';
    if ($id>0) {
      $formulario = VehiculoFormulario::where('id',$id)->update([
        'id_usuario' => $id_usuario,
        'fecha' => $dt->createFromFormat('d/m/Y',$fecha),
        'fecha_vto' => $dt->createFromFormat('d/m/Y',$fecha_vto),
        'responsable' => $responsable,
        'arancel' => $arancel,
        'observaciones' => $observaciones,
      ]);
      $tipo = TipoFormulario::where('id',$id_formulario)->first();
      $arancel = $arancel>0?' Arancel: '.$arancel:'';
      $descripcion = 'Formulario "'.$tipo->nombre.'" Editado.'.$arancel;
      $this->flash->addMessage('info', 'Formulario editado Exitosamente.');
    } else {
      $formulario = VehiculoFormulario::create([
        'id_vehiculo' => $id_vehiculo,
        'id_formulario' => $id_formulario,
        'id_usuario' => $id_usuario,
        'fecha' => $dt->createFromFormat('d/m/Y',$fecha),
        'fecha_vto' => $dt->createFromFormat('d/m/Y',$fecha_vto),
        'responsable' => $responsable,
        'arancel' => $arancel,
        'observaciones' => $observaciones,
      ]);
      $arancel = ($arancel>0)?' Arancel: $'.$arancel:'';
      $descripcion = 'Formulario "'.$formulario->tipo->nombre.'" Agregado.'.$arancel;
      $this->flash->addMessage('info', 'Formulario asociado Exitosamente.');
    }
    VehiculoHistorial::Create([
      'id_vehiculo' => $id_vehiculo,
      'id_usuario' => $_SESSION['userid'],
      'descripcion' => $descripcion,
      'id_estado' => 4,
    ]);
    return $response->withRedirect($this->router->pathFor('vehicle.formulario',
      [
        'id'=>$id_vehiculo,
      ]
    ));
  }

  public function getVehiculoFormulario($request,$response,$args){
    $id_formulario = $args['id_formulario'];
    $formulario = VehiculoFormulario::where('id',$id_formulario)->first();
    return $response->withStatus(200)->withJson($formulario);
  }

  public function deleteVehiculoFormulario($request,$response,$args){
    $id_vehiculo = $args['id'];
    $id_formulario = $args['id_formulario'];
    $formulario = VehiculoFormulario::where('id',$id_formulario)->update([
      'estado'=>0,
    ]);
    $this->flash->addMessage('info', 'Formulario eliminado Exitosamente.');
    return $response->withRedirect($this->router->pathFor('vehicle.formulario',
      [
        'id'=>$id_vehiculo,
      ]
    ));
  }

  public function tablero($request,$response){
    $tipos = TipoFormulario::where('estado',1)->orderBy('orden','asc')->get();
    return $this->container->view->render($response, 'admin_views/vehicles/tableroFormulario.twig',[
			'tipos' => $tipos,
		]);
  }

  public function post($request,$response){
    $id_formulario = $request->getParam('id_formulario',0);
    $nombre = $request->getParam('nombre');
    $descripcion = $request->getParam('descripcion');
    $caducidad = $request->getParam('caducidad');
    $arancel = $request->getParam('arancel');
    $tipo = $request->getParam('tipo');

    TipoFormulario::updateOrCreate([
      'id'=>$id_formulario,
    ],[
      'nombre'=>$nombre,
      'descripcion'=>$descripcion,
      'caducidad'=>$caducidad,
      'arancel'=>$arancel,
      'tipo'=>$tipo,
    ]);
    if($id_formulario>0){
      $this->flash->addMessage('info', 'El Formulario fue editado Exitosamente.');
    } else {
      $this->flash->addMessage('info', 'El Formulario fue creado Exitosamente.');
    }
		return $response->withRedirect($this->router->pathFor('formulario.tablero'));
  }

  public function get($request,$response,$args){
    $id_formulario = $args['id'];
    $formulario = TipoFormulario::where('id',$id_formulario)->first();
    return $response->withStatus(200)->withJson($formulario);
  }

  public function delete($request,$response,$args){
    $id_formulario = $args['id'];
    $formulario = TipoFormulario::where('id',$id_formulario)->update([
      'estado'=>0,
    ]);
    $this->flash->addMessage('info', 'El Formulario fue eliminado Exitosamente.');
    return $response->withRedirect($this->router->pathFor('formulario.tablero'));
  }

  public function postOrden($request,$response){
		$orden = $request->getParam('orden');
		$iterador = explode(';', $orden);
		$nuevoOrden = 1;

		foreach ($iterador as $clave => $valor) {
			list($id,$orden)=explode(',', $valor);
			TipoFormulario::where('id',$id)->update([
				'orden'=>$nuevoOrden,
			]);
			$nuevoOrden = $nuevoOrden + 1;
		}

		$this->flash->addMessage('info', 'Formularios ordenados exitosamente.');
    return $response->withRedirect($this->router->pathFor('formulario.tablero'));
	}
}
