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
	$htmlchar = array (
		'<' => '&lt;',
		'>' => '&gt;',
		'&' => '&amp;',
		'"' => '&quot;',
	);
	return strtr($string, $htmlchar);
}
function ge_basename ($path) {
	$path_array = explode('/', $path);
	return end($path_array);
}
function real_path ($string) {
	$path_encoding = $_SESSION['path_encoding'];
	$charset = 'UTF-8';
	if ($path_encoding != $charset) {
		$string = mb_convert_encoding($string, $path_encoding , $charset);
	}
	return $string;
}
function user_path ($string) {
	$path_encoding = $_SESSION['path_encoding'];
	$charset = 'UTF-8';
	if ($path_encoding != $charset) {
		$string = mb_convert_encoding($string, $charset, $path_encoding);
	}
	return $string;
}
function real_text ($string) {
	$text_encoding = $_SESSION['text_encoding'];
	$charset = 'UTF-8';
	if ($text_encoding != $charset) {
		$string = mb_convert_encoding($string, $text_encoding , $charset);
	}
	return $string;
}
function user_text ($string) {
	$text_encoding = $_SESSION['text_encoding'];
	$charset = 'UTF-8';
	if ($text_encoding != $charset) {
		$string = mb_convert_encoding($string, $charset, $text_encoding);
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

	if (isset($_GET['path_encoding'])) {
		$path_encoding = $_SESSION['path_encoding'] = $_GET['path_encoding'];
	} elseif (isset($_SESSION['path_encoding'])) {
		$path_encoding = $_SESSION['path_encoding'];
	} else {
		$path_encoding = $_SESSION['path_encoding'] = 'GBK';
	}
	if (isset($_GET['text_encoding'])) {
		$text_encoding = $_SESSION['text_encoding'] = $_GET['text_encoding'];
	} elseif (isset($_SESSION['text_encoding'])) {
		$text_encoding = $_SESSION['text_encoding'];
	} else {
		$text_encoding = $_SESSION['text_encoding'] = 'UTF-8';
	}

	$user_path = isset($_GET['path']) ? $_GET['path'] : FMROOT;
	$path = real_path($user_path);

	if (stripos($path, FMROOT) === false && true) {
		echo 'The dir is not accessible <a href="javascript:history.back();">Back</a>';
		exit;
	}

	if (isset($_POST['mkdir']) && $_POST['mkdir'] != '') {
		mkdir($path . '/' . real_path($_POST['mkdir']));
	}

	if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
		$file = $_FILES["file"];
		move_uploaded_file($file['tmp_name'], $path . '/' . real_path($file['name']));
	}

	if (isset($_POST['filecontent']) && $_POST['filecontent'] != '') {
		file_put_contents($path, real_text($_POST['filecontent']));
	}

	$act = isset($_GET['act']) ? $_GET['act'] : 'opendir';
	if ($act == 'download') {
		header('Content-Type: ' . mime_content_type($path));
		header('Content-Length: '. filesize($path));
		header('Content-Disposition: attachment; filename=' . ge_basename($user_path));
		readfile($path);
		exit;
	}

	if (isset($_POST['rename']) && $_POST['rename'] != '') {
		rename($path, dirname($path) . '/' . real_path($_POST['rename']));
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
<?php if (is_dir($path) && $dh = opendir($path)): ?>
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
.filelist .filedelete{
	width:150px;
}
</style>
</head>
<body>
<a href="?path=<?php echo url_percent(dirname($user_path)); ?>">Parent</a>
Path encoding:
<a<?php if ($path_encoding=='UTF-8'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('path_encoding', 'UTF-8'); ?>">UTF-8</a>
<a<?php if ($path_encoding=='GBK'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('path_encoding', 'GBK'); ?>">GBK</a>
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
		$filepath = $path . '/' . $filename;

		if ($filename =='.' || $filename =='..' || ! file_exists($filepath)) {
			continue;
		}

		$filetype = filetype($filepath);
		$filesize = filesize($filepath);
		$filemtime = date('Y-m-d H:i:s', filemtime($filepath));

$delete =  '';
if (isset($_GET['delete']) && real_path($_GET['delete']) == $filename) {
	if (unlink($filepath)) {
		$delete =  ' (Delete success) ';
	} else {
		$delete =  ' (Delete false) ';
	}
} elseif (isset($_GET['rmdir']) && real_path($_GET['rmdir']) == $filename) {
	if (rmdir($filepath)) {
		$delete =  ' (Delete success) ';
	} else {
		$delete =  ' (Delete false) ';
	}
}
		$user_filename = user_path($filename);
		$url_filename = url_percent($user_filename);	
		$url_path = url_percent($user_path);
		$url_filepath = url_percent(user_path($filepath));	

		if ($filetype =='dir') {
			$file_act = 'opendir';
			$file_img = '&#x1f4c1;';
			$filesize = ' --- ';
			$file_download = ' --- ';
			$file_delete = "<a href=\"?path=$url_path&delete=$url_filename\">Delete$delete</a>";
		} else {
			$file_act = 'openfile';
			$file_img = '&#x1f4c4;';
			$file_download = "<a href=\"?path=$url_filepath&act=download\">Download</a>";
			$file_delete = "<a href=\"?path=$url_path&delete=$url_filename\">Delete$delete</a>";
		}
		echo "<tr class=\"$filetype\">
		<td class=\"fileimg\">$file_img</td>
		<td class=\"filename\"><a href=\"?path=$url_filepath&act=$file_act\">$user_filename</a></td>
		<td class=\"filemtime\">$filesize</td>
		<td class=\"filemtime\">$filemtime</td>
		<td class=\"fileact\">$file_download</td>
		<td class=\"fileact\"><a href=\"?path=$url_filepath&act=rename\">Rename</a></td>
		<td class=\"filedelete\">$file_delete</td>
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
	$filecontent = user_text(file_get_contents($path));
	$filecontent = ge_htmlspecialchars($filecontent);
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
<a href="?path=<?php echo url_percent(dirname($user_path)); ?>">Parent</a>
Text encoding:
<a<?php if ($text_encoding=='UTF-8'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('text_encoding', 'UTF-8'); ?>">UTF-8</a>
<a<?php if ($text_encoding=='GBK'): ?> class="hover"<?php endif; ?> href="<?php echo url_add_query('text_encoding', 'GBK'); ?>">GBK</a>
<form method="post">
	<textarea name="filecontent" style="width:100%;height:400px;"><?php echo $filecontent; ?></textarea><br />
	<input type="submit" />
</form>
</body>
</html>
<?php elseif ($act == 'rename'):
	$rename = ge_basename($user_path);
?>
<html>
<head>
<title></title>
<meta charset="utf-8" />
</head>
<body>
<a href="?path=<?php echo url_percent(dirname($user_path)); ?>">Parent</a>
<form method="post">
	<input name="rename" placeholder="rename" value="<?php echo $rename; ?>" /><br />
	<input type="submit" />
</form>
</body>
</html>
<?php endif; ?>