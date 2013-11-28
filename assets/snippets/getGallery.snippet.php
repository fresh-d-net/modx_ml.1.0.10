<?php
//Settings
$s_out_tpl = isset($out) ? $modx->getTpl($out) : 'none';
$s_row_tpl = isset($row) ? $modx->getTpl($row) : 'none';
$s_img_row_tpl = isset($img) ? $modx->getTpl($img) : 'none';

$i_id = isset($id) ? $id : $modx->documentIdentifier;
$s_thumb = isset($thumb) ? $thumb : 'h_102';
$i_tv_id = isset($tv) ? $tv : 1;

$s_fields = isset($fields) ? $fields : 'id, longtitle, introtext';
$s_order_by = isset($order_by) ? $order_by : 'sc.menuindex';//'IF( sc.pub_date !=0, sc.pub_date, sc.createdon )';
$s_order_type = isset($order_type) ? $order_type : 'ASC';
$i_limit = isset($limit) ? $limit : false;
$i_offset = isset($offset) ? $offset : false;
if($i_limit && !$i_offset) $i_offset = 0;

$full_size = isset($full_size) ? $full_size : 'w_100';
$thumb_size = isset($thumb_size) ? $thumb_size : 'w_100';

$s_action = isset($action) ? $action : '';

$a_res = array();

//additional js-libraries
$s_js_lib = $modx->getChunk('additional_libraries');
$modx->regClientStartupScript($s_js_lib);

//query
$a_publications = $modx->getDocumentWithTv(
	"parent = {$i_id}", // condition
	$s_fields, //request fields
	$i_tv_id,
	$s_order_by, //order by
	$s_order_type, //order type
	($i_limit && isset($i_offset)) ? "{$i_offset}, {$i_limit}" : '' //limit
);


if(!$a_publications) return false;

$i_counter = 1; $a_res = array();

foreach($a_publications as $a_item){
	$a_item_tv = json_decode($a_item['images']);
	$a_img_res = array();

	if(is_array($a_item_tv)){
		foreach($a_item_tv as $item_tv){

//			$s_thumb_url = $modx->getThumb($item_tv[0], $s_thumb);
//			$a_thumb = file_exists(MODX_BASE_PATH . $s_thumb_url) ? getimagesize(MODX_BASE_PATH . $s_thumb_url) : array();
			$a_replacer = array(
				'counter' => $i_counter,
				'img.title' => $item_tv[0]
			);

			$a_replacer['img.src.full'] = $modx->getThumb($item_tv[1], $full_size);
			$a_replacer['img.src.thumb'] = $modx->getThumb($item_tv[1], $thumb_size);
			$a_img_res[] = ModExt::setPlaceHolders($s_img_row_tpl, $a_replacer);
		}
	}

	$a_replacer = array(
		'longtitle' => $a_item['longtitle'],
		'content' => $a_item['content'],
		'price' => $a_item['price'],
		'img.wrapper' => implode("\n", $a_img_res),
		'counter' => $i_counter
	);

	$a_res[] = ModExt::setPlaceHolders($s_row_tpl, $a_replacer);

	$i_counter++;
}


$s_list = 'none';
if($a_res){
	$s_list = ModExt::setPlaceHolders($s_out_tpl, array('wrapper'=> implode("\n", $a_res)));
}

return $s_list;
?>