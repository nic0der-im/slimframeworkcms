<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Permisos extends Model
{
	protected $table = 'permisos_usuario';

	protected $fillable = [
		'id_enlace',		
		'id_usuario',
	];

	public function getModulos() {
		return $this->hasMany('App\Models\ModuloEnlaces', 'id', 'id_enlace')->orderBy('orden', 'asc');
	}
}