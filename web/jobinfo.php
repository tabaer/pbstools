<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'DB.php';
require_once 'page-layout.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['jobid'])) {
    $_POST = $_GET;
}

if ( isset($_POST['jobid']) )
  { 
    $title="Job info for ".$_POST['system']." jobid ".$_POST['jobid'];
  } 
 else
  {
    $title="Job info";
  }
page_header($title);

$props=array("username","groupname","jobname","nproc","mppe","mppssp",
	     "nodes","queue","submit_ts","start_ts","end_ts","cput_req",
	     "cput","walltime_req","walltime","mem_req","mem_kb",
	     "vmem_req","vmem_kb","hostlist","exit_status","script");

// special key "all=1" turns on all the $props.
if (!empty($_POST['all'])) {
    unset($_POST['all']);
    foreach ($props as $key)
	$_POST[$key] = 1;
}

$keys = array_keys($_POST);
if ( isset($_POST['jobid']) )
  {
    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
    if ( DB::isError($db) )
      {
        die ($db->getMessage());
      }
    else
      {
	$sql = "SELECT jobid";
	foreach ($keys as $key)
	  {
	    if ( isset($_POST[$key]) && $key!='jobid' ) { $sql = $sql.",".$key; }
	  }
	$sql = $sql." FROM Jobs WHERE jobid LIKE '".$_POST['jobid'].".%' AND system LIKE '".$_POST['system']."';";
	$result = $db->query($sql);
	if ( DB::isError($db) )
	  {
	    die ($db->getMessage());
	  }
	else
	  {
	    while ($result->fetchInto($row))
	      {
		echo "<TABLE border=1 width=\"100%\">\n";
		foreach ($keys as $key)
		  {
		    if ( isset($_POST[$key]) )
		      {
			$data[$key]=array_shift($row);
			echo "<TR><TD width=\"10%\"><PRE>".$key."</PRE></TD><TD width=\"90%\"><PRE>";
			if ( $key=="submit_ts" || $key=="start_ts" || $key=="end_ts" )
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
	  }
      }

    $db->disconnect();
  }
else
  {
    echo "<FORM method=\"POST\" action=\"jobinfo.php\">\n";
    echo "Job id:  <INPUT type=\"text\" name=\"jobid\" size=\"8\"> (Numeric jobid only!)<BR>\n";
    echo "System:  <SELECT name=\"system\" size=\"1\">\n";
    echo "<OPTION value=\"%\">Any\n";
    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
    if ( DB::isError($db) )
      {
        die ($db->getMessage());
      }
    else
      {
	$sql = "SELECT DISTINCT(system) FROM Jobs;";
	$result = $db->query($sql);
	if ( DB::isError($db) )
	  {
	    die ($db->getMessage());
	  }
	else
	  {
	    while ($result->fetchInto($row))
	      {
		$rkeys = array_keys($row);
		foreach ($rkeys as $rkey)
		  {
		    echo "<OPTION>".$row[$rkey]."\n";
		  }
	      }
	  }
      }
    $db->disconnect();
    echo "</SELECT><BR>\n";

    echo "Show properties:<BR>\n";
    checkboxes_from_array($props);

    echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";
  }

page_footer();
?>
