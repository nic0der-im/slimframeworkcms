<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\User;
use App\Models\Publicacion;
use App\Models\PublicacionRenobacion;
use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\UsadoTercero;

use Illuminate\Database\Capsule\Manager as DB;
use App\Auth\auth;

class PublicacionController extends Controller {

	public function index($request, $response){
		return $this->container->view->render($response, 'admin_views/publicaciones/index.twig', [
			'publicaciones'=>Publicacion::where('mostrar', 1)->orderBy('created_at', 'desc')->get()
		]);
	}

	public function crearGenericos($request, $response){
		$var = 0;
		$publicaciones = Publicacion::select('id_vehiculo')->where('mostrar', 0)->get();
		$vehiculos = Vehiculo::orderBy('created_at')->whereIn('id', $publicaciones)->get();
		
		foreach ($vehiculos as $vehiculo) {
		    if($vehiculo->getFotos->count()) {
				$var = $var + 1;
				$titulo = $vehiculo->getMarca->nombre . " " . $vehiculo->modelo;
				$publicacion = Publicacion::Create([
				'id_vehiculo'=>$vehiculo->id,
				'id_usuario'=>$_SESSION['userid'],
				'titulo'=> $titulo,
				'contenido'=>$request->getParam('pub_crear_contenido')
				]);
			}
		}
		
		$this->flash->addMessage('info', 'Se crearon publicaciones para los '. $var .' vehículos seleccionados.');
		$url = $this->router->pathFor('publicaciones.index');
		return $response->withStatus(301)->withHeader('Location', $url); 
	}

	public function getCrear($request, $response, array $args){
		$publicacion = Publicacion::where('id_vehiculo', '=', $args['id'])->first();

		if($publicacion) {
			$this->flash->addMessage('error', 'El vehículo ya ha sido publicado.');
			$url = $this->router->pathFor('publicaciones.editar',['id'=>$publicacion->id]);
			return $response->withStatus(301)->withHeader('Location', $url); 
		}

		$vehiculo = Vehiculo::find($args['id']);

		if(!$vehiculo->getFotos->count()) {
			$this->flash->addMessage('error', 'No se han cargado fotos del vehículo, por lo que no se puede publicar.');
			$url = $this->router->pathFor('vehicle.index');
			return $response->withStatus(301)->withHeader('Location', $url); 
		}
		$titulo = $vehiculo->getMarca->nombre . " " . $vehiculo->modelo;

		$tercero = UsadoTercero::where('id_vehiculo','=', $args['id'])->first();

		return $this->container->view->render($response, 'admin_views/publicaciones/crear.twig', [
			'vehiculo'=>$vehiculo,
			'titulo'=>$titulo,
			'usado_tercero'=>$tercero,
		]);

	}

	public function getEliminar($request, $response, array $args){
		$publicacion = Publicacion::find($args['id']);
		$publicacion->mostrar = 0;
		$publicacion->save();
		
		$this->flash->addMessage('info', 'Publicación eliminada exitosamente.');

		$url = $this->router->pathFor('publicaciones.index');
		return $response->withStatus(301)->withHeader('Location', $url);
	}

	public function getEditar($request, $response, array $args){

		return $this->container->view->render($response, 'admin_views/publicaciones/editar.twig', [
			'publicaciones'=>Publicacion::find($args['id']),
		]);
	}

	public function postCrear($request,$response){
		$publicacion = Publicacion::Create([
		'id_vehiculo'=>$request->getParam('pub_crear_id_vehiculo'),
		'id_usuario'=>$request->getParam('pub_crear_id_usuario'),
		'titulo'=>$request->getParam('pub_crear_titulo'),
		'contenido'=>$request->getParam('pub_crear_contenido'),
		]);

		$this->flash->addMessage('info', 'Publicación creada exitosamente.');

		$url = $this->router->pathFor('publicaciones.index');
		return $response->withStatus(301)->withHeader('Location', $url);

	}
	public function postEditar($request,$response, $args){
		$publicacion = Publicacion::find($request->getParam('pub_editar_id_publicacion'));

		$publicacion->titulo = $request->getParam('pub_editar_titulo');
		$publicacion->contenido = $request->getParam('pub_editar_contenido');
		$publicacion->mostrar = 1;
		$publicacion->save();

		
		$this->flash->addMessage('info', 'Publicación editada exitosamente.');

		$url = $this->router->pathFor('publicaciones.index');
		return $response->withStatus(301)->withHeader('Location', $url);

	}

