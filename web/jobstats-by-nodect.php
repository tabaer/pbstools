<?php
# Copyright 2006 Ohio Supercomputer Center
# Copyright 2008, 2011 University of Tennessee
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

// by nodect
jobstats_input_header();
jobstats_input_metric("Job Count vs. Node Count","jobs_vs_nodect");
jobstats_input_metric("Core Hours vs. Node Count","cpuhours_vs_nodect");
jobstats_input_metric("Node Hours vs. Node Count","nodehours_vs_nodect");
jobstats_input_metric("Charges vs. Node Count","charges_vs_nodect");
jobstats_input_metric("Job Length vs. Node Count","walltime_vs_nodect");
jobstats_input_metric("Queue Time vs. Node Count","qtime_vs_nodect");
jobstats_input_metric("Real Memory vs. Node Count","mem_kb_vs_nodect");
jobstats_input_metric("Virtual Memory vs. Node Count","vmem_kb_vs_nodect");
jobstats_input_metric("Walltime Accuracy vs. Node Count","walltime_acc_vs_nodect");
jobstats_input_metric("CPU Efficiency vs. Node Count","cpu_eff_vs_nodect");
jobstats_input_metric("Active Users vs. Node Count","users_vs_nodect");
jobstats_input_metric("Active Groups vs. Node Count","groups_vs_nodect");
jobstats_input_metric("Active Accounts vs. Node Count","accounts_vs_nodect");
jobstats_input_footer();

end_form();

page_footer();
?>
