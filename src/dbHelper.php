<?php

// Here is where the SQL happens. You find for every table in the database a corresponding class that handles databas communication.
// Some of the classes extend the classes StandardTable or SortableStandardTable. Standardtable offers basic functionality to insert,
// update, remove and query database-entries. SortableStandardTable additionally offers methods to sort and change order of records
// in tables that have a pos field.

class StandardTable {
	var $tabName;
	var $pk_name;
	var $pk_value;
	var $where;
	var $type_name;
	var $type_value;
	var $num_attributes;
	var $attributes;
	
	function __construct($tabName, $keys, $values, $type_name) {

		$this->num_attributes = count($keys);
		// array $values auffüllen damit gleich viele werte wie in $keys existieren
		for($i=0; $i<$this->num_attributes;$i++) {
			if($i < count($values))	{
				$this->attributes[$keys[$i]] = mysql_real_escape_string($values[$i]);
				$this->$keys[$i] = mysql_real_escape_string($values[$i]);
			} else {
				$this->attributes[$keys[$i]] = "";	
				$this->$keys[$i] = "";	
			}
		}
		
		$this->tabName = $tabName;
		$this->pk_name = $keys[0];
		$this->pk_value = $values[0];
		
		$this->type_name = $type_name;
		if($type_name != "") $this->type_value = $this->$type_name;
		
		if($this->type_name != "" && $this->type_value != "") $this->where = $this->type_name."='".$this->type_value."'";
		else $this->where = "";
	}
	
	function get($optSelect="") {
		global $con;
		if($optSelect != "") $optSelect =", ".$optSelect;
		
		$statement = "SELECT * $optSelect FROM ".$this->tabName." WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode get()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		return $row;
	}
	
	function getOnly($select="") {
		global $con;
	
		$statement = "SELECT $select FROM ".$this->tabName." WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode getOnly()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		return $row;
	}
	
	function getAll($optSelect="", $optWhere="", $optOrder="") {
		global $con;
		
		if($optSelect == "") $optSelect = "*";
		
		if($optOrder != "") $optOrder = " ORDER BY ".$optOrder;
		
		if($this->where != "") {
			if($optWhere != "") $optWhere .= " AND ";
			$statement = "SELECT $optSelect FROM ".$this->tabName." WHERE $optWhere".$this->where.$optOrder;
		} else {
			if($optWhere != "") $optWhere = " WHERE ".$optWhere;
			$statement = "SELECT $optSelect FROM ".$this->tabName.$optWhere.$optOrder;
		}
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode getAll()<br>".mysql_error()."<br>".$statement);
		return $result;
	}
	
	function remove($optWhere="") {
		global $con;
		if($optWhere!="") $where = $optWhere;
		else if($this->pk_name=="" || $this->pk_value=="") die("Failure: Tabelle ".$this->tabName.": Methode remove()<br>Ungültige Parameter!");
		else $where = $this->pk_name."='".$this->pk_value."'";
		$statement = "DELETE FROM ".$this->tabName." WHERE ".$where;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode remove()<br>".mysql_error()."<br>".$statement);
	}

	function insert($transaction=true) {
		global $con;
		
		$attStr = "";
		$valStr = "";
		$keys = array_keys($this->attributes);
		for($i=1; $i<$this->num_attributes; $i++) {
			if($i > 1) {
				$attStr .= ",";
				$valStr .= ",";
			}
			$attStr .= $keys[$i];
			if($this->attributes[$keys[$i]]=="NULL") $valStr .= "NULL";
			else $valStr .= "'".$this->attributes[$keys[$i]]."'";
		}

		if($transaction) mysql_query("START TRANSACTION", $con);
		
		$statement = "INSERT INTO ".$this->tabName." ($attStr) VALUES ($valStr)";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode insert()<br>".mysql_error()."<br>".$statement);

		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode insert()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_array($result);
		
		if($transaction) mysql_query("COMMIT", $con);
		
		return $row[$this->pk_name];
	}

	function update() {
		global $con;
		
		$firstinuse=false;
		$setStr = "";
		$keys = array_keys($this->attributes);
		for($i=1; $i<$this->num_attributes; $i++) {
			//if($this->attributes[$keys[$i]] != "") {
				if($firstinuse && $this->attributes[$keys[$i]]!="LEAVE ALONE") $setStr .= ",";
				if($this->attributes[$keys[$i]]=="NULL") {
					$setStr .= $keys[$i]."=NULL";
					$firstinuse = true;
				} else if($this->attributes[$keys[$i]]!="LEAVE ALONE") {
					$setStr .= $keys[$i]."='".$this->attributes[$keys[$i]]."'";
					$firstinuse = true;
				}
			//}
		}


		$statement = "UPDATE ".$this->tabName." SET $setStr WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode update()<br>".mysql_error()."<br>".$statement);
	}


}

