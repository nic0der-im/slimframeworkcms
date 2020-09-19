<?php

namespace App\Models\Prospectos;
use Illuminate\Database\Eloquent\Model;

use App\Models\Prospectos\Caracteristica;
use Illuminate\Database\Capsule\Manager as DB;

class ProspectoCaracteristica extends Model
{
	protected $table = 'prospecto_caracteristicas';
	protected $fillable = [
		'id_prospecto',
		'id_caracteristica',
		'caracteristica',
		'estado',
	];
	
	public function prospecto() {
		return $this->hasOne('App\Models\Prospecto', 'id', 'id_prospecto');
	}

	public function caracter() {
		return $this->hasOne('App\Models\Prospectos\Caracteristica', 'id', 'id_caracteristica');
	}

	protected $appends = ['nombre'];

	public function getNombreAttribute(){
		$caracteristica = Caracteristica::where('id',$this['id_caracteristica'])->first();
		if($caracteristica){
			if(strlen($caracteristica->tabla)>0){
				$query = DB::table($caracteristica->tabla)->where('id',$this['caracteristica'])->first();
				return $query->nombre;
			} else {
				return $this['caracteristica'];
			}
		}
		return '';
	}

}