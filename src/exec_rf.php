<?

// This file is called from run_progress.php via ajax.
// The basic function of this file is to build up the commandline, create the xml-scenario file
// and the csv-injection file in the system's temporary folder, execute the call and return its process id (pid).
// Further the names of the log files are created, the port for remote controlling sipp is determined, and then
// these informations are stored in a session object for later processing.
// expects (GET-variables): call_id, party (a or b), run_id, test_id, test_version
// returns (output to browser): eitehr call_id;process_id;party, or call_id;exit_code;party if an error occurs

	require_once "Call_Data.php";
	session_start();

	require_once "read_config.php";
	require_once "db.php";
	require_once "dbHelper.php";
	require_once "sys_get_temp_dir.php";


	$call_id = $_GET["id"];
	$test_id = $_GET["test_id"];
	$test_version = $_GET["test_version"];
	$party = $_GET["party"];
	$run_id = $_GET["run_id"];
	
	
	header("text/xml");
	
	$cObj = new RobotRun($call_id);
	$cRow = $cObj->get();
	
	// store actiual timestamp in database
	$rcObj = new Run_Call($run_id, $test_id, $test_version, $call_id);
	$rcObj->setTime();
	
	// sould information be logged in files and monitored on screen?
	//$log = $cRow->log == "t";
	//$monitor = $cRow->monitor == "t";
	
	// does this call uses a default scenario or a specific xml scenario?
	//$default = $cRow->def == "t";
	
	/* bild up options for sipp call. options are assembled as follows:
	 * first the extended_parameters defined in the call,
	 * then the regular parameters defined in the call,
	 * followed by the avp parameters defined in the config file,
	 * then all parameters that are needed to log data (-trace_screen...),
	 * and finally the extended_parameters again to ensure that they overwrite ambiguous parameters from the config file.
	*/
	//$logParameters = $log ? " -trace_err -trace_stat -trace_rtt -trace_logs" : "";
	
	//$logParameters .= $monitor ? " -trace_screen" : "";
	//$options = $cObj->getOptions($cRow, getConfigParameters().$logParameters);
	
	// look for sipp executeable in config file
	//$sipp_path = $executables[$cRow->executable];
	$pybot_path = '/usr/local/bin/pybot'
	
	// create a folder for this session in the systems temp directory
	$working_dir = get_working_dir($test_id, $test_version, $run_id);
	if(!file_exists($working_dir)) mkdir($working_dir, 0777);

	//store xml data in temporary files
	$temporary_txt_file = tempnam($working_dir, "txt");
	$handle = fopen($temporary_txt_file, "w");
	fwrite($handle, $cRow->txt);
	fclose($handle);

	
	// build up the command line
	$command = "DISPLAY=:80 ".$pybot_path." ".$temporary_txt_file;
	
	// define an exitcode file
	$exit_code_file = $temporary_txt_file."_".$call_id."_exitcode.log";
	
	$std_error_file = $temporary_txt_file."_".$call_id."_std_error.log";

	$pid = execute_background($command, $exit_code_file, $std_error_file, "/dev/null");

	if($pid!="" && is_numeric($pid)) {
		
		// generate log filenames
		$error_file = $temporary_txt_file."_".$pid."_errors.log";
		$rtt_file = $temporary_txt_file."_".$pid."_rtt.csv";
		$logs_file = $temporary_txt_file."_".$pid."_logs.log";
		$shortmessages_file = $temporary_txt_file."_".$pid."_shortmessages.log";
		$stat_file = $temporary_txt_file."_".$pid."_.csv";
		$screen_file = $temporary_txt_file."_".$pid."_screen.log";
		$messages_file = $temporary_txt_file."_".$pid."_messages.log";
		$output_file = $temporary_txt_file."_".$pid."_output.log";
		$report_file = $temporary_txt_file."_".$pid."_report.log";
		$std_out_file = $temporary_txt_file."_".$pid."_stdout.log";
		// store call specific data in session object
		$_SESSION["s_call_".$pid] =  new Call_Data($pid, $call_id, $test_id, $test_version, $run_id, $log, $monitor, $temporary_txt_file, $std_error_file, $exit_code_file, $logs_file, $output_file, $report_file, $std_out_file);	
		echo "$call_id;$party;$pid";
	} else {
		$cdObj = new Call_Data("", $call_id, $test_id, $test_version, $run_id, $log, $monitor, $temporary_txt_file, $std_error_file, $exit_code_file);
		$exit_code = $cdObj->getExitCode();
		$cdObj->updateDatabase();
		if($std_error_file != "" && file_exists($std_error_file)) $std_error = file_get_contents($std_error_file);
	else $std_error = "";
		$cdObj->cleanUp();
		echo "$call_id;$party;exit=$exit_code&std_error&".htmlentities($std_error);
	}
	
	function execute_background($command, $exit_code_file, $error_file, $output_file) {
		// execute as background process, store exit code in a file and prompt pid of command (parent pid of sipp)
		$ppid = shell_exec("export TERM=vt100; (nohup $command ; echo $? > $exit_code_file) 2> $error_file > $output_file & echo $!");

		$ppid = substr($ppid, 0, -1);
		// because $ppid is the parent pid of the sipp call, determine the pid of childprocess
		$pid = shell_exec("ps -o pid,ppid -e | grep \"^[[:space:]]*[0-9]\+[[:space:]]\+".$ppid."\" | awk '{print $1}'");
		$pid = substr($pid, 0, -1);
		return $pid;
	}
	
?>