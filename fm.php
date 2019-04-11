<?php
/**
 * PHP File Manager
 */
session_start();

define('FMROOT', strtolower(dirname($_SERVER['SCRIPT_FILENAME'])));

function checkPW () {
	if (isset($_SESSION['pw'])) {
		return true;
	} elseif (isset($_POST['pw']) && $_POST['pw'] == '123456') {
		$_SESSION['pw'] = true;
		return true;
	} else {
		return false;
	}
}
function url_percent ($string) {
	$percent = array (
		'#' => '%23',
		'&' => '%26',
		'+' => '%2b',
	);
	return strtr($string, $percent);
}
function ge_htmlspecialchars ($string) {
	$percent = array (
		'<' => '&lt;',
		'>' => '&gt;',
		'&' => '&amp;',
		'"' => '&quot;',
	);
	return strtr($string, $percent);
}
function ge_basename ($path) {
	$path_array = explode('/', $path);
	return end($path_array);
}
function put_os ($string) {
	$os_encoding = $_SESSION['os_encoding'];
	$charset = 'UTF-8';
	if ($os_encoding != $charset) {
		$string = mb_convert_encoding($string, $os_encoding , $charset);
	}
	return $string;
}
function get_os ($string) {
	$os_encoding = $_SESSION['os_encoding'];
	$charset = 'UTF-8';
	if ($os_encoding != $charset) {
		$string = mb_convert_encoding($string, $charset, $os_encoding);
	}
	return $string;
}
function put_file ($string) {
	$file_encoding = $_SESSION['file_encoding'];
	$charset = 'UTF-8';
	if ($file_encoding != $charset) {
		$string = mb_convert_encoding($string, $file_encoding , $charset);
	}
	return $string;
}
function get_file ($string) {
	$file_encoding = $_SESSION['file_encoding'];
	$charset = 'UTF-8';
	if ($file_encoding != $charset) {
		$string = mb_convert_encoding($string, $charset, $file_encoding);
	}
	return $string;
}
function url_add_query($key, $value, $urlLinkChar = '=', $firstUrlLinkChar = '?', $urlDivChar = '&')
{
	$url = $_SERVER['REQUEST_URI'];
	$replacement	= $key . $urlLinkChar . $value;
	if (false != strpos($url, $key)) {
		$pattern		= '/' . $key . str_replace('/', '\/', $urlLinkChar) . '?([^#&=]*)/';
		$url			= preg_replace($pattern, $replacement, $url);
	} elseif (false != strpos($url, $firstUrlLinkChar)) {
		$url = $url . $urlDivChar . $replacement;
	} else {
		$url = $url . $firstUrlLinkChar . $replacement;
	}
	return $url;
}

if (checkPW()) {

	if (isset($_GET['os_encoding'])) {
		$os_encoding = $_SESSION['os_encoding'] = $_GET['os_encoding'];
	} elseif (isset($_SESSION['os_encoding'])) {
		$os_encoding = $_SESSION['os_encoding'];
	} else {
		$os_encoding = 'GB2312';
	}
	if (isset($_GET['file_encoding'])) {
		$file_encoding = $_SESSION['file_encoding'] = $_GET['file_encoding'];
	} elseif (isset($_SESSION['file_encoding'])) {
		$file_encoding = $_SESSION['file_encoding'];
	} else {
		$file_encoding = 'UTF-8';
	}

	$path = isset($_GET['path']) ? $_GET['path'] : FMROOT;
	$os_path = put_os($path);

	if (stripos($os_path, FMROOT) === false && true) {
		echo 'The dir is not accessible <a href="javascript:history.back();">Back</a>';
		exit;
	}

	$mkdir = isset($_POST['mkdir']) ? $_POST['mkdir'] : '';
	if ($mkdir != '') {
		mkdir($os_path . '/' . put_os($mkdir));
	}

	$file = isset($_FILES["file"]) ? $_FILES["file"] : '';
	if ($file != '' && $file['error'] == 0) {
		move_uploaded_file($file['tmp_name'], $os_path . '/' . put_os($file['name']));
	}

	$filecontent = isset($_POST['filecontent']) ? $_POST['filecontent'] : '';
	if ($filecontent != '') {
		file_put_contents($path, put_file($filecontent));
	}

	$act = isset($_GET['act']) ? $_GET['act'] : 'opendir';
	if ($act == 'download') {
		header('Content-Type: ' . mime_content_type($os_path));
		header('Content-Length: '. filesize($os_path));
		header('Content-Disposition: attachment; filename=' . ge_basename($path));
		readfile($os_path);
		exit;
	}

	$rename = isset($_POST['rename']) ? $_POST['rename'] : '';
	if ($act == 'rename' && $rename) {
		$repath = dirname($os_path) . '/' . put_os($rename);
		rename($os_path, $repath);
	}
}
?>
<?php if(! checkPW()):?>
<!doctype html>
<html>
<head>
<title></title>
<meta charset="utf-8" />
</head>
<body>
<form method="post">
	<input name="pw" placeholder="password" /><br />
	<input type="submit" />
