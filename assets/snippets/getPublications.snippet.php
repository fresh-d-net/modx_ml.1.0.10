<?php
//[[getPublications? &prefix=`services` &thumb=`w_300,h_127` &tv=`8` &fields=`menutitle,introtext`]]

if(isset($prefix)){
	$row = "{$prefix}_row";
	$out = "{$prefix}_out";

	if(!isset($suffix)){
		$suffix = "_tpl";
	}

	$row = $row . $suffix;
	$out = $out . $suffix;
}

$s_row_tpl = isset($row) ? $modx->getTpl($row) : 'не задан шаблон $row';
$s_out_tpl = isset($out) ? $modx->getTpl($out) : 'не задан шаблон $out';

$tv = isset($tv) ? $tv : false;
$i_limit = isset($limit) ? $limit : false;
$i_offset = isset($offset) ? $offset : 0;
$s_order_by = isset($order_by) ? $order_by : 'sc.menuindex';
$s_order_type = isset($order_type) ? $order_type : 'DESC';

$s_action = isset($action) ? $action : '';
$s_thumb_opt = isset($thumb) ? $thumb : 'w_300,h_127'; //thumb parameters
$i_char_limit = isset($char_limit) ? $char_limit : '';
$fields = isset($fields) ? $fields : array('id', 'menutitle', 'introtext', 'pub_date', 'createdon');//request fields

$i_id = isset($id) ? $id : $this->documentIdentifier;

switch($s_action){
	case 'template':
		$s_condition = "template = {$i_id} AND sc.published=1 AND sc.deleted=0";
		break;
	case 'sub_parent':
		$s_id_list = implode(',', $this->getChildIds($i_id, 1));
		$s_condition = "parent IN ({$s_id_list}) AND sc.published=1 AND sc.deleted=0";
		break;
	case 'parent':
		$s_condition = "parent = {$i_id} AND sc.published=1 AND sc.deleted=0";
		break;
	default:
		$s_condition = "parent = {$i_id} AND sc.published=1 AND sc.deleted=0";
		break;
}

$a_data = $modx->getDocumentWithTv(
	$s_condition, // condition
	$fields,
	$tv, // tv-id
	$s_order_by, //order by
	$s_order_type, //order type
	($i_limit && isset($i_offset)) ? "{$i_offset}, {$i_limit}" : '' //limit
);

$a_res = array();

if($a_data){
	$a_ph = ModExt::getPlaceHolders($s_row_tpl);

	foreach($a_data as $id => $a_item){
		//
		if(in_array('image', $a_ph)){
			$a_item['image'] = $s_thumb_opt ? ModExt::getThumb($a_item['image'], $s_thumb_opt) : $a_item['image'];
		}

		if(in_array('images', $a_ph)){
			$a_item_img = json_decode($a_item['images']);

			if(is_array($a_item_img)){
				list($a_item['images_title'], $a_item['images']) = $a_item_img[0];
				$a_item['images'] = $s_thumb_opt ? ModExt::getThumb($a_item['images'], $s_thumb_opt) : $a_item['images'];
			}
		}

		if(in_array('url', $a_ph)){
			$a_item['url'] = $this->makeUrl($id);
		}

		//
		if(in_array('date', $a_ph)){
			$s_date = empty($a_item['pub_date']) ? $a_item['createdon'] : $a_item['pub_date'];
			$a_item['date'] = ModExt::getTextDate($s_date);
		}

		//
		$a_res[] = ModExt::setPlaceHolders($s_row_tpl, $a_item);
	}
}

//Snippet settings
$a_settings = array(
	'id' => $i_id,
	'row' => $row,
	'out' => $out,
	'tv'=> $tv,
	'thumb' =>  $thumb,
	'order_type' => $s_order_type,
	'offset'=> intval($limit) + intval($offset),
	'limit' => $limit
);

if($modx->is_ajax){
	$_a = array();
	$_a['settings'] = $a_settings;
	$_a['html'] = implode("\n", $a_res);
	$s_res = json_encode($_a);
}else{
	$s_res = ModExt::setPlaceHolders($s_out_tpl, array('wrapper' => implode("\n", $a_res), 'settings' => htmlspecialchars(json_encode($a_settings))));
}

return $s_res;
?>