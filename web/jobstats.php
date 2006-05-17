<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'DB.php';
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

$db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
if ( DB::isError($db) )
  {
    die ($db->getMessage());
  }

if ( isset($_POST['system']) )
  {
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
  }
else
  {
    echo "<FORM method=\"POST\" action=\"jobstats.php\">\n";
    echo "System:  <SELECT name=\"system\" size=\"1\">\n";
    echo "<OPTION value=\"%\">Any\n";
    foreach (sys_list() as $host)
      {
	echo "<OPTION>".$host."\n";
      }
    echo "</SELECT><BR>\n";
    echo "Start date: <INPUT type=\"text\" name=\"start_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";
    echo "End date: <INPUT type=\"text\" name=\"end_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";
    echo "<TABLE>\n";
    echo "<TR>\n";
    echo "  <TH>Metrics</TH>\n";
    echo "  <TH>Graph</TH>";
    echo "  <TH>Table</TH>\n";
    echo "</TR>\n";
    
    // by nproc
    jobstats_input_metric("Job Count vs. CPU Count","jobcount_vs_nproc");
    jobstats_input_metric("CPU Time vs. CPU Count","cpuhours_vs_nproc");
    jobstats_input_metric("Job Length vs. CPU Count","walltime_vs_nproc");
    jobstats_input_metric("Queue Time vs. CPU Count","qtime_vs_nproc");
    jobstats_input_metric("Real Memory vs. CPU Count","mem_kb_vs_nproc");
    jobstats_input_metric("Virtual Memory vs. CPU Count","vmem_kb_vs_nproc");
    jobstats_input_metric("Walltime Accuracy vs. CPU Count","walltime_acc_vs_nproc");
    jobstats_input_metric("CPU Efficiency vs. CPU Count","cpu_eff_vs_nproc");
    
    // by queue
    echo "<TR><TH colspan=\"3\"><HR></TH></TR>\n";
    jobstats_input_metric("Job Count vs. Job Class","jobcount_vs_queue");
    jobstats_input_metric("CPU Time vs. Job Class","cpuhours_vs_queue");
    jobstats_input_metric("Job Length vs. Job Class","walltime_vs_queue");
    jobstats_input_metric("Queue Time vs. Job Class","qtime_vs_queue");
    jobstats_input_metric("Real Memory vs. Job Class","mem_kb_vs_queue");
    jobstats_input_metric("Virtual Memory vs. Job Class","vmem_kb_vs_queue");
    jobstats_input_metric("Walltime Accuracy vs. Job Class","walltime_acc_vs_queue");
    jobstats_input_metric("CPU Efficiency vs. Job Class","cpu_eff_vs_queue");

    // by groupname
    echo "<TR><TH colspan=\"3\"><HR></TH></TR>\n";
    jobstats_input_metric("Job Count vs. Group/Project","jobcount_vs_groupname");
    jobstats_input_metric("CPU Time vs. Group/Project","cpuhours_vs_groupname");
    jobstats_input_metric("Job Length vs. Group/Project","walltime_vs_groupname");
    jobstats_input_metric("Queue Time vs. Group/Project","qtime_vs_groupname");
    jobstats_input_metric("Real Memory vs. Group/Project","mem_kb_vs_groupname");
    jobstats_input_metric("Virtual Memory vs. Group/Project","vmem_kb_vs_groupname");
    jobstats_input_metric("Walltime Accuracy vs. Group/Project","walltime_acc_vs_groupname");
    jobstats_input_metric("CPU Efficiency vs. Group/Project","cpu_eff_vs_groupname");

    // by username
    echo "<TR><TH colspan=\"3\"><HR></TH></TR>\n";
    jobstats_input_metric("Job Count vs. User","jobcount_vs_username");
    jobstats_input_metric("CPU Time vs. User","cpuhours_vs_username");
    jobstats_input_metric("Job Length vs. User","walltime_vs_username");
    jobstats_input_metric("Queue Time vs. User","qtime_vs_username");
    jobstats_input_metric("Real Memory vs. User","mem_kb_vs_username");
    jobstats_input_metric("Virtual Memory vs. User","vmem_kb_vs_username");
    jobstats_input_metric("Walltime Accuracy vs. User","walltime_acc_vs_username");
    jobstats_input_metric("CPU Efficiency vs. User","cpu_eff_vs_username");

    // by month
    echo "<TR><TH colspan=\"3\"><HR></TH></TR>\n";
    jobstats_input_metric("Job Count by Month","jobcount_vs_month");
    jobstats_input_metric("CPU Time by Month","cpuhours_vs_month");
    jobstats_input_metric("Job Length by Month","walltime_vs_month");
    jobstats_input_metric("Queue Time by Month","qtime_vs_month");
    
    echo "</TABLE>\n";
    echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";   
  }

$db->disconnect;
page_footer();
?>