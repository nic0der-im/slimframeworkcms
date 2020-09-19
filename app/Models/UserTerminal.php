<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserTerminal extends Model
{
	protected $table = 'usuario_terminal';

	protected $fillable = [
		'id_usuario',
		'latitud',
		'longitud',
		'estado',
    'os_user_id',
	];

	public function sessiones() {
		return $this->hasMany('App\Models\UserActividad', 'id_terminal', 'id');
	}

}
