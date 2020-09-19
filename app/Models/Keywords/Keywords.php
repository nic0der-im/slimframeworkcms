<?php

namespace App\Models\Keywords;
use Illuminate\Database\Eloquent\Model;

class Keywords extends Model {
	
	protected $table = 'keywords';

	protected $fillable = [
		'formato',
		'estado',
	];
}
