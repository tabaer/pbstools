<?php
# Copyright 2007, 2008 Ohio Supercomputer Center
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/active-groups.php $
# $Revision: 199 $
# $Date: 2007-09-12 13:39:15 -0400 (Wed, 12 Sep 2007) $
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

if ( isset($_POST['system']) )
  { 
    $title = "Most active groups on ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
      {
	$title .= " on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " between ".$_POST['start_date']." and ".$_POST['end_date'];
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
else
  {
    $title = "Most active groups";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db = db_connect();
    $sql = "SELECT groupname, COUNT(DISTINCT(username)) AS users, COUNT(jobid) AS jobcount, SUM(nproc*TIME_TO_SEC(walltime))/3600 AS cpuhrs FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ( ".dateselect("submit",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY groupname ORDER BY ".$_POST['order']." DESC LIMIT ".$_POST['limit'];
#    echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    echo "<TABLE border=\"1\">\n";
    echo "<TR><TH>group</TH><TH>users</TH><TH>job count</TH><TH>CPU-hours</TH></TR>\n";
        while ($result->fetchInto($row))
      {
	echo "<TR>";
	$rkeys=array_keys($row);
	foreach ($rkeys as $key)
	  {
	    $data[$key]=array_shift($row);
	    echo "<TD align=\"right\"><PRE>".htmlspecialchars($data[$key])."</PRE></TD>";
	  }
	echo "</TR>\n";
      }
    echo "</TABLE>\n";
  
    db_disconnect($db);
    bookmarkable_url();
  }
else
  {
    begin_form("active-groups.php");

    system_chooser();
    date_fields();

    $choices=array("cpuhrs","jobcount","users");
    $defaultchoice="cpuhrs";
    pulldown("order","Order by",$choices,$defaultchoice);
    textfield("limit","Max shown","10",4);

    end_form();
  }

page_footer();
?>