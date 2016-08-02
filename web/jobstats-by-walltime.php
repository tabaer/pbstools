
<?php
# Copyright 2006, 2007 Ohio Supercomputer Center
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/jobstats-by-walltime.php $
# $Revision: 144 $
# $Date: 2006-08-04 14:47:32 -0400 (Fri, 04 Aug 2006) $
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

jobstats_input_header();

// by walltime_req
jobstats_input_metric("Job Count vs. Job Length Requested","jobcount_vs_walltime_req");
jobstats_input_metric("CPU Time vs. Job Length Requested","cpuhours_vs_walltime_req");
jobstats_input_metric("Queue Time vs. Job Length Requested","qtime_vs_walltime_req");
jobstats_input_metric("Job Length vs. Job Length Requested","walltime_vs_walltime_req");
jobstats_input_metric("Expansion Factor vs. Job Length Requested","xfactor_vs_walltime_req");
jobstats_input_metric("Real Memory vs. Job Length Requested","mem_kb_vs_walltime_req");
jobstats_input_metric("Virtual Memory vs. Job Length Requested","vmem_kb_vs_walltime_req");
jobstats_input_metric("Walltime Accuracy vs. Job Length Requested","walltime_acc_vs_walltime_req");
jobstats_input_metric("CPU Efficiency vs. Job Length Requested","cpu_eff_vs_walltime_req");

// by walltime
jobstats_input_spacer();
jobstats_input_metric("Job Count vs. Job Length","jobcount_vs_walltime");
jobstats_input_metric("CPU Time vs. Job Length","cpuhours_vs_walltime");
jobstats_input_metric("Queue Time vs. Job Length","qtime_vs_walltime");
jobstats_input_metric("Job Length vs. Job Length","walltime_vs_walltime");
jobstats_input_metric("Expansion Factor vs. Job Length","xfactor_vs_walltime");
jobstats_input_metric("Real Memory vs. Job Length","mem_kb_vs_walltime");
jobstats_input_metric("Virtual Memory vs. Job Length","vmem_kb_vs_walltime");
jobstats_input_metric("Walltime Accuracy vs. Job Length","walltime_acc_vs_walltime");
jobstats_input_metric("CPU Efficiency vs. Job Length","cpu_eff_vs_walltime");

jobstats_input_footer();

end_form();

page_footer();
?>