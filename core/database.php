<?php
if (!file_exists("data")) {
	mkdir("data");
}
$_DATABASE = array();
class database {
	function create($name) {
		global $api;
		$name = strtolower($name);
		if (file_exists("data/".$name.".db")) {
			$api-log(" [DB] {$name} already exists! Delete it or load it!");
			return false;
		} else {
			global $_DATABASE;
			$_DATABASE[$name] = array();
			return true;
		}
	}
	function insert($name,$values) {
		global $api,$_DATABASE;
		$name = strtolower($name);
		if (isset($_DATABASE[$name])) {
			$_DATABASE[$name][] = $values;
			return true;
		} else {
			$api->log(" [DB] Unable to modify data in {$name}! Database not loaded.");
			return false;
		}
	}
	function update($name,$values,$cond = false) {
	
	}
	function remove($name,$values,$cond = false) {
		global $api,$_DATABASE;
		$name = strtolower($name);
		if (isset($_DATABASE[$name])) {
			if ($cond) {
				// Search Stuff
				// WIP!
			} else {
				// Return everything.
				return $_DATABASE[$name];
			}
		} else {
			$api->log(" [DB] Unable to modify data in {$name}! Database not loaded.");
			return false;
		}
	}
	function select($name,$cond = false) {
		global $api,$_DATABASE;
		$name = strtolower($name);
		if (isset($_DATABASE[$name])) {
			if ($cond) {
				// Search Stuff
				$return = array();
				foreach ($_DATABASE[$name] as $col => $row) {
					// Now the fun begins.
					foreach ($cond as $col => $val) {
						$preg = "/^".str_replace("%","(.*)",$val)."$/i";
						if (isset($row[$col])) {
							if (preg_match($preg,$row[$col])) {
								if (!isset($row['index'])) {
									$row['index'] = $col;
								}
								$return[] = $row;
							}
						}
					}
				}
				return $return;
			} else {
				// Return everything.
				return $_DATABASE[$name];
			}
		} else {
			$api->log(" [DB] Unable to modify data in {$name}! Database not loaded.");
			return false;
		}	
	}
	function delete($name) {
		global $api,$_DATABASE;
		$name = strtolower($name);
		if (isset($_DATABASE[$name])) {
			if (file_exists("data/{$name}.db")) {
				unlink("data/{$name}.db");
			}
			unset($_DATABASE[$name]);
			return true;
		} else {
			$api->log(" [DB] Unable to modify data in {$name}! Database not loaded.");
			return false;
		}
	}
	function save($name) {
		global $api,$_DATABASE;
		$name = strtolower($name);
		if (isset($_DATABASE[$name])) {
			file_put_contents("data/{$name}.db",json_encode($_DATABASE[$name]));
			return true;
		} else {
			$api->log(" [DB] Unable to save {$name}! Database not loaded!");
			return false;
		}
	}
	function saveall() {
		global $api,$_DATABASE;
		$success = 0;
		foreach ($_DATABASE as $name => $val) {
			$name = strtolower($name);
			if (isset($_DATABASE[$name])) {
				file_put_contents("data/{$name}.db",json_encode($_DATABASE[$name]));
				$success++;
			} else {
				$api->log(" [DB] Unable to save {$name}! Database not loaded!");
			}
		}
		if ($success === count($_DATABASE)) {
			return true;
		} else {
			$err = (count($_DATABASE) - $success);
			$api->log(" [DB] Error saving {$err} databases!");
			return false;
		}
	}
	function load($name) {
		global $api,$_DATABASE;
		$name = strtolower($name);
		if (!file_exists("data/".$name.".db")) {
			$api->log(" [DB] Unable to load {$name}! Database doesn't exist!");
			return false;
		} else {
			if (isset($_DATABASE[$name])) {
				unset($_DATABASE[$name]);
			}
			$_DATABASE[$name] = json_decode(file_get_contents("data/".$name.".db"),true);
			return true;
		}
	}
	function unload($name) {
		global $api,$_DATABASE;
		$name = strtolower($name);
		if (isset($_DATABASE[$name])) {
			unset($_DATABASE[$name]);
			return true;
		} else {
			$api->log(" [DB] Unable to unload {$name}! Database not loaded!");
			return false;
		}
	}
}
$db = new database();
?>