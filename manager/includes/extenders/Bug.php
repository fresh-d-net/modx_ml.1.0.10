<?php
/**
 * Created by JetBrains PhpStorm.
 * User: -fedo-
 * Date: 20.10.12
 * Time: 14:21
 * To change this template use File | Settings | File Templates.
 */

class Bug {

	/**
	 * @param  $var
	 * @param string $type
	 * @return void
	 */
	public static function dump($var, $type='print_r'){

			switch($type){
				case 'print_r':
					echo '<pre>' . print_r($var, true) . '</pre>';
					break;
				case 'echo':
					echo $var;
					break;
				case 'var_export':
					var_export($var);
					break;
				default:
					if(is_string($var)){
						echo '<br>' . $var;	break;
					}else{
						echo '<pre>' . var_dump($var, true) . '</pre>';break;
					}
				
			}
	}

}
