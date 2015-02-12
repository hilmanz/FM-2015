<?php
// Time format is UNIX timestamp or
  // PHP strtotime compatible strings
  function dateDiff($time1, $time2, $precision = 6) {
    // If not numeric then convert texts to unix timestamps
    if (!is_int($time1)) {
      $time1 = strtotime($time1);
    }
    if (!is_int($time2)) {
      $time2 = strtotime($time2);
    }
 
    // If time1 is bigger than time2
    // Then swap time1 and time2
    if ($time1 > $time2) {
      $ttime = $time1;
      $time1 = $time2;
      $time2 = $ttime;
    }
 
    // Set up intervals and diffs arrays
    $intervals = array('year','month','day','hour','minute','second');
    $diffs = array();
 
    // Loop thru all intervals
    foreach ($intervals as $interval) {
      // Set default diff to 0
      $diffs[$interval] = 0;
      // Create temp time from time1 and interval
      $ttime = strtotime("+1 " . $interval, $time1);
      // Loop until temp time is smaller than time2
      while ($time2 >= $ttime) {
  $time1 = $ttime;
  $diffs[$interval]++;
  // Create new temp time from time1 and interval
  $ttime = strtotime("+1 " . $interval, $time1);
      }
    }
 
    $count = 0;
    $times = array();
    // Loop thru all diffs
    foreach ($diffs as $interval => $value) {
      // Break if we have needed precission
      if ($count >= $precision) {
  break;
      }
      // Add value and interval 
      // if value is bigger than 0
      if ($value > 0) {
  // Add s if value is not 1
  if ($value != 1) {
    $interval .= "s";
  }
  // Add value and interval to times array
  $times[] = $value . " " . $interval;
  $count++;
      }
    }
 
    // Return string with times
    return implode(", ", $times);
  }
  
  function subval_sort($a,$subkey) {
  foreach($a as $k=>$v) {
    $b[$k] = strtolower($v[$subkey]);
  }
  asort($b);
  foreach($b as $key=>$val) {
    $c[] = $a[$key];
  }
  return $c;
}
/**
 * a helper function to help sorting an array based on its key's value reversely
 * @param $a
 * @param $subkey
 */
function subval_rsort($a,$subkey) {
  foreach($a as $k=>$v) {
    $b[$k] = strtolower($v[$subkey]);
  }
  arsort($b);
  foreach($b as $key=>$val) {
    $c[] = $a[$key];
  }
  return $c;
}

function phpbb_email_hash($email)
{
  return sprintf('%u', crc32(strtolower($email))) . strlen($email);
}

// Parameters:
// $text = The text that you want to encrypt.
// $key = The key you're using to encrypt.
// $alg = The algorithm.
// $crypt = 1 if you want to crypt, or 0 if you want to decrypt.

