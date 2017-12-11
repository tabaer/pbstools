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

// by queue
jobstats_input_header();
jobstats_input_metric("Job Count vs. Job Class","jobs_vs_queue");
jobstats_input_metric("Core Hours vs. Job Class","cpuhours_vs_queue");
jobstats_input_metric("GPU Hours vs. Job Class","gpuhours_vs_queue");
jobstats_input_metric("Node Hours vs. Job Class","nodehours_vs_queue");
jobstats_input_metric("Charges vs. Job Class","charges_vs_queue");
jobstats_input_metric("Job Length vs. Job Class","walltime_vs_queue");
jobstats_input_metric("Queue Time vs. Job Class","qtime_vs_queue");
jobstats_input_metric("Real Memory vs. Job Class","mem_kb_vs_queue");
jobstats_input_metric("Virtual Memory vs. Job Class","vmem_kb_vs_queue");
jobstats_input_metric("Walltime Accuracy vs. Job Class","walltime_acc_vs_queue");
jobstats_input_metric("CPU Efficiency vs. Job Class","cpu_eff_vs_queue");
jobstats_input_metric("Active Users vs. Job Class","users_vs_queue");
jobstats_input_metric("Active Groups vs. Job Class","groups_vs_queue");
jobstats_input_metric("Active Accounts vs. Job Class","accounts_vs_queue");
jobstats_input_metric("Moab Statistics vs. Job Class","moabstats_vs_queue");
jobstats_input_footer();

echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";   

page_footer();
?>
