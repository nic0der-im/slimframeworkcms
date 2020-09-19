<?php

namespace App\Models\Negocio;
use Illuminate\Database\Eloquent\Model;

class Datero extends Model
{
	protected $table = 'dateros';

	protected $fillable = [
		'id_cliente',
		'id_cliente_conyugue',
		'id_vehiculo',
		'id_marca',
		'observaciones',

		'sexo',
		'apellido',
		'nombre',
		'estado_civil',
		'id_tipo_documento',
		'documento',
		'fecha_nacimiento',
		'nacionalidad',
		'lugar_nacimiento',
		'padre',
		'madre',
		'cargo',
		'vivienda',
		'gasto_mensual',
		'estudios',
		'telefono',
		'celular',
		'email',
		'domicilio',
		'numero',
		'piso',
		'dpto',
		'domicilio_observaciones',
		'id_provincia',
		'localidad',
		'cp',

		'id_actividad_economica',
		'profesion',
		'empresa',
		'sector_economico',
		'ramo',
		'relacion_dependencia',
		'plan_sueldo',
		'posicion',
		'ocupacion',
		'antiguedad',
		'antiguedad_anterior',
		'ingresos',
		'ingresos_otros',

		'laboral_telefono',
		'laboral_celular',
		'laboral_domicilio',
		'laboral_numero',
		'laboral_piso',
		'laboral_dpto',
		'laboral_domicilio_observaciones',
		'laboral_id_provincia',
		'laboral_localidad',
		'laboral_cp',

		'conyugue_sexo',
		'conyugue_apellido',
		'conyugue_nombre',
		'conyugue_cotitular',
		'conyugue_id_tipo_documento',
		'conyugue_documento',
		'conyugue_fecha_nacimiento',
		'conyugue_nacionalidad',
		'conyugue_lugar_nacimiento',
		'conyugue_padre',
		'conyugue_madre',
		'conyugue_cargo',

		'conyugue_id_actividad_economica',
		'conyugue_profesion',
		'conyugue_empresa',
		'conyugue_sector_economico',
		'conyugue_ramo',
		'conyugue_relacion_dependencia',
		'conyugue_plan_sueldo',
		'conyugue_posicion',
		'conyugue_ocupacion',
		'conyugue_antiguedad',
		'conyugue_antiguedad_anterior',
		'conyugue_ingresos',
		'conyugue_ingresos_otros',

		'conyugue_laboral_telefono',
		'conyugue_laboral_celular',
		'conyugue_laboral_domicilio',
		'conyugue_laboral_numero',
		'conyugue_laboral_piso',
		'conyugue_laboral_dpto',
		'conyugue_laboral_domicilio_observaciones',
		'conyugue_laboral_id_provincia',
		'conyugue_laboral_localidad',
		'conyugue_laboral_cp',

		'vehiculo_calidad',
		'vehiculo_gnc',
		'vehiculo_año',
		'vehiculo_uso',
		'vehiculo_prima',
		'vehiculo_modelo',
		'vehiculo_precio',
		'vehiculo_entrega_minima',
		'vehiculo_seguro',
		'vehiculo_seguro_producto',
		'vehiculo_credito',
		'vehiculo_tasa',
		'banco',

		'presupuesto_seña',
		'presupuesto_financiar',
		'presupuesto_vehiculo_valor',
		'presupuesto_porcentaje',
		'presupuesto_plazos',
		'presupuesto_tasa',
		'presupuesto_capital_intereses',
		'presupuesto_iva',
		'presupuesto_cuota',
		'estado',
		'id_usuario',
		'id_carpeta',
		'id_tipo_estado',
	];

	public function cliente() {
		return $this->hasOne('App\Models\Negocio\Cliente', 'id', 'id_cliente');
	}

	public function conyugue() {
		return $this->hasOne('App\Models\Negocio\Cliente', 'id', 'id_cliente_conyugue');
	}

	public function vehiculo() {
		return $this->hasOne('App\Models\Vehiculos\Vehiculo', 'id', 'id_vehiculo');
	}

	public function usado() {
		return $this->hasOne('App\Models\Vehiculos\Usado', 'id_vehiculo', 'id_vehiculo');
	}

	public function marca() {
		return $this->hasOne('App\Models\Vehiculos\Marca', 'id', 'id_marca');
	}

	public function tipoDocumento() {
		return $this->hasOne('App\Models\Transacciones\TipoDocumento', 'id', 'id_tipo_documento');
	}

	public function conyugueTipoDocumento() {
		return $this->hasOne('App\Models\Transacciones\TipoDocumento', 'id', 'conyugue_id_tipo_documento');
	}

	public function provincia() {
		return $this->hasOne('App\Models\Extras\Provincia', 'id', 'id_provincia');
	}

	public function laboralProvincia() {
		return $this->hasOne('App\Models\Extras\Provincia', 'id', 'laboral_id_provincia');
	}

	public function conyugueLaboralProvincia() {
		return $this->hasOne('App\Models\Extras\Provincia', 'id', 'conyugue_laboral_id_provincia');
	}

	public function actividadEconomica() {
		return $this->hasOne('App\Models\Extras\ActividadEconomica', 'id', 'id_actividad_economica');
	}

	public function conyugueActividadEconomica() {
		return $this->hasOne('App\Models\Extras\ActividadEconomica', 'id', 'conyugue_id_actividad_economica');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function historial() {
		return $this->hasMany('App\Models\Negocio\DateroHistorial', 'id_datero', 'id');
	}

	public function tipoEstado() {
		return $this->hasOne('App\Models\Negocio\DateroEstado', 'id', 'id_tipo_estado');
	}

	protected $dates = [
    'fecha_nacimiento',
    'conyugue_fecha_nacimiento',
	];


	protected $appends = [
		'format_fecha_nacimiento',
		'format_conyugue_fecha_nacimiento',
	];

	public function getFormatFechaNacimientoAttribute() {
    return $this->fecha_nacimiento->format('d/m/Y');
	}

	public function getFormatConyugueFechaNacimientoAttribute() {
    return $this->fecha_nacimiento->format('d/m/Y');
	}

}
