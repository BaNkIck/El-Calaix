<?php
/**
 * Script per llistar tots els fitxers i directoris a partir d'una ruta
 * especificada ordenats per la última data de modificació.
 * 
 * Aquest script pot ajudar a detectar quins fitxers han estat modificats
 * per algun un virus o similar.
 * 
 * @author Aaron Navarro Heras
 * @version 1.0
 */

/**
 * Àlies de la constant de PHP que conté el separador de directoris.
 */
define ("DS", DIRECTORY_SEPARATOR);

/**
 * Retorna el llistat de fitxers i directoris de la ruta especificada
 * 
 * @param string $basepath Ruta base
 * @param boolean $recursive Escanejar subdirectoris (recursiu)
 * @param boolean $hidden Escanejar fitxers i directoris ocults
 * @return array
 */
function scanPath($basepath, $recursive = false, $hidden = false){
	
	$results = array();
	$files   = scandir($basepath);

	foreach ($files as $file){
		
		if (in_array($file, array(".", ".."))) continue;
		if (!$hidden && preg_match("/^\./", $file)) continue;
		
		$path = $basepath.DS.$file;
		
		$result = array(
			"path"  => $path,
			"mtime" => filemtime($path),
			"perms" => fileperms($path),
		);

		$results[] = $result;

		if ($recursive){
			if (is_dir($path)){
				$subdir_results = scanPath($path, $recursive);
				$results = array_merge($results, $subdir_results);
			}
		}
	}

	return $results;

}

/**
 * Callback per ordenar els resultats de la funció scanPath per la data de la
 * última modificació.
 * 
 * Aquesta funció s'hauria d'utilitzar com a callback de la funció 'usort' de
 * PHP.
 * 
 * @param array $a Element a comparar 'A'
 * @param array $b Element a comparar 'B'
 * @return int
 */
function sortByModifiedTime($a, $b){
	if ($a["mtime"] < $b["mtime"]) return 1;
	elseif ($a["mtime"] > $b["mtime"]) return -1;
	else return 0;
}

// Paràmetres
$path      = !empty($_GET["path"]) ? $_GET["path"] : ".";
$recursive = $_GET["recursive"] == 1 ? true : false;
$hidden    = $_GET["hidden"] == 1 ? true : false;

// Escanegem...
$files = scanPath($path, $recursive, $hidden);

// I ordenem
usort($files, "sortByModifiedTime");

?>
<!DOCTYPE html>
<html>
<head>
<title>Last Modified Files</title>
<style type="text/css">

html, body{
	margin: 0;
	padding: 0;
}

body{
	color: #333;
	font-family: Arial, Verdana;
	font-size: 11px;

	padding: 10px;
}

fieldset{
	border: 1px solid #ccc;
	padding-top: 10px;
	width: 500px;
}

legend{
	color: #666;
	font-size: 13px;
	font-weight: bold;
}

table thead td{
	font-weight: bold;
}

table tr td{
	background: #eee;
	padding: 5px 10px;
	white-space: nowrap;
}

footer{
	border-top: 1px solid #ccc;
	color: #999;

	clear: both;
	margin-top: 20px;
	padding: 20px 0;
}

.form-element{
	display: block;
	line-height: 25px;
	overflow: hidden;
	margin-bottom: 1px;
}


.form-element .label{
	font-weight: bold;

	float: left;
	margin-right: 10px;
	padding: 0 5px;
	width: 100px;
}

.form-element .element{
	float: left;
}

.form-element.input .element input{
	border: 1px solid #ccc;
}

.form-element.radio .element label{
	padding-left: 3px;
}

.form-element.radio .element input+label+input{
	margin-left: 10px;
}

.form-element.submit{
	background: #f6f6f6;
	border-top: 1px solid #ddd;
	padding: 5px 0;
}

.form-element.submit .element{
	margin-left: 120px;
}

</style>
</head>
<body>

	<fieldset>
		<legend>Scanning Options</legend>
		<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="get">
			<div class="form-element input">
				<div class="label">
					<label for="path">Path:</label>
				</div>
				<div class="element input">
					<input type="text" name="path" id="path" value="<?php echo $path; ?>" size="70" />
				</div>
			</div>
			<div class="form-element radio">
				<div class="label">
					<label for="recursive">Recursive:</label>
				</div>
				<div class="element">
					<input type="radio" name="recursive" id="recursive0" value="0"<?php echo !$recursive ? " checked" : "" ?> /><label for="recursive0">No</label>
					<input type="radio" name="recursive" id="recursive1" value="1"<?php echo $recursive ? " checked" : "" ?> /><label for="recursive1">Yes</label>
				</div>
			</div>
			<div class="form-element radio">
				<div class="label">
					<label for="hidden">Hidden:</label>
				</div>
				<div class="element">
					<input type="radio" name="hidden" id="hidden0" value="0"<?php echo !$hidden ? " checked" : "" ?> /><label for="hidden0">No</label>
					<input type="radio" name="hidden" id="hidden1" value="1"<?php echo $hidden ? " checked" : "" ?> /><label for="hidden1">Yes</label>

				</div>
			</div>
			<div class="form-element submit">
				<div class="element">
					<input type="submit" value="Scan path" />
				</div>
			</div>
		</form>
	</fieldset>
	
	<p>Scanned Path: <?php echo realpath($path); ?><br /><?php echo count($files); ?> results sorted by last modified time.</p>
	
	<table cellspacing="1">
		<thead>
			<tr>
				<td>Path</td>
				<td>Last modified time</td>
				<td>Permissions</td>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($files as $file): ?>
			<tr>
				<td><?php echo $file["path"]; ?></td>
				<td><?php echo strftime("%Y-%m-%d %X", $file["mtime"]); ?></td>
				<td><?php echo substr(sprintf('%o', $file["perms"]), -4);; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<footer>&copy; 2012  - Aaron Navarro</footer>

</body>
</html>