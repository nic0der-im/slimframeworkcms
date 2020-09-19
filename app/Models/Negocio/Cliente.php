<?php

namespace App\Models\Negocio;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
	protected $table = 'clientes';

	protected $fillable = [
		'nombre',
		'apellido',
		'id_tipo_documento',
		'id_tipo_responsable',
		'documento',
		'fecha_nacimiento',
		'telefono',
		'celular',
		'email',
		'domicilio',
		'numero',
		'piso',
		'domicilio_observaciones',
		'localidad',
		'cp',
		'id_provincia',
		'observaciones',
		'estado',
		'id_usuario',
		'id_agencia',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function tipoDocumento() {
		return $this->hasOne('App\Models\Transacciones\TipoDocumento', 'id', 'id_tipo_documento');
	}

	public function tipoResponsable() {
		return $this->hasOne('App\Models\Transacciones\TipoResponsable', 'id', 'id_tipo_responsable');
	}

	public function provincia() {
		return $this->hasOne('App\Models\Extras\Provincia', 'id', 'id_provincia');
	}

	public function agencia() {
		return $this->hasOne('App\Models\SP_Extras\Agencia', 'id', 'id_agencia');
	}

	protected $dates = [
    'fecha_nacimiento'
	];


	protected $appends = [
		'format_fecha_nacimiento',
		'edad',
	];

	public function getFormatFechaNacimientoAttribute() {
		if($this->fecha_nacimiento == NULL)
		{
			return "invalid";
		}
    	return $this->fecha_nacimiento->format('d/m/Y');
	}

	public function getEdadAttribute() {
		if($this->fecha_nacimiento == NULL)
		{
			return "invalid";
		}

		$today = new \DateTime();
		$age = $today->diff($this->fecha_nacimiento);
    	return $age->format('%y');
	}

	
}