<?php

namespace App\Controllers\Correo;

use \Datetime;

use App\Models\User;
use App\Models\Individuo;
use App\Models\Empleado;
use App\Models\Suscripcion;
use App\Models\SuscripcionCampaña;
use App\Models\Consulta;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use PhpImap\Mailbox;

class SuscripcionController extends Controller {


	public function post($request, $response, $args) {
		$validation = $this->validator->validate($request, [
			'suscripcion_email'=>v::notEmpty(),
		]);

		if($validation->failed()) {
			$this->flash->addMessage('error', 'Hemos encontrado algunos errores al registrar como Suscriptor.');
			return $response->withRedirect($this->router->pathFor('home'));
		}

		$email = strtolower($request->getParam('suscripcion_email'));

		$suscripcion = Suscripcion::where('estado',1)
		->where('email','like', $email)->get();
    if($suscripcion->count()>0){
    	$this->flash->addMessage('error', 'El correo '.$email.' ya se encuentra suscripto.');
			return $response->withRedirect($this->router->pathFor('home'));
    }
    $factory = new \RandomLib\Factory;
		$generator = $factory->getMediumStrengthGenerator();

		Suscripcion::Create([
			'email'=> $email,
			'origen' => 'https://ciroautomotores.com.ar/suscripcion',
			'token' => $generator->generateString(128,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
		]);

		$this->flash->addMessage('info', 'Hemos agendado su correo. Pronto empezara a recibir nuestras novedades.');
		return $response->withRedirect($this->router->pathFor('home'));
	}

	public function index($request,$response){
		try{
			$client = new \GuzzleHttp\Client();
			$res = $client->request('GET', 'https://api.getresponse.com/v3/campaigns', [
			    'headers' => [
				      'X-Auth-Token' => 'api-key 95499a2dfedc606471c8ff2b6d752fda',
				    ]
			]);
			$campañas = json_decode($res->getBody()->getContents());
			return $this->container->view->render($response, 'admin_views/clientes/tableroSuscripcion.twig', [
				'campañas' => $campañas,
			]);
		} catch (\Exception $e) {
			$this->flash->addMessage('warning', 'A ocurrido un error con getResponse. Vuelva a intentar mas tarde. Si vuelve a ocurrir comuniquese con IT');
			return $response->withRedirect($this->router->pathFor('suscripcion.index'));
		}
		
	}

	public function getAll($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Suscripcion::with('campañas','listas')
			->where('estado',1);
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('origen','like','%'.$value.'%');
					});
				}
			}
		}

		$recordsFiltered = $registros->count();
		$registros = $registros->orderBy($orderColumna,$orderTipo);
		if($length>0){
			$registros = $registros->limit($length);
			$registros = $registros->offset($start)->get();
		} else {
			$registros = $registros->get();
		}

		return $response->withStatus(200)->withJson([
			'draw' => intval($draw),
			'recordsTotal' => intval($recordsTotal),
			'recordsFiltered' => intval($recordsFiltered),
			'data' => $registros,
		]); 
	}

	public function getConsultas($request,$response){
		$start = $request->getParam('start');
		$length = $request->getParam('length');
		$order = $request->getParam('order');
		$search = $request->getParam('search');
		$draw = $request->getParam('draw');
		$columns = $request->getParam('columns');

		$orderColumna = $columns[$order[0]['column']]['name'];
		$orderTipo = $order[0]['dir'];

		$values = explode(" ", $search['value']);

		$registros = Consulta::where('estado',1)->where('email','!=','')->whereNotNull('email');
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhere('email','like','%'.$value.'%')
							->orWhere('url','like','%'.$value.'%');
					});
				}
			}
		}

		$recordsFiltered = $registros->count();
		$registros = $registros->orderBy($orderColumna,$orderTipo);
		if($length>0){
			$registros = $registros->limit($length);
			$registros = $registros->offset($start)->get();
		} else {
			$registros = $registros->get();
		}

		return $response->withStatus(200)->withJson([
			'draw' => intval($draw),
			'recordsTotal' => intval($recordsTotal),
			'recordsFiltered' => intval($recordsFiltered),
			'data' => $registros,
		]); 
	}

	public function eliminar($request,$response,$args){
		$id_suscripcion = $args['id'];
		$suscripcion = Suscripcion::where('id',$id_suscripcion)->first();
		Suscripcion::where('id',$id_suscripcion)->update([
			'estado' => 0,
		]);

		$api = '';
		if($suscripcion->gr_contactId !== null and !empty($suscripcion->gr_contactId)){
			try{
				$client = new \GuzzleHttp\Client();
				$res = $client->delete('https://api.getresponse.com/v3/contacts/'.$suscripcion->gr_contactId);
				$api = ' Tambien desde el GetResponse';
			} catch (GuzzleHttp\Exception\TransferException $e) {

			}
		}

		return $response->withStatus(200)->withJson([
			'message' => 'El correo de '.$suscripcion->email.' fue eliminado con exito. '.$api,
		]); 
	}

	public function consultarCampañas($request,$response,$args){
		$client = new \GuzzleHttp\Client();
		$res = $client->request('GET', 'https://api.getresponse.com/v3/campaigns', [
		    'headers' => [
			      'X-Auth-Token' => 'api-key 95499a2dfedc606471c8ff2b6d752fda',
			    ]
		]);
		$encode = json_decode($res->getBody()->getContents());
		return $response->withStatus(200)->withJson($encode);
	}

	public function enviarCampaña($request,$response){
		
		$campaña = $request->getParam('campaña');
		$name = $request->getParam('nombre');
		$registros = Suscripcion::where('estado',1)->get();
		$count = 0;
		$countNuevos = 0;
		$countEstan = 0;
		$client = new \GuzzleHttp\Client();
		foreach ($registros as $item) {
			$isCampaña = SuscripcionCampaña::where('estado',1)->where('id_suscripcion',$item->id)->where('gr_campaignId','like','%'.$campaña.'%')->get();
			if($isCampaña->count()==0){
				$gr_estado = 0;
				try{
					$res = $client->post('https://api.getresponse.com/v3/contacts',
						[
							'headers' => [
					      'X-Auth-Token' => 'api-key 95499a2dfedc606471c8ff2b6d752fda',
					    ],
							'json'=> [
								'email' => $item->email,
								'campaign' => [
									'campaignId' => $campaña,
								],
							]
						]
					);
					$countNuevos = $countNuevos + 1;
				} catch (GuzzleHttp\Exception\TransferException $e) {
					$res = $e->getResponse();
					$json = json_decode($res->getBody(), true);
					if($json['code']==1008){
						if(strpos($json['message'],'Contact already added')!==false){
							$gr_estado = 1;
						}
					}
					$countEstan = $countEstan + 1;
				} catch (\Exception $e){
					$countEstan = $countEstan + 1;
				}
				SuscripcionCampaña::create([
					'id_suscripcion' => $item->id,
					'gr_campaignId' => $campaña,
					'gr_name' => $name,
					'gr_estado' => $gr_estado,
				]);
				$count = $count + 1;
			}
		}
		
		$this->flash->addMessage('info', 'Los contactos ( Total '.$count.') fueron enviados correctamente a la campaña '.$name.'. De los cuales '.$countNuevos.' son nuevos y '.$countEstan.' estan en confirmar.');
		return $response->withRedirect($this->router->pathFor('suscripcion.index'));
	}
}