class SortableStandardTable extends StandardTable {
	var $newFirst;
	function __construct($newFirst, $tabName, $attributes, $values, $type_name) {
		$this->newFirst = $newFirst;
		parent::__construct($tabName, $attributes, $values, $type_name);
	}
	
	function getAll($optSelect="", $optWhere="", $optOrder="") {
		global $con;
		
		if($optSelect == "") $optSelect = "*";

		if($this->newFirst) $order = "DESC";
		else $order = "ASC";
		
		if($optOrder != "") $optOrder .= ",";
		
		if($this->where != "") {
			if($optWhere != "") $optWhere .= " AND ";
			$statement = "SELECT $optSelect FROM ".$this->tabName." WHERE $optWhere".$this->where." ORDER BY $optOrder pos $order";
		} else {
			if($optWhere != "") $optWhere = "WHERE ".$optWhere;
			$statement = "SELECT $optSelect FROM ".$this->tabName." $optWhere ORDER BY $optOrder pos $order";
		}
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode getAll()<br>".mysql_error()."<br>".$statement);
		return $result;
	}
	
	
	public function down($id="") {
		if($id=="" && $this->pk_name != "" && $this->pk_value != "") $id = $this->pk_value;
		
		if($id != "") {
			if($this->newFirst) $this->changePos($id, true);
			else $this->changePos($id, false);
		}
	}
	
	public function up($id="") {
		if($id=="" && $this->pk_name != "" && $this->pk_value != "") $id = $this->pk_value;

		if($id != "") {
			if($this->newFirst) $this->changePos($id, false);
			else $this->changePos($id, true);
		}
	}
	
