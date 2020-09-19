<?php

namespace App\Models\Extras;
use Illuminate\Database\Eloquent\Model;

class Profesion extends Model
{
	protected $table = 'profesiones';
	protected $fillable = [
		'nombre',
		'estado',
	];

	public $timestamps = false;

}