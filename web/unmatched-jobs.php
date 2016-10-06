<?php
# Copyright 2006, 2007, 2008, 2016 Ohio Supercomputer Center
# Copyright 2009, 2010, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'page-layout.php';
require_once 'dbutils.php';
require_once 'metrics.php';
require_once 'site-specific.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

# connect to DB
$db = db_connect();

# list of software packages
#$packages=software_list($db);

# regular expressions for different software packages
#$pkgmatch=software_match_list($db);

if ( isset($_POST['system']) )
  { 
    $title = "Unclassified jobs on ".$_POST['system'];
    $verb = title_verb($_POST['datelogic']);
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && 
	 $_POST['start_date']==$_POST['end_date'] && $_POST['start_date']!="" )
      {
	$title .= " ".$verb." on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && 
	      $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " ".$verb." between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " ".$verb." after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " ".$verb." before ".$_POST['end_date'];
      }
  }
else
  {
    $title = "Unclassified jobs";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    # system summary table
    echo "<H3>System Summary</H3>\n";
    $sql = "SELECT system, COUNT(jobid) AS jobs, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE script IS NOT NULL AND sw_app is NULL AND ( ".sysselect($_POST['system'])." ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) GROUP BY system ORDER BY jobs DESC";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    echo "<TABLE border=1>\n";
    echo "<TR><TH>system</TH><TH>jobs</TH><TH>users</TH><TH>groups</TH><TH>accounts</TH></TR>\n";
    while ($result->fetchInto($row))
      {
	echo "<TR>";
	$rkeys=array_keys($row);
	foreach ($rkeys as $key)
	  {
	    $data=array();
	    $data[$key] = $row[$key];
	    echo "<TD align=\"right\"><PRE>".$data[$key]."</PRE></TD>";
	  }
	echo "</TR>\n";
      }
    echo "</TABLE>\n";

    ob_flush();
    flush();

    # account summary table
    echo "<H3>Account Summary</H3>\n";
    $sql = "SELECT account, system, COUNT(DISTINCT(username)) AS users, COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS cpuhours, SUM(".charges($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS charges FROM Jobs WHERE script IS NOT NULL AND sw_app IS NULL AND ( ".sysselect($_POST['system'])." ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) GROUP BY account, system ORDER BY cpuhours DESC";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    echo "<TABLE border=1>\n";
    echo "<TR><TH>account</TH><TH>system</TH><TH>users</TH><TH>jobs</TH><TH>cpuhours</TH><TH>charges</TH></TR>\n";
    while ($result->fetchInto($row))
      {
	echo "<TR>";
	$rkeys=array_keys($row);
	foreach ($rkeys as $key)
	  {
	    $data=array();
	    $data[$key] = $row[$key];
	    echo "<TD align=\"right\"><PRE>".$data[$key]."</PRE></TD>";
	  }
	echo "</TR>\n";
      }
    echo "</TABLE>\n";

    ob_flush();
    flush();    

    # user summary table
    echo "<H3>User Summary</H3>\n";
    $sql = "SELECT DISTINCT(username) AS username, groupname, account, system, COUNT(jobid) AS jobs FROM Jobs WHERE script IS NOT NULL AND sw_app IS NULL AND ( ".sysselect($_POST['system'])." ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) GROUP BY username, account, system ORDER BY jobs DESC";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    echo "<TABLE border=1>\n";
    echo "<TR><TH>user</TH><TH>group</TH><TH>account</TH><TH>system</TH><TH>jobs</TH></TR>\n";
    while ($result->fetchInto($row))
      {
	echo "<TR>";
	$rkeys=array_keys($row);
	foreach ($rkeys as $key)
	  {
	    $data=array();
	    $data[$key] = $row[$key];
	    echo "<TD align=\"right\"><PRE>".$data[$key]."</PRE></TD>";
	  }
	echo "</TR>\n";
      }
    echo "</TABLE>\n";

    ob_flush();
    flush();
    
    # job info
    echo "<H3>Jobs</H3>\n";
    $sql = "SELECT jobid";
    foreach ($keys as $key)
      {
	if ( isset($_POST[$key]) && $key!='jobid' && $key!='start_date' && $key!='end_date' && $key!='datelogic' )
	  {
	    $sql .= ",".$key;
	  }
      }
    $sql .= " FROM Jobs WHERE ( ";
    $sql .= "script IS NOT NULL AND sw_app IS NULL";
    $sql .= " ) AND ( ".sysselect($_POST['system'])." ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) ORDER BY start_ts;";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    echo "<TABLE border=\"1\">\n";
    $ncols=1;
    $col[0]="jobid";
    echo "<TR><TH>jobid</TH>";
    foreach ($keys as $key)
      {
	if ( $key!='start_date' && $key!='end_date' && $key!='datelogic' )
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
  
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("unmatched-jobs.php");

    system_chooser();
    date_fields();

    $props=array("username","groupname","account","jobname","nproc","mppe","mppssp",
		 "nodes","feature","gres","queue","qos","submit_ts","start_ts","end_ts","cput_req",
		 "cput","walltime_req","walltime","mem_req","mem_kb",
		 "vmem_req","vmem_kb","energy","software","submithost","hostlist",
		 "exit_status","script");
    checkboxes_from_array("Properties",$props);

    end_form();
  }

db_disconnect($db);
page_footer();
?>
