<?php

namespace App\Models\SP_Extras;
use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
	protected $table = 'puestos';
	protected $fillable = [
		'nombre',
		'id_agencia'
	];

	public $timestamps = false;
	public $id_agencia;
	
	public function getEmpleados() {
		return $this->hasMany('App\Models\Empleado', 'id_puesto', 'id_puesto')->where('id_agencia', $this->id_agencia);
	}
}