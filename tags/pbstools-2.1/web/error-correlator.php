<?php
# Copyright 2006 Ohio Supercomputer Center
# Copyright 2008, 2009 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'page-layout.php';
require_once 'dbutils.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['jobid']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['error_time']) )
  { 
    $title="Job info for errors on ".$_POST['system']." running '".$_POST['program']."' at ".$_POST['error_time'];
  } 
 else
  {
    $title="Job info";
  }
page_header($title);

$props=array("username","groupname","account","jobname","nproc","mppe","mppssp",
	     "nodes","feature","queue","qos","submit_ts","start_ts","end_ts","cput_req",
	     "cput","walltime_req","walltime","mem_req","mem_kb",
	     "vmem_req","vmem_kb","software","hostlist","exit_status","script");

// special key "all=1" turns on all the $props.
if (!empty($_POST['all'])) {
    unset($_POST['all']);
    foreach ($props as $key)
	$_POST[$key] = 1;
}

$keys = array_keys($_POST);
if ( isset($_POST['error_time']) )
  {
    $db = db_connect();
    $sql = "SELECT jobid";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='jobid' && $key!='error_time' && $key!='program' ) { $sql = $sql.",".$key; }
      }
    $sql = $sql." FROM Jobs WHERE script LIKE '%".$_POST['program']."%' AND system LIKE '".$_POST['system']."' AND FROM_UNIXTIME(start_ts) <= '".$_POST['error_time']."' AND FROM_UNIXTIME(end_ts) >= '".$_POST['error_time']."' ORDER BY end_ts-UNIX_TIMESTAMP('".$_POST['error_time']."') LIMIT 1;";
    #echo "<PRE>".$sql,"</PRE>\n";
    $result = db_query($db,$sql);
    while ($result->fetchInto($row))
      {
	echo "<TABLE border=\"1\">\n";
	foreach ($keys as $key)
	  {
	    if ( isset($_POST[$key]) && $key!='error_time' && $key!='program' )
	      {
		$data[$key]=array_shift($row);
		echo "<TR><TD width=\"10%\"><PRE>".$key."</PRE></TD><TD width=\"90%\"><PRE>";
		if ( $key=="submit_ts" || $key=="start_ts" || $key=="end_ts" )
		  {
		    echo date("Y-m-d H:i:s",$data[$key]);
		  }
		else
		  {
		    echo htmlspecialchars($data[$key]);
		  }
		echo "</PRE></TD></TR>\n";
	      }
	  }
	echo "</TABLE>\n";
      }
    db_disconnect($db);
    bookmarkable_url();
  }
else
  {
    begin_form("error-correlator.php");

    text_field("Error date/time (YYYY-MM-DD HH:MM:SS)","error_time",20);
    text_field("Executable","program",8);
    hidden_field("jobid","unknown");
    system_chooser();

    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>
