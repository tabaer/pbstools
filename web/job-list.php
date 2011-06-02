<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
# Copyright 2009, 2011 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['system']) )
  {
    $title = "Jobs on ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$title .= " ending on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " ending between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " ending after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " submitted before ".$_POST['end_date'];
      }
  }
else
  {
    $title = "Job list";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db =db_connect();
    $sql = "SELECT system, jobid, username, account, jobname, nproc, nodes, mem_req, mem_kb, submit_ts, start_ts, end_ts, walltime_req, walltime, ".cpuhours($db,$_POST['system'])." AS cpuhours, queue, IF(script IS NULL,'interactive','batch') AS type, software FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ( ".dateselect("end",$_POST['start_date'],$_POST['end_date'])." ) ORDER BY start_ts;";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    echo "<TABLE border=\"1\">\n";
    $col[0]="jobid";
    $col[1]="username";
    $ncols=0;
    $columns = array("system", "jobid", "username", "account", "jobname", "nproc", "nodes", "mem_req", "mem_used", "submit_time", "start_time", "end_time", "walltime_req", "walltime", "cpuhours", "queue", "type", "software");
    echo "<TR>";
    foreach ($columns as $column)
      {
	echo "<TH align=\"center\">".$column."</TH>";
	$col[$ncols]=$column;
	$ncols++;
      }
    echo "</TR>\n";
    
    while ($result->fetchInto($row))
      {
	$rkeys=array_keys($row);
	
	$nproc=0;
	$nnodes=0;
	$system="";
	echo "<TR>";
	foreach ($rkeys as $key)
	  {
	    $data[$key]=array_shift($row);
	    if ( $col[$key]=="submit_time" || $col[$key]=="start_time" || $col[$key]=="end_time")
	      {
		echo "<TD><PRE>".date("Y-m-d H:i:s",$data[$key])."</PRE></TD>";
	      }
	    else if ( $col[$key]=="mem_used" )
	      {
		echo "<TD><PRE>".htmlspecialchars(round($data[$key]/1024))."MB</PRE></TD>";
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
    begin_form("job-list.php");

    system_chooser();
    date_fields();

    end_form();
  }

page_footer();
?>
