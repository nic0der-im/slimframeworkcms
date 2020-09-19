<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

use App\Models\Publicacion;

class PublicacionVista extends Model{

	protected $table = 'publicacion_vista';

	protected $fillable = [
		'id_publicacion',
		'ip',
	];

}