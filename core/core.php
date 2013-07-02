<?php
/*
	#############################
	#   PITC IRC BOT FRAMEWORK  #
	#    By Thomas Edwards      #
	#  COPYRIGHT TMFKSOFT 2012  #
	#############################
 */
 
// One line you may want to tweak.
$refresh = "5000";

/*
 * You can tweak the refresh from within PITC to find a suitable speed.
 * Use '/refresh' to return the current speed.
 * Use '/refresh value' to set the refresh speed.
 */
 
 // DO NOT EDIT ANY CODE IN THIS FILE, You not longer need to.
 
 // DEBUG
	$keybuff_arr = array();
 // END DEBUG
 
echo "Loading...\n";
declare(ticks = 1);
@ini_set("memory_limit","8M"); // Ask for more memory
stream_set_blocking(STDIN, 0);
stream_set_blocking(STDOUT, 0);
set_error_handler("pitcError");
$log_irc = true; // Log IRC to the main window as well?
$rawlog = array();
$start_stamp = time();
$rawlog = array();
$ctcps = array();
$error_log = array();
$timers = array();

$_DEBUG = array(); // Used to set global vars for /dump from within functions.
$loaded = array(); // Loaded scripts.

if (isset($argv[1]) && $argv[1] == "-a") {
	$autoconnect = true;
}
else {
	$autoconnect = false;
}

if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
	system("stty -icanon"); // Only Linux can do this :D
	$shell_cols = exec('tput cols');
	$shell_rows = exec('tput lines');
}
else {
	$shell_cols = "80";
	$shell_rows = "24";
}

// Init some Variables.
$version = "1.2"; // Do not change this!

if (file_exists($_SERVER['PWD']."/core/functions.php")) {
	include($_SERVER['PWD']."/core/functions.php");
}
else {
	die("Missing Functions.php! PITC CANNOT Function without this.");
}

if (file_exists($_SERVER['PWD']."/core/config.php")) {
	include($_SERVER['PWD']."/core/config.php");
}
else {
	shutdown("ERROR Loading Config.php!\n");
}

if (!file_exists($_SERVER['PWD']."/core/config.cfg")) {
	stream_set_blocking(STDIN, 1);
	run_config();
	sleep(1);
	stream_set_blocking(STDIN, 0);
}

// Load the config and language pack.
$_CONFIG = load_config();
if (isset($_CONFIG['lang'])) {
	$language = $_CONFIG['lang'];
}
else {
	$language = "en";
}
$lng = array();
if (file_exists("langs/".$language.".lng")) {
	eval(file_get_contents("langs/".$language.".lng"));
}
else {
	if (file_exists("langs/en.lng")) {
		eval(file_get_contents("langs/en.lng"));
	}
	else {
		shutdown("Unable to load Specified Language or English Language!\n");
	}
}


// Variable Inits - LEAVE THEM ALONE!

// Windows and $scrollback are no longer used, Kept in for now.
$active = "strt"; // Current window being viewed.
$windows = array();
$scrollback['0'] = array(" = {$lng['STATUS']} {$lng['WINDOW']}. =");
$text = "";

// Channel Stuff.
$chan_modes = array();
$chan_topic = array();

if (file_exists($_SERVER['PWD']."/core/api.php")) {
	include($_SERVER['PWD']."/core/api.php");
}
else {
	shutdown("{$lng['MSNG_API']}\n");
}

// ASCI is no longer in PITCBots.


// Scripting interface/api
$api_commands = array();
$api_messages = array();
$api_actions = array();
$api_ctcps = array();
$api_joins = array();
$api_parts = array();
$api_connect = array();
$api_tick = array();
$api_raw = array();
$api_start = array();
$api_stop = array();
$_PITC = array();

// PITC Variables
$_PITC['nick'] = $_CONFIG['nick'];
$_PITC['altnick'] = $_CONFIG['altnick'];
$_PITC['network'] = false;
$_PITC['server'] = false;
$_PITC['address'] = false;

// Temp DB Data.
$channels = array(); // Channels im in.
$users = array(); // User info.

