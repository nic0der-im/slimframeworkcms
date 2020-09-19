<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
	protected $table = 'consultas';

	protected $fillable = [
		'id_usuario',
		'nombre',		
		'apellido',
		'telefono',
		'texto',
		'url',
		'email',
		'prospectado',
		'estado',
	];
	
	public function usuario() {
		return $this->hasOne('App\Models\User', 'id', 'id_usuario');
	}
}
