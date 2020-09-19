<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Individuo;
use Slim\Views\Twig as View;
use App\Models\Correo\UsuarioVirtual;
use App\Models\Empleado;

use App\Auth\auth;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\TipoDocumento;
use App\Models\Extras\Provincia;

class PerfilController extends Controller {

	public function index($request, $response)
	{
		return $this->container->view->render($response, 'admin_views/perfil/index.twig', [
			'tipoDocumento'=>TipoDocumento::where('estado',1)->get(),
			'provincias' => Provincia::all(),
		]);
	}

	public function cambiarfoto($request, $response, array $args)
	{
		$empleado = Empleado::find($args['id']);
		$empleado->fotoperfil = $args['foto'];
		$empleado->save();
		return true;
	}

	public function cambiarContraseña($request,$response,$args){
		$user = auth::user();

		$vieja = $request->getParam('vieja');
		$nueva = $request->getParam('nueva');
		$re = $request->getParam('re');

		if(password_verify($vieja, $user->password)){
			if($nueva == $re){
				User::where('id',$user->id)->update([
					'password' => password_hash($nueva, PASSWORD_DEFAULT)	,
				]);
				$salt = substr(sha1(rand()), 0, 16);
				$hashedPassword = "{SHA512-CRYPT}" . crypt($nueva, "$6$$salt");
				UsuarioVirtual::where('id_usuario',$user->id)->update([
					'contrasenia' => $hashedPassword,
				]);

				$this->flash->addMessage('info', 'La contraseña a sido cambiada con exito.');
			} else {
				$this->flash->addMessage('warning', 'La contraseña nueva no coincide.');
			}
		} else {
			$this->flash->addMessage('warning', 'Contraseña incorrecta.');
		}

		return $response->withRedirect($this->router->pathFor('perfil.index'));
	}

	public function cambiarDatos($request,$response,$args){

		$validation = $this->validator->validate($request, [
			'nombre'=>v::alpha(),
			'apellido'=>v::alpha(),
			'documento'=>v::optional(v::numeric()),
			'email'=>v::optional(v::email()),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores.');
			return $response->withRedirect($this->router->pathFor('perfil.index'));
		}

		$nombre = $request->getParam('nombre');
		$apellido = $request->getParam('apellido');
		$email = $request->getParam('email');
		$fecha_nacimiento = $request->getParam('fecha_nacimiento');
		$telefono = $request->getParam('telefono');
		$domicilio = $request->getParam('domicilio');
		$id_tipo_documento = $request->getParam('id_tipo_documento');
		$documento = $request->getParam('documento');
		$observaciones_domicilio = $request->getParam('observaciones_domicilio');
		$id_localidad = $request->getParam('id_localidad');
		$id_provincia = $request->getParam('id_provincia');
		$observaciones = $request->getParam('observaciones');

		$user = auth::user();
		$dt = new \DateTime();
		Individuo::where('id_usuario',$user->id)->update([
			'nombre' => $nombre,
			'apellido' => $apellido,
			'email' => $email,
			'id_tipo_documento' => $id_tipo_documento,
			'documento' => $documento,
			'fecha_nacimiento' => $dt->createFromFormat('d/m/Y', $fecha_nacimiento),
			'telefono' => $telefono,
			'domicilio' => $domicilio,
			'observaciones_domicilio' => $observaciones_domicilio,
			'id_localidad' => $id_localidad,
			'id_provincia' => $id_provincia,
			'observaciones'  => $observaciones,
		]);

		$this->flash->addMessage('info', 'Los datos fueron modificados con exito.');
		return $response->withRedirect($this->router->pathFor('perfil.index'));
	}
}