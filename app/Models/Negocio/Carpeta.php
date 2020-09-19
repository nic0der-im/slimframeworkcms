<?php

namespace App\Models\Negocio;
use Illuminate\Database\Eloquent\Model;

class Carpeta extends Model
{
	protected $table = 'carpetas';

	protected $fillable = [
		'id_usuario',
		'estado',
		'id_cliente',
		'id_vehiculo',
		'id_datero',
		'id_cuentacorriente',
		'aprobado',
		'cerrado',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function cliente() {
		return $this->hasOne('App\Models\Negocio\Cliente', 'id', 'id_cliente');
	}

	public function clientes() {
		return $this->hasMany('App\Models\Negocio\CarpetaCliente', 'id_carpeta', 'id');
	}

	public function vehiculo() {
		return $this->hasOne('App\Models\Vehiculos\Vehiculo', 'id', 'id_vehiculo');
	}

	public function vehiculos() {
		return $this->hasMany('App\Models\Negocio\CarpetaVehiculo', 'id_carpeta', 'id');
	}

	public function datero() {
		return $this->hasOne('App\Models\Negocio\Datero', 'id', 'id_datero');
	}

	public function dateros() {
		return $this->hasMany('App\Models\Negocio\Datero', 'id_carpeta', 'id');
	}

	public function cuenta() {
		return $this->hasOne('App\Models\Caja\Cuenta', 'id', 'id_cuentacorriente');
	}

	public function cuentas() {
		return $this->hasMany('App\Models\Caja\Cuenta', 'id_carpeta', 'id');
	}

}