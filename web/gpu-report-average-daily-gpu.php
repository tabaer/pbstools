<?php
# Copyright 2006 Ohio Supercomputer Center
# Copyright 2009, 2010 University of Tennessee
# Revision info:
# $HeadURL: https://svn.nics.utk.edu/repos/pbstools/trunk/web/jobinfo.php $
require_once 'gpu-page-layout.php';
require_once 'gpu-dbutils.php';
require_once 'gpu-metrics.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['system']) )
  { 
    $title="Report for ".$_POST['system']." on average GPU Utilization";
  } 
 else
  {
    $title="Report Form";
  }
if ( isset($_POST['system']) )
{
    ## All jobs on a certain date
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) &&
        $_POST['start_date']==$_POST['end_date'] &&
        $_POST['start_date']!="" )   
    {
        $title .= " on ".$_POST['start_date'];
        $start  = strtotime($_POST['start_date']);
        $end    = strtotime($_POST['end_date']) + 24*60*60-1;
    }
    ## Start and End dates are filled in.  A 'normal' from x to y.
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && 
        $_POST['start_date']!=$_POST['end_date'] &&
        $_POST['start_date']!="" &&  $_POST['end_date']!="" )
    {
        $title .= " from ".$_POST['start_date']." to ".$_POST['end_date'];
        $start  = strtotime($_POST['start_date']);
        $end    = strtotime($_POST['end_date']) + 24*60*60-1;
    }
    ## All dates past the start
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
    {
        $title .= " after ".$_POST['start_date'];
        $start  = strtotime($_POST['start_date']);
        $end    = time();
    }
    ## All dates after the end date
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
    {
        $title .= " before ".$_POST['end_date'];
        $start  = 0;
        $end    = strtotime($_POST['end_date']);
    }
}

page_header($title);

$keys = array_keys($_POST);

if ( isset($_POST['system']) )
  {
    $db = db_connect();

    ## If we don't have a start and stop, make it for the beginning to end of time!
    ##  (jk, make it from one day past today)
    if ( !( isset($start) and isset($end) ) )
    {
        $start = 0;
        #$end = time()+24*60*60;
        $end = mktime(0, 0, 0, date("m",time()), date("d",time())+1, date("Y",time()));
    }

    ## Initialize our SQL to be nice.
    $sql = "";

    # Grab a query for each day in the range.  Oh, did I mention we
    #   increment $i by one day each iteration?
    for($i = $start; $i <= $end; $i = mktime(0, 0, 0, date("m", $i), date("d", $i)+1, date("Y", $i)))
    {
        ## Grab what's a day from $i
        $i_next = mktime(0, 0, -1, date("m", $i), date("d", $i)+1, date("Y", $i));
		$d = date("Y-m-d",$i);
        
        ## Append the SQL onto our current sql
        $sql = $sql."SELECT '$d' AS Date, AVG(gpu_utilization) FROM keeneland_gpu_stats WHERE time_epoch BETWEEN $i AND $i_next";
        if ( $i_next+1 <= $end )
        {
            $sql = $sql." UNION ";
        }
    }

    $result =& db_query($db, $sql);
    
    $columns = array("Date", "Average GPU Utilization");

    if ( isset($_POST['csv']) )
    {
        $csvresult = db_query($db,$sql);
        result_as_csv($csvresult,$columns,$_POST['system']."-daily_gpu_utilization-".$_POST['start_date']."-".$_POST['end_date']);
    }
    if ( isset($_POST['xls']) )
    {
        $xlsresult = db_query($db,$sql);
        result_as_xls($xlsresult,$columns,$_POST['system']."-daily_gpu_utilization-".$_POST['start_date']."-".$_POST['end_date']);
    }
    if ( isset($_POST['ods']) )
    {
        $odsresult = db_query($db,$sql);
        result_as_ods($odsresult,$columns,$_POST['system']."-daily_gpu_utilization-".$_POST['start_date']."-".$_POST['end_date']);
    }
    db_disconnect($db);
    //bookmarkable_url();
  }
else
  {
    begin_form("gpu-report-average-daily-gpu.php");

    system_chooser();
    
    date_fields();

    ## This will handle exporting....
    checkbox("Generate CSV files for supplemental reports","csv");
    checkbox("Generate Excel files for supplemental reports","xls");
    checkbox("Generate ODF files for supplemental reports","ods");

    end_form();
  }

page_footer();
?>
