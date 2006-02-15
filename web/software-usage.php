<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'DB.php';
require_once 'page-layout.php';
require_once 'site-specific.php';

$title = "Software usage";
if ( isset($_POST['system']) )
  {
    $title .= " on ".$_POST['system'];
  }
if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
     $_POST['start_date']!="" )
  {
    $title .= " started on ".$_POST['start_date'];
  }
 else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	   $_POST['start_date']!="" &&  $_POST['end_date']!="" )
   {
     $title .= " started between ".$_POST['start_date']." and ".$_POST['end_date'];
   }
 else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
   {
     $title .= " started after ".$_POST['start_date'];
   }
 else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
   {
     $title .= " started before ".$_POST['end_date'];
   }
page_header($title);

# list of software packages
$packages=software_list();

# regular expressions for different software packages
$pkgre=software_regexp_list();

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
    if ( DB::isError($db) )
      {
	die ($db->getMessage());
      }
    else
      {
	foreach ($keys as $key)
	  {
	    if ( $key!='system' && $key!='start_date' && $key!='end_date' )
	      {
		echo "<H3><CODE>".$key."</CODE></H3>\n";
		$sql = "SELECT system,COUNT(jobid) AS jobcount,SEC_TO_TIME(SUM(nproc*TIME_TO_SEC(walltime))) AS cpuhours,COUNT(DISTINCT(username)) AS users,COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."' AND script REGEXP ";
		if ( isset($pkgre[$key]) )
		  {
		    $sql .= "'".$pkgre[$key]."'";
		  }
		else
		  {
		    $sql .= "'".$key."'";
		  }
		if ( isset($_POST['start_date']) &&   isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
		     $_POST['start_date']!="" )
		  {
		    $sql = $sql." AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		    $sql = $sql." AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
		  }
		else
		  {
		    if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
		      {
			$sql = $sql." AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		      }
		    if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
		      {
			$sql = $sql." AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
		      }
		  }
		$sql .= " GROUP BY system;";
		$result = $db->query($sql);
		if ( DB::isError($db) )
		  {
		    die ($db->getMessage());
		  }
		else
		  {
		    echo "<TABLE border=1>\n";
		    echo "<TR><TH>system</TH><TH>jobcount</TH><TH>cpuhours</TH><TH>users</TH><TH>groups</TH></TR>\n";
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
		    if ( $_POST['system']=="%" )
		      {
                        # compute totals iff wildcarding on all systems
			$sql = "SELECT COUNT(jobid) AS jobcount,SEC_TO_TIME(SUM(nproc*TIME_TO_SEC(walltime))) AS cpuhours,COUNT(DISTINCT(username)) AS users,COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE script REGEXP ";
			if ( isset($pkgre[$key]) )
			  {
			    $sql .= "'".$pkgre[$key]."'";
			  }
			else
			  {
			    $sql .= "'".$key."'";
			  }
			if ( isset($_POST['start_date']) &&   isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
			     $_POST['start_date']!="" )
			  {
			    $sql = $sql." AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
			    $sql = $sql." AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
			  }
			else
			  {
			    if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
			      {
				$sql = $sql." AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
			      }
			    if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
			      {
				$sql = $sql." AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
			      }
			  }
			$sql .= ";";
			#echo "<PRE>".$sql."</PRE>\n";
			$result = $db->query($sql);
			if ( DB::isError($db) )
			  {
			    die ($db->getMessage());
			  }
			else
			  {
			    while ($result->fetchInto($row))
			      {
				$rkeys=array_keys($row);
				echo "<TR><TH>Total</TH>";
				foreach ($rkeys as $rkey)
				  {
				    $data[$rkey]=array_shift($row);
				    echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
				  }
				echo "</TR>\n";
			      }
			  }
		      }
		    echo "</TABLE>\n";
		  }
	      }
	  }
      }
    $db->disconnect();
  }
else
  {
    echo "<FORM method=\"POST\" action=\"software-usage.php\">\n";
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

    echo "Show packages:<BR>\n";
    checkboxes_from_array($packages);

    echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";
  }

page_footer();
?>