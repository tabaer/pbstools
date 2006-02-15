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

// by groupname
jobstats_input_metric("Job Count vs. Group/Project","jobcount_vs_groupname");
jobstats_input_metric("CPU Time vs. Group/Project","cpuhours_vs_groupname");
jobstats_input_metric("Job Length vs. Group/Project","walltime_vs_groupname");
jobstats_input_metric("Queue Time vs. Group/Project","qtime_vs_groupname");
jobstats_input_metric("Real Memory vs. Group/Project","mem_kb_vs_groupname");
jobstats_input_metric("Virtual Memory vs. Group/Project","vmem_kb_vs_groupname");
jobstats_input_metric("Walltime Accuracy vs. Group/Project","walltime_acc_vs_groupname");
jobstats_input_metric("CPU Efficiency vs. Group/Project","cpu_eff_vs_groupname");

echo "</TABLE>\n";
echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";

page_footer();
?>