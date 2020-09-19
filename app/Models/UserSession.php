<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
	protected $table = 'usuario_session';

	protected $fillable = [
		'id_usuario',
		'ip',
		'estado',
		'id_terminal',
	];

	public function actividades() {
		return $this->hasMany('App\Models\UserActividad', 'id_session', 'id');
	}

}
