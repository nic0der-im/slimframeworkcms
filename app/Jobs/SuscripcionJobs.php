<?php

namespace App\Jobs;

use App\Jobs\Jobs;
use App\Funcionalidades\MailChimp;
use App\Funcionalidades\OneSignal;

use App\Models\Suscripcion;
use App\Models\SuscripcionLista;
use App\Models\UserTerminal;

use App\Models\Publicacion;

use Illuminate\Database\Capsule\Manager as DB;
use App\Auth\auth;

class SuscripcionJobs extends Jobs {

	public function call_enviar(){
		$logger = $this->logger;
		return $this->scheduler->call(function() use ($logger){
			//////////////////////////      INICIO DEL JOBS
			$listaSuscriptores = '4cad5cea31';
			$listaRegistros = '136d2c9dbd';
			$suscriptos = SuscripcionLista::where('estado',1)->whereIn('mc_listId',[$listaSuscriptores,$listaRegistros])->pluck('id_suscripcion')->toArray();
			$nuevos = Suscripcion::selectRaw(" *,if(origen like '%usuario%',1,0) as tipo ")
				->where('estado',1)
				->whereNotIn('id',$suscriptos)
				->where(function($q){
					$q->where('origen', 'like', '%usuario%')
					->orWhere('origen', 'like', '%suscripcion%');
				})
				->get();
			$api = MailChimp::api();
			$cantidadFinal = 0;
			$logger->info('SuscripcionJobs LIST cantidad a suscribir '.$nuevos->count());
			foreach ($nuevos as $item) {
				$suscribir = false;
				$listId = '';
				if (strpos($item->origen, 'usuario') !== false) {
					$logger->info('SuscripcionJobs LIST Para enviar a Nuevos Registros.');
				  $listId = $listaRegistros; // Nuevos Registros
					$suscripto = SuscripcionLista::where('estado',1)->where('mc_listId',$listId)->where('id_suscripcion',$item->id)->first();
					if($suscripto){
						$suscribir = false;
					} else {
						$suscribir = true;
					}
				} else if (strpos($item->origen, 'suscripcion') !== false) {
					$logger->info('SuscripcionJobs LIST Para enviar a Nuevos Suscriptos.');
					$listId = $listaSuscriptores; // Nuevos Suscriptores
					$suscripto = SuscripcionLista::where('estado',1)->where('mc_listId',$listId)->where('id_suscripcion',$item->id)->first();
					if($suscripto){
						$suscribir = false;
					} else {
						$suscribir = true;
					}
				}
				if( $suscribir ) {
					try{
						$res = $api->post('lists/'.$listId.'/members',[
							'json'=> [
									'email_address' => $item->email,
									'status' => 'subscribed',
								]
						]);
						$json = json_decode($res->getBody(), true);
						$logger->info('SuscripcionJobs LIST '.$item->id.'. ',$json);
						$memberId = $json['id'];
						SuscripcionLista::create([
							'id_suscripcion' => $item->id,
							'mc_memberId' => $memberId,
							'mc_listId' => $listId,
						]);
						$cantidadFinal++;
					} catch (GuzzleHttp\Exception\TransferException $e){
						SuscripcionLista::create([
							'id_suscripcion' => $item->id,
							'mc_listId' => $listId,
							'estado' => 0,
						]);
						$logger->error('SuscripcionJobs LIST '.$item->id.'. '.$e->getMessage());
					} catch (\Exception $e){
						SuscripcionLista::create([
							'id_suscripcion' => $item->id,
							'mc_listId' => $listId,
							'estado' => 0,
						]);
						$logger->error('SuscripcionJobs LIST '.$item->id.'. '.$e->getMessage());
					}
				}
			}

			return $cantidadFinal;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}

	public function call_todos(){
		$logger = $this->logger;
		return $this->scheduler->call(function() use ($logger){
			//////////////////////////      INICIO DEL JOBS
			$listaTodos = '49fa9673f2';
			$suscriptos = SuscripcionLista::where('mc_listId',$listaTodos)->pluck('id_suscripcion')->toArray();
			$nuevos = Suscripcion::where('estado',1)->whereNotIn('id',$suscriptos)->get();
			$logger->info('SuscripcionJobs TODOS cantidad '.$nuevos->count());
			$api = MailChimp::api();
			$listId = $listaTodos;
			foreach ($nuevos as $item) {
				try{
					$res = $api->post('lists/'.$listId.'/members',[
						'json'=> [
								'email_address' => $item->email,
								'status' => 'subscribed',
							]
					]);
					$json = json_decode($res->getBody(), true);
					$logger->info('SuscripcionJobs TODOS '.$item->id.'. ',$json);
					$memberId = $json['id'];
					SuscripcionLista::create([
						'id_suscripcion' => $item->id,
						'mc_memberId' => $memberId,
						'mc_listId' => $listId,
					]);
				} catch (GuzzleHttp\Exception\TransferException $e){
					SuscripcionLista::create([
						'id_suscripcion' => $item->id,
						'mc_listId' => $listId,
						'estado' => 0,
					]);
					$logger->error('SuscripcionJobs TODOS '.$item->id.'. '.$e->getMessage());
				} catch (\Exception $e){
					SuscripcionLista::create([
						'id_suscripcion' => $item->id,
						'mc_listId' => $listId,
						'estado' => 0,
					]);
					$logger->error('SuscripcionJobs TODOS '.$item->id.'. '.$e->getMessage());
				}
			}
			return true;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}

	public function call_notificar_nuevos(){
		$logger = $this->logger;
		$router = $this->router;
		return $this->scheduler->call(function() use ($logger,$router){
			//////////////////////////      INICIO DEL JOBS
			$publicaciones = Publicacion::where('mostrar', 1)
				->whereRaw('created_at >= curdate() - INTERVAL DAYOFWEEK(curdate())+6 DAY')
				->whereRaw('created_at < curdate() - INTERVAL DAYOFWEEK(curdate())-1 DAY')
				->take(3)
				->get();
			$logger->info('SuscripcionJobs NOTIFICAR NUEVOS count: '.count($publicaciones));
			$chrome_big_picture = "";
			$api = OneSignal::api();
			if(count($publicaciones)>1){
				$imagen = '';
				$message = 'Llegaron: ';
				foreach ($publicaciones as $publicacion) {
					$message = $message.$publicacion->titulo.', ';
					$imagen = $publicacion->vehiculo->getFotos[0]->archivo;
				}
				$message = rtrim($message,", ");
				$message = $message.'... Y encuentras mas de lo que buscas en Ciro Automotores.';
				$title = 'Mira los ultimos 🚘 Usados ';
				$url = 'https://ciroautomotores.com.ar';
				$chrome_big_picture = $url.'/img'.$imagen.'?p=chrome_big_picture';
			} else {
				$rankSemana = PublicacionVista::select('publicacion_vista.id_publicacion',DB::raw('count(distinct ip) as rank'))
					->whereRaw('publicacion_vista.created_at >= curdate() - INTERVAL DAYOFWEEK(curdate())+6 DAY')
					->whereRaw('publicacion_vista.created_at < curdate() - INTERVAL DAYOFWEEK(curdate())-1 DAY')
					->join('publicaciones','publicacion_vista.id_publicacion','=','publicaciones.id')
					->where('publicaciones.mostrar', 1)
					->groupBy('id_publicacion')
					->orderBy('rank','desc')
					->limit(3)
					->get();
				$field = '';
				$columna = [];
				foreach ($rankSemana as $value) {
					$field = $field .','.$value->id_publicacion;
					array_push($columna,$value->id_publicacion);
				}
				$publicaciones = Publicacion::whereIn('id',$columna)
					->where('mostrar', 1)
					->orderByRaw('FIELD(id'.$field.')')
					->get();
				$imagen = '';
				$message = 'Los mas vistos la semana pasada: ';
				foreach ($publicaciones as $publicacion) {
					$message = $message.$publicacion->titulo.', ';
					$imagen = $publicacion->vehiculo->getFotos[0]->archivo;
				}
				$message = rtrim($message,", ");
				$message = $message.'... Y encuentras mas de lo que buscas en Ciro Automotores.';
				$title = 'Mira el Rank Semanal 🚗 ';
				$url = 'https://ciroautomotores.com.ar';
				$chrome_big_picture = $url.'/img'.$imagen.'?p=chrome_big_picture';
			}

			try{
				$res = $api->post('notifications',[
					'json'=> [
						"app_id" => "78034361-ab14-4018-a430-6be0744c770a",
						//"include_player_ids"=> ["f2a6cba8-7e5c-403a-8d15-9acc2165b268"],
						"filters" => [
							[
								"field" => "tag",
								"key" => "sector",
								"relation" => "=",
								"value" => "publico",
							],
							[
								"field" => "tag",
								"key" => "nivel",
								"relation" => "=",
								"value" => "1",
							],
						],

						'headings' => ['en'=>$title,'es'=>$title],
						"data"=> ["modulo"=> "usados","estado"=> "nuevo"],
						'url'=> $url,
						"contents"=> ["en"=> $message, 'es'=>$message],
						'chrome_big_picture' => $chrome_big_picture,
						'big_picture' => $chrome_big_picture,
						]
				]);
				$json = json_decode($res->getBody(), true);
				$logger->info('SuscripcionJobs NOTIFICAR OS id: '.$json['id'].' enviados: '.$json['recipients'].' mensaje: '.$message);
			} catch (\Exception $e){
				$logger->error('SuscripcionJobs NOTIFICAR OS '.$e->getMessage());
			}

			return $chrome_big_picture;
			/////////////////////////				FINALIZACION DEL JOBS
		});
	}
}
