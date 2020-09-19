<?php

namespace App\Models\SP_Extras;
use Illuminate\Database\Eloquent\Model;

class Prospecto extends Model
{
	protected $table = 'sp_prospectos';
	protected $fillable = [
		'id_usuario_vendedor',
		'nombre',
		'apellido',
		'telefono',
		'localidad',
		'observaciones',
		'estado',
		'fecha_hora_llamado',
	];
	
	public function getVendedor() {
		return $this->hasOne('App\Models\SP_Extras\Vendedor', 'id_usuario', 'id_usuario_vendedor');
	}
}