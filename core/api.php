<?php
class core {
	public function internal($text) {
		global $rawlog;
		$rawlog[] = $text;
		echo $text."\n";
	}
}
$core = new core;
class pitcapi {
	public function log($text = false) {
		global $core,$cserver;
		if (!$text) {
			die("Error. Missing TEXT in function LOG");
		}
		else {
			$core->internal($text);
		}
	}
	public function addCommand($command = false,$function = false) {
		global $core;
		$core->internal(" ERROR. PITCBots does not support commands! Ignoring handler.");
	}
	public function addTextHandler($function = false) {
		global $core,$api_messages,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDTEXTHANDLER");
		}
		else {
			$api_messages[] = strtolower($function);
		}
	}
	public function addConnectHandler($function = false) {
		global $core,$api_connect,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDCONNECTHANDLER");
		}
		else {
			$api_connect[] = strtolower($function);
		}
	}
	public function addActionHandler($function = false) {
		global $core,$api_actions,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDACTIONHANDLER");
		}
		else {
			$api_actions[] = strtolower($function);
		}
	}
	public function addStartHandler($function = false) {
		global $core,$api_start,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDSTARTHANDLER");
		}
		else {
			$api_start[] = strtolower($function);
		}
	}
	public function addShutDownHandler($function = false) {
		global $core,$api_stop,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDSHUTDOWNHANDLER");
		}
		else {
			$api_stop[] = strtolower($function);
		}
	}
	public function addJoinHandler($function = false) {
		global $core,$api_joins,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDJOINHANDLER");
		}
		else {
			$api_joins[] = strtolower($function);
		}
	}
	public function addPartHandler($function = false) {
		global $core,$api_parts,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDPARTHANDLER");
		}
		else {
			$api_parts[] = strtolower($function);
		}
	}
	public function addTickHandler($function = false) {
		global $core,$api_tick,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDTICKHANDLER");
		}
		else {
			$api_tick[] = strtolower($function);
		}
	}
	public function addRawHandler($function = false) {
		global $core,$api_raw,$active;
		if (!$function) {
			$core->internal(" ERROR. Missing FUNCTION in function ADDRAWHANDLER");
		}
		else {
			$api_raw[] = strtolower($function);
		}
	}
	// Now we add the commands.
	public function pecho($text = false,$window = false) {
		global $core,$active;
		if (!$text) {
			$core->internal(" ERROR. Missing TEXT in function PECHO");
		}
		else {
			// PITCBots unlike PITC lacks windows and only has one window, The Terminal
			$core->internal($text);
		}
	}
	public function msg($channel = false,$text = false) {
		global $core,$log_irc, $sid, $cnick;
		if (!$channel) {
			$core->internal(" ERROR. Missing TEXT in function MSG");
		}
		else if (!$text) {
			$core->internal(" ERROR. Missing TEXT in function MSG");
		}
		else {
			if ($sid) {
				socket_write($sid,"PRIVMSG ".$channel." :".$text."\n");
				if ($log_irc) {
					$core->internal($channel.": <".$cnick."> ".$text);
				}
			}
			else {
				$core->internal(" ERROR. PITCBots is not CONNECTED to IRC. Cannot MSG.");
			}
		}
	}
	public function notice($channel = false,$text = false) {
		global $core,$log_irc, $sid, $cnick;
		if (!$channel) {
			$core->internal(" ERROR. Missing TEXT in function NOTICE");
		}
		else if (!$text) {
			$core->internal(" ERROR. Missing TEXT in function NOTICE");
		}
		else {
			if ($sid) {
				socket_write($sid,"NOTICE ".$channel." :".$text."\n");
				if ($log_irc) {
					$core->internal(" -".$cnick."- -> ".$text);
				}
			}
			else {
				$core->internal(" Unable to NOTICE. Not connected to IRC.");
			}
		}
	}
	public function action($channel = false,$text = false) {
		global $core,$log_irc, $colors, $sid, $cnick;
		if (!$channel) {
			$core->internal(" ERROR. Missing TEXT in function ACTION");
		}
		else if (!$text) {
			$core->internal(" ERROR. Missing TEXT in function ACTION");
		}
		else {
			if ($sid) {
				socket_write($sid,"PRIVMSG ".$channel." :ACTION ".$text."\n");
				if ($log_irc) {
					$core->internal($channel.": ".$colors->getColoredString("* ".$cnick." ".$text,"purple"));
				}
			}
			else {
				$core->internal(" ERROR. You cannot send an ACTION to IRC when you're not connected!");
			}
		}
	}
	public function quit($message = "Goodbye! For now!") {
		global $core,$sid,$api_stop;
	
		// START Handler/Hook
		$x = 0;
		while ($x != count($api_stop)) {
			$args = array(); // Empty for now
			call_user_func($api_stop[$x],$args);
			$x++;
		}
		if ($sid) {
			socket_write($sid,"QUIT :".$message."\n");
			fclose($sid);
		}
		die();
	}
	public function part($channel = false,$message = "Parting!") {
		global $core,$sid,$scrollback;
		if ($sid) {
			socket_write($sid,"PART ".$channel." :".$message."\n");
		}
		else {
			$core->internal(" ERROR. Not connected to IRC! Cannot part.");
		}
	}
	public function join($channel = false) {
		global $core,$sid,$scrollback;
		if ($sid) {
			socket_write($sid,"JOIN ".$channel."\n");
		}
		else {
			$core->internal(" ERROR. Not connected to IRC! Cannot join.");
		}
	}
	public function nick($nick = false) {
		global $core,$sid,$scrollback;
		if ($nick == false) {
			$core->internal(" ERROR. Missing NICK in function NICK");
		}
		else {
			if ($sid) {
				socket_write($sid,"NICK :".$nick."\n");
			}
			$_CONFIG['nick'] = $nick;
		}
	}
	public function raw($text = false) {
		global $core,$sid,$scrollback;
		if ($sid) {
			socket_write($sid,$text."\n");
		}
		else {
			$core->internal(" ERROR. Unable to send RAW Data, not connected to IRC!");
		}
	}
	public function mode($chan = false,$mode = false) {
		global $core,$sid,$scrollback;
		if (!$chan) {
			$core->internal(" ERROR. Missing CHANNEL in function MODE");
		}
		else if (!$mode) {
			$core->internal(" ERROR. Missing MODE(S) in function MODE");
		}
		else {
			if ($chan[0] == "#") {
				if ($sid) {
					socket_write($sid,"MODE {$chan} {$mode}\n");
				}
				else {
					$core->internal(" ERROR. Unable to set MODE. Not connected to IRC.");
				}
			}
			else {
				$core->internal(" ERROR. Invalid CHANNEL in function MODE");
			}
		}
	}
	public function ctcp($nick = false,$ctcp = false) {
		global $core,$sid,$scrollback;
		if (!$nick) {
			$core->internal(" ERROR. Missing NICK in function CTCP");
		}
		else if (!$ctcp) {
			$core->internal(" ERROR. Missing CTCP in function CTCP");
		}
		else {
			if ($sid) {
				ctcp($nick,$ctcp);
			}
			else {
				$sthis->internal(" ERROR. Unable to CTCP. Not connected to IRC.");
			}
		}
	}
	public function topic($chan = false,$text = false) {
		global $core,$sid,$scrollback;
		if (!$chan) {
			$core->internal(" ERROR. Missing CHANNEL in function TOPIC");
		}
		else if (!$ctcp) {
			$core->internal(" ERROR. Missing CHANNEL in function TOPIC");
		}
		else {
			if ($sid) {
				socket_write($sid,"TOPIC {$chan} :{$text}\n");
			}
			else {
				$core->internal(" ERROR. Unable to set TOPIC in {$chan}. Not connected to IRC.");
			}
		}
	}
	public function ctcpreply($nick = false,$ctcp = false,$text = false) {
		global $core,$sid,$scrollback;
		if (!$nick) {
			$core->internal(" ERROR. Missing NICK in function CTCPREPLY");
		}
		else if (!$ctcp) {
			$core->internal(" ERROR. Missing CTCP in function CTCPREPLY");
		}
		else if (!$text) {
			$core->internal(" ERROR. Missing TEXT in function CTCPREPLY");
		}
		else {
			if ($sid) {
				ctcpreply($nick,$ctcp,$text);
			}
			else {
				$core->internal(" ERROR. Unable to CTCPREPLY. Not connected to IRC.");
			}
		}
	}
	// Window Control
	public function addWindow($name) {
		// Not Possible. Function left in for PITC Client Support.
		return false;
	}
	public function delWindow($name) {
		// Not Possible. Function left in for PITC Client Support.
		return false;
	}
	public function checkWindow($id) {
		// Not Possible. Function left in for PITC Client Support.
		return false;
	}
	public function color($col,$text) {
		return "".$col.$text."";
	}
	public function colour($col,$text) {
		return "".$col.$text."";
	}
	public function bold($text) {
		return "".$text."";
	}
	public function italic($text) {
		return "".$text."";
	}
}
class channel {
	public function topic($chan) {
		global $core,$chan_topic;
		$chan = getWid($chan);
		if ($chan) {
			if (isset($chan_topic[$chan])) {
				return $chan_topic[$chan];
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	public function modes($chan) {
		global $core,$chan_modes;
		$chan = getWid($chan);
		if ($chan) {
			if (isset($chan_modes[$chan])) {
				return $chan_modes[$chan];
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	public function users($chan) {
		global $core,$userlist;
		$chan = strtolower(getWid($chan));
		if ($chan) {
			if (isset($userlist[$chan])) {
				return $userlist[$chan];
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	public function ison($user,$chan = false) {
		global $core,$userlist,$active,$api;
		/* Derived from GetPrefix() */
		/* Used in the core, dont remove or edit! */
		if ($chan == FALSE) { $wid = $active; } else { $wid = getwid($chan); }
		if (isset($userlist[$wid])) {
			$nicks = $userlist[$wid];
		}
		else {
			$nicks = 0;
		}
		if ($nicks > 0) {
			$nick = strtolower($user);
			$nicknames = array();
			foreach ($nicks as $n) { $nicknames[] = trim(strtolower($n),"~&@%+"); }
			$match = strtolower($user);
			$ret = "PITC".array_search($match,$nicknames);
			if ($ret != "PITC") {
				return True;
			}
			else {
				return False;
			}
		}
		else {
			return False;
		}
	}
}
class timer {
	public function addtimer($delay = false,$rep = false,$function = false ,$args = false) {
		global $core,$timers,$scrollback;
		if ($delay == false | $function == false) {
			if (!$delay) {
				$core->internal(" ERROR. Missing DELAY in function TIMER->ADDTIMER");
			}
			else {
				$core->internal(" ERROR. Missing FUNCTION in function TIMER->ADDTIMER");
			}
			return false;
		}
		else {
			$dat = array();
			$dat['delay'] = $delay;
			$dat['rep'] = $rep;
			$dat['function'] = $function;
			$dat['args'] = $args;
			$dat['next'] = $this->calcnext($delay);
			$timers[] = $dat;
			$core->internal(" Added Timer with delay {$delay}");
			end($timers); 
			return $timers[key($timers)]; 
		}
	}
	public function deltimer($id) {
		// Deletes a timer with the specified ID.
		global $core,$timers;
		if (!$id) {
			$core->internal(" ERROR. Missing ID in function TIMER->DELTIMER");
		}
		else {
			if (isset($timers[$id])) {
				unset($timers[$id]);
				$core->internal(" Timer {$id} Removed.");
				return true;
			}
			else {
				$core->internal(" Timer {$id} not found!");
				return false;
			}
		}
	}
	public function checktimers() {
		global $core,$timers;
		foreach ($timers as $id => $tmr) {
			if ($tmr['next'] == time()) {
				// Trigger timer.
				call_user_func($tmr['function'], $tmr['args']);
				// Update Next Call.
				$timers[$id]['next'] = $this->calcnext($tmr['delay']);
				if ($tmr['rep'] != false) {
					// Not continuous.
					$timers[$id]['rep']--;
					if ($timers[$id]['rep'] == 0) {
						// Remove - Actually a Debug Line I never removed but in this case Its good.
						$core->internal(" Unset timer {$id} running funct '{$tmr['function']}'");
						unset($timers[$id]);
					}
				}
			}
		}
	}
	public function texttosec($text) {
		global $core,$scrollback;
		// Returns the contents of $text in seconds, e.g. 1m = 60 Seconds
		if (!$text) {
			$core->internal(" ERROR. Missing TEXT in function TIMER->TEXTOSEC");
		}
		else {
		if (is_numeric($text)) {
			return $text;
		}
		else {
			$text = strtolower($text);
			$num = substr($text, 0, -1);
			if (substr($text,-1) === "s") {
				// Seconds
				return $num;
			}
			elseif (substr($text,-1) === "m") {
				// Mins
				return (60*$num);
			}
			elseif (substr($text,-1) === "h") {
				// Hours
				return ((60*$num)*60);
			}
			elseif (substr($text,-1) === "d") {
				// Days?!
				return (((60*$num)*60)*24);
			}
			elseif (substr($text,-1) === "w") {
				// Weeks - Really now?
				return ((((60*$num)*60)*24)*7);
			}
			else {
				// Just seconds then.
				return $num;
			}
			
		}
		}
	}
	private function calcnext($text) {
		// Calculated the next time a timer will go off.
		$sec = 0;
		$time = explode(" ",$text);
		foreach ($time as $t) {
			$sec += $this->texttosec($t);
		}
		return time()+$sec;
	}
}
?>