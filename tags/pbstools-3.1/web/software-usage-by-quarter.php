<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
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

$title = "Software usage by quarter";
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

# connect to DB
$db = db_connect();

# list of software packages
$packages=software_list($db);

# regular expressions for different software packages
#$pkgmatch=software_match_list($db);

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    foreach ($keys as $key)
      {
	if ( $key!='system' && $key!='start_date' && $key!='end_date' )
	  {
	    echo "<H3><CODE>".$key."</CODE></H3>\n";
	    $sql = "SELECT ".xaxis_column("quarter").", COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system']).") AS cpuhours, SUM(".charges($db,$_POST['system']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ( ";
// 	    if ( isset($pkgmatch[$key]) )
// 	      {
// 		$sql .= $pkgmatch[$key];
// 	      }
// 	    else
// 	      {
// 		$sql .= "script LIKE '%".$key."%' OR software LIKE '%".$key."%'";
// 	      }
	    $sql .= "sw_app='".$key."'";
	    $sql .= " ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY quarter;";
            #echo "<PRE>".htmlspecialchars($sql)."</PRE>";
	    $result = db_query($db,$sql);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    echo "<TABLE border=1>\n";
	    echo "<TR><TH>quarter</TH><TH>jobs</TH><TH>cpuhours</TH><TH>charges</TH><TH>users</TH><TH>groups</TH></TR>\n";
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
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("software-usage-by-quarter.php");

    system_chooser();
    date_fields();

    checkboxes_from_array("Packages",$packages);

    end_form();
  }

db_disconnect($db);
page_footer();
?>
