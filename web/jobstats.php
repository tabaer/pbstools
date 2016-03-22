<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
# Copyright 2008, 2009, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
require_once 'page-layout.php';
require_once 'metrics.php';
require_once 'site-specific.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

$title = "Job statistics";
if ( isset($_POST['system']) )
  {
    $title .= " for ".$_POST['system'];
    $verb = title_verb($_POST['datelogic']);
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) &&
	 $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$title .= " ".$verb." on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	$title .= " ".$verb." between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	$title .= " ".$verb." after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	$title .= " ".$verb." before ".$_POST['end_date'];
      }
  }
page_header($title);


if ( isset($_POST['system']) )
  {
    $db = db_connect();

    jobstats_summary($db,$_POST['system'],$_POST['start_date'],$_POST['end_date'],$_POST['datelogic']);

    // by CPU count
    jobstats_output_metric('Job Count vs. CPU Count',
			   'jobs_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Time vs. CPU Count',
			   'cpuhours_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Charges vs. CPU Count',
			   'charges_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Job Length vs. CPU Count',
			   'walltime_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Queue Time vs. CPU Count',
			   'qtime_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Real Memory vs. CPU Count',
			   'mem_kb_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Virtual Memory vs. CPU Count',
			   'vmem_kb_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Walltime Accuracy vs. CPU Count',
			   'walltime_acc_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Efficiency vs. CPU Count',
			   'cpu_eff_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Users vs. CPU Count',
			   'users_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Groups vs. CPU Count',
			   'groups_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Active Accounts vs. CPU Count',
			   'accounts_vs_nproc',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by node count
    jobstats_output_metric('Job Count vs. Node Count',
			   'jobs_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Time vs. Node Count',
			   'cpuhours_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Charges vs. Node Count',
			   'charges_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Job Length vs. Node Count',
			   'walltime_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Queue Time vs. Node Count',
			   'qtime_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Real Memory vs. Node Count',
			   'mem_kb_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Virtual Memory vs. Node Count',
			   'vmem_kb_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Walltime Accuracy vs. Node Count',
			   'walltime_acc_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Efficiency vs. Node Count',
			   'cpu_eff_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Users vs. Node Count',
			   'users_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Groups vs. Node Count',
			   'groups_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Active Accounts vs. Node Count',
			   'accounts_vs_nodect',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by queue
    jobstats_output_metric('Job Count vs. Job Class',
			   'jobs_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Time vs. Job Class',
			   'cpuhours_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Charges vs. Job Class',
			   'charges_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Job Length vs. Job Class',
			   'walltime_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Queue Time vs. Job Class',
			   'qtime_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Expansion Factor vs. Job Class',
			   'xfactor_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Real Memory vs. Job Class',
			   'mem_kb_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Virtual Memory vs. Job Class',
			   'vmem_kb_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Walltime Accuracy vs. Job Class',
			   'walltime_acc_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Efficiency vs. Job Class',
			   'cpu_eff_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Active Users vs. Job Class',
			   'users_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Active Groups vs. Job Class',
			   'groups_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Accounts vs. Job Class',
			   'accounts_vs_queue',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by wallclock time requested
    jobstats_output_bucketed_metric('Job Count vs. Job Length Requested',
				    'jobs_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Time vs. Job Length Requested',
				    'cpuhours_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Charges vs. Job Length Requested',
				    'charges_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Queue Time vs. Job Length Requested',
				    'qtime_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('Job Length vs. Job Length Requested',
				    'walltime_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Expansion Factor vs. Job Length Requested',
				    'xfactor_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('Real Memory vs. Job Length Requested',
				    'mem_kb_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Virtual Memory vs. Job Length Requested',
				    'vmem_kb_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Walltime Accuracy vs. Job Length Requested',
				    'walltime_acc_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Efficiency vs. Job Length Requested',
				    'cpu_eff_vs_walltime_req',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    // by wallclock time
    jobstats_output_bucketed_metric('Job Count vs. Job Length',
				    'jobs_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Time vs. Job Length',
				    'cpuhours_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Charges vs. Job Length',
				    'charges_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Queue Time vs. Job Length',
				    'qtime_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Job Length vs. Job Length',
				    'walltime_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Expansion Factor vs. Job Length',
				    'xfactor_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Real Memory vs. Job Length',
				    'mem_kb_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Virtual Memory vs. Job Length',
				    'vmem_kb_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Walltime Accuracy vs. Job Length',
				    'walltime_acc_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Efficiency vs. Job Length',
				    'cpu_eff_vs_walltime',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    // by account
    jobstats_output_metric('Job Count vs. Account',
			   'jobs_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Time vs. Account',
			   'cpuhours_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Charges vs. Account',
			   'charges_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Job Length vs. Account',
			   'walltime_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Queue Time vs. Account',
			   'qtime_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Real Memory vs. Account',
			   'mem_kb_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Virtual Memory vs. Account',
			   'vmem_kb_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Walltime Accuracy vs. Account',
			   'walltime_acc_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Efficiency vs. Account',
			   'cpu_eff_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Users vs. Account',
			   'users_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Processor Count vs. Account',
			   'nproc_vs_account',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    // by groupname
    jobstats_output_metric('Job Count vs. Group',
			   'jobs_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Time vs. Group',
			   'cpuhours_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Charges vs. Group',
			   'charges_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Job Length vs. Group',
			   'walltime_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Queue Time vs. Group',
			   'qtime_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Real Memory vs. Group',
			   'mem_kb_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Virtual Memory vs. Group',
			   'vmem_kb_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Walltime Accuracy vs. Group',
			   'walltime_acc_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('CPU Efficiency vs. Group',
			   'cpu_eff_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Users vs. Group',
			   'users_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Active Accounts vs. Group',
			   'accounts_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    jobstats_output_metric('Processor Count vs. Group',
			   'nproc_vs_groupname',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);
    
    // by username
    jobstats_output_metric('Job Count vs. User',
			   'jobs_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Time vs. User',
			   'cpuhours_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Charges vs. User',
			   'charges_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Job Length vs. User',
			   'walltime_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Queue Time vs. User',
			   'qtime_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Real Memory vs. User',
			   'mem_kb_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Virtual Memory vs. User',
			   'vmem_kb_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Walltime Accuracy vs. User',
			   'walltime_acc_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Efficiency vs. User',
			   'cpu_eff_vs_username',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by QOS
    jobstats_output_metric('Job Count vs. QOS',
			   'jobs_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Time vs. QOS',
			   'cpuhours_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Charges vs. QOS',
			   'charges_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Job Length vs. QOS',
			   'walltime_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Queue Time vs. QOS',
			   'qtime_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Real Memory vs. QOS',
			   'mem_kb_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Virtual Memory vs. QOS',
			   'vmem_kb_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Walltime Accuracy vs. QOS',
			   'walltime_acc_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Efficiency vs. QOS',
			   'cpu_eff_vs_qos',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by quarter
    jobstats_output_metric('Job Count vs. Quarter',
			   'jobs_vs_quarter',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Time vs. Quarter',
			   'cpuhours_vs_quarter',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Charges vs. Quarter',
			   'charges_vs_quarter',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Job Length vs. Quarter',
			   'walltime_vs_quarter',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Queue Time by Quarter',
			   'qtime_vs_quarter',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Backlog by Quarter',
			   'backlog_vs_quarter',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Expansion Factor by Quarter",
			   "xfactor_vs_quarter",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Users by Quarter",
			   "users_vs_quarter",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Groups by Quarter",
			   "groups_vs_quarter",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Accounts by Quarter",
			   "accounts_vs_quarter",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by month
    jobstats_output_metric('Job Count vs. Month',
			   'jobs_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Time vs. Month',
			   'cpuhours_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Charges vs. Month',
			   'charges_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Job Length vs. Month',
			   'walltime_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Queue Time by Month',
			   'qtime_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Backlog by Month',
			   'backlog_vs_month',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Expansion Factor by Month",
			   "xfactor_vs_month",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Users by Month",
			   "users_vs_month",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Groups by Month",
			   "groups_vs_month",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Accounts by Month",
			   "accounts_vs_month",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by week
    jobstats_output_metric('Job Count vs. Week',
			   'jobs_vs_week',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Time vs. Week',
			   'cpuhours_vs_week',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Charges vs. Week',
			   'charges_vs_week',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Job Length vs. Week',
			   'walltime_vs_week',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Queue Time by Week',
			   'qtime_vs_week',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Backlog by Week',
			   'backlog_vs_week',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Expansion Factor by Week",
			   "xfactor_vs_week",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Users by Week",
			   "users_vs_week",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Groups by Week",
			   "groups_vs_week",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric("Active Accounts by Week",
			   "accounts_vs_week",
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // by institution
    jobstats_output_metric('Job Count vs. Institution',
			   'jobs_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('CPU Time vs. Institution',
			   'cpuhours_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Charges vs. Institution',
			   'charges_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Users vs. Institution',
			   'users_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Groups vs. Institution',
			   'groups_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    jobstats_output_metric('Active Accounts vs. Institution',
			   'accounts_vs_institution',
			   $db,
			   $_POST['system'],
			   $_POST['start_date'],
			   $_POST['end_date'],
			   $_POST['datelogic']);

    // custom wackiness
    jobstats_output_bucketed_metric('Job Count vs. Processor Count',
				    'jobs_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Time vs. Processor Count',
				    'cpuhours_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Charges vs. Processor Count',
				    'charges_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Job Length vs. Processor Count',
				    'walltime_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Queue Time vs. Processor Count',
				    'qtime_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Real Memory vs. Processor Count',
				    'mem_kb_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Virtual Memory vs. Processor Count',
				    'vmem_kb_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Walltime Accuracy vs. Processor Count',
				    'walltime_acc_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Efficiency vs. Processor Count',
				    'cpu_eff_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Active Users vs. Processor Count',
				    'users_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Active Groups vs. Processor Count',
				    'groups_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Active Accounts vs. Processor Count',
				    'accounts_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('Expansion Factor vs. Processor Count',
				    'xfactor_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('DoD Metrics vs. Processor Count',
				    'dodmetrics_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('PSC Metrics vs. Processor Count',
				    'pscmetrics_vs_nproc_bucketed',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('Job Count vs. Normalized Processor Count',
				    'jobs_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Time vs. Normalized Processor Count',
				    'cpuhours_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Job Length vs. Normalized Processor Count',
				    'walltime_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Queue Time vs. Normalized Processor Count',
				    'qtime_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Real Memory vs. Normalized Processor Count',
				    'mem_kb_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Virtual Memory vs. Normalized Processor Count',
				    'vmem_kb_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Walltime Accuracy vs. Normalized Processor Count',
				    'walltime_acc_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('CPU Efficiency vs. Normalized Processor Count',
				    'cpu_eff_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Active Users vs. Normalized Processor Count',
				    'users_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Active Groups vs. Normalized Processor Count',
				    'groups_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);
    
    jobstats_output_bucketed_metric('Active Accounts vs. Normalized Processor Count',
				    'accounts_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('Expansion Factor vs. Normalized Processor Count',
				    'xfactor_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    jobstats_output_bucketed_metric('DoD Metrics vs. Normalized Processor Count',
				    'dodmetrics_vs_nproc_norm',
				    $db,
				    $_POST['system'],
				    $_POST['start_date'],
				    $_POST['end_date'],
				    $_POST['datelogic']);

    db_disconnect($db);
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("jobstats.php");

    virtual_system_chooser();
    date_fields();
    jobstats_input_header();
    
    // by nproc
    jobstats_input_metric("Job Count vs. CPU Count","jobs_vs_nproc");
    jobstats_input_metric("CPU Time vs. CPU Count","cpuhours_vs_nproc");
    jobstats_input_metric("Charges vs. CPU Count","charges_vs_nproc");
    jobstats_input_metric("Job Length vs. CPU Count","walltime_vs_nproc");
    jobstats_input_metric("Queue Time vs. CPU Count","qtime_vs_nproc");
    jobstats_input_metric("Real Memory vs. CPU Count","mem_kb_vs_nproc");
    jobstats_input_metric("Virtual Memory vs. CPU Count","vmem_kb_vs_nproc");
    jobstats_input_metric("Walltime Accuracy vs. CPU Count","walltime_acc_vs_nproc");
    jobstats_input_metric("CPU Efficiency vs. CPU Count","cpu_eff_vs_nproc");
    jobstats_input_metric("Active Users vs. CPU Count","users_vs_nproc");
    jobstats_input_metric("Active Groups vs. CPU Count","groups_vs_nproc");
    jobstats_input_metric("Active Accounts vs. CPU Count","accounts_vs_nproc");
    
    // by nodect
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Node Count","jobs_vs_nodect");
    jobstats_input_metric("CPU Time vs. Node Count","cpuhours_vs_nodect");
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
    
    // by queue
    jobstats_input_metric("Job Count vs. Job Class","jobs_vs_queue");
    jobstats_input_metric("CPU Time vs. Job Class","cpuhours_vs_queue");
    jobstats_input_metric("Charges vs. Job Class","charges_vs_queue");
    jobstats_input_metric("Job Length vs. Job Class","walltime_vs_queue");
    jobstats_input_metric("Queue Time vs. Job Class","qtime_vs_queue");
    jobstats_input_metric("Expansion Factor vs. Job Class","xfactor_vs_queue");
    jobstats_input_metric("Real Memory vs. Job Class","mem_kb_vs_queue");
    jobstats_input_metric("Virtual Memory vs. Job Class","vmem_kb_vs_queue");
    jobstats_input_metric("Walltime Accuracy vs. Job Class","walltime_acc_vs_queue");
    jobstats_input_metric("CPU Efficiency vs. Job Class","cpu_eff_vs_queue");
    jobstats_input_metric("Active Users vs. Job Class","users_vs_queue");
    jobstats_input_metric("Active Groups vs. Job Class","groups_vs_queue");
    jobstats_input_metric("Active Accounts vs. Job Class","accounts_vs_queue");

    // by walltime_req
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Job Length Requested","jobs_vs_walltime_req");
    jobstats_input_metric("CPU Time vs. Job Length Requested","cpuhours_vs_walltime_req");
    jobstats_input_metric("Charges vs. Job Length Requested","charges_vs_walltime_req");
    jobstats_input_metric("Queue Time vs. Job Length Requested","qtime_vs_walltime_req");
    jobstats_input_metric("Job Length vs. Job Length Requested","walltime_vs_walltime_req");
    jobstats_input_metric("Real Memory vs. Job Length Requested","mem_kb_vs_walltime_req");
    jobstats_input_metric("Virtual Memory vs. Job Length Requested","vmem_kb_vs_walltime_req");
    jobstats_input_metric("Walltime Accuracy vs. Job Length Requested","walltime_acc_vs_walltime_req");
    jobstats_input_metric("CPU Efficiency vs. Job Length Requested","cpu_eff_vs_walltime_req");

    // by walltime
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Job Length","jobs_vs_walltime");
    jobstats_input_metric("CPU Time vs. Job Length","cpuhours_vs_walltime");
    jobstats_input_metric("Charges vs. Job Length","charges_vs_walltime");
    jobstats_input_metric("Queue Time vs. Job Length","qtime_vs_walltime");
    jobstats_input_metric("Real Memory vs. Job Length","mem_kb_vs_walltime");
    jobstats_input_metric("Virtual Memory vs. Job Length","vmem_kb_vs_walltime");
    jobstats_input_metric("Walltime Accuracy vs. Job Length","walltime_acc_vs_walltime");
    jobstats_input_metric("CPU Efficiency vs. Job Length","cpu_eff_vs_walltime");

    // by account
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Account","jobs_vs_account");
    jobstats_input_metric("CPU Time vs. Account","cpuhours_vs_account");
    jobstats_input_metric("Charges vs. Account","charges_vs_account");
    jobstats_input_metric("Job Length vs. Account","walltime_vs_account");
    jobstats_input_metric("Queue Time vs. Account","qtime_vs_account");
    jobstats_input_metric("Real Memory vs. Account","mem_kb_vs_account");
    jobstats_input_metric("Virtual Memory vs. Account","vmem_kb_vs_account");
    jobstats_input_metric("Walltime Accuracy vs. Account","walltime_acc_vs_account");
    jobstats_input_metric("CPU Efficiency vs. Account","cpu_eff_vs_account");
    jobstats_input_metric("Active Users vs. Account","users_vs_account");
    jobstats_input_metric("Processor Count vs. Account","nproc_vs_account");

    // by groupname
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Group","jobs_vs_groupname");
    jobstats_input_metric("CPU Time vs. Group","cpuhours_vs_groupname");
    jobstats_input_metric("Charges vs. Group","charges_vs_groupname");
    jobstats_input_metric("Job Length vs. Group","walltime_vs_groupname");
    jobstats_input_metric("Queue Time vs. Group","qtime_vs_groupname");
    jobstats_input_metric("Real Memory vs. Group","mem_kb_vs_groupname");
    jobstats_input_metric("Virtual Memory vs. Group","vmem_kb_vs_groupname");
    jobstats_input_metric("Walltime Accuracy vs. Group","walltime_acc_vs_groupname");
    jobstats_input_metric("CPU Efficiency vs. Group","cpu_eff_vs_groupname");
    jobstats_input_metric("Active Users vs. Group","users_vs_groupname");
    jobstats_input_metric("Active Accounts vs. Group","accounts_vs_groupname");
    jobstats_input_metric("Processor Count vs. Group","nproc_vs_groupname");

    // by username
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. User","jobs_vs_username");
    jobstats_input_metric("CPU Time vs. User","cpuhours_vs_username");
    jobstats_input_metric("Charges vs. User","charges_vs_username");
    jobstats_input_metric("Job Length vs. User","walltime_vs_username");
    jobstats_input_metric("Queue Time vs. User","qtime_vs_username");
    jobstats_input_metric("Real Memory vs. User","mem_kb_vs_username");
    jobstats_input_metric("Virtual Memory vs. User","vmem_kb_vs_username");
    jobstats_input_metric("Walltime Accuracy vs. User","walltime_acc_vs_username");
    jobstats_input_metric("CPU Efficiency vs. User","cpu_eff_vs_username");

    // by QOS
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. QOS","jobs_vs_qos");
    jobstats_input_metric("CPU Time vs. QOS","cpuhours_vs_qos");
    jobstats_input_metric("Charges vs. QOS","charges_vs_qos");
    jobstats_input_metric("Job Length vs. QOS","walltime_vs_qos");
    jobstats_input_metric("Queue Time vs. QOS","qtime_vs_qos");
    jobstats_input_metric("Real Memory vs. QOS","mem_kb_vs_qos");
    jobstats_input_metric("Virtual Memory vs. QOS","vmem_kb_vs_qos");
    jobstats_input_metric("Walltime Accuracy vs. QOS","walltime_acc_vs_qos");
    jobstats_input_metric("CPU Efficiency vs. QOS","cpu_eff_vs_qos");

    // by quarter
    jobstats_input_spacer();
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

    // by month
    jobstats_input_spacer();
    jobstats_input_metric("Job Count by Month","jobs_vs_month");
    jobstats_input_metric("CPU Time by Month","cpuhours_vs_month");
    jobstats_input_metric("Charges by Month","charges_vs_month");
    jobstats_input_metric("Job Length by Month","walltime_vs_month");
    jobstats_input_metric("Queue Time by Month","qtime_vs_month");
    jobstats_input_metric("Backlog by Month","backlog_vs_month");
    jobstats_input_metric("Expansion Factor by Month","xfactor_vs_month");
    jobstats_input_metric("Active Users by Month","users_vs_month");
    jobstats_input_metric("Active Groups by Month","groups_vs_month");
    jobstats_input_metric("Active Accounts by Month","accounts_vs_month");

    // by week
    jobstats_input_spacer();
    jobstats_input_metric("Job Count by Week","jobs_vs_week");
    jobstats_input_metric("CPU Time by Week","cpuhours_vs_week");
    jobstats_input_metric("Charges by Week","charges_vs_week");
    jobstats_input_metric("Job Length by Week","walltime_vs_week");
    jobstats_input_metric("Queue Time by Week","qtime_vs_week");
    jobstats_input_metric("Backlog by Week","backlog_vs_week");
    jobstats_input_metric("Expansion Factor by Week","xfactor_vs_week");
    jobstats_input_metric("Active Users by Week","users_vs_week");
    jobstats_input_metric("Active Groups by Week","groups_vs_week");
    jobstats_input_metric("Active Accounts by Week","accounts_vs_week");
    
    // by institution
    jobstats_input_spacer();
    jobstats_input_metric("Job Count by Institution","jobs_vs_institution");
    jobstats_input_metric("CPU Time by Institution","cpuhours_vs_institution");
    jobstats_input_metric("Charges by Institution","charges_vs_institution");
    jobstats_input_metric("Active Users by Institution","users_vs_institution");
    jobstats_input_metric("Active Groups by Institution","groups_vs_institution");
    jobstats_input_metric("Active Accounts by Institution","accounts_vs_institution");

    // custom wackiness
    jobstats_input_spacer();
    jobstats_input_metric("Job Count vs. Processor Count","jobs_vs_nproc_bucketed");
    jobstats_input_metric("CPU Time vs. Processor Count","cpuhours_vs_nproc_bucketed");
    jobstats_input_metric("Charges vs. Processor Count","charges_vs_nproc_bucketed");
    jobstats_input_metric("Job Length vs. Processor Count","walltime_vs_nproc_bucketed");
    jobstats_input_metric("Queue Time vs. Processor Count","qtime_vs_nproc_bucketed");
    jobstats_input_metric("Real Memory vs. Processor Count","mem_kb_vs_nproc_bucketed");
    jobstats_input_metric("Virtual Memory vs. Processor Count","vmem_kb_vs_nproc_bucketed");
    jobstats_input_metric("Walltime Accuracy vs. Processor Count","walltime_acc_vs_nproc_bucketed");
    jobstats_input_metric("CPU Efficiency vs. Processor Count","cpu_eff_vs_nproc_bucketed");
    jobstats_input_metric("Active Users vs. Processor Count","users_vs_nproc_bucketed");
    jobstats_input_metric("Active Groups vs. Processor Count","groups_vs_nproc_bucketed");
    jobstats_input_metric("Active Accounts vs. Processor Count","accounts_vs_nproc_bucketed");
    jobstats_input_metric("Expansion Factor vs. Processor Count","xfactor_vs_nproc_bucketed");
    jobstats_input_metric("DoD Metrics vs. Processor Count","dodmetrics_vs_nproc_bucketed");
    jobstats_input_metric("PSC Metrics vs. Processor Count","pscmetrics_vs_nproc_bucketed");
    jobstats_input_metric("Job Count vs. Normalized Processor Count","jobs_vs_nproc_norm");
    jobstats_input_metric("CPU Time vs. Normalized Processor Count","cpuhours_vs_nproc_norm");
    jobstats_input_metric("Job Length vs. Normalized Processor Count","walltime_vs_nproc_norm");
    jobstats_input_metric("Queue Time vs. Normalized Processor Count","qtime_vs_nproc_norm");
    jobstats_input_metric("Real Memory vs. Normalized Processor Count","mem_kb_vs_nproc_norm");
    jobstats_input_metric("Virtual Memory vs. Normalized Processor Count","vmem_kb_vs_nproc_norm");
    jobstats_input_metric("Walltime Accuracy vs. Normalized Processor Count","walltime_acc_vs_nproc_norm");
    jobstats_input_metric("CPU Efficiency vs. Normalized Processor Count","cpu_eff_vs_nproc_norm");
    jobstats_input_metric("Active Users vs. Normalized Processor Count","users_vs_nproc_norm");
    jobstats_input_metric("Active Groups vs. Normalized Processor Count","groups_vs_nproc_norm");
    jobstats_input_metric("Active Accounts vs. Normalized Processor Count","accounts_vs_nproc_norm");
    jobstats_input_metric("Expansion Factor vs. Normalized Processor Count","xfactor_vs_nproc_norm");
    jobstats_input_metric("DoD Metrics vs. Normalized Processor Count","dodmetrics_vs_nproc_norm");

    jobstats_input_footer();

    end_form();
  }

page_footer();
?>
