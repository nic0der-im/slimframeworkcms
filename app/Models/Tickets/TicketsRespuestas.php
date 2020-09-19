<?php

namespace App\Models\Tickets;
use Illuminate\Database\Eloquent\Model;

class TicketsRespuestas extends Model
{
	protected $table = 'tickets_respuestas';

	protected $fillable = [
		'id_ticket',		
		'id_usuario',
		'respuesta',
		'created_at',
		'updated_at',
	];
}