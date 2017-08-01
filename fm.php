<?php
/**
 * PHP File Manager
 * Solve the problem of garbled
 */
$oswebcharset = array(
	'zh-CN' => 'GBK',
);
$is_otherdir_forbidden = false;
$osweblanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5); //zh-CN,zh;q=0.8,

#初始化
session_start();
define('ROOT', webroot ());
define('FMROOT', strtolower(dirname($_SERVER['SCRIPT_FILENAME'])) . DIRECTORY_SEPARATOR);
setoswebcharset ();
function checkPW() {
	$pw = isset($_POST['pw']) ? $_POST['pw'] : '';
	if (! isset($_SESSION['pw'])) {
		if ($pw == '像我这样重感情的人') {
			$_SESSION['pw'] = true;
			return true;
		}
	} else {
		return true;
	}
}

function setoswebcharset () {
	global $oswebcharset;
	global $osweblanguage;
	global $os_charset;
	global $web_charset;
	$os_charset = $web_charset = 'UTF-8';
	if (isset($_POST['os_charset'])) {
		$os_charset = $_SESSION['os_charset'] = isset($_POST['os_charset']) ? $_POST['os_charset'] : '';
	} if (isset($_SESSION['os_charset'])) {
		$os_charset = $_SESSION['os_charset'];
	} elseif (PHP_OS == 'WINNT') {
		$os_charset = $oswebcharset[$osweblanguage];
	}
}
function webroot () {
	return $_SERVER['CONTEXT_DOCUMENT_ROOT'];
}
function osfromweb ($string) {
	global $os_charset;
	global $web_charset;
	if ($os_charset != $web_charset) {
		$string = mb_convert_encoding($string, $os_charset , $web_charset);
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
function ostoweb ($string) {
	global $os_charset;
	global $web_charset;
	if ($os_charset != $web_charset) {
		$string = mb_convert_encoding($string, $web_charset , $os_charset);
	}
	return $string;
}
function osfiletoweb ($string, $fromcharset) {
	global $web_charset;
	if ($fromcharset != $web_charset) {
		$string = mb_convert_encoding($string, $web_charset , $fromcharset);
	}
	return $string;
}
define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00).chr(0x00).chr(0xFE).chr(0xFF));
define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE).chr(0x00).chr(0x00));
define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE).chr(0xFF));
define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF).chr(0xFE));
define ('UTF8_BOM'               , chr(0xEF).chr(0xBB).chr(0xBF));
function get_encoding($text){
    $first2 = mb_substr($text, 0, 2);
    $first3 = substr($text, 0, 3);
    $first4 = mb_substr($text, 0, 4);
    if ($first3 == UTF8_BOM) return 'UTF-8'; // WITH BOM
    elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
    elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
    elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
    elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
    else return false;
}


if (checkPW()) {
	$act = isset($_GET['act']) ? $_GET['act'] : '';
	$path = isset($_GET['path']) ? $_GET['path'] : FMROOT;
	$os_path = osfromweb ($path);
	$getfiletype = isset($_GET['filetype']) ? $_GET['filetype'] : 'dir';
	$file_charset = isset($_POST['file_charset']) ? $_POST['file_charset'] : '';
	if (stripos($path, ROOT) === false && $is_otherdir_forbidden) {
		echo 'The dir is not accessible';exit;
	}
	
	$mkdir = isset($_POST['mkdir']) ? $_POST['mkdir'] : '';
	if ($mkdir != '') {
		$mkdir = osfromweb ($mkdir);
		mkdir ($mkdir);
	}
	$file = isset($_FILES["file"]) ? $_FILES["file"] : '';
	if ($file != '' && $file['error'] == 0) {
		$file['name'] = osfromweb ($file['name']);
		move_uploaded_file($file['tmp_name'], $os_path . $file['name']);
	}
	
	$filecontent = isset($_POST['filecontent']) ? $_POST['filecontent'] : '';
	if ($filecontent != '') {
		file_put_contents($os_path, $filecontent);
	}
	if ($act == 'download') {
		$path_array = explode('/', $path);
		$filename = end($path_array);
		header('Content-Type: ' . mime_content_type($os_path));
		header('Content-Length: '. filesize($os_path));
		header('Content-Disposition: attachment; filename=' . $filename );
		ob_flush();
		readfile($os_path);
		exit;
	}
}
	