	private function changePos($id, $down) {

		global $con;
		if($this->type_name != "" && $this->where != "") {
			$where = " AND ".$this->where;
			$sel_type = ", ".$this->type_name." ";
		} else {
			$where = "";
			$sel_type = "";
		}
		
		mysql_query("START TRANSACTION", $con);
		
		$statement = "SELECT pos $sel_type FROM ".$this->tabName." WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_row($result);
		$pos = $row[0];
		$type = $row[1];

		if($down) $statement = "SELECT max(pos) as mpos FROM ".$this->tabName." WHERE pos < $pos $where";
		else $statement = "SELECT min(pos) as mpos FROM ".$this->tabName." WHERE pos > $pos $where";
		$result = mysql_query ($statement , $con) OR  die("Failure: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		if($row->mpos != NULL) {
			$statement = "UPDATE ".$this->tabName." SET pos = $pos WHERE pos = ".$row->mpos." $where";
			$result = mysql_query ($statement , $con) OR  die("Failure: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysql_error()."<br>".$statement);
	
			$statement = "UPDATE ".$this->tabName." SET pos = ".$row->mpos." WHERE ".$this->pk_name." = '$id' $where";
			$result = mysql_query ($statement , $con) OR  die("Failure: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysql_error()."<br>".$statement);
		}
		mysql_query("COMMIT", $con) OR die("Failure: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysql_error()."<br>".$statement);
		
	}
	
	function nextInsertPos() {
		global $con;
		
		if($this->type_name != "" && $this->where != "") $statement = "SELECT max(pos) as pos FROM ".$this->tabName." WHERE ".$this->where;
		else  $statement = "SELECT max(pos) as pos FROM ".$this->tabName;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode nextInsertPos()<br>".mysql_error()."<br>".$statement);

		if(mysql_num_rows($result) == 0) $pos = 1;
		else {
			$row = mysql_fetch_object($result);
			$pos = $row->pos + 1;
		}
		return $pos;
	}

	function insert($transaction=true) {
		global $con;
		
		$attStr = "";
		$valStr = "";
		$keys = array_keys($this->attributes);
		for($i=1; $i<$this->num_attributes; $i++) {
			if($i > 1) {
				$attStr .= ",";
				$valStr .= ",";
			}
			$attStr .= $keys[$i];
			if($this->attributes[$keys[$i]]=="NULL") $valStr .= "NULL";
			else $valStr .= "'".$this->attributes[$keys[$i]]."'";
		}

		$pos = $this->nextInsertPos();


		if($transaction) mysql_query("START TRANSACTION", $con);
		
		$statement = "INSERT INTO ".$this->tabName." ($attStr,pos) VALUES ($valStr,$pos)";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Method insert()<br>".mysql_error()."<br>".$statement);
 
		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Method insert() (for id) <br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		
		if($transaction) mysql_query("COMMIT", $con);
		
		return $row->id;
	}

}


class Scenario extends SortableStandardTable {

	function __construct() {

		$attributes = array("id", "name", "description", "txt", "bind_local", "def", "visible");
		$values = func_get_args();
		$type_name = "visible";
		// __construct(newfirst, tablename, attributes, values, type_name);
		parent::__construct(true, "Scenario", $attributes, $values, $type_name);
	}
	
	function remove() {
		global $con;
		$statement = "UPDATE ".$this->tabName." SET visible='f' WHERE id=".$this->id;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Class Scenario: Methode remove()<br>".mysql_error()."<br>".$statement);
	}
}

class Test extends StandardTable {

	function __construct() {

		$attributes = array("id", "name", "description");
		$values = func_get_args();
		$type_name = "";
		// __construct(tablename, attributes, values, type_name);
		parent::__construct("Test", $attributes, $values, $type_name);
	}
	
	function insert($transaction=true) {
		global $con;
		
		if($transaction) mysql_query("START TRANSACTION", $con);
		
		$statement = "INSERT INTO ".$this->tabName." (name, description) VALUES ('".$this->name."', '".$this->description."')";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode insert()<br>".mysql_error()."<br>".$statement);

		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode insert()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		
		$vObj = new Version($row->id, "", 0, "a");
		$vObj->insert(false);
				
		if($transaction) mysql_query("COMMIT", $con);
		
		return $row->id;
	}
	
	function duplicate($version) {
		global $con;
		
		mysql_query("START TRANSACTION", $con);
		
		// load test data
		$row = $this->get();
		
		// insert new test
		$statement = "INSERT INTO ".$this->tabName." (name, description) VALUES ('".addslashes($row->name)." copy', '".addslashes($row->description)."')";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode duplicate()<br>".mysql_error()."<br>".$statement);
		// get new test id
		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode duplicate()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		$newid = $row->id;
		// load version
		$vObj = new Version($this->id, $version);
		$row = $vObj->get();
		// insert new version
		$vObj = new Version($newid, "", $row->delay, $row->delay_party);
		$newversion = $vObj->insert(false);
		
		// load calls
		$cObj = new RobotRun("", $this->id, $version);
		$cRes = $cObj->getAll();
		// insert calls
		while($cRow = mysql_fetch_object($cRes)) {
			$cObj = new RobotRun("", $newid, $newversion, $cRow->description, addslashes($cRow->txt), $cRow->def, $cRow->bind_local, $cRow->ip_address, $cRow->monitor, $cRow->extended_parameters, $cRow->log, $cRow->scenario_id);
			$cObj->insert(false);
		}
		
		mysql_query("COMMIT", $con);
		
		return $newid;
	}
	
	function remove() {
		global $con;
		$statement = "UPDATE ".$this->tabName." SET visible='f' WHERE id=".$this->id;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode remove()<br>".mysql_error()."<br>".$statement);
	}

	function getOverview($optOrder="") {
		global $con;
		
		if($optOrder != "") $optOrder = " ORDER BY ".$optOrder;
		
		$statement = "SELECT t.id, t.name, t.description, DATE_FORMAT(t.created,'%Y.%m.%d') AS created, DATE_FORMAT(MAX(v.created), '%Y.%m.%d') AS last_modified, COUNT(r.id) AS run_count FROM Test t, Version v LEFT JOIN Run r ON (r.test_version = v.version AND r.test_id = v.id) WHERE v.id=t.id AND t.visible='t' GROUP BY t.id".$optOrder;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode getOverview()<br>".mysql_error()."<br>".$statement);	
		return $result;
	}
	
	function getVersions() {
		global $con;
		$statement = "SELECT version FROM Version WHERE id=".$this->id." AND visible='t' ORDER BY version DESC";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode getVersions()<br>".mysql_error()."<br>".$statement);	
		return $result;
	}

}

class Version extends StandardTable {

	function __construct() {

		$attributes = array("id", "version", "delay", "delay_party");
		$values = func_get_args();
		$type_name = "id";
		// __construct(newfirst, tablename, attributes, values, type_name);
		parent::__construct("Version", $attributes, $values, $type_name);
	}
	
	function insert($transaction=true) {
		global $con;
		
		if($transaction) mysql_query("START TRANSACTION", $con);
		
		// $pos = parent::nextInsertPos();
		if($this->version == "") {
			$statement = "SELECT MAX(version) as version_number FROM Version WHERE id=".$this->id;
			$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode insert()<br>".mysql_error()."<br>".$statement);
			
			if(mysql_num_rows($result) == 0) $version_number = 1;
			else {
				$row = mysql_fetch_object($result);
				$version_number = $row->version_number + 1;
			}
		} else $version_number = $this->version;

		
		
		$statement = "INSERT INTO ".$this->tabName." (id, version, delay, delay_party, visible) VALUES (".$this->id.", ".$version_number.", ".$this->delay.", '".$this->delay_party."', 't')";
		$result = mysql_query ($statement , $con) OR die("Failure: Table ".$this->tabName.": Methode insert()<br>".mysql_error()."<br>".$statement);
	
		if($transaction) mysql_query("COMMIT", $con);
		
		return $version_number;
	}
	
	
	
	function update() {
		global $con;
		
		mysql_query("START TRANSACTION", $con);
		
		// If this version has already been tested (and thus a run has been created), then create a new version.
		if($this->hasRun()) {
			$new_version_number = $this->duplicateVersion(false);
			$this->duplicateRobotRuns($new_version_number, "", false);
			$this->version = $new_version_number;
		}
		
		$statement = "UPDATE Version SET delay=".$this->delay.", delay_party='".$this->delay_party."' WHERE id=".$this->id." AND version='".$this->version."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode update()<br>".mysql_error()."<br>".$statement);
		
		mysql_query("COMMIT", $con) OR die("Failure: Class Version: Methode update()<br>".mysql_error()."<br>COMMIT failed!");
	}
	
	function get() {
		global $con;
		$statement = "SELECT * FROM Version WHERE id=".$this->id." AND version='".$this->version."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode get()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		
		return $row;
	}
	
	function remove() {
		global $con;
		$statement = "UPDATE ".$this->tabName." SET visible='f' WHERE id=".$this->id." AND version='".$this->version."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Methode remove()<br>".mysql_error()."<br>".$statement);
	}

	function hasRun() {
		$rObj = new Run("", $this->id, $this->version);
		$res = $rObj->getAll();
		return mysql_num_rows($res) > 0;
	}
	
	function duplicateVersion($transaction=true) {
		global $con;
		
		if($transaction) mysql_query("START TRANSACTION", $con);
		
		$detailRow = $this->get();
		$vObj = new Version($detailRow->id, "", $detailRow->delay, $detailRow->delay_party);
		$new_version_number = $vObj->insert(false);
		
		// testsuite_detail.php should know the new version number
		$_SESSION["s_version"] = $new_version_number;
		
		if($transaction) mysql_query("COMMIT", $con);
		
		return $new_version_number;
	}
	
	function duplicateRobotRuns($new_version_number, $return_new_id, $transaction=true) {
		global $con;
		
		$new_id = false;
		
		if($transaction) mysql_query("START TRANSACTION", $con);

		$cObj = new RobotRun("", $this->id, $this->version);
		$cRes = $cObj->getAll();
		while($cRow = mysql_fetch_object($cRes)) {
			$cObj = new RobotRun("", $this->id, $new_version_number, $cRow->description, addslashes($cRow->txt), $cRow->def, $cRow->bind_local, $cRow->ip_address, $cRow->monitor, $cRow->extended_parameters, $cRow->log, $cRow->scenario_id);
			if($cRow->id == $return_new_id) $new_id = $cObj->insert(false);
			else $cObj->insert(false);
		}
		
		if($transaction) mysql_query("COMMIT", $con);
		
		return $new_id;
	}

}

class RobotRun {

