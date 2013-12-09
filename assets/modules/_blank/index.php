<?php
/**
 * Create blank-module:
 * <?return include_once(MODX_BASE_PATH.'assets/modules/_blank/index.php');?>
 * Created by fedo
 * Date: 08.11.13
 * Time: 11:18 * 
 */


$s_module_dir_path = str_replace(MODX_BASE_PATH, '', dirname(__FILE__)) . '/';//	assets/modules/productImport
$s_document_tpl = $modx->getTpl("@TPL:{$s_module_dir_path}tpl/document.tpl");
$s_form_tpl = $modx->getTpl("@TPL:{$s_module_dir_path}tpl/form.tpl");

$a_content = array();

$a_doc_ph = array();

//get exist placeholders
$_a = ModExt::getPlaceHolders($s_document_tpl);
foreach($_a as $_item){	$a_doc_ph[$_item] = '';}//set it to empty value

//fill placeholders
$a_doc_ph['direction'] = (isset($modx->config['manager_direction']) && $modx->config['manager_direction'] == 'rtl') ? 'dir="rtl"' : '';
$a_doc_ph['lang'] =  isset($modx->config['manager_lang_attribute']) ? "lang=\"{$modx->config['manager_lang_attribute']}\" xml:lang=\"{$modx->config['manager_lang_attribute']}\"" : '';
$a_doc_ph['manager_theme'] = $modx->config['manager_theme'];
$a_doc_ph['title'] = isset($module_title) ? $module_title : '';
$a_doc_ph['description'] = isset($module_description) ? $module_description : '';

//Form
$a_form_ph = array();
$a_form_ph['moduleid'] = intval($_REQUEST['id']);
$a_form_ph['modulea'] = intval($_REQUEST['a']);
$a_content[] = ModExt::setPlaceHolders($s_form_tpl, $a_form_ph);

$a_content[] = "<pre>" . print_r($_REQUEST, true) . "</pre>";

$a_doc_ph['content'] = implode("\n", $a_content);
$s_document = ModExt::setPlaceHolders($s_document_tpl, $a_doc_ph);

return $s_document;
