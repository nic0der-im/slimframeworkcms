<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserActividad extends Model
{
	protected $table = 'usuario_actividad';

	protected $fillable = [
		'id_session',
		'route',
		'arguments',
		'method',
		'status_code',
	];

	
}