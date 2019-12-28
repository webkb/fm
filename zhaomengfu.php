<?php
/**
 * PHP File Manager About 赵孟頫
 */

$path = 'f:/fm/test/';
if (is_dir($path) && $dh = opendir($path)) {
	while (($filename = readdir($dh)) !== false) {
		if ($filename =='.' || $filename =='..') {
			continue;
		}

		$filepath = $path . $filename;

		var_dump($filename);
		var_dump(file_exists($filename));//对赵孟頫无效
		var_dump(filetype($filename));//混淆 //对赵孟頫无效
		var_dump(filesize($filename));///对赵孟頫无效
		var_dump(filemtime($filename));//对赵孟頫无效
		//var_dump(file_get_contents($filename));//文件名无效
		//var_dump(rename($filename, $path . 'test'));//文件名无效

		var_dump($filepath);
		var_dump(file_exists($filepath));//对赵孟頫无效
		var_dump(filetype($filepath));//对赵孟頫 有效
		var_dump(filesize($filepath));//对赵孟頫无效
		var_dump(filemtime($filepath));//对赵孟頫无效
		var_dump(file_get_contents($filepath));//对赵孟頫 有效
		var_dump(rename($filepath, $path . 'test'));//对赵孟頫 单文件有效 多文件有效无效
	}
}
