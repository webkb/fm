<?php
/**
 * PHP File Manager
 */

	$path_array = explode('/', $user_path);
	$path_array[6] = real_path($path_array[6]);
	$path = implode('/', $path_array);
