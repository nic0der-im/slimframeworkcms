<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PaginasBloques extends Model
{
	protected $table = 'paginas_bloques';

	protected $fillable = [
		'id_pagina',
		'titulo',
		'parrafo',
		'imagen',
		'orden',
		'tipo',
		'imagen_title'
	];

	public function scopeActiva($query)
	{
		return $query->where('mostrar', '=', 1);
	}

	public function fotos() {
		return $this->hasMany('App\Models\PaginasFotos', 'id_pagina');
	}
}