// START Handler/Hook
$x = 0;
while ($x != count($api_start)) {
	$args = array(); // Empty for now
	call_user_func($api_start[$x],$args);
	$x++;
}

// Init our API's
$api = new pitcapi();
$chan_api = new channel();
$timer = new timer();

// Load any core scripts.
include("colours.php");
$colors = new Colors(); // Part of Colours Script

// Load auto scripts.
if (file_exists($_SERVER['PWD']."/scripts/autoload")) {
	$scripts = explode("\n",file_get_contents($_SERVER['PWD']."/scripts/autoload"));
	for ($x=0;$x != count($scripts);$x++) {
		if ($scripts[$x][0] != ";") {
			$script = $_SERVER['PWD']."/scripts/".trim($scripts[$x]);
			if (file_exists($script)) {
				include_once($script);
				$loaded[] = $script;
			}
			else {
				$core->internal(" = {$lng['AUTO_ERROR']} '{$scripts[$x]}' {$lng['NOSUCHFILE']} =");
			}
		}
	}
	//unset($scripts);
}
if ($_SERVER['TERM'] == "screen") {
	$core->internal(" = {$lng['SCREEN']} =");
}
$ann = data_get("http://announcements.pitc.x10.mx/");
if ($ann != false && $ann->message != "none") { $core->internal(" ".$ann->message); }

// Connect!
$core->internal(" = {$lng['CONN_DEF']} (".$_CONFIG['address'].") =");
$address = $_CONFIG['address'];
$_PITC['address'] = $address;

$address = explode(":",$address);
if (isset($address[1]) && is_numeric($address[1])) { $port = $address[1]; }
else { $port = 6667; }
if (isset($text[2])) { $password = $text[2]; } else { if (isset($_CONFIG['password'])) { $password = $_CONFIG['password']; } else { $password = false; } }
$ssl = false;
if ($port[0] == "+") { $ssl = true; }
$sid = connect($_CONFIG['nick'],$address[0],$port,$ssl,$password);
if (!$sid) {
	$core->internal(" = {$lng['CONN_ERROR']} =");
	unset($sid);
}
else {
	stream_set_blocking($sid, 0);
}


/* Handle being terminated */
if (function_exists('pcntl_signal')) {
	/*
	 * Mac OS X (darwin) doesn't be default come with the pcntl module bundled
	 * with it's PHP install.
	 * Load it to take advantage of Signal Features.
	*/
	///* Currently broken
	pcntl_signal(SIGTERM, "shutdown");
	pcntl_signal(SIGINT, "shutdown");
	pcntl_signal(SIGHUP, "shutdown");
	pcntl_signal(SIGUSR1, "shutdown");
	//*/
}

