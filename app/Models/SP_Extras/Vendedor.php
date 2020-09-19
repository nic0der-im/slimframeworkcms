<?php

namespace App\Models\SP_Extras;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
	protected $table = 'sp_vendedores';
	protected $fillable = [
		'id_usuario',
		'id_agencia',
	];

	public $timestamps = false;

	public function getEmpleado() {
		return $this->hasOne('App\Models\Empleado', 'id_usuario', 'id_usuario');
	}

	public function getIndividuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');	
	}
}