	var $id;
	var $test_id;
	var $test_version;
		
	var $description;
	var $txt;
	var $def;
	var $bind_local;
	
	var $ip_address;
	var $monitor;
	var $extended_parameters;
	var $log;
	var $scenario_id;

	
	function RobotRun($id="", $test_id="", $test_version="", $description="", $txt="", $def="", $bind_local="", $ip_address="", $monitor="", $extended_parameters="", $log="", $scenario_id="") {
		$this->id = $id;
		$this->test_id = $test_id;
		$this->test_version = $test_version;
		$this->description = $description;
		$this->txt = $txt;
		$this->def = $def;
		$this->bind_local = $bind_local;
		$this->ip_address = $ip_address;
		$this->monitor = $monitor;
		$this->extended_parameters = $extended_parameters;
		$this->log = $log;
		$this->scenario_id = $scenario_id;		
	}
	

	function insert($transaction=true) {
		global $con;		
		
		if($transaction) mysql_query("START TRANSACTION", $con);
		
		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$vObj->duplicateRobotRuns($new_version_number, "", false);
			$this->test_version = $new_version_number;
		}

				
		$pos = $this->nextInsertPos();
		
		$statement = "INSERT INTO RobotRun (test_id, test_version, description, txt, def, bind_local, ip_address, monitor, extended_parameters, log, scenario_id, pos) VALUES (" 
			. $this->test_id.", '".$this->test_version."', '".$this->description."', '".$this->txt."', '".$this->def."', '".$this->bind_local."', '".$this->ip_address."', '".$this->monitor."', '".$this->extended_parameters."', '".$this->log."',".$this->scenario_id.", $pos)";

