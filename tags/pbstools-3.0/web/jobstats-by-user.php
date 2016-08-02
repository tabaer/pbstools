<?php
# Copyright 2006 Ohio Supercomputer Center
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

// by username
jobstats_input_header();
jobstats_input_metric("Job Count vs. User","jobs_vs_username");
jobstats_input_metric("CPU Time vs. User","cpuhours_vs_username");
jobstats_input_metric("Job Length vs. User","walltime_vs_username");
jobstats_input_metric("Queue Time vs. User","qtime_vs_username");
jobstats_input_metric("Real Memory vs. User","mem_kb_vs_username");
jobstats_input_metric("Virtual Memory vs. User","vmem_kb_vs_username");
jobstats_input_metric("Walltime Accuracy vs. User","walltime_acc_vs_username");
jobstats_input_metric("CPU Efficiency vs. User","cpu_eff_vs_username");
jobstats_input_footer();

end_form();

page_footer();
?>