<?php
# Copyright 2007, 2008 Ohio Supercomputer Center
# Copyright 2008, 2009, 2010, 2011, 2014 University of Tennessee
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

# connect to DB
$db = db_connect();

# list of software packages
#$packages=software_list($db);

# regular expressions for different software packages
#$pkgmatch=software_match_list($db);

$title = "Usage summary";
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
    # system overview
    echo "<H3>Overview</H3>\n";
    $sql = "SELECT system, COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system']).") AS cpuhours, SUM(".charges($db,$_POST['system']).") AS charges, NULL AS pct_util, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE ( ".sysselect($_POST['system'])." ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY system ORDER BY ".$_POST['order']." DESC";
    #echo "<PRE>\n".$sql."</PRE>\n";
    echo "<TABLE border=1>\n";
    echo "<TR><TH>system</TH><TH>jobs</TH><TH>cpuhours</TH><TH>charges</TH><TH>%util</TH><TH>users</TH><TH>groups</TH><TH>accounts</TH></TR>\n";
    ob_flush();
    flush();

    $result = db_query($db,$sql);
    if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
    while ($result->fetchInto($row))
      {
	$data=array();
	$rkeys=array_keys($row);
	echo "<TR>";
	foreach ($rkeys as $rkey)
	  {
	    if ( $row[$rkey]==NULL )
	      {
		$ndays=ndays($db,$row[0],$_POST['start_date'],$_POST['end_date']);
		if ( $ndays[1]>0 )
		  {
		    $data[$rkey]=sprintf("%6.2f",100.0*$row[2]/$ndays[1]);
		  }
		else
		  {
		    $data[$rkey]="N/A";
		  }
	      }
	    else
	      {
		$data[$rkey]=$row[$rkey];
	      }
	    # if a float, format appropriately
	    if ( preg_match("/^-?\d*\.\d+$/",$data[$rkey])==1 )
	      {
		echo "<TD align=\"right\"><PRE>".number_format(floatval($data[$rkey]),4)."</PRE></TD>";
	      }
            # if an int, format appropriately
	    else if ( preg_match("/^-?\d+$/",$data[$rkey])==1 )
	      {
		echo "<TD align=\"right\"><PRE>".number_format(floatval($data[$rkey]))."</PRE></TD>";
	      }
            # otherwise print verbatim
	    else
	      {
		echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
	      }
	  }
	echo "</TR>\n";
	ob_flush();
	flush();
      }
    if ( $_POST['system']=="%" )
      {
	$sql = "SELECT 'TOTAL', COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system']).") AS cpuhours, SUM(".charges($db,$_POST['system']).") AS charges, 'N/A' AS pct_util, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE system LIKE '".$_POST['system']."' AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." )";
	$result = db_query($db,$sql);
	if ( PEAR::isError($result) )
	  {
	    echo "<PRE>".$result->getMessage()."</PRE>\n";
	  }
	while ($result->fetchInto($row))
	  {
	    $rkeys=array_keys($row);
	    echo "<TR>";
	    foreach ($rkeys as $rkey)
	      {
		$data[$rkey]=array_shift($row);
                # if a float, format appropriately
		if ( preg_match("/^-?\d*\.\d+$/",$data[$rkey])==1 )
		  {
		    echo "<TD align=\"right\"><PRE>".number_format($data[$rkey],4)."</PRE></TD>";
		  }
		# if an int, format appropriately
		else if ( preg_match("/^-?\d+$/",$data[$rkey])==1 )
		  {
		    echo "<TD align=\"right\"><PRE>".number_format($data[$rkey])."</PRE></TD>";
		  }
                # otherwise print verbatim
		else
		  {
		    echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
		  }
	      }
	    echo "</TR>\n";
	    ob_flush();
	    flush();
	  }
      }
    echo "</TABLE>\n";    

    # by institution
    # NOTE By-institution jobstats involves site-specific logic.  You may
    # want to comment out the following statement.
    $inst_summary=true;
    if ( isset($_POST['institution']) && isset($inst_summary) && $inst_summary==true )
      {
	echo "<H3>Usage By Institution</H3>\n";
	if  ( isset($_POST['table']) )
	  {
	    $result=get_metric($db,$_POST['system'],'institution','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_table($result,'institution','usage');
	  }
	if ( isset($_POST['csv']) )
	  {
	    $result=get_metric($db,$_POST['system'],'institution','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_csv($result,'institution','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	if ( isset($_POST['xls']) )
	  {
	    $result=get_metric($db,$_POST['system'],'institution','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_xls($result,'institution','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	if ( isset($_POST['ods']) )
	  {
	    $result=get_metric($db,$_POST['system'],'institution','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_ods($result,'institution','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	ob_flush();
	flush();
      }

    # by account
    if ( isset($_POST['account']) )
      {
	echo "<H3>Usage By Account</H3>\n";
	if ( isset($_POST['table']) )
	  {
	    $result=get_metric($db,$_POST['system'],'account','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_table($result,'account','usage');
	  }
	if ( isset($_POST['csv']) )
	  {
	    $result=get_metric($db,$_POST['system'],'account','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_csv($result,'account','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	if ( isset($_POST['xls']) )
	  {
	    $result=get_metric($db,$_POST['system'],'account','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_xls($result,'account','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	if ( isset($_POST['ods']) )
	  {
	    $result=get_metric($db,$_POST['system'],'account','usage',$_POST['start_date'],$_POST['end_date']);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    metric_as_ods($result,'account','usage',$_POST['system'],$_POST['start_date'],$_POST['end_date']);
	  }
	ob_flush();
	flush();
      }

    # software usage
    if ( isset($_POST['software']) )
      {
	echo "<H3>Software Usage</H3>\n";
// 	$first=1;
// 	$sql = "SELECT * FROM ( ";
// 	foreach ( $packages as $pkg )
// 	  {
// 	    if ( $first==1 )
// 	      {
// 		$first=0;
// 	      }
// 	    else
// 	      {
// 		$sql .= "UNION\n";
// 	      }
// 	    $sql .= "SELECT '".$pkg."', COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system']).") AS cpuhours, SUM(".charges($db,$_POST['system']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE ( ".sysselect($_POST['system'])." ) AND ( ";
// 	    if ( isset($pkgmatch[$pkg]) )
// 	      {
// 		$sql .= $pkgmatch[$pkg];
// 	      }
// 	    else
// 	      {
// 		$sql .= "script LIKE '%".$pkg."%' OR software LIKE '%".$package."%'";
// 	      }
// 	    $sql .= " ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." )";
// 	    $sql .= "\n";
// 	  }
// 	$sql .= " ) AS usgsofttmp WHERE jobs>0 ORDER BY ".$_POST['order']." DESC";
	$sql = "SELECT sw_app, COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system']).") AS cpuhours, SUM(".charges($db,$_POST['system']).") AS charges, COUNT(DISTINCT(username)) AS users, COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts FROM Jobs WHERE sw_app IS NOT NULL AND ( ".sysselect($_POST['system'])." ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) GROUP BY sw_app ORDER BY ".$_POST['order']." DESC";
        echo "<PRE>\n".$sql."</PRE>\n";
	$columns = array("package","jobs","cpuhours","charges","users","groups", "accounts");
	if (  isset($_POST['table']) )
	  {
	    $result = db_query($db,$sql);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    result_as_table($result,$columns); 
	  }
	if ( isset($_POST['csv']) )
	  {
	    $result = db_query($db,$sql);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    result_as_csv($result,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
	if ( isset($_POST['xls']) )
	  {
	    $result = db_query($db,$sql);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    result_as_xls($result,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
	if ( isset($_POST['ods']) )
	  {
	    $result = db_query($db,$sql);
	    if ( PEAR::isError($result) )
	      {
		echo "<PRE>".$result->getMessage()."</PRE>\n";
	      }
	    result_as_ods($result,$columns,$_POST['system']."-software_usage-".$_POST['start_date']."-".$_POST['end_date']);
	  }
      }

    db_disconnect($db);
    page_timer();
    bookmarkable_url();
  }
else
  {
    begin_form("usage-summary.php");

    virtual_system_chooser();
    date_fields();

    $orders=array("jobs","cpuhours","charges","users","groups");
    checkboxes_from_array("Supplemental reports",array("institution","account","software"));
    $defaultorder="cpuhours";
    pulldown("order","Order results by",$orders,$defaultorder);
    checkbox("Generate HTML tables for supplemental reports","table",1);
    checkbox("Generate CSV files for supplemental reports","csv");
    checkbox("Generate Excel files for supplemental reports","xls");
    checkbox("Generate ODF files for supplemental reports","ods");

    end_form();
  }

page_footer();
?>