?>
<?php if(! checkPW()):?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
</head>
<body>
<form method="post">
	<input name="pw" placeholder="password" /><br />
	<input type="submit" />
</form>
</body>
</html>
<?php else: ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
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
	width:30px;
}
.filelist .filename{
	width:400px;
}
.filelist .filemtime{
	width:200px;
}
</style>
</head>
<body>
<a href="?path=<?php echo preg_replace('/\/$/', '', str_replace('\\', '/', dirname($path))) . '/'; ?>">Parent</a>
<?php if ($getfiletype == 'dir'): ?>
<form method="post" enctype="multipart/form-data">
	<input name="os_charset" placeholder="charset" value="<?php echo $os_charset; ?>" /><br />
	The defalut OS File system charset is utf-8, if your php run in Windows OS, you maybe need set Windows OS local file system charset.<br />
	The Php File Manager guess from your browser accept language(<?php echo $osweblanguage; ?>), if not right, please write the current charset.<br />
	<input name="mkdir" placeholder="mkdir" /><br />
	<input name="file" type="file" /><br />
	<input type="submit" />
</form>
	<table class="filelist">
		<tr class=""><th class="imgtype"></th><th class="filename">Name</th><th class="filemtime">Size</th><th class="filemtime">Time</th><th class="filemtime">Download</th></tr>
<?php
if (is_dir($os_path)) {
	if ($dh = opendir($os_path)) {
		$i=0;
		while (($filename = readdir($dh)) !== false) {
			if ($filename =='.' || $filename =='..' || ! file_exists($os_path . $filename)) {
				continue;
			}
			$i++;
			$os_filepath = $os_path . $filename;
			$filetype = filetype($os_filepath);
			$filemtime = date('Y-m-d H:i:s', filemtime($os_filepath));
			$filesize = filesize($os_filepath);
			$is_writeable = is_writeable($os_filepath);
$del =  '';
if (isset($_GET['del']) && $_GET['del'] ==$i) {
	if (unlink($os_filepath)) {
		$del =  'del success';
	} else {
		$del =  'del false';
	}
}
			$filename = ostoweb ($filename);
			$filepath = percentosfromweb($path . $filename);
			if ($filetype =='dir') {
				$filepath = $filepath . '/';
				$imgtype = '&#x1f4c1;';
				$file_download = ' --- ';
				$filesize = ' --- ';
			} else {
				$filepath = $filepath . '&filetype=file';
				$imgtype = '&#x1f4c4;';
				$file_download = "<a href=\"?act=download&path=$filepath\">Download</a>";
			}
			echo "<tr class=\"$filetype\"><td class=\"imgtype\">$imgtype</td><td class=\"filename\"><a href=\"?path=$filepath\">$filename</a></td><td class=\"filemtime\">$filesize</td><td class=\"filemtime\">$filemtime</td><td class=\"filename\">$file_download</td></tr>\r\n";
		}
		closedir($dh);
	}
}
?>
	</table>
<?php endif; ?>
<?php if ($getfiletype == 'file'): ?>
<?php

	$filecontent =  file_get_contents($os_path);
	if (! $file_charset) {
		$file_charset = get_encoding($filecontent);
	}
	if ($file_charset) {
		$filecontent =  osfiletoweb($filecontent, $file_charset);
	}
?>
<form method="post" enctype="multipart/form-data">
	<input name="file_charset" placeholder="charset" value="<?php echo $file_charset ? $file_charset : $os_charset; ?>" /><br />
	The defalut OS File system charset is utf-8, if your php run in Windows OS, you maybe need set Windows OS local file system charset.<br />
	The Php File Manager guess from your browser accept language(<?php echo $osweblanguage; ?>), if not right, please write the current charset.<br />
	<input type="submit" />
</form>
<form method="post" enctype="multipart/form-data">
	<textarea name="filecontent" style="width:100%;height:300px;"><?php echo htmlentities($filecontent); ?></textarea><br />
	<input type="submit" />
</form>
<?php endif; ?>
</body>
</html>
<?php endif; ?>