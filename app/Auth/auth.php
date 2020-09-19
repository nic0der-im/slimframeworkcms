<?php

namespace App\Auth;
use App\Models\User;
use App\Models\Individuo;
use App\Models\Empleado;
use App\Models\Permisos;
use App\Models\Modulo;
use App\Models\ModuloEnlaces;
use App\Models\Notificaciones\Notificaciones;

use App\Models\Transacciones\SucursalEmpleado;
use App\Models\Transacciones\Sucursal;
use App\Models\Correo\UsuarioVirtual;

use PhpImap\Mailbox;

class Auth
{
	public function logout()
	{
		unset($_SESSION['userid']);
		unset($_SESSION['id_sucursal']);
	}
	public function check()
	{
		return isset($_SESSION['userid']);
	}

	public function getPermisos()
	{
		if(isset($_SESSION['userid']))
		{
			return Permisos::where('id_usuario', $_SESSION['userid'])->get();
		}
		return false;
	}

	public function getPermisosEnlaces()
	{
		if(isset($_SESSION['userid']))
		{
			return Permisos::where('id_usuario', $_SESSION['userid'])->pluck('id_enlace')->toArray();
		}
		return [false];
	}

	public function getModulos()
	{
		return Modulo::orderBy('orden_modulo')->get();
	}

	public function user()
	{
		if(isset($_SESSION['userid']))
		{
			return User::find($_SESSION['userid']);
		}
		return false;
	}

	public function individuo()
	{
		if(isset($_SESSION['userid']))
		{
			return Individuo::where('id_usuario', $_SESSION['userid'])->first();
		}
		return false;
	}

	public function empleado()
	{
		if(isset($_SESSION['userid']))
		{
			return Empleado::where('id_usuario', $_SESSION['userid'])->first();
		}
		return false;
	}

	public function attempt($email, $password)
	{
		$user = User::where('email', $email)->first();

		if(!$user)
		{
			$_SESSION['errors']['car_email'][0] = "La dirección de correo no se encuentra registrada.";
			return false;
		}

		if(password_verify($password, $user->password))
		{
			$_SESSION['userid'] = $user->id;

			if (strpos($email, '@ciroautomotores.com.ar') !== false) {

				$usuarioVirtual = UsuarioVirtual::where('id_usuario',$user->id)->where('contrasenia','')->orWhereNull('contrasenia')->first();
				if($usuarioVirtual){
					$salt = substr(sha1(rand()), 0, 16);
					$hashedPassword = "{SHA512-CRYPT}" . crypt($password, "$6$$salt");
					UsuarioVirtual::where('id',$usuarioVirtual->id)->update([
						'contrasenia' => $hashedPassword,
					]);
				}

				try{
					$mailbox = new Mailbox('{mail.ciroautomotores.com.ar:143/imap/tls/novalidate-cert}', $email, $password , __DIR__);
					$_SESSION['mailbox'] = serialize($mailbox);
				}catch (Exception $e){
				}
			}

			return true;
		}
		else {
			$_SESSION['errors']['car_password'][0] = "La contraseña ingresada es incorrecta.";
		}

		return false;
	}

	public function sucursal($id_sucursal=null){
		if($id_sucursal!=null){
			unset($_SESSION['id_sucursal']);
			$_SESSION['id_sucursal']=$id_sucursal;
		}
		if(isset($_SESSION['id_sucursal']) and isset($_SESSION['userid']) ){
			return Sucursal::where('id',$_SESSION['id_sucursal'])->first();
		} else if ( isset($_SESSION['userid']) ) {
			$sucursalEmpleado = SucursalEmpleado::where('id_usuario',$_SESSION['userid'])->where('estado',1)->first();
			if($sucursalEmpleado){
				$_SESSION['id_sucursal']=$sucursalEmpleado->id_sucursal;
				return Sucursal::where('id',$sucursalEmpleado->id_sucursal)->first();
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function sucursales(){
		if (isset($_SESSION['userid'])) {
			$sucursalEmpleado = SucursalEmpleado::where(['id_usuario'=>$_SESSION['userid'],'estado'=>1])->get(['id_sucursal']);
			$sucursales = Sucursal::whereIn('id',$sucursalEmpleado)->get();
			return $sucursales;
		} else {
			return [];
		}
	}

	public function notificaciones($limit = 5){
		if (isset($_SESSION['userid'])) {
			if($limit>4){
				$sucursales = Notificaciones::whereIn('id_usuario',[0,$_SESSION['userid']])
					->orderBy('created_at','desc')
					->limit($limit)
					->get();
			} else {
				$sucursales = Notificaciones::whereIn('id_usuario',[0,$_SESSION['userid']])
					->orderBy('created_at','desc')
					->get();
			}
			return $sucursales;
		} else {
			return [];
		}
	}
}
