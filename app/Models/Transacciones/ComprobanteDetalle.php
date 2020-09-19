<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class ComprobanteDetalle extends Model
{
	protected $table = 'tra_comprobante_detalles';
	protected $fillable = [
		'id_comprobante',
		'id_tipo_condicion_iva',
		'codigo',
		'descripcion',
		'importe',
		'cantidad',
		'impuesto',
		'bonificado',
		'subtotal',
		'estado',
		'id_usuario',
		'orden',
	];

	public function recibos() {
		return $this->hasMany('App\Models\Transacciones\FacturaRecibo', 'id_factura', 'id');
	}

	public function facturas() {
		return $this->hasMany('App\Models\Transacciones\FacturaRecibo', 'id_recibo', 'id');
	}

	public function comprobante() {
		return $this->hasOne('App\Models\Transacciones\Comprobante', 'id', 'id_comprobante');
	}

	public function tipoCondicionIva() {
		return $this->hasOne('App\Models\Transacciones\TipoCondicionIva', 'id', 'id_tipo_condicion_iva');
	}

}