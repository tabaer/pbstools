<?php
# Copyright 2006, 2008 Ohio Supercomputer Center
# Copyright 2008 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'page-layout.php';
require_once 'dbutils.php';
require_once 'metrics.php';
require_once 'site-specific.php';

$title = "Job statistics";
if ( isset($_POST['system']) )
  {
    $title .= " for ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) &&
	 $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$title .= " on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " from ".$_POST['start_date']." to ".$_POST['end_date'];
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
page_header($title);

begin_form("jobstats.php");

virtual_system_chooser();
date_fields();

// by week
jobstats_input_header();
jobstats_input_metric("Job Count by Week","jobs_vs_week");
jobstats_input_metric("CPU Time by Week","cpuhours_vs_week");
jobstats_input_metric("Charges by Week","charges_vs_week");
jobstats_input_metric("Job Length by Week","walltime_vs_week");
jobstats_input_metric("Queue Time by Week","qtime_vs_week");
jobstats_input_metric("Backlog by Week","backlog_vs_week");
jobstats_input_metric("Expansion Factor by Week","xfactor_vs_week");
jobstats_input_metric("Active Users by Week","users_vs_week");
jobstats_input_metric("Active Groups by Week","groups_vs_week");
jobstats_input_metric("Active Accounts by Week","accounts_vs_week");
jobstats_input_footer();

end_form();

page_footer();
?>
