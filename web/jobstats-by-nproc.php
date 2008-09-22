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

// by nproc
jobstats_input_header();
jobstats_input_metric("Job Count vs. CPU Count","jobcount_vs_nproc");
jobstats_input_metric("CPU Time vs. CPU Count","cpuhours_vs_nproc");
jobstats_input_metric("Job Length vs. CPU Count","walltime_vs_nproc");
jobstats_input_metric("Queue Time vs. CPU Count","qtime_vs_nproc");
jobstats_input_metric("Real Memory vs. CPU Count","mem_kb_vs_nproc");
jobstats_input_metric("Virtual Memory vs. CPU Count","vmem_kb_vs_nproc");
jobstats_input_metric("Walltime Accuracy vs. CPU Count","walltime_acc_vs_nproc");
jobstats_input_metric("CPU Efficiency vs. CPU Count","cpu_eff_vs_nproc");
jobstats_input_metric("Active Users vs. CPU Count","users_vs_nproc");
jobstats_input_metric("Active Groups vs. CPU Count","groups_vs_nproc");
jobstats_input_metric("Active Accounts vs. CPU Count","accounts_vs_nproc");
jobstats_input_footer();

end_form();

page_footer();
?>