function cryptare($text, $key, $alg, $crypt)
{
    $encrypted_data="";
    switch($alg)
    {
        case "3des":
            $td = mcrypt_module_open('tripledes', '', 'ecb', '');
            break;
        case "cast-128":
            $td = mcrypt_module_open('cast-128', '', 'ecb', '');
            break;   
        case "gost":
            $td = mcrypt_module_open('gost', '', 'ecb', '');
            break;   
        case "rijndael-128":
            $td = mcrypt_module_open('rijndael-128', '', 'ecb', '');
            break;       
        case "twofish":
            $td = mcrypt_module_open('twofish', '', 'ecb', '');
            break;   
        case "arcfour":
            $td = mcrypt_module_open('arcfour', '', 'ecb', '');
            break;
        case "cast-256":
            $td = mcrypt_module_open('cast-256', '', 'ecb', '');
            break;   
        case "loki97":
            $td = mcrypt_module_open('loki97', '', 'ecb', '');
            break;       
        case "rijndael-192":
            $td = mcrypt_module_open('rijndael-192', '', 'ecb', '');
            break;
        case "saferplus":
            $td = mcrypt_module_open('saferplus', '', 'ecb', '');
            break;
        case "wake":
            $td = mcrypt_module_open('wake', '', 'ecb', '');
            break;
        case "blowfish-compat":
            $td = mcrypt_module_open('blowfish-compat', '', 'ecb', '');
            break;
        case "des":
            $td = mcrypt_module_open('des', '', 'ecb', '');
            break;
        case "rijndael-256":
            $td = mcrypt_module_open('rijndael-256', '', 'ecb', '');
            break;
        case "xtea":
            $td = mcrypt_module_open('xtea', '', 'ecb', '');
            break;
        case "enigma":
            $td = mcrypt_module_open('enigma', '', 'ecb', '');
            break;
        case "rc2":
            $td = mcrypt_module_open('rc2', '', 'ecb', '');
            break;   
        default:
            $td = mcrypt_module_open('blowfish', '', 'ecb', '');
            break;                                           
    }
   
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    $key = substr($key, 0, mcrypt_enc_get_key_size($td));
    mcrypt_generic_init($td, $key, $iv);
   
    if($crypt)
    {
        $encrypted_data = @mcrypt_generic($td, $text);
    }
    else
    {
        $encrypted_data = @mdecrypt_generic($td, $text);
    }
   
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
   
    return $encrypted_data;
} 
function convertBase64($str){
  $str = str_replace("=",".",$str);
  $str = str_replace("+","-",$str);
  $str = str_replace("/","_",$str);
  return $str;
}
function realBase64($str){
  $str = str_replace(".","=",$str);
  $str = str_replace("-","+",$str);
  $str = str_replace("_","/",$str);
  return $str;
}
function urlencode64($str){
  
  $key = md5('th1$ 1s my b0unty, n0t y0urs !');
  $hash = cryptare($str,$key,'des',1);
  $str = convertBase64(base64_encode($hash));
  return $str;
}
function urldecode64($str){
  global $CONFIG;
  $key = md5('th1$ 1s my b0unty, n0t y0urs !');
  $secret = base64_decode(realBase64($str));
  $str = cryptare($secret,$key,'des',0);
  return trim($str);
}
function encrypt_param($str){
  return urlencode64($str);
}
function decrypt_param($str){
  return urldecode64($str);
}
function to_secure_params($str){
  parse_str($str,$p);
  
  $json = json_encode($p);
  $str_hash = urlencode64($json);
  //print $str_hash;
  
  return $str_hash;
}
function reveal($str){
  $str = trim(urldecode64($str));
  return json_decode($str);
} 

/**
* Return unique id
* Borrowed from PHPBB
* @param string $extra additional entropy
*/
function unique_id($id)
{
  return $id;
}
function curlGet($url,$params,$cookie_file='',$timeout=15){
  
  if(count($params) > 0){
    $url .= "?".http_build_query($params);
  }
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
  curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if($cookie_file!=''){
    curl_setopt($ch,CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch,CURLOPT_COOKIEFILE, $cookie_file); 
  }
  $response = curl_exec ($ch);
  $info = curl_getinfo($ch);
  if($info['http_code']==0){
    $response = json_encode(array('error'=>'unable to connect to web service !'));
  }
  curl_close ($ch);
  return $response;
}
function curlPost($url,$params,$cookie_file='',$timeout=15){
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  
  if($cookie_file!=''){
    curl_setopt($ch,CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch,CURLOPT_COOKIEFILE, $cookie_file); 
  }
  $response = curl_exec ($ch);
  $info = curl_getinfo($ch);
  if($info['http_code']==0){
    $response = json_encode(array('error'=>'unable to connect to web service !'));
  }
  curl_close ($ch);
  return $response;
}

function getTransferValueBonus($performance,$transfer_value){
  $performance_bonus = (((($performance / 10) * 1)/100) * 
                                            intval($transfer_value));
  return $performance_bonus;
}

function send_mail($data = array())
{   

    App::import('Component', 'Email'); 
    $view = new View();

    $body = $view->element($data['template'], $data);

    $Email = new CakeEmail('smtp');

    $Email->to(trim($data['to']));
    $Email->subject($data['subject']);
    $Email->emailFormat('html');
    if($Email->send($body))
    {
      return true;
    }

    return false;
}