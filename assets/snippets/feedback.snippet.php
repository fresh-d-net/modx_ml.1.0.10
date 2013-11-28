<?php
$b_error = false; $a_res = array();
	
	$s_name = isset($_POST['name'])? $_POST['name']: $name;
	$s_phone = isset($_POST['phone'])? $_POST['phone']: $phone;
	$s_email = isset($_POST['email'])? $_POST['email']: $email;
	$s_text = isset($_POST['text'])? $_POST['text']: $text;

	//check Email
	if(!$s_email || !$modx->checkEmail($s_email)){
		$b_error = true;
		$a_res['message'][] = 'Такой Email не существует';
		$a_res['status'][] = 'email_error';
	}
		
	if($s_name && $s_email && $s_text){
		//getting templates
		$s_tpl = $modx->getChunk('mail_tpl');
	
		$s_body = $modx->setPlaceholders(
			$s_tpl,
			array(
				'name' => $s_name,
				'email' => $s_email,
				'text' => $s_text,
				'phone' => $s_phone
			)
		);
	}else{
		$b_error = true;
		$a_res['message'][] = 'Не заполнено одно из полей формы';
	}
	
	if(!$b_error){
		# send abuse alert
		$oMail = new Mailer();
		$oMail->IsMail();
		$oMail->IsHTML(true);
		$oMail->CharSet = 'utf-8';
		$oMail->From = $s_email;
		$oMail->FromName = $s_name;
		$oMail->Subject = 'Письмо с сайта ' . $modx->config['site_name'];
	
		$oMail->Body = $s_body;
		$oMail->AddAddress($modx->config['emailsender']);
		
		//adding attachment
		if(is_array($_FILES)){
			foreach($_FILES as $a_file){
				$oMail->AddAttachment($a_file['tmp_name'], $a_file['name']);
			}
		}
		
		if (!$oMail->send()) {
			$a_res['status'] = 'error';
		} else {
			$a_res['status'] = 'send';
			$_SESSION['feedback'] = $s_name.$s_email;
			$a_res['message'][] = $modx->config['feedback_success_message'];
			$a_res['title'] = 'Сообщение отправлено';
		}
	}
		


//checking request type
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
	$a_res = json_encode($a_res);
}

return $a_res;
?>
