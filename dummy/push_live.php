<?
//set xml request
$xml = file_get_contents('/home/duf/node_workspace/fantasy_fork/data/srml-8-2013-f694955-matchresults.xml');

$data = array(
'x-meta-feed-type: F9',
'x-meta-feed-parameters: feed params',
'x-meta-default-filename: srml-8-2013-f694955-matchresults.xml',
'x-meta-game-id: 8',
'x-meta-competition-id: 8',
'x-meta-season-id: 2012',
'x-meta-game-id: f442367',
'x-meta-gamesystem-id: 1',
'x-meta-matchday: 1',
'x-meta-away-team-id: 1',
'x-meta-home-team-id: 1',
'x-meta-game-status: 11',
'x-meta-language: en',
'x-meta-production-server: server',
'x-meta-production-server-timestamp: 1',
'x-meta-production-server-module: 1',
'x-meta-mime-type: text/xml',
'encoding: UTF-8'
);
//set URL
$url = 'http://push-sg.supersoccer.co.id/';
// Get the curl session object
$session = curl_init($url);
// set url to post to
//curl_setopt($session, CURLOPT_URL,$url);
// Tell curl to use HTTP POST;
curl_setopt ($session, CURLOPT_POST, true);
curl_setopt($session, CURLOPT_HTTPHEADER, $data);
// Tell curl that this is the body of the POST
curl_setopt ($session, CURLOPT_POSTFIELDS, $xml);
// Tell curl not to return headers, and return the response
curl_setopt($session, CURLOPT_HEADER, false);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
curl_setopt($session, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
// allow redirects
//curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($session);
if (curl_errno($session)) {
	print "Error: " . curl_error($session);
} else {

// Show me the result
//var_dump($response);

// curl_close($session);
}
print_r ($response);
curl_close($session);
?>
