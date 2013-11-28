<?php
/**
 * Created by fedo
 * Date: 19.09.13
 * Time: 11:08 *
 */

$e = &$this->Event;

class FileSource{
	public	$found = true,
				$regexp = "/@FILE\s([^\n].*)/";

	public function parse($s){

		preg_match_all($this->regexp, $s, $a_match);

		if(is_array($a_match[1]) && !empty($a_match[1])){
			foreach($a_match[1] as $_item){
				$s_file_path = MODX_BASE_PATH . trim($_item);

				if(file_exists($s_file_path)){
					$s_content = file_get_contents($s_file_path);
					$s = str_replace("@FILE $_item", $s_content, $s);
				}else{
					$s = str_replace("@FILE $_item", "File {$s_file_path} does not exist", $s);
				}
			}
			$this->found = true;
		}else{
			$this->found = false; //no more @FILE patterns
		}

		//run recursivity
		if($this->found){
			$s = $this->parse($s);
		}

		return $s;
	}
}

if($e->name == 'OnParseDocument'){
	$o_fs = new FileSource();

	$this->documentOutput = $o_fs->parse($this->documentOutput);
}
