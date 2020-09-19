<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class Bloque extends Model
{
	protected $table = 'paginas_bloques';

	protected $fillable = [
		'id_pagina',
		'nombre',		
		'href',
		'orden',
		'tipo',
	];

	public function getFotosGaleria() {
		return $this->hasMany('App\Models\Pagina\FotoGaleria', 'id_bloque');
	}

	public function getFullscreen() {
		return $this->hasOne('App\Models\Pagina\Fullscreen', 'id_bloque');
	}

	public function getItemsTextoImg() {
		return $this->hasMany('App\Models\Pagina\TextoImg', 'id_bloque');	
	}

	public function getItemsFichaTecnica() {
		return $this->hasMany('App\Models\Pagina\FichaTecnica', 'id_bloque');	
	}

	public function getVersiones() {
		return $this->hasMany('App\Models\Pagina\Version', 'id_bloque');	
	}

	public function scopegetVersiones($query) {
		return $query->where('tipo', 'versiones');

	}

}