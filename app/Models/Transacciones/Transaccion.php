<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
	protected $table = 'tra_transacciones';
	protected $fillable = [
	  'id_sucursal',
	  'id_diaria',
	  'id_empleado',
	  'tipo_transaccion',
	  'tipo_eoi',
	  'id_origen',
	  'id_destino',
	  'descripcion_transaccion',
	  'importe_transaccion',
	  'modo_transaccion',
	  'estado_transaccion',
	  'fecha_transaccion',
	  'id_cancelo',
	  'obligaciones',
	];

	public function origen()
	{
		return $this->hasOne('App\Models\Transacciones\Origen','id','id_origen');
	}

	public function destino()
	{
		return $this->hasOne('App\Models\Transacciones\Destino','id','id_destino');
	}

	public function modo()
	{
		return $this->hasOne('App\Models\Transacciones\Modo','id','modo_transaccion');
	}

	public function empleado()
	{
		return $this->hasOne('App\Models\Empleado','id','id_empleado');
	}

	public function empleadoCancelo()
	{
		return $this->hasOne('App\Models\Empleado','id','id_cancelo');
	}

	public function tipoTransaccion()
	{
		return $this->hasOne('App\Models\Transacciones\TiposTransaccion','id','tipo_transaccion');
	}
}