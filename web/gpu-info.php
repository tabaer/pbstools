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
if (isset($_GET['jobid']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['jobid']) )
  { 
    $title="Job info for ".$_POST['system']." jobids like ".$_POST['jobid'];
  } 
 else
  {
    $title="Job info";
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

$props= array("id", "hostname", "gpu_number", "gpu_utilization","memory_utilization", "time_epoch");
// special key "all=1" turns on all the $props.
if (!empty($_POST['all'])) {
    unset($_POST['all']);
    foreach ($props as $key)
	$_POST[$key] = 1;
}

$keys = array_keys($_POST);

$do_checks = array("id", "hostname", "gpu_number", "gpu_utilization", "memory_utilization", "time_epoch");

if ( isset($_POST['jobid']) )
  {
    $db = db_connect();
    $sql = "SELECT id";
    foreach ($do_checks as $key)
    {
	    if ( isset($_POST[$key]) && $key!='id' ) 
        { 
            $sql = $sql.",".$key;
        }
    }
    $sql = $sql." FROM keeneland_gpu_stats WHERE id LIKE '".$_POST['jobid']."%'";

    # Let's do some time restaints
    if ( isset($start) and isset($end) )
    {
        $sql = $sql." AND time_epoch BETWEEN $start AND $end";
    }

    $result =& db_query($db, $sql);
        
    $columns = array("id");
    foreach ($do_checks as $heading)
    {
        if ( isset($_POST[$heading]) )
        {
            $columns[] = $heading;
        }
    }

    if ( isset($_POST['csv']) )
    {
        $csvresult = db_query($db,$sql);
        result_as_csv($csvresult,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
    }
    if ( isset($_POST['xls']) )
    {
        $xlsresult = db_query($db,$sql);
        result_as_xls($xlsresult,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
    }
    if ( isset($_POST['ods']) )
    {
        $odsresult = db_query($db,$sql);
        result_as_ods($odsresult,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
    }
    
    while( $result->fetchInto($row))
    {
          
	    echo "<TABLE border=\"1\">\n";
	    foreach ($props as $key)
	    {
	        if ( isset($_POST[$key]) && $key!='jobid' && $key!='system')
	        {
		        $data[$key]=array_shift($row);

		        echo "<TR><TD width=\"10%\"><PRE>".$key."</PRE></TD><TD width=\"90%\"><PRE>";
		        if ( $key=="time_epoch" )
		        {
		            echo date("Y-m-d H:i:s",$data[$key]);
		        }
		        else
		        {
		            echo htmlspecialchars($data[$key]);
		        }
		echo "</PRE></TD></TR>\n";
	      }
	  }
	echo "</TABLE>\n";
      }
    db_disconnect($db);
    //bookmarkable_url();
  }
else
  {
    begin_form("gpu-info.php");

    text_field("Job id","jobid",8);
    system_chooser();
    
    date_fields();

    checkboxes_from_array("Properties",$props);

    echo "<BR />";

    ## This will handle exporting....
    checkbox("Generate CSV files for supplemental reports","csv");
    checkbox("Generate Excel files for supplemental reports","xls");
    checkbox("Generate ODF files for supplemental reports","ods");

    end_form();
  }

page_footer();
?>
