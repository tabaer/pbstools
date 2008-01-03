<?php
# Copyright 2007 Ohio Supercomputer Center
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/usage-summary.php $
# $Revision: 168 $
# $Date: 2007-06-26 16:53:55 -0400 (Tue, 26 Jun 2007) $
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';
require_once 'site-specific.php';

# list of software packages
$packages=software_list();

# regular expressions for different software packages
$pkgmatch=software_match_list();

$title = "Usage summary";
if ( isset($_POST['system']) )
  {
    $title .= " for ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) &&
	 $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$title .= " on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " from ".$_POST['start_date']." to ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " before ".$_POST['end_date'];
      }
  }
page_header($title);


if ( isset($_POST['system']) )
  {
    $db = db_connect();

    echo "<H3>Overview</H3>\n";

    $sql = "SELECT system, COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, NULL AS pct_util, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."'";
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
	$sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
      }
    else
      {
	if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
	  {
	    $sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
	  }
	if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
	  {
	    $sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
	  }
      }
    $sql .= " GROUP BY system ORDER BY ".$_POST['order']." DESC";
    #echo "<PRE>\n".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    echo "<TABLE border=1>\n";
    echo "<TR><TH>system</TH><TH>jobcount</TH><TH>cpuhours</TH><TH>%util</TH><TH>users</TH><TH>groups</TH></TR>\n";
    while ($result->fetchInto($row))
      {
	$data=array();
	$rkeys=array_keys($row);
	echo "<TR>";
	foreach ($rkeys as $rkey)
	  {
	    if ( $row[$rkey]==NULL )
	      {
		$ndays=ndays($db,$row[0],$_POST['start_date'],$_POST['end_date']);
		if ( $ndays[1]>0 )
		  {
		    $data[$rkey]=sprintf("%6.2f",100.0*$row[2]/$ndays[1]);
		  }
		else
		  {
		    $data[$rkey]="system=".$row[0].",startdate=".$_POST['start_date'].",enddate=".$_POST['end_date'];
		  }
	      }
	    else
	      {
		$data[$rkey]=$row[$rkey];
	      }
	    echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
	  }
	echo "</TR>\n";
      }
    if ( $_POST['system']=="%" )
      {
	$sql = "SELECT 'TOTAL', COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, 'N/A' AS pct_util, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."'";
	if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
	  {
	    $sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
	    $sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
	  }
	else
	  {
	    if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
	      {
		$sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
	      }
	    if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
	      {
		$sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
	      }
	  }
	$result = db_query($db,$sql);
	while ($result->fetchInto($row))
	  {
	    $rkeys=array_keys($row);
	    echo "<TR>";
	    foreach ($rkeys as $rkey)
	      {
		$data[$rkey]=array_shift($row);
		echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
	      }
	    echo "</TR>\n";
	  }
      }
    echo "</TABLE>\n";    

    echo "<H3>Software Usage</H3>\n";

    $first=1;
    $sql = "";
    foreach ( $packages as $pkg )
      {
	if ( isset($_POST[$pkg]) )
	  {
	    if ( $first==1 )
	      {
		$first=0;
	      }
	    else
	      {
		$sql .= "UNION\n";
	      }
	    $sql .= "SELECT '".$pkg."', COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ( ";
	    if ( isset($pkgmatch[$pkg]) )
	      {
		$sql .= $pkgmatch[$pkg];
	      }
	    else
	      {
		$sql .= "script LIKE '%".$pkg."%' OR software LIKE '%".$package."%'";
	      }
	    $sql .= " )";
	    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
		 $_POST['start_date']!="" )
	      {
		$sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		$sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
	      }
	    else
	      {
		if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
		  {
		    $sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		  }
		if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
		  {
		    $sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
		  }
	      }
	    $sql .= "\n";
	  }
      }
    $sql .= " ORDER BY ".$_POST['order']." DESC";

    #echo "<PRE>\n".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    echo "<TABLE border=1>\n";
    echo "<TR><TH>package</TH><TH>jobcount</TH><TH>cpuhours</TH><TH>users</TH><TH>groups</TH></TR>\n";
    while ($result->fetchInto($row))
      {
	$rkeys=array_keys($row);
	echo "<TR>";
	foreach ($rkeys as $rkey)
	  {
	    $data[$rkey]=array_shift($row);
	    echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
	  }
	echo "</TR>\n";
      }
    echo "</TABLE>\n";

    db_disconnect($db);
  }
else
  {
    begin_form("usage-summary.php");

    system_chooser();
    date_fields();

    $orders=array("jobcount","cpuhours","users","groups");
    $defaultorder="cpuhours";
    pulldown("order","Order by",$orders,$defaultorder);
    checkboxes_from_array("Software packages",$packages);

    end_form();
  }

page_footer();
?>