<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class HistorialProspecto extends Model
{
	protected $table = 'prospecto_historial';

	protected $fillable = [
		'id_prospecto',
		'id_vendedor',
		'duracion_llamada',
		'observaciones',
		'valor_estado',
		'estado',
	];
	
	public function getProspecto() {
		return $this->hasOne('App\Models\Prospecto', 'id', 'id_prospecto');
	}

	public function individuo(){
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_vendedor');
	}
}