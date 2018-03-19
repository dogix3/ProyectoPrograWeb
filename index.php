<?php 
	session_start();
	###########################################################
	# SECCION DONDE SE DECLARAN VARIABLES GLOBALES A UTILIZAR #
	###########################################################
	#variable que obtiene el valor de los mensajes a mostrar
	$mensaje = "";
	#variable para habilitar el formulario de compartir
	$share = false;
	############################################################################
	# INICIO DONDE SE CAPTURAN LOS SUBMIR NECESARIOS PARA LLAMAR LAS FUNCIONES #
	############################################################################
	#si se ha dado click sobre el boton de registro
	if (isset($_POST['btnRegistrarse'])) {
		if (empty($_POST['txtNombre']) || empty($_POST['txtApellido']) || empty($_POST['txtCuenta']) || empty($_POST['txtContrasenna']) || empty($_POST['txtConfirmarContrasenna'])) {
			$mensaje = "No pueden haber campos vacios";
		}else if ($_POST['txtContrasenna'] != $_POST['txtConfirmarContrasenna']) {
			$mensaje = "Las contraseñas no coinciden";
		}else{
			$mensaje = register($_POST['txtNombre'], $_POST['txtApellido'], $_POST['txtCuenta'], $_POST['txtContrasenna']);
			if ($mensaje == "Registrado satisfactoriamente") {
				$_SESSION['action'] = "login";
			}else{
				$_SESSION['action'] = "register";
			}
		}
	}
	#si se ha dado click sobre el boton de login
	if (isset($_POST['btnLogin'])) {
		if (empty($_POST['txtUsername']) || empty($_POST['txtPassword'])) {
			$mensaje = "No pueden haber campos vacios";
		}else{
			$mensaje = login($_POST['txtUsername'], $_POST['txtPassword']);
			if ($mensaje == "1") {
				$mensaje = "";
				$_SESSION['action'] = "upload";
				$_SESSION['login'] = $_POST['txtUsername'];
				header('Location: index.php');
			}
		}
	}
	#se se ha dado click sobre el boton de subir archivos
	if (isset($_POST['btnSubmitFormFile'])) {
		if (empty($_FILES['btnSelectFile']) || empty($_POST['txtFileName']) || empty($_POST['txtAuthor']) || empty($_POST['txtFileClasification']) || empty($_POST['txtDescripcion'])) {
			$mensaje = "No pueden haber campos vacios";
		}else{
			$ext = pathinfo($_FILES['btnSelectFile']['name'], PATHINFO_EXTENSION);
			if ($ext != "xlsx" && $ext != "xls") {
				$mensaje = "Tipo de archivo invalido";
			}else{
				$tempName = $_FILES['btnSelectFile']['tmp_name'];
				$fileSize = formatSizeUnits($_FILES['btnSelectFile']['size']);
				$actualDate = date("d-m-Y");
				$mensaje = writeFileData($tempName, $_POST['txtFileName'], $_POST['txtAuthor'], $actualDate, $fileSize, $_SESSION['login'], $_POST['txtDescripcion'], $_POST['txtFileClasification'], $ext);
			}
			
		}
	}
	#si se ha dado click sobre el boton compartir archivo que se encuentra en la tabla
	if (isset($_POST['btnShareFile'])) {
		$share = true;
		$_SESSION['fileToShare'] = $_POST['btnShareFile'];
	}
	#se se ha dado click sobre el boton de compartir en el formulario compartir
	if (isset($_POST['btnShare'])) {
		if (isset($_POST['cmbUserName']) && isset($_SESSION['fileToShare'])) {
			$mensaje = shareFile($_SESSION['fileToShare'], $_POST['cmbUserName'], $_SESSION['login']);
			unset($_SESSION['fileToShare']);
		}else{
			$mensaje = "No se selecciono a un usuario";
		}
	}
	#si se ha dado click sobre el boton de registro
	if (isset($_POST['btnLoginRegister'])) {
		$_SESSION['action'] = "register";
	}
	#volver en el formulario de registro
	if (isset($_POST['btnRegisterBack'])) {
		$_SESSION['action'] = "login";
	}
	#cerrar sesion
	if (isset($_POST['btnLogout'])) {
		session_destroy();
		header('Location: index.php');
	}
	#eliminar archivos
	if (isset($_POST["btnDeleteFile"])) {
		$nombre = $_POST["btnDeleteFile"];
		$folder = $_SESSION['login'];
		$mensaje = eliminar("./$folder/$nombre", $nombre);
	}
	#variables utilizada para mostrar los diferentes formularios segun las acciones
	if (!isset($_SESSION['login']) && !isset($_SESSION['action'])) {
		$action = "login";
	}else{
		$action = $_SESSION['action'];
	}
	#####################################################################
	# SECCION DONDE SE REGISTRAN TODAS LAS FUNCIONES QUE VAN A UTILIZAR #
	#####################################################################
	/**
	 * Metodo para la verificación del loguien del usuario
	 * @param  string $user usuario para loguear
	 * @param  string $password contraseña del usuario
	 * @return int retorno que con el que se comprueba si los credenciales son correctos
	 */
	function login($user, $password){
		#Se comprueba si el usuario existe para iniciar session
		$filearray = file('usersData.pff');
		if ($filearray) {
			while (list($num, $lin) = each($filearray)) {
				if (isset($lin) && trim($lin)) {
					$datosUsuario = explode("||", trim($lin));
					if ($datosUsuario[2] == trim($user) && $datosUsuario[3] == md5($password)) {
						$_SESSION['firtsName'] = $datosUsuario[0];
						$_SESSION['lastName'] = $datosUsuario[1];
						return "1";
					}
				}
			}
		}
		return "La contraseña o el usuario son invalidos";
	}
	/**
	 * @param  string $name nombre del usuario
	 * @param  string $lastName apellido del usuario
	 * @param  string $user usuario para el login
	 * @param  string $password contraseña del usuario
	 * @return string string que alerta si existe un usuario igual o no
	 */
	function register($name, $lastName, $user, $password){
		#Se comprueba si el usuario existe
		if (file_exists('usersData.pff')) {
			$filearray = file('usersData.pff');
			if ($filearray) {
				while (list($num, $lin) = each($filearray)) {
					if (isset($lin) && trim($lin)) {
						$datosUsuario = explode("||", trim($lin));
						if ($datosUsuario[2] == trim($user)) {
							return 'El usuario ya existe, seleccione otro';
						}
					}
				}
			}
		}
		$userData = $name."||".$lastName."||".$user."||".md5($password).PHP_EOL;
		$usersFile = fopen('usersData.pff', "a");
		fwrite($usersFile, $userData);
		fclose($usersFile);
		#Creación de directorio para archivos de usuario creado
		mkdir("./".$user, 0700);
		return "Registrado satisfactoriamente";
	}
	/**
	 * Funcion que se encarga de subir los archivos, ademas de escribir en un respectivo txt los datos de
	 * los mismos
	 * @param  string $tmpFileName   nombre temporal del archivo
	 * @param  string $fileName      nombre con el que se guarda el archivo
	 * @param  string $author        autor del documento
	 * @param  string $actualDate    fecha actual en que se sube el documento
	 * @param  string $fileSize      tamaño del archivo a subir
	 * @param  string $owner         dueño del archivo (usuario actual)
	 * @param  string $description   descripcion del archivo
	 * @param  string $clasification clasificación
	 * @param  string $ext 			 revise la extención del archivo
	 * @return string                retorna mensaje de estado de ejecución
	 */
	function writeFileData($tmpFileName, $fileName, $author, $actualDate, $fileSize, $owner, $description, $clasification, $ext){
		#Codigo de subida del archivo
		if (move_uploaded_file($tmpFileName, "./".$owner."/".$fileName.".".$ext)) {
			#Codigo de guardado de datos del archivo subido
			$fileData = $fileName.".".$ext."||".$author."||".$actualDate."||".$fileSize."||".$owner."||".$description."||".$clasification.PHP_EOL;
			$fileFile = fopen('fileData.pff', "a");
			fwrite($fileFile, $fileData);
			fclose($fileFile);
			return "Archivo subido satisfactoriamente";
		}else{
			return "Fallo al subir archivo";
		}
	}
	/**
	 * funcion que lee los datos de los archivos subidos, pero unicamente de los que son parte del usuario
	 * @return string retorna la tabla de datos a mostrar
	 */
	function readFileData(){
		$user = $_SESSION['login'];
		$fileList = filesByUser($user);
		$tabla = "";
		if (file_exists('fileData.pff')) {
			$filearray = file('fileData.pff');
			if (!empty($fileList)) {
				if ($filearray) {
					while (list($var, $val) = each($filearray)) {
						++$var;
						if (isset($val) && trim($val) != "") {
							$datos_lista = explode("||", trim($val));
							if (count($datos_lista)>2 && in_array($datos_lista[0], $fileList)) {
								$tabla .= "<tr>
						 					<td><a href='$user/$datos_lista[0]'>$datos_lista[0]</a></td>
						 					<td>$datos_lista[1]</td>
						 					<td>$datos_lista[2]</td>
						 					<td>$datos_lista[3]</td>
						 					<td>$datos_lista[4]</td>
						 					<td>$datos_lista[5]</td>
						 					<td>$datos_lista[6]</td>
						 					<td><button class='btn_delete' name='btnDeleteFile' value='$datos_lista[0]'>Eliminar</button></td>
											<td><button class='btn_share' name='btnShareFile' value='$datos_lista[0]'>Compartir</button></td>
						 				</tr>";
							}
							
						}
					}
				}
			}
		}
		
		return $tabla;
	}
	function readSharedFileData(){
		$tabla = "";
		$user = "";
		$usuariosArray = array();
		if (file_exists('fileShared.pff')) {
			$sharedarray = file('fileShared.pff');
			if ($sharedarray) {
				while (list($var, $val) = each($sharedarray)) {
					++$var;
					if (isset($val) && trim($val) != "") {
						$datos_lista = explode("||", trim($val));
						if (count($datos_lista)>2 && $datos_lista[1] == $_SESSION['login']) {
							$usuariosArray[] = $datos_lista[0]."||".$datos_lista[2];
						}
					}
				}
			}
		}
		
		if (file_exists('fileData.pff')) {
			$filearray = file('fileData.pff');
			if ($filearray) {
				while (list($var, $val) = each($filearray)) {
					++$var;
					if (isset($val) && trim($val) != "") {
						$datos_lista = explode("||", trim($val));
						if (count($datos_lista)>2 && in_array("$datos_lista[0]||$datos_lista[4]", $usuariosArray)) {
							$tabla .= "<tr>
					 					<td><a href='$datos_lista[4]/$datos_lista[0]'>$datos_lista[0]</a></td>
					 					<td>$datos_lista[1]</td>
					 					<td>$datos_lista[2]</td>
					 					<td>$datos_lista[3]</td>
					 					<td>$datos_lista[4]</td>
					 					<td>$datos_lista[5]</td>
					 					<td>$datos_lista[6]</td>
					 					<td></td>
										<td>No se puede compartir</td>
					 				</tr>";
						}
					}
				}
			}
		}
		return $tabla;
	}
	/**
	 * obtiene todos los archivos relacionados con un usuario que se encuentran en su respectivo folder, luego los retorna en un array
	 * @param  string $user se envia el nombre de usuario, lo que es equivalente al nombre de la carpeta
	 * @return [type]       retorna array con el nombre de todos los archivos que existen en el folder
	 */
	function filesByUser($user){
		$fileList = array();
		$handle = opendir('./'.$user);
		if ($handle) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$fileList[] = $file;
				}
			}
			closedir($handle);
		}
		return $fileList;
	}
	/**
	 * funcion para obtener el peso mas adecuado de los archivos
	 * @param  int $bytes recibe la cantidad de bytes que pesa el archivo
	 * @return string        retorna el peso del archivo junto con la unidad
	 */
	function formatSizeUnits($bytes)
    {
    	if ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        else if ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        else if ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        else if ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }
        return $bytes;
	}
	/**
	 * funcion que eliminar el archivo, ademas de eliminar el registro en el archivo
	 * @param  string $nombreFichero direccion del archivo a eliminar
	 * @return string                mensaje de estado de acción
	 */
	function eliminar($fileUrl, $fileName){
		if (unlink($fileUrl)) {
			$arrayFilesData = array();
			$filearray = file('fileData.pff');
			if ($filearray) {
				while (list($var, $val) = each($filearray)) {
					++$var;
					if (isset($val) && trim($val) != "") {
						$datos_lista = explode("||", trim($val));
						if (count($datos_lista)>2 && $datos_lista[0] != $fileName) {
							$arrayFilesData[] = $val;
						}
					}
				}
				$dataFiles = fopen('fileData.pff', "w");
				foreach ($arrayFilesData as $value) {
					if ($value != "") {
						fwrite($dataFiles, $value);
					}
				}
				fclose($dataFiles);
			}
			return "Archivo eliminar con exito!";
		}
		return "Error al eliminar archivo";
	}
	/**
	 * función que permite obtener todos los user names de los usuarios para cuando se van a copartir.
	 * @return string retorna las opciones para ser insertadas en el combo box
	 */
	function getUserNames(){
		$options = "";
		$filearray = file("usersData.pff");
		if ($filearray) {
			while (list($var, $val) = each($filearray)) {
				++$var;
				if (isset($val) && trim($val) != "") {
					$datosUsuario = explode("||", trim($val));
					if (count($datosUsuario) > 2) {
						$options .= "<option value='$datosUsuario[2]'>$datosUsuario[0]"." "."$datosUsuario[1]</option>";
					}
				}
			}
		}
		return $options;
	}
	function shareFile($fileName, $sharedTo, $owner){
		$dataFiles = fopen('fileShared.pff', "a");
		$data = $fileName . "||" . $sharedTo . "||". $owner.PHP_EOL;
		fwrite($dataFiles, $data);
		fclose($dataFiles);
		return "Archivo compartido con exito!";
	}
	#
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Manejo de archivos</title>
	<link href="style.css" rel="stylesheet" type="text/css">

