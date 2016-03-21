<?php
# Copyright 2007, 2008 Ohio Supercomputer Center
# Copyright 2009, 2010, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/queue-software.php $
# $Revision: 315 $
# $Date: 2009-06-04 18:34:52 -0400 (Thu, 04 Jun 2009) $
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

$title = "Software usage by queue ";
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

    $queues = array();
    $sql = "SELECT DISTINCT(queue) FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ".dateselect("during",$_POST['start_date'],$_POST['end_date']);
    #echo "<PRE>\n".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    while ($result->fetchInto($row))
      {
	while ($result->fetchInto($row))
	  {
	    $rkeys=array_keys($row);
	    foreach ($rkeys as $rkey)
	      {
		$newelt = array_shift($row);
		array_push($queues,$newelt);
	      }
	  }
      }

    # software usage
    foreach ( $queues as $queue )
      {
	echo "<H3>System <TT>".$_POST['system']."</TT> queue <TT>".$queue."</TT></H3>\n";
	echo "<TABLE border=1>\n";
	echo "<TR><TH>package</TH><TH>jobs</TH><TH>cpuhours</TH><TH>charges</TH><TH>users</TH><TH>groups</TH><TH>accounts</TH></TR>\n";
	ob_flush();
	flush();
	
	$sql = "SELECT sw_app, COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date']).") AS cpuhours, SUM(".charges($db,$_POST['system'],$_POST['start_date'],$_POST['end_date']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE sw_app IS NOT NULL AND system LIKE '".$_POST['system']."' AND queue LIKE '".$queue."' AND ( ".dateselect("during",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY sw_app ORDER BY ".$_POST['order']." DESC";
	
	
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
	if ( isset($_POST['csv']) )
	  {
	    $csvresult = db_query($db,$sql);
	    $columns = array("package","jobs","cpuhours","charges","users","groups","accounts");
	    result_as_csv($csvresult,$columns,$_POST['system']."-".$_POST['username']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
	if ( isset($_POST['xls']) )
	  {
	    $xlsresult = db_query($db,$sql);
	    $columns = array("package","jobs","cpuhours","charges","users","groups","accounts");
	    result_as_xls($xlsresult,$columns,$_POST['system']."-".$_POST['username']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
	if ( isset($_POST['ods']) )
	  {
	    $odsresult = db_query($db,$sql);
	    $columns = array("package","jobs","cpuhours","charges","users","groups","accounts");
	    result_as_ods($odsresult,$columns,$_POST['system']."-".$_POST['username']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
      }

    db_disconnect($db);
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("queue-software.php");

    system_chooser();
    date_fields();

    $orders=array("jobs","cpuhours","charges","users","groups","accounts");
    $defaultorder="cpuhours";
    pulldown("order","Order results by",$orders,$defaultorder);
    checkbox("Generate CSV file","csv");
    checkbox("Generate Excel file","xls");
    checkbox("Generate ODF file","ods");

    end_form();
  }

page_footer();
?>
