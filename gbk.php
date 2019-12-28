<?php
/**
 * PHP File Manager Only GBK
 */
session_start();

define('FMROOT', strtolower(dirname($_SERVER['SCRIPT_FILENAME'])));

function checkPW () {
	if (isset($_SESSION['pw'])) {
		return true;
	} elseif (isset($_POST['pw']) && $_POST['pw'] == '像我这样重感情的人') {
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
//下函数为编码文档中的HTML标记以及双引号
//原函数不支持GBK，GB2312又支持不全，如“镕”字
function ge_htmlspecialchars ($string) {
	$percent = array (
		'<' => '&lt;',
		'>' => '&gt;',
		'&' => '&amp;',
		'"' => '&quot;',
	);
	return strtr($string, $percent);
}
//原函数在多字节字符集会有BUG
function ge_basename ($path) {
	$path_array = explode('/', $path);
	return end($path_array);
}
if (checkPW()) {

	$path = isset($_GET['path']) ? $_GET['path'] : FMROOT;

	if (stripos($path, FMROOT) === false && true) {
		echo 'The dir is not accessible <a href="javascript:history.back();">Back</a>';
		exit;
	}

	$mkdir = isset($_POST['mkdir']) ? $_POST['mkdir'] : '';
	if ($mkdir != '') {
		mkdir($path . '/' . $mkdir);
	}

	$file = isset($_FILES["file"]) ? $_FILES["file"] : '';
	if ($file != '' && $file['error'] == 0) {
		move_uploaded_file($file['tmp_name'], $path . '/' . $file['name']);
	}

	$filecontent = isset($_POST['filecontent']) ? $_POST['filecontent'] : '';
	if ($filecontent != '') {
		file_put_contents($path, $filecontent);
	}

	$act = isset($_GET['act']) ? $_GET['act'] : 'opendir';
	if ($act == 'download') {
		header('Content-Type: ' . mime_content_type($path));
		header('Content-Length: '. filesize($path));
		header('Content-Disposition: attachment; filename=' . ge_basename($path));
		readfile($path);
		exit;
	}
	$rename = isset($_POST['rename']) ? $_POST['rename'] : '';
	if ($act == 'rename' && $rename) {
		$repath = dirname($path) . '/' . $rename;
		rename($path, $repath);
	}
	if ($act == 'do_nothing') {
		echo 'php error <a href="javascript:history.back();">Back</a>';
		exit;
	}
}
header("Content-type: text/html; charset=GB2312");
?>
<?php if(! checkPW()):?>
<!doctype html>
<html>
<head>
<title></title>
<meta charset="GB2312" />
<style>
body {
	font-size:12px;
}
</style>
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
<meta charset="GB2312" />
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

		if ($filename =='.' || $filename =='..') {
			continue;
		}
		if (file_exists($filepath)) {

		$filetype = filetype($filepath);
		$filesize = filesize($filepath);
		$filemtime = date('Y-m-d H:i:s', filemtime($filepath));

$delete =  '';
if (isset($_GET['delete']) && $_GET['delete'] == $filename) {
	if (unlink($filepath)) {
		$delete =  '（Delete success）';
	} else {
		$delete =  '（Delete false）';
	}
} elseif (isset($_GET['rmdir']) && $_GET['rmdir'] == $filename) {
	if (rmdir($filepath)) {
		$delete =  '（Delete success）';
	} else {
		$delete =  '（Delete false）';
	}
}

		$url_path = url_percent($path);	
		$url_filepath = url_percent($filepath);
		$url_filename = url_percent($filename);
		} else {
			$filetype = 'php_error';
		}
		if ($filetype == 'dir') {
			$file_act = 'opendir';
			$file_img = '&#x1f4c1;';
			$filesize = ' --- ';
			$file_download = ' --- ';
			$file_rename = "<a href=\"?path=$url_filepath&act=rename\">Rename</a>";
			$file_delete = "<a href=\"?path=$url_path&rmdir=$url_filename\">Delete$delete</a>";
		} elseif ($filetype == 'file') {
			$file_act = 'openfile';
			$file_img = '&#x1f4c4;';
			$file_download = "<a href=\"?path=$url_filepath&act=download\">Download</a>";
			$file_rename = "<a href=\"?path=$url_filepath&act=rename\">Rename</a>";
			$file_delete = "<a href=\"?path=$url_path&delete=$url_filename\">Delete$delete</a>";
		} elseif ($filetype == 'php_error')  {
			$file_act = 'do_nothing';
			$file_img = '&#x1f4d2;';
			$filesize = ' --- ';
			$filemtime = ' --- ';
			$file_download = " --- ";
			$file_rename = " --- ";
			$file_delete = " --- ";
		}
		echo "<tr class=\"$filetype\">
		<td class=\"fileimg\">$file_img</td>
		<td class=\"filename\"><a href=\"?path=$url_filepath&act=$file_act\">$filename</a></td>
		<td class=\"filemtime\">$filesize</td>
		<td class=\"filemtime\">$filemtime</td>
		<td class=\"fileact\">$file_download</td>
		<td class=\"fileact\">$file_rename</td>
		<td class=\"fileact\">$file_delete</td>
		</tr>\r\n";
	}
	closedir($dh);
?>
</table>
</body>
</html>
<?php else: ?>
<!doctype html>
<html>
<head>
<title></title>
<meta charset="GB2312" />
<style>
body {
	font-size:12px;
}
</style>
</head>
<body>
The dir is not right <a href="javascript:history.back();">Back</a>
</body>
</html>
<?php endif; ?>
<?php elseif ($act == 'openfile'):
//文档编辑
	$filecontent = file_get_contents($path);
	$filecontent =  ge_htmlspecialchars($filecontent);
?>
<!doctype html>
<html>
<head>
<title></title>
<meta charset="GB2312" />
<style>
body {
	font-size:12px;
}
</style>
</head>
<body>
<a href="?path=<?php echo url_percent(dirname($path)); ?>">Parent</a>
<form method="post">
	<textarea name="filecontent" style="width:100%;height:400px;"><?php echo $filecontent; ?></textarea><br />
	<input type="submit" />
</form>
</body>
</html>
<?php elseif ($act == 'rename'):
//文档重命名
	$rename =  ge_basename($path);
?>
<html>
<head>
<title></title>
<meta charset="GB2312" />
<style>
body {
	font-size:12px;
}
</style>
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