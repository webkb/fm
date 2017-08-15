<?php
/**
 * PHP File Manager
**/
$charset = array(
	'UTF-8',
	'GB2312',
);
// $charset value Must be in uppercase,gbk must be GB2312 http://php.net/manual/zh/function.htmlspecialchars.php
$default_encoding = $charset[0];
$is_otherdir_forbidden = false;
define('ROOT', $_SERVER['CONTEXT_DOCUMENT_ROOT'] || $_SERVER['DOCUMENT_ROOT']);
define('FMROOT', dirname($_SERVER['SCRIPT_FILENAME']));

session_start();

function file_from_default ($string) {
	global $file_encoding;
	global $default_encoding;
	if ($file_encoding != $default_encoding) {
		$string = mb_convert_encoding($string, $file_encoding , $default_encoding);
	}
	return $string;
}
function file_to_default ($string) {
	global $file_encoding;
	global $default_encoding;
	if ($file_encoding != $default_encoding) {
		$string = mb_convert_encoding($string, $default_encoding , $file_encoding);
	}
	return $string;
}
function ge_htmlspecialchars($string) {
	global $default_encoding;
	return htmlspecialchars ($string, ENT_COMPAT, $default_encoding);
}
function stripper($string) { 
    if (get_magic_quotes_gpc()){ 
        $string = stripslashes($string); 
    } 
    return $string; 
} 
function percentosfromweb ($string) {
	$percent = array (
		'#' => '%23',
		'&' => '%26',
		'+' => '%2b',
	);
	return strtr($string, $percent);
}

function checkPW() {
	$pw = isset($_POST['pw']) ? $_POST['pw'] : '';
	if (! isset($_SESSION['pw'])) {
		if ($pw == '123456') {
			$_SESSION['pw'] = true;
			return true;
		}
	} else {
		return true;
	}
}

function mb_basename($path) {
	$path_array = explode('/', $path);
	$path = end($path_array);
	$path_array = explode('\\', $path);
	return end($path_array);
}


if (checkPW()) {

	$path = isset($_GET['path']) ? $_GET['path'] : FMROOT;
	if (stripos($path, ROOT) === false && $is_otherdir_forbidden) {
		echo 'The dir is not accessible';exit;
	}

	$act = isset($_GET['act']) ? $_GET['act'] : 'dir';
	
	$mkdir = isset($_POST['mkdir']) ? $_POST['mkdir'] : '';
	if ($mkdir != '') {
		mkdir($path . DIRECTORY_SEPARATOR . $mkdir);
	}
	$file = isset($_FILES["file"]) ? $_FILES["file"] : '';
	if ($file != '' && $file['error'] == 0) {
		move_uploaded_file($file['tmp_name'], $path . DIRECTORY_SEPARATOR . $file['name']);
	}

	if (isset($_GET['default_encoding'])) {
		$default_encoding = $_SESSION['default_encoding'] = $_GET['default_encoding'];
	} elseif (isset($_SESSION['default_encoding'])) {
		$default_encoding = $_SESSION['default_encoding'];
	}
	$file_encoding = isset($_GET['file_encoding']) ? $_GET['file_encoding'] : $default_encoding;
	header('Content-Type:text/html; charset=' . $default_encoding);
	$file_content = isset($_POST['file_content']) ? $_POST['file_content'] : '';
	if ($file_content != '') {
		$file_content = stripper($file_content);
		$file_content = file_from_default($file_content);
		file_put_contents($path, $file_content);
	}

	$filename = isset($_GET['filename']) ? $_GET['filename'] : '';
	if ($act == 'download') {
		header('Content-Type: ' . mime_content_type($path));
		header('Content-Length: '. filesize($path));
		header('Content-Disposition: attachment; filename=' . mb_basename($path));
		readfile($path);
		exit;
	}

	$rename = isset($_POST['rename']) ? $_POST['rename'] : '';
	if ($act == 'rename' && $rename) {
		$repath = dirname($path) . DIRECTORY_SEPARATOR . $rename;
		rename($path, $repath);
	}
}
?>
<!doctype html>
<html>
<head>
<meta charset="<?php echo $default_encoding; ?>" />
<style>
body {
	font-size:12px;
}
.filelist tr {
	line-height:30px;
}
.filelist tr:nth-of-type(odd) {
	background-color: #fafafa;
}
.filelist tr:hover {
	background-color: #f0f9ff;
}
.filelist th {
	text-align:left;
	background-color: #f0f9ff;
}
.filelist .imgtype{
	width:40px;
	text-align:center;
}
.filelist .filename{
	width:400px;
}
.filelist .filemtime{
	width:150px;
}
.filelist .fileact{
	width:100px;
}
</style>
</head>
<body>
<?php if(! checkPW()):?>
<form method="post">
	<input name="pw" placeholder="password" /><br />
	<input type="submit" />
