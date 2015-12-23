<?php
# Copyright 2006 Ohio Supercomputer Center
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

// by month
jobstats_input_header();
jobstats_input_metric("Job Count by Month","jobs_vs_month");
jobstats_input_metric("CPU Time by Month","cpuhours_vs_month");
jobstats_input_metric("Charges by Month","charges_vs_month");
jobstats_input_metric("Job Length by Month","walltime_vs_month");
jobstats_input_metric("Queue Time by Month","qtime_vs_month");
jobstats_input_metric("Backlog by Month","backlog_vs_month");
jobstats_input_metric("Expansion Factor by Month","xfactor_vs_month");
jobstats_input_metric("Active Users by Month","users_vs_month");
jobstats_input_metric("Active Groups by Month","groups_vs_month");
jobstats_input_metric("Active Accounts by Month","accounts_vs_month");
jobstats_input_footer();

end_form();

page_footer();
?>
