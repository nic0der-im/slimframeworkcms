<?php

namespace App\Models\Notificaciones;
use Illuminate\Database\Eloquent\Model;

class Notificaciones extends Model
{
	protected $table = 'notificaciones';

	protected $fillable = [
		'id_usuario',
		'estado',
		'prioridad',
		'mensaje',
		'titulo',
		'url',
		'id_puesto',
	];
}
