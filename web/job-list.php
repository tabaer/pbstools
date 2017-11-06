<?php
# Copyright 2006, 2007, 2008, 2016, 2017 Ohio Supercomputer Center
# Copyright 2009, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';
require_once 'site-specific.php';
require_once 'phplib/Excel/Workbook.php';
require_once 'phplib/Excel/Worksheet.php';
require_once 'phplib/Excel/Format.php';
require_once 'phplib/ods.php';


# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['system']) )
  {
    $title = "Jobs on ".$_POST['system'];
    $verb = title_verb($_POST['datelogic']);
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$title .= " ".$verb." on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
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
    $title = "Job list";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db =db_connect();
    $sql = "SELECT system, jobid, username, groupname, account, jobname, nproc, nodes, mem_req, mem_kb, FROM_UNIXTIME(submit_ts), FROM_UNIXTIME(eligible_ts), FROM_UNIXTIME(start_ts), FROM_UNIXTIME(end_ts), SEC_TO_TIME(start_ts-eligible_ts), walltime_req, walltime, ".cpuhours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic'])." AS cpuhours, ".nodehours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic'])." AS nodehours, ".charges($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic'])." AS charges, queue, IF(script IS NULL,'interactive','batch') AS type, sw_app AS software FROM Jobs WHERE ( ".sysselect($_POST['system'])." ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) ORDER BY start_ts;";
    #echo "<PRE>".$sql."</PRE>\n";
    $columns = array("system", "jobid", "username", "groupname", "account", "jobname", "nproc", "nodes", "mem_req", "mem_used", "submit_time", "eligible_time", "start_time", "end_time", "qtime", "walltime_req", "walltime", "cpuhours", "nodehours", "charges", "queue", "type", "software");
    $file_base = $_POST['system']."-joblist-".$_POST['start_date']."-".$_POST['end_date'];
    // if table
    if ( isset( $_POST['table'] ) )
      {
	$table_result = db_query($db,$sql);
	if ( PEAR::isError($table_result) )
	  {
	    echo "<PRE>".$result->getMessage()."</PRE>\n";
	  }
	result_as_table($table_result,$columns);
      }
    // if csv
    if ( isset( $_POST['csv'] ) )
      {
	$csv_result = db_query($db,$sql);
	result_as_csv($csv_result,$columns,$file_base);
      }
    //if xls
    if ( isset( $_POST['xls'] ) )
      {
	$xls_result = db_query($db,$sql);
	result_as_xls($xls_result,$columns,$file_base);
      }
    // if ods
    if ( isset( $_POST['ods'] ) )
      {
	$ods_result = db_query($db,$sql);
	result_as_ods($ods_result,$columns,$file_base);
      }
    db_disconnect($db);
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("job-list.php");

    virtual_system_chooser();
    date_fields();
    checkbox("Generate HTML table","table",1);
    checkbox("Generate CSV file","csv");
    checkbox("Generate Excel file","xls");
    checkbox("Generate ODF files","ods");

    end_form();
  }

page_footer();
?>
