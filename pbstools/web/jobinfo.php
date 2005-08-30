<?php
require_once 'DB.php';

echo "<HTML>\n<HEAD>\n<TITLE>";
if ( isset($_POST['jobid']) )
  { 
    echo "Job info for ".$_POST['system']." jobid ".$_POST['jobid'];
  } 
 else
  {
    echo "Job info";
  }
echo "</TITLE>\n</HEAD>\n<BODY>\n";

$keys = array_keys($_POST);
if ( isset($_POST['jobid']) )
  {
    echo "<h1>Job info for ".$_POST['system']." jobid ".$_POST['jobid']."</h1>\n";
      
    $db = DB::connect("mysql://webapp@localhost/pbsacct", FALSE);
    if ( DB::isError($db) )
      {
        die ($db->getMessage());
      }
    else
      {
	$sql = "SELECT jobid";
	foreach ($keys as $key)
	  {
	    if ( isset($_POST[$key]) && $key!='jobid' ) { $sql = $sql.",".$key; }
	  }
	$sql = $sql." FROM Jobs WHERE jobid LIKE '".$_POST['jobid'].".%' AND system LIKE '".$_POST['system']."';";
	$result = $db->query($sql);
	if ( DB::isError($db) )
	  {
	    die ($db->getMessage());
	  }
	else
	  {
	    while ($result->fetchInto($row))
	      {
		echo "<TABLE border=1>\n";
		foreach ($keys as $key)
		  {
		    if ( isset($_POST[$key]) )
		      {
			$data[$key]=array_shift($row);
			echo "<TR><TD><B>".$key."</B></TD><TD>".$data[$key]."</TD></TR>\n";
		      }
		  }
		echo "</TABLE>\n";
	       }
	  }
      }

    $db->disconnect();
  }
else
  {
    echo "<FORM method=\"POST\" action=\"jobinfo.php\">\n";
    echo "Job id:  <INPUT type=\"text\" name=\"jobid\" size=\"30\"><BR>\n";
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
    echo "Show properties:<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"username\" value=\"1\"> user]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"groupname\" value=\"1\"> group]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"jobname\" value=\"1\"> job name]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"nproc\" value=\"1\"> # procs]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"nodes\" value=\"1\"> node request]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"queue\" value=\"1\"> queue]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"submit_ts\" value=\"0\"> submission time]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"start_ts\" value=\"0\"> start time]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"end_ts\" value=\"0\"> end time]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"cput_req\" value=\"0\"> CPU time requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"cput\" value=\"1\"> CPU time used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"walltime_req\" value=\"0\"> wallclock time requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"walltime\" value=\"1\"> wallclock time used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mem_req\" value=\"0\"> real memory requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mem_kb\" value=\"1\"> real memory used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"vmem_req\" value=\"0\"> virtual memory requested]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"vmem_kb\" value=\"1\"> virtual memory used]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mppe\" value=\"0\"> MSPs (Cray X1 only)]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"mppssp\" value=\"0\"> SSPs (Cray X1 only)]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"hostlist\" value=\"1\"> host list]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"exit_status\" value=\"1\"> exit status]<BR>\n";
    echo "[<INPUT type=\"checkbox\" name=\"script\" value=\"0\"> job script]<BR>\n";
    echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";
  }
echo "</BODY>\n</HTML>\n";
?>