</form>
<?php else: ?>
<a href="?path=<?php echo preg_replace('/\\\\$/', '', dirname($path)) ; ?>">Parent</a>
<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&default_encoding=GB2312">GB2312</a>
<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&default_encoding=UTF-8">UTF-8</a>
<?php if ($act == 'dir'): ?>
<form method="post" enctype="multipart/form-data">
	<input name="mkdir" placeholder="mkdir" /><br />
	<input name="file" type="file" /><br />
	<input type="submit" />
</form>
	<table class="filelist">
		<tr class="">
			<th class="imgtype"></th>
			<th class="filename">Name</th>
			<th class="filemtime">Size</th>
			<th class="filemtime">Time</th>
			<th class="fileact">Download</th>
			<th class="fileact">Rename</th>
			<th class="filemtime">Delete</th>
		</tr>
<?php
if (is_dir($path)) {
	if ($dh = opendir($path)) {
		while (($filename = readdir($dh)) !== false) {
			if ($filename =='.' || $filename =='..' || ! file_exists($filepath = $path . DIRECTORY_SEPARATOR . $filename)) {
				continue;
			}

			$filetype = filetype($filepath);
			$filemtime = date('Y-m-d H:i:s', filemtime($filepath));
			$filesize = filesize($filepath);
			$is_writeable = is_writeable($filepath);
			
			$delete =  '';
			if (isset($_GET['delete']) && $_GET['delete'] == $filename) {
				if (is_file($filepath) && unlink($filepath)) {
					$delete =  'file del success';
				} elseif (is_dir($filepath) && rmdir($filepath)) {
					$delete =  'dir del success';
				} else {
					$delete =  'del false';
				}
			}
			$filepath = percentosfromweb($filepath);
			if ($filetype =='dir') {
				$imgtype = '&#x1f4c1;';
				$file_download = ' --- ';
				$filesize = ' --- ';
			} else {
				$imgtype = '&#x1f4c4;';
				$file_download = "<a href=\"?act=download&path=$filepath&filename=$filename\">Download</a>";
			}
			echo "<tr class=\"$filetype\">
			<td class=\"imgtype\">$imgtype</td>
			<td class=\"filename\"><a href=\"?act=$filetype&path=$filepath\">$filename</a></td>
			<td class=\"filemtime\">$filesize</td><td class=\"filemtime\">$filemtime</td>
			<td class=\"fileact\">$file_download</td>
			<td class=\"fileact\"><a href=\"?act=rename&path=$filepath&filename=$filename\">Rename</a></td>
			<td class=\"filemtime\"><a href=\"?path=$path&delete=$filename\">Delete</a> $delete</td>
			</tr>\r\n";
		}
		closedir($dh);
	}
}
?>
	</table>
<?php endif; ?>
<?php if ($act == 'file'): ?>
<?php
	$file_content = file_get_contents($path);
	$file_content = file_to_default($file_content);
	$file_content = ge_htmlspecialchars($file_content);

?>
<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&file_encoding=GB2312">GB2312</a>
<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&file_encoding=UTF-8">UTF-8</a>
<form method="post">
	<textarea name="file_content" style="width:100%;height:550px;"><?php echo $file_content; ?></textarea><br />
	<input type="submit" />
</form>
<?php endif; ?>
<?php if ($act == 'rename'): ?>
<?php
	$rename =  mb_basename($path);
?>
<form method="post">
	<input name="rename" placeholder="rename" value="" /><br />
	the old filename: <?php echo $rename; ?>
	<input type="submit" />
</form>
<?php endif; ?>
<?php endif; ?>
</body>
</html>