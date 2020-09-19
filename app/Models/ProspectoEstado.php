<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProspectoEstado extends Model
{
	protected $table = 'prospecto_tipo_estados';

	public function prospectos() {
		return $this->hasMany('App\Models\Prospectos\ProspectoHistorial', 'estado', 'id');
	}
}