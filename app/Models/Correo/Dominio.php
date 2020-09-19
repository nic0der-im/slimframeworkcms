<?php

namespace App\Models\Correo;
use Illuminate\Database\Eloquent\Model;

class Dominio extends Model
{
	protected $table = 'vir_dominios';

	protected $fillable = [
		'nombre',		
		'estado',
	];

}