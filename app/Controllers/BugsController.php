<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\Bugs;


class BugsController extends Controller {

	public function index($request, $response)
	{
		$bugs = Bugs::orderBy('id','DESC')->get();
		return $this->container->view->render($response, 'admin_views/admin/bugs.twig', [
			'bugs'=>$bugs,
		]);
	}

	public function getEliminar($request, $response, array $args)
	{
		$bug = Bugs::destroy($args['id']);
		
		$this->flash->addMessage('error', 'Bug/tarea eliminada correctamente.');

		$url = $this->router->pathFor('bugs.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getAprobar($request,$response, $args)
	{
		$bug = Bugs::find($args['id']);

		if($bug->estado == 1)
		{
			$bug->estado=0;
		}
		else
		{
			$bug->estado=1;
		}
		$bug->save();
		$url = $this->router->pathFor('bugs.index');
		return $response->withStatus(302)->withHeader('Location', $url);

	}

	public function postCrear($request, $response) {
		$estado = 0;
		$tipo = $request->getParam('bug_crear_accion'); 
		$bug = Bugs::Create([ 
		'texto'=>$request->getParam('bugs_crear_form_texto'),
		'estado'=>$estado,
		'descubiertopor'=>$request->getParam('bug_crear_id_usuario'), 
		'tipo' => $tipo,
		]);

		if($tipo == 0)
		{
			$this->flash->addMessage('info', 'El bug fue cargado exitosamente para su reparación/revisión.');
		}
		else
		{
			$this->flash->addMessage('info', 'La tarea fue agregada correctamente para su revisión.');
		}
		$url = $this->router->pathFor('bugs.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}
}