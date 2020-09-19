<?php

namespace App\Models\SP_Extras;
use Illuminate\Database\Eloquent\Model;

class Agencia extends Model
{
	protected $table = 'sp_agencias';
	protected $fillable = [
		'agencia',
	];

	public $timestamps = false;

	
	public function getVendedores() {
		return $this->hasMany('App\Models\SP_Extras\Vendedor', 'id_agencia');
	}

	public function getEmpleados() {
		return $this->hasMany('App\Models\Empleado', 'id_agencia');
	}

	
}