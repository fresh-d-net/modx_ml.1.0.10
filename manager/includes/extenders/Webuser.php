<?php
/**
 ** webuser.class.inc.php
 ** WebUser Class for Modx Evolution
 ** @Version: 0.1a
 ** @Author: a.looze (a.looze@gmail.com)
 **
 ** Usage:
 ** $wu = new Webuser($userId);
 ** OR
 ** $wu = new Webuser();
 ** ==Existence webuser==
 ** $wu->loadUserByEmail($email);
 ** OR
 ** $wu->loadUserByUsername($username);
 ** OR
 ** == New webuser
 ** $wu->Set('username', 'john');
 ** $wu->Set('password', '123456'); //not need md5!
 ** OR
 ** $wu->Set('hash', '_MD5_HASH_STRING_'); //$wu->attr['password'] will be not set!
 ** $wu->Set('email', 'test@test.com');
 ** $wu->Set('rememberme', 1);
 ** $wu->Save();
 **
 ** if ($wu->error != '') echo $wu->error;
 ** else $wu->login(); //login to site
 ** ...
 ** $wu->logout(); //logout
 **/

class Webuser {
	var $uid; //user ID
	var $attr = array(); //user attributes
	var $groups = ''; //user groups
	var $error = ''; //error messages
	var $isNew;
	var $isLoggedIn = false;
	var $userExists = false;

	function __construct($id = 0) {
		global $modx;
		$this->isNew = $id == 0;

		if (!$this->isNew) {

			$this->attr = $this->getWebuserInfo();
			$this->uid = $id;
		} else {
			$this->attr['username'] = 'user_' . uniqid();
			$this->attr['password'] = uniqid();
			$this->attr['usertype'] = 'web';
			$this->attr['rememberme'] = false;
		}

	}

	/**
	 ** Save all attributes and properties for webuser into DB
	 **/
	function Save() {
		global $modx;

		$username = isset($this->attr['username']) ? $this->attr['username'] : '';
		$password = isset($this->attr['password']) ? md5($this->attr['password']) : '';
		if ($password == '') {
			$password = isset($this->attr['hash']) ? $this->attr['hash'] : '';
		}
		$cachepwd = isset($this->attr['cachepwd']) ? $this->attr['cachepwd'] : '';

		$fullname = isset($this->attr['fullname']) ? $this->attr['fullname'] : '';
		$role = isset($this->attr['role']) ? $this->attr['role'] : '';
		$email = isset($this->attr['email']) ? $this->attr['email'] : '';
		if (!$this->isValidEmail($email)){ $email = ''; $this->error .= 'Не правильный Email'; return;}

		$phone = isset($this->attr['phone']) ? $this->attr['phone'] : '';
		$mobilephone = isset($this->attr['mobilephone']) ? $this->attr['mobilephone'] : '';
		$blocked = isset($this->attr['blocked']) ? $this->attr['blocked'] : '';
		$blockeduntil = isset($this->attr['blockeduntil']) ? $this->attr['blockeduntil'] : '';
		$blockedafter = isset($this->attr['blockedafter']) ? $this->attr['blockedafter'] : '';
		$dob = isset($this->attr['dob']) ? $this->attr['dob'] : '';
		$gender = isset($this->attr['gender']) ? $this->attr['gender'] : '';
		$country = isset($this->attr['country']) ? $this->attr['country'] : '';
		$state = isset($this->attr['state']) ? $this->attr['state'] : '';
		$zip = isset($this->attr['zip']) ? $this->attr['zip'] : '';
		$fax = isset($this->attr['fax']) ? $this->attr['fax'] : '';
		$photo = isset($this->attr['photo']) ? $this->attr['photo'] : '';
		$comment = isset($this->attr['comment']) ? $this->attr['comment'] : '';

		//check data for add new webuser

		// check for duplicate user name or email
		if ($username == '') {
			$this->error .= "Missing username. Please enter a user name.\n";
			return;
		}
		if ($email == '') {
			$this->error .= "Missing email. Please enter a email.\n";
			return;
		}

		$this->loadUserByUsername($username);
		$this->loadUserByEmail($email);

		if ($this->error != '') {
			return;
		}

		//all data is correct, insert in DB
		if ($this->isNew) {
			$toInsertData = array('id' => null,
				'username' => $this->attr['username'],
				'password' => md5($this->attr['password']),
				'cachepwd' => $cachepwd
			);

			$this->uid = $modx->db->insert($toInsertData, $modx->getFullTableName('web_users'));

			$toInsertAttr = array('id' => null,
				'internalKey' => $this->uid,
				'fullname' => $fullname,
				'role' => $role,
				'email' => $email,
				'phone' => $phone,
				'mobilephone' => $mobilephone,
				'blocked' => $blocked,
				'blockeduntil' => $blockeduntil,
				'blockedafter' => $blockedafter,
				'logincount' => null,
				'lastlogin' => null,
				'thislogin' => null,
				'failedlogincount' => null,
				'sessionid' => null,
				'dob' => $dob,
				'gender' => $gender,
				'country' => $country,
				'state' => $state,
				'zip' => $zip,
				'fax' => $fax,
				'photo' => $photo,
				'comment' => $comment
			);
			$res = $modx->db->insert($toInsertAttr, $modx->getFullTableName('web_user_attributes'));
			$this->isNew = false;
		} else {
			$toUpdateData = array('username' => $this->attr['username'],
				'password' => $this->attr['password'], //already hashed
				'cachepwd' => $cachepwd
			);
			$res = $modx->db->update($toUpdateData, $modx->getFullTableName('web_users'), 'id=' . $this->uid);

			$toUpdateAttr = array('fullname' => $fullname,
				'role' => $role,
				'email' => $email,
				'phone' => $phone,
				'mobilephone' => $mobilephone,
				'blocked' => $blocked,
				'blockeduntil' => $blockeduntil,
				'blockedafter' => $blockedafter,
				'logincount' => null,
				'lastlogin' => null,
				'thislogin' => null,
				'failedlogincount' => null,
				'sessionid' => null,
				'dob' => $dob,
				'gender' => $gender,
				'country' => $country,
				'state' => $state,
				'zip' => $zip,
				'fax' => $fax,
				'photo' => $photo,
				'comment' => $comment
			);
			$res = $modx->db->update($toUpdateAttr, $modx->getFullTableName('web_user_attributes'), 'internalKey=' . $this->uid);
		}

		//add webuser to webgroups
		if ($this->groups != '') {
			$grAr = explode(',', $this->groups);
			foreach ($grAr as $grName) {
				if ($grName == '') continue;
				$res = $modx->db->select('id', $modx->getFullTableName('webgroup_names'), 'name="' . $grName . '"');
				$row = $modx->db->getRow($res);
				if (!is_array($row)) continue;
				$grId = $row['id'];

				//check if user already in this group
				$res = $modx->db->select('id', $modx->getFullTableName('web_groups'), 'webuser=' . $this->uid . ' AND webgroup=' . $grId);
				if ($cnt = $modx->db->getRecordCount($res)) continue; //already exists
				//add webuser to webgroup
				$res = $modx->db->insert(array('id' => null, 'webuser' => $this->uid, 'webgroup' => $grId), $modx->getFullTableName('web_groups'));
			}
		}
	}

