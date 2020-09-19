<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class Icono extends Model
{
	protected $table = 'paginas_iconos';

	protected $fillable = [
		'id_usuario',
		'nombre',
		'enlace',
	];

	
}