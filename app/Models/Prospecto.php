<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

use App\Models\Prospectos\ProspectoHistorial;
use App\Models\Prospectos\Fuentes;

class Prospecto extends Model
{
	protected $table = 'prospectos';
	protected $fillable = [
		'id_vendedor',
		'id_sniper',
		'id_agencia',
		'nombre',
		'apellido',
		'telefono',
		'localidad',
		'observaciones',
		'estado',
		'fuente',
	];
	
	public function getVendedor() {
		return $this->hasOne('App\Models\Empleado', 'id_usuario', 'id_vendedor');
	}

	public function getVendedorNombre() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_vendedor');
	}

	public function getSniper() {
		return $this->hasOne('App\Models\Empleado', 'id_usuario', 'id_sniper');
	}

	public function getSniperNombre() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_sniper');
	}

	public function getHistorial() {
		return $this->hasMany('App\Models\Prospectos\ProspectoHistorial', 'id_prospecto', 'id');
	}

	public function getHistorialNoProcesos() {
		return $this->hasMany('App\Models\Prospectos\ProspectoHistorial', 'id_prospecto', 'id')->where('estado','<',5);
	}

	public function sinllamar() {
		return $this::where('estado', '=', '0')->get();
	}

	public function prospectoEstado() {
		return $this->hasOne('App\Models\ProspectoEstado', 'id', 'estado');
	}

	public function caracteristicas(){
		return $this->hasMany('App\Models\Prospectos\ProspectoCaracteristica', 'id_prospecto', 'id');
	}

	public function agencia() {
		return $this->hasOne('App\Models\SP_Extras\Agencia', 'id', 'id_agencia');
	}

	public function fuente() {
		return $this->hasOne('App\Models\Prospectos\Fuentes', 'id', 'fuente');
	}

	public $appends = [
		'ultimo',
		'ultimo_no_proceso',
	];

	public function getUltimoAttribute(){
		$ultimo = ProspectoHistorial::with('individuo')
			->where('id_prospecto',$this->id)
			->orderBy('created_at','desc')
			->first();
    return $ultimo;  
  }
  public function getUltimoNoProcesoAttribute(){
		$ultimo = ProspectoHistorial::with('individuo')
			->where('id_prospecto',$this->id)
			->where('estado','<',5)
			->orderBy('created_at','desc')
			->first();
    return $ultimo;  
  } 
}