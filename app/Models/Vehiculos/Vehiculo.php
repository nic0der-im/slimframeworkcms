<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;
use App\Models\Publicacion;

use App\Models\Vehiculos\Creditos;
use App\Models\Vehiculos\CreditosFijos;

class Vehiculo extends Model
{
	protected $table = 'vehiculos';
	protected $fillable = [
		'id_marca',
		'modelo',
		'id_localidad',
		'id_ubicacion',
		'year',
		'motor',
		'id_tipo_motor',
		'id_transmision',
		'cantidad_puertas',
		'entrega_minima',
		'precio_venta',
		'precio_lista',
		'id_estado',
		'estado_vehiculo',
		'eliminado',
		'id_usuario',
		'id_precios_vehiculos',
		'precio_usd'
	];

	public function getPublicacion() {
		return $this->hasOne('App\Models\Publicacion', 'id_vehiculo');
	}

	public function getUsado()
	{
		return $this->hasOne('App\Models\Vehiculos\Usado', 'id_vehiculo');
	}

	public function getMarca()
	{
		return $this->hasOne('App\Models\Vehiculos\Marca', 'id', 'id_marca');
	}

	public function getLocalidad()
	{
		return $this->hasOne('App\Models\Vehiculos\Localidad', 'id', 'id_localidad');
	}

	public function getTipoMotor()
	{
		return $this->hasOne('App\Models\Vehiculos\TiposMotor', 'id', 'id_tipo_motor');
	}

	public function getTransmision()
	{
		return $this->hasOne('App\Models\Vehiculos\Transmision', 'id', 'id_transmision');
	}

	public function getTiposEstado()
	{
		return $this->hasOne('App\Models\Vehiculos\TiposEstado', 'id', 'id_estado');
	}

	public function getEstadoLista()
	{
		return $this->hasOne('App\Models\Vehiculos\EstadosVeh', 'id', 'estado_vehiculo');
	}

	public function getFotos()
	{
		return $this->hasMany('App\Models\Vehiculos\ImagenesVeh', 'id_vehiculo', 'id')->orderBy('orden','asc');
	}

	public function getUbicacion()
	{
		return $this->hasOne('App\Models\SP_Extras\Ubicacion', 'id', 'id_ubicacion');
	}

	public function tercero()
	{
		return $this->hasOne('App\Models\Vehiculos\UsadoTercero', 'id_vehiculo', 'id');
	}

	public function formularios(){
		return $this->hasMany('App\Models\Vehiculos\VehiculoFormulario', 'id_vehiculo', 'id')->orderBy('fecha','desc');
	}
	

	public function scopeVehiculoPublicado($query) {

		$publicacion = Publicacion::where('id_vehiculo', $this->id)
			->where('mostrar', 1)
			->get();

		if(count($publicacion) >= 1) {
			return $query->join('publicaciones', 'vehiculos.id', '=', 'publicaciones.id_vehiculo');
		}

	}

	public function scopePublicado($query)
	{
		$publicacion = Publicacion::where('id_vehiculo', $this->id)->get();

		if(count($publicacion) >= 1) {
			return true;
		}
		return false;
	}

	public function EstaPublicado() {
		$publicacion = Publicacion::where('id_vehiculo', $this->id)
			->where('mostrar', 1)
			->get();
		if(count($publicacion) >= 1) {
			return true;
		}
		return false;
	}

	protected $appends = ['publicado','calculo_entrega_minima','calculo_credito'];

	public function getPublicadoAttribute(){
    $publicacion = Publicacion::where('id_vehiculo', $this->id)
			->where('mostrar', 1)
			->get();
		if(count($publicacion) >= 1) {
			return true;
		}
		return false;
  }

	public function getCalculoEntregaMinimaAttribute(){
		if($this->precio_venta and $this->year) {
			$financiacion = Creditos::where('year', $this->year)->first();
			if(!$financiacion){
				return 0;
			}
			$credito = $this->precio_venta * ( $financiacion->porcentaje / 100 );

			$fijos = CreditosFijos::orderBy('id', 'DESC')->first();
			$entrega_minima = ( $this->precio_venta - $credito) + ( $this->precio_venta * ($fijos->transferencia / 100)) + $fijos->otorgamiento + ($credito * ($fijos->prenda / 100));
			return $entrega_minima;
		} else{
			return 0;
		}
  }
	public function getCalculoCreditoAttribute(){
		if($this->precio_venta) {
			$financiacion = Creditos::where('year', $this->year)->first();
			if(!$financiacion){
				return 0;
			}
			$credito = $this->precio_venta * ( $financiacion->porcentaje / 100 );
			return $credito;
		} else {
			return 0;
		}
  }

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function historial()
	{
		return $this->hasMany('App\Models\Vehiculos\VehiculoHistorial', 'id_vehiculo', 'id');
	}
}
