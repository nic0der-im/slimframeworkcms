<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PublicacionRenobacion extends Model
{
	protected $table = 'publicacion_renobacion';

	protected $fillable = [
		'id_publicacion',		
		'id_usuario',
		'fecha',
		'dias',
		'fecha_vto',
		'observacion',
		'estado',
	];

	public function publicacion() {
		return $this->hasOne('App\Models\Publicacion', 'id', 'id_publicacion');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}
}