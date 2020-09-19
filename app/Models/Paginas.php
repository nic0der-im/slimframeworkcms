<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Paginas extends Model
{
	protected $table = 'paginas';

	protected $fillable = [
		'id_usuario',
		'id_marca',
		'titulo',
		'contenido',
		'mostrar',
		'eliminado',
		'carrusel1_img',
		'carrusel1_titulo',
		'carrusel1_parrafo',
		'carrusel2_img',
		'carrusel2_titulo',
		'carrusel2_parrafo',
		'carrusel3_img',
		'carrusel3_titulo',
		'carrusel3_parrafo',
		'url_parallax',
		'contador',
	];

	public function scopeActiva($query)
	{
		return $query->where('mostrar', '=', 1);
	}

	public function fotos() {
		return $this->hasMany('App\Models\PaginasFotos', 'id_pagina');
	}
}