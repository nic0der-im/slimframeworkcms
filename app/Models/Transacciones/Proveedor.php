<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
	protected $table = 'tra_proveedores';

	protected $fillable = [
		'razon_social',
		'id_tipo_proveedor',
		'id_tipo_documento',
		'id_tipo_responsable',
		'documento',
		'telefono',
		'email',
		'domicilio',
		'numero',
		'piso',
		'domicilio_observaciones',
		'localidad',
		'id_provincia',
		'observaciones',
		'estado',
		'id_usuario',
		'id_sucursal',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function tipoProveedor() {
		return $this->hasOne('App\Models\Transacciones\TipoProveedor', 'id', 'id_tipo_proveedor');
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
}