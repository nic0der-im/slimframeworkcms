<?php

namespace App\Controllers;

use Slim\Views\Twig as View;
use Illuminate\Database\QueryException;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

use App\Models\User;
use App\Models\Pagina\Pagina;
use App\Models\Pagina\FotoGaleria;
use App\Models\Pagina\Bloque;
use App\Models\Pagina\Fullscreen;
use App\Models\Pagina\TextoImg;
use App\Models\Pagina\FichaTecnica;
use App\Models\Pagina\Version;

use App\Models\Pagina\Icono;

use App\Models\Vehiculos\Marca;

class PageController extends Controller {

	// Utils

	public function saveImage($titulo, $id_pagina, $id_bloque, $base64Image, $orden = 1)
	{  
 		$uploaddir = __DIR__ ;
 		$titulo = preg_replace('/[^a-z0-9\.]/i', '-', strtolower($titulo));
 		$nombre = $titulo.'_'.$id_pagina.'_'.$id_bloque.'_'.$orden.'.jpg';
    $filename = $uploaddir.'/../../public/images/uploads/'.$nombre;
    $test_archivo = $uploaddir.'/../../public'.$base64Image;
    if($base64Image != "" && file_exists($test_archivo)) {
    	if($res = rename($test_archivo, $filename))
    	{
    		return array("success"=>$res, "nombre"=>'/images/uploads/'.$nombre);    	
    	}
    	else
    	{
    		throw new \Exception("El archivo no existe. Sube la foto nuevamente", 1);
    	}    	
    }
    else {
    	$tmp_img = explode('base64,', $base64Image);
    	if(isset($tmp_img[1])) {
    		$tmp_img = str_replace(' ', '+', $tmp_img[1]);
				$img = base64_decode($tmp_img);
				$res = file_put_contents($filename, $img);
				return array("success"=>$res, "nombre"=>'/images/uploads/'.$nombre);	
    	}
    	else {
    		throw new \Exception("El archivo subido no es una foto", 1);    		
    	}			
		}    
	}

	// ----------------------------------------------------------------------------------

	// Index:
	public function getListado0km($request, $response)
	{
		return $this->container->view->render($response, 'admin_views/new_paginas/index.twig', [
			'paginas'=>Pagina::where('eliminado', 0)->orderBy('created_at', 'desc')->get()
		]);
	}	

	// ----------------------------------------------------------------------------------

