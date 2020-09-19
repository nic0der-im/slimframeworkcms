<?php

namespace App\Controllers\Correo;

use \Datetime;

use App\Models\User;
use App\Models\Correo\Alias;
use App\Models\Correo\Dominio;
use App\Models\Correo\UsuarioVirtual;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Funcionalidades\Correo;

use PhpImap\Mailbox;

class VirtualController extends Controller {

	protected $passwd = '/home/admin/conf/mail/ciroautomotores.com.ar/passwd';

	public function index($request,$response){

		return $this->container->view->render($response, 'admin_views/empleados/tableroVirtual.twig',[
			'alias' => Alias::where('estado',1)->get(),
			'dominios' => Dominio::where('estado',1)->get(),
			'usuarios' => UsuarioVirtual::all(),
		]);
	}

	public function agregarCorreo($request,$response,$args){
		$id_usuario = $args['id'];

		$usuarioVirtual = UsuarioVirtual::where('id_usuario',$id_usuario)->first();
		if($usuarioVirtual){
			$this->flash->addMessage('info', 'El usuario ya posee el correo.');
		} else {
			$user = User::where('id',$id_usuario)->first();
			UsuarioVirtual::create([
				'id_usuario' => $id_usuario,
				'id_dominio' => 1,
				'contrasenia'=>'',
				'email' => $user->email,
			]);
			if(file_exists($this->passwd)){
				$user = explode("@",  $user->email);
				if(exec('grep '.escapeshellarg($user[0]).' '.$this->passwd)) {
					$this->flash->addMessage('warning', 'Usuario existente en los registros. Es posible que tenga correos.');
	      }else{
	      	$txt = $user[0].":{MD5}$1$MUR4JB0w$vbgbnFwwKyYGcnvF1NbfL0:admin:mail::/home/admin:0";
					file_put_contents($this->passwd, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
					$this->flash->addMessage('warning', 'Usuario agregado a los registros internos.');
	      }
			}
			$this->flash->addMessage('info', 'Correo electronico creado. El usuario debe logear una vez para guardar la contraseña.');
		}

		return $response->withRedirect($this->router->pathFor('correovirtual.index'));
	}

	public function agregarAlias($request,$response){
		$origen = $request->getParam('origen');
		$destino = $request->getParam('destino');

		Alias::create([
			'id_dominio' => 1,
			'origen' => $origen,
			'destino' => $destino,
		]);

		return $response->withRedirect($this->router->pathFor('correovirtual.index'));
	}

	public function eliminarAlias($request,$response,$args){
		$id_alias = $args['id'];

		Alias::where('id',$id_alias)->update([
			'estado' => 0,
		]);

		$this->flash->addMessage('info', 'Redireccion eliminada.');
		return $response->withRedirect($this->router->pathFor('correovirtual.index'));
	}

	public function agregarUsuarioVirtual($request,$response){
		$email = $request->getParam('email');
		$contrasenia = $request->getParam('contrasenia');
		$salt = substr(sha1(rand()), 0, 16);
		$hashedPassword = "{SHA512-CRYPT}" . crypt($contrasenia, "$6$$salt");
		UsuarioVirtual::create([
			'id_usuario' => 0,
			'id_dominio' => 1,
			'contrasenia'=> $hashedPassword,
			'email' => $email,
		]);
		if(file_exists($this->passwd)){
			$user = explode("@", $email);
			if(exec('grep '.escapeshellarg($user[0]).' '.$this->passwd)) {
				$this->flash->addMessage('warning', 'Usuario existente en los registros. Es posible que tenga correos.');
      }else{
      	$txt = $user[0].":{MD5}$1$MUR4JB0w$vbgbnFwwKyYGcnvF1NbfL0:admin:mail::/home/admin:0";
				file_put_contents($this->passwd, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
				$this->flash->addMessage('warning', 'Usuario agregado a los registros internos.');
      }
		} else {
			$this->flash->addMessage('error', 'Archivo '.$this->passwd.' inexistente.');
		}
		$this->flash->addMessage('info', 'Correo virtual creado.');
		return $response->withRedirect($this->router->pathFor('correovirtual.index'));
	} 

	public function eliminarUsuarioVirtual($request,$response,$args){
		$id_usuariovirtual = $args['id'];

		UsuarioVirtual::where('id',$id_usuariovirtual)->update([
			'estado' => 0,
		]);

		$this->flash->addMessage('info', 'Correo virtual deshabilitado.');
		return $response->withRedirect($this->router->pathFor('correovirtual.index'));
	}

	public function habilitarUsuarioVirtual($request,$response,$args){
		$id_usuariovirtual = $args['id'];

		UsuarioVirtual::where('id',$id_usuariovirtual)->update([
			'estado' => 1,
		]);
		if(file_exists($this->passwd)){
			$user = UsuarioVirtual::where('id',$id_usuariovirtual)->first();
			$user = explode("@", $user->email);
			if(exec('grep '.escapeshellarg($user[0]).' '.$this->passwd)) {
				$this->flash->addMessage('warning', 'Usuario existente en los registros. Es posible que tenga correos.');
      }else{
      	$txt = $user[0].":{MD5}$1$MUR4JB0w$vbgbnFwwKyYGcnvF1NbfL0:admin:mail::/home/admin:0";
				file_put_contents($this->passwd, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
				$this->flash->addMessage('warning', 'Usuario agregado a los registros internos.');
      }
		}
		$this->flash->addMessage('info', 'Correo virtual habilitado.');
		return $response->withRedirect($this->router->pathFor('correovirtual.index'));
	}
}