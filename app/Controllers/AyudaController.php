<?php

namespace App\Controllers;

use Slim\Views\Twig as View;
use App\Models\Manuales;
use App\Models\Modulo;

class AyudaController extends Controller {

	public function index($request, $response)
	{
		return $this->container->view->render($response, 'admin_views/ayuda/index.twig', [
			'manuales' => Manuales::orderBy('id','ASC')->get(),
			'modulos'=>Modulo::orderBy('id','ASC')->get(),
		]);
	}

	public function verManual($request, $response, $args)
	{
		return $this->container->view->render($response, 'admin_views/ayuda/verManual.twig', [
			'manual'=>Manuales::find($args['id']),
		]);
	}

	public function crearManual($request, $response)
	{
		return $this->container->view->render($response, 'admin_views/ayuda/crearManual.twig', [
			'modulos'=>Modulo::orderBy('id','ASC')->get(),
		]);
	}

	public function editarManual($request, $response, $args)
	{
		return $this->container->view->render($response, 'admin_views/ayuda/editarManual.twig', [
			'manual'=>Manuales::find($args['id']),
			'modulos'=>Modulo::orderBy('id','ASC')->get(),
			'id'=>$args['id'],
		]);
	}

	public function editarManualPost($request,$response, $args)
	{
		$manual = Manuales::find($args['id']);
		$manual->nombre = $request->getParam('manual_titulo');
		$manual->descripcion = $request->getParam('manual_descripcion');
		$manual->categoria = $request->getParam('manual_categoria');
		$manual->texto = $request->getParam('manual_contenido');
		$manual->save();

		$this->flash->addMessage('info', 'Manual editado exitosamente.');
		$url = $this->router->pathFor('ayuda.index');
		return $response->withStatus(302)->withHeader('Location', $url);

	}

	public function eliminarManual($request, $response, array $args)
	{
		$manual = Manuales::destroy($args['id']);
		$this->flash->addMessage('error', 'Manual eliminado correctamente.');
		$url = $this->router->pathFor('ayuda.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function crearManualPost($request, $response)
	{
		$manual = Manuales::Create([
		'nombre'=>$request->getParam('manual_titulo'),
		'descripcion'=>$request->getParam('manual_descripcion'),
		'categoria'=>$request->getParam('manual_categoria'),
		'texto'=>$request->getParam('manual_contenido'),
		'creador'=>$_SESSION['userid'],
		])->id;

		$this->flash->addMessage('info', 'Se creo el manual correctamente, puedes editarlo cuando lo necesites.');
		$url = $this->router->pathFor('ayuda.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}



}