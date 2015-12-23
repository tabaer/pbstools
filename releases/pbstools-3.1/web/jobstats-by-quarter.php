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

// by quarter
jobstats_input_header();
jobstats_input_metric("Job Count by Quarter","jobs_vs_quarter");
jobstats_input_metric("CPU Time by Quarter","cpuhours_vs_quarter");
jobstats_input_metric("Charges by Quarter","charges_vs_quarter");
jobstats_input_metric("Job Length by Quarter","walltime_vs_quarter");
jobstats_input_metric("Queue Time by Quarter","qtime_vs_quarter");
jobstats_input_metric("Backlog by Quarter","backlog_vs_quarter");
jobstats_input_metric("Expansion Factor by Quarter","xfactor_vs_quarter");
jobstats_input_metric("Active Users by Quarter","users_vs_quarter");
jobstats_input_metric("Active Groups by Quarter","groups_vs_quarter");
jobstats_input_metric("Active Accounts by Quarter","accounts_vs_quarter");
jobstats_input_footer();

end_form();

page_footer();
?>
