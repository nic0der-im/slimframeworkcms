<?php

namespace App\Models\Negocio;
use Illuminate\Database\Eloquent\Model;

class CarpetaCliente extends Model
{
	protected $table = 'carpeta_cliente';

	protected $fillable = [
		'id_carpeta',
		'id_cliente',
		'id_usuario',
		'estado',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function cliente() {
		return $this->hasOne('App\Models\Negocio\Cliente', 'id', 'id_cliente');
	}

}