	/**
	 ** Make webuser logged in
	 **/
	function login() {
		global $modx;
		/*if ($this->isNew ) { //|| !$this->userExists
			  return;
			} */
		$username = $this->attr['username'];
		$password = $this->attr['password'];
		$hash = isset($this->attr['hash']) ? $this->attr['hash'] : md5($this->attr['password']);
		$rememberme = $this->attr['rememberme'];

		$modx->invokeEvent("OnBeforeWebLogin",
			array(
				"username" => $username,
				"userpassword" => $password,
				"rememberme" => $rememberme
			));

		$dbase = $modx->dbConfig['dbase'];
		$table_prefix = $modx->dbConfig['table_prefix'];

		$query = "SELECT $dbase.`" . $table_prefix . "web_users`.*, $dbase.`" . $table_prefix . "web_user_attributes`.* FROM $dbase.`" . $table_prefix . "web_users`, $dbase.`" . $table_prefix . "web_user_attributes` WHERE BINARY $dbase.`" . $table_prefix . "web_users`.username = '" . $username . "' and $dbase.`" . $table_prefix . "web_user_attributes`.internalKey=$dbase.`" . $table_prefix . "web_users`.id;";
		$res = $modx->db->query($query);

		$cnt = $modx->db->getRecordCount($res);

		if ($cnt == 0 || $cnt > 1) {
			$this->error .= "Incorrect username or password entered!\n";
			return;
		}

		$row = $modx->db->getRow($res);

		$internalKey = $row['internalKey'];
		$dbasePassword = $row['password'];
		$failedlogins = $row['failedlogincount'];
		$blocked = $row['blocked'];
		$blockeduntildate = $row['blockeduntil'];
		$blockedafterdate = $row['blockedafter'];
		$registeredsessionid = $row['sessionid'];
		$role = $row['role'];
		$lastlogin = $row['lastlogin'];
		$nrlogins = $row['logincount'];
		$fullname = $row['fullname'];
		$email = $row['email'];

		// load user settings
		if ($internalKey) {
			$result = $modx->db->query("SELECT setting_name, setting_value FROM " . $dbase . ".`" . $table_prefix . "web_user_settings` WHERE webuser='" . $this->uid . "'");
			while ($row = $modx->fetchRow($result, 'both')) $modx->config[$row[0]] = $row[1];
		}

		if ($failedlogins >= $modx->config['failed_login_attempts'] && $blockeduntildate > time()) { // blocked due to number of login errors.
			session_destroy();
			session_unset();
			$this->error .= "Due to too many failed logins, you have been blocked!\n";
			return;
		}

		if ($failedlogins >= $modx->config['failed_login_attempts'] && $blockeduntildate < time()) { // blocked due to number of login errors, but get to try again
			$sql = "UPDATE $dbase.`" . $table_prefix . "web_user_attributes` SET failedlogincount='0', blockeduntil='" . (time() - 1) . "' where internalKey=" . $this->uid;
			$ds = $modx->db->query($sql);
		}

		if ($blocked == "1") { // this user has been blocked by an admin, so no way he's loggin in!
			session_destroy();
			session_unset();
			$this->error .= "You are blocked and cannot log in!\n";
			return;
		}

		// blockuntil
		if ($blockeduntildate > time()) { // this user has a block until date
			session_destroy();
			session_unset();
			$this->error .= "You are blocked and cannot log in! Please try again later.\n";
			return;
		}

		// blockafter
		if ($blockedafterdate > 0 && $blockedafterdate < time()) { // this user has a block after date
			session_destroy();
			session_unset();
			$this->error .= "You are blocked and cannot log in! Please try again later.\n";
			return;
		}

		// allowed ip
		if (isset($modx->config['allowed_ip'])) {
			if (strpos($modx->config['allowed_ip'], $_SERVER['REMOTE_ADDR']) === false) {
				$this->error .= "You are not allowed to login from this location.\n";
				return;
			}
		}

		// allowed days
		if (isset($modx->config['allowed_days'])) {
			$date = getdate();
			$day = $date['wday'] + 1;
			if (strpos($modx->config['allowed_days'], "$day") === false) {
				$this->error .= "You are not allowed to login at this time. Please try again later.\n";
				return;
			}
		}

		// invoke OnWebAuthentication event
		$rt = $modx->invokeEvent("OnWebAuthentication",
			array(
				"userid" => $internalKey,
				"username" => $username,
				"userpassword" => $password,
				"savedpassword" => $dbasePassword,
				"rememberme" => $rememberme
			));
		// check if plugin authenticated the user
		if (!$rt || (is_array($rt) && !in_array(TRUE, $rt))) {
			// check user password - local authentication
			if ($dbasePassword != $hash) {
				//echo "$dbasePassword != $hash";
				$this->error .= "Incorrect username or password entered!\n";
				$newloginerror = 1;
			}
		}
		/*
			if(isset($modx->config['use_captcha']) && $modx->config['use_captcha']==1) {
				if($_SESSION['veriword']!=$captcha_code) {
					$this->error.= "The security code you entered didn't validate! Please try to login again!";
					$newloginerror = 1;
				}
			}*/

		if (isset($newloginerror) && $newloginerror == 1) {
			$failedlogins += $newloginerror;
			if ($failedlogins >= $modx->config['failed_login_attempts']) { //increment the failed login counter, and block!
				$sql = "update $dbase.`" . $table_prefix . "web_user_attributes` SET failedlogincount='$failedlogins', blocked=1, blockeduntil='" . (time() + ($modx->config['blocked_minutes'] * 60)) . "' where internalKey=$internalKey";
				$ds = $modx->db->query($sql);
			} else { //increment the failed login counter
				$sql = "update $dbase.`" . $table_prefix . "web_user_attributes` SET failedlogincount='$failedlogins' where internalKey=$internalKey";
				$ds = $modx->db->query($sql);
			}
			session_destroy();
			session_unset();
			return;
		}

		$currentsessionid = session_id();

		if (!isset($_SESSION['webValidated'])) {
			$sql = "update $dbase.`" . $table_prefix . "web_user_attributes` SET failedlogincount=0, logincount=logincount+1, lastlogin=thislogin, thislogin=" . time() . ", sessionid='$currentsessionid' where internalKey=$internalKey";
			$ds = $modx->db->query($sql);
		}

		$_SESSION['webShortname'] = $username;
		$_SESSION['webFullname'] = $fullname;
		$_SESSION['webEmail'] = $email;
		$_SESSION['webValidated'] = 1;
		$_SESSION['webInternalKey'] = $internalKey;
		$_SESSION['webValid'] = base64_encode($password);
		$_SESSION['webUser'] = base64_encode($username);
		$_SESSION['webFailedlogins'] = $failedlogins;
		$_SESSION['webLastlogin'] = $lastlogin;
		$_SESSION['webnrlogins'] = $nrlogins;
		$_SESSION['webUserGroupNames'] = ''; // reset user group names

		// get user's document groups
		$dg = '';
		$i = 0;
		$tblug = $dbase . ".`" . $table_prefix . "web_groups`";
		$tbluga = $dbase . ".`" . $table_prefix . "webgroup_access`";
		$sql = "SELECT uga.documentgroup
            FROM $tblug ug
            INNER JOIN $tbluga uga ON uga.webgroup=ug.webgroup
            WHERE ug.webuser =" . $internalKey;
		$ds = $modx->db->query($sql);
		while ($row = $modx->db->getRow($ds, 'num')) $dg[$i++] = $row[0];
		$_SESSION['webDocgroups'] = $dg;

		$tblwgn = $modx->getFullTableName("webgroup_names");
		$tblwg = $modx->getFullTableName("web_groups");
		$sql = "SELECT wgn.name
    FROM $tblwgn wgn
    INNER JOIN $tblwg wg ON wg.webgroup=wgn.id AND wg.webuser=" . $internalKey;
		$grpNames = $modx->db->getColumn("name", $sql);
		$_SESSION['webUserGroupNames'] = $grpNames;

		if ($rememberme) {
			$_SESSION['modx.web.session.cookie.lifetime'] = intval($modx->config['session.cookie.lifetime']);
		} else {
			$_SESSION['modx.web.session.cookie.lifetime'] = 0;
		}

		/*$log = new logHandler;
			$log->initAndWriteLog("Logged in", $_SESSION['webInternalKey'], $_SESSION['webShortname'], "58", "-", "WebLogin");*/
		$this->isLoggedIn = true;
		$this->uid = $internalKey;
		$this->getWebuserInfo();
	}

