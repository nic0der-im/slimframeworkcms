<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

use App\Models\Publicacion;
use App\Models\PublicacionVista;

class Publicacion extends Model{

	protected $table = 'publicaciones';

	protected $fillable = [
		'id_vehiculo',
		'id_usuario',
		'titulo',
		'contenido',
		'mostrar',
		'publicaciones',
		'vistas',
	];

	public function vehiculo() {
		return $this->hasOne('App\Models\Vehiculos\Vehiculo', 'id', 'id_vehiculo');
	}

	public function empleado() {
		return $this->hasOne('App\Models\Empleado', 'id_usuario');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function renobaciones() {
		return $this->hasMany('App\Models\PublicacionRenobacion','id_publicacion', 'id');
	}

	public function scopeActiva($query)
	{
		return $query->where('mostrar', '=', 1);
	}

  protected $appends = ['rank'];

  public function getRankAttribute(){
  	$dt = new \DateTime();
  	$vistas = $this->vistas;
  	if(new \DateTime($this->created_at)>$dt->modify('-2 Week')){
			$rank = Publicacion::where('mostrar', 1)->orderBy('vistas','desc')->limit(9)->get();
  		$vistas = $rank[random_int(2, 8)]->vistas;
  	}
    return $vistas;
  }

}
