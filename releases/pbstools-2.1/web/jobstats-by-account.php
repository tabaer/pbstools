<?php
# Copyright 2006, 2007 Ohio Supercomputer Center
# Copyright 2009 University of Tennessee
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

// by account
jobstats_input_header();
jobstats_input_metric("Job Count vs. Account","jobs_vs_account");
jobstats_input_metric("CPU Time vs. Account","cpuhours_vs_account");
jobstats_input_metric("Job Length vs. Account","walltime_vs_account");
jobstats_input_metric("Queue Time vs. Account","qtime_vs_account");
jobstats_input_metric("Real Memory vs. Account","mem_kb_vs_account");
jobstats_input_metric("Virtual Memory vs. Account","vmem_kb_vs_account");
jobstats_input_metric("Walltime Accuracy vs. Account","walltime_acc_vs_account");
jobstats_input_metric("CPU Efficiency vs. Account","cpu_eff_vs_account");
jobstats_input_metric("Active Users vs. Account","users_vs_account");
jobstats_input_metric("Processor Count vs. Account","nproc_vs_account");
jobstats_input_footer();

end_form();

page_footer();
?>
