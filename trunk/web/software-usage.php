<?php
require_once 'DB.php';
require_once 'page-layout.php';

$title = "Software usage";
if ( isset($_POST['system']) )
  {
    $title .= " on ".$_POST['system'];
  }
if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
     $_POST['start_date']!="" )
  {
    $title .= " started on ".$_POST['start_date'];
  }
 else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	   $_POST['start_date']!="" &&  $_POST['end_date']!="" )
   {
     $title .= " started between ".$_POST['start_date']." and ".$_POST['end_date'];
   }
 else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
   {
     $title .= " started after ".$_POST['start_date'];
   }
 else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
   {
     $title .= " started before ".$_POST['end_date'];
   }
page_header($title);

// regular expressions for different software packages
$pkgre['gamess']="(gamess|rungmx)";

$keys = array_keys($_POST);
if ( isset($_POST['system']) )
  {
    foreach ($keys as $key)
      {
	if ( $key!='system' && $key!='start_date' && $key!='end_date' )
	  {
	    echo "<H3><CODE>".$key."</CODE></H3>\n";
	    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
	    if ( DB::isError($db) )
	      {
		die ($db->getMessage());
	      }
	    else
	      {
		$sql = "SELECT system,COUNT(jobid) AS JOBCOUNT,SEC_TO_TIME(SUM(nproc*TIME_TO_SEC(walltime))) AS CPUHOURS FROM Jobs WHERE system LIKE '".$_POST['system']."' AND script REGEXP ";
		if ( isset($pkgre[$key]) )
		  {
		    $sql .= "'".$pkgre[$key]."'";
		  }
		else
		  {
		    $sql .= "'".$key."'";
		  }
		if ( isset($_POST['start_date']) &&   isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
		     $_POST['start_date']!="" )
		  {
		    $sql = $sql." AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		    $sql = $sql." AND FROM_UNIXTIME(start_ts) <= '".$_POST['start_date']." 23:59:59'";
		  }
		else
		  {
		    if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
		      {
			$sql = $sql." AND FROM_UNIXTIME(start_ts) >= '".$_POST['start_date']." 00:00:00'";
		      }
		    if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
		      {
			$sql = $sql." AND FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
		      }
		  }
		$sql .= " GROUP BY system;";
		#echo "<PRE>".$sql."</PRE>\n";
		$result = $db->query($sql);
		if ( DB::isError($db) )
		  {
		    die ($db->getMessage());
		  }
		else
		  {
		    #echo "<TABLE border=1 width=\"100%\">\n";
		    echo "<TABLE border=1>\n";
		    echo "<TR><TH>system</TH><TH>jobcount</TH><TH>cpuhours</TH></TR>\n";
		    while ($result->fetchInto($row))
		      {
			$rkeys=array_keys($row);
			echo "<TR>";
			foreach ($rkeys as $key)
			  {
			    $data[$key]=array_shift($row);
			    echo "<TD align=\"right\"><PRE>".$data[$key]."</PRE></TD>";
			  }
			echo "</TR>\n";
		      }
		    echo "</TABLE>\n";
		  }
	      }
	    $db->disconnect();
	  }
      }
  }
else
  {
    echo "<FORM method=\"POST\" action=\"software-usage.php\">\n";
    echo "System:  <SELECT name=\"system\" size=\"1\">\n";
    echo "<OPTION value=\"%\">Any\n";
    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
    if ( DB::isError($db) )
      {
        die ($db->getMessage());
      }
    else
      {
	$sql = "SELECT DISTINCT(system) FROM Jobs;";
	$result = $db->query($sql);
	if ( DB::isError($db) )
	  {
	    die ($db->getMessage());
	  }
	else
	  {
	    while ($result->fetchInto($row))
	      {
		$rkeys = array_keys($row);
		foreach ($rkeys as $rkey)
		  {
		    echo "<OPTION>".$row[$rkey]."\n";
		  }
	      }
	  }
      }
    $db->disconnect();
    echo "</SELECT><BR>\n";
    echo "Start date: <INPUT type=\"text\" name=\"start_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";
    echo "End date: <INPUT type=\"text\" name=\"end_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";

    echo "Show properties:<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"abaqus\" value=\"1\"> abaqus<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"amber\" value=\"1\"> amber<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"ansys\" value=\"1\"> ansys<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"fidap\" value=\"1\"> fidap<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"flow3d\" value=\"1\"> flow3d<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"fluent\" value=\"1\"> fluent<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"g98\" value=\"1\"> g98<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"g03\" value=\"1\"> g03<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"gamess\" value=\"1\"> gamess<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"matlab\" value=\"1\"> matlab<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"NAG\" value=\"1\"> NAG<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"namd\" value=\"1\"> namd<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"nwchem\" value=\"1\"> nwchem<BR>\n";
    echo "<INPUT type=\"checkbox\" name=\"scalapack\" value=\"1\"> scalapack<BR>\n";

    echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";
  }

page_footer();
?>