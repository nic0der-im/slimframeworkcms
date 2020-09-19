<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
	protected $table = 'suscripciones';

	protected $fillable = [
		'gr_contactId',
		'email',
		'origen',
		'token',
		'estado',
	];

	public function campañas() {
		return $this->hasMany('App\Models\SuscripcionCampaña', 'id_suscripcion', 'id');
	}

	public function listas() {
		return $this->hasMany('App\Models\SuscripcionLista', 'id_suscripcion', 'id');
	}

}