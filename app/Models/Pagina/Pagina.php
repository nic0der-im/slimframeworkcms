<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class Pagina extends Model
{
	protected $table = 'paginas';

	protected $fillable = [
		'id_usuario',
		'id_marca',
		'titulo',
		'url_name',
		'descripcion',
		'keywords',
		'mostrar',
		'eliminado',
		'contador',
	];

	public function scopeActiva($query)
	{
		return $query->where('mostrar', '=', 1);
	}	

	public function getAutor() {
		return $this->belongsTo('App\Models\User', 'id_usuario');
	}

	public function getBloques() {
		return $this->hasMany('App\Models\Pagina\Bloque', 'id_pagina');
	}


}