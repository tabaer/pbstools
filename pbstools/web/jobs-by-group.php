<?php
require_once 'DB.php';

echo "<HTML>\n<HEAD>\n<TITLE>";
if ( isset($_POST['groupname']) )
  { 
    echo "Job owned by group ".$_POST['groupname']." on ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
      {
	echo " submitted on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	echo " submitted between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	echo " submitted after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	echo " submitted before ".$_POST['end_date'];
      }
  }
else
  {
    echo "Jobs by group";
  }
echo "</TITLE>\n</HEAD>\n<BODY>\n";

$keys = array_keys($_POST);
if ( isset($_POST['groupname']) )
  {
    echo "<h1>Jobs owned by group ".$_POST['groupname']." on ".$_POST['system'];
    if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
      {
	echo " submitted on ".$_POST['start_date'];
      }
    else if ( isset($_POST['start_date']) && isset($_POST['end_date']) && $_POST['start_date']!=$_POST['end_date'] && 
	      $_POST['start_date']!="" &&  $_POST['end_date']!="" )
      {
	echo " submitted between ".$_POST['start_date']." and ".$_POST['end_date'];
      }
    else if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
      {
	echo " submitted after ".$_POST['start_date'];
      }
    else if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
      {
	echo " submitted before ".$_POST['end_date'];
      }
    echo "</h1>\n";
    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
    if ( DB::isError($db) )
      {
        die ($db->getMessage());
      }
    else
      {
	$sql = "SELECT jobid,groupname,username";
	foreach ($keys as $key)
	  {
	    if ( isset($_POST[$key]) && $key!='jobid' && $key!='groupname' && $key!='username' &&
                 $key!='start_date' && $key!='end_date' )
	      {
		$sql = $sql.",".$key;
	      }
	  }
	$sql = $sql." FROM Jobs WHERE groupname = '".$_POST['groupname']."' AND system LIKE '".$_POST['system']."'";
	if ( isset($_POST['start_date']) &&   isset($_POST['end_date']) && $_POST['start_date']==$_POST['end_date'] && 
	     $_POST['start_date']!="" )
	  {
	    $sql = $sql." AND FROM_UNIXTIME(submit_ts) = '".$_POST['start_date']."'";
	  }
	else
	  {
	    if ( isset($_POST['start_date']) && $_POST['start_date']!="" )
	      {
		$sql = $sql." AND FROM_UNIXTIME(submit_ts) > '".$_POST['start_date']."'";
	      }
	    if ( isset($_POST['end_date']) && $_POST['end_date']!="" )
	      {
		$sql = $sql." AND FROM_UNIXTIME(submit_ts) < '".$_POST['end_date']."'";
	      }
	  }
        $sql = $sql." ORDER BY submit_ts;";
#	echo "<PRE>".$sql."</PRE>\n";
	$result = $db->query($sql);
	if ( DB::isError($db) )
	  {
	    die ($db->getMessage());
	  }
	else
	  {
	    echo "<TABLE border=1>\n";
	    $i=0;
	    while ($result->fetchInto($row))
	      {
		$rkeys=array_keys($row);
		if ( $i==0 )
		  {
		    echo "<TR><TH>jobid</TH><TH>groupname</TH><TH>username</TH>";
		    foreach ($keys as $key)
		      {
			if ( $key!='jobid' && $key!='groupname' && $key!='username' && $key!='start_date' && $key!='end_date' )
			  {
			    echo "<TH>".$key."</TH>";
			  }
		      }
		    echo "</TR>\n";
		    $i++;
		  } 
		echo "<TR>";
		foreach ($rkeys as $key)
		  {
		    $data[$key]=array_shift($row);
		    echo "<TD>".$data[$key]."</TD>";
		  }
		echo "</TR>\n";
	       }
	    echo "</TABLE>\n";
	  }
      }

    $db->disconnect();
  }
else
  {
    echo "<FORM method=\"POST\" action=\"jobs-by-group.php\">\n";
    echo "Group:  <INPUT type=\"text\" name=\"groupname\" size=\"16\"><BR>\n";
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
    echo "[<INPUT type=\"checkbox\" name=\"jobname\" value=\"1\"> job name]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"nproc\" value=\"1\"> # procs]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"nodes\" value=\"1\"> node request]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"queue\" value=\"1\"> queue]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"submit_ts\" value=\"1\"> submission time]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"start_ts\" value=\"1\"> start time]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"end_ts\" value=\"1\"> end time]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"cput_req\" value=\"1\"> CPU time requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"cput\" value=\"1\"> CPU time used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"walltime_req\" value=\"1\"> wallclock time requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"walltime\" value=\"1\"> wallclock time used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mem_req\" value=\"1\"> real memory requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mem_kb\" value=\"1\"> real memory used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"vmem_req\" value=\"1\"> virtual memory requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"vmem_kb\" value=\"1\"> virtual memory used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mppe\" value=\"1\"> MSPs (Cray X1 only)]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mppssp\" value=\"1\"> SSPs (Cray X1 only)]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"hostlist\" value=\"1\"> host list]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"exit_status\" value=\"1\"> exit status]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"script\" value=\"1\"> job script]<BR>\n";
    echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";
  }
echo "</BODY>\n</HTML>\n";
?>