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

// by institution
jobstats_input_header();
jobstats_input_metric("Job Count by Institution","jobs_vs_institution");
jobstats_input_metric("Core Hours by Institution","cpuhours_vs_institution");
jobstats_input_metric("GPU Hours by Institution","gpuhours_vs_institution");
jobstats_input_metric("Node Hours by Institution","nodehours_vs_institution");
jobstats_input_metric("Charges by Institution","charges_vs_institution");
jobstats_input_metric("Active Users by Institution","users_vs_institution");
jobstats_input_metric("Active Groups by Institution","groups_vs_institution");
jobstats_input_metric("Active Accounts by Institution","accounts_vs_institution");
jobstats_input_metric("Moab Statistics by Institution","moabstats_vs_institution");
jobstats_input_footer();

end_form();

page_footer();
?>
