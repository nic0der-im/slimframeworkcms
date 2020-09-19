<?php

namespace App\Models\Notificaciones;
use Illuminate\Database\Eloquent\Model;

class Notificaciones extends Model
{
	protected $table = 'notificaciones_categoria';

	protected $fillable = [
		'nombre',		
		'color',
	];
}
