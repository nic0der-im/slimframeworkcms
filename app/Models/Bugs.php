<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bugs extends Model
{
	protected $table = 'bugs';

	protected $fillable = [
		'texto',		
		'descubiertopor',
		'estado',
		'tipo',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'descubiertopor');
	}
}