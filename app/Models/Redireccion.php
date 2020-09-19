<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Redireccion extends Model
{
	protected $table = 'redirecciones';

	protected $fillable = [
		'url',		
		'destino',
	];
}