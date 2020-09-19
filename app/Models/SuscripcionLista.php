<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SuscripcionLista extends Model{

	protected $table = 'suscripcion_lista';

	protected $fillable = [
		'id_suscripcion',
		'mc_memberId',
		'mc_listId',
		'estado',
	];

}