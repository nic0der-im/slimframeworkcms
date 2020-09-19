<?php

namespace App\Models\Correo;
use Illuminate\Database\Eloquent\Model;

class UsuarioVirtual extends Model
{
	protected $table = 'vir_usuarios';

	protected $fillable = [
		'email',		
		'contrasenia',
		'estado',
		'id_dominio',
		'id_usuario',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function dominio() {
		return $this->hasOne('App\Models\Correo\Dominio', 'id', 'id_dominio');
	}

}