</head>
<body>
	<main>
	<?php if ($action == "register"):  ?>
		<div id="register">
			<h3>Registrarse<span class="ayuda">   <a href="#openHelpRegister"><img src="help.png"/></a></span></h3>
			<form action="" method="post" id="form_register">
				<input type="text" name="txtNombre" placeholder="Nombre">
				<input type="text" name="txtApellido" placeholder="Apellido">
				<input type="text" name="txtCuenta" placeholder="Cuenta">
				<input type="password" name="txtContrasenna" placeholder="Contraseña">
				<input type="password" name="txtConfirmarContrasenna" placeholder="Confirme la Contraseña">
				<input class="btn" type="submit" value="Registrarse" name="btnRegistrarse">
				<input class="btn" type="submit" value="Volver" name="btnRegisterBack">
			</form>
			<div class="mensaje"><?php echo $mensaje; ?></div>
		</div>
	<?php elseif ($action == "login"): ?>
		<div id="login">
			<h3>Inicio de Sesion</h3>
			<form action="" method="post" id="form_login">
				<input type="text" name="txtUsername" placeholder="Nombre de Usuario">
				<input type="password" name="txtPassword" placeholder="Contraseña">
				<input class="btn" type="submit" name="btnLogin" value="Iniciar Sesión">
				<input class="btn" type="submit" name="btnLoginRegister" value="¿No tienes una cuenta?">
			</form>
			<div class="mensaje"><?php echo $mensaje; ?></div>
		</div>
	<?php elseif ($action == "upload"): ?>
		<div id="logout">
			<form action="" method="post" id="form_logout">
				<span style="font-weight: bold">Bienvenido(a): </span><span><?php echo $_SESSION['firtsName'] ." ". $_SESSION['lastName']?></span>
				<input value="Cerrar sesión" class="btn btn_delete" type="submit" name="btnLogout">
			</form>
		</div><br>
		<h2 class="t titulo">Manejo de Archivos</h2>
		<div id="manejo_archivos">
			<div id="busqueda">
				<form action="" method="post" id="form_search">
					<input class="search" type="text" name="txtSearch" placeholder="Ingrese un nombre">
					<input class="btn btn_buscar" type="submit" name="btnSearch" value="Buscar">
				</form>
			</div>
			<div class="frm_add">
				<p>Agregar Archivos<span class="ayuda">   <a href="#openHelpAdd"><img src="help.png"/></a></span></p>

				<form enctype="multipart/form-data" method="post" action="" id="form_upload_file">
				 	<input class="form_input" type="text" name="txtFileName" placeholder="Nombre de archivo">
				 	<input class="form_input" type="text" name="txtAuthor" placeholder="Autor">
				 	<input class="form_input" type="text" name="txtDescripcion" placeholder="Descripción">
				 	<input class="form_input" type="text" name="txtFileClasification" placeholder="Clasificación">
				 	<input class="form_input" id="selectFile" type="file" name="btnSelectFile" accept=".xlsx, .xls"/>
				 	<div class="div_add">
				 		<input class="btn btn_add" type="submit" value="Subir archivo" name="btnSubmitFormFile" />
				 	</div>
				 	<div class="mensaje"><?php echo $mensaje; ?></div><br>
				</form>
			</div>
			<div class="frm_table">
				<p>Mis Archivos<span class="ayuda">   <a href="#openHelpFiles"><img src="help.png"/></a></span></p>
				<form action="" method="post" id="form_table">
					<table id="files_details">
						<thead>
							<tr>
								<th>Nombre</th>
								<th>Autor</th>
								<th>Fecha subido</th>
								<th>Tamaño</th>
								<th>Propietario</th>
								<th>Descripción</th>
								<th>Clasificación</th>
								<th>Eliminar</th>
								<th>Compartir</th>
							</tr>
						</thead>
						<tbody>
							<?php echo readFileData(); ?>
							<?php echo readSharedFileData(); ?>
						</tbody>
					</table>
				</form>
			</div>
		</div>
	<?php elseif ($action == "help"): ?>
		<div id="ayuda">
		</div>
		</div>
	<?php endif ?>
	<?php if ($share): ?>
		<div id="openModal" class="modalDialog">
			<div class="divModal modalCompartir">
				<h3>Compartir Archivo</h3>
				<form action="" method="post" id="form_share">
					<select name="cmbUserName" id="">
						<?php echo getUserNames(); ?>
					</select><br><br>
					<div class="btn_modal">
						<input type="submit" name="btnShare" value="Compartir">
						<input class="btn_cancelar" type="submit" value="Cancelar">
					</div>
				</form>
			</div>
		</div>
	<?php endif ?>
	<div id="openHelpRegister" class="modalDialog2">
		<div class="divModal modalAyuda">
			<a href="#close" title="Close" class="close">X</a>
			<h2 class="titulo">Registrarse</h2>
			<p>En esta sección debe registrarse para poder ingresar al sistema</p>
			<span style="font-weight: bold">¿Cómo me registro?</span><p>Solo debes llenar la información solicitada en cada uno de los cuadros y presionar el botón Registrarse.</p>
		</div>
	</div>
	<div id="openHelpAdd" class="modalDialog2">
		<div class="divModal modalAyuda modalAdd">
			<a href="#close" title="Close" class="close">X</a>
			<h2 class="titulo">Agregar Archivos</h2>
			<p>Esta sección es para subir un nuevo archivo.</p>
			<span style="font-weight: bold">¿Cómo agrego un nuevo archivo?</span><p>Solo debes llenar la información solicitada en cada uno de los cuadros, seleccionar el archivo deseado y presionar subir archivo.</p>
			<p><span style="font-weight: bold">Importante:</span> Recuerda que solo puedes subir archivos de Excel</p>
		</div>
	</div>
	<div id="openHelpFiles" class="modalDialog2">
		<div class="divModal modalAyuda modalFiles">
			<a href="#close" title="Close" class="close">X</a>
			<h2 class="titulo">Mis Archivos</h2>
			<p>En esta sección puedes ver los archivos que has subido y los que han compartido contigo. También puedes eliminar o compartir un archivo.</p>
			<span style="font-weight: bold">¿Cómo elimino un archivo?</span><p>Solo debes presionar el botón de eliminar que se encuentra disponible en la misma línea del archivo que deseas eliminar.</p>
			<p><span style="font-weight: bold">Importante:</span> Recuerda que debes presionar el botón que se encuentra en la misma fila del archivo deseado.</p>

			<span style="font-weight: bold">¿Cómo comparto un archivo?</span><p>Solo debes presionar el botón de compartir que se encuentra disponible en la misma línea del archivo que deseas compartir.<br><br>
			Una vez presiones el botón de Compartir, te saldrá un pequeño cuadro en el que debes seleccionar el usuario con el que deseas Compartir el archivo y presionar el botón de Compartir.<br><br>
			Si cambias de opinión y no deseas Compartir el archivo, solo debes presionar Cancelar para regresar a la pagina principal.</p>
			<p><span style="font-weight: bold">Importante:</span> Recuerda que debes presionar el botón que se encuentra en la misma fila del archivo deseado.<br>
			Solo puedes compartir los archivos que has subido, por lo que NO puedes compartir los archivos que han sido compartidos contigo.<br><br>
			Una vez hayas compartido el archivo, la única manera de quitar el acceso compartido es eliminando el archivo.</p>
		</div>
	</div>
	</main>
</body>
</html>