		$result = mysql_query ($statement , $con) OR die("Failure: Table RobotRun: Methode insert()<br>".mysql_error()."<br>".$statement);

		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysql_query ($statement , $con) OR die("Failure: Table RobotRun: Methode insert()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
	
		if($transaction) mysql_query("COMMIT", $con);
		
		return $row->id;
	}
	
	function update($scenario=false) {
		global $con;
		
		mysql_query("START TRANSACTION", $con);

		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$new_call_id = $vObj->duplicateRobotRuns($new_version_number, $this->id, false);
			$this->test_version = $new_version_number;
			$this->id = $new_call_id;
		}

		$statement = "UPDATE RobotRun SET ip_address='".$this->ip_address."', monitor='".$this->monitor."', extended_parameters='".$this->extended_parameters."', log='".$this->log."', description='".$this->description."', txt='".$this->txt."', def='".$this->def."', bind_local='".$this->bind_local."', scenario_id=".$this->scenario_id."'";

		if($scenario) {
			$statement .= ", description='".$this->description."', txt='".$this->txt."', def='".$this->def."', bind_local='".$this->bind_local."', scenario_id=".$this->scenario_id;
		}
		
		$statement .= " WHERE id=".$this->id;
		$result = mysql_query ($statement , $con) OR die("Failure: Table RobotRun: Methode update()<br>".mysql_error()."<br>".$statement);
		
		mysql_query("COMMIT", $con) OR die("Failure: Class RobotRun: Methode update()<br>".mysql_error()."<br>COMMIT failed!");
	}
	
	function nextInsertPos() {
		global $con;
		
		$statement = "SELECT max(pos) as pos FROM RobotRun WHERE test_id=".$this->test_id." AND test_version='".$this->test_version."'";

		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle RobotRun: Methode nextInsertPos()<br>".mysql_error()."<br>".$statement);

		if(mysql_num_rows($result) == 0) $pos = 1;
		else {
			$row = mysql_fetch_object($result);
			$pos = $row->pos + 1;
		}
		return $pos;
	}
	
	function getAll($optSelect="") {
		global $con;
		
		$select = $optSelect=="" ? "c.*, s.name": $optSelect;
		$statement = "SELECT $select FROM RobotRun c, Scenario s WHERE s.id=c.scenario_id";
		if($this->test_id != "") $statement .= " AND test_id=".$this->test_id;
		if($this->test_version != "") $statement .= " AND test_version='".$this->test_version."'";
		$statement .= " ORDER BY c.pos";
		$result = mysql_query ($statement , $con) OR die("Failure: Table RobotRun: Method getAll()<br>".mysql_error()."<br>".$statement);

		return $result;
	}
	
	function remove() {
		global $con;
		
		mysql_query("START TRANSACTION", $con) OR die("Failure: Tabelle RobotRun: Methode remove()<br>".mysql_error()."<br>START TRANSACTION failed!");
		
		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$new_call_id = $vObj->duplicateRobotRuns($new_version_number, $this->id, false);
			$this->test_version = $new_version_number;
			$this->id = $new_call_id;
		}

