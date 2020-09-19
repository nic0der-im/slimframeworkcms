<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

/**
* Unidades o cantidad en la relacion entre una factura y su recibo
* con el movimiento de caja. Si una factura es pagada, se debe generarle
* un recibo X para constatar el pago.
* Por cada factura que tienen sus detalles o item, a cada linea se debe
* generarle su pago en el recibo indicando la cantidad de productos a pagar.
* 
*/
class FacturaRecibo extends Model
{
	protected $table = 'tra_factura_recibo';
	protected $fillable = [
		'id_factura',
		'id_recibo',
		'id_movimiento',
		'id_usuario',
		'estado',
		'cantidad',
	];

	public function factura_detalle() {
		return $this->hasOne('App\Models\Transacciones\ComprobanteDetalle', 'id', 'id_factura');
	}

	public function recibo_detalle() {
		return $this->hasOne('App\Models\Transacciones\ComprobanteDetalle', 'id', 'id_recibo');
	}

	public function movimiento() {
		return $this->hasOne('App\Models\Transacciones\Movimiento', 'id', 'id_movimiento');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

}