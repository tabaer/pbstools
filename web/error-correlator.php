<?php
# Copyright 2006, 2016 Ohio Supercomputer Center
# Copyright 2008, 2009, 2010, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'page-layout.php';
require_once 'dbutils.php';
require_once 'site-specific.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['jobid']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['error_time']) )
  { 
    $title = "Job info for errors on ".$_POST['system'];
    if ( isset($_POST['node_regex']) && $_POST['node_regex']!='' )
      {
        $title = $title." nodes ".$_POST['node_regex'];
      }
    if ( isset($_POST['program']) && $_POST['program']!='' )
      {
        $title = $title." running ".$_POST['program'];
      }
    $title = $title." at ".$_POST['error_time'];
  } 
 else
  {
    $title="Job info";
  }
page_header($title);

$props=array("username","groupname","account","jobname","nproc","mppe","mppssp",
	     "nodes","feature","gattr","gres","queue","qos",
	     "submit_ts","eligible_ts","start_ts","end_ts",
	     "cput_req","cput_req_sec","cput","cput_sec",
	     "walltime_req","walltime_req_sec","walltime","walltime_sec",
	     "mem_req","mem_kb","vmem_req","vmem_kb",
	     "energy","software","submithost","hostlist",
	     "exit_status","script","sw_app");

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
    $sql = "SELECT system, jobid";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='system' && $key!='jobid' && $key!='error_time' && $key!='program' && $key!='node_regex' && $key!='limit' ) { $sql = $sql.",".$key; }
      }
    $sql = $sql." FROM Jobs WHERE ( ".sysselect($_POST['system'])." ) AND ( start_ts <= UNIX_TIMESTAMP('".$_POST['error_time']."') AND end_ts >= UNIX_TIMESTAMP('".$_POST['error_time']."') )";
    if ( isset($_POST['program']) && $_POST['program']!='' )
      {
        $sql = $sql." AND ( script LIKE '%".$_POST['program']."%' )";
      }
    if ( isset($_POST['node_regex']) && $_POST['node_regex']!='' )
      {
        $sql = $sql." AND ( hostlist REGEXP '".$_POST['node_regex']."' )";
      }
    $sql = $sql." ORDER BY end_ts-UNIX_TIMESTAMP('".$_POST['error_time']."')";
    if ( isset($_POST['limit']) && $_POST['limit']!='' )
      {
        $sql = $sql." LIMIT ".$_POST['limit'].";";
      }
    #echo "<PRE>".$sql,"</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    while ($result->fetchInto($row))
      {
	echo "<TABLE border=\"1\">\n";
	foreach ($keys as $key)
	  {
	    if ( isset($_POST[$key]) && $key!='error_time' && $key!='program' && $key!='node_regex' && $key!='limit' )
	      {
		$data[$key]=array_shift($row);
		echo "<TR><TD width=\"10%\"><PRE>".$key."</PRE></TD><TD width=\"90%\"><PRE>";
		if ( $key=="submit_ts" ||  $key=="eligible_ts" || $key=="start_ts" || $key=="end_ts" )
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
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("error-correlator.php");

    system_chooser();
    text_field("Error date/time (YYYY-MM-DD HH:MM:SS)","error_time",20);
    text_field("(OPTIONAL) Executable","program",20);
    text_field("(OPTIONAL) Node regex","node_regex",20);
    text_field("(OPTIONAL) Max results","limit",4);
    hidden_field("jobid","unknown");

    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>
