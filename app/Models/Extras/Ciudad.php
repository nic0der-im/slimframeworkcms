<?php

namespace App\Models\Extras;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
	protected $table = 'ciudades';

	public function provincia() {
		return $this->hasOne('App\Models\Individuo', 'id', 'id_provincia');
	}

}