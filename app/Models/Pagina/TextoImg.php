<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class TextoImg extends Model
{
	protected $table = 'paginas_bloques_textoimg';
	
	protected $fillable = [
		'id_bloque',
		'orden',
		'titulo',
		'parrafo',
		'imagen'
	];
	
}