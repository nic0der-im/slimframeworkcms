<?php

namespace App\Controllers\Negocio;

use Slim\Views\Twig as View;

use App\Funcionalidades\Correo;
use App\Controllers\Controller;

use PHPJasper\PHPJasper;
use App\Models\Correo\ModuloCorreo;

use App\Models\Negocio\Cliente;
use App\Models\Negocio\Datero;
use App\Models\Negocio\Carpeta;
use App\Models\Negocio\CarpetaCliente;
use App\Models\Negocio\CarpetaVehiculo;
use App\Models\Negocio\DateroEstado;

use App\Models\Vehiculos\TipoFormulario;
use App\Models\Vehiculos\VehiculoFormulario;

use App\Models\Transacciones\Sucursal;
use App\Models\Transacciones\TipoDocumento;
use App\Models\Caja\Cuenta;
use App\Auth\auth;
use Illuminate\Database\Capsule\Manager as DB;
use Respect\Validation\Validator as v;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CarpetaController extends Controller {

	public function tablero($request,$response){
		return $this->container->view->render($response, 'admin_views/negocio/tableroCarpeta.twig');
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

		$registros = Carpeta::with('individuo','cliente.tipoDocumento','vehiculo.getMarca','datero','cuenta')
			->where('estado',1);
		if(auth::empleado()->id_puesto == 2){
			$registros = $registros->where('id_usuario',auth::empleado()->id_usuario);
		}
		$recordsTotal = $registros->count();
		if(count($values)>0){
			foreach ($values as $key => $value) {
				if(strlen($value)>1){
					$registros = $registros->where(function($query) use  ($value) {
						$query->where(DB::raw("DATE_FORMAT(created_at,'%d/%m/%Y')"), 'like', '%'.$value.'%')
							->orWhereIn('id_usuario',function($d) use ($value){
								$d->select('id_usuario')->from('individuos')
									->where('nombre','like','%'.$value.'%')
									->orWhere('apellido','like','%'.$value.'%');
							})
							->orWhere(function($q)use($value){
								$q->where('id_cliente','>',0)
								->whereIn('id_cliente',function($d) use ($value){
									$d->select('id')->from('clientes')
										->where('nombre','like','%'.$value.'%')
										->orWhere('apellido','like','%'.$value.'%');
								});
							})
							->orWhere(function($q)use($value){
								$q->where('id_vehiculo','>',0)
								->whereIn('id_vehiculo',function($d) use ($value){
									$d->select('id')->from('vehiculos')
										->where('modelo','like','%'.$value.'%')
										->orWhere('year','like','%'.$value.'%')
										->orWhereIn('id_marca',function($d) use ($value){
											$d->select('id')->from('veh_listado-marcas')
												->where('nombre','like','%'.$value.'%');
										});
								});
							});

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

	public function post($request,$response){
		$id_usuario = auth::empleado()->id_usuario;
		$id_sucursal = auth::sucursal()->id;
		$tipo = $request->getParam('tipo','U');

		$carpeta = Carpeta::create([
			'id_usuario' => $id_usuario,
		]);
		//02N012000001
		$operacion = str_pad($id_sucursal,2,"0",STR_PAD_LEFT).$tipo.str_pad($id_usuario,3,"0",STR_PAD_LEFT).str_pad($carpeta->id,10,"0",STR_PAD_LEFT);
	 	Carpeta::where('id',$carpeta->id)->update([
			'operacion'=>$operacion,
		]);
		$this->flash->addMessage('info', 'La carpeta fue creado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$carpeta->id]));
	}

	public function desdeDatero($request,$response,$args){
		$id_datero = $args['id_datero'];
		$datero = Datero::where('id',$id_datero)->first();
		if($datero->id_carpeta==0){
			$id_usuario = auth::empleado()->id_usuario;
			$id_sucursal = auth::sucursal()->id;
			if($datero->vehiculo_calidad == "USADO"){
				$tipo = 'U';
			} else {
				$tipo = 'N';
			}

			$id_carpeta = Carpeta::create([
				'id_vehiculo' => $datero->id_vehiculo,
				'id_datero' => $id_datero,
				'id_usuario' => $id_usuario,
			])->id;
			//02N012000001
			$operacion = str_pad($id_sucursal,2,"0",STR_PAD_LEFT).$tipo.str_pad($id_usuario,3,"0",STR_PAD_LEFT).str_pad($id_carpeta,10,"0",STR_PAD_LEFT);
		 	Carpeta::where('id',$id_carpeta)->update([
				'operacion'=>$operacion,
			]);
			CarpetaCliente::create([
				'id_cliente' => $datero->id_cliente,
				'id_carpeta' => $id_carpeta,
				'id_usuario' => $id_usuario,
			]);
			if($id_cliente_conyugue>0){
				CarpetaCliente::create([
					'id_cliente' => $datero->$id_cliente_conyugue,
					'id_carpeta' => $id_carpeta,
					'id_usuario' => $id_usuario,
				]);
			}
			Datero::where('id',$id_datero)->update([
				'id_carpeta'=>$id_carpeta,
			]);
			$this->flash->addMessage('info', 'La carpeta fue creado Exitosamente.');
			return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$id_carpeta]));
		}
		return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$datero->id_carpeta]));
	}

	public function get($request,$response,$args){
		$id_carpeta = $args['id'];

		$dateroEstados = DateroEstado::selectRaw('id as value, nombre as text')
			->where('estado',1)
			->get();

		$carpeta = Carpeta::with([
			'clientes'=>function($query){
				$query->where('estado',1)->groupBy('id_cliente');
			},
			])
			->where('id',$id_carpeta)
			->first();
		$vehiculos = CarpetaVehiculo::select('id_vehiculo')
			->where('id_carpeta',$carpeta->id)
			->where('estado',1)
			->pluck('id_vehiculo')
			->toArray();
		$formularios = VehiculoFormulario::selectRaw('max(id) as id')
			->whereIn('id_vehiculo',$vehiculos)
			->where('estado',1)
			->groupBy('id_formulario')
			->pluck('id')
			->toArray();
		$formularios = VehiculoFormulario::select('id','arancel')->whereIn('id',$formularios)
			->get();
		return $this->container->view->render($response, 'admin_views/negocio/carpeta.twig',[
			'carpeta'=>$carpeta,
			'montoFormularios'=>$formularios->sum('arancel'),
			'dateroEstados' => $dateroEstados,
		]);
	}

	public function vincularCliente($request,$response,$args){
		$id_usuario = auth::empleado()->id_usuario;
		$id_carpeta = $args['id'];
		$id_cliente = $args['id_cliente'];
		$vinculo = CarpetaCliente::where([
			['id_carpeta','=',$id_carpeta],
			['id_cliente','=',$id_cliente],
			['estado','=',1],
		])->first();
		if(!$vinculo){
			CarpetaCliente::create([
				'id_carpeta'=>$id_carpeta,
				'id_cliente' => $id_cliente,
				'id_usuario' => $id_usuario,
			]);
			$cliente = Cliente::with('tipoDocumento')->where('id',$id_cliente)->first();
			return $response->withStatus(200)->withJson($cliente);
		} else {
			return $response->withStatus(409);
		}
	}

	public function desvincularCliente($request,$response,$args){
		$id_carpeta = $args['id'];
		$id_cliente = $args['id_cliente'];
		$vinculo = CarpetaCliente::where([
			['id_carpeta','=',$id_carpeta],
			['id_cliente','=',$id_cliente],
		])->update([
			'estado'=>0
		]);
		$cliente = Cliente::with('tipoDocumento')->where('id',$id_cliente)->first();
		return $response->withStatus(200)->withJson($cliente);
	}

	public function titular($request,$response,$args){
		$id_carpeta = $args['id'];
		$id_cliente = $args['id_cliente'];
		$vinculo = Carpeta::where('id',$id_carpeta)
			->update([
				'id_cliente'=>$id_cliente,
			]);
		$this->flash->addMessage('info', 'El titular fue cambiado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$id_carpeta]));
	}

	public function crearCuenta($request,$response,$args){
		$id_sucursal = auth::sucursal()->id;
		$id_carpeta = $args['id'];
		$carpeta = Carpeta::where('id',$id_carpeta)->first();
		$cuenta = Cuenta::create([
			'nombre_cuenta'=> $carpeta->operacion.' - '.$carpeta->datero->apellido.' '.$carpeta->datero->nombre.' - '.$carpeta->vehiculo->getMarca->nombre.' '.$carpeta->vehiculo->modelo,
			'tipo_cuenta' => 1,
			'id_provedor_cliente' => $carpeta->id_cliente,
			'id_sucursal'=> $id_sucursal,
			'id_carpeta' => $id_carpeta,
		]);
		Carpeta::where('id',$id_carpeta)->update([
			'id_cuentacorriente' => $cuenta->id,
		]);

		$this->flash->addMessage('info', 'La cuenta corriente fue creada Exitosamente.');
		return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$id_carpeta]));
	}

	public function cerrar($request,$response,$args){
		$id_carpeta = $args['id'];

		$dt = new \DateTime;
		Carpeta::where('id',$id_carpeta)->update([
			'cerrado' => $dt,
		]);

		$this->flash->addMessage('info', 'La carpeta fue cerrado Exitosamente.');
		return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$id_carpeta]));
	}

	public function aprobar($request,$response,$args){
		$id_carpeta = $args['id'];

		$dt = new \DateTime;
		Carpeta::where('id',$id_carpeta)->update([
			'aprobado' => $dt,
		]);

		$this->flash->addMessage('info', 'La carpeta fue aprobada Exitosamente.');
		return $response->withRedirect($this->router->pathFor('carpeta',['id'=>$id_carpeta]));
	}

}
