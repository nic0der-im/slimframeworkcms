<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model{
	
	protected $table = 'empleados';

	protected $fillable = [
		'id_usuario',
		'id_agencia',
		'id_puesto',
		'id_ubicacion',
		'acceso_sistema',
		'master',
		'ventas_usados',
		'ventas_0km',
		'fotoperfil',
		'msg_admin'
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function usuario() {
		return $this->hasOne('App\Models\User', 'id', 'id_usuario');
	}

	public function puesto() {
		return $this->hasOne('App\Models\SP_Extras\Puesto', 'id_puesto', 'id_puesto');	
	}

	public function agencia() {
		return $this->hasOne('App\Models\SP_Extras\Agencia', 'id', 'id_agencia');	
	}

	public function ubicacion() {
		return $this->hasOne('App\Models\SP_Extras\Ubicacion','id', 'id_ubicacion');
	}

	public function prospectos() {
		return $this->hasMany('App\Models\Prospecto','id_vendedor', 'id_usuario');
	}

	public function concretadas() {
		$concretadas = Prospecto::where('id_vendedor', $this->id)->where('estado', '4')->get();
		return count($concretadas);
	}

	public function perdidas() {
		$perdidas = Prospecto::where('id_vendedor', $this->id)->where('estado', '3')->get();
		return count($perdidas);
	}

	public function notificaciones() {
	 	return $this->hasMany('App\Models\Notificaciones\Notificaciones', 'id_usuario', 'id_usuario')->where('estado', 0)->orderBy('created_at', 'DESC');
	}

	public function correo() {
		return $this->hasOne('App\Models\Correo\UsuarioVirtual', 'id_usuario', 'id_usuario');
	}

	public function sesiones() {
		return $this->hasMany('App\Models\UserSession', 'id_usuario', 'id_usuario');	
	}
	
}