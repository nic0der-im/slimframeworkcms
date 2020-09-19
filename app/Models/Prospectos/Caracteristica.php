<?php

namespace App\Models\Prospectos;
use Illuminate\Database\Eloquent\Model;

class Caracteristica extends Model
{
	protected $table = 'veh_caracteristicas';

	public function prospectoCaracteristica() {
		return $this->hasMany('App\Models\Prospectos\Caracteristica', 'id_caracteristica', 'id');
	}
	
	public $timestamps = false;
}