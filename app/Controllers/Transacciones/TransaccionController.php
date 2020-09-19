<?php

namespace App\Controllers\Transacciones;


use App\Models\User;
use App\Models\Individuo;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

use App\Models\Transacciones\Diaria;
use App\Models\Transacciones\Obligacion;
use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TiposTransaccion;
use App\Models\Transacciones\SucursalEmpleado;

use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;

class TransaccionController extends Controller {


	public function index($request,$response){
		$sucursalEmpleado = SucursalEmpleado::where(['id_usuario'=>auth::individuo()->id_usuario,'estado'=>1])->get(['id_sucursal']);
		$sucursales = Sucursal::whereIn('id',$sucursalEmpleado)->get();
		$id_sucursal = auth::sucursal()->id;
  	$tiposTransaccion = DB::table('tra_tipos_transaccion  as t1')->
	    select(DB::raw('concat(t2.nombre_tipo,"/",t1.nombre_tipo) as nombre, t1.id as id, t1.padre as padre'))->
	    join('tra_tipos_transaccion AS t2', 't2.id', '=', 't1.padre')->
	    where('t1.estado_tipo', 1)->
	    where(function($query) use ($id_sucursal){
					$query->where('t1.sucursales',$id_sucursal)
						->orWhere('t1.sucursales','like','%'.$id_sucursal.'%')
						->orWhere('t1.sucursales',-1);
				});
  	$categorias = TiposTransaccion::select('nombre_tipo as nombre','id','padre')->where('padre',0);
  	$tiposTransaccion = $tiposTransaccion->union($categorias)->orderBy('nombre','asc')->get();

		return $this->container->view->render($response, 'admin_views/caja/parametros.twig',[
			'sucursal'=>auth::sucursal(),
			'tiposTransaccion' => $tiposTransaccion,
			'sucursales'=>$sucursales,
		]);
	}

	public function sucursal($request, $response){
		$return_to = $request->getQueryParam('return_to',null);
		$sucursal = auth::sucursal($request->getParam('empleado_sucursales'));
		$this->flash->addMessage('info', "La sucursal fue cambiada a ".$sucursal->sucursal.'('.$sucursal->descripcion.')');
		if(!is_null($return_to)){
			return $response->withRedirect('/'.$return_to);
		}
		return $response->withStatus(302)->withHeader('Location', $url);
	}


}
