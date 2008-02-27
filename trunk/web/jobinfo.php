<?php
# Copyright 2006 Ohio Supercomputer Center
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

if ( isset($_POST['jobid']) )
  { 
    $title="Job info for ".$_POST['system']." jobid ".$_POST['jobid'];
  } 
 else
  {
    $title="Job info";
  }
page_header($title);

$props=array("username","groupname","jobname","nproc","mppe","mppssp",
	     "nodes","queue","submit_ts","start_ts","end_ts","cput_req",
	     "cput","walltime_req","walltime","mem_req","mem_kb",
	     "vmem_req","vmem_kb","hostlist","exit_status","script");

// special key "all=1" turns on all the $props.
if (!empty($_POST['all'])) {
    unset($_POST['all']);
    foreach ($props as $key)
	$_POST[$key] = 1;
}

$keys = array_keys($_POST);
if ( isset($_POST['jobid']) )
  {
    $db = db_connect();
    $sql = "SELECT jobid";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='jobid' ) { $sql = $sql.",".$key; }
      }
    $sql = $sql." FROM Jobs WHERE jobid LIKE '".$_POST['jobid'].".%' AND system LIKE '".$_POST['system']."';";
    $result = db_query($db,$sql);
    while ($result->fetchInto($row))
      {
	echo "<TABLE border=1 width=\"100%\">\n";
	foreach ($keys as $key)
	  {
	    if ( isset($_POST[$key]) )
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
  }
else
  {
    begin_form("jobinfo.php");

    text_field("Job id","jobid",8);
    system_chooser();

    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>
