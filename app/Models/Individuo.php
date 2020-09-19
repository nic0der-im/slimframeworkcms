<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Individuo extends Model{
	
	protected $table = 'individuos';

	protected $fillable = [
		'nombre',
		'apellido',
		'id_usuario',
		'id_tipo_documento',
		'documento',
		'fecha_nacimiento',
		'telefono',
		'domicilio',
		'observaciones_domicilio',
		'id_localidad',
		'id_provincia',
		'email',
		'observaciones',
	];

	public function usuario() {
		return $this->hasOne('App\Models\User', 'id', 'id_usuario');	
	}

	public function empleado() {
		return $this->hasOne('App\Models\Empleado', 'id_usuario', 'id_usuario');	
	}

	public function sesiones() {
		return $this->hasMany('App\Models\UserSession', 'id_usuario', 'id_usuario');	
	}

	public function ciudad(){
		return $this->hasOne('App\Models\Extras\Ciudad', 'id', 'id_localidad');	
	}

}
