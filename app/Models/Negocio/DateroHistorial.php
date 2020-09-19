<?php

namespace App\Models\Negocio;
use Illuminate\Database\Eloquent\Model;

class DateroHistorial extends Model
{
	protected $table = 'datero_historial';

	protected $fillable = [
		'id_datero',
		'id_usuario',
		'id_tipo_estado',
		'descripcion',
		'estado',
	];

	public function estado() {
		return $this->hasOne('App\Models\Negocio\DateroEstado', 'id', 'id_tipo_estado');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

}