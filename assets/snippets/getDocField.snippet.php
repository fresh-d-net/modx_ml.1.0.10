<?php

$top_level = isset($top_level) ? $top_level : false ;
$fields = isset($fields) ? $fields : false;
$tvs = isset($tvs) ? explode(',', str_replace(' ', '', $tvs)) : false;
$id = isset($id) ? $id : $modx->documentIdentifier;

$s_tpl = isset($tpl) ? $this->getTpl($tpl) : false;

if($top_level)
	$i_id = $modx->runSnippet('UltimateParent', array('topLevel'=>$top_level));
else
	$i_id = $id; 

$field_res = array();

if($fields){
	$field_res = $modx->getDocument($i_id, $fields);
}

if($tvs){
	if($a_data = $modx->getTemplateVars($tvs, "*", $docid= $i_id)){
		foreach($a_data as $_item){
			$tv_field_res[$_item['name']] = $_item['value'];
		}
	}
}

if($s_tpl){
	if(is_array($field_res))
		$field_res = ModExt::setPlaceHolders($s_tpl, $field_res);

	if(is_array($tv_field_res))
		$field_res = ModExt::setPlaceHolders($s_tpl, $tv_field_res);

}else{

	if($fields)
		$field_res = $field_res[$fields];
	else{
		$field_res = $tv_field_res[$tvs[0]];
	}


}

return $field_res;
?>
