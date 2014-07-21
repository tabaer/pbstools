<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
# Copyright 2009, 2010, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['username']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['username']) )
  {
    $title = "Jobs owned by user ".$_POST['username']." on ".$_POST['system'];
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
    $title = "Jobs by user";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['username']) )
  {
    $db =db_connect();
    $sql = "SELECT jobid,username";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='jobid' && $key!='username' && $key!='start_date' && $key!='end_date' )
	  {
	    $sql .= ",".$key;
	  }
      }
    $sql .= " FROM Jobs WHERE username = '".$_POST['username']."' AND system LIKE '".$_POST['system']."' AND ( ".dateselect("submit",$_POST['start_date'],$_POST['end_date'])." ) ORDER BY submit_ts;";
#	echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    echo "<TABLE border=\"1\">\n";
    $col[0]="jobid";
    $col[1]="username";
    $ncols=2;
    echo "<TR><TH>jobid</TH><TH>username</TH>";
    foreach ($keys as $key)
      {
	if ( $key!='jobid' && $key!='username' && $key!='start_date' && $key!='end_date' )
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
		echo "<TD><PRE>".date("Y-m-d H:i:s",$data[$key])."</PRE></TD>\n";
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
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("jobs-by-user.php");

    text_field("User","username",16);
    system_chooser();
    date_fields();

    $props=array("groupname","account","jobname","nproc","mppe","mppssp",
		 "nodes","feature","gres","queue","qos","submit_ts","start_ts","end_ts","cput_req",
		 "cput","walltime_req","walltime","mem_req","mem_kb",
		 "vmem_req","vmem_kb","software","submithost","hostlist","exit_status","script");
    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>
