<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;

class Usado extends Model
{
	protected $table = 'usados';
	protected $fillable = [
		'dominio',
		'id_vehiculo',
		'kilometraje',
		'observaciones',
		'exowner',
		'color',
		'precio_revista',
		'precio_toma',
		'precio_reparacion_estimado',
		'email',
		'id_tipo_documento',
		'documento',
		'telefono',
		'celular',
		'domicilio',
		'numero',
		'piso',
		'dpto',
		'id_provincia',
		'localidad',
		'cp',
		'domicilio_observaciones',
		'id_proveedor',
		'fecha_nacimiento',
		'sexo',
		'id_cliente',
	];

	public $incrementing = false;

	protected $dates = [
    'fecha_nacimiento'
	];


	protected $appends = [
		'format_fecha_nacimiento',
		'edad',
	];

	public function getFormatFechaNacimientoAttribute() {
		if($this->fecha_nacimiento != null)
		{
    		return $this->fecha_nacimiento->format('d/m/Y');
		}
		return false;
	}

	public function getEdadAttribute() {
		if($this->fecha_nacimiento != null)
		{
			$today = new \DateTime();
			$age = $today->diff($this->fecha_nacimiento);
	    	return $age->format('%y');
	   	}
	   	return false;
	}
	
}