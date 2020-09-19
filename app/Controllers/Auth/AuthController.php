<?php

namespace App\Controllers\Auth;


use App\Funcionalidades\Correo;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Individuo;
use App\Models\Suscripcion;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use SecurityLib\Strength;
use App\Auth\auth;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\Marca;
use App\Models\Vehiculos\Transmision;
use App\Models\Vehiculos\TiposMotor;
use App\Models\Vehiculos\EstadosVeh;
use App\Models\Vehiculos\ImagenesVeh;
use App\Models\Extras\Localidad;
use App\Models\Publicacion;

class AuthController extends Controller
{

	public function getSignOut($request, $response)
	{
		$this->logger->info("Sign Out-Usuario: ".auth::individuo()->id_usuario." ".auth::individuo()->nombre." ".auth::individuo()->apellido);
		$this->auth->logout();
		$this->flash->addMessage('info', '¡Hasta luego!');
		return $response->withRedirect($this->router->pathFor('home'));
	}
	public function getSignIn($request, $response, array $args)
	{
		if(!isset($args['return_to'])) {
			$args['return_to'] = "";
		} else {
				$parametros = str_replace(array('/marca/', 'marca/', '/marca'), '#!#marca#@#',$args['return_to']);
				$parametros = str_replace(array('/ubicacion/', 'ubicacion/', '/ubicacion'), '#!#ubicacion#@#', $parametros);
				$parametros = str_replace(array('/year/', 'year/', '/year'), '#!#year#@#', $parametros);
				$parametros = str_replace(array('/transmision/', 'transmision/', '/transmision'), '#!#transmision#@#', $parametros);
				$parametros = str_replace(array('/motor/', 'motor/', '/motor'), '#!#motor#@#', $parametros);
				$parametros = str_replace(array('/modelo/', 'modelo/', '/modelo'), '#!#modelo#@#', $parametros);
				$tags = explode('#!#', $parametros);
				foreach($tags as $tag) {
					$tmp_tag = explode('#@#', $tag);
					if(isset($tmp_tag[1])) {		
						$filtred_tags[$tmp_tag[0]] = str_replace('-', ' ', $tmp_tag[1]);
					}
				}
				if(count($tags)>1){
					$unset = 0;
					$search_keys = array();
					foreach($filtred_tags as $fkey=>$tag) {
						switch($fkey) {
							case 'ubicacion': {
								$tmp = Localidad::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_localidad'; $return = 'id'; $show = 'nombre';
								break;
							}
							case 'marca': {
								$tmp = Marca::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_marca';	$return = 'id';	$show = 'nombre';
								break;
							}
							case 'transmision': {
								$tmp = Transmision::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_transmision'; $return = 'id'; $show = 'nombre';
								break;					
							}
							case 'motor': {
								$tmp = TiposMotor::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_tipo_motor'; $return = 'id'; $show = 'nombre';
								break;
							}
							case 'modelo': {
								$tmp = Vehiculo::where('modelo', 'like', '%'.$tag.'%')->orderBy('modelo')->first();
								$search_for = 'modelo'; $return = 'modelo'; $show = 'modelo';
								break;
							}
							case 'year': {
								$tmp = Vehiculo::where('year', 'like', '%'.$tag.'%')->first();
								$search_for = 'year'; $return = 'year'; $show = 'year';
								break;
							}
						}
						if($tmp) {				
							$tmp->search_for = $search_for;
							$tmp->return = $return;
							$tmp->show = $tmp->$show;
							$filtred_tags[$fkey] = $tmp;
							$search_keys[$search_for] = $tmp;
						}
						else {
							$unset++;
						}
					}
				} else {
					$pos = strrpos($args['return_to'], "_") + 1;
					$id = substr($args['return_to'], $pos);
					$publicacion = Publicacion::where('id', $id)->first();
				}
		}
		return $this->view->render($response, 'guest_views/auth/signin.twig',[
			'return_url'=>$args['return_to'],
			'filtros'=> isset($filtred_tags)?$filtred_tags:null,
			'publicacion'=> isset($publicacion)?$publicacion:null,
		]);
	}

