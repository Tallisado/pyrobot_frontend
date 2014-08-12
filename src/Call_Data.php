<?
 
// Class Call_Data contains call specific data (process-id, logfilenames...) of a currently running call. 
// It has methods to store logfiles in the database, delete the logfiles and determine the exitcode after execution.
// When a call is about to be executed, a object of this class is generated and stored in a session variable.
class Call_Data {
	var $pid;
	var $call_id;
	var $test_id;
	var $test_version;
	var $run_id;
	var $monitor;
	var $txt_file;
	var $std_error_file;
	var $std_out_file;
	var $exit_code_file;
	var $logs_file;
	var $output_file;
	var $report_file;
	
	var $exit_code;
	
	var $stdout_line_count;
	var $std_out_file_tail;
	
	function Call_Data($pid, $call_id, $test_id, $test_version, $run_id, $log, $monitor, $txt_file, $std_error_file, $exit_code_file, $logs_file="", $output_file="", $report_file="", $std_out_file="") {
		$this->pid = $pid;
		$this->call_id = $call_id;
		$this->test_id = $test_id;
		$this->test_version = $test_version;
		$this->run_id = $run_id;		
		$this->log = $log;
		$this->monitor = $monitor;
		$this->txt_file = $txt_file;
		$this->std_error_file = $std_error_file;
		$this->std_out_file = $std_out_file;		
		$this->exit_code_file = $exit_code_file;
		$this->logs_file = $logs_file;
		$this->output_file = $output_file;
		$this->report_file = $report_file;

		$this->std_out_file_tail = $std_out_file.".tail";
		$this->exit_code = "-1";
	}
	
	// updateDatabase is called after the execution of a call has finished. here the logfiles are stored in the datbase.
	function updateDatabase() {
		global $con;
		
		// maybe files are quite big, so increase memory limit
		ini_set('memory_limit', '50M');
		
		// load content of std_error file
		if($this->std_error_file != "" && file_exists($this->std_error_file)) {
			$std_error = addslashes(file_get_contents($this->std_error_file));
		} else $std_error = "";
		
		// load content of std_out file
		if($this->std_out_file != "" && file_exists($this->std_out_file)) {
			$std_out = addslashes(file_get_contents($this->std_out_file));
		} else $std_out = "";		
		
		// load content of logs file
		if($this->logs_file != "" && file_exists($this->logs_file)) {
			$logs = addslashes(file_get_contents($this->logs_file));
		} else $logs = "";
	
		if($this->output_file != "" && file_exists($this->output_file)) {
			$output = addslashes(file_get_contents($this->output_file));
		} else $output = "";

		if($this->report_file != "" && file_exists($this->report_file)) {
			$report = addslashes(file_get_contents($this->report_file));
		} else $report = "";
				
		$rcObj = new Run_Call($this->run_id, $this->test_id, $this->test_version, $this->call_id, $this->exit_code, $std_error, $std_out, $logs, $output, $report);
		$rcObj->update();
	}
	
	// extract and return exit code from exit code file
	function getExitCode() {
		if($this->exit_code_file != "" && file_exists($this->exit_code_file)) {
			$this->exit_code =  preg_replace("(\r\n|\n|\r)", "", file_get_contents($this->exit_code_file));
		}
		return $this->exit_code;
	}
	

	// deletes all log files and the call specific session object, that were created earlier
	function cleanUp() {
		if($this->txt_file != "" && file_exists($this->txt_file)) unlink($this->txt_file);
		if($this->std_error_file != "" && file_exists($this->std_error_file)) unlink($this->std_error_file);
		if($this->exit_code_file != "" && file_exists($this->exit_code_file)) unlink($this->exit_code_file);
		if($this->logs_file != "" && file_exists($this->logs_file)) unlink($this->logs_file);
		if($this->output_file != "" && file_exists($this->output_file)) unlink($this->output_file);
		if($this->report_file != "" && file_exists($this->report_file)) unlink($this->report_file);
		if($this->std_out_file != "" && file_exists($this->std_out_file)) unlink($this->std_out_file);
		
		// Because the messages file normally is very big, it isn't stored in the database. instead it stays in the working
		// directory until the garbage collector deletes it, so that the user can view it after test execution. Yet later
		// the Call_Data object is destroyed, and the filename of the messages file is lost, hence it gets renamed to "messages_<testid>".
		// if(file_exists($this->messages_file)) rename($this->messages_file, dirname($this->messages_file)."/messages_".$this->call_id);

		// delete session object
		unset($_SESSION["s_call_".$this->pid]);
	}

}
?>