<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

/**
* El movimiento de caja representa la fluctuacion del dinero para la caja asignada
* que puede ser de entrada o salida, a diferencia de una factura que solo representa
* la proyeccion del costo, el movimiento es el concreto.
* Es posible que el movimiento se referido a un comprobante o no.
*/
class Movimiento extends Model
{
	protected $table = 'tra_movimientos';
	protected $fillable = [
		'id_usuario',
		'cheque_numero',
		'cheque_banco',
		'id_banco',
		'cheque_cuenta',
		'fecha',
		'cheque_vto',
		'fecha_cobro',
		'id_tipo_movimiento',
		'tarjeta_numero',
		'tarjeta_titular',
		'tarjeta_cupon',
		'tarjeta_adicional',
		'tarjeta_cuota',
		'tributo_numero',
		'tributo_nombre',
		'documento_emisor',
		'documento_receptor',
		'monto',
		'id_sucursal',
		'estado',
		'descripcion',
		'tipo_ioe', //0: INGRESO | 1: EGRESO
	];

	public function comprobantes(){
		return $this->hasMany('App\Models\Transacciones\FacturaRecibo', 'id_movimiento', 'id');
	}

	public function banco(){
		return $this->hasOne('App\Models\Transacciones\Banco','id','id_banco');
	}

	public function tipo(){
		return $this->hasOne('App\Models\Transacciones\TipoMovimiento','id','id_tipo_movimiento');
	}

	public function individuo(){
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function obligacion(){
		return $this->hasOne('App\Models\Transacciones\Obligacion','id_movimiento','id');
	}

	public function sucursal() {
		return $this->hasOne('App\Models\Transacciones\Sucursal', 'id', 'id_sucursal');
	}

	protected $appends = ['caracteristica'];

 protected $dates = [
    'created_at',
    'updated_at',
  ];

	public function getCaracteristicaAttribute(){
		$respuesta = '';
		switch ($this['id_tipo_movimiento']) {
			case 1:// EFECTIVO
				break;
			case 2:// CHEQUE
				$respuesta = 'Cuenta: '.$this['cheque_cuenta'].' - Numero:'.$this['cheque_numero'];
				break;
			case 3:// TARJETA
				$respuesta = 'Titular: '.$this['tarjeta_titular'].' - Numero:'.$this['tarjeta_numero'];
				break;
			case 4:// TRIBUTO
				$respuesta = 'Impuesto: '.$this['tributo_nombre'].' - Numero:'.$this['tributo_numero'];
				break;
			case 5:// DOCUMENTO
				$respuesta = 'Emisor: '.$this['documento_emisor'].' - Receptor:'.$this['documento_receptor'];
				break;
		}
		return $respuesta;
	}
}