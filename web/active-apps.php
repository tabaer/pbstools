<?php
# Copyright 2007, 2008, 2016, 2017 Ohio Supercomputer Center
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

if ( isset($_POST['system']) )
  { 
    $title = "Most active applications on ".$_POST['system'];
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
    $title = "Most active software applications";
  }
page_header($title);

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    $db = db_connect();
    $sql = "SELECT sw_app, COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS cpuhours, SUM(".nodehours($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS nodehours, SUM(".charges($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE ( ".sysselect($_POST['system'])." ) AND ( ".dateselect($_POST['datelogic'],$_POST['start_date'],$_POST['end_date'])." ) AND sw_app IS NOT NULL GROUP BY sw_app ORDER BY ".$_POST['order']." DESC LIMIT ".$_POST['limit'];
    #echo "<PRE>".$sql."</PRE>\n";
    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    echo "<TABLE border=\"1\">\n";
    echo "<TR><TH>sw_app</TH><TH>job count</TH><TH>CPU-hours</TH><TH>nodehours</TH><TH>charges</TH><TH>users</TH><TH>groups</TH><TH>accounts</TH></TR>\n";
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
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("active-apps.php");

    virtual_system_chooser();
    date_fields();

    $choices=array("cpuhours","nodehours","charges","jobs","users","groups","accounts");
    $defaultchoice="cpuhours";
    pulldown("order","Order by",$choices,$defaultchoice);
    textfield("limit","Max shown","10",4);

    end_form();
  }

page_footer();
?>
