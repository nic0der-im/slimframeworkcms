<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class SucursalEmpleado extends Model
{
	protected $table = 'tra_sucursal_empleado';

	protected $fillable = [
		'id_sucursal',
		'id_usuario',
		'usuario_alta',
		'estado',
	];
}