	public function postFacebookRequest($request, $response) 
	{
		if($request->isXhr()) 
		{
			//Comprobar si el email está registrado.
			//$usuario = User::where('email', $request->getParam('email'))->first();
			$usuario = User::where('facebook_id', $request->getParam('id_facebook'))->first();
			if($usuario) 
			{
				$_SESSION['userid'] = $usuario->id;
				$this->flash->addMessage('info', '¡Bienvenido!');
				
				$_SESSION['id_session'] = UserSession::create([
					'id_usuario' => $_SESSION['userid'],
					'ip' => $request->getAttribute('ip_address'),
				])->id;
				return $response->withJson([
					'login'=>true,
					'return_url'=>$request->getParam('return_url'),
					'first_login'=>false,
				]);
				/*//Si está registrado se comprueba que la ID de usuario de facebook coincida.
				if($usuario->facebook_id == $request->getParam('id_facebook')) {
					$_SESSION['userid'] = $usuario->id;
					$this->flash->addMessage('info', '¡Bienvenido!');
					return $response->withJson([
						'login'=>true,
						'return_url'=>$request->getParam('return_url'),
						'first_login'=>false,
					]);
				}
				else
				 {
					return $response->withJson([
						'login'=>false,
						'reason'=>'WRONG_ID'
					]);
				}*/
			}
			else 
			{
				//Si no está registrado, se crea una cuenta con el email y el facebook_id.
				
				$factory = new Factory;
				$generator = $factory->getMediumStrengthGenerator();

				$passwordLength = 12; // Or more
				$randomPassword = $generator->generateString($passwordLength);

				$user = User::create([
					'email'=>null/*$request->getParam('email')*/,
					'password'=>password_hash($randomPassword, PASSWORD_DEFAULT),
					'facebook_id'=>$request->getParam('id_facebook')
				]);

				$individuo = Individuo::Create([
					'id_usuario'=>$user->id,
					'nombre'=>$request->getParam('nombre'),
					'apellido'=>$request->getParam('apellido'),
					'telefono'=>null,
				]);

				$empleado = Empleado::Create([
					'id_usuario'=>$user->id,
					'segundo_correo'=>$request->getParam('email'),
				]);
				
				$_SESSION['userid'] = $user->id;

				UserSession::where('id_usuario',$_SESSION['userid'])->update([
					'estado'=>0,
				]);
				$_SESSION['id_session'] = UserSession::create([
					'id_usuario' => $_SESSION['userid'],
					'ip' => $request->getAttribute('ip_address'),
				])->id;
				
				$this->auth->attempt($request->getParam('email'), $randomPassword);
				return $response->withJson([
					'login'=>true,
					'return_url'=>$request->getParam('return_url'),
					'first_login'=>true,
				]);
			}
		}		
	}

	public function postSignIn($request, $response)
	{

		$validation = $this->validator->validate($request, [
			'car_email'=>v::email(),
			'car_password'=>v::notEmpty()->noWhitespace(),
		]);

		if($validation->failed()) {
			if(!empty($request->getParam('return_to'))) {
				$query='vehiculos/filtrar/';
				$string = $request->getParam('return_to');
				if(substr($string, 0, strlen($query)) === $query){
					$string=str_replace($query, '', $string);
					$string=rtrim($string,"/ ");
				}
				return $response->withRedirect($this->router->pathFor('auth.signin',['return_to'=>$string]));	
			}
			return $response->withRedirect($this->router->pathFor('auth.signin'));
		}
		
		$auth = $this->auth->attempt($request->getParam('car_email'), $request->getParam('car_password'));

		if(!$auth) {
			if(!empty($request->getParam('return_to'))) {
				$query='vehiculos/filtrar/';
				$string = $request->getParam('return_to');
				if(substr($string, 0, strlen($query)) === $query){
					$string=str_replace($query, '', $string);
					$string=rtrim($string,"/ ");
				}
				return $response->withRedirect($this->router->pathFor('auth.signin',['return_to'=>$string]));	
			}
			return $response->withRedirect($this->router->pathFor('auth.signin'));
		}

		UserSession::where('id_usuario',$_SESSION['userid'])->update([
			'estado'=>0,
		]);
		$_SESSION['id_session'] = UserSession::create([
			'id_usuario' => $_SESSION['userid'],
			'ip' => $request->getAttribute('ip_address'),
		])->id;

		$this->flash->addMessage('info', '¡Bienvenido!');
		$this->logger->info("Sign In-Usuario: ".auth::individuo()->id_usuario." ".auth::individuo()->nombre." ".auth::individuo()->apellido);
		return $response->withRedirect('/'.$request->getParam('return_to'));
	}

