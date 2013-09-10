<?php
/**
 * Created by JetBrains PhpStorm.
 * User: -fedo-
 * Date: 23.10.12
 * Time: 22:29
 */

class Cookie {
	var $cName = '';
	var $cTime = '';
	var $encode = false;
	var $cPath = '';

	function __construct($cookieName, $cookieTimeout, $cookieSerialize = false, $cookiePath = "/") {
		$this->cName = $cookieName;
		$this->cTime = $cookieTimeout;
		$this->encode = $cookieSerialize;
		$this->cPath = $cookiePath;
		// This should fix the issue if you have cookies set and THEN turn on the serialization.
		$iname = $this->cName;
		if ($this->encode && !isset($_COOKIE[$iname])) {
			$cookArr = array();
			foreach ($_COOKIE as $name => $val) {
				if (strpos($name, $this->cName) !== false) { // make sure it is a cookie set by this application
					$subname = substr($name, strlen($this->cName) + 1);
					$cookArr[$subname] = $val;
					$this->delete($name);
				}
			}
			$this->set($cookArr);
		}
		// This is the opposite from above. changes a serialized cookie to multiple cookies without loss of data
		if (!$this->encode && isset($_COOKIE[$iname])) {
			$cookArr = (array)json_decode($_COOKIE[$iname]);
			$this->delete($iname);
			$this->set($cookArr);
		}
	}

	function flush() {
		foreach ($_COOKIE as $name => $val) {
			if (strpos($name, $this->cName) !== false) {
				$_COOKIE[$name] = NULL;
				$this->delete($name);
			}
		}
	}

	function get($item='') {
		if ($this->encode) {
			$name = $this->cName;
			if (isset($_COOKIE[$name])) {
				// handle the cookie as a serialzied variable
				$aCookie = (array)json_decode($_COOKIE[$name]);
				if (is_array($aCookie)) {
					return $aCookie;
				} else {
					return NULL;
				}
			} else {
				return NULL;
			}
		} else {
			$name = $this->cName . "_" . $item;
			if (isset($_COOKIE[$name])) {
				// handle the item as separate cookies
				return $_COOKIE[$name];
			} else {
				return NULL;
			}
		}
	}

	function delete($cName) {
		$tStamp = time() - 432000;
		setcookie($cName, "", $tStamp, $this->cPath);
	}

	function set($itemArr) {
		if ($this->encode) {
			$sItems = (string)json_encode($itemArr);
			$name = $this->cName;
			$_COOKIE[$name] = $sItems;
			$tStamp = time() + $this->cTime;
			setcookie($name, $sItems, $tStamp, $this->cPath);
		} else {
			$tStamp = time() + $this->cTime;
			foreach ($itemArr as $nam => $val) {
				$name = $this->cName . "_" . $nam;
				$_COOKIE[$name] = $val;
				setcookie($name, $val, $tStamp, $this->cPath);
			}
		}
	}

}