	//////TERCEROS
	public function obtenerPublicacion($request,$response,$args){
		$id_vehiculo = $args['id'];
		$id_usuario = auth::individuo()->id_usuario;
		$vehiculo = Vehiculo::where('eliminado',0)->where('id_usuario',$id_usuario)->where('id',$id_vehiculo)->first();
		if($vehiculo){
			$publicacion = Publicacion::where('id_usuario',$id_usuario)->where('id_vehiculo',$id_vehiculo)->first();
			$usado = UsadoTercero::where('id_vehiculo',$id_vehiculo)->first();
			if($publicacion){
				if($publicacion->mostrar == 0){
					return $response->withStatus(200)->withJson($publicacion);
				} else {
					return $response->withStatus(204);
				}
			} else {
				return $response->withStatus(200)->withJson([
					'titulo'=>$vehiculo->modelo,
					'contenido'=> $usado->observaciones,
				]);
			}
		} else {
			return $response->withStatus(401);
		}
	}

	public function modificarPublicacion($request,$response,$args){
		$id_vehiculo = $args['id'];
		$id_usuario = auth::individuo()->id_usuario;
		$vehiculo = Vehiculo::where('eliminado',0)->where('id_usuario',$id_usuario)->where('id',$id_vehiculo)->first();
		if($vehiculo){
			$publicacion = Publicacion::where('id_usuario',$id_usuario)->where('id_vehiculo',$id_vehiculo)->first();
			$titulo = $request->getParam('titulo');
			$contenido = $request->getParam('contenido');
			if($publicacion){
				Publicacion::where('id',$publicacion->id)->update([
					'titulo'=>$titulo,
					'contenido'=>$contenido,
				]);
			} else {
				Publicacion::create([
					'titulo'=>$titulo,
					'contenido'=>$contenido,
					'id_vehiculo'=>$id_vehiculo,
					'id_usuario'=>$id_usuario,
					'mostrar'=>0,
				]);
			}
			$url = $this->router->pathFor('vendetuauto');
			return $response->withStatus(301)->withHeader('Location', $url);
		} else {
			return $response->withStatus(401);
		}
	}

	public function obtenerRenobacion($request,$response,$args){
		$id_publicacion = $args['id'];
		$publicacion = Publicacion::with([
				'renobaciones'=>function($query){
					$query->where('estado','=',1);
				},
				'renobaciones.individuo'=>function($query){
				},
			])
			->where('id',$id_publicacion)
			->first();
		return $response->withStatus(200)->withJson($publicacion);
	}

	public function eliminarRenobacion($request,$response,$args){
		$id_renobacion = $args['id'];
		PublicacionRenobacion::where('id',$id_renobacion)->update([
			'estado' => 0,
		]);
		$renobacion = PublicacionRenobacion::where('id',$id_renobacion)->first();
		Publicacion::where('id',$renobacion->id_publicacion)->update([
			'mostrar' => 0,
		]);
		return $response->withStatus(200)->withJson($publicacion);
	}

	public function modificarRenobacion($request,$response,$args){
		$id_publicacion = $args['id'];
		$titulo = $request->getParam('titulo');
		$contenido = $request->getParam('contenido');
		Publicacion::where('id',$id_publicacion)->update([
			'titulo' => $titulo,
			'contenido' => $contenido,
		]);

		$url = $this->router->pathFor('vehicle.terceros');
		return $response->withStatus(301)->withHeader('Location', $url);
	}

	public function renobar($request,$response,$args){
		$id_publicacion = $args['id'];
		$fecha = $request->getParam('fecha');
		$dias = $request->getParam('dias');
		$fecha_vto = $request->getParam('fecha_vto');
		$observacion = $request->getParam('observacion');
		$publicacion = Publicacion::where('id',$id_publicacion)->first();

		$dt = new \DateTime();
		$fecha = $dt->createFromFormat('d/m/Y', $fecha );
		$fecha_vto = $dt->createFromFormat('d/m/Y', $fecha_vto );
		PublicacionRenobacion::create([
			'id_publicacion'=>$id_publicacion,
			'id_usuario'=> auth::individuo()->id_usuario,
			'fecha'=> $fecha->format('Y-m-d'),
			'dias'=>$dias,
			'fecha_vto' => $fecha_vto->format('Y-m-d'),
			'observacion' => $observacion,
		]);

		$url = $this->router->pathFor('vehicle.terceros');
		return $response->withStatus(301)->withHeader('Location', $url);
	}


}