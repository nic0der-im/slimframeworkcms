<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PaginasFotos extends Model
{
	protected $table = 'paginas_fotos';

	protected $fillable = [
		'id_pagina',
		'enlace',
	];
}