<?php
/**
 * Created by fedo
 * Date: 27.12.12
 * Time: 10:31
 * Расширения для modx
 */
 
class ModExt  extends DocumentParser{

	var $is_ajax = false;

	function ModExt($is_ajax = false) {
        global $database_server;
        if(substr(PHP_OS,0,3) === 'WIN' && $database_server==='localhost') $database_server = '127.0.0.1';
        $this->loadExtension('DBAPI') or die('Could not load DBAPI class.'); // load DBAPI class
        $this->dbConfig= & $this->db->config; // alias for backward compatibility

		/*fedo (studio-fresh)*/
		if(!$is_ajax){
			$this->jscripts= array ();
			$this->sjscripts= array ();
			$this->loadedjscripts= array ();
			// events
			$this->event= new SystemEvent();
			$this->Event= & $this->event; //alias for backward compatibility
			$this->pluginEvent= array ();
		}else{
			 // get the settings
			if (empty ($this->config)) {
				$this->getSettings();
			}
		}
		/*End fedo (studio-fresh)*/

        // set track_errors ini variable
        @ ini_set("track_errors", "1"); // enable error tracking in $php_errormsg
        $this->error_reporting = 1;
    }

	/** Метод расставляет плейсхолдеры в указанной строке
	 * Controller::setPlaceHolders( (str)sTpl, array('key'=> 'val') [, (str)prefix] [, (str)suffix])
	 * Пример вызова:
	 * [code]
	 *      Controller::setPlaceHolders('some [+wrapper+] in text', array('wrapper'=>'tag'));// some tag in text
	 * [/code]
	 *
	 *
	*/
	public static function setPlaceHolders($sTpl, $aPlaceHolders, $prefix = '[+', $suffix = '+]'){
		if(is_array($aPlaceHolders)){

			reset($aPlaceHolders);
			while (list($key, $value)= each($aPlaceHolders)) {
				$sTpl = str_replace($prefix.$key.$suffix, $value, $sTpl);
			}
			return $sTpl;
		}
	}



	/**
	 * Получает значения плейсхолдеров из строки
	 * @param  $sTpl
	 * @param string $prefix
	 * @param string $suffix
	 * @return array()
	 */
	public static function getPlaceHolders($sTpl, $prefix = '[+', $suffix = '+]') {

		$sPrefix = $prefix; $sSufix = $suffix; $sLash_pattern = '\~\+\[\]\$\!';

	//Проэкранировали спецсимволы
		$sLashPrefix = addcslashes($sPrefix, $sLash_pattern);
		$sLashSufix = addcslashes($sSufix, $sLash_pattern);
	//End Проэкранировали спецсимволы

		preg_match_all('~' . $sLashPrefix . '(.*?)' . $sLashSufix . '~', $sTpl, $aPlaceHolders, PREG_PATTERN_ORDER);

		return isset($aPlaceHolders[1]) ? $aPlaceHolders[1] : false;

	}



	/**
	 *
	 * Получает поля документа с тв-параметрами
	 */
	public function getDocumentWithTv ($s_doc_condition, $field_list, $tv_list = false, $order_by='sc.menuindex', $order_type = 'ASC', $limit = ''){
		$a_res = array();

		//Проверяем данные
		if(is_string($field_list)){//необходимо получить масив
			$field_list = str_replace(" ", "", $field_list);
			$field_list = explode(",", $field_list);
		}

		if($tv_list){
			//Необходимо получить строку
			if(is_array($tv_list))$tv_list = implode(", ", $tv_list);

			//Составляем SQL
			$s_sql = "
				SELECT sc.*, IF(tvc.value !='', tvc.value, tv.default_text) as value, tvtpl.`tmplvarid`  FROM {$this->getFullTableName('site_content')} as sc \n

				LEFT JOIN {$this->getFullTableName('site_tmplvar_templates')} as tvtpl ON (tvtpl.`templateid`= sc.`template` AND tvtpl.tmplvarid IN ($tv_list)) \n
				LEFT JOIN {$this->getFullTableName('site_tmplvar_contentvalues')} as tvc ON (tvc.`contentid` = sc.`id` AND tvtpl.tmplvarid = tvc.`tmplvarid` AND tvc.`tmplvarid` IN ($tv_list)) \n
				LEFT JOIN {$this->getFullTableName('site_tmplvars')} as tv ON (tvtpl.`tmplvarid`= tv.`id` AND tv.id IN ($tv_list)) \n

				WHERE sc.{$s_doc_condition} \n
				ORDER BY {$order_by} {$order_type}
			";
		}else{

			//Составляем SQL
			$s_sql = "
					SELECT sc.*  FROM {$this->getFullTableName('site_content')} as sc

					WHERE sc.{$s_doc_condition}
					ORDER BY {$order_by} {$order_type}
			";
		}
		
		//Добавляем лимит, если установлен
		if($limit) $s_sql .= "LIMIT {$limit}";

		$result = $this->db->query($s_sql);

		while( $a_row = $this->db->getRow($result) ){
				foreach($field_list as $item_field){
					$a_res[$a_row['id']][$item_field] = $a_row[$item_field];
				}
				if(isset($a_row['tmplvarid']))
					$a_res[$a_row['id']]['tv'][$a_row['tmplvarid']] = $a_row['value'];
		}

		return $a_res;
	}



