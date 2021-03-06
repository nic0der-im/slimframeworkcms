<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vehiculos\Vehiculo;


class TiposMotor extends Model
{
	protected $table = 'veh_tipos-motor';
	protected $fillable = [
		'nombre',
	];


	public function tienevehiculos() {
		$vehiculos = Vehiculo::where('id_tipo_motor', $this->id)->get();
		if(count($vehiculos) >= 1) {
			$num = 0;
			foreach($vehiculos as $vehiculo) {
				if($vehiculo->EstaPublicado()){
					$num++;
				}
			}
			if($num > 0) { return $num; }
		}
		return false;
	}

	public function tienevehiculosfiltrados($search_keys) {
		$vehiculos = Vehiculo::where('id_tipo_motor', $this->id)->get();
		if(count($vehiculos) >= 1) {
			$num = 0;
			foreach($vehiculos as $vehiculo) {
				if($vehiculo->EstaPublicado()){
					$filter = true;
					foreach($search_keys as $skey=>$sk){
						$ret = $sk->return;
						if($vehiculo->$skey != $sk->$ret) { $filter = false; }
					}
					if($filter) {
						$num++;
					}
				}
			}
			if($num > 0) { return $num; }
		}
		return false;
	}

	public $timestamps = false;
}