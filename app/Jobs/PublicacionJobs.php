<?php

namespace App\Jobs;

use App\Jobs\Jobs;

use App\Models\User;
use App\Models\Publicacion;
use App\Models\PublicacionRenobacion;
use App\Models\Vehiculos\Vehiculo;
use App\Models\Vehiculos\UsadoTercero;

use Illuminate\Database\Capsule\Manager as DB;
use App\Auth\auth;

class PublicacionJobs extends Jobs {

	public function call_controlar_renobacion(){
		$logger = $this->logger;
		return $this->scheduler->call(function() use ($logger){
			//////////////////////////      INICIO DEL JOBS
			$ahora = date('Y-m-d');
			$renobaciones = PublicacionRenobacion::leftJoin('publicaciones','publicacion_renobacion.id_publicacion','=','publicaciones.id')
				->where('publicaciones.mostrar',0)
				->where('estado',1)
				->where('fecha','<=',$ahora)
				->where('fecha_vto','>=',$ahora)
				->get();
			$logger->info('Cantidad renobaciones sin publicar '.$renobaciones->count());
			foreach ($renobaciones as $item) {
				Publicacion::where('id',$item->id_publicacion)->update([
					'mostrar' => 1 ,
				]);
				$logger->info('Publicacion '.$item->id_publicacion.' agregada por Renobacion '.$item->id);
			}
			$renobaciones = PublicacionRenobacion::where('estado',1)->where('fecha_vto','<',$ahora)->get();
			$logger->info('Cantidad renobaciones vencidas '.$renobaciones->count());
			foreach ($renobaciones as $item) {
				Publicacion::where('id',$item->id_publicacion)->update([
					'mostrar' => 0 ,
				]);
				PublicacionRenobacion::where('id',$item->id)->update([
					'estado' => 1 ,
				]);
				$logger->info('Publicacion '.$item->id_publicacion.' eliminada por Renobacion '.$item->id);
			}
			return true;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}

}