	/**
	 * @param  $sAliasPath
	 * @return mixed
	 * Метод преобразует алиас в путь
	 */
	public function getPathFromAlias($sAliasPath){
		if(is_string($sAliasPath)){
			//заменяем расширение (маскируем)
			$aSearcher_ext = array('.inc.php', '.php', '.tpl', '.txt');
			$aReplacer_64 = array();
			foreach($aSearcher_ext as $sExt){
				$aReplacer_64[] = base64_encode($sExt);
			}
			$sAliasPath = str_replace($aSearcher_ext, $aReplacer_64, $sAliasPath);
			//End заменяем расширение

			//Заменяем предустановленные выражения
			$sAliasPath = str_replace(array('.*', '.'), DIRECTORY_SEPARATOR, $sAliasPath);
			$aSearcher = array(
				'base_path',
				'reader_path'
			);
			$aReplacer = array(
				$this->aConfig['base_path'],
				$this->aConfig['reader_path']
			);
			$sAliasPath = str_replace($aSearcher, $aReplacer, $sAliasPath);

			//End Заменяем предустановленные выраения

			//Возвращаем назад замаскированные расширения

			return str_replace($aReplacer_64, $aSearcher_ext, $sAliasPath);
		}
	}




	/**
	 * @static
	 * @param  $s_file_url
	 * @param  $s_options
	 * @return array|string
	 *
	* Метод делает тумбы
	* getThumb( $input='/[+tvimagename+]', $options='h_170,w_255')
	* $options .= "&f=jpg&q=100";
	 */
	public static function getThumb($s_file_url, $s_options){

		//define extensions
		preg_match('/\.[^\.]+$/i',$s_file_url, $ext);
		$s_ext = $ext[0];
		$s_file_hash = md5_file($s_file_url);

		$outputFilename = MODX_BASE_PATH . "/assets/cache/.phpthumb_cache/" . $s_file_hash . "_" . $s_options ."{$s_ext}";
		if (!file_exists($outputFilename)){

			$replace  = array("," => "&", "_" => "=");
			$s_options  = strtr($s_options, $replace);

			$phpThumb = new PhpThumb();
			$phpThumb->setSourceFilename($s_file_url);

			//normalize options
			$_options = explode("&", $s_options);
			$a_options = array();

			foreach ($_options as $value) {
			   list($_key, $_val) = explode("=", $value);
			   $a_options[$_key] = $_val;
			}

			$a_options['f'] = $s_ext;
			$a_options['md5s'] = $s_file_hash;

			foreach($a_options as $_key=>$_val){
				$phpThumb->setParameter($_key, $_val);
			}

			   if ($phpThumb->GenerateThumbnail())
				   $phpThumb->RenderToFile($outputFilename) ;


		}
		$res = explode("/assets", $outputFilename);
		$res = "/assets". $res[1];
		return $res;
	}



