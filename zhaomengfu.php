<?php
/**
 * PHP File Manager About �����\
 */

$path = 'f:/fm/test/';
if (is_dir($path) && $dh = opendir($path)) {
	while (($filename = readdir($dh)) !== false) {
		if ($filename =='.' || $filename =='..') {
			continue;
		}

		$filepath = $path . $filename;

		var_dump($filename);
		var_dump(file_exists($filename));//�������\��Ч
		var_dump(filetype($filename));//���� //�������\��Ч
		var_dump(filesize($filename));///�������\��Ч
		var_dump(filemtime($filename));//�������\��Ч
		//var_dump(file_get_contents($filename));//�ļ�����Ч
		//var_dump(rename($filename, $path . 'test'));//�ļ�����Ч

		var_dump($filepath);
		var_dump(file_exists($filepath));//�������\��Ч
		var_dump(filetype($filepath));//�������\ ��Ч
		var_dump(filesize($filepath));//�������\��Ч
		var_dump(filemtime($filepath));//�������\��Ч
		var_dump(file_get_contents($filepath));//�������\ ��Ч
		var_dump(rename($filepath, $path . 'test'));//�������\ ���ļ���Ч ���ļ���Ч��Ч
	}
}
