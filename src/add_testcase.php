<?

session_start();
require_once "authentication.php";
if(!$admin) { header("location: testsuites.php"); die("Authentication required!"); }
require_once "read_config.php";
require_once "db.php";
require_once "dbHelper.php";

$id = $_GET["id"];
$action = $_GET["action"];

$test_id = $_GET["test_id"];
$version = $_GET["version"];
$party = $_GET["party"];


// check if there were automatically added slashes before " and ' where it is relevant, and remove them
if(get_magic_quotes_gpc()) {
	$ip_address = stripslashes($_POST["ip_address"]);
	$extended_parameters = stripslashes($_POST["extended_parameters"]);
} else {
	$ip_address = $_POST["ip_address"];
	$extended_parameters = $_POST["extended_parameters"];
}

$scenario = $_POST["scenario"];
$monitor = $_POST["monitor"] == "t" ? "t" : "f";
$log = $_POST["log"] == "t" ? "t" : "f";

if($action == "save") {

	$sObj = new Scenario($scenario);
	$sRow = $sObj->get("");
	
	$cObj = new RobotRun("", $test_id, $version, $sRow->description, $sRow->def=="t" ? $sRow->name :  addslashes($sRow->txt), $sRow->def, $sRow->bind_local, $ip_address, $monitor, $extended_parameters, $log, $scenario);
	$id = $cObj->insert();
	
	header( 'Location: testsuite_detail.php' ) ;
	die();
} else if($action == "update") {

	if($scenario != "-1") {
		$sObj = new Scenario($scenario);
		$sRow = $sObj->get("");
		$cObj = new RobotRun($id, $test_id, $version,$sRow->description, $sRow->def=="t" ? $sRow->name : addslashes($sRow->txt), $sRow->def, $sRow->bind_local, $ip_address, $monitor, $extended_parameters, $log, $scenario);
		$cObj->update(true);
	} else {
		$cObj = new RobotRun($id, $test_id, $version, "", "", "", "", $ip_address, $monitor, $extended_parameters, $log, $scenario);
		$cObj->update();
	}
	header( 'Location: testsuite_detail.php' ) ;
	die();
}

// load call information
if($id != "" && $action != "new") {
	$cObj = new RobotRun($id);
	$cRow = $cObj->get("");
}

// load test information
$tObj = new Test($test_id);
$tRow = $tObj->get();

// load scenario information
$sObj = new Scenario();
$sRes = $sObj->getAll("id, name","visible='t'");



?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script language="javascript" type="text/javascript" src="js/helper.js"></script>
<script language="javascript" type="text/javascript">
function checkAndSubmit() {
		var f = document.cForm;
		f.action = "<? if($action != "new") echo "?action=update&id=".$id."&test_id=".$test_id."&version=".$version; else echo "?action=save&test_id=".$test_id."&version=".$version; ?>";
		f.submit();
	
}
</script>
</head>
<body>
<? require_once "navigation/pagehead.php"; ?>
<form action="" method="post" name="cForm">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="20" height="10">&nbsp;</td>
    <td>&nbsp;</td>
    <td width="10">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="testsuites.php" class="breadcumbs">Tests overview </a> <strong>&raquo;</strong> <a href="testsuite_detail.php" class="breadcumbs"> modify test (<? echo $tRow->name; ?>) </a><strong>&raquo;</strong> <a href="" class="breadcumbs"><? if($action=="new") echo "Create new test"; else echo "Modify test"; ?> </a></td>
	<td valign="top" class="smalltext">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="javascript: checkAndSubmit()"><br>&raquo;save test <img src="pix/save.gif" width="14" height="14" border="0" alt="view"></a><br></td>
	<td valign="top" class="smalltext">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top"><table cellpadding="3" cellspacing="2" border="0" class="formtable">
	<tr style="background-color:#DDDDDD;">
		<td valign="top">Scenario</td>
		<td valign="top"><? if($action!="new") { if($cRow->def == "f") { ?><a href="view_txt.php?callid=<? echo $cRow->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"> view txt</a>   
		
		<? if($cRow->bind_local != "") { ?><br>bind_local: <? echo $cRow->bind_local; } ?>
		
		<? } ?>
		<br>
		<select name="scenario" class="input" style="width:150px;">
		<? if($action != "new") { ?><option value="-1">-- replace with --</option><? } ?>
		<?
		while($sRow=mysql_fetch_object($sRes)) {
			?><option value="<? echo $sRow->id; ?>"><? echo $sRow->name; ?></option><?
		}
		?>
        </select>		</td>
		<td valign="top" class="smalltext">&nbsp;</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
      <td valign="top">Base URL </td>
	  <td valign="top"><input type="text" name="ip_address" id="ip_address" value="<? if($action != "new") echo $cRow->ip_address; ?>" class="input" style="width:150px;"></td>
	  <td valign="top" class="smalltext">remote host[:port]<br>
	    todo. </td>
	</tr>
	<tr style="background-color:#DDDDDD;">
      <td valign="top">Monitor test </td>
	  <td valign="top"><input type="checkbox" name="monitor" id="monitor" value="t" <? if($action != "new" && $cRow->monitor=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">If this is checked, you recieve realtime feedback during test. </td>
	</tr>
	<tr style="background-color:#EEEEEE;">
      <td valign="top">Log</td>
	  <td valign="top"><input type="checkbox" name="log" id="log" value="t" <? if($action != "new" && $cRow->log=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">Should log information be stored? </td>
	</tr>
	<tr style="background-color:#EEEEEE;">
	  <td valign="top">Extended parameters </td>
	  <td valign="top"><textarea name="extended_parameters" rows="3" class="input" id="extended_parameters" style="width:150px;"><? if($action != "new") echo $cRow->extended_parameters; ?></textarea></td>
	  <td valign="top" class="smalltext">Here you can specify additional commandline parameters. </td>
	</tr>

</table>
      <br>
        <br>
      <a href="javascript: checkAndSubmit()">&raquo;save test <img src="pix/save.gif" width="14" height="14" border="0" alt="view"></a><br>
<br>
<br>
</td>
    <td valign="top">&nbsp;</td>
  </tr>
</table>

</form>

</body>
</html>