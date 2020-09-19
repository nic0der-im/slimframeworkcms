<?php
if(isset($_FILES["file"]["type"]))
{
	$validextensions = array("jpeg", "jpg", "png");
	$temporary = explode(".", $_FILES["file"]["name"]);
	$file_extension = end($temporary);
	if ((($_FILES["file"]["type"] == "image/png") || ($_FILES["file"]["type"] == "image/jpg") || ($_FILES["file"]["type"] == "image/jpeg")) && ($_FILES["file"]["size"] < 1000000) && in_array($file_extension, $validextensions)) 
	{
		if ($_FILES["file"]["error"] > 0)
		{
			$a = array('dir'=>0);
		}
		else
		{
			$path = $_FILES['file']['name'];
			$ext = pathinfo($path, PATHINFO_EXTENSION);
			$nombre = $_POST['nombre-usuario'].".".$ext;

			if (file_exists("perfil/" . $_FILES["file"]["name"])) 
			{
				$unlink("perfil/".$nombre);
				$sourcePath = $_FILES['file']['tmp_name']; // Storing source path of the file in a variable
				$targetPath = "perfil/".$nombre; // Target path where file is to be stored
				move_uploaded_file($sourcePath,$targetPath); // Moving Uploaded file
				$a = array('dir'=>$nombre);
			}
			else
			{
				$sourcePath = $_FILES['file']['tmp_name']; // Storing source path of the file in a variable
				$targetPath = "perfil/".$nombre; // Target path where file is to be stored
				move_uploaded_file($sourcePath,$targetPath); // Moving Uploaded file
				$a = array('dir'=>$nombre);
			}
		}
	}
	else
	{
		$a = array('dir'=>0);
	}
	echo json_encode($a);
}
?>