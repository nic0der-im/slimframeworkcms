<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class FichaTecnica extends Model
{
	protected $table = 'paginas_bloques_fichatecnica';
	public $timestamps = false;
	
	protected $fillable = [
		'id_bloque',
		'titulo',		
		'parrafo',
		'orden',
		'id_icono',
	];

	public function getIcono() {
		return $this->hasOne('App\Models\Pagina\Icono', 'id', 'id_icono');
	}


}