</form>
</body>
</html>
<?php elseif ($act == 'opendir'): ?>
<?php if (is_dir($os_path) && $dh = opendir($os_path)): ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<style>
body {
	font-size:12px;
}
.hover {
	color: #FFF;
	background: #4F5B93;
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
.filelist .fileimg{
	width:30px;
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
<a href="?path=<?php echo url_percent(dirname($path)); ?>">Parent</a>
<a<?php if ($os_encoding=='GB2312'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('os_encoding', 'GB2312'); ?>">GB2312</a>
<a<?php if ($os_encoding=='UTF-8'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('os_encoding', 'UTF-8'); ?>">UTF-8</a>
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
		<th class="fileact">Delete</th>
	</tr>
<?php
	while (($filename = readdir($dh)) !== false) {
		$filepath = $os_path . '/' . $filename;

		if ($filename =='.' || $filename =='..' || ! file_exists($filepath)) {
			continue;
		}

		$filetype = filetype($filepath);
		$filesize = filesize($filepath);
		$filemtime = date('Y-m-d H:i:s', filemtime($filepath));
		$is_writeable = is_writeable($filepath);

$delete =  '';
if (isset($_GET['delete']) && put_os($_GET['delete']) == $filename) {
	if (unlink($filepath)) {
		$delete =  ' (Delete success) ';
	} else {
		$delete =  ' (Delete false) ';
	}
} elseif (isset($_GET['rmdir']) && put_os($_GET['rmdir']) == $filename) {
	if (rmdir($filepath)) {
		$delete =  ' (Delete success) ';
	} else {
		$delete =  ' (Delete false) ';
	}
}
		$filename = get_os($filename);	
		$url_path = url_percent(get_os($os_path));	
		$url_filepath = url_percent(get_os($filepath));	
		$url_filename = url_percent($filename);	
		if ($filetype =='dir') {
			$file_act = 'opendir';
			$file_img = '&#x1f4c1;';
			$filesize = ' --- ';
			$file_download = ' --- ';
			$file_delete = "<a href=\"?path=$url_path&rmdir=$url_filename\">Delete$delete</a>";
		} else {
			$file_act = 'openfile';
			$file_img = '&#x1f4c4;';
			$file_download = "<a href=\"?path=$url_filepath&act=download\">Download</a>";
			$file_delete = "<a href=\"?path=$url_path&delete=$url_filename\">Delete$delete</a>";
		}
		echo "<tr class=\"$filetype\">
		<td class=\"fileimg\">$file_img</td>
		<td class=\"filename\"><a href=\"?path=$url_filepath&act=$file_act\">$filename</a></td>
		<td class=\"filemtime\">$filesize</td>
		<td class=\"filemtime\">$filemtime</td>
		<td class=\"fileact\">$file_download</td>
		<td class=\"fileact\"><a href=\"?path=$url_filepath&act=rename\">Rename</a></td>
		<td class=\"fileact\">$file_delete</td>
		</tr>\r\n";
	}
	closedir($dh);
?>
</table>
</body>
</html>
<?php else: ?>
The dir is not right <a href="javascript:history.back();">Back</a>
<?php endif; ?>
<?php elseif ($act == 'openfile'):

	$filecontent = get_file(file_get_contents($os_path));
	$filecontent =  ge_htmlspecialchars($filecontent);
?>
<!doctype html>
<html>
<head>
<title></title>
<meta charset="utf-8" />
<style>
body {
	font-size:12px;
}
.hover {
	color: #FFF;
	background: #4F5B93;
}
</style>
</head>
<body>
<a href="?path=<?php echo url_percent(dirname($path)); ?>">Parent</a>
<a<?php if ($file_encoding=='GB2312'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('file_encoding', 'GB2312'); ?>">GB2312</a>
<a<?php if ($file_encoding=='UTF-8'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('file_encoding', 'UTF-8'); ?>">UTF-8</a>
<form method="post">
	<textarea name="filecontent" style="width:100%;height:400px;"><?php echo $filecontent; ?></textarea><br />
	<input type="submit" />
</form>
</body>
</html>
<?php elseif ($act == 'rename'):
	$rename =  ge_basename($path);
?>
<html>
<head>
<title></title>
<meta charset="utf-8" />
</head>
<body>
<a href="?path=<?php echo url_percent(dirname($path)); ?>">Parent</a>
<form method="post">
	<input name="rename" placeholder="rename" value="<?php echo $rename; ?>" /><br />
	<input type="submit" />
</form>
</body>
</html>
<?php endif; ?>