<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class Fullscreen extends Model
{
	protected $table = 'paginas_bloques_fullscreen';
	public $timestamps = false;
	

	protected $fillable = [
		'id_bloque',
		'titulo',		
		'subtitulo',
		'imagen',
	];

}