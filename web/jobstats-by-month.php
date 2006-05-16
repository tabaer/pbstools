<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/jobstats.php $
# $Revision: 93 $
# $Date: 2006-02-15 13:53:25 -0500 (Wed, 15 Feb 2006) $
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

// by month
echo "<TR><TH colspan=\"3\"><HR></TH></TR>\n";
jobstats_input_metric("Job Count by Month","jobcount_vs_month");
jobstats_input_metric("CPU Time by Month","cpuhours_vs_month");
jobstats_input_metric("Job Length by Month","walltime_vs_month");
jobstats_input_metric("Queue Time by Month","qtime_vs_month");

echo "</TABLE>\n";
echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";   

$db->disconnect;
page_footer();
?>