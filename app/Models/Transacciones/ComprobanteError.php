<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

/**
*  Errores encontrados al procesar en el WS
*
*/
class ComprobanteError extends Model
{
	protected $table = 'tra_comprobante_errores';
	protected $fillable = [
		'id_comprobante',
		'id_usuario',
		'descripcion',
		'estado',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function comprobante() {
		return $this->hasOne('App\Models\Transacciones\Comprobante', 'id', 'id_comprobante');
	}

}