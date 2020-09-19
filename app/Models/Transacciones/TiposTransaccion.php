<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class TiposTransaccion extends Model
{
	protected $table = 'tra_tipos_transaccion';

	protected $fillable = [
		'tipo',
		'padre',
		'nombre_tipo',
		'sucursales',
		'estado_tipo',
	];

	public $timestamps = false;

  public function tipoTransaccion()
	{
	     return $this->hasOne('App\Models\Transacciones\TiposTransaccion', 'id', 'padre');
	}

}