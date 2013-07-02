<?php
$api->addTextHandler("smallurl");

// Simple SmallURL Script to shorten URLS it detects.
// Put your API Key in and load it up.
$key = "47a236f2a858808b7ebcbe84fe0536d3";

function smallurl($args) {
	$trigger = "!";
	global $api;
	$msg = $args['text_array'];
	$chan = $args['channel'];
	if ($msg[0] == $trigger."shorten") {
		if (isset($msg[1])) {
			$smallurl = smallify($msg[1]);
			$api->msg($chan,$smallurl);
		}
		else {
			$api->msg($chan,"Usage: {$trigger}shorten URL");
		}
	}
	else if ($msg[0] == $trigger."inspect") {
		if (isset($msg[1])) {
			if (preg_match("/http:\/\/smallurl.in\//i",$msg[1])) {
				$smallurl = explode("/",$msg[1]);
				$smallurl = $smallurl[count($smallurl)-1];
			}
			else if (!preg_match("/http:\/\//i",$msg[1])) {
				$smallurl = $msg[1];
			}
			$data = inspectify($smallurl);
			if ($data['res'] == false) {
				echo "SmallURL API ERROR: ".$data['msg']."\n";
				$api->msg($chan,"There was an error: ".$data['msg']);
			}
			else {
				if ($data['private'] == "true") {
					$privacy = "is private.";
				}
				else {
					$privacy = "is not private.";
				}
				$info = "http://smallurl.in/{$data['short']} points to {$data['url']} which was shortened {$data['nice_date']} by {$data['user']} and ".$privacy;
				$api->msg($chan,$info);
			}
		}
		else {
			$api->msg($chan,"Usage: {$trigger}inspect SmallURL");
		}
	}
}
function smallify($url) {
	$res = file_get_contents("http://smallurl.in/api/api.php?url=".urlencode($url));
	$result = json_decode($res);
	if ($result->res == true) {
		return "http://smallurl.in/".$result->short;
	}
	else {
		return $result->msg;
	}
}
function inspectify($shorturl) {
	global $key;
	echo "Checking Key {$key}\n";
	$res = json_decode(file_get_contents("http://smallurl.in/api/api.php?action=inspect&key={$key}&short=".urlencode($shorturl)),true);
	return $res;
}