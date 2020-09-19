<?php

namespace App\Models\Correo;
use Illuminate\Database\Eloquent\Model;

class ModuloCorreo extends Model
{
	protected $table = 'modulo_correo';

	protected $fillable = [
		'id_usuario',
		'id_enlace',
		'url_name',
		'email',
		'estado',
	];

	public function enlace() {
		return $this->hasOne('App\Models\ModuloEnlaces', 'id', 'id_enlace');	
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');	
	}

}