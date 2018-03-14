<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Manejo de archivos</title>
	<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
	<main>
		<div id="register">
			<h3>Registro de usuarios</h3>
			<form action="" method="post" id="form_register">
				<input type="text" placeholder="Nombre">
				<input type="text" placeholder="Apellido">
				<input type="text" placeholder="Cuenta">
				<input type="password" placeholder="Contraseña">
				<input type="password" placeholder="Confirme la Contraseña">
			</form>
		</div>
		<div id="login">
			<h3>Inicio de sesion</h3>
			<form action="" method="post" id="form_login">
				<input type="text" name="txtUsername">
				<input type="password" name="txtPassword">
				<input class="btn" type="submit" name="btnLogin">
			</form>
		</div>
		<div id="busqueda">
			<h3>Busqueda mediante nombre</h3>
			<form action="" method="post" id="form_search">
				<input type="text" name="txtSearch">
				<input class="btn" type="submit" name="btnSearch">
			</form>
		</div>
		<div id="manejo_archivos">
			<h3>Manejo de archivos</h3>
			<form enctype="multipart/form-data" method="post" action="">
			 	<span>Seleccione archivo</span><input name="btnSelectFile" type="file" />
			 	<input class="btn" type="submit" value="Send File" name="btnUploadFile" />
			</form>
			<form action="" method="post">
				<table id="files_details">
					<thead>
						<tr>
							<th>Nombre</th>
							<th>Autor</th>
							<th>Fecha subido</th>
							<th>Tamaño (MB)</th>
							<th>Propietario</th>
							<th>Descripción</th>
							<th>Clasificacion</th>
							<th>Eliminar</th>
							<th>Compartir</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><a href="#">Archivo 1.xlsx</a></td>
							<td>Fernanda Leon</td>
							<td>12/03/2018</td>
							<td>12</td>
							<td>Fernanda Leon</td>
							<td>Archivo de prueba para la estructura</td>
							<td>Trabajos</td>
							<td><button>Eliminar</button></td>
							<td><button>Compartir</button></td>
						</tr>
						<tr>
							<td><a href="#">Archivo 2.xlsx</a></td>
							<td>Francisco Gómez</td>
							<td>10/03/2018</td>
							<td>4</td>
							<td>Fernanda Leon</td>
							<td>Archivo de prueba para la estructura</td>
							<td>Analisis</td>
							<td><button>Eliminar</button></td>
							<td><button>Compartir</button></td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>
		<div id="ayuda">
			
		</div>
		<div id="compartir">
			<h3>Compartir archivo</h3>
			<form action="" method="post" id="form_share">
				<select name="" id="">
					<option value="1">opcion 1</option>
				</select>
				<input class="btn" type="submit" name="btnShare">
			</form>
		</div>
	</main>
</body>
</html>