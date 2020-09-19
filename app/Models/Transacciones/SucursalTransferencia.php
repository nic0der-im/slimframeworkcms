<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class SucursalTransferencia extends Model
{
	protected $table = 'tra_sucursal_transferencia';

	protected $fillable = [
		'id_sucursal_origen',
		'id_sucursal_destino',
		'id_comprobante_origen',
		'id_comprobante_destino',
		'total',
		'responsable',
		'observaciones',
		'id_usuario',
		'estado',
	];
}