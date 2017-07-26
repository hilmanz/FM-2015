<?php
$str = "GET:".PHP_EOL;
$str.= json_encode($_GET).PHP_EOL;
$str.= "POST:".PHP_EOL;
$str.= json_encode($_POST).PHP_EOL;
$body = file_get_contents('php://input');

$str.= "body -> ".$body;

 if ($_GET['id'] == null) {
    //$body = file_get_contents('php://input');
	if(!empty($body)) {
	  $data = explode(",", $body);
	  $ticket = $data[0];
	  $phone_no = $data[1];
	  $trace_no = $data[2];
	  $order_id = trim($data[3]);
	  $status = trim($data[4]);
	  
	  $returnid = $ticket;
	  
	  $str.= "ID==NULL".PHP_EOL;
	  $str.= $body.PHP_EOL.PHP_EOL;

	  $ch = curl_init();
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_FAILONERROR, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    $validationUrl = "https://182.253.203.90/ecommgateway/validation.html?id=";
	    curl_setopt($ch, CURLOPT_URL, $validationUrl . $returnid);
	    $result = curl_exec($ch);
	    curl_close($ch);
	    $validation = explode(",", $result);
	    $val_ticket = $validation[0];
	    $val_phonenmbr = $validation[2];
	    $val_tracenmbr = $validation[1];
	    $val_order_id = trim($validation[3]);
	    $val_pstatus = trim($validation[4]);
	    $str.= "VALIDATE".PHP_EOL;
	    $str.= $result.PHP_EOL.PHP_EOL;
	}
}
$fp = fopen('logs/test.log','a+');
fwrite($fp,$str,strlen($str));
fclose($fp);
?>