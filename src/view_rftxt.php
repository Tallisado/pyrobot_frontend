<?
 
// This file is for downloading XML files (scenario files) that are stored in the database.
require_once "db.php";
require_once "dbHelper.php";

$id = $_GET["id"];
$testid = $_GET["testid"];

if($id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->getOnly("name, txt");
	$name = $row->name;
} else if($testid != "") {
	$cObj = new RobotRun($testid);
	$row = $cObj->getOnly("txt");
	$name = "scenario";
}


header('Content-Type: text/plain');
header('Content-Disposition: inline; filename='.$name.'.txt');

echo $row->txt; 
?> 