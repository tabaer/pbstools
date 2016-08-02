<?php
# Copyright 2007, 2008 Ohio Supercomputer Center
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/usage-summary.php $
# $Revision: 168 $
# $Date: 2007-06-26 16:53:55 -0400 (Tue, 26 Jun 2007) $
require_once 'dbutils.php';
require_once 'page-layout.php';
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

    # system overview
    echo "<H3>Overview</H3>\n";
    $sql = "SELECT system, COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, NULL AS pct_util, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY system ORDER BY ".$_POST['order']." DESC";
    #echo "<PRE>\n".$sql."</PRE>\n";
    echo "<TABLE border=1>\n";
    echo "<TR><TH>system</TH><TH>jobcount</TH><TH>cpuhours</TH><TH>%util</TH><TH>users</TH><TH>groups</TH></TR>\n";
    ob_flush();
    flush();

    $result = db_query($db,$sql);
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
		    $data[$rkey]="N/A";
		  }
	      }
	    else
	      {
		$data[$rkey]=$row[$rkey];
	      }
	    echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
	  }
	echo "</TR>\n";
	ob_flush();
	flush();
      }
    if ( $_POST['system']=="%" )
      {
	$sql = "SELECT 'TOTAL', COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, 'N/A' AS pct_util, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." )";
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
	    ob_flush();
	    flush();
	  }
      }
    echo "</TABLE>\n";    

    # by institution
    # NOTE By-institution jobstats involves OSC site-specific logic.  You may
    # want to comment out the following statement.
    $inst_summary=true;
    if ( isset($_POST['institution']) && isset($inst_summary) && $inst_summary==true )
      {
	echo "<H3>Usage By Institution</H#>\n";
	$result=get_metric($db,$_POST['system'],'institution','usage',$_POST['start_date'],$_POST['end_date']);
	metric_as_table($result,'institution','usage');
	if ( isset($_POST['xls']) )
	  {
	    $xlsresult=get_metric($db,$_POST['system'],'institution','usage',$_POST['start_date'],$_POST['end_date']);
	    metric_as_xls($xlsresult,'institution','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	if ( isset($_POST['ods']) )
	  {
	    $odsresult=get_metric($db,$_POST['system'],'institution','usage',$_POST['start_date'],$_POST['end_date']);
	    metric_as_ods($odsresult,'institution','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	ob_flush();
	flush();
      }

    # software usage
    if ( isset($_POST['software']) )
      {
	echo "<H3>Software Usage</H3>\n";
	echo "<TABLE border=1>\n";
	echo "<TR><TH>package</TH><TH>jobcount</TH><TH>cpuhours</TH><TH>users</TH><TH>groups</TH></TR>\n";
	ob_flush();
	flush();
	
	$first=1;
	$sql = "";
	foreach ( $packages as $pkg )
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
	    $sql .= " ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." )";
	    $sql .= "\n";
	  }
	$sql .= " ORDER BY ".$_POST['order']." DESC";
	
#echo "<PRE>\n".$sql."</PRE>\n";
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
	    ob_flush();
	    flush();
	  }
	echo "</TABLE>\n";
	if ( isset($_POST['xls']) )
	  {
	    $xlsresult = db_query($db,$sql);
	    $columns = array("package","jobcount","cpuhours","users","groups");
	    result_as_xls($xlsresult,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
	if ( isset($_POST['ods']) )
	  {
	    $odsresult = db_query($db,$sql);
	    $columns = array("package","jobcount","cpuhours","users","groups");
	    result_as_ods($odsresult,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
      }

    db_disconnect($db);
    bookmarkable_url();
  }
else
  {
    begin_form("usage-summary.php");

    system_chooser();
    date_fields();

    $orders=array("jobcount","cpuhours","users","groups");
    checkboxes_from_array("Supplemental reports",array("institution","software"));
    $defaultorder="cpuhours";
    pulldown("order","Order results by",$orders,$defaultorder);
    checkbox("Generate Excel files for supplemental reports","xls");
    checkbox("Generate ODF files for supplemental reports","ods");

    end_form();
  }

page_footer();
?>