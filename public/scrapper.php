/* RECOLECTOR DE GRUPOS DE FACEBOOK v15 by Ignacio
* ------------------------------------
* PASOS:
* 1- Ingresar a https://www.facebook.com/groups/
* 2- Ingresar a la consola con F12
* 3- Seleccionar todo el código de este archivo y pegar en la consola
* 4- Presionar ENTER y esperar un rato.
* 5- Listorti vieja, tenes los grupos en un CSV.
*/


console.log("Se inició la recolección, espere unos segundos...");
seguir();

function seguir()
{
	a = document.getElementsByClassName('uiMorePagerPrimary');
	if(a.length == 0)
	{
		setTimeout(buscaroro, 1000);
	}
	else
	{
		a[0].click();
		setTimeout(seguir, 1500);
	}
}

function buscaroro()
{
	grupos = document.getElementsByClassName("_4-jy");
	array = new Array(grupos.length);

	for(i = 0; i < grupos.length; i++)
	{
		titulo = grupos[i].getElementsByTagName('img')[0].getAttribute("aria-label");
		titulo = titulo.replace(/"/g,'@');

		id = grupos[i].getElementsByTagName('a')[0].getAttribute("data-hovercard");
		id = id.replace("/ajax/hovercard/group.php?id=","https://www.facebook.com/groups/");
		id = id.replace("&ref=group_browse_new","/");

		var id_foto = grupos[i].getElementsByTagName('img')[0].getAttribute("src");
		var id_foto2 = id_foto.split("?");
		var id_foto3 = id_foto2[0].split("/");

		if(id_foto3.length == 8) 
		{ 
			array[i] = new Array(id,'"'+titulo+'"', id_foto3[7]);
		} 
		else if(id_foto3.length == 9) 
		{ 
			array[i] = new Array(id,'"'+titulo+'"', id_foto3[8]);
		}
	}

	console.log("Generando archivo CSV...");
	let header = "data:text/csv;charset=utf-8,%EF%BB%BF";
	let csvContent = "";
	array.forEach(function(rowArray){
	   let row = rowArray.join(",");
	   csvContent += row + "\r\n";
	}); 

	var nombre = "archivo.csv";

	console.log("Descargando archivo CSV...");
	var encodedUri = encodeURI(csvContent);
	var link = document.createElement("a");
	link.setAttribute("href", header + encodedUri);
	link.setAttribute("download", nombre);
	link.innerHTML= "BAJAN2";
	document.body.appendChild(link);
	link.click();
}