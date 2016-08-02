<?php
# Copyright 2006, 2007 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
require_once 'page-layout.php';
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


if ( isset($_POST['system']) )
  {
    $db = db_connect();

    jobstats_summary($db,$_POST['system'],$_POST['start_date'],$_POST['end_date']);

    // by CPU count
    jobstats_output_metric('Job Count vs. CPU Count',
			   'jobcount_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('CPU Time vs. CPU Count',
			   'cpuhours_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Job Length vs. CPU Count',
			   'walltime_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Queue Time vs. CPU Count',
			   'qtime_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Real Memory vs. CPU Count',
			   'mem_kb_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Virtual Memory vs. CPU Count',
			   'vmem_kb_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Walltime Accuracy vs. CPU Count',
			   'walltime_acc_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('CPU Efficiency vs. CPU Count',
			   'cpu_eff_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Active Users vs. CPU Count',
			   'users_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Active Groups/Projects vs. CPU Count',
			   'groups_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    // by queue
    jobstats_output_metric('Job Count vs. Job Class',
			   'jobcount_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('CPU Time vs. Job Class',
			   'cpuhours_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Job Length vs. Job Class',
			   'walltime_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Queue Time vs. Job Class',
			   'qtime_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Real Memory vs. Job Class',
			   'mem_kb_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Virtual Memory vs. Job Class',
			   'vmem_kb_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Walltime Accuracy vs. Job Class',
			   'walltime_acc_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('CPU Efficiency vs. Job Class',
			   'cpu_eff_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Active Users vs. Job Class',
			   'users_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Active Groups/Projects vs. Job Class',
			   'groups_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    // by wallclock time requested
    jobstats_output_bucketed_metric('Job Count vs. Job Length Requested',
				    'jobcount_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('CPU Time vs. Job Length Requested',
				    'cpuhours_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('Queue Time vs. Job Length Requested',
				    'qtime_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);

   jobstats_output_bucketed_metric('Job Length vs. Job Length Requested',
				    'walltime_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
   jobstats_output_bucketed_metric('Expansion Factor vs. Job Length Requested',
				    'xfactor_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);

    jobstats_output_bucketed_metric('Real Memory vs. Job Length Requested',
				    'mem_kb_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('Virtual Memory vs. Job Length Requested',
				    'vmem_kb_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_metric('Walltime Accuracy vs. Job Length Requested',
				    'walltime_acc_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_metric('CPU Efficiency vs. Job Length Requested',
				    'cpu_eff_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);

    // by wallclock time
    jobstats_output_bucketed_metric('Job Count vs. Job Length',
				    'jobcount_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('CPU Time vs. Job Length',
				    'cpuhours_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('Queue Time vs. Job Length',
				    'qtime_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
   jobstats_output_bucketed_metric('Job Length vs. Job Length',
				    'walltime_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
   jobstats_output_bucketed_metric('Expansion Factor vs. Job Length',
				    'xfactor_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('Real Memory vs. Job Length',
				    'mem_kb_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('Virtual Memory vs. Job Length',
				    'vmem_kb_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('Walltime Accuracy vs. Job Length',
				    'walltime_acc_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    jobstats_output_bucketed_metric('CPU Efficiency vs. Job Length',
				    'cpu_eff_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);
    
    // by groupname
    jobstats_output_metric('Job Count vs. Group/Project',
			   'jobcount_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('CPU Time vs. Group/Project',
			   'cpuhours_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Job Length vs. Group/Project',
			   'walltime_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Queue Time vs. Group/Project',
			   'qtime_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Real Memory vs. Group/Project',
			   'mem_kb_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Virtual Memory vs. Group/Project',
			   'vmem_kb_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Walltime Accuracy vs. Group/Project',
			   'walltime_acc_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('CPU Efficiency vs. Group/Project',
			   'cpu_eff_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Active Users vs. Group/Project',
			   'users_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    jobstats_output_metric('Processor Count vs. Group/Project',
			   'nproc_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);
    
    // by username
    jobstats_output_metric('Job Count vs. User',
			   'jobcount_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('CPU Time vs. User',
			   'cpuhours_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Job Length vs. User',
			   'walltime_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Queue Time vs. User',
			   'qtime_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Real Memory vs. User',
			   'mem_kb_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Virtual Memory vs. User',
			   'vmem_kb_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Walltime Accuracy vs. User',
			   'walltime_acc_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('CPU Efficiency vs. User',
			   'cpu_eff_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    // by month
    jobstats_output_metric('Job Count vs. Month',
			   'jobcount_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('CPU Time vs. Month',
			   'cpuhours_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Job Length vs. Month',
			   'walltime_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Queue Time by Month',
			   'qtime_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Backlog by Month',
			   'backlog_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric("Expansion Factor by Month",
			   "xfactor_vs_month",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric("Active Users by Month",
			   "users_vs_month",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric("Active Groups/Projects by Month",
			   "groups_vs_month",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    // by institution
    jobstats_output_metric('Job Count vs. Institution',
			   'jobcount_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('CPU Time vs. Institution',
			   'cpuhours_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Active Users vs. Institution',
			   'users_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    jobstats_output_metric('Active Groups/Projects vs. Institution',
			   'groups_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date']);

    // custom wackiness
    jobstats_output_bucketed_metric('DoD Metrics vs. Processor Count',
				    'dodmetrics_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date']);

    db_disconnect($db);
  }
else
  {
    begin_form("jobstats.php");

    virtual_system_chooser();
    date_fields();
    jobstats_input_header();
    
    // by nproc
    jobstats_input_metric("Job Count vs. CPU Count","jobcount_vs_nproc");
    jobstats_input_metric("CPU Time vs. CPU Count","cpuhours_vs_nproc");
    jobstats_input_metric("Job Length vs. CPU Count","walltime_vs_nproc");
    jobstats_input_metric("Queue Time vs. CPU Count","qtime_vs_nproc");
    jobstats_input_metric("Real Memory vs. CPU Count","mem_kb_vs_nproc");
    jobstats_input_metric("Virtual Memory vs. CPU Count","vmem_kb_vs_nproc");
    jobstats_input_metric("Walltime Accuracy vs. CPU Count","walltime_acc_vs_nproc");
    jobstats_input_metric("CPU Efficiency vs. CPU Count","cpu_eff_vs_nproc");
    jobstats_input_metric("Active Users vs. CPU Count","users_vs_nproc");
    jobstats_input_metric("Active Groups/Projects vs. CPU Count","groups_vs_nproc");
    
    // by queue
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Job Class","jobcount_vs_queue");
    jobstats_input_metric("CPU Time vs. Job Class","cpuhours_vs_queue");
    jobstats_input_metric("Job Length vs. Job Class","walltime_vs_queue");
    jobstats_input_metric("Queue Time vs. Job Class","qtime_vs_queue");
    jobstats_input_metric("Real Memory vs. Job Class","mem_kb_vs_queue");
    jobstats_input_metric("Virtual Memory vs. Job Class","vmem_kb_vs_queue");
    jobstats_input_metric("Walltime Accuracy vs. Job Class","walltime_acc_vs_queue");
    jobstats_input_metric("CPU Efficiency vs. Job Class","cpu_eff_vs_queue");
    jobstats_input_metric("Active Users vs. Job Class","users_vs_queue");
    jobstats_input_metric("Active Groups/Projects vs. Job Class","groups_vs_queue");

    // by walltime_req
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Job Length Requested","jobcount_vs_walltime_req");
    jobstats_input_metric("CPU Time vs. Job Length Requested","cpuhours_vs_walltime_req");
    jobstats_input_metric("Queue Time vs. Job Length Requested","qtime_vs_walltime_req");
    jobstats_input_metric("Job Length vs. Job Length Requested","walltime_vs_walltime_req");
    jobstats_input_metric("Real Memory vs. Job Length Requested","mem_kb_vs_walltime_req");
    jobstats_input_metric("Virtual Memory vs. Job Length Requested","vmem_kb_vs_walltime_req");
    jobstats_input_metric("Walltime Accuracy vs. Job Length Requested","walltime_acc_vs_walltime_req");
    jobstats_input_metric("CPU Efficiency vs. Job Length Requested","cpu_eff_vs_walltime_req");

    // by walltime
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Job Length","jobcount_vs_walltime");
    jobstats_input_metric("CPU Time vs. Job Length","cpuhours_vs_walltime");
    jobstats_input_metric("Queue Time vs. Job Length","qtime_vs_walltime");
    jobstats_input_metric("Real Memory vs. Job Length","mem_kb_vs_walltime");
    jobstats_input_metric("Virtual Memory vs. Job Length","vmem_kb_vs_walltime");
    jobstats_input_metric("Walltime Accuracy vs. Job Length","walltime_acc_vs_walltime");
    jobstats_input_metric("CPU Efficiency vs. Job Length","cpu_eff_vs_walltime");

    // by groupname
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Group/Project","jobcount_vs_groupname");
    jobstats_input_metric("CPU Time vs. Group/Project","cpuhours_vs_groupname");
    jobstats_input_metric("Job Length vs. Group/Project","walltime_vs_groupname");
    jobstats_input_metric("Queue Time vs. Group/Project","qtime_vs_groupname");
    jobstats_input_metric("Real Memory vs. Group/Project","mem_kb_vs_groupname");
    jobstats_input_metric("Virtual Memory vs. Group/Project","vmem_kb_vs_groupname");
    jobstats_input_metric("Walltime Accuracy vs. Group/Project","walltime_acc_vs_groupname");
    jobstats_input_metric("CPU Efficiency vs. Group/Project","cpu_eff_vs_groupname");
    jobstats_input_metric("Active Users vs. Group/Project","users_vs_groupname");
    jobstats_input_metric("Processor Count vs. Group/Project","nproc_vs_groupname");

    // by username
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. User","jobcount_vs_username");
    jobstats_input_metric("CPU Time vs. User","cpuhours_vs_username");
    jobstats_input_metric("Job Length vs. User","walltime_vs_username");
    jobstats_input_metric("Queue Time vs. User","qtime_vs_username");
    jobstats_input_metric("Real Memory vs. User","mem_kb_vs_username");
    jobstats_input_metric("Virtual Memory vs. User","vmem_kb_vs_username");
    jobstats_input_metric("Walltime Accuracy vs. User","walltime_acc_vs_username");
    jobstats_input_metric("CPU Efficiency vs. User","cpu_eff_vs_username");

    // by month
    jobstats_input_spacer();
    jobstats_input_metric("Job Count by Month","jobcount_vs_month");
    jobstats_input_metric("CPU Time by Month","cpuhours_vs_month");
    jobstats_input_metric("Job Length by Month","walltime_vs_month");
    jobstats_input_metric("Queue Time by Month","qtime_vs_month");
    jobstats_input_metric("Backlog by Month","backlog_vs_month");
    jobstats_input_metric("Expansion Factor by Month","xfactor_vs_month");
    jobstats_input_metric("Active Users by Month","users_vs_month");
    jobstats_input_metric("Active Groups/Projects by Month","groups_vs_month");
    
    // by institution
    jobstats_input_spacer();
    jobstats_input_metric("Job Count by Institution","jobcount_vs_institution");
    jobstats_input_metric("CPU Time by Institution","cpuhours_vs_institution");
    jobstats_input_metric("Active Users by Institution","users_vs_institution");
    jobstats_input_metric("Active Groups/Projects by Institution","groups_vs_institution");

    // custom wackiness
    jobstats_input_spacer();
    jobstats_input_metric("DoD Metrics vs. Processor Count","dodmetrics_vs_nproc_bucketed");

    jobstats_input_footer();

    end_form();
  }

page_footer();
?>