		$statement = "DELETE FROM RobotRun WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Table RobotRun: Methode remove()<br>".mysql_error()."<br>".$statement);
		
		mysql_query("COMMIT", $con) OR die("Failure: Tabelle RobotRun: Methode remove()<br>".mysql_error()."<br>COMMIT failed!");
	}
	
	function get($optSelect="") {
		global $con;

		if($optSelect != "") $optSelect = ", ".$optSelect;

		$statement = "SELECT * $optSelect FROM RobotRun WHERE id=".$this->id;
		$result = mysql_query ($statement , $con) OR die("Failure: Table RobotRun: Methode get()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		return $row;
	}
	
	function getOnly($select="") {
		global $con;

		$statement = "SELECT $select FROM RobotRun WHERE id=".$this->id;
		$result = mysql_query ($statement , $con) OR die("Failure: Table RobotRun: Methode getOnly()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		return $row;
	}
	
	function getOptions($row, $additionalParameters="") {
		$options = "";
		// $this->addToOptions($options, $row->extended_parameters);
		// $this->addToOptions($options, "-bind_local", $row->bind_local);
		// $this->addToOptions($options, "-i", $row->a_i);
		// $this->addToOptions($options, "-m", $row->a_m);
		// if($row->a_nd == "t") $this->addToOptions($options, "-nd");
		// if($row->a_nr == "t") $this->addToOptions($options, "-nr");
		// $this->addToOptions($options, "-t", $row->a_t);
		// $this->addToOptions($options, "-p", $row->a_p);
		// $this->addToOptions($options, "-r", $row->a_r);
		// $this->addToOptions($options, "-timeout", $row->a_timeout);
		// if($row->a_pause_msg_ign == "t") $this->addToOptions($options, "-pause_msg_ign");
		// if($row->a_trace_msg == "t") $this->addToOptions($options, "-trace_msg");
		// if($row->a_trace_shortmsg == "t") $this->addToOptions($options, "-trace_shortmsg");
		// if($additionalParameters != "") {
			// $options .= " ".$additionalParameters;
			// $this->addToOptions($options, $row->extended_parameters);
		// }
		return $options;
	}
	
	private function addToOptions(&$options, $option, $value="-1") {
		$option = trim($option);
		$value = trim($value);
		if($option != "" && $value != "" && $value != "-1") {
			$options .= " ".$option;
			$options .= " ".$value;
		} else if($option != "" && $value == "-1") {
			$options .= " ".$option;		
		}
		
	}


	public function down() {
		$this->changePos(false);
	}
	
	public function up() {
		$this->changePos(true);
	}
	
	private function changePos($down) {

		global $con;
		
		mysql_query("START TRANSACTION", $con);

		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$new_call_id = $vObj->duplicateRobotRuns($new_version_number, $this->id, false);
			$this->test_version = $new_version_number;
			$this->id = $new_call_id;
		}
	
		
		$statement = "SELECT pos FROM RobotRun WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle RobotRun: Methode changePos()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_row($result);
		$pos = $row[0];

		if($down) $statement = "SELECT max(pos) as mpos FROM RobotRun WHERE pos < $pos AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
		else $statement = "SELECT min(pos) as mpos FROM RobotRun WHERE pos > $pos AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
		$result = mysql_query ($statement , $con) OR  die("Failure: Tabelle RobotRun: Methode changePos()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		if($row->mpos != NULL) {
			$statement = "UPDATE RobotRun SET pos = $pos WHERE pos = ".$row->mpos." AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
			$result = mysql_query ($statement , $con) OR  die("Failure: Tabelle RobotRun: Methode changePos()<br>".mysql_error()."<br>".$statement);
	
			$statement = "UPDATE RobotRun SET pos = ".$row->mpos." WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
			$result = mysql_query ($statement , $con) OR  die("Failure: Tabelle RobotRun: Methode changePos()<br>".mysql_error()."<br>".$statement);
		}
		mysql_query("COMMIT", $con) OR die("Failure: Tabelle RobotRun: Methode changePos()<br>".mysql_error()."<br>COMMIT failed.");
		
	}
}

class Run extends StandardTable {

	function __construct() {

		$attributes = array("id", "test_id", "test_version", "timestamp", "success");
		$values = func_get_args();
		$type_name = "";
		// __construct(tablename, attributes, values, type_name);
		parent::__construct("Run", $attributes, $values, $type_name);
	}
	