	// Creación:
	public function getCrear($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/new_paginas/crear.twig', [
			'marcas'=>Marca::all(),
			'iconos'=>Icono::all(),
		]);
	}

	public function postCrear($request,$response)
	{
		$pagedata = json_decode($request->getParam('pagedata'));

		$pagina_data = $pagedata->pagina;
		$secciones_data = $pagedata->secciones;

		$errores = array();

		$same_url = Pagina::where([['url_name', '=', $pagina_data->url]])->get();

		if(count($same_url) != 0) {
			$errores[] = array("step"=>"Creación de la página", "debug"=>$same_url, "error"=>"Ya existe una pagina con esa URL.");
			if($request->isXhr()){
				return $response->withJson([
					'success'=>false,
					'errores'=>$errores,
				]);
			}
		}

		$pagina = Pagina::create([
			'id_usuario'=>$_SESSION['userid'],
			'id_marca'=>$pagina_data->marca,
			'titulo'=>$pagina_data->titulo,
			'keywords'=>$pagina_data->keywords,
			'descripcion'=>$pagina_data->descripcion,
			'url_name'=>$pagina_data->url
		]);

		$success = true;

		foreach($secciones_data as $_bloque) {

			try {
				$bloque = Bloque::create([
					'id_pagina'=>$pagina->id,
					'nombre'=>$_bloque->nombre,
					'href'=>$_bloque->href,
					'orden'=>$_bloque->orden,
					'tipo'=>$_bloque->type,
				]);
			} catch(\Illuminate\Database\QueryException $e) {
				$errores[] = array('step'=>'Creacion de bloques/secciones', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
				$success = false;
			}
				if($_bloque->type == "fullscreen") {

					try {
						$imagen = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_bloque->imagen);

						try {
							$fullscreen = Fullscreen::create([
								'id_bloque'=>$bloque->id,
								'titulo'=>$_bloque->titulo,
								'subtitulo'=>$_bloque->subtitulo,
								'imagen'=>$imagen['nombre']
							]);

						}
						catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}

				}
			else if($_bloque->type == "galeria") {
				foreach($_bloque->images as $_imagen) {
					try {
						$tmp_imagen = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_imagen->imagen, $_imagen->orden);						
						try {
							$fotoGaleria = FotoGaleria::create([
								'id_bloque'=>$bloque->id,
								'orden'=>$_imagen->orden,
								'alt'=>$_imagen->alt,
								'mode'=>'',
								'enlace'=>$tmp_imagen['nombre']
							]);
						}
						catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}
				}
			}
			else if($_bloque->type == "textoimg") {
				
				foreach($_bloque->items as $_item) {
					
					try {
						$tmp_item = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_item->imagen, $_item->orden);
						
						try {
							$fotoGaleria = TextoImg::create([
								'id_bloque'=>$bloque->id,
								'titulo'=>$_item->titulo,
								'parrafo'=>$_item->parrafo,
								'orden'=>$_item->orden,
								'imagen'=>$tmp_item['nombre']
							]);

						} catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}
				}					
			}
			else if($_bloque->type == "fichatecnica") {					
				foreach($_bloque->items as $_item) {			
					try {		
						$itemFicha = FichaTecnica::create([
							'id_bloque'=>$bloque->id,
							'titulo'=>$_item->titulo,
							'parrafo'=>$_item->parrafo,
							'orden'=>$_item->orden,
							'id_icono'=>$_item->id_icono,
						]);
					}
					catch(\Illuminate\Database\QueryException $e) {
						$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
						$success = false;
					}
				} 
			}
			else if($_bloque->type == "versiones") {
				foreach($_bloque->items as $_item) {

					try {
						$tmp_item = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_item->imagen, $_item->orden);
						
						try {
							$fotoGaleria = Version::create([
								'id_bloque'=>$bloque->id,
								'titulo'=>$_item->titulo,
								'contenido'=>$_item->parrafo,
								'orden'=>$_item->orden,								
								'enlace'=>$tmp_item['nombre'],
								'id_vehiculo'=>$_item->vehiculo->id
							]);

						} catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}
				} 
			}
		}

		if($request->isXhr()){
			if($success) {
				$this->flash->addMessage('info', 'La página ha sido creada correctamente.');
			}
			return $response->withJson([
				'success'=>$success,
				'params'=>$request->getParams(),
				'pagedata'=>$pagedata,
				'pagina'=>$pagina,
				'errores'=>$errores,
			]);
			
		}
	}


	// ----------------------------------------------------------------------------------
	// Cargar Icono
	public function getCargarIcono($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/new_paginas/cargar-icono.twig', [
		]);
	}

	public function postCargarIcono($request, $response, array $args) {

		$uploaddir = __DIR__ . '/../../public/images/iconos/';

		$uploadedFiles = $request->getUploadedFiles();

		$uploadedFile = $uploadedFiles['icono_enlace'];
    if($uploadedFile->getError() === UPLOAD_ERR_OK){

    	$filename = $this->moveUploadedFile($uploaddir, $uploadedFile, $request->getParam('icono_nombre'));
    	Icono::create([
				'id_usuario'=>$_SESSION['userid'],
				'nombre'=>$request->getParam('icono_nombre'),
				'enlace'=>'/images/iconos/'.$filename,
			]);

			$this->flash->addMessage('info', 'Icono cargado exitosamente');
  	 	return $response->withRedirect($this->router->pathFor('paginas.cargar.icono'));

    }

    $this->flash->addMessage('error', 'Ha ocurrido un error al cargar el archivo.<br>'.$uploadedFile->getError());
	 	return $response->withRedirect($this->router->pathFor('paginas.cargar.icono'));

	}

	// Listado iconos
	public function getListadoIconos($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/new_paginas/listado-iconos.twig', [
			'iconos'=>Icono::get(),
		]);
	}

	// ----------------------------------------------------------------------------------
	// Previsualizacion

	public function getPreview($request, $response, array $args) {

		$pagina = Pagina::find($args['id']);

		if($pagina) {
			return $this->container->view->render($response, 'admin_views/new_paginas/preview.twig', [
				'pagina'=>$pagina,
				'iconos'=>Icono::all(),
			]);	
		}
		else {
			$this->flash->addMessage('error', 'La página solicitada no existe.');
	 		return $response->withRedirect($this->router->pathFor('paginas.index'));
		}
		
	
	}

	public function getPreviewActual($request, $response, array $args) {
		return $this->container->view->render($response, 'admin_views/new_paginas/previewactual.twig', [			
		]);	
	}


	
	
	// Edición:
	public function getEditar($request, $response, array $args)
	{
		$pagina = Pagina::find($args['id']);

		if($pagina) {
			return $this->container->view->render($response, 'admin_views/new_paginas/editar.twig', [
				'pagina'=>$pagina,
				'marcas'=>Marca::get(),
				'iconos'=>Icono::all(),
			]);	
		}
		else {
			$this->flash->addMessage('error', 'La página solicitada no existe.');
	 		return $response->withRedirect($this->router->pathFor('paginas.index'));
		}
	}

	public function postEditar($request,$response, $args){

		$pagina = Pagina::find($args['id']);		

		$pagedata = json_decode($request->getParam('pagedata'));

		$pagina_data = $pagedata->pagina;
		$secciones_data = $pagedata->secciones;

		$errores = array();

		$same_url = Pagina::where([['url_name', '=', $pagina_data->url], ['id', '<>', $args['id']]])->get();

		if(count($same_url) == 0) {
			$pagina->url_name = $pagina_data->url;			
		}

		else {
			$errores[] = array("step"=>"Modificación de la página", "debug"=>$same_url, "error"=>"Ya existe una pagina con esa URL.");
			if($request->isXhr()){
				return $response->withJson([
					'success'=>false,
					'errores'=>$errores,
				]);
			}
		}

		$pagina->id_marca = $pagina_data->marca;
		$pagina->titulo = $pagina_data->titulo;
		$pagina->descripcion = $pagina_data->descripcion;
		$pagina->keywords = $pagina_data->keywords;	

		$pagina->save();

		// Eliminar los bloques existentes para evitar conflictos.
		$bloques = Bloque::where('id_pagina', $args['id'])->get();

		foreach ($bloques as $bloque) {

			if($bloque->tipo == 'fullscreen') {
				$fullscreen = Fullscreen::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'textoimg') {
				$textoimg = TextoImg::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'galeria') {
				$galeria = FotoGaleria::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'fichatecnica') {
				$fichatecnica = FichaTecnica::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'versiones') {
				$versiones = Version::where('id_bloque', $bloque->id)->delete();
			}
			$bloque->delete();
		}

		$success = true;

		foreach($secciones_data as $_bloque) {

			try {
				$bloque = Bloque::create([
					'id_pagina'=>$pagina->id,
					'nombre'=>$_bloque->nombre,
					'href'=>$_bloque->href,
					'orden'=>$_bloque->orden,
					'tipo'=>$_bloque->type,
				]);
			} catch(\Illuminate\Database\QueryException $e) {
				$errores[] = array('step'=>'Creacion de bloques/secciones', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
				$success = false;
			}
				if($_bloque->type == "fullscreen") {

					try {
						$imagen = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_bloque->imagen);

						try {
							$fullscreen = Fullscreen::create([
								'id_bloque'=>$bloque->id,
								'titulo'=>$_bloque->titulo,
								'subtitulo'=>$_bloque->subtitulo,
								'imagen'=>$imagen['nombre']
							]);

						}
						catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}

				}
			else if($_bloque->type == "galeria") {
				foreach($_bloque->images as $_imagen) {
					try {
						$tmp_imagen = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_imagen->imagen, $_imagen->orden);						
						try {
							$fotoGaleria = FotoGaleria::create([
								'id_bloque'=>$bloque->id,
								'orden'=>$_imagen->orden,
								'alt'=>$_imagen->alt,
								'mode'=>'',
								'enlace'=>$tmp_imagen['nombre']
							]);
						}
						catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}
				}
			}
			else if($_bloque->type == "textoimg") {
				
				foreach($_bloque->items as $_item) {
					
					try {
						$tmp_item = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_item->imagen, $_item->orden);
						
						try {
							$fotoGaleria = TextoImg::create([
								'id_bloque'=>$bloque->id,
								'titulo'=>$_item->titulo,
								'parrafo'=>$_item->parrafo,
								'orden'=>$_item->orden,
								'imagen'=>$tmp_item['nombre']
							]);

						} catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}
				}					
			}
			else if($_bloque->type == "fichatecnica") {					
				foreach($_bloque->items as $_item) {			
					try {		
						$itemFicha = FichaTecnica::create([
							'id_bloque'=>$bloque->id,
							'titulo'=>$_item->titulo,
							'parrafo'=>$_item->parrafo,
							'orden'=>$_item->orden,
							'id_icono'=>$_item->id_icono,
						]);
					}
					catch(\Illuminate\Database\QueryException $e) {
						$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
						$success = false;
					}
				} 
			}
			else if($_bloque->type == "versiones") {
				foreach($_bloque->items as $_item) {

					try {
						$tmp_item = $this->saveImage($pagina_data->titulo.'-'.$_bloque->nombre, $pagina->id, $bloque->id, $_item->imagen, $_item->orden);
						
						try {
							$fotoGaleria = Version::create([
								'id_bloque'=>$bloque->id,
								'titulo'=>$_item->titulo,
								'contenido'=>$_item->parrafo,
								'orden'=>$_item->orden,								
								'enlace'=>$tmp_item['nombre'],
								'id_vehiculo'=>$_item->vehiculo->id
							]);

						} catch(\Illuminate\Database\QueryException $e) {
							$errores[] = array('step'=>'creacion sub bloque', 'debug'=>$_bloque, 'error'=>$e->errorInfo);
							$success = false;
						}
					}
					catch (\Exception $e) {
						$errores[] = array('step'=>'Procesamiento imagen', 'debug'=>$_bloque, 'error'=>$e->getMessage());
						$success = false;
					}
				} 
			}
		}

		if($request->isXhr()){

			if($success) {
				$this->flash->addMessage('info', 'La página ha sido editada correctamente.');
			}

			return $response->withJson([
				'success'=>$success,
				'params'=>$request->getParams(),
				'pagedata'=>$pagedata,
				'pagina'=>$pagina,
				'errores'=>$errores,
			]);

		}

	}

	public function postBloquesEditar($request,$response, $args){
		$id_bloque = $args['id'];
		$id_pagina = $args['id_pagina'];
		$bloque = PaginasBloques::find($id_bloque);

		$uploadedFiles = $request->getUploadedFiles();
		$file = $uploadedFiles['imagen'];
		if ($file->getError() === UPLOAD_ERR_OK) {
			$uploaddir = './images/uploads/';
			$factory = new \RandomLib\Factory;
			$generator = $factory->getMediumStrengthGenerator();
			$uuid = $generator->generateString(64,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
			$nuevo_nombre = 'blo'.$uuid.'.jpg';
			$fichero = $uploaddir.basename($nuevo_nombre);
			while(file_exists($fichero)) {
				$uuid = $generator->generateString(64,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
				$nuevo_nombre = 'blo'.$uuid.'.jpg';
				$fichero = $uploaddir.basename($nuevo_nombre);
			}
			$file->moveTo($uploaddir.basename($nuevo_nombre));
			$this->compress_image($uploaddir.basename($nuevo_nombre), $uploaddir.basename($nuevo_nombre), 80);
			$archivo = str_replace('./', '/', $uploaddir .$nuevo_nombre);

			$string = $bloque->imagen;
			if(strpos( $string, "/images/uploads/" )===0){
				unlink(getcwd(). $string);
			}

			$bloque->imagen = $archivo;
		}

		$bloque->titulo = $request->getParam('titulo');
		$bloque->parrafo = $request->getParam('parrafo');
		$bloque->imagen_title = $request->getParam('imagen_title');
		$bloque->completo = $request->getParam('completo');
		$bloque->tipo = $request->getParam('tipo');
		$bloque->save();

		$this->flash->addMessage('info', 'Bloque editado exitosamente.');
		$url = $this->router->pathFor('paginas.bloques', ['id_pagina' => $id_pagina]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function postFotosEditar($request,$response, $args)
	{
		$bloque = PaginasBloques::find($request->getParam('foto_id'));
		$bloque->enlace = $request->getParam('foto_enlace');
		$bloque->save();

		$this->flash->addMessage('info', 'Bloque editado exitosamente.');
		$url = $this->router->pathFor('paginas.fotos', ['id' => $request->getParam('pagina_id')]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	
	// ----------------------------------------------------------------------------------


	// Eliminación:
	public function getEliminar($request, $response, array $args)
	{
		$publicacion = Pagina::find($args['id']);
		$bloques = Bloque::where('id_pagina', $args['id'])->get();

		foreach ($bloques as $bloque) {
			if($bloque->tipo == 'fullscreen') {
				$fullscreen = Fullscreen::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'textoimg') {
				$textoimg = TextoImg::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'galeria') {
				$galeria = FotoGaleria::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'fichatecnica') {
				$fichatecnica = FichaTecnica::where('id_bloque', $bloque->id)->delete();
			}
			else if($bloque->tipo == 'versiones') {
				$versiones = Version::where('id_bloque', $bloque->id)->delete();
			}

			$bloque->delete();
		}

		$publicacion->delete();
		
		$this->flash->addMessage('info', 'Página eliminada exitosamente.');

		$url = $this->router->pathFor('paginas.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getBloquesEliminar($request, $response, array $args)
	{
		$bloque = PaginasBloques::find($args['id']);
		$url = $this->router->pathFor('paginas.bloques',['id_pagina'=>$bloque->id_pagina]);
		$bloque->delete();
		$this->flash->addMessage('info', "Se eliminó el bloque.");
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function getFotosEliminar($request, $response, array $args)
	{
		$foto = PaginasFotos::find($args['id']);
		$url = $this->router->pathFor('paginas.fotos',['id'=>$foto->id_pagina]);
		if(substr( $foto->enlace, 0, 4 ) != "http"){
			unlink(getcwd().$foto->enlace);
		}
		$foto->delete();
		$this->flash->addMessage('info', "Se eliminó la foto.");
		return $response->withStatus(302)->withHeader('Location', $url);
	}
	// ----------------------------------------------------------------------------------

	public function postBloquesOrden($request,$response,$args){
		$bloque_orden = $request->getParam('bloque_orden');
		$id_pagina = $args['id_pagina'];
		$iterador = explode(';', $bloque_orden);
		$nuevoOrden = 1;

		foreach ($iterador as $clave => $valor) {
			list($id,$orden)=explode(',', $valor);
			PaginasBloques::where('id',$id)->update([
				'orden'=>$nuevoOrden,
			]);
			$nuevoOrden = $nuevoOrden + 1;
		}

		$this->flash->addMessage('info', 'Bloque Ordenado exitosamente.');
		$url = $this->router->pathFor('paginas.bloques', ['id_pagina' => $id_pagina]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function postImagen($request, $response, $args) {
		$id_pagina = $args['id'];
		$atributo = $request->getParam('atributo');

		$uploadedFiles = $request->getUploadedFiles();
		$file = $uploadedFiles['file'];

		$uploaddir = './images/uploads/';
		$factory = new \RandomLib\Factory;
		$generator = $factory->getMediumStrengthGenerator();
		$uuid = $generator->generateString(64,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

		$nuevo_nombre = 'pag'.$uuid.'.jpg';
		$fichero = $uploaddir.basename($nuevo_nombre);
		while(file_exists($fichero)) {
			$uuid = $generator->generateString(64,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
			$nuevo_nombre = 'pag'.$uuid.'.jpg';
			$fichero = $uploaddir.basename($nuevo_nombre);
		}
		if ($file->getError() === UPLOAD_ERR_OK) {
			$file->moveTo($uploaddir.basename($nuevo_nombre));
			$this->compress_image($uploaddir.basename($nuevo_nombre), $uploaddir.basename($nuevo_nombre), 80);

			$archivo = str_replace('./', '/', $uploaddir .$nuevo_nombre);
			$string = Paginas::where('id',$id_pagina)->pluck($atributo)->toArray();
			if(strpos( $string[0], "/images/uploads/" )===0){
				unlink(getcwd(). $string[0]);
			}
			Paginas::where('id',$id_pagina)->update([
				$atributo => $archivo,
			]);

			$this->flash->addMessage('info', 'Imagen agregada exitosamente como '.$atributo.'.');
		} else {
			$this->flash->addMessage('error', 'A ocurrido un problema.');
		}

		$url = $this->router->pathFor('paginas.editar', ['id' => $id_pagina]);
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function mostrar($request,$response,$args){
		$id_pagina = $args['id'];
		Pagina::where('id',$id_pagina)->update([
			'mostrar'=>1,
		]);
		$this->flash->addMessage('warning', 'Pagina cambiada su estado a mostrar.');
		$url = $this->router->pathFor('paginas.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function ocultar($request,$response,$args){
		$id_pagina = $args['id'];
		Pagina::where('id',$id_pagina)->update([
			'mostrar'=>0,
		]);
		$this->flash->addMessage('warning', 'Pagina ocultada.');
		$url = $this->router->pathFor('paginas.index');
		return $response->withStatus(302)->withHeader('Location', $url);
	}

	public function compress_image($source_url, $destination_url, $quality){

		$info = getimagesize($source_url);
		ini_set('gd.jpeg_ignore_warning', 1);
		if ($info['mime'] == 'image/jpeg') {
			$image = @imagecreatefromjpeg($source_url);
			if(!$image) {
				$image = imagecreatefromstring(file_get_contents($source_url));
			}			
		}
		

		elseif ($info['mime'] == 'image/gif')
		$image = imagecreatefromgif($source_url);

		elseif ($info['mime'] == 'image/png')
		$image = imagecreatefrompng($source_url);

		imagejpeg($image, $destination_url, $quality);
		return $destination_url;

	}

	public function moveUploadedFile($directory, UploadedFile $uploadedFile, $nombre)
	{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $filename = preg_replace('/[^a-z0-9\.]/i', '-', strtolower($nombre)).'.'.$extension;
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
	}
}