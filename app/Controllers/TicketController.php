<?php

namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\Tickets\Tickets;
use App\Models\Tickets\TicketsRespuestas;
use App\Models\SP_Extras\Puesto;
use App\Models\Individuo;
use App\Models\Empleado;

class TicketController extends Controller {

	public function index($request, $response)
	{
		return $this->container->view->render($response, 'admin_views/tickets/index.twig', [
			'tickets'=>Tickets::where('id_usuario', '=', $_SESSION['userid'])->where('archivado', '=', '0')->orderBy('created_at', 'asc')->get(),
			'puestos'=>Puesto::get(),
		]);
	}

	public function indexDepartamento($request, $response)
	{
		$empleado = Empleado::where('id_usuario', '=', $_SESSION['userid'])->first();
		return $this->container->view->render($response, 'admin_views/tickets/indexDepartamento.twig', [
			'tickets'=>Tickets::where('destinatario', '=', $empleado->id_puesto)->where('archivado', '=', '0')->orderBy('created_at', 'asc')->get(),
			'puesto_id'=>$empleado->id_puesto,
			'puestos'=>Puesto::get(),
		]);
	}

	public function verTicket($request, $response, $args)
	{
		$ticket = Tickets::find($args['id']);
		return $this->container->view->render($response, 'admin_views/tickets/verTicket.twig', [
			'ticket'=> $ticket,
			'individuo'=>Individuo::where('id_usuario', '=', $ticket->id_usuario)->first(),
			'puestos'=>Puesto::get(),
		]);
	}

	public function crearTicket($request, $response)
	{
		$ticket = Tickets::Create([
		'id_usuario' => $_SESSION['userid'],
		'descripcion' => $request->getParam('respuesta_texto'),
		'asunto'=> $request->getParam('asunto_ticket'),
		'destinatario'=> $request->getParam('departamento_ticket'),
		'urgente'=> $request->getParam('prioridad'),
		])->id;

		$this->flash->addMessage('info', 'Tu ticket fue enviado correctamente, espere una respuesta.' . $_SESSION['userid']);
		$url = $this->router->pathFor('tickets.ver', ['id' => $ticket]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function responderTicket($request, $response, $args)
	{
		$respuesta = TicketsRespuestas::Create([
		'id_ticket'=>$args['id'],
		'id_usuario'=>$_SESSION['userid'],
		'respuesta'=>$request->getParam('respuesta_texto'),
		])->id;

		$this->flash->addMessage('info', 'Tu respuesta fue enviada correctamente.');
		$url = $this->router->pathFor('tickets.ver', ['id' => $args['id']]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function estadoTicket($request, $response, $args)
	{
		$ticket = Tickets::find($args['id']);
		$ticket->estado = $args['estado'];
		$ticket->save();

		$this->flash->addMessage('info', 'Estado cambiado a correctamente.');
		$url = $this->router->pathFor('tickets.ver', ['id' => $args['id']]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function prioridadTicket($request, $response, $args)
	{
		$ticket = Tickets::find($args['id']);
		$ticket->urgente = $args['prioridad'];
		$ticket->save();

		if($args['prioridad'] == 0)
		{
			$this->flash->addMessage('info', 'El ticket cambió su prioridad a urgente.');
		}
		else
		{	
			$this->flash->addMessage('info', 'El ticket cambió su prioridad a normal.');
		}

		$url = $this->router->pathFor('tickets.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}	

	public function archivarTicket($request, $response, $args)
	{
		$ticket = Tickets::find($args['id']);
		$ticket->archivado = 1;
		$ticket->save();
		// ------------------------
		$this->flash->addMessage('info', 'El ticket se archivó correctamente.');
		$url = $this->router->pathFor('tickets.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}	
}

?>