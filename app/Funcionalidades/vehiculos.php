<?php

namespace App\Funcionalidades;
use App\Models\Vehiculos\Marca;
use App\Models\Vehiculos\Transmision;
use App\Models\Vehiculos\TiposMotor;
use App\Models\Vehiculos\EstadosVeh;
use App\Models\Vehiculos\ImagenesVeh;
use App\Models\Extras\Provincia;
use App\Models\Extras\Ubicacion;
use App\Models\Extras\Localidad;

use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\Usado;

class Vehiculos
{
	public function listar_marcas(){
		return Marca::orderBy('nombre', 'asc')->get();
	}

	public function listar_transmisiones(){
		return Transmision::orderBy('nombre', 'desc')->get();
	}

	public function listar_tiposmotor(){
		return TiposMotor::orderBy('nombre', 'desc')->get();
	}

	public function listar_ubicaciones(){
		return Ubicacion::orderBy('nombre', 'asc')->get();
	}

	public function listar_estados(){
		return EstadosVeh::orderBy('nombre', 'asc')->get();
	}

	public function listar_localidades(){
		$provincias = Provincia::orderBy('nombre', 'asc')->get();
		foreach($provincias as $key=>$value) {
			$provincias[$key]['localidades'] = Localidad::where('id_provincia',$value->id)->get();
		}
		return $provincias;
	}

	public function listar_vehiculos(){
		$real_vehiculos = array();
		$vehiculos = Vehiculo::orderBy('created_at', 'desc')->get();
		foreach($vehiculos as $key=>$vehiculo) {

			$real_vehiculos[$key] = $vehiculo;

			$marca = Marca::find($vehiculo->id_marca)->nombre;
			$tipo_motor = TiposMotor::find($vehiculo->id_tipo_motor)->nombre;
			$transmision = Transmision::find($vehiculo->id_transmision)->nombre;
			$localidad = Localidad::find($vehiculo->id_localidad)->nombre;

			$real_vehiculos[$key]['marca'] = $marca;
			$real_vehiculos[$key]['tipo_motor'] = $tipo_motor;
			$real_vehiculos[$key]['transmision'] = $transmision;
			$real_vehiculos[$key]['localidad'] = $localidad;

			$usado = Usado::where('id_vehiculo', $vehiculo->id)->first();
			if($usado) {
				$real_vehiculos[$key]['usado'] = $usado;
			}

			$imagenes = ImagenesVeh::where('id_vehiculo', $vehiculo->id)->take(15)->get();
			if($imagenes) {
				$real_vehiculos[$key]['images'] = $imagenes;
			}
		}
		return $real_vehiculos;
	}
}
