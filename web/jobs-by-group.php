<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'page-layout.php';
require_once 'dbutils.php';

if ( isset($_POST['groupname']) )
  { 
    $title = "Jobs owned by group ".$_POST['groupname']." on ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
      {
	$title .= " submitted on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " submitted between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " submitted after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " submitted before ".$_POST['end_date'];
      }
  }
else
  {
    $title = "Jobs by group";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['groupname']) )
  {
    $db = db_connect();
    $sql = "SELECT jobid,groupname,username";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='jobid' && $key!='groupname' && $key!='username' &&
	     $key!='start_date' && $key!='end_date' )
	  {
	    $sql .= ",".$key;
	  }
      }
    $sql = $sql." FROM Jobs WHERE groupname = '".$_POST['groupname']."' AND system LIKE '".$_POST['system']."'";
    if ( isset($_POST['start_date']) &&   isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$sql .= " AND FROM_UNIXTIME(submit_ts) >= '".$_POST['start_date']." 00:00:00'";
	$sql .= " AND FROM_UNIXTIME(submit_ts) <= '".$_POST['start_date']." 23:59:59'";
      }
    else
      {
	if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
	  {
	    $sql .= " AND FROM_UNIXTIME(submit_ts) >= '".$_POST['start_date']." 00:00:00'";
	      }
	if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
	  {
	    $sql .= " AND FROM_UNIXTIME(submit_ts) <= '".$_POST['end_date']." 23:59:59'";
	  }
      }
    $sql .= " ORDER BY submit_ts;";
#    echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    echo "<TABLE border=1 width=\"100%\">\n";
    $col[0]="jobid";
    $col[1]="username";
    $col[2]="groupname";
    $ncols=3;
    echo "<TR><TH>jobid</TH><TH>username</TH><TH>groupname</TH>";
    foreach ($keys as $key)
      {
	if ( $key!='jobid' && $key!='username' && $key!='groupname' && $key!='start_date' && $key!='end_date' )
	  {
	    echo "<TH>".$key."</TH>";
	    $col[$ncols]=$key;
	    $ncols++;
	  }
      }
    echo "</TR>\n";
    
    while ($result->fetchInto($row))
      {
	$rkeys=array_keys($row);
	echo "<TR>";
	foreach ($rkeys as $key)
	  {
	    $data[$key]=array_shift($row);
	    if ( $col[$key]=="submit_ts" || $col[$key]=="start_ts" || $col[$key]=="end_ts")
	      {
		echo "<TD><PRE>".date("Y-m-d H:i:s",$data[$key])."</PVRE></TD>\n";
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
  }
else
  {
    begin_form("jobs-by-group.php");

    text_field("Group","groupname",16);
    system_chooser();
    date_fields();

    $props=array("jobname","nproc","mppe","mppssp",
		 "nodes","queue","submit_ts","start_ts","end_ts","cput_req",
		 "cput","walltime_req","walltime","mem_req","mem_kb",
		 "vmem_req","vmem_kb","hostlist","exit_status","script");
    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>