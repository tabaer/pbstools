<?php
# Copyright 2006, 2007, 2008, 2016 Ohio Supercomputer Center
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

$title = "Software usage by institution";
if ( isset($_POST['system']) )
  {
    $title .= " on ".$_POST['system'];
    $verb = title_verb($_POST['datelogic']);
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && 
	 $_POST['start_date']==$_POST['end_date'] && $_POST['start_date']!="" )
      {
	$title .= " running on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " running between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " running after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " running before ".$_POST['end_date'];
      }
  }
page_header($title);

# connect to DB
$db = db_connect();

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    foreach ($keys as $key)
      {
	if ( $key!='system' && $key!='start_date' && $key!='end_date' && 
	     $key!='order' && $key!='datelogic' )
	  {
	    echo "<H3><CODE>".$key."</CODE></H3>\n";
	    $sql = "SELECT ".institution_match().", COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS cpuhours, SUM(".charges($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE ( ".sysselect($_POST['system'])." )";
	    $sql .= " AND ( ";
	    $sql .= "sw_app='".$key."'";
	    $sql .= " ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) GROUP BY institution";
	    $sql ." ORDER BY ".$_POST['order']." DESC";
            #echo "<PRE>".htmlspecialchars($sql)."</PRE>";
	    $result = db_query($db,$sql);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    echo "<TABLE border=1>\n";
	    echo "<TR><TH>institution</TH><TH>jobs</TH><TH>cpuhours</TH><TH>charges</TH><TH>users</TH><TH>groups</TH><TH>accounts</TH></TR>\n";
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
	    $sql = "SELECT 'TOTAL:',COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS cpuhours, SUM(".charges($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE ( ".sysselect($_POST['system'])." ) AND username IS NOT NULL AND ( ";
	    $sql .= "sw_app='".$key."'";
	    $sql .= " ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." )";
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
            ob_flush();
            flush();
	  }
      }
    page_timer();
    bookmarkable_url();
  }
else
  {
    # list of software packages
    $packages=software_list($db);

    begin_form("software-usage-by-institution.php");

    system_chooser();
    date_fields();

    $orders=array("jobs","cpuhours","charges","users","groups","accounts");
    $defaultorder="cpuhours";
    pulldown("order","Order by",$orders,$defaultorder);

    checkboxes_from_array("Packages",$packages);

    end_form();
  }

db_disconnect($db);
page_footer();
?>
