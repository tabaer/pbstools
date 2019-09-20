<?php
# Copyright 2006, 2019 Ohio Supercomputer Center
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

// by ngpus
jobstats_input_header();
jobstats_input_metric("Job Count vs. GPU Count","jobs_vs_ngpus");
jobstats_input_metric("Core Hours vs. GPU Count","cpuhours_vs_ngpus");
jobstats_input_metric("GPU Hours vs. GPU Count","gpuhours_vs_ngpus");
jobstats_input_metric("Node Hours vs. GPU Count","nodehours_vs_ngpus");
jobstats_input_metric("Charges vs. GPU Count","charges_vs_ngpus");
jobstats_input_metric("Job Length vs. GPU Count","walltime_vs_ngpus");
jobstats_input_metric("Queue Time vs. GPU Count","qtime_vs_ngpus");
jobstats_input_metric("Real Memory vs. GPU Count","mem_kb_vs_ngpus");
jobstats_input_metric("Virtual Memory vs. GPU Count","vmem_kb_vs_ngpus");
jobstats_input_metric("Walltime Accuracy vs. GPU Count","walltime_acc_vs_ngpus");
jobstats_input_metric("CPU Efficiency vs. GPU Count","cpu_eff_vs_ngpus");
jobstats_input_metric("Active Users vs. GPU Count","users_vs_ngpus");
jobstats_input_metric("Active Groups vs. GPU Count","groups_vs_ngpus");
jobstats_input_metric("Active Accounts vs. GPU Count","accounts_vs_ngpus");
jobstats_input_footer();

end_form();

page_footer();
?>
