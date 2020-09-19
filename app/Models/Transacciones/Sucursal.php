<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
	protected $table = 'tra_sucursales';

	public function movimientos() {
		return $this->hasMany('App\Models\Transacciones\Movimiento', 'id_sucursal', 'id');
	}
}