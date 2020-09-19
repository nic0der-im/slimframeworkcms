<?php

namespace App\Controllers\Home;
use App\Controllers\Controller;

use Slim\Views\Twig as View;
use App\Funcionalidades\OneSignal;
use App\Models\UserTerminal;

use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;

use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;
use App\Auth\auth;

class SuscripcionController extends Controller {

  public function etiquetar($request,$response,$args){
    $player_id = $request->getParamQuery('player_id',null);
    if($player_id==null){
      return $response->withJson([]);
    }

    $api = OneSignal::api();
    try{
      $res = $api->put('players/'.$player_id,[
        'json'=> [
          "app_id" => "78034361-ab14-4018-a430-6be0744c770a",
          'tags'=>['sector'=>'publico']
          ]
      ]);
      $json = json_decode($res->getBody(), true);
      return $response->withJson($json);
    } catch (\Exception $e){
      return $response->withJson(['success'=>false]);
    }
  }

  public function asociarUsuario($request,$response){
    $id_usuario = 0;
    if(auth::check()){
      $id_usuario = auth::individuo()->id_usuario;
    }
		$latitud = $request->getQueryParam('latitud',0);
		$longitud = $request->getQueryParam('longitud',0);
		$os_user_id = $request->getQueryParam('user_id',null);
		if($os_user_id!=null){
			$terminal = UserTerminal::where('os_user_id',$os_user_id)->first();
			if($terminal){
        $terminal->id_usuario = $id_usuario;
				$terminal->estado = 1;
				$terminal->save();
			} else {
				$terminal = UserTerminal::create([
          'id_usuario' => $id_usuario,
					'latitud' => $latitud,
					'longitud' => $longitud,
					'os_user_id' => $os_user_id,
				]);
			}
		}
		return $response->withJson(['success'=>true]);
	}

  public function desasociarUsuario($request,$response){
		$os_user_id = $request->getQueryParam('user_id',null);
		if($os_user_id!=null){
			$terminal = UserTerminal::where('os_user_id',$os_user_id)->first();
			$terminal->estado = 0;
			$terminal->save();
		}
		return $response->withJson(['success'=>true]);
	}

  public function terminal($request,$response){
    $user_id = $request->getParamQuery('user_id',null);
    if($user_id!=null){
  		$cookie = FigRequestCookies::get($request, 'osud',"")->getValue();
  		if(empty($cookie)){
        $response = FigResponseCookies::set($response, SetCookie::create('osud')
  				->withValue($user_id)
  				->withExpires(strtotime("+1 year"))
  			);
  		}
    }

    return $response->withJson(['success'=>true]);
  }

}
