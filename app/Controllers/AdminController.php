<?php


namespace App\Controllers;

use Slim\Views\Twig as View;

use App\Models\Permisos;
use App\Models\Prospecto;
use App\Models\Prospectos\ProspectoHistorial;
use App\Models\Empleado;
use App\Models\Notificaciones\Notificaciones;
use App\Models\Vehiculos\VehiculoHistorial;
use App\Models\Vehiculos\Vehiculo;

use Illuminate\Database\Capsule\Manager as DB;
use GuzzleHttp\Client;



class AdminController extends Controller {

	public function index($request, $response)
	{
		$empleado = Empleado::where('id_usuario','=',$_SESSION['userid'])->first();
		$flag_msg = $empleado->msg_admin;
		if($flag_msg == 0)
		{
			$empleado->msg_admin = 1;
			$empleado->save();
		}

		$ultimos_vehiculos = VehiculoHistorial::whereIn('id_estado', [1, 2, 3])->orderBy('id', 'DESC')->limit(10)->get();

		$ventas_mes = DB::table('vehiculos')
						->selectRaw('MONTH(updated_at) as mes, id_localidad as localidad, COUNT(id) as cantidad')
						->where('id_estado', 3)
						->where('estado_vehiculo', 2)
						->groupBy(DB::raw('MONTH(updated_at), id_localidad'))
						->get();

		$ventas_mes_total = DB::table('vehiculos')
						->where('id_estado', 3)
						->where('estado_vehiculo', 2)
						->whereRaw('MONTH(updated_at) = '. (intval(date('m')) - 1) )
						->count();

		$ventas_mes_pasado_total = DB::table('vehiculos')
						->where('id_estado', 3)
						->where('estado_vehiculo', 2)
						->whereRaw('MONTH(updated_at) = '. (intval(date('m')) - 2))
						->count();

		$llamadas = ProspectoHistorial::selectRaw('MONTH(updated_at) as mes, COUNT(id) as cantidad')
						->where('id_vendedor', '=', $_SESSION["userid"])
						->whereIn('estado', [1,2,3,4])
						->groupBy(DB::raw('MONTH(updated_at)'))
						->get();

		return $this->container->view->render($response, 'admin_views/home.twig', [
			'mostrar_msg' => $flag_msg,
			'ultimos_vehiculos' => $ultimos_vehiculos,
			'ventas_mes' => $ventas_mes,
			'ventas_este_mes' => $ventas_mes_total,
			'ventas_mes_pasado' => $ventas_mes_pasado_total,
			'llamadas' => $llamadas,
		]);
	}

	public function rendimiento($request, $response)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/b93cd8de78148b43648df1a49c8018aa/settings/development_mode",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "content-type: application/json",
		    "postman-token: 439e991b-77a4-0837-fa3a-c3afa571c3a1",
		    "x-auth-email: ignaciomedina1@hotmail.com.ar",
		    "x-auth-key: 6baa7ebbb8e343b9fc046e7f4606d18bc4400"
		  ),
		));

		$respuesta = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		return $this->container->view->render($response, 'admin_views/admin/rendimiento.twig', [
			//'estado'=> $estado,
			'respuesta'=> json_decode($respuesta),
		]);
	}

	public function rendimiento_desarrollo($request, $response)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/b93cd8de78148b43648df1a49c8018aa/settings/development_mode",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PATCH",
		  CURLOPT_POSTFIELDS => "{\n\t\"value\":\"on\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "content-type: application/json",
		    "x-auth-email: ignaciomedina1@hotmail.com.ar",
		    "x-auth-key: 6baa7ebbb8e343b9fc046e7f4606d18bc4400"
		  ),
		));

		$respuesta = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $respuesta;
		}

		$this->flash->addMessage('info', 'El modo de desarrollo fue activado, se desactivo el caché de Twig y de Cloudflare. <br>Respuesta servidor:<br> <code>' . $respuesta . '</code>');
		$url = $this->router->pathFor('godmode.rendimiento');

		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function rendimiento_desarrollo_desactivar($request, $response)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/b93cd8de78148b43648df1a49c8018aa/settings/development_mode",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PATCH",
		  CURLOPT_POSTFIELDS => "{\n\t\"value\":\"off\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "content-type: application/json",
		    "x-auth-email: ignaciomedina1@hotmail.com.ar",
		    "x-auth-key: 6baa7ebbb8e343b9fc046e7f4606d18bc4400"
		  ),
		));

		$respuesta = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $respuesta;
		}

		$this->flash->addMessage('info', 'El modo de desarrollo fue desactivado, se re-activo el caché de Twig y de Cloudflare. <br>Respuesta servidor:<br> <code>' . $respuesta . '</code>');
		$url = $this->router->pathFor('godmode.rendimiento');

		return $response->withStatus(302)->withHeader('Location', $url);
	}
}
