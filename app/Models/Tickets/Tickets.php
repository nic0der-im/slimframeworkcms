<?php

namespace App\Models\Tickets;
use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
	protected $table = 'tickets';

	protected $fillable = [
		'nombre',
		'id_usuario',		
		'descripcion',
		'destinatario',
		'asunto',
		'estado',
		'archivado',
		'urgente',
		'created_at',
		'updated_at',
	];

	public function respuestas() {
		return $this->hasMany('App\Models\Tickets\TicketsRespuestas','id_ticket', 'id');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo','id_usuario', 'id_usuario');
	}
}