	/**
	 ** Make webuser logged in
	 **/
	function logout() {
		global $modx;

		if (!$modx->userLoggedIn()) return;
		$internalKey = $_SESSION['webInternalKey'];
		$username = $_SESSION['webShortname'];

		// invoke OnBeforeWebLogout event
		$modx->invokeEvent("OnBeforeWebLogout",
			array(
				"userid" => $internalKey,
				"username" => $username
			));

		// if we were launched from the manager
		// do NOT destroy session
		if (isset($_SESSION['mgrValidated'])) {
			unset($_SESSION['webShortname']);
			unset($_SESSION['webFullname']);
			unset($_SESSION['webEmail']);
			unset($_SESSION['webValidated']);
			unset($_SESSION['webInternalKey']);
			unset($_SESSION['webValid']);
			unset($_SESSION['webUser']);
			unset($_SESSION['webFailedlogins']);
			unset($_SESSION['webLastlogin']);
			unset($_SESSION['webnrlogins']);
			unset($_SESSION['webUsrConfigSet']);
			unset($_SESSION['webUserGroupNames']);
			unset($_SESSION['webDocgroups']);
		} else {
			// destroy session cookie
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', 0, MODX_BASE_URL);
			}
			session_destroy();
		}

		// invoke OnWebLogout event
		$modx->invokeEvent("OnWebLogout",
			array(
				"userid" => $internalKey,
				"username" => $username
			));

