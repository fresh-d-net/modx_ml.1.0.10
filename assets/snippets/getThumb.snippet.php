<?php
$s_img = isset($img) ? $img : false;
$s_thumb_opt = isset($options) ? $options : 'w_20,h_20';

if($s_thumb_opt && $s_img)
	$_res = ModExt::getThumb($s_img, $s_thumb_opt);
else
	$_res = 'image undefined';

echo $_res;
?>