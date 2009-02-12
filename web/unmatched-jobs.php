<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
# Copyright 2009 University of Tennessee
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

# list of software packages
$packages=software_list();

# regular expressions for different software packages
$pkgmatch=software_match_list();

if ( isset($_POST['system']) )
  { 
    $title = "Unclassified jobs on ".$_POST['system'];
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
    $title = "Unclassified jobs";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db = db_connect();

    # system summary table
    echo "<H3>System Summary</H3>\n";
    $sql = "SELECT system, COUNT(jobid) AS jobs, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE script IS NOT NULL";
    foreach ( $packages as $pkg )
      {
	$sql .= " AND ";
	if ( isset($pkgmatch[$pkg]) )
	  {
	    $sql .= "(NOT (".$pkgmatch[$pkg]."))";
	  }
	else
	  {
	    $sql .= "( script NOT LIKE '%".$pkg."%' AND software NOT LIKE '%".$pkg."%' )";
	  }
      }
    $sql .= " AND system LIKE '".$_POST['system']."' AND ( ".dateselect("submit",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY system ORDER BY jobs DESC";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
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

    # user summary table
    echo "<H3>User Summary</H3>\n";
    $sql = "SELECT DISTINCT(username) AS username, groupname, account, system, COUNT(jobid) AS jobs FROM Jobs WHERE ( ";
    $isfirst = 1;
    foreach ( $packages as $pkg )
      {
	if ( $isfirst==1 )
	  {
	    $isfirst = 0;
	  }
	else
	  {
	    $sql .= " AND ";
	  }
	if ( isset($pkgmatch[$pkg]) )
	  {
	    $sql .= "NOT (".$pkgmatch[$pkg].")";
	  }
	else
	  {
	    $sql .= "( script NOT LIKE '%".$pkg."%' AND software NOT LIKE '%".$pkg."%' )";
	  }
      }
    $sql .= " ) AND system LIKE '".$_POST['system']."' AND ( ".dateselect("submit",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY username, account, system ORDER BY jobs DESC";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
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
	if ( isset($_POST[$key]) && $key!='jobid' && $key!='start_date' && $key!='end_date' )
	  {
	    $sql .= ",".$key;
	  }
      }
    $sql .= " FROM Jobs WHERE ( ";
    $isfirst = 1;
    foreach ( $packages as $pkg )
      {
	if ( $isfirst==1 )
	  {
	    $isfirst = 0;
	  }
	else
	  {
	    $sql .= " AND ";
	  }
	if ( isset($pkgmatch[$pkg]) )
	  {
	    $sql .= "NOT (".$pkgmatch[$pkg].")";
	  }
	else
	  {
	    $sql .= "( script NOT LIKE '%".$pkg."%' AND software NOT LIKE '%".$pkg."%' )";
	  }
      }
    $sql .= " ) AND system LIKE '".$_POST['system']."' AND ( ".dateselect("submit",$_POST['start_date'],$_POST['end_date'])." ) ORDER BY start_ts;";
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    echo "<TABLE border=\"1\">\n";
    $ncols=1;
    $col[0]="jobid";
    echo "<TR><TH>jobid</TH>";
    foreach ($keys as $key)
      {
	if ( $key!='start_date' && $key!='end_date' )
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
    begin_form("unknown-jobs.php");

    system_chooser();
    date_fields();

    $props=array("username","groupname","account","jobname","nproc","mppe","mppssp",
		 "nodes","queue","submit_ts","start_ts","end_ts","cput_req",
		 "cput","walltime_req","walltime","mem_req","mem_kb",
		 "vmem_req","vmem_kb","hostlist","exit_status","script");
    checkboxes_from_array("Properties",$props);

    end_form();
  }

page_footer();
?>
