<?php
# Copyright 2007, 2008 Ohio Supercomputer Center
# Copyright 2009 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
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

$title = "Software usage by project ";
if ( isset($_POST['project']) )
  {
    $title .= $_POST['project'];
  }
if ( isset($_POST['system']) )
  {
    $title .= " on ".$_POST['system'];
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

    # software usage
    echo "<TABLE border=1>\n";
    echo "<TR><TH>package</TH><TH>jobs</TH><TH>cpuhours</TH><TH>users</TH><TH>groups</TH></TR>\n";
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
	$sql .= "SELECT '".$pkg."', COUNT(jobid) AS jobs, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."' AND account LIKE '".$_POST['project']."' AND ( ";
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
    $sql .= " LIMIT ".$_POST['limit'];
    
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
	$columns = array("package","jobs","cpuhours","users","groups");
	result_as_xls($xlsresult,$columns,$_POST['system']."-".$_POST['project']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
      }
    if ( isset($_POST['ods']) )
      {
	$odsresult = db_query($db,$sql);
	$columns = array("package","jobs","cpuhours","users","groups");
	result_as_ods($odsresult,$columns,$_POST['system']."-".$_POST['project']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
      }

    db_disconnect($db);
    bookmarkable_url();
  }
else
  {
    begin_form("project-software.php");

    text_field("Project","project",16);
    system_chooser();
    date_fields();

    $orders=array("jobs","cpuhours","users","groups");
    $defaultorder="cpuhours";
    pulldown("order","Order results by",$orders,$defaultorder);
    textfield("limit","Max shown","10",4);
    checkbox("Generate Excel file","xls");
    checkbox("Generate ODF file","ods");

    end_form();
  }

page_footer();
?>