	public function getSignUp($request, $response, array $args)
	{
		if(!isset($args['return_to'])) {
			$args['return_to'] = "";
		} else {
				$parametros = str_replace(array('/marca/', 'marca/', '/marca'), '#!#marca#@#',$args['return_to']);
				$parametros = str_replace(array('/ubicacion/', 'ubicacion/', '/ubicacion'), '#!#ubicacion#@#', $parametros);
				$parametros = str_replace(array('/year/', 'year/', '/year'), '#!#year#@#', $parametros);
				$parametros = str_replace(array('/transmision/', 'transmision/', '/transmision'), '#!#transmision#@#', $parametros);
				$parametros = str_replace(array('/motor/', 'motor/', '/motor'), '#!#motor#@#', $parametros);
				$parametros = str_replace(array('/modelo/', 'modelo/', '/modelo'), '#!#modelo#@#', $parametros);
				$tags = explode('#!#', $parametros);
				foreach($tags as $tag) {
					$tmp_tag = explode('#@#', $tag);
					if(isset($tmp_tag[1])) {		
						$filtred_tags[$tmp_tag[0]] = str_replace('-', ' ', $tmp_tag[1]);
					}
				}
				if(count($tags)>1){
					$unset = 0;
					$search_keys = array();
					foreach($filtred_tags as $fkey=>$tag) {
						switch($fkey) {
							case 'ubicacion': {
								$tmp = Localidad::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_localidad'; $return = 'id'; $show = 'nombre';
								break;
							}
							case 'marca': {
								$tmp = Marca::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_marca';	$return = 'id';	$show = 'nombre';
								break;
							}
							case 'transmision': {
								$tmp = Transmision::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_transmision'; $return = 'id'; $show = 'nombre';
								break;					
							}
							case 'motor': {
								$tmp = TiposMotor::where('nombre', 'like', '%'.$tag.'%')->first();
								$search_for = 'id_tipo_motor'; $return = 'id'; $show = 'nombre';
								break;
							}
							case 'modelo': {
								$tmp = Vehiculo::where('modelo', 'like', '%'.$tag.'%')->orderBy('modelo')->first();
								$search_for = 'modelo'; $return = 'modelo'; $show = 'modelo';
								break;
							}
							case 'year': {
								$tmp = Vehiculo::where('year', 'like', '%'.$tag.'%')->first();
								$search_for = 'year'; $return = 'year'; $show = 'year';
								break;
							}
						}
						if($tmp) {				
							$tmp->search_for = $search_for;
							$tmp->return = $return;
							$tmp->show = $tmp->$show;
							$filtred_tags[$fkey] = $tmp;
							$search_keys[$search_for] = $tmp;
						}
						else {
							$unset++;
						}
					}
				} else {
					$pos = strrpos($args['return_to'], "_") + 1;
					$id = substr($args['return_to'], $pos);
					$publicacion = Publicacion::where('id', $id)->first();
				}
		}
		return $this->view->render($response, 'guest_views/auth/signup.twig',[
			'return_url'=>$args['return_to'],
			'filtros'=> isset($filtred_tags)?$filtred_tags:null,
			'publicacion'=> isset($publicacion)?$publicacion:null,
		]);
	}

