<?php

namespace App\Models\Extras;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
	protected $table = 'ubicaciones';
	protected $fillable = [
		'nombre',
	];

	public $timestamps = false;
}