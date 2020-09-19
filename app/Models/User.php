<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
	protected $table = 'usuarios';

	protected $fillable = [
		'email',
		'password',
		'facebook_id'
	];

	public function sesiones() {
		return $this->hasMany('App\Models\UserSession', 'id_usuario', 'id');	
	}

	public function individuo(){
		return $this->hasOne('App\Models\Individuo','id_usuario','id');
	}
}