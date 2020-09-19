<?php

if(isset($_FILES))
{
	$error = false;
	$err_desc = "";
	$files = array();

	$uploaddir = './images/uploads/';

	$a = 1;
	foreach($_FILES as $file)
	{
		$extension = pathinfo(parse_url($file['name'])['path'], PATHINFO_EXTENSION);

		$nuevo_nombre = $_POST['vehicle-marca'].'-'.$_POST['vehicle-modelo'].'-'.$_POST['vehicle-year'].'-'.$_POST['vehicle-tipo'].'-'.$_POST['vehicle-extra'].'_'.$_POST['vehicle-id'].'-'.$a.'.jpg';

		$fichero = $uploaddir.basename($nuevo_nombre);
		while(file_exists($fichero)) {
			$a++;
			$nuevo_nombre = $_POST['vehicle-marca'].'-'.$_POST['vehicle-modelo'].'-'.$_POST['vehicle-year'].'-'.$_POST['vehicle-tipo'].'-'.$_POST['vehicle-extra'].'_'.$_POST['vehicle-id'].'-'.$a.'.jpg';
			$fichero = $uploaddir.basename($nuevo_nombre);
		}
		
		$moved = move_uploaded_file($file['tmp_name'], $uploaddir.basename($nuevo_nombre));		
		if($moved)
		{
			compress_image($uploaddir.basename($nuevo_nombre), $uploaddir.basename($nuevo_nombre), 80);
			$files[] = $uploaddir .$nuevo_nombre;
		}
		else
		{
			$error = true;
			$err_desc = $file['error'];
		}
		$a++;
	}
	$data = ($error) ? array('success' => false, 'error_desc'=>$err_desc) : array('success' => true, 'files' => $files);
}

function compress_image($source_url, $destination_url, $quality)
{

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

echo json_encode($data);
?>