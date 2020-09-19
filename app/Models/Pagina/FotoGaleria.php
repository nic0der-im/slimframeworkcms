<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class FotoGaleria extends Model
{
	protected $table = 'paginas_bloques_fotogaleria';
	public $timestamps = false;
	

	protected $fillable = [
		'id_bloque',
		'orden',
		'enlace',
		'mode',
		'alt'
	];
	
}