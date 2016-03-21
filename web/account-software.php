<?php
# Copyright 2007, 2008 Ohio Supercomputer Center
# Copyright 2009, 2011, 2014 University of Tennessee
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

$title = "Software usage by account ";
if ( isset($_POST['account']) )
  {
    $title .= $_POST['account'];
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
    # connect to DB
    $db = db_connect();

    # software usage
    echo "<TABLE border=1>\n";
    echo "<TR><TH>package</TH><TH>jobs</TH><TH>cpuhours</TH><TH>charges</TH><TH>users</TH><TH>groups</TH></TR>\n";
    ob_flush();
    flush();
    
    $sql = "SELECT sw_app, COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date']).") AS cpuhours, SUM(".charges($db,$_POST['system'],$_POST['start_date'],$_POST['end_date']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE sw_app IS NOT NULL AND system LIKE '".$_POST['system']."' AND account LIKE '".$_POST['account']."' AND ( ".dateselect("during",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY sw_app ORDER BY ".$_POST['order']." DESC";
    
    #echo "<PRE>\n".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
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
    if ( isset($_POST['csv']) )
      {
	$csvresult = db_query($db,$sql);
	$columns = array("package","jobs","cpuhours","users","groups");
	result_as_csv($csvresult,$columns,$_POST['system']."-".$_POST['groupname']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
      }
    if ( isset($_POST['xls']) )
      {
	$xlsresult = db_query($db,$sql);
	$columns = array("package","jobs","cpuhours","users","groups");
	result_as_xls($xlsresult,$columns,$_POST['system']."-".$_POST['groupname']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
      }
    if ( isset($_POST['ods']) )
      {
	$odsresult = db_query($db,$sql);
	$columns = array("package","jobs","cpuhours","users","groups");
	result_as_ods($odsresult,$columns,$_POST['system']."-".$_POST['groupname']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
      }

    db_disconnect($db);
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("account-software.php");

    text_field("Account","account",16);
    system_chooser();
    date_fields();

    $orders=array("jobs","cpuhours","users");
    $defaultorder="cpuhours";
    pulldown("order","Order results by",$orders,$defaultorder);
    checkbox("Generate CSV file","csv");
    checkbox("Generate Excel file","xls");
    checkbox("Generate ODF file","ods");

    end_form();
  }

page_footer();
?>
