<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
	protected $table = 'clientes';

	protected $fillable = [
		'nombre',
		'apellido',
		'id_tipo_documento',
		'documento',
		'fecha_nacimiento',
		'telefono',
		'celular',
		'email',
		'domicilio',
		'departamento',
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

	public function provincia() {
		return $this->hasOne('App\Models\Extras\Provincia', 'id', 'id_provincia');
	}

	public function agencia() {
		return $this->hasOne('App\Models\SP_Extras\Agencia', 'id', 'id_agencia');
	}
}