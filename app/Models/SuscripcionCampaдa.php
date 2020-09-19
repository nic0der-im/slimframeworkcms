<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SuscripcionCampaña extends Model{

	protected $table = 'suscripcion_campaña';

	protected $fillable = [
		'id_suscripcion',
		'gr_campaignId',
		'gr_name',
		'gr_estado',
		'estado',
	];

}