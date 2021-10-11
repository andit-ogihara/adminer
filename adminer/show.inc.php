<?php
$TABLE = $_GET["show"];
$fields = fields($TABLE);
$where = (isset($_GET["select"]) ? "" : where($_GET, $fields));

$row = null;
if ($where) {
	$select = array();
	foreach ($fields as $name => $field) {
		if (isset($field["privileges"]["select"])) {
			$as = convert_field($field);
			if ($jush == "sql" && preg_match("~enum|set~", $field["type"])) {
				$as = "1*" . idf_escape($name);
			}
			$select[] = ($as ? "$as AS " : "") . idf_escape($name);
		}
	}
	$row = array();
	if (!support("table")) {
		$select = array("*");
	}
	if ($select) {
		$result = $driver->select($TABLE, $select, array($where), $select, array(), (isset($_GET["select"]) ? 2 : 1));
		if (!$result) {
			$error = error();
		} else {
			$row = $result->fetch_assoc();
			if (!$row) { // MySQLi returns null
				$row = false;
			}
		}
		if (isset($_GET["select"]) && (!$row || $result->fetch_assoc())) { // $result->num_rows != 1 isn't available in all drivers
			$row = null;
		}
	}
}

if (!support("table") && !$fields) {
	if (!$where) { // insert
		$result = $driver->select($TABLE, array("*"), $where, array("*"));
		$row = ($result ? $result->fetch_assoc() : false);
		if (!$row) {
			$row = array($driver->primary => "");
		}
	}
	if ($row) {
		foreach ($row as $key => $val) {
			if (!$where) {
				$row[$key] = null;
			}
			$fields[$key] = array("field" => $key, "null" => ($key != $driver->primary), "auto_increment" => ($key == $driver->primary));
		}
	}
}

show_form($TABLE, $fields, $row);
