<?php

namespace App\Controllers\Campains\Criptomonedas;

use App\Controllers\Controller;
use Slim\Views\Twig as View;

use App\Models\Consulta;
use App\Models\Suscripcion;
use App\Models\User;

use App\Models\Vehiculos\Marca;

use App\Models\Paginas;
use App\Models\PaginasBloques;
use App\Models\PaginasFotos;

use App\Models\Redireccion;

use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;
use App\Auth\auth;

class CriptomonedasController extends Controller 
{	
	public function getIndex($request, $response, array $args)
	{
		$paginaMarcas = Marca::with('paginas')->whereHas('paginas',function($query){
			$query->where('mostrar',1);
		})->get();


		return $this->container->view->render($response, 'guest_views/campains/criptomonedas/index.twig', [			
			'paginas'=>Paginas::where('mostrar',1)->orderBy('id','DESC')->get(),
			'paginasMarca'=> $paginaMarcas,
		]);
	}

	public function postConsulta($request, $response) {

		if($request->isXhr()){

			$consulta = Consulta::create([
			'id_usuario'=> -1,
			'nombre'=>$request->getParam('pm_nombre'),
			'apellido'=>$request->getParam('pm_apellido'),
			'texto'=>'Estoy interesado/a en este #PrecioMundial: ' . $request->getParam('pm_vehiculo'),
			'telefono'=>$request->getParam('pm_telefono'),
			'url'=>'precios-mundiales',
			'email'=>'',
		]);

			return $response->withJson([
				'success'=>true,
			]);
		}

	}

}