	public function postSignUp($request, $response)
	{

		$validation = $this->validator->validate($request, [
			'car_nombre'=>v::notEmpty()->alpha(),
			'car_apellido'=>v::notEmpty()->alpha(),
			'car_email'=>v::email()->EmailAvailable(),
			'car_phone'=>v::notEmpty()->noWhitespace(),
			'car_password'=>v::notEmpty()->noWhitespace(),
		]);

		if($validation->failed()) {
			return $response->withRedirect($this->router->pathFor('auth.signup'));
		}

		$user = User::Create([
			'email'=>$request->getParam('car_email'),
			'password'=>password_hash($request->getParam('car_password'), PASSWORD_DEFAULT)			
		]);


		$individuo = Individuo::Create([
			'id_usuario'=>$user->id,
			'nombre'=>$request->getParam('car_nombre'),
			'apellido'=>$request->getParam('car_apellido'),
			'telefono'=>$request->getParam('car_phone'),
		]);

		$factory = new \RandomLib\Factory;
		$generator = $factory->getMediumStrengthGenerator();

		Suscripcion::create([
			'email' => $request->getParam('car_email'),
			'origen' => 'usuario',
			'token' => $generator->generateString(128,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
		]);

		
		$this->auth->attempt($request->getParam('car_email'), $request->getParam('car_password'));

		$_SESSION['id_session'] = UserSession::create([
			'id_usuario' => $_SESSION['userid'],
			'ip' => $request->getAttribute('ip_address'),
		])->id;

		return $response->withRedirect($this->router->pathFor('auth.firstwelcome'));
	}

	public function FirstWelcome($request, $response) {
		return $this->view->render($response, 'guest_views/auth/firstwelcome.twig');
	}

	public function recuperar($request,$response){
		return $this->view->render($response, 'guest_views/auth/contraseña_recuperar.twig');
	}

	public function recuperarEmail($request,$response){
		$email = $request->getParam('email');
		$cuenta = User::where('email',$email)->first();
		if ($cuenta) {
			try{
				if(strlen($cuenta->token)==0){
					$factory = new \RandomLib\Factory;
					$generator = $factory->getMediumStrengthGenerator();
					$token = $generator->generateString(128,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
					$cuenta->token = $token;
					$cuenta->save();
				} else {
					$token = $cuenta->token;
				}
				
				$link = $this->router->pathFor('recuperar.cambiar',['token'=>$token]);
				$link = 'https://ciroautomotores.com.ar'.$link;
				$mail = Correo::ciro();
				$mail->setFrom('no-reply@ciroautomotores.com.ar','No responder');
		    $mail->addAddress($email);

		    $mail->isHTML(true);
		    $mail->Subject = "Recuperacion de Contraseña";
		    $mail->Body    = '<p>Ingrese al siguiente link para poder cambiar la contraseña.</p><p><a href="'.$link.'">'.$link.'</a></p>';

		    $mail->send();
				$this->flash->addMessage('info', 'El correo de recuperacion fue enviado. Revisé su casilla de correo');
		  } catch (\Exception $e) {
		  	$this->flash->addMessage('error', 'A ocurrido un error en nuestro servidor. '.$e->getMessage());
		  }
		} else {
			$this->flash->addMessage('error', 'El correo ingresado no se encuentra registrado.');
		}
		return $response->withRedirect($this->router->pathFor('recuperar'));
	}

	public function recuperarValidar($request,$response,$args){
		$token = $args['token'];
		if(strlen($token)>=128){
			$cuenta = User::where('token',$token)->first();
			if($cuenta){
				return $this->view->render($response, 'guest_views/auth/contraseña_cambiar.twig',['token'=>$token]);
			}
		}
		$this->flash->addMessage('warning', 'El token no existe.');
		return $response->withRedirect($this->router->pathFor('auth.signin'));
	}

	public function recuperarCambiar($request,$response,$args){
		$nueva = $request->getParam('nueva');
		$re = $request->getParam('re');

		if($nueva == $re ){
			User::where('token',$token)->update([
				'password' => password_hash($nueva, PASSWORD_DEFAULT)	,
				'token' => '',
			]);
			$this->flash->addMessage('info', 'La contraseña fue cambiada con exito. Vuelva a ingresar al sistema');
			return $response->withRedirect('/');
		} else {
			$this->flash->addMessage('warning', 'La contraseña nueva no coincide. Vuelva ingresar desde el correo.');
			return $response->withRedirect($this->router->pathFor('auth.signin'));
		}
		
	}



}