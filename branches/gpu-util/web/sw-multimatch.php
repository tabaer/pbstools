<?php
# Copyright 2006 Ohio Supercomputer Center
# Copyright 2008, 2009, 2010, 2011 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'page-layout.php';
require_once 'dbutils.php';
require_once 'metrics.php';
require_once 'site-specific.php';

# accept get queries too for handy command-line usage:  suck all the
# parameters into _POST.
if (isset($_GET['system']))
  {
    $_POST = $_GET;
  }

$title = "Jobs matching multiple software packages";
if ( isset($_POST['system']) )
  { 
    $title .= " on ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) &&
	 $_POST['start_date']==$_POST['end_date'] && 
	 $_POST['start_date']!="" )
      {
	$title .= " on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && 
	      isset($_POST['end_date']) && 
	      $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" && 
	      $_POST['end_date']!="" )
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
    $packages = software_list();
    $pkgmatch=software_match_list();
    $done = array();
    foreach ( $packages as $pkg1 )
      {
	 foreach ( $packages as $pkg2 )
	   {
	     if ( $pkg1!=$pkg2 && $done[$pkg1."|".$pkg2]!=1 )
	       {
		 $sql = "SELECT system, COUNT(jobid) AS jobs, SUM(".cpuhours($db,$_POST['system']).") AS cpuhours, COUNT(DISTINCT(username)), COUNT(DISTINCT(groupname)), COUNT(DISTINCT(account)) FROM Jobs WHERE ( ".sysselect($_POST['system'])." ) AND ( ".dateselect("start",$_POST['start_date'],$_POST['end_date'])." ) AND ( ( ".$pkgmatch[$pkg1]." ) AND ( ".$pkgmatch[$pkg2]." ) ) GROUP BY system";
                 #echo "<PRE>".$sql,"</PRE>\n";
		 $result = db_query($db,$sql);
		 if ( PEAR::isError($result) )
		   {
		     echo "<PRE>$pkg1/$pkg2 -- ".$result->getMessage()."</PRE>\n";
		   }
		 else if ( $result->numRows()>0 )
		   {
		     echo "<H3><CODE>".$pkg1."/".$pkg2."</CODE></H3>\n";
		     echo "<TABLE border=\"1\">\n";
		     echo "<TR><TH>system</TH><TH>jobs</TH><TH>cpuhours</TH><TH>users</TH><TH>groups</TH><TH>accounts</TH></TR>\n";
		     while ($result->fetchInto($row))
		       {
			 $rkeys=array_keys($row);
			 echo "<TR>";
			 foreach ($rkeys as $rkey)
			   {
			     $data[$rkey]=array_shift($row);
			     echo "<TD align=\"right\"><PRE>".$data[$rkey]."</PRE></TD>";
			   }
			 echo "</TR>\n";
		       }
		     echo "</TABLE>\n";
		   }
	       }
	     $done[$pkg1."|".$pkg2] = 1;
	     $done[$pkg2."|".$pkg1] = 1;
	     echo "\n";
	     ob_flush();
	     flush();
	   }
      }
    bookmarkable_url();
    db_disconnect($db);
  }
else
  {
    begin_form("sw-multimatch.php");

    system_chooser();
    date_fields();

    end_form();
  }

page_footer();
?>
