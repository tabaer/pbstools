<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
# Copyright 2009 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['node']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['node']) )
  { 
    $title = "Jobs using node ".$_POST['node']." on ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
      {
	$title .= " started on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " started between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " started after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " started before ".$_POST['end_date'];
      }
  }
else
  {
    $title = "Jobs on node";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['node']) )
  {
    $db = db_connect();
    $sql = "SELECT jobid";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='jobid' && $key!='node' && $key!='start_date' && $key!='end_date' )
	  {
	    $sql .= ",".$key;
	  }
      }
    $sql .= " FROM Jobs WHERE hostlist REGEXP '".$_POST['node']."' AND system LIKE '".$_POST['system']."' AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) ORDER BY start_ts;";
#    echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    echo "<TABLE border=\"1\">\n";
    $ncols=1;
    $col[0]="jobid";
    echo "<TR><TH>jobid</TH>";
    foreach ($keys as $key)
      {
	if ( $key!='node' && $key!='start_date' && $key!='end_date' )
	  {
	    echo "<TH>".$key."</TH>";
	    $col[$ncols]=$key;
	    $ncols++;
	  }
      }
    echo "</TR>\n";
    
    while ($result->fetchInto($row))
      {
	echo "<TR>";
	$rkeys=array_keys($row);
	foreach ($rkeys as $key)
	  {
	    $data[$key]=array_shift($row);
	    if ( $col[$key]=="submit_ts" || $col[$key]=="start_ts" || $col[$key]=="end_ts")
	      {
		echo "<TD><PRE>".date("Y-m-d H:i:s",$data[$key])."</PRE></TD>\n";
	      }
	    else if ($col[$key] == "jobid")
	      {
		$jobid_nodot = ereg_replace('\..*', '', $data[$key]);
		echo "<TD><PRE><A HREF=\"",
		  "jobinfo.php?jobid=$jobid_nodot",
		  "&system=$_POST[system]&all=1\"\>",
		  htmlspecialchars($jobid_nodot), "</A></PRE></TD>";
	      }
	    else
	      {
		echo "<TD><PRE>".htmlspecialchars($data[$key])."</PRE></TD>";
	      }
	  }
	echo "</TR>\n";
      }
    echo "</TABLE>\n";
  
    db_disconnect($db);
    bookmarkable_url();
  }
else
  {
    begin_form("jobs-by-node.php");

    text_field("Node RegExp","node",16);
    system_chooser();
    date_fields();

    $props=array("username","groupname","account","jobname","nproc","mppe","mppssp",
		 "nodes","queue","submit_ts","start_ts","end_ts","cput_req",
		 "cput","walltime_req","walltime","mem_req","mem_kb",
		 "vmem_req","vmem_kb","software","hostlist","exit_status","script");
    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>
