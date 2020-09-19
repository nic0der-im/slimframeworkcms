<?php

namespace App\Controllers\Correo;

use \Datetime;

use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

use PHPMailer\PHPMailer\PHPMailer;
use App\Funcionalidades\Correo;

use PhpImap\Mailbox;

class CorreoController extends Controller {

	public function index($request,$response){
		if(isset($_SESSION['mailbox']))
		{
			try{
				$mailbox = unserialize($_SESSION['mailbox']);
				$info = $mailbox->getMailboxInfo();
				$todos = $mailbox->getMailboxes();
				$folders = [];
				foreach ($todos as $value) {
					$shortpath = $value['shortpath'];
					$mailbox->switchMailbox('{mail.ciroautomotores.com.ar:143/imap/tls/novalidate-cert}'.$shortpath);
					$folders[$shortpath] = $mailbox->getMailboxInfo();
				}
			} catch (\Exception $e) {
				unset($_SESSION['mailbox']);
				$this->flash->addMessage('error', 'Vuelva a ingresar. Error al conectarse: '.$e->getMessage());
				return $response->withStatus(302)->withHeader('Location',  $this->router->pathFor('correo.logout'));
			}
			return $this->container->view->render($response, 'admin_views/correo/mailbox.twig', [
				'info'=>$info,
				'folders'=>$folders,
			]);
		}
		else
		{
			return $response->withStatus(302)->withHeader('Location',  $this->router->pathFor('correo.logout'));
		}
	}

	public function login($request,$response)
	{
		$password = $request->getParam('password');
		$email = $request->getParam('email');
		try
		{
			$mailbox = new Mailbox('{mail.ciroautomotores.com.ar:143/imap/tls/novalidate-cert}', $email, $password , __DIR__);
			$_SESSION['mailbox'] = serialize($mailbox);
		} 
		catch (Exception $e)
		{
			unset($_SESSION['mailbox']);
			$this->flash->addMessage('error', 'Error al conectarse: '.$e->getMessage());
		}
		$url = $this->router->pathFor('correo');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function logout($request,$response){
		if(isset($_SESSION['mailbox'])){
			$mailbox = unserialize($_SESSION['mailbox']);
			$mailbox->disconnect();
			unset($_SESSION['mailbox']);
		}
		return $this->container->view->render($response, 'admin_views/correo/login.twig', [
			]);
	}

	public function getAll($request,$response)
	{
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$mailbox = unserialize($_SESSION['mailbox']);
		$list = $mailbox->searchMailbox();
		$recordsTotal = count($list);
		$todo = $mailbox->getMailsInfo($list);
		rsort($todo);
		return $response->withStatus(200)->withJson([
			'draw' => intval($draw),
			'recordsTotal' => intval($recordsTotal),
			'recordsFiltered' => intval($recordsTotal),
			'data' => $todo,
			'order' => $order,
			'search' => $search,
			'columns' => $columns,
		]); 
	}
	

	public function get($request,$response, array $args){
		$mailbox = unserialize($_SESSION['mailbox']);
		$list = $mailbox->searchMailbox();
		if( in_array($args['id'], $list) ){
			$todo = $mailbox->getMail(intval($args['id']));
			return $response->withStatus(200)->withJson($todo
			);
		} else {
			return $response->withStatus(404);
		}
	}

	public function eliminar($request,$response, array $args){
		$mailbox = unserialize($_SESSION['mailbox']);
		$todo = $mailbox->deleteMail(intval($args['id']));
		$url = $this->router->pathFor('correo');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function post($request,$response){
		$body = $request->getParam('writeBody');
		$subject = $request->getParam('writeSubject');
		$to = $request->getParam('writeTo');

		$mail = Correo::ciro();
		try {

	    $mail->setFrom(auth::user()->email, auth::individuo()->apellido .' '.auth::individuo()->nombre);
	    $mail->addAddress($to);
	    $mail->addReplyTo(auth::user()->email, auth::individuo()->apellido .' '.auth::individuo()->nombre);

	    $mail->isHTML(true);
	    $mail->Subject = $subject;
	    $mail->Body    = $body;

	    $mail->send();
	    $this->flash->addMessage('info', 'Mensaje enviado.');

			$mailbox = unserialize($_SESSION['mailbox']);
	    $stream = $mailbox->getImapStream();
	    imap_append($stream, "{mail.ciroautomotores.com.ar:143/imap/tls/novalidate-cert}Sent"
                   , "From: ".auth::user()->email."\r\n"
                   . "To: ".$to."\r\n"
                   . "Subject: ".$subject."\r\n"
                   . "\r\n"
                   . $body."\r\n"
                   );
		} catch (Exception $e) {
	    $this->flash->addMessage('error', 'El mensaje no pudo enviarse. '.$mail->ErrorInfo);
		}

		$url = $this->router->pathFor('correo');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getAllMailBoxes($request,$response){
		$mailbox = unserialize($_SESSION['mailbox']);
		$todos = $mailbox->getMailboxes();
		$folders = [];
		foreach ($todos as $value) {
			$shortpath = $value['shortpath'];
			$mailbox->switchMailbox('{mail.ciroautomotores.com.ar:143/imap/tls/novalidate-cert}'.$shortpath);
			$folders[$shortpath] = $mailbox->getMailboxInfo();
		}
		return $response->withStatus(200)->withJson($folders
		);
	}

	public function crearMailBox($request,$response,array $args){
		$mailbox = unserialize($_SESSION['mailbox']);
		$nombre = $args['nombre'];
		$todo = $mailbox->createMailbox($nombre);
		return $response->withStatus(200)->withJson($todo
		);
	}

	public function cambiarMailbox($request,$response, array $args){
		$mailbox = unserialize($_SESSION['mailbox']);
		$nombre = $args['nombre'];
		$mailbox->switchMailbox("{mail.ciroautomotores.com.ar:143/imap/tls/novalidate-cert}".$nombre);
		unset($_SESSION['mailbox']);
		$_SESSION['mailbox'] = serialize($mailbox);
		$url = $this->router->pathFor('correo');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

}