	function update() {
		global $con;

		
		$statement = "UPDATE ".$this->tabName." SET success='".$this->success."' WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle Run: Methode update()<br>".mysql_error()."<br>".$statement);
		return $result;
	}

	function getAll($optSelect="", $optWhere="") {
		global $con;
		
		if($optSelect == "") $optSelect = "*";
		if($optWhere != "") $optWhere = " AND ".$optWhere;

		$statement = "SELECT $optSelect FROM Run WHERE test_id=".$this->test_id." AND test_version='".$this->test_version."' $optWhere ORDER BY timestamp DESC";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle Run: Methode getAll()<br>".mysql_error()."<br>".$statement);
		return $result;
	}
	
	function get($optSelect="") {
		global $con;

		if($optSelect != "") $optSelect = ", ".$optSelect;

		$statement = "SELECT * $optSelect FROM Run WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysql_query ($statement , $con) OR die("Failure: Table Run: Methode get()<br>".mysql_error()."<br>".$statement);
		$row = mysql_fetch_object($result);
		return $row;
	}
	
	function remove() {
		global $con;
		$statement = "DELETE FROM ".$this->tabName." WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle ".$this->tabName.": Class Run: Methode remove()<br>".mysql_error()."<br>".$statement);
	}
}

class Run_Call {
	var $run_id;
	var $call_id;
	var $test_id;
	var $test_version;
	var $std_error;
	var $exit_code;
	var $log;
	
	function Run_Call($run_id="", $test_id="", $test_version="", $call_id="", $exit_code="", $std_error="",  $std_out="", $log="", $output="", $report="") {
		$this->run_id = $run_id;
		$this->call_id = $call_id;
		$this->test_id = $test_id;
		$this->test_version = $test_version;
		$this->std_error = $std_error;
		$this->exit_code = $exit_code;
		$this->std_out = $std_out;
		$this->log = $log;
		$this->output = $output;
		$this->report = $report;
	}
	
	function insert() {
		global $con;
		
		$statement = "INSERT INTO Run_Call (run_id, call_id, test_id, test_version, timestamp, exit_code, std_error, std_out, log, output, report) VALUES (".$this->run_id.", ".$this->call_id.", ".$this->test_id.", ".$this->test_version.",  0, '-1', '".$this->std_error."', '', '', '', '')";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle Run_Call: Methode insert()<br>".mysql_error()."<br>".$statement);		
	}
	
	function update() {
		global $con;
		$statement = "UPDATE Run_Call SET std_error='".$this->std_error."', exit_code='".$this->exit_code."' , std_out='".$this->std_out."', log='".$this->log."', output='".$this->output."', report='".$this->report."' WHERE run_id=".$this->run_id." AND call_id=".$this->call_id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle Run_Call: Methode update()<br>".mysql_error()."<br>".$statement);		
	}
	
	function setTime() {
		global $con;
		
		$statement = "UPDATE Run_Call SET timestamp=CURRENT_TIMESTAMP() WHERE run_id=".$this->run_id." AND call_id=".$this->call_id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle Run_Call: Methode setTime()<br>".mysql_error()."<br>".$statement);		
	}
	
	function getAll($party="") {
		global $con;
		
		if($party!="") $party="AND c.party='".$party."'";

		$statement = "SELECT c.*, s.name, rc.call_id, rc.run_id, rc.timestamp, rc.std_error, rc.exit_code, rc.errors, rc.rtt, rc.log, rc.shortmessages, rc.stat FROM Run_Call rc, RobotRun c, Scenario s WHERE rc.run_id=".$this->run_id." AND rc.test_id=".$this->test_id." AND rc.test_version=".$this->test_version." AND rc.call_id=c.id AND rc.test_id=c.test_id AND rc.test_version=c.test_version AND c.scenario_id=s.id $party ORDER BY c.pos";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle Run_Call: Methode getAll()<br>".mysql_error()."<br>".$statement);
		return $result;
	}
	
	function getOnly($field) {
		global $con;

		$statement = "SELECT $field FROM Run_Call rc WHERE run_id=".$this->run_id." AND test_id=".$this->test_id." AND test_version=".$this->test_version." AND call_id=".$this->call_id."";
		$result = mysql_query ($statement , $con) OR die("Failure: Tabelle Run_Call: Methode getAll()<br>".mysql_error()."<br>".$statement);
		$row=mysql_fetch_array($result,MYSQL_NUM);
		return $row;
	}
}




?>