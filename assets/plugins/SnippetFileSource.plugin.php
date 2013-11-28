<?php

/**
 * @name FileSource
 * @version 0.1
 *
 * @description Позволяет хранить сниппеты в виде файлов
 *
 * @author Maxim Mukharev
 * @install
 * Привязываем к следующим событиям:
 *  - OnSnipFormRender
 *  - OnBeforeSnipFormSave
 *  - OnSnipFormPrerender
 */

global $_lang;

$e = $this->Event;

$output = '';

/**
 * Подготовка информации перед рендером формы редактирования сниппета
 */

switch ($e->name) {
        case 'OnSnipFormPrerender':
                global $content;
                if(substr(trim($content['snippet']),0,49) == 'return include MODX_BASE_PATH . \'assets/snippets/'){
					    $content['file_binding'] = str_replace(array(';','\''),'',trim(substr(trim($content['snippet']),49,250)));
                        $snippetPath = MODX_BASE_PATH . 'assets/snippets/' . $content['file_binding'];
                        $content['snippet'] = file_get_contents($snippetPath);
                        if ( strncmp($content['snippet'], "<?", 2) == 0 ) { // strip out PHP tags (from save_snippet.processor.php)
                                $content['snippet'] = substr($content['snippet'], 2);
                                if ( strncmp( $content['snippet'], "php", 3 ) == 0 ) $content['snippet'] = substr($content['snippet'], 3);
                                if ( substr($content['snippet'], -2, 2) == '?>' ) $content['snippet'] = substr($content['snippet'], 0, -2);
                } else {
                        $content['file_binding'] = '';
                }
                        $_SESSION['itemname']=$content['name'];
                } elseif (substr(trim($content['snippet']),0,7) == '//@FILE'){ // Added by Carw
                        $content['file_binding'] = str_replace(';','',trim(substr(trim($content['snippet']),7,250)));
                        $snippetPath = MODX_BASE_PATH . 'assets/snippets/' . $content['file_binding'];
                        $content['snippet'] = file_get_contents($snippetPath);
                        if ( strncmp($content['snippet'], "<?", 2) == 0 ) { // strip out PHP tags (from save_snippet.processor.php)
                                $content['snippet'] = substr($content['snippet'], 2);
                                if ( strncmp( $content['snippet'], "php", 3 ) == 0 ) $content['snippet'] = substr($content['snippet'], 3);
                                if ( substr($content['snippet'], -2, 2) == '?>' ) $content['snippet'] = substr($content['snippet'], 0, -2);
                } else {
                        $content['file_binding'] = '';
                }
                $_SESSION['itemname']=$content['name'];
                } else {
                        $_SESSION['itemname']="New snippet";
                }

                break;
        case 'OnSnipFormRender':
                global $content;
                $output = '
                        <script type="text/javascript">
                                mE1 = new Element("tr");
                                mE11 = new Element("td",{"align":"left","styles":{"padding-top":"14px"}});
                                mE12 = new Element("td",{"align":"left","styles":{"padding-top":"14px"}});
                                mE122 = new Element("input",{"name":"filebinding","type":"text","maxlength":"45","value":"'.$content['file_binding'].'","class":"inputBox","styles":{"width":"300px","margin-left":"14px"},"events":{"change":function(){documentDirty=true;}}});

                                mE11.appendText("Привязанный файл:");
                                mE11.inject(mE1);
                                mE122.inject(mE12);
                                mE12.inject(mE1);

                                setPlace = $("displayparamrow");

                                mE1.inject(setPlace,"after");

                        </script>
                ';
                break;
        case 'OnBeforeSnipFormSave':
                if(!empty($_POST['filebinding'])) {
                        global $snippet;
                        $pathsnippet = trim($this->db->escape($_POST['filebinding']));
                        $fullpathsnippet = MODX_BASE_PATH . 'assets/snippets/' . $pathsnippet;

                        if($fl = @fopen($fullpathsnippet,'w')) {
                                fwrite($fl, $_POST['post']);
                                fclose($fl);
                                $snippet = $this->db->escape('return include MODX_BASE_PATH . \'assets/snippets/' . $pathsnippet . '\';');
                        }
                }
                break;
}

if($output != '') {
        $e->output($output);
}