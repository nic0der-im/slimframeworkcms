<?php

namespace App\Models\Extras;
use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
	protected $table = 'provincias';
	protected $fillable = [
		'nombre',
	];

	public $timestamps = false;

	public function getLocalidades() {
		return $this->hasMany('App\Models\Extras\Localidad', 'id_provincia', 'id');
	}

}