		return true;
	}

	/**
	 ** Load user data by email
	 **/
	function loadUserByEmail($email) {
		global $modx;

		$res = $modx->db->select('internalKey', $modx->getFullTableName("web_user_attributes"), 'email="' . $email . '"');
		$cnt = $modx->db->getRecordCount($res);
		if ($cnt > 1) {
			$this->error = 'Duplicate email ' . $email . ' in DB!';
		} else if ($cnt == 1) {
			$row = $modx->db->getRow($res);
			$this->uid = $row['internalKey'];
			$this->getWebuserInfo();
			if ($this->error == '') {
				$this->isNew = false;
				$this->userExists = true;
			}
		}
	}

	/**
	 ** Load user data by username
	 **/
	function loadUserByUsername($username) {
		global $modx;

		$res = $modx->db->select('id', $modx->getFullTableName("web_users"), 'username="' . $username . '"');
		$cnt = $modx->db->getRecordCount($res);
		if ($cnt > 1) {
			$this->error = 'Duplicate username ' . $username . ' in DB!';
		} else if ($cnt == 1) {
			$row = $modx->db->getRow($res);
			$this->uid = $row['id'];
			$this->getWebuserInfo();
			if ($this->error == '') {
				$this->isNew = false;
				$this->userExists = true;
			}
		}
	}

	/**
	 ** Load all attributes for webuser into $this->attr array
	 **/
	function getWebuserInfo() {
		global $modx;
		/*if ($this->isNew) {
			  $this->error = 'No new webuser data can be loaded';
			}*/
		$res = $modx->getWebUserInfo($this->uid);
		if (is_array($res)) {
			$this->attr = $res;
			$this->userExists = true;
		} else {
			$this->error = 'No webuser data found';
		}
		//add groups info
		$res = $modx->db->select('webgroup', $modx->getFullTableName('web_groups'), 'webuser=' . $this->uid);
		$cnt = $modx->db->getRecordCount($res);
		if ($cnt > 0) {
			$grNames = array();
			while ($row = $modx->db->getRow($res)) {
				$res1 = $modx->db->select('name', $modx->getFullTableName('webgroup_names'), 'id=' . $row['webgroup']);
				if ($row1 = $modx->db->getRow($res1)) {
					$grNames[] = $row1['name'];
				}
			}
			$this->groups = implode(',', $grNames);
		}
	}

	/**
	 ** Set an attribute / property
	 **/
	function Set($attr, $value) {
		if ($attr == 'group') {
			//set web group
			$this->addGroup($value);
		} else {
			//set attribute
			$this->attr[$attr] = $value;
		}
	}

	/**
	 ** Add webuser to webgroup
	 **/
	function addGroup($grp) {
		if ($this->groups == '') {
			$this->groups = $grp;
		} else {
			$grAr = explode(',', $this->groups);
			$grAr[] = $grp;
			$this->groups = implode(',', $grAr);
		}
	}

	/**
	 ** Clean all groups
	 ** This method reserved for TODO
	 **/
	function removeAllGroups() {
		$this->groups = '';
	}

	/**
	 ** Check if email is valid
	 **/
	function isValidEmail($email) {
		if ($ret = filter_var($email, FILTER_VALIDATE_EMAIL)) {
			//проверяем доступность домена на котором находится мейл
			list($username, $domain) = explode('@', $email);
			if (!checkdnsrr($domain, 'MX')) {
				$ret = false;
			}
		}
		return $ret;
	}


	/**
	 * @param  $oldPwd (md5)
	 * @param  $newPwd (none md5)
	 * @return bool|string
	 * Change current web user's password - returns true if successful, oterhwise return error message
	 * This method used in "forgot password" action
	 */
	function changePassword($oldPwd, $newPwd) {
		global $modx;
		$rt = false;
		$tbl = $modx->getFullTableName("web_users");
		$ds = $modx->db->query("SELECT `id`, `username`, `password` FROM $tbl WHERE `id`='" . $this->uid . "'");
		$limit = mysql_num_rows($ds);
		if ($limit == 1) {
			$row = $modx->db->getRow($ds);
			if ($row["password"] == $oldPwd) {
				if (strlen($newPwd) < 6) {
					return "Password is too short!";
				}
				elseif ($newPwd == "") {
					return "You didn't specify a password for this user!";
				} else {
					$modx->db->query("UPDATE $tbl SET password = md5('" . $modx->db->escape($newPwd) . "') WHERE id='" . $this->uid . "'");
					// invoke OnWebChangePassword event
					$modx->invokeEvent("OnWebChangePassword", array(
						"userid" => $row["id"],
						"username" => $row["username"],
						"userpassword" => $newPwd
					));
					return true;
				}
			} else {
				return "Incorrect password.";
			}
		}
	}


	/**
	 * @param  $number
	 * @return string
	 * генерация случайного пароля
	 */
	function generatePassword($number) {
		$arr = array('a', 'b', 'c', 'd', 'e', 'f',
			'g', 'h', 'i', 'j', 'k', 'l',
			'm', 'n', 'o', 'p', 'r', 's',
			't', 'u', 'v', 'x', 'y', 'z',
			'A', 'B', 'C', 'D', 'E', 'F',
			'G', 'H', 'I', 'J', 'K', 'L',
			'M', 'N', 'O', 'P', 'R', 'S',
			'T', 'U', 'V', 'X', 'Y', 'Z',
			'1', '2', '3', '4', '5', '6',
			'7', '8', '9', '0');
		// Генерируем пароль
		$pass = "";
		for ($i = 0; $i < $number; $i++)
		{
			// Вычисляем случайный индекс массива
			$index = rand(0, count($arr) - 1);
			$pass .= $arr[$index];
		}
		return $pass;
	}

}

?>