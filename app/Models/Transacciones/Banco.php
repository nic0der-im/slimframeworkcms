<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
	protected $table = 'tra_bancos';
	protected $fillable = [
		'codigo',
		'denominacion',
		'nombre',
		'imagen',
		'estado',
	];

}