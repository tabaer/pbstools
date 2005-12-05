<?php
require_once 'DB.php';
require_once 'page-layout.php';

if ( isset($_POST['groupname']) )
  { 
    $title = "Jobs owned by group ".$_POST['groupname']." on ".$_POST['system'];
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
    $title = "Jobs by group";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['groupname']) )
  {
    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
    if ( DB::isError($db) )
      {
        die ($db->getMessage());
      }
    else
      {
	$sql = "SELECT jobid,groupname,username";
	foreach ($keys as $key)
	  {
	    if ( isset($_POST[$key]) && $key!='jobid' && $key!='groupname' && $key!='username' &&
                 $key!='start_date' && $key!='end_date' )
	      {
		$sql = $sql.",".$key;
	      }
	  }
	$sql = $sql." FROM Jobs WHERE groupname = '".$_POST['groupname']."' AND system LIKE '".$_POST['system']."'";
	if ( isset($_POST['start_date']) &&   isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
	  {
	    $sql = $sql." AND FROM_UNIXTIME(submit_ts) >= '".$_POST['start_date']." 00:00:00'";
	    $sql = $sql." AND FROM_UNIXTIME(submit_ts) <= '".$_POST['start_date']." 23:59:59'";
	  }
	else
	  {
	    if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
	      {
		$sql = $sql." AND FROM_UNIXTIME(submit_ts) >= '".$_POST['start_date']." 00:00:00'";
	      }
	    if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
	      {
		$sql = $sql." AND FROM_UNIXTIME(submit_ts) <= '".$_POST['end_date']." 23:59:59'";
	      }
	  }
        $sql = $sql." ORDER BY submit_ts;";
#	echo "<PRE>".$sql."</PRE>\n";
	$result = $db->query($sql);
	if ( DB::isError($db) )
	  {
	    die ($db->getMessage());
	  }
	else
	  {
	    echo "<TABLE border=1 width=\"100%\">\n";
	    $i=0;
	    while ($result->fetchInto($row))
	      {
		$rkeys=array_keys($row);
		if ( $i==0 )
		  {
		    echo "<TR><TH>jobid</TH><TH>groupname</TH><TH>username</TH>";
		    foreach ($keys as $key)
		      {
			if ( $key!='jobid' && $key!='groupname' && $key!='username' && $key!='start_date' && $key!='end_date' )
			  {
			    echo "<TH>".$key."</TH>";
			  }
		      }
		    echo "</TR>\n";
		    $i++;
		  } 
		echo "<TR>";
		foreach ($rkeys as $key)
		  {
		    $data[$key]=array_shift($row);
		    echo "<TD><PRE>".htmlspecialchars($data[$key])."</PRE></TD>";
		  }
		echo "</TR>\n";
	       }
	    echo "</TABLE>\n";
	  }
      }

    $db->disconnect();
  }
else
  {
    echo "<FORM method=\"POST\" action=\"jobs-by-group.php\">\n";
    echo "Group:  <INPUT type=\"text\" name=\"groupname\" size=\"16\"><BR>\n";
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

    echo "Start date: <INPUT type=\"text\" name=\"start_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";
    echo "End date: <INPUT type=\"text\" name=\"end_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";

    echo "Show properties:<BR>\n";
    $props=array("jobname","nproc","mppe","mppssp",
		 "nodes","queue","submit_ts","start_ts","end_ts","cput_req",
		 "cput","walltime_req","walltime","mem_req","mem_kb",
		 "vmem_req","vmem_kb","hostlist","exit_status","script");
    checkboxes_from_array($props);

    echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";
  }

page_footer();
?>