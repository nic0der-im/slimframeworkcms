<?php

namespace App\Models\Prospectos;
use Illuminate\Database\Eloquent\Model;

class ProspectoHistorial extends Model
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
	
	public function prospecto() {
		return $this->hasOne('App\Models\Prospecto', 'id', 'id_prospecto');
	}

	public function individuo(){
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_vendedor');
	}

	public function prospectoEstado() {
		return $this->hasOne('App\Models\ProspectoEstado', 'id', 'valor_estado');
	}
}