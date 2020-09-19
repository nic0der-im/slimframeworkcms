<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ModuloEnlaces extends Model
{
	protected $table = 'modulos_enlaces';

	protected $fillable = [
		'id_modulo',
		'tipo_enlace',
		'nombre',
		'url_name',
		'orden',
	];

	public $timestamps = false;
}