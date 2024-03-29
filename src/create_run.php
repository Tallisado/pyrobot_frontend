<?


// This file is called from run_progress.php via ajax. It creates a run in the database with the current timestamp.
// For each Call of the specified test, a Run_Call entry is created.
// expects (GET-variables): test_id and test_version as GET-variables
// returns (output to browser): run_id or errormessage

require_once "db.php";
require_once "dbHelper.php";
	
$test_id = $_GET["test_id"];
$test_version = $_GET["test_version"];
	
	
mysql_query("START TRANSACTION", $con);
	
// create run
$rObj = new Run("", $test_id, $test_version, "NULL", "error");
$runid = $rObj->insert(false);
	
//load calls
$cObj = new RobotRun("", $test_id, $test_version);
$cRes = $cObj->getAll("c.id");
	
// for each call create a Run_Call
while($cRow = mysql_fetch_object($cRes)) {
	$testid = $cRow->id;
	$rcObj = new Run_Call($runid, $test_id, $test_version, $testid);
	$rcObj->insert();
}
	
mysql_query("COMMIT", $con);
	
echo $runid;
	
?>