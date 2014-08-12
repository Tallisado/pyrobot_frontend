<?

// Here the user can create and modify scenarios in the database.
session_start();
require_once "authentication.php";
if(!$admin) { header("location: testsuites.php"); die("Authentication required!"); }
require_once "db.php";
require_once "dbHelper.php";

$filename = "testcode_detail.php";

$error = false;
$errormessage = "";

$action = $_GET["action"];
$id = $_GET["id"];


if($action=="save" || $action=="update") {
	// check if there were automatically added slashes before " and ' where it is relevant, and remove them
	if(get_magic_quotes_gpc()) {
		$name = stripslashes($_POST["name"]);
		$description = stripslashes($_POST["description"]);
		$bind_local = stripslashes($_POST["bind_local"]);
	} else {
		$name = $_POST["name"];
		$description = $_POST["description"];
		$bind_local = $_POST["bind_local"];
	}
	
	$txt_isfile = is_file($_FILES["txt"]["tmp_name"]);

	if($txt_isfile) $txt = file_get_contents($_FILES["txt"]["tmp_name"]);
}

if($action == "save") {

	if($txt_isfile == false) {
		error("Upload of TXT file failed! File not found! Testcode was not stored!");
		$row = new Scenario("", $name, $description, "", $bind_local);
		$action="";
	} else {
		$sObj = new Scenario("", $name, $description, $txt, $bind_local, "f", "t");
		$sObj->insert();
		die("<script language='javascript' type='text/javascript'>location.replace('testcode.php');</script>");
	}
} else if($action == "update") {
	if(($_POST["txtvalue"] != "" && $txt_isfile == false) || ($_POST["csvvalue"] != "" && $csv_isfile == false)) {
		error("Upload of TXT file failed! File not found! Testcode was not stored!");
		$action="mod";
	} else {
		if($txt_isfile == false) $txt = "LEAVE ALONE";
		$csv = "LEAVE ALONE";
		$sObj = new Scenario($id, $name, $description, $txt, $csv, $bind_local, "LEAVE ALONE", "LEAVE ALONE");
		$sObj->update();
		die("<script language='javascript' type='text/javascript'>location.replace('testcode.php');</script>");
	}
} else if($action == "del" && $id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->remove();
	die("<script language='javascript' type='text/javascript'>location.replace('testcode.php');</script>");
}




if($action == "mod" && $id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->get("");
}

function error($msg) {
	global $errormessage;
	global $error;
	$errormessage = $msg;
	$error = true;
}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script language="javascript" type="text/javascript" src="js/helper.js"></script>
<script language="javascript" type="text/javascript">
	function removecsv() {
		var check = window.confirm("Sind sie sicher dass das CSV File entfernt werden soll?");
		if(check) location.href ="<? echo $filename."?action=removecsv&id=".$id; ?>";
	}

	function checkAndSubmit() {
		var f = document.someform;
		
		if(checkText("name", "Name", false, TEXT)
		<? if($action!="mod") { ?>&& checkText("txt", "TXT-file", false, TEXT)<? } ?>
		&& checkText("bind_local", "bind_local", true, IP)) {
			document.getElementById("txtvalue").value = trim(document.getElementById("txt").value);
			f.action = "<? echo $filename; if($action == "mod") echo "?action=update&id=".$id; else echo "?action=save"; ?>";
			f.submit();
		}
	}
</script>
</head>
<body>
<? require_once "navigation/pagehead.php"; ?>
<? if($error) { ?><span class="error"><? echo $errormessage; ?></span><br /><br /><? } ?>
<form enctype="multipart/form-data" action="" method="post" name="someform">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="20" height="10">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="testcode.php" class="breadcumbs">Testcode Overview </a> <strong>&raquo;</strong> <a href="#" class="breadcumbs"><? if($action=="mod") echo "modify testcode (".$row->name.")"; else echo "create new testcode"; ?></a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="testcode_detail.php">&raquo; create new testcode <img src="pix/new.gif" width="14" height="14" border="0" alt="view"></a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><? if($error) { ?><span class="error"><? echo $errormessage; ?></span><br /><br /><? } ?>
<table cellpadding="3" cellspacing="2" border="0" class="formtable">

	<tr style="background-color:#EEEEEE;">
		<td>Name*</td>
		<td><input type="text" name="name" id="name" value="<? if($action=="mod" || $error) echo $row->name; ?>" class="input" style="width:150px;"></td>
		</tr>
	<tr style="background-color:#DDDDDD;">
		<td>Description</td>
		<td><textarea name="description" rows="4" class="input" id="description" style="width:150px;"><? if($action=="mod" || $error) echo $row->description; ?></textarea></td>
		</tr>
	<tr style="background-color:#EEEEEE;">
		<td>TXT-file*</td>
		<td><? if($action=="mod") { ?><a href="view_xml.php?id=<? echo $row->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"> view file</a><br><? } ?>
		  <input type="file" name="txt" id="txt" value="<? if($error) echo $row->xml; ?>" class="input"><input type="hidden" name="txtvalue" id="txtvalue"></td>
		</tr>
	<tr style="background-color:#EEEEEE;">
		<td>bind_local</td>
		<td><input type="text" name="bind_local" id="bind_local" value="<? if($action=="mod" || $error) echo $row->bind_local; ?>" class="input" style="width:150px;"></td>
		</tr>
</table>
      <br>
    <br>
    <a href="javascript: checkAndSubmit();">&raquo;save testcode <img src="pix/save.gif" width="14" height="14" border="0" alt="view"></a>&nbsp;<a href="<? echo $filename."?action=del&id=".$id; ?>">&raquo;remove testcode <img src="pix/del.gif" width="14" height="14" border="0" alt="view"></a></td>
  </tr>
</table>

</form>

</body>
</html>