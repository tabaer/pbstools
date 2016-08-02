<?php
# Copyright 2006 Ohio Supercomputer Center
# Copyright 2011-2013 University of Tennessee
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

// by qos
jobstats_input_header();
jobstats_input_metric("Job Count vs. QOS","jobs_vs_qos");
jobstats_input_metric("CPU Time vs. QOS","cpuhours_vs_qos");
jobstats_input_metric("Job Length vs. QOS","walltime_vs_qos");
jobstats_input_metric("Queue Time vs. QOS","qtime_vs_qos");
jobstats_input_metric("Real Memory vs. QOS","mem_kb_vs_qos");
jobstats_input_metric("Virtual Memory vs. QOS","vmem_kb_vs_qos");
jobstats_input_metric("Walltime Accuracy vs. QOS","walltime_acc_vs_qos");
jobstats_input_metric("CPU Efficiency vs. QOS","cpu_eff_vs_qos");
jobstats_input_footer();

end_form();

page_footer();
?>