while (1) {
	
	// There is NO Buffer anymore!
	// There are NO Commands anymore.
	// Handle Connection - It's all we care about now.
	if (isset($sid)) {
		$irc = parse($sid);
		if ($irc) {
			// Handle IRC.
			
			$irc_data = explode(" ",$irc);
			// Raw Handler.
			$x = 0;
			while ($x != count($api_raw)) {
				call_user_func($api_raw[$x],$irc_data);
				$x++;
			}
			if ($irc_data[1] == "001") {
				$cnick = $irc_data[2];
				$x = 0;
				while ($x != count($api_connect)) {
					$args = array(); // Empty for now
					call_user_func($api_connect[$x],$args);
					$x++;
				}
				$_PITC['network'] = $irc_data[1];
			}
			else if ($irc_data[1] == "CAP" && $irc_data[4] == ":sasl") {
				// SASL Time.
				if (isset($_CONFIG['sasl']) && strtolower($_CONFIG['sasl']) == "y") {
					$core->internal(" = IRC Network supports SASL, Using SASL! =");
					pitc_raw("AUTHENTICATE PLAIN");
				}
			}
			else if ($irc_data[0] == "AUTHENTICATE" && $irc_data[1] == "+") {
				if (isset($_CONFIG['sasl']) && strtolower($_CONFIG['sasl']) == "y") {
					$enc = base64_encode(chr(0).$_CONFIG['sasluser'].chr(0).$_CONFIG['saslpass']);
					pitc_raw("AUTHENTICATE {$enc}");
				}
			}
			else if ($irc_data[0] == "AUTHENTICATE" && $irc_data[1] == "A") {
				// IRCD Aborted SASL.
				$scrollback[0][] = " = Server aborted SASL conversation! =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[0] == "AUTHENTICATE" && $irc_data[1] == "F") {
				// Some form of Failiure, Not sure which. InspIRCD seems to send it.
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "900") {
				$scrollback[0][] = " = You are logged in via SASL! =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "904" || $irc_data[1] == "905") {
				$scrollback[0][] = " = SASL Auth failed. Incorrect details =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "906") {
				// IRCD Aborted SASL.
				$scrollback[0][] = " = Server aborted SASL conversation! =";
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "903") {
				pitc_raw("CAP END");
			}
			else if ($irc_data[1] == "353") {
				// Userlist :3
				// 2013 - Fixed in regards to a bug causing issues. :D
				// [WIP] Userlist shows every time a mode changes etc, needs to be sorted.
				$users = array_slice($irc_data,5);
				$chan = $irc_data[4];
				$users[0] = substr($users[0],1);
				//$scrollback[getWid($chan)][] = $colors->getColoredString(" [ ".implode(" ",uListSort($users))." ]","cyan");
				$userlist[getWid($chan)] = array_merge($userlist[getWid($chan)],$users);
				$userlist[getWid($chan)] = uListSort($userlist[getWid($chan)]);
				array_values($userlist[getWid($chan)]);
			}
			else if ($irc_data[1] == "324") {
				$mode = $irc_data[4];
				$chan = $irc_data[3];
				$id = getWid($chan);
				$mode = str_split($mode);
				sort($mode);
				$mode = implode("",$mode);
				$chan_modes[$id] = $mode;
			}
			else if ($irc_data[1] == "311") {
				// WHOIS.
				$scrollback[$active][] = " = WHOIS for {$irc_data[3]} =";
				$scrollback[$active][] = " * {$irc_data[3]} is ".implode(" ",array_slice($irc_data,4));
			}
			else if ($irc_data[1] == "379" || $irc_data[1] == "378") {
				$scrollback[$active][] = " * Whois data";
			}
			else if ($irc_data[1] == "PRIVMSG") {
				$ex = explode("!",$irc_data[0]);
				$source = substr($ex[0],1);
				$target = $irc_data[2];
				if ($target[0] == "#") {
					// Ulist Check!
					$source = get_prefix($source,$userlist[getWid($target)]);
				}
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				$isctcp = false;
				if ($target == $cnick) {
					// Check for CTCP!
					$msg_d = explode(" ",$message); // Reversing the previous, I know.
					$msg_d_lchar = strlen($msg_d[0][0])-1;
					$msg_d_lchar = $msg_d[0][$msg_d_lchar];
					if ($msg_d[0][0] == "" && $msg_d_lchar == "") {
						// CTCP!
						$ctcp = trim($msg_d[0],"");
						$ctcp_data = getCtcp($ctcp);
						$core->internal($colors->getColoredString("[".$source." ".$ctcp."]","light_red"));
						if ($ctcp == "PING") {
							ctcpReply($source,$ctcp,trim($msg_d[1],""));
						}
						if ($ctcp_data) {
							ctcpReply($source,$ctcp,$ctcp_data);
						}
						$isctcp = true;
						// CTCP API
						$args = array();
						$args[] = strtolower($source);
						$args[] = $ctcp;
						$x = 0;
						while ($x != count($api_ctcps)) {
							call_user_func($api_ctcps[$x],$args);
							$x++;
						}
					}
					// Message to me.
					$wid = getWid($source);
					$win = $source;
				}
				else {
					// Message to a channel.
					$wid = getWid($target);
					$win = $target;
				}
				if (!$wid && !$isctcp) {
					// No such channel. Create it.
					$windows[] = $win;
					$wid = getWid($win);
					// Wat.
					$core->internal($colors->getColoredString(" = {$lng['MSG_IN']} [".$wid.":".$win."] {$lng['FROM']} ".$source." = ","cyan"));
					// Get the new id.
				}

				$words = explode(" ",$message);
				// Last Char
				$sc = implode(" ",$words);
				$length = strlen($sc);
				$lchar = $sc[$length-1];
				// Figure out if its an action or not. -.-
				if ($words[0] == "ACTION" && $lchar == "" && !$isctcp) {
					// ACTION!
					unset($words[0]);
					$words_string = trim(implode(" ",$words),"");
					// Check for Highlight!
					if (isHighlight($words_string,$cnick)) {
						// Highlight!
						$core->internal($colors->getColoredString($target.": * ".$source." ".$words_string,"yellow"));
					}
					else {
						$core->internal($colors->getColoredString($target.": * ".$source." ".$words_string,"purple"));
					}
					// API TIME!
					$args = array();
					$args['active'] = $active;
					$args['nick'] = str_replace(str_split('~&@%+'),'',$source);
					$args['nick_mode'] = $source;
					$args['channel'] = strtolower($win);
					$args['text'] = $words_string;
					$args['text_array'] = explode(" ",$words_string);
					$x = 0;
					while ($x != count($api_actions)) {
						call_user_func($api_actions[$x],$args);
						$x++;
					}
				}
				else {
					if (!$isctcp) {
						// Message! - Check for highlight!
						//$scrollback[$wid][] = $cnick." ".$_CONFIG['nick']." ".stripos($message,$cnick)." ".stripos($message,$_CONFIG['nick']); // H/L Debug
						if (isHighlight($message,$cnick)) {
							// Highlight!
							$core->internal($colors->getColoredString($target.": <".$source."> ".$message,"yellow"));
						}
						else {
							$core->internal($target.": <".$source."> ".format_text($message));
						}
						// API TIME!
						$args = array();
						$args['nick'] = str_replace(str_split('~&@%+'),'',$source);
						$args['nick_mode'] = $source;
						$args['channel'] = strtolower($win);
						$args['text'] = $message;
						$args['text_array'] = explode(" ",$message);
						$x = 0;
						while ($x != count($api_messages)) {
							call_user_func($api_messages[$x],$args);
							$x++;
						}
						// Done
					}
				}
			}
			else if ($irc_data[1] == "NICK") {
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				if ($irc_data[2][0] == ":") {
					$nnick = substr($irc_data[2],1);
				}
				else {
					$nnick = $irc_data[2];
				}
				if ($nick != $cnick) {
					// someone changed their nick, Lets return the shizzle for them.
					$string = $colors->getColoredString("  * ".$nick." {$lng['NICK_OTHER']} ".$nnick, "green");
				}
				else {
					$string = $colors->getColoredString("  * {$lng['NICK_SELF']} ".$nnick, "green");
					$cnick = $nnick;
				}
				// No more shiny code, We dont care where they're in.
				$core->internal($string);
			}
			else if ($irc_data[0] == "PING") {
				// Do nothing.
			}
			else if ($irc_data[0] == "ERROR") {
				// Lost connection!
				$message = array_slice($irc_data,1);
				$message = substr(implode(" ",$message),1);
				$scrollback[0][] = $colors->getColoredString(" = ".$message." =","blue");
				$x = 0;
				while ($x != key($scrollback)) {
					if (isset($scrollback[$x])) {
						$core->internal($colors->getColoredString(" = {$lng['DISCONNECTED']} ".$_PITC['address']." {$lng['RECONNECT']} =","blue"));
					}
					$x++;
				}
				unset($sid);
			}
			else if ($irc_data[1] == "NOTICE") {
				// Got Notice!
				$dest = $irc_data[2];
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				
				// CTCP Stuff.
				$msg_d = explode(" ",$message); // Reversing the previous, I know.
				$msg_d_lchar = strlen($msg_d[count($msg_d)-1][0])-1;
				$ctcp = trim($msg_d[0],"");
				$ctcp_data = trim(implode(" ",array_slice($msg_d, 1)),"");
				$msg_d_lchar = $msg_d[0][$msg_d_lchar];
				
				if ($dest[0] == "#") {
					// Channel notice.
					$wid = getWid($dest);
					$scrollback[$wid][] = $colors->getColoredString(" -".$nick.":".$dest."- ".$message, "red");
				}
				else {
					// Private notice. Forward to Status window
					if ($msg_d[0][0] == "" && $msg_d_lchar == "") {
						$scrollback['0'][] = $colors->getColoredString(" <- [".$nick." ".$ctcp." reply]: ".$ctcp_data, "light_red");
					}
					else {
						$scrollback['0'][] = $colors->getColoredString(" -".$nick."- ".$message, "red");
					}
				}
			}
			else if ($irc_data[1] == "421") {
				// IRCD Threw an error regarding a command :o
				$message = array_slice($irc_data, 4);
				$message = substr(implode(" ",$message),1);
				$scrollback[0][] = strtoupper($irc_data[3])." ".$message;
			}
			else if ($irc_data[1] == "404") {
				// 3 - chan
				$message = array_slice($irc_data, 4);
				$message = substr(implode(" ",$message),1);
				$scrollback[getWid($irc_data['3'])][] = $colors->getColoredString(" = ".$message." =","light_red");
			}
			else if ($irc_data[1] == "TOPIC") {
				$chan = $irc_data[2];
				$wid = getWid($chan);
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				$chan_topic[$wid] = $message;
				$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." {$lng['TOPIC_CHANGE']} '".$message."'", "green");
			}
			else if ($irc_data[1] == "332") {
				// Topic.
				$chan = $irc_data[3];
				$wid = getWid($chan);
				$message = array_slice($irc_data, 4);
				$message = substr(implode(" ",$message),1);
				$chan_topic[$wid] = $message;
				$scrollback[$wid][] = $colors->getColoredString("  * {$lng['TOPIC_IS']} '".format_text($message)."'","green");
			}
			else if ($irc_data[1] == "333") {
				$chan = $irc_data[3];
				$wid = getWid($chan);
				$ex = explode("!",$irc_data[4]);
				$nick = $ex[0];
				$date = date(DATE_RFC822,$irc_data[5]);
				$scrollback[$wid][] = $colors->getColoredString("  * {$lng['TOPIC_BY']} ".$nick." ".$date,"green");
			}
			else if ($irc_data[1] == "MODE") {
				$chan = $irc_data[2];
				if ($chan[0] == "#") {
					$wid = getWid($chan);
				}
				else {
					$wid = "0";
				}
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$message = array_slice($irc_data,3);
				$message = implode(" ",$message);
				if ($message[0] == ":") { $message = substr($message,1); }
				if ($chan[0] == "#") {
					$nick = get_prefix($nick,$userlist[$wid]);
					// Recapture the userlist.
					$userlist[$wid] = array();
					pitc_raw("NAMES ".$chan);
					pitc_raw("MODE {$chan}");
				}
				$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." {$lng['SETS_MODE']}: ".$message,"green");
			}
			else if ($irc_data[1] == "JOIN") {
				// Joined to a channel.
				// Add a new window.
				if ($irc_data[2][0] == ":") {
					$channel = substr($irc_data[2],1);
				}
				else {
					$channel = $irc_data[2];
				}
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				
				// Did I join or did someone else?
				if ($nick == $cnick) {
					// I joined, Make a window.
					$wid = count($windows); // Our new ID.
					$windows[$wid] = $channel;
					pitc_raw("MODE {$channel}");
					$userlist[$wid] = array();
					$core->internal($colors->getColoredString("  * {$lng['JOIN_SELF']} ".$channel,"green"));
					$active = $wid;
				}
				else {
					// Someone else did.
					$wid = getWid($channel);
					$core->internal($colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['JOIN_OTHER']} ".$channel,"green"));
					// Recapture the userlist.
					$userlist[$wid] = array();
					pitc_raw("NAMES ".$channel);
				}
				// API TIME!
				$args = array();
				$args['nick'] = $nick;
				$args['channel'] = strtolower($channel);
				$args['host'] = $ex[1];
				$x = 0;
				while ($x != count($api_joins)) {
					call_user_func($api_joins[$x],$args);
					$x++;
				}
			}
			else if ($irc_data[1] == "PART") {
				$channel = $irc_data[2];
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				$wid = getWid($channel);
				if ($nick != $cnick) {
					if (isset($irc_data[3])) {
						$message = array_slice($irc_data, 3);
						$message = substr(implode(" ",$message),1);
						$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['PARTED']} ".$channel." (".$message.")","green");
					}
					else {
						$scrollback[$wid][] = $colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['PARTED']} ".$channel,"green");
					}
				}
				// Repopulate the Userlist.
				$userlist[$wid] = array();
				if ($nick != $cnick) {
					pitc_raw("NAMES ".$channel);
				}
				// API TIME!
				$args = array();
				$args['nick'] = $nick;
				$args['channel'] = strtolower($channel);
				$args['host'] = $ex[1];
				$args['text'] = $message;
				$args['text_array'] = explode(" ",$message);
				$x = 0;
				while ($x != count($api_joins)) {
					call_user_func($api_joins[$x],$args);
					$x++;
				}
			}
			else if ($irc_data[1] == "KICK") {
				$channel = $irc_data[2];
				$ex = explode("!",$irc_data[0]);
				$kicker = substr($ex[0],1);
				$wid = getWid($channel);
				$kicked = $irc_data[3];
				if ($kicked != $cnick) {
					if (isset($irc_data[4])) {
						$message = array_slice($irc_data, 4);
						$message = substr(implode(" ",$message),1);
						$scrollback[$wid][] = $colors->getColoredString("  * ".$kicked." {$lng['KICK_OTHER']} ".$kicker." (".$message.")","green");
					}
					else { // %5 chance of this ever been used. but hey still could be!
						$scrollback[$wid][] = $colors->getColoredString("  * ".$kicked." {$lng['KICK_OTHER']} ".$kicker,"green");
					}
				}
				else {
					// I've been kicked.
					if (isset($irc_data[4])) {
						$message = array_slice($irc_data, 4);
						$message = substr(implode(" ",$message),1);
						$scrollback['0'][] = $colors->getColoredString("  * ".$kicker." {$lng['KICK_SELF']} ".$channel." (".$message.")","green");
					}
					else {
						$scrollback['0'][] = $colors->getColoredString("  * ".$kicked." {$lng['KICK_SELF']} ".$channel,"green");
					}
					$active = 0;
					unset($windows[$wid], $scrollback[$wid],$userlist[$wid]);
					array_values($windows);
				}
			}
			else if ($irc_data[1] == "QUIT") {
				$ex = explode("!",$irc_data[0]);
				$nick = substr($ex[0],1);
				if ($nick != $cnick) {
					// Not me.
					$message = array_slice($irc_data, 2);
					$message = substr(implode(" ",$message),1);
					$string = $colors->getColoredString("  * ".$nick." (".$ex[1].") {$lng['QUIT']} (".$message.")","blue");
					
					$matches = 0;
					foreach ($windows as $channel) {
						if ($channel[0] == "#" || $channel == $nick) {
							if ($channel[0] == "#") {
								$ison = $chan_api->ison($nick,$channel);
								if ($ison) { pitc_raw("NAMES ".$channel); }
							}
							else {
								$ison = true;
							}
							if ($ison) {
								$wid = getwid($channel);
								$scrollback[$wid][] = $string;
								$matches++;
							}
						}
					}
					if ($matches == 0) { $scrollback[0] = $string; }
				}
			}
			else {
				$message = array_slice($irc_data, 3);
				$message = substr(implode(" ",$message),1);
				$core->internal($message);
			}
		}
	}
	// Check if any timers are being called.
	$timer->checktimers();
	usleep($refresh);
}

function pitcError($errno, $errstr, $errfile, $errline) {
	global $active,$core;
	// Dirty fix to supress connection issues for now.
	if ($errline != 171) {
		echo "PITC PHP Error: (Line ".$errline.") [$errno] $errstr in $errfile\n";
	}
}
?>