	/**
	 * @param  $url
	 * @return mixed
	 *
	 */
	static function curlTo($url, $s_uagent = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.71 Safari/534.24")
    	{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (isset($_SERVER['HTTP_REFERER'])) {
            curl_setopt($curl, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $s_uagent);
        $response = curl_exec($curl);
        // Check if any error occured
        /*if(curl_errno($curl))
        {
            $this->_errors .=  "Curl Error: ".curl_error($curl);
            return false;
        }*/
        curl_close($curl);
        return $response;
    }


	//
	static function urlExists($url=NULL){
		if($url == NULL) return false;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($httpcode>=200 && $httpcode<300){
		    return true;
		} else {
		    return false;
		}
	}



	/**
	 * @static
	 * @param  $s - source string
	 * @param  $i - length of string
	 * @return void
	 */
	static function cutString($s, $i, $charset = 'UTF-8'){
		return $s = (mb_strlen($s, $charset) > $i) ? mb_substr($s, 0, $i, $charset) . '...' : $s;
	}



	/**
	 * normalize string
	 * @static
	 * @param  $s
	 * @return string
	 */
	static function normString($s){
		return ucfirst(strtolower($s));
	}



	static function getTextDate($iUnixtime, $schema='%d %m %Y'){

			$iMonth = date("n", $iUnixtime); // номер месяца
			$iDay = date("j", $iUnixtime); // день
			$iYear = date("Y", $iUnixtime); // год

			$aMonth = array('', 'Январ', 'Феврал', 'Март', 'Апрел', 'Ма', 'Июн', 'Июл', 'Август', 'Сентябр', 'Октябр', 'Ноябр', 'Декабр');
			$sMonth = $aMonth[$iMonth];
			if ( ($iMonth == 3) OR ($iMonth == 8) ){$sEnd = "а";}
			else{$sEnd = "я";}

			$schema = str_replace('%m', $sMonth.$sEnd, $schema);

		    $s_date = strftime($schema, $iUnixtime);

			return $s_date;
	}



	function getSettings() {
        if (!is_array($this->config) || empty ($this->config)) {
            if ($included= file_exists(MODX_BASE_PATH . 'assets/cache/siteCache.idx.php')) {
                $included= include_once (MODX_BASE_PATH . 'assets/cache/siteCache.idx.php');
            }
            if (!$included || !is_array($this->config) || empty ($this->config)) {
                include_once MODX_BASE_PATH . "/manager/processors/cache_sync.class.processor.php";
                $cache = new synccache();
                $cache->setCachepath(MODX_BASE_PATH . "/assets/cache/");
                $cache->setReport(false);
                $rebuilt = $cache->buildCache($this);
                $included = false;
                if($rebuilt && $included= file_exists(MODX_BASE_PATH . 'assets/cache/siteCache.idx.php')) {
                    $included= include MODX_BASE_PATH . 'assets/cache/siteCache.idx.php';
                }
                if(!$included) {
                    $result= $this->db->query('SELECT setting_name, setting_value FROM ' . $this->getFullTableName('system_settings'));
                    while ($row= $this->db->getRow($result, 'both')) {
                        $this->config[$row[0]]= $row[1];
                    }
                }
            }

            // added for backwards compatibility - garry FS#104
            $this->config['etomite_charset'] = & $this->config['modx_charset'];

            // store base_url and base_path inside config array
            $this->config['base_url']= MODX_BASE_URL;
            $this->config['base_path']= MODX_BASE_PATH;
            $this->config['site_url']= MODX_SITE_URL;

            // load user setting if user is logged in
            $usrSettings= array ();
            if ($id= $this->getLoginUserID()) {
                $usrType= $this->getLoginUserType();
                if (isset ($usrType) && $usrType == 'manager')
                    $usrType= 'mgr';

                if ($usrType == 'mgr' && $this->isBackend()) {
                    // invoke the OnBeforeManagerPageInit event, only if in backend
                    $this->invokeEvent("OnBeforeManagerPageInit");
                }

                if (isset ($_SESSION[$usrType . 'UsrConfigSet'])) {
                    $usrSettings= & $_SESSION[$usrType . 'UsrConfigSet'];
                } else {
                    if ($usrType == 'web')
                        $query= $this->getFullTableName('web_user_settings') . ' WHERE webuser=\'' . $id . '\'';
                    else
                        $query= $this->getFullTableName('user_settings') . ' WHERE user=\'' . $id . '\'';
                    $result= $this->db->query('SELECT setting_name, setting_value FROM ' . $query);
                    while ($row= $this->db->getRow($result, 'both'))
                        $usrSettings[$row[0]]= $row[1];
                    if (isset ($usrType))
                        $_SESSION[$usrType . 'UsrConfigSet']= $usrSettings; // store user settings in session
                }
            }
            if ($this->isFrontend() && $mgrid= $this->getLoginUserID('mgr')) {
                $musrSettings= array ();
                if (isset ($_SESSION['mgrUsrConfigSet'])) {
                    $musrSettings= & $_SESSION['mgrUsrConfigSet'];
                } else {
                    $query= $this->getFullTableName('user_settings') . ' WHERE user=\'' . $mgrid . '\'';
                    if ($result= $this->db->query('SELECT setting_name, setting_value FROM ' . $query)) {
                        while ($row= $this->db->getRow($result, 'both')) {
                            $usrSettings[$row[0]]= $row[1];
                        }
                        $_SESSION['mgrUsrConfigSet']= $musrSettings; // store user settings in session
                    }
                }
                if (!empty ($musrSettings)) {
                    $usrSettings= array_merge($musrSettings, $usrSettings);
                }
            }
            $this->error_reporting = $this->config['error_reporting'];
            $this->config= array_merge($this->config, $usrSettings);
        }

		//2013.04.23 - fedo
		//Инициализируем контекст
		global $domain_config;
		$a_context_config = $domain_config;

		$curHost = str_replace('www.', '', $_SERVER['HTTP_HOST']); //домен без www используется как идентификатор контекста
		if(empty($a_context_config[$curHost])){
			die('Context config is empty.');
		}else{
			foreach($a_context_config[$curHost] as $key=>$val){
				$this->config[$key] = $val;
			}
		}

		//End 2013.04.23 - fedo
    }



	function executeParser() {

        //error_reporting(0);
        if (version_compare(phpversion(), "5.0.0", ">="))
            set_error_handler(array (
                & $this,
                "phpError"
            ), E_ALL);
        else
            set_error_handler(array (
                & $this,
                "phpError"
            ));

        $this->db->connect();

        // get settings
        if (empty ($this->config)) {
            $this->getSettings();
        }

		//Check AJAX Request. Fedo (fresh)
		if($this->is_ajax) return;

        // IIS friendly url fix
        if ($this->config['friendly_urls'] == 1 && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false) {
            $url= $_SERVER['QUERY_STRING'];
            $err= substr($url, 0, 3);
            if ($err == '404' || $err == '405') {
                $k= array_keys($_GET);
                unset ($_GET[$k[0]]);
                unset ($_REQUEST[$k[0]]); // remove 404,405 entry
                $_SERVER['QUERY_STRING']= $qp['query'];
                $qp= parse_url(str_replace($this->config['site_url'], '', substr($url, 4)));
                if (!empty ($qp['query'])) {
                    parse_str($qp['query'], $qv);
                    foreach ($qv as $n => $v)
                        $_REQUEST[$n]= $_GET[$n]= $v;
                }
                $_SERVER['PHP_SELF']= $this->config['base_url'] . $qp['path'];
                $_REQUEST['q']= $_GET['q']= $qp['path'];
            }
        }

        // check site settings
        if (!$this->checkSiteStatus()) {
            header('HTTP/1.0 503 Service Unavailable');
            if (!$this->config['site_unavailable_page']) {
                // display offline message
                $this->documentContent= $this->config['site_unavailable_message'];
                $this->outputContent();
                exit; // stop processing here, as the site's offline
            } else {
                // setup offline page document settings
                $this->documentMethod= "id";
                $this->documentIdentifier= $this->config['site_unavailable_page'];
            }
        } else {
            // make sure the cache doesn't need updating
            $this->checkPublishStatus();

            // find out which document we need to display
            $this->documentMethod= $this->getDocumentMethod();
            $this->documentIdentifier= $this->getDocumentIdentifier($this->documentMethod);
        }

        if ($this->documentMethod == "none") {
            $this->documentMethod= "id"; // now we know the site_start, change the none method to id
        }
        if ($this->documentMethod == "alias") {
            $this->documentIdentifier= $this->cleanDocumentIdentifier($this->documentIdentifier);
        }

        if ($this->documentMethod == "alias") {

            // Check use_alias_path and check if $this->virtualDir is set to anything, then parse the path
            if ($this->config['use_alias_path'] == 1) {
                $alias= (strlen($this->virtualDir) > 0 ? $this->virtualDir . '/' : '') . $this->documentIdentifier;
                if (array_key_exists($alias, $this->documentListing)) {
                    $this->documentIdentifier= $this->documentListing[$alias];
                } else {
                    //multisite fedo. 2013.04.23
						$found = false;

						if (array_key_exists( $this->config['culture_key']."/".$alias, $this->documentListing)) {
						//if (array_key_exists( $this->config['site_url'] ."/".$alias, $this->documentListing)) {
						  $this->documentIdentifier = $this->documentListing[$this->config['culture_key'] ."/".$alias];

						  $found = true;
						}

						if(!$found){
						  $this->sendErrorPage();
						}
					//End multisite
                }
            } else {
                $this->documentIdentifier= $this->documentListing[$this->documentIdentifier];
            }
            $this->documentMethod= 'id';
        }


        // invoke OnWebPageInit event
        $this->invokeEvent("OnWebPageInit");

        // invoke OnLogPageView event
        if ($this->config['track_visitors'] == 1) {
            $this->invokeEvent("OnLogPageHit");
        }

        $this->prepareResponse();
    }


    function outputContent($noEvent= false) {

        $this->documentOutput= $this->documentContent;

        if ($this->documentGenerated == 1 && $this->documentObject['cacheable'] == 1 && $this->documentObject['type'] == 'document' && $this->documentObject['published'] == 1) {
    		if (!empty($this->sjscripts)) $this->documentObject['__MODxSJScripts__'] = $this->sjscripts;
    		if (!empty($this->jscripts)) $this->documentObject['__MODxJScripts__'] = $this->jscripts;
        }

        // check for non-cached snippet output
        if (strpos($this->documentOutput, '[!') > -1) {
            $this->documentOutput= str_replace('[!', '[[', $this->documentOutput);
            $this->documentOutput= str_replace('!]', ']]', $this->documentOutput);

            // Parse document source
            $this->documentOutput= $this->parseDocumentSource($this->documentOutput);
    	}

    	// Moved from prepareResponse() by sirlancelot
    	// Insert Startup jscripts & CSS scripts into template - template must have a <head> tag
    	if ($js= $this->getRegisteredClientStartupScripts()) {
    		// change to just before closing </head>
    		// $this->documentContent = preg_replace("/(<head[^>]*>)/i", "\\1\n".$js, $this->documentContent);
    		$this->documentOutput= preg_replace("/(<\/head>)/i", $js . "\n\\1", $this->documentOutput);
    	}

    	// Insert jscripts & html block into template - template must have a </body> tag
    	if ($js= $this->getRegisteredClientScripts()) {
    		$this->documentOutput= preg_replace("/(<\/body>)/i", $js . "\n\\1", $this->documentOutput);
    	}
    	// End fix by sirlancelot

        // remove all unused placeholders
        if (strpos($this->documentOutput, '[+') > -1) {
            $matches= array ();
            preg_match_all('~\[\+(.*?)\+\]~s', $this->documentOutput, $matches);
            if ($matches[0])
                $this->documentOutput= str_replace($matches[0], '', $this->documentOutput);
        }

        $this->documentOutput= $this->rewriteUrls($this->documentOutput);

        // send out content-type and content-disposition headers
        if (IN_PARSER_MODE == "true") {
            $type= !empty ($this->contentTypes[$this->documentIdentifier]) ? $this->contentTypes[$this->documentIdentifier] : "text/html";
            header('Content-Type: ' . $type . '; charset=' . $this->config['modx_charset']);
//            if (($this->documentIdentifier == $this->config['error_page']) || $redirect_error)
//                header('HTTP/1.0 404 Not Found');
            if (!$this->checkPreview() && $this->documentObject['content_dispo'] == 1) {
                if ($this->documentObject['alias'])
                    $name= $this->documentObject['alias'];
                else {
                    // strip title of special characters
                    $name= $this->documentObject['pagetitle'];
                    $name= strip_tags($name);
                    $name= strtolower($name);
                    $name= preg_replace('/&.+?;/', '', $name); // kill entities
                    $name= preg_replace('/[^\.%a-z0-9 _-]/', '', $name);
                    $name= preg_replace('/\s+/', '-', $name);
                    $name= preg_replace('|-+|', '-', $name);
                    $name= trim($name, '-');
                }
                $header= 'Content-Disposition: attachment; filename=' . $name;
                header($header);
            }
        }

        $totalTime= ($this->getMicroTime() - $this->tstart);
        $queryTime= $this->queryTime;
        $phpTime= $totalTime - $queryTime;

        $queryTime= sprintf("%2.4f s", $queryTime);
        $totalTime= sprintf("%2.4f s", $totalTime);
        $phpTime= sprintf("%2.4f s", $phpTime);
        $source= $this->documentGenerated == 1 ? "database" : "cache";
        $queries= isset ($this->executedQueries) ? $this->executedQueries : 0;
        $phpMemory = (memory_get_peak_usage(true) / 1024 / 1024) . " mb";

        $out =& $this->documentOutput;
        if ($this->dumpSQL) {
            $out .= $this->queryCode;
        }
        $out= str_replace("[^q^]", $queries, $out);
        $out= str_replace("[^qt^]", $queryTime, $out);
        $out= str_replace("[^p^]", $phpTime, $out);
        $out= str_replace("[^t^]", $totalTime, $out);
        $out= str_replace("[^s^]", $source, $out);
        $out= str_replace("[^m^]", $phpMemory, $out);
        //$this->documentOutput= $out;

		/*Replace ru-prefix from url  fedo 2013.09.10*/
		$this->documentOutput = preg_replace("#/?[^\"]?\/" . $this->config['culture_key'] . "/#i","/", $this->documentOutput);
		/*End Replace ru-prefix*/

        // invoke OnWebPagePrerender event
        if (!$noEvent) {
            $this->invokeEvent("OnWebPagePrerender");
        }

        echo $this->documentOutput;
        ob_end_flush();
    }



    /**
     * name: parseDocumentSource - used by parser
     * desc: return document source aftering parsing tvs, snippets, chunks, etc.
     */
    function parseDocumentSource($source) {
        // set the number of times we are to parse the document source
        $this->minParserPasses= empty ($this->minParserPasses) ? 2 : $this->minParserPasses;
        $this->maxParserPasses= empty ($this->maxParserPasses) ? 10 : $this->maxParserPasses;
        $passes= $this->minParserPasses;
        for ($i= 0; $i < $passes; $i++) {
            // get source length if this is the final pass
            if ($i == ($passes -1))
                $st= strlen($source);
            if ($this->dumpSnippets == 1) {
                echo "<fieldset><legend><b style='color: #821517;'>PARSE PASS " . ($i +1) . "</b></legend>The following snippets (if any) were parsed during this pass.<div style='width:100%' align='center'>";
            }

            // invoke OnParseDocument event
            $this->documentOutput= $source; // store source code so plugins can
            $this->invokeEvent("OnParseDocument"); // work on it via $modx->documentOutput
            $source= $this->documentOutput;

            // combine template and document variables
            $source= $this->mergeDocumentContent($source);
            // replace settings referenced in document
            $source= $this->mergeSettingsContent($source);
            // replace HTMLSnippets in document
            $source= $this->mergeChunkContent($source);
			// insert META tags & keywords
			$source= $this->mergeDocumentMETATags($source);

			//Заменяем переменные лексикона перед выводом документа. fedo - 2013.04.23
			$this->documentOutput = $source;
			$this->setLexicon();
			$source = $this->documentOutput;
			//End Заменяем переменные лексикона перед выводом документа

            // find and merge snippets
            $source= $this->evalSnippets($source);
            // find and replace Placeholders (must be parsed last) - Added by Raymond
            $source= $this->mergePlaceholderContent($source);
            if ($this->dumpSnippets == 1) {
                echo "</div></fieldset><br />";
            }
            if ($i == ($passes -1) && $i < ($this->maxParserPasses - 1)) {
                // check if source length was changed
                $et= strlen($source);
                if ($st != $et)
                    $passes++; // if content change then increase passes because
            } // we have not yet reached maxParserPasses
        }
        return $source;
    }



	function makeUrl($id, $alias= '', $args= '', $scheme= '') {
        $url= '';
        $virtualDir= '';
        $f_url_prefix = $this->config['friendly_url_prefix'];
        $f_url_suffix = $this->config['friendly_url_suffix'];
        if (!is_numeric($id)) {
            $this->messageQuit('`' . $id . '` is not numeric and may not be passed to makeUrl()');
        }
        if ($args != '' && $this->config['friendly_urls'] == 1) {
            // add ? to $args if missing
            $c= substr($args, 0, 1);
            if (strpos($f_url_prefix, '?') === false) {
                if ($c == '&')
                    $args= '?' . substr($args, 1);
                elseif ($c != '?') $args= '?' . $args;
            } else {
                if ($c == '?')
                    $args= '&' . substr($args, 1);
                elseif ($c != '&') $args= '&' . $args;
            }
        }
        elseif ($args != '') {
            // add & to $args if missing
            $c= substr($args, 0, 1);
            if ($c == '?')
                $args= '&' . substr($args, 1);
            elseif ($c != '&') $args= '&' . $args;
        }
        if ($this->config['friendly_urls'] == 1 && $alias != '') {
            $url= $f_url_prefix . $alias . $f_url_suffix . $args;
        }
        elseif ($this->config['friendly_urls'] == 1 && $alias == '') {
            $alias= $id;
            if ($this->config['friendly_alias_urls'] == 1) {
                $al= $this->aliasListing[$id];

				//Multisite. Deleted en/ or ru/ level. fedo - 2013.04.23
				$context_alias = $this->config['culture_key'];
				$al['path'] = str_replace($context_alias.'/', '',$al['path']);
				//End

                if($al['isfolder']===1 && $this->config['make_folders']==='1')
                    $f_url_suffix = '/';
                $alPath= !empty ($al['path']) ? $al['path'] . '/' : '';
                if ($al && $al['alias'])
                    $alias= $al['alias'];
            }
            $alias= $alPath . $f_url_prefix . $alias . $f_url_suffix;
            $url= $alias . $args;
        } else {
            $url= 'index.php?id=' . $id . $args;
        }

        $host= $this->config['base_url'];
        // check if scheme argument has been set
        if ($scheme != '') {
            // for backward compatibility - check if the desired scheme is different than the current scheme
            if (is_numeric($scheme) && $scheme != $_SERVER['HTTPS']) {
                $scheme= ($_SERVER['HTTPS'] ? 'http' : 'https');
            }

            // to-do: check to make sure that $site_url incudes the url :port (e.g. :8080)
            $host= $scheme == 'full' ? $this->config['site_url'] : $scheme . '://' . $_SERVER['HTTP_HOST'] . $host;
        }

        if ($this->config['xhtml_urls']) {
        	return preg_replace("/&(?!amp;)/","&amp;", $host . $virtualDir . $url);
        } else {
        	return $host . $virtualDir . $url;
        }
    }



	/**
	 * @param  $email
	 * @return bool
	 * метод выполняет валидацию Email.
	 */
	static function checkEmail($email) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL)){
			//проверяем доступность домена на котором находится мейл
			list($username, $domain) = explode('@', $email);
			if(!checkdnsrr($domain, 'MX')) {
				return false;
			}
		}
		return true;
	}



	/**
	* Получает указанное поле документа. fedo - 2013.04.23
	*/
	function getDocField($id=false, $field='pagetitle', $top_level = false){
		$id = $id ? $id : $this->documentIdentifier;

		if($top_level)
			$i_id = $this->ultimateParent($id, false, $top_level);
		else
			$i_id = $id;

		$field_res = $this->getDocument($i_id, $field);
		return $field_res[$field];
	}

	function ultimateParent($id=false, $top=0, $topLevel=0){
		$top= intval($top);
		$id= $id && intval($id) ? intval($id) : $this->documentIdentifier;
		$topLevel= $topLevel && intval($topLevel) ? intval($topLevel) : 0;
		if ($id && $id != $top) {
			$pid= $id;
			if (!$topLevel || count($this->getParentIds($id)) >= $topLevel) {
				while ($parentIds= $this->getParentIds($id, 1)) {
					$pid= array_pop($parentIds);
					if ($pid == $top) {
						break;
					}
					$id= $pid;
					if ($topLevel && count($this->getParentIds($id)) < $topLevel) {
						break;
					}
				}
			}
		}
		return $id;
	}
	//End added fedo



	//2012.04.12 - fedo
	/**
	 * Set lexicon variables
	 * @param int $ck
	 * @return bool
	 */
	function setLexicon($ck=0){//ck - kulture_key
		$ck = $ck? $ck : $ck = $this->config['culture_key'];
		preg_match_all('~\[%(.*?)\]~', $this->documentOutput, $onlyId , PREG_PATTERN_ORDER);
		//распарсивает массив
		//eval(file_get_contents(MODX_BASE_PATH.'assets/lexicon/'.$ck.'.inc.php'));
		include(MODX_BASE_PATH.'assets/lexicon/'.$ck.'.inc.php');

		$searcher = array();
		$replacer = array();
		foreach($onlyId[1] as $key=>$val){
			$searcher[] = $onlyId[0][$key];
			$replacer[] = $lexicon[$val];
			//echo $key.'=>'.$val.'->'.$lexicon[$val];//debug string
		}
		$this->documentOutput = str_replace($searcher, $replacer, $this->documentOutput);
		return true;
	}



	/**
	 * get laxicon value from vocabular
	 * @param  $key
	 * @param string $ck
	 * @return string
	 */
	function getLexicon($key, $ck = ''){//ck - culture_key
		$ck = $ck != '' ? $ck : $this->config['culture_key'];
		$s_file = MODX_BASE_PATH."assets/lexicon/{$ck}.inc.php";
		if (file_exists($s_file))
			include($s_file);
			if(is_array($lexicon) && isset($lexicon[$key]))
				$res = $lexicon[$key];
		else {
			$res  = 'Error. Lexicon file not exist: ' .$s_file ;
		}

		return $res;
	}
	//End 2012.04.12 - fedo


	//
	function isAjax(){
		if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
			return true;
		else
			return false;
	}



	/**
	 * метод для преобразования текста в транлсит
	 * @return string - транслитированный текст
	 * @param string $sData - входящий текст
	 */
	public static function makeTranslit($sData, $sSpaceDelimiter='-', $mCase = 'MB_CASE_LOWER') {

		// массив замен
		$aMapping = array(
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
			'ё' => 'yo', 'ж' => 'g', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
			'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
			'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
			'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'i', 'ь' => '',
			'э' => 'e', 'ю' => 'yu', 'я' => 'ya', '\'' => '', '`' => '',

			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
			'Ё' => 'Yo', 'Ж' => 'G', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K',
			'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
			'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts',
			'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'I', 'Ь' => '',
			'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
		);

		// преобразуем русские символы в латинские
		$sData = strtr($sData, $aMapping);

		// удаляем лишние пробелы
		$sData = trim($sData);

		// заменяем пробелы
		$sData = preg_replace('/\s+/', $sSpaceDelimiter, $sData);

		// удаляем ненужные символы
		$sData = preg_replace('/[^a-z0-9\_\-.\s]+/mi', '', $sData);

		// определяем регистр
		$aCase = array(0 => 'MB_CASE_UPPER', 1 => 'MB_CASE_LOWER', 2 => 'MB_CASE_TITLE');
		if (in_array($mCase, $aCase)) {
			// выводим
			return mb_convert_case($sData, array_search($mCase, $aCase), 'utf-8');
		} else {
			// выводим
			return $sData;
		}
	}
}

