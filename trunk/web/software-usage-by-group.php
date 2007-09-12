<?php
# Copyright 2006, 2007 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'page-layout.php';
require_once 'dbutils.php';
require_once 'site-specific.php';

$title = "Software usage by group";
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
$pkgmatch=software_match_list();

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db = db_connect();
    foreach ($keys as $key)
      {
	if ( $key!='system' && $key!='start_date' && $key!='end_date' )
	  {
	    echo "<H3><CODE>".$key."</CODE></H3>\n";
	    $sql = "SELECT groupname, COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, SUM(TIME_TO_SEC(cput))/3600.0 AS cpuhours_alt FROM Jobs WHERE system LIKE '".$_POST['system']."'  AND groupname IS NOT NULL AND ( ";
	    if ( isset($pkgmatch[$key]) )
	      {
		$sql .= $pkgmatch[$key];
	      }
	    else
	      {
		$sql .= "script LIKE '%".$key."%' OR software LIKE '%".$key."%'";
	      }
	    $sql .= " )";
	    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
		 $_POST['start_date']!="" )
		  {
		    $sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		    $sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
		  }
		else
		  {
		    if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
		      {
			$sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		      }
		    if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
		      {
			$sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
		      }
		  }
	    $sql .= " GROUP BY groupname UNION SELECT 'TOTAL:',COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600.0 AS cpuhours, SUM(TIME_TO_SEC(cput))/3600.0 AS alt_cpuhours FROM Jobs WHERE system LIKE '".$_POST['system']."'  AND groupname IS NOT NULL AND ( ";
	    if ( isset($pkgmatch[$key]) )
	      {
		$sql .= $pkgmatch[$key];
	      }
	    else
	      {
		$sql .= "script LIKE '%".$key."%' OR software LIKE '%".$key."%'";
	      }
	    $sql .= " )";
	    if ( isset($_POST['start_date']) &&   isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
		 $_POST['start_date']!="" )
	      {
		$sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		$sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
	      }
	    else
	      {
		if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
		  {
		    $sql .= " AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		  }
		if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
		  {
		    $sql .= " AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
		  }
	      }
	    $sql .= ";";
            #echo "<PRE>".htmlspecialchars($sql)."</PRE>";
	    $result = db_query($db,$sql);
	    echo "<TABLE border=1>\n";
	    echo "<TR><TH>groupname</TH><TH>jobcount</TH><TH>cpuhours</TH><TH>cpuhours_alt</TH></TR>\n";
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
	    echo "</TABLE>\n";
	  }
      }
    db_disconnect($db);
  }
else
  {
    begin_form("software-usage-by-group.php");

    system_chooser();
    date_fields();

    checkboxes_from_array("Packages",$packages);

    end_form();
  }

page_footer();
?>