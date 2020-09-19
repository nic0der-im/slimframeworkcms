<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Manuales extends Model
{
	protected $table = 'manuales';

	protected $fillable = [
		'nombre',		
		'descripcion',
		'categoria',
		'texto',
	];

	public function nombrecategoria() {
		return $this->hasOne('App\Models\Modulo', 'id', 'categoria');
	}
}