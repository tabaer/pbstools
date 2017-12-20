<?php
# Copyright 2006, 2007, 2008, 2017 Ohio Supercomputer Center
# Copyright 2008, 2009, 2011, 2014 University of Tennessee
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

$title = "Moab-style Statistics";
if ( isset($_POST['system']) )
  {
    $title .= " for ".$_POST['system']. " jobs";
    $verb = title_verb($_POST['datelogic']);
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) &&
	 $_POST['start_date']==$_POST['end_date'] && 
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
page_header($title);

if ( isset($_POST['system']) )
  {
    if ( isset($_POST['order']) )
      {
	$order = $_POST['order'];
      }
    else
      {
	$order = 'cpuhours';
      }
    if ( isset($_POST['limit']) )
      {
	$limit = $_POST['limit'];
      }
    else
      {
	$limit = 0;
      }

    $db = db_connect();

    jobstats_summary($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']);

    // Moab statistics
    jobstats_output_metric('Moab Statistics vs. Job Class',
			   'moabstats_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic'],
			   false, // limit_access
			   false, // ascending
			   $limit); 

    jobstats_output_metric('Moab Statistics vs. Institution',
			   'moabstats_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic'],
			   false, // limit_access
			   false, // ascending
			   $limit);

    jobstats_output_metric('Moab Statistics vs. Account',
			   'moabstats_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic'],
			   false, // limit_access
			   false, // ascending
			   $limit);

    jobstats_output_metric('Moab Statistics vs. Group',
			   'moabstats_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic'],
			   false, // limit_access
			   false, // ascending
			   $limit);

    jobstats_output_metric('Moab Statistics vs. User',
			   'moabstats_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic'],
			   false, // limit_access
			   false, // ascending
			   $limit);

    jobstats_output_metric('Moab Statistics vs. QOS',
			   'moabstats_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic'],
			   false, // limit_access
			   false, // ascending
			   $limit);

    db_disconnect($db);
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("moab-stats.php");

    $oneweekago = date("Y-m-d",time()-(7*24*3600));
    $yesterday = date("Y-m-d",time()-(1*24*3600));

    virtual_system_chooser();
    date_fields($oneweekago,$yesterday);

    $choices = columnnames('moabstats');
    array_unshift($choices,'jobs');
    $defaultchoice = 'cpuhours';
    pulldown("order","Order by",$choices,$defaultchoice);
    textfield("limit","Max shown","0",4);
    echo "(Max shown=0 means no limit)<BR>\n";

    jobstats_input_header();

    // Moab statistics
    jobstats_input_metric("Moab Statistics vs. Job Class","moabstats_vs_queue");
    jobstats_input_metric("Moab Statistics vs. Institution","moabstats_vs_institution");
    jobstats_input_metric("Moab Statistics vs. Account","moabstats_vs_account");
    jobstats_input_metric("Moab Statistics vs. Group","moabstats_vs_groupname");
    jobstats_input_metric("Moab Statistics vs. User","moabstats_vs_username");
    //jobstats_input_metric("Moab Statistics vs. QOS","moabstats_vs_qos");

    jobstats_input_footer();

    end_form();
  }

page_footer();
?>
