<?php

namespace App\Models\Correo;
use Illuminate\Database\Eloquent\Model;

class Alias extends Model
{
	protected $table = 'vir_alias';

	protected $fillable = [
		'origen',		
		'destino',
		'id_dominio',
		'estado',
	];

}