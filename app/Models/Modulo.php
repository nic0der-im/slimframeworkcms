<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuloEnlaces;
use App\Models\Permisos;
use App\Models\Manuales;

class Modulo extends Model
{
	protected $table = 'modulos';

	protected $fillable = [
		'nombre_modulo',		
		'enlace_modulo',
		'icono_modulo',
		'orden_modulo',
	];


	public function getManuales() {
		return $this->hasMany('App\Models\Manuales', 'categoria', 'id');
	}

	public function getEnlaces() {
		return $this->hasMany('App\Models\ModuloEnlaces', 'id_modulo', 'id')
			->orderBy('tipo_enlace','asc')
			->orderBy('orden', 'asc');
	}

	public function scopeTengoAcceso() {
		$enlaces = $this->getEnlaces()->get();
		$permisos = Permisos::where('id_usuario', $_SESSION['userid'])->get();

		foreach($enlaces as $enlace) {
			foreach($permisos as $permiso)
			{
				if($permiso->id_enlace == $enlace->id) 
				{
					return true;
				}
			}
		}
		return false;
	}

	public $timestamps = false;

}