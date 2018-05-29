<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
# Copyright 2008, 2009, 2010, 2011 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';
#require_once 'phplib/jpgraph/jpgraph.php';
#require_once 'phplib/jpgraph/jpgraph_bar.php';
#require_once 'phplib/jpgraph/jpgraph_error.php';
require_once 'phplib/Excel/Workbook.php';
require_once 'phplib/Excel/Worksheet.php';
require_once 'phplib/Excel/Format.php';
require_once 'phplib/ods.php';
require_once 'site-specific.php';

function xaxis($fn)
{
  return preg_replace('/^.*_vs_/','',$fn);
}

function xaxis_column($x,$system,$datelogic="during")
{
  if ( $x=="quarter" )
    {
      if ( $datelogic=="during" )
	{
	  return "CONCAT(DATE_FORMAT(start_date,'%Yq'),QUARTER(start_date)) AS quarter";
	}
      else
	{
	  return "CONCAT(DATE_FORMAT(".$datelogic."_date,'%Yq'),QUARTER(".$datelogic."_date)) AS quarter";
	}
    }
  elseif ( $x=="month" )
    {
      if ( $datelogic=="during" )
	{
	  return "DATE_FORMAT(start_date,'%Y/%m') AS month";
	}
      else
	{
	  return "DATE_FORMAT(".$datelogic."_date,'%Y/%m') AS month";
	}
    }
  elseif ( $x=="week" )
    {
      if ( $datelogic=="during" )
	{
	  return "DATE_FORMAT(start_date,'%Y-wk%v') AS week";
	}
      else
	{
	  return "DATE_FORMAT(".$datelogic."_date,'%Y-wk%v') AS week";
	}
    }
  elseif ( $x=="institution" )
    {
      return institution_match();
    }
  elseif ( $x=="qtime" )
    {
      $qtime = "start_ts-GREATEST(submit_ts,eligible_ts)";
      $maxs = bucket_maxs($x);
      $column = "CASE WHEN ".$qtime." <= TIME_TO_SEC('".$maxs[0]."') THEN '<=".$maxs[0]."'";
      for ( $i=1 ; $i<count($maxs) ; $i++ )
	{
	  $column .= " WHEN ".$qtime." > TIME_TO_SEC('".$maxs[$i-1]."') AND ".$qtime." <= TIME_TO_SEC('".$maxs[$i]."') THEN '&gt;".$maxs[$i-1]."-".$maxs[$i]."'";
	}
      $column .= " ELSE '>".$maxs[count($maxs)-1]."' END AS ".$x."_bucketed";
      return $column;
    }
  elseif ( $x=="walltime" || $x=="walltime_req" )
    {
      $maxs = bucket_maxs($x);
      $column = "CASE WHEN TIME_TO_SEC(".$x.") <= TIME_TO_SEC('".$maxs[0]."') THEN '<=".$maxs[0]."'";
      for ( $i=1 ; $i<count($maxs) ; $i++ )
	{
	  $column .= " WHEN TIME_TO_SEC(".$x.") > TIME_TO_SEC('".$maxs[$i-1]."') AND TIME_TO_SEC(".$x.") <= TIME_TO_SEC('".$maxs[$i]."') THEN '&gt;".$maxs[$i-1]."-".$maxs[$i]."'";
	}
      $column .= " ELSE '>".$maxs[count($maxs)-1]."' END AS ".$x."_bucketed";
      return $column;
    }
  elseif ( $x=="nproc_bucketed" )
    {
      $maxs = bucket_maxs("nproc");
      $column = "CASE WHEN nproc <= '".$maxs[0]."' THEN '<=".$maxs[0]."'";
      for ( $i=1 ; $i<count($maxs) ; $i++ )
	{
	  $column .= " WHEN nproc > '".$maxs[$i-1]."' AND nproc <= '".$maxs[$i]."' THEN '&gt;".$maxs[$i-1]."-".$maxs[$i]."'";
	}
      $column .= " ELSE '>".$maxs[count($maxs)-1]."' END AS nproc_bucketed";
      return $column;
    }
  elseif ( $x=="nproc_norm" )
    {
      $maxs = bucket_maxs("nproc_norm");
      $column = "CASE WHEN nproc/".nprocs($system)." <= '".$maxs[0]."' THEN '<=".$maxs[0]."'";
      for ( $i=1 ; $i<count($maxs) ; $i++ )
	{
	  $column .= " WHEN nproc/".nprocs($system)." > '".$maxs[$i-1]."' AND nproc/".nprocs($system)." <= '".$maxs[$i]."' THEN '&gt;".$maxs[$i-1]."-".$maxs[$i]."'";
	}
      $column .= " ELSE '>".$maxs[count($maxs)-1]."' END AS nproc_norm";
      return $column;
    }
  elseif ( $x=="qos" )
    {
      $column = "CASE WHEN qos IS NULL THEN 'default' ELSE qos END as qos";
      return $column;
    }

  else
    {
      return $x;
    }
}

function clause($xaxis,$metric)
{
  if ( $metric=="qtime" || $metric=="xfactor" ) return "( start_ts >= submit_ts )";
  if ( $xaxis=="account" ) return "account IS NOT NULL";
  return "";
}

function metric($fn)
{
  return preg_replace('/_vs_.*$/','',$fn);
}


function time_to_hrs($time)
{
  $times=preg_split('/:/',$time);
  $hrs=$times[0];
  $min=$times[1];
  $sec=$times[2];
  return $hrs+($min/60)+($sec/3600);
}


function units($metric)
{
  if (  preg_match('/^cpu/',$metric)|| preg_match('/time$/',$metric) )
    {
      return " (hrs)";
    }
  if ( preg_match('/_kb$/',$metric) )
    {
      return " (kB)";
    }
  return "";
}


// date selector
function dateselect($action,$start_date,$end_date)
{
  if ( $action=="during" )
    {
      if ( isset($start_date) && isset($end_date) &&
	    $start_date!="" && $end_date!="" )
	{
	  return "( start_date BETWEEN '".$start_date."' AND '".$end_date."' ) OR ( end_date BETWEEN '".$start_date."' AND '".$end_date."' ) OR ( start_date<='".$start_date."' AND end_date>='".$end_date."' )";
	}
      else if ( isset($start_date) && $start_date!="" )
	{
	  return "start_date>='".$start_date."'";
	}
      else if ( isset($end_date) && $end_date!="" )
	{
	  return "end_date>='".$end_date."'";
	}
      else
	{
	  return "start_date IS NOT NULL AND end_date IS NOT NULL";
	}
    }
  else if ( isset($start_date) && isset($end_date) &&
	    $start_date!="" && $end_date!="" )
    {
      #return $action."_date BETWEEN '".$start_date."' AND '".$end_date."'";
      return $action."_date>='".$start_date."' AND ".$action."_date<='".$end_date."'";
    }
  else if ( isset($start_date) && $start_date!="" )
    {
      return $action."_date >= '".$start_date."'";
    }
  else if ( isset($end_date) && $end_date!="" )
    {
      return $action."_date <= '".$_POST['end_date']."'";
    }
  else
    {
      return $action."_date IS NOT NULL";
    }
}


function ndays($db,$system,$start_date,$end_date)
{
  if ( isset($start_date) && $start_date!="" )
    {
      $begin="'".$start_date." 00:00:00'";
    }
  else
    {
      $begin="FROM_UNIXTIME(MIN(submit_ts))";
    }
  if ( isset($end_date) && $end_date!="" )
    {
      $end="'".$end_date." 23:59:59'";
    }
  else
    {
      $end="FROM_UNIXTIME(MAX(end_ts))";
    }
  $query =  "SELECT SUM(DATEDIFF(end,start)+1) AS ndays,\n";
  $query .= "       SUM(nproc*24*(DATEDIFF(end,start)+1)) AS cpuhrs_avail\n";
  $query .= "FROM ( SELECT nproc,\n";
  $query .= "       CASE\n";
  $query .= "         WHEN '".$start_date."' >= start AND ( ( end IS NULL AND CURRENT_DATE < '".$start_date."' ) OR '".$start_date."' <= end ) THEN '".$start_date."'\n";
  $query .= "         WHEN '".$start_date."' < start THEN start\n";
  $query .= "         WHEN end IS NOT NULL AND '".$start_date."' > end THEN end\n";
  $query .= "         WHEN end IS NULL AND '".$start_date."' > CURRENT_DATE THEN CURRENT_DATE\n";
  $query .= "         ELSE '".$start_date."'\n";
  $query .= "       END AS start,\n";
  $query .= "       CASE\n";
  $query .= "         WHEN '".$end_date."' >= start AND ( ( end IS NULL AND '".$end_date."' <= CURRENT_DATE ) OR '".$end_date."' <= end ) THEN '".$end_date."'\n";
  $query .= "         WHEN end IS NOT NULL AND '".$end_date."' > end THEN end\n";
  $query .= "         WHEN end IS NULL AND '".$end_date."' > CURRENT_DATE THEN CURRENT_DATE\n";
  $query .= "         WHEN '".$end_date."' < start THEN start\n";
  $query .= "         ELSE '".$end_date."'\n";
  $query .= "        END AS end\n";
  $query .= " FROM Config WHERE system = SUBSTR('".$system."',1,8)\n";
  $query .= "               AND ( ( end IS NOT NULL AND end >= '".$start_date."' )\n";
  $query .= "                  OR ( end IS NULL AND CURRENT_DATE >= '".$start_date."' ) )\n";
  $query .= "               AND start <= '".$end_date."' ) AS tmp;\n";

  #echo "<PRE>".$query."</PRE><BR>\n";
  $result = db_query($db,$query);
  if ( PEAR::isError($result) )
      {
        echo "<PRE>".$result->getMessage()."</PRE>\n";
      }
  $result->fetchInto($row);

  return $row;
}


// metric -> column mapping
function columns($metric,$system,$db,$start_date,$end_date,$datelogic="during")
{
  if ( $metric=='cpuhours' ) return "SUM(".cpuhours($db,$system,$start_date,$end_date,$datelogic).") AS cpuhours";
  if ( $metric=='gpuhours' ) return "SUM(".gpuhours($db,$system,$start_date,$end_date,$datelogic).") AS gpuhours";
  if ( $metric=='nodehours' ) return "SUM(".nodehours($db,$system,$start_date,$end_date,$datelogic).") AS nodehours";
  if ( $metric=='charges' ) return "SUM(".charges($db,$system,$start_date,$end_date,$datelogic).") AS charges";
  if ( $metric=='qtime' ) return "SEC_TO_TIME(MIN(start_ts-GREATEST(submit_ts,eligible_ts))) AS 'MIN(qtime)', SEC_TO_TIME(MAX(start_ts-GREATEST(submit_ts,eligible_ts))) AS 'MAX(qtime)', SEC_TO_TIME(AVG(start_ts-GREATEST(submit_ts,eligible_ts))) AS 'AVG(qtime)', SEC_TO_TIME(STDDEV(start_ts-GREATEST(submit_ts,eligible_ts)))  AS 'STDDEV(qtime)'";
  if ( $metric=='mem_kb' ) return "MIN(mem_kb), MAX(mem_kb), AVG(mem_kb), STDDEV(mem_kb)";
  if ( $metric=='vmem_kb' ) return "MIN(vmem_kb), MAX(vmem_kb), AVG(vmem_kb), STDDEV(vmem_kb)";
  if ( $metric=='walltime' ) return "SEC_TO_TIME(MIN(walltime_sec)) AS 'MIN(walltime)', SEC_TO_TIME(MAX(walltime_sec)) AS 'MAX(walltime)', SEC_TO_TIME(AVG(walltime_sec)) AS 'AVG(walltime)', SEC_TO_TIME(STDDEV(walltime_sec)) AS 'STDDEV(walltime)'";
  if ( $metric=='cput' ) return "SEC_TO_TIME(MIN(cput_sec)) AS 'MIN(cput)',SEC_TO_TIME(MAX(cput_sec)) AS 'MAX(cput)',SEC_TO_TIME(AVG(cput_sec)) AS 'AVG(cput)',SEC_TO_TIME(STDDEV(cput_sec)) AS 'STDDEV(cput)'";
  if ( $metric=='cputime' ) return "SEC_TO_TIME(MIN(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic).")) AS 'MIN(cputime)', SEC_TO_TIME(MAX(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic).")) AS 'MAX(cputime)', SEC_TO_TIME(AVG(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic).")) AS 'AVG(cputime)', SEC_TO_TIME(STDDEV(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic).")) AS 'STDDEV(cputime)'";
  if ( $metric=='walltime_acc' ) return "MIN(walltime_sec/walltime_req_sec) AS 'MIN(walltime_acc)',MAX(walltime_sec/walltime_req_sec) AS 'MAX(walltime_acc)',AVG(walltime_sec/walltime_req_sec) AS 'AVG(walltime_acc)',STDDEV(walltime_sec/walltime_req_sec) AS 'STDDEV(walltime_acc)'";
  if ( $metric=='cpu_eff' ) return "MIN(cput_sec/(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic).")), MAX(cput_sec/(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic).")), AVG(cput_sec/(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic).")), STDDEV(cput_sec/(3600*".cpuhours($db,$system,$start_date,$end_date,$datelogic)."))";
  if ( $metric=='usercount' ) return "COUNT(DISTINCT(username)) AS users,COUNT(DISTINCT(groupname)) AS groups, COUNT(DISTINCT(account)) AS accounts";
  if ( $metric=='backlog' ) return cpuhours($db,$system,$start_date,$end_date,$datelogic)." AS cpuhours, SUM(start_ts-GREATEST(submit_ts,eligible_ts))/3600.0 AS 'SUM(qtime)'";
#  if ( $metric=='xfactor' ) return "1+(SUM(start_ts-submit_ts))/(SUM(walltime_sec)) AS xfactor";
  if ( $metric=='xfactor' ) return "MIN(1+(start_ts-GREATEST(submit_ts,eligible_ts))/walltime_sec) AS 'MIN(xfactor)', MAX(1+(start_ts-GREATEST(submit_ts,eligible_ts))/walltime_sec) AS 'MAX(xfactor)', AVG(1+(start_ts-GREATEST(submit_ts,eligible_ts))/walltime_sec) AS 'AVG(xfactor)', STDDEV(1+(start_ts-GREATEST(submit_ts,eligible_ts))/walltime_sec) AS 'STDDEV(xfactor)'";
  if ( $metric=='users' ) return "COUNT(DISTINCT(username)) AS users";
  if ( $metric=='groups' ) return "COUNT(DISTINCT(groupname)) AS groups";
  if ( $metric=='accounts' ) return "COUNT(DISTINCT(account)) AS accounts";
  if ( $metric=='dodmetrics' ) return "COUNT(DISTINCT(username)) AS users,COUNT(DISTINCT(groupname)) AS projects,".columns('cpuhours',$system,$db,$start_date,$end_date,$datelogic);
  if ( $metric=='nproc' ) return "MIN(nproc),MAX(nproc),AVG(nproc),STDDEV(nproc)";
  if ( $metric=='usage' ) return columns('cpuhours',$system,$db,$start_date,$end_date,$datelogic).", ". columns('nodehours',$system,$db,$start_date,$end_date,$datelogic).", ".columns('charges',$system,$db,$start_date,$end_date,$datelogic).", ".columns('usercount',$system,$db,$start_date,$end_date,$datelogic);
  if ( $metric=='pscmetrics' )
    {
      $first = 0;
      $maxs = bucket_maxs("walltime");
      $column = "SUM( CASE WHEN (".bounded_walltime_sec($start_date,$end_date,$datelogic).")<=TIME_TO_SEC('".$maxs[0]."') THEN ".cpuhours($db,$system,$start_date,$end_date,$datelogic)." ELSE 0 END ) AS '<=".$maxs[0]."'";
      for ( $i=1 ; $i<count($maxs) ; $i++ )
	{
	  $column .= ", SUM( CASE WHEN (".bounded_walltime_sec($start_date,$end_date,$datelogic).")>TIME_TO_SEC('".$maxs[$i-1]."') AND walltime_sec<=TIME_TO_SEC('".$maxs[$i]."') THEN ".cpuhours($db,$system,$start_date,$end_date,$datelogic)." ELSE 0 END ) AS '".$maxs[$i-1]."-".$maxs[$i]."'"; 
	}
      $column .= ", SUM( CASE WHEN (".bounded_walltime_sec($start_date,$end_date,$datelogic).")>TIME_TO_SEC('".$maxs[count($maxs)-1]."') THEN ".cpuhours($db,$system,$start_date,$end_date,$datelogic)." ELSE 0 END ) AS '>".$maxs[count($maxs)-1]."'";
      return $column;
    }
  if ( $metric=='moabstats' ) return columns('cpuhours',$system,$db,$start_date,$end_date,$datelogic).", AVG(1+(start_ts-GREATEST(submit_ts,eligible_ts))/walltime_sec) AS 'AvgXF', MAX(1+(start_ts-GREATEST(submit_ts,eligible_ts))/walltime_sec) AS 'MaxXF', AVG(start_ts-GREATEST(submit_ts,eligible_ts))/3600.0 AS 'AvgQH', AVG(cput_sec/(3600*(".cpuhours($db,$system,$start_date,$end_date,$datelogic)."))) AS 'Effic', AVG(walltime_sec/walltime_req_sec) AS 'WCAcc'";

  return "";
}


// column namings
function columnnames($metric)
{
  if ( $metric=='cpuhours' ) return array("cpuhours");
  if ( $metric=='gpuhours' ) return array("gpuhours");
  if ( $metric=='nodehours' ) return array("nodehours");
  if ( $metric=='charges' ) return array("charges");
  if ( $metric=='qtime' ) return array("MIN(qtime)","MAX(qtime)","AVG(qtime)","STDDEV(qtime)");
  if ( $metric=='mem_kb' ) return array("MIN(mem_kb)","MAX(mem_kb)","AVG(mem_kb)","STDDEV(mem_kb)");
  if ( $metric=='vmem_kb' ) return array("MIN(vmem_kb)","MAX(vmem_kb)","AVG(vmem_kb)","STDDEV(vmem_kb)");
  if ( $metric=='walltime' ) return array("MIN(walltime)","MAX(walltime)","AVG(walltime)","STDDEV(walltime)");
  if ( $metric=='cput' ) return array("MIN(cput)","MAX(cput)","AVG(cput)","STDDEV(cput)");
  if ( $metric=='cputime' ) return array("MIN(cputime)","MAX(cputime)","AVG(cputime)","STDDEV(cputime)");
  if ( $metric=='walltime_acc' ) return array("MIN(walltime_acc)","MAX(walltime_acc)","AVG(walltime_acc)","STDDEV(walltime_acc)");
  if ( $metric=='cpu_eff' ) return array("MIN(cpu_eff)","MAX(cpu_eff)","AVG(cpu_eff)","STDDEV(cpu_eff)");
  if ( $metric=='usercount' ) return array("users","groups","accounts");
  if ( $metric=='backlog' ) return array("cpuhours","SUM(qtime)");
#  if ( $metric=='xfactor' ) return array("xfactor");
  if ( $metric=='xfactor' ) return array("MIN(xfactor)","MAX(xfactor)","AVG(xfactor)","STDDEV(xfactor)");
  if ( $metric=='users' ) return array("users");
  if ( $metric=='groups' ) return array("groups");
  if ( $metric=='accounts' ) return array("accounts");
  if ( $metric=='dodmetrics' ) return array("users","projects","cpuhours");
  if ( $metric=='nproc' ) return array("MIN(nproc)","MAX(nproc)","AVG(nproc)","STDDEV(nproc)");
  if ( $metric=='usage' )
    {
      $output = columnnames('cpuhours');
      foreach (columnnames('nodehours') as $element)
	{
	  array_push($output,$element);
	}
      foreach (columnnames('charges') as $element)
	{
	  array_push($output,$element);
	}
      foreach (columnnames('usercount') as $element)
	{
	  array_push($output,$element);
	}
      return $output;
    }
  if ( $metric=='pscmetrics' )
    {
      $maxs = bucket_maxs("walltime");
      $output = array("&lt;=".$maxs[0]);
      for ( $i=1 ; $i<count($maxs) ; $i++ )
	{
	  $output[] = $maxs[$i-1]."-".$maxs[$i];
	}
      $output[] = "&gt;".$maxs[count($maxs)-1];
      return $output;
    }
  if ( $metric=='moabstats' )
    {
      $output = columnnames('cpuhours');
      array_push($output,"AvgXF");
      array_push($output,"MaxXF");
      array_push($output,"AvgQH");
      array_push($output,"Effic");      
      array_push($output,"WCAcc");      
      return $output;
    }

  return array();
}


function get_metric($db,$system,$xaxis,$metric,$start_date,$end_date,$datelogic="during",$limit_access=false,$ascending=true,$limit=0)
{
  $query = "SELECT ";
   if ( $xaxis!="" )
    { 
      $query .= xaxis_column($xaxis,$system,$datelogic).",";
    }
   $query .= "COUNT(jobid) AS jobs";
   if ( columns($metric,$system,$db,$start_date,$end_date,$datelogic)!="" )
     {
       $query .= ",".columns($metric,$system,$db,$start_date,$end_date,$datelogic);
     }
   $query .= " FROM Jobs WHERE (".sysselect($system).") AND (".
     dateselect($datelogic,$start_date,$end_date).")";
   if ( $limit_access )
     {
       $query .= " AND ( ".limit_user_access($_SERVER['PHP_AUTH_USER'])." )";
     }
   if ( $xaxis!="" )
     {
       if ( $xaxis=="institution" )
	 {
           # OSC site-specific logic begins here
	   #$query .= " AND ( username IS NOT NULL AND username REGEXP '[A-z]{3,4}[0-9]{3,4}' AND username NOT LIKE 'osc%' AND username NOT LIKE 'wrk%' AND username NOT LIKE 'test%')";
           # OSC site-specific logic ends here
	 }
#       else
#	 {
#	   $query .= " AND (".xaxis_column($xaxis,$system,$datelogic)." IS NOT NULL)";
#	 }
       if ( clause($xaxis,$metric)!="" )
	 {
	   $query .= " AND ".clause($xaxis,$metric);
	 }    
       $query .= " GROUP BY ".$xaxis;
     }
   if ( $xaxis=="institution" )
     {
       # OSC site-specific logic begins here
       #$query .= " UNION SELECT 'osc' AS institution,COUNT(jobid) AS jobs";
       #if ( columns($metric,$system,$db,$start_date,$end_date)!="" )
       # {
       #   $query .= ",".columns($metric,$system,$db,$start_date,$end_date);
       # }
       #$query .= " FROM Jobs WHERE (".sysselect($system).") AND (".
       # dateselect("during",$start_date,$end_date).") AND ".
       # "( username IS NOT NULL AND (username NOT REGEXP '[A-z]{3,4}[0-9]{3,4}' OR username LIKE 'osc%' OR username LIKE 'wrk%' OR username LIKE 'test%') )";
       #if ( clause($xaxis,$metric)!="" )
       # {
       #   $query .= " AND ".clause($xaxis,$metric);
       # }
       # OSC site-specific logic ends here
     }
   $query .= " ".sort_criteria($metric."_vs_".$xaxis,$ascending);
   if ( $limit>0 )
     {
       $query .= " LIMIT ".$limit;
     }
   #print "<PRE>".$query."</PRE>\n";
   return db_query($db,$query);
}


function get_bucketed_metric($db,$system,$xaxis,$metric,$start_date,$end_date,$datelogic="during",$limit_access=false)
{
  $query = "SELECT ".xaxis_column($xaxis,$system,$datelogic).",COUNT(jobid) AS jobs";
  if ( columns($metric,$system,$db,$start_date,$end_date)!="" )
    {
      $query .= ",".columns($metric,$system,$db,$start_date,$end_date);
    }
  if ( $xaxis=="walltime" || $xaxis=="walltime_req" )
    {
      $query .= ",MIN(TIME_TO_SEC(".$xaxis.")) AS hidden";
    }
  elseif ( $xaxis=="nproc_bucketed" || $xaxis=="nproc_norm" )
    {
      $query .= ",MIN(nproc) AS hidden";
    }
  elseif ( $xaxis=="qtime" )
    {
      $query .= ",MIN(start_ts-GREATEST(submit_ts,eligible_ts)) AS hidden";
    }
  else
    {
      $query .= ",MIN(".$xaxis.") AS hidden";
    }
  $query .= " FROM Jobs WHERE (".sysselect($system).") AND (".
    dateselect($datelogic,$start_date,$end_date).")";
  if ( clause($xaxis,$metric)!="" )
    {
      $query .= " AND ".clause($xaxis,$metric);
    }
  if ( $limit_access )
    {
      $query .= " AND ( ".limit_user_access($_SERVER['PHP_AUTH_USER'])." )";
    }
  if ( $xaxis=="nproc_bucketed" )
    {
      $query .= " GROUP BY nproc_bucketed";
    }
  elseif ( $xaxis=="nproc_norm" )
    {
      $query .= " GROUP BY nproc_norm";
    }
  else
    {
      $query .= " GROUP BY ".$xaxis."_bucketed";
    }
  $query .= " ORDER BY hidden;";
  print "<PRE>".$query."</PRE>\n";
  return db_query($db,$query);
}


function metric_as_graph($result,$xaxis,$metric,$system,$start_date,$end_date)
{
  set_time_limit(900);
  $myresult=$result;
  $nrows=0;
  $xmax=0;
  while ($myresult->fetchInto($row))
    {
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  $rawdata[$nrows][$key]=$row[$key];
	}
      $nrows++;
    }
  if ( $xaxis=='nproc' )
    {
      for ($i=0; $i<=nprocs($system); $i++ )
	{
	  $x[$i]="";
	  $y[$i]="";
	  $min[$i]="";
	  $max[$i]="";
	  $stddev[$i]="";
	  $max[$i]="";
	  $ysigma[2*$i]="";
	  $ysigma[2*$i+1]="";
	}
    }
  if ( $metric=='jobs' )
    {
      for ($i=0; $i<$nrows; $i++)
	{
	  $x[$i]=$rawdata[$i][0];
	  if ( $xaxis=='nproc' )
	    {
	      if ( $x[$i]>$xmax ) $xmax=$x[$i];
	      $y[$x[$i]]=$rawdata[$i][1];
	    }
	  else
	    {
	      $y[$i]=$rawdata[$i][1];
	    }
	}
    } 
  elseif ( $metric=='cpuhours' || $metric=='xfactor' ||
	   $metric=='users' || $metric=='groups' )
    {
      for ($i=0; $i<$nrows; $i++)
	{
	  $x[$i]=$rawdata[$i][0];
	  if ( $xaxis=='nproc' )
	    {
	      if ( $x[$i]>$xmax ) $xmax=$x[$i];
	      $y[$x[$i]]=$rawdata[$i][2];
	    }
	  else
	    {
	      $y[$i]=$rawdata[$i][2];
	    }
	}
    }
  elseif ( $metric=='backlog' )
    {
      for ($i=0; $i<$nrows; $i++)
	{
	  $x[$i]=$rawdata[$i][0];
	  if ( $xaxis=='nproc' )
	    {
	      if ( $x[$i]>$xmax ) $xmax=$x[$i];
	      $y[$x[$i]]=time_to_hrs($rawdata[$i][3]);
	      $max[$x[$i]]=time_to_hrs($rawdata[$i][2]);
	    }
	  else
	    {
	      $y[$i]=time_to_hrs($rawdata[$i][3]);
	      $max[$i]=time_to_hrs($rawdata[$i][2]);
	    }
	}
    }
  else // everything else is min/max/avg/stddev, plot avg
    {
      for ($i=0; $i<$nrows; $i++)
	{
	  $x[$i]=$rawdata[$i][0];
	  if ( $xaxis=='nproc' )
	    {
	      if ( $x[$i]>$xmax ) $xmax=$x[$i];
	      $y[$x[$i]]=$rawdata[$i][4];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$y[$x[$i]]) )
		{
		  $y[$x[$i]]=time_to_hrs($y[$x[$i]]);
		}
	      $min[$x[$i]]=$rawdata[$i][2];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$min[$x[$i]]) )
		{
		  $min[$x[$i]]=time_to_hrs($min[$x[$i]]);
		}
	      $max[$x[$i]]=$rawdata[$i][3];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$max[$x[$i]]) )
		{
		  $max[$x[$i]]=time_to_hrs($max[$x[$i]]);
		}
	      $stddev[$x[$i]]=$rawdata[$i][5];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$stddev[$x[$i]]) )
		{
		  $stddev[$x[$i]]=time_to_hrs($stddev[$x[$i]]);
		}
	      $ysigma[2*$x[$i]]=$y[$x[$i]]-$stddev[$x[$i]];
	      if ( $ysigma[2*$x[$i]]<0.0 ) $ysigma[2*$x[$i]]=0.0;
	      $ysigma[2*$x[$i]+1]=$y[$x[$i]]+$stddev[$x[$i]];      
	    }
	  else
	    {
	      $y[$i]=$rawdata[$i][4];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$y[$i]) )
		{
		  $y[$i]=time_to_hrs($y[$i]);
		}
	      $min[$i]=$rawdata[$i][2];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$min[$i]) )
		{
		  $min[$i]=time_to_hrs($min[$i]);
		}
	      $max[$i]=$rawdata[$i][3];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$max[$i]) )
		{
		  $max[$i]=time_to_hrs($max[$i]);
		}
	      $stddev[$i]=$rawdata[$i][5];
	      if ( preg_match('/^[0-9]+:[0-5][0-9]:[0-5][0-9]$/',$stddev[$i]) )
		{
		  $stddev[$i]=time_to_hrs($stddev[$i]);
		}
	      $ysigma[2*$i]=$y[$i]-$stddev[$i];
	      if ( $ysigma[2*$i]<0.0 ) $ysigma[2*$i]=0.0;
	      $ysigma[2*$i+1]=$y[$i]+$stddev[$i];
	    }
	}
    }
  $cache = APACHE_CACHE_DIR;
  if ( ! file_exists("/tmp/".$cache) )
    {
      mkdir("/tmp/".$cache,0750);
    }
  $plot=$system."-".$metric."_vs_".$xaxis."-".$start_date."-".$end_date.".png";
  //  $graph = new graph(640,480,$plot,2,0);
  $graph = new graph(800,600,$plot,2,0);
  $graph->img->SetMargin(75, 30, 30, 75);
  if ( $xaxis=='nproc' )
    {
      $graph->SetScale("linlin");
      //$graph->xaxis->SetAutoMax(nprocs($system));
    }
  else
    {
      $graph->SetScale("textlin");
      $graph->xaxis->SetLabelAngle(90);
      $graph->xaxis->SetTickLabels($x);
    }
  $graph->xaxis->title->Set($xaxis);
  $graph->yaxis->title->Set($metric.units($metric));
  if ( $metric=="walltime_acc" || $metric=="cpu_eff" )
    {
      $graph->yscale->SetAutoMax(1.1);
    }
  elseif ( $metric=="xfactor" )
    {
      $graph->yscale->SetAutoMin(1.0);
    }
  if ( $metric!="jobs" && $metric!="cpuhours" && 
       $metric!="backlog" && $metric!="xfactor" &&
       $metric!="users" && $metric!="groups" )
    {
      $maxbar = new BarPlot($max);
      $maxbar->SetWidth(1.0);
      $maxbar->SetFillColor("gray");
      $maxbar->SetLegend("Maximum");
      $graph->Add($maxbar);
    }
  else if ( $metric=="backlog" )
    {
      $maxbar = new BarPlot($max);
      $maxbar->SetWidth(1.0);
      $maxbar->SetFillColor("gray");
      $maxbar->SetLegend("CPU Hours");
      $graph->Add($maxbar);
    }
  $ybar = new BarPlot($y);
  $ybar->SetWidth(1.0);
  if ( $metric!="jobs" && $metric!="cpuhours" && 
       $metric!="backlog" && $metric!="xfactor" &&
       $metric!="users" && $metric!="groups" )
    {
      $ybar->SetLegend("Mean");
    }
  else if ( $metric=="backlog" )
    {
      $ybar->SetLegend("Queue Hours");      
    }  
  $graph->Add($ybar);
  if ( $metric!="jobs" && $metric!="cpuhours" &&
       $metric!="backlog" && $metric!="xfactor" &&
       $metric!="users" && $metric!="groups" )
    {
      $minbar = new BarPlot($min);
      $minbar->SetWidth(1.0);
      $minbar->SetFillColor("white");
      $minbar->SetLegend("Minimum");
      $graph->Add($minbar);
      $errbars = new ErrorPlot($ysigma);
      $errbars->SetColor("red");
      //$errbars->SetCenter();
      $errbars->SetWeight(2);
      $errbars->SetLegend("Std.Dev.");
      $graph->Add($errbars);
    }
  $graph->Stroke();
  $imgurl=$cache.rawurlencode($plot);
  echo "<img src=\"".$imgurl."\">\n";
}


function metric_as_table($result,$xaxis,$metric)
{
  $mycolumnname=array($xaxis,"jobs");
  foreach (columnnames($metric) as $columnname)
    {
      array_push($mycolumnname,$columnname);
    }
  $myresult=$result;
  echo "<TABLE border=\"1\">\n";
  echo "<TR>";
  foreach ($mycolumnname as $header)
    {
      if ( !($header=='hidden') && !($header=='') )
	{
	  echo "<TH>".$header."</TH>";
	}
    }
  echo "</TR>\n";
  while ($myresult->fetchInto($row))
    {
      echo "<TR valign=\"top\">";
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  if ( isset($mycolumnname[$key]) && !($mycolumnname[$key]=='hidden') )
	    {
	      # if a float, format appropriately
	      if ( preg_match("/^-?\d*\.\d+$/",$row[$key])==1 )
		{
		  echo "<TD align=\"right\"><PRE>".number_format(floatval($row[$key]),4)."</PRE></TD>";
		}
              # if an int, format appropriately
	      else if ( preg_match("/^-?\d+$/",$row[$key])==1 )
		{
		  echo "<TD align=\"right\"><PRE>".number_format(floatval($row[$key]))."</PRE></TD>";
		}
	      # otherwise print verbatim
	      else
		{
		  echo "<TD align=\"right\"><PRE>".$row[$key]."</PRE></TD>";
		}
	    }
	}
      echo "</TR>\n";
    }
  echo "</TABLE>\n";
}

function metric_as_xls($result,$xaxis,$metric,$system,$start_date,$end_date)
{
  $myresult=$result;
  $xlsfile=$system."-".$metric."_vs_".$xaxis."-".$start_date."-".$end_date.".xls";
  $cache = APACHE_CACHE_DIR;
  if ( ! file_exists("/tmp/".$cache) )
    {
      mkdir("/tmp/".$cache,0750);
    }

  $workbook = new Workbook("/tmp/".$cache.$xlsfile);
  $worksheet =& $workbook->add_worksheet($metric." vs ".$xaxis);

  $format_hdr =& $workbook->add_format();
  $format_hdr->set_bold();
  $format_hdr->set_align('center');

  $mycolumnname=array($xaxis,"jobs");
  foreach (columnnames($metric) as $columnname)
    {
      array_push($mycolumnname,$columnname);
    }
  $rowctr=0;
  $colctr=0;
  foreach ($mycolumnname as $header)
    {
      $worksheet->write($rowctr,$colctr,"$header",$format_hdr);
      $colctr++;
    }
  while ($myresult->fetchInto($row))
    {
      $rowctr++;
      $colctr=0;
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  if ( isset($mycolumnname[$key]) && !($mycolumnname[$key]=='hidden') )
	    {
	      $worksheet->write($rowctr,$colctr,$row[$key]);
	      $colctr++;
	    }
	}
    }
  $workbook->close();
  echo "<P>Excel file:  <A href=\"/tmp/".$cache.rawurlencode($xlsfile)."\">".$xlsfile."</A></P>\n";
}

function metric_as_ods($result,$xaxis,$metric,$system,$start_date,$end_date)
{
  $myresult=$result;
  $odsfile=$system."-".$metric."_vs_".$xaxis."-".$start_date."-".$end_date.".ods";
  $cache = APACHE_CACHE_DIR;
  if ( ! file_exists("/tmp/".$cache) )
    {
      mkdir("/tmp/".$cache,0750);
    }

  $workbook = newOds();

  $sheet=0;
  $rowctr=0;
  $colctr=0;

  $mycolumnname=array($xaxis,"jobs");
  foreach (columnnames($metric) as $columnname)
    {
      array_push($mycolumnname,$columnname);
    }
  foreach ($mycolumnname as $header)
    {
      $workbook->addCell($sheet,$rowctr,$colctr,"$header","string");
      $colctr++;
    }
  while ($myresult->fetchInto($row))
    {
      $rowctr++;
      $colctr=0;
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  $data=array_shift($row);
	  $type = "string";
	  # regex found on http://www.regular-expressions.info/floatingpoint.html
	  if ( preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/',$data) )
	    {
	      $type = "float";
	    }
	  $workbook->addCell($sheet,$rowctr,$colctr,htmlspecialchars("$data"),$type);
	  $colctr++;
	}
    }
  saveOds($workbook,"/tmp/".$cache.$odsfile);
  echo "<P>ODF file:  <A href=\"/tmp/".$cache.rawurlencode($odsfile)."\">".$odsfile."</A></P>\n";
}

function metric_as_csv($result,$xaxis,$metric,$system,$start_date,$end_date)
{
  $csvfile=$system."-".$metric."_vs_".$xaxis."-".$start_date."-".$end_date.".csv";
  $cache = APACHE_CACHE_DIR;
  if ( ! file_exists("/tmp/".$cache) )
    {
      mkdir("/tmp/".$cache,0750);
    }
  $fh = fopen("/tmp/".$cache.$csvfile,'w');

  $mycolumnname=array($xaxis,"jobs");
  foreach (columnnames($metric) as $columnname)
    {
      array_push($mycolumnname,$columnname);
    }
  $myresult=$result;

  fwrite($fh,"#");
  foreach ($mycolumnname as $header)
    {
      if ( !($header=='hidden') && !($header=='') )
	{
	  fwrite($fh,$header.",");
	}
    }
  fwrite($fh,"\n");

  while ($myresult->fetchInto($row))
    {
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  if ( isset($mycolumnname[$key]) && !($mycolumnname[$key]=='hidden') )
	    {
              if ( $mycolumnname[$key]=='jobname' )
                {
                  fwrite($fh,"\"".$row[$key]."\",");
                }
              else
                {
	          fwrite($fh,$row[$key].",");
                }
	    }
	}
      fwrite($fh,"\n");
    }
  fclose($fh);
  echo "<P>CSV file:  <A href=\"/tmp/".$cache.rawurlencode($csvfile)."\">".$csvfile."</A></P>\n";
}

function result_as_table($result,$mycolumnname)
{
  $myresult=$result;
  echo "<TABLE border=\"1\">\n";
  echo "<TR>";
  foreach ($mycolumnname as $header)
    {
      if ( !($header=='hidden') && !($header=='') )
        {
          echo "<TH>".$header."</TH>";
        }
    }
  echo "</TR>\n";
  while ($myresult->fetchInto($row))
    {
      echo "<TR valign=\"top\">";
      $keys=array_keys($row);
      foreach ($keys as $key)
        {
          if ( isset($mycolumnname[$key]) && !($mycolumnname[$key]=='hidden') && !($mycolumnname[$key]=='script') )
            {
	      # if a float, format appropriately
	      if ( preg_match("/^-?\d*\.\d+$/",$row[$key])==1 )
		{
		  echo "<TD align=\"right\"><PRE>".number_format(floatval($row[$key]),4)."</PRE></TD>";
		}
              # if an int, format appropriately
	      else if ( preg_match("/^-?\d+$/",$row[$key])==1 )
		{
		  echo "<TD align=\"right\"><PRE>".number_format(floatval($row[$key]))."</PRE></TD>";
		}
	      # otherwise print verbatim
	      else
		{
		  echo "<TD align=\"right\"><PRE>".$row[$key]."</PRE></TD>";
		}
            }
	  else if ( $mycolumnname[$key]=='script' )
	    {
              echo "<TD><PRE>".$row[$key]."</PRE></TD>";
	    }
        }
      echo "</TR>\n";
    }
  echo "</TABLE>\n";
}

function result_as_xls($result,$mycolumnname,$filebase)
{
  $myresult=$result;
  $cache = APACHE_CACHE_DIR;
  if ( ! file_exists("/tmp/".$cache) )
    {
      mkdir("/tmp/".$cache,0750);
    }

  $xlsfile = $filebase.".xls";

  $workbook = new Workbook("/tmp/".$cache.$xlsfile);
  $worksheet =& $workbook->add_worksheet("Sheet 1");

  $format_hdr =& $workbook->add_format();
  $format_hdr->set_bold();
  $format_hdr->set_align('center');

  $rowctr=0;
  $colctr=0;
  foreach ($mycolumnname as $header)
    {
      $worksheet->write($rowctr,$colctr,"$header",$format_hdr);
      $colctr++;
    }
  while ($myresult->fetchInto($row))
    {
      $rowctr++;
      $colctr=0;
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  if ( isset($mycolumnname[$key]) && !($mycolumnname[$key]=='hidden') )
	    {
	      $worksheet->write($rowctr,$colctr,$row[$key]);
	      $colctr++;
	    }
	}
    }
  $workbook->close();
  echo "<P>Excel file:  <A href=\"/tmp/".$cache.rawurlencode($xlsfile)."\">".$xlsfile."</A></P>\n";
}

function result_as_ods($result,$mycolumnname,$filebase)
{
  $myresult=$result;
  $cache = APACHE_CACHE_DIR;
  if ( ! file_exists("/tmp/".$cache) )
    {
      mkdir("/tmp/".$cache,0750);
    }

  $odsfile = $filebase.".ods";

  $workbook = newOds();

  $sheet=0;
  $rowctr=0;
  $colctr=0;
  foreach ($mycolumnname as $header)
    {
      $workbook->addCell($sheet,$rowctr,$colctr,"$header","string");
      $colctr++;
    }
  while ($myresult->fetchInto($row))
    {
      $rowctr++;
      $colctr=0;
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  $data=array_shift($row);
	  $type = "string";
	  # regex found on http://www.regular-expressions.info/floatingpoint.html
	  if ( preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/',$data) )
	    {
	      $type = "float";
	    }
	  $workbook->addCell($sheet,$rowctr,$colctr,htmlspecialchars("$data"),$type);
	  $colctr++;
	}
    }
  saveOds($workbook,"/tmp/".$cache.$odsfile);
  echo "<P>ODS file:  <A href=\"/tmp/".$cache.rawurlencode($odsfile)."\">".$odsfile."</A></P>\n";
}

function result_as_csv($result,$mycolumnname,$filebase)
{
  $csvfile=$filebase.".csv";
  $cache = APACHE_CACHE_DIR;
  if ( ! file_exists("/tmp/".$cache) )
    {
      mkdir("/tmp/".$cache,0750);
    }
  $fh = fopen("/tmp/".$cache.$csvfile,'w');

  $myresult=$result;

  fwrite($fh,"#");
  foreach ($mycolumnname as $header)
    {
      if ( !($header=='hidden') && !($header=='') )
	{
	  fwrite($fh,$header.",");
	}
    }
  fwrite($fh,"\n");

  while ($myresult->fetchInto($row))
    {
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  if ( isset($mycolumnname[$key]) && !($mycolumnname[$key]=='hidden') )
	    {
              if ( $mycolumnname[$key]=='jobname' )
                {
                  fwrite($fh,"\"".$row[$key]."\",");
                }
              else
                {
                  fwrite($fh,$row[$key].",");
                }
	    }
	}
      fwrite($fh,"\n");
    }
  fclose($fh);
  echo "<P>CSV file:  <A href=\"/tmp/".$cache.rawurlencode($csvfile)."\">".$csvfile."</A></P>\n";  
}


function jobstats_input_header()
{
  echo "<TABLE>\n";
  echo "<TR>\n";
  echo "  <TH>Metrics</TH>\n";
  #echo "  <TH>Graph</TH>";
  echo "  <TH>Table</TH>\n";
  echo "  <TH>CSV</TH>\n";
  echo "  <TH>Excel</TH>\n";
  echo "  <TH>ODF</TH>\n";
  echo "</TR>\n";
}

function jobstats_input_spacer()
{
  echo "<TR><TH colspan=\"5\"><HR></TH></TR>\n";
}

function jobstats_input_footer()
{
  echo "</TABLE>\n";
}

function jobstats_input_metric($name,$fn)
{
    echo "<TR>\n";
    echo "  <TD>".$name."</TD>";
    #echo "  <TD align=\"center\"><INPUT type=\"checkbox\" name=\"".$fn."_graph\" value=\"1\"></TD>\n";
    echo "  <TD align=\"center\"><INPUT type=\"checkbox\" name=\"".$fn."_table\" value=\"1\">\n";
    echo "  <TD align=\"center\"><INPUT type=\"checkbox\" name=\"".$fn."_csv\" value=\"1\">\n";
    echo "  <TD align=\"center\"><INPUT type=\"checkbox\" name=\"".$fn."_xls\" value=\"1\">\n";
    echo "  <TD align=\"center\"><INPUT type=\"checkbox\" name=\"".$fn."_ods\" value=\"1\">\n";
    echo "</TR>\n";
}


function jobstats_output_metric($name,$fn,$db,$system,$start_date,$end_date,$datelogic="during",$limit_access=false,$ascending=true,$limit=0)
{
  if (    isset($_POST[$fn.'_graph'])
       || isset($_POST[$fn.'_table'])
       || isset($_POST[$fn.'_csv']) 
       || isset($_POST[$fn.'_xls']) 
       || isset($_POST[$fn.'_ods'])
       )
    {
      echo "<H2>".$name."</H2>\n";
      
      if ( isset($_POST[$fn.'_graph']) )
	{
	  $result=get_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access,$ascending,$limit);
	  metric_as_graph($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}
      
      if ( isset($_POST[$fn.'_table']) )
	{
	  $result=get_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access,$ascending,$limit);
	  metric_as_table($result,xaxis($fn),metric($fn));
	}

      if ( isset($_POST[$fn.'_csv']) )
	{
	  $result=get_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access,$ascending,$limit);
	  metric_as_csv($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}

      if ( isset($_POST[$fn.'_xls']) )
	{
	  $result=get_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access,$ascending,$limit);
	  metric_as_xls($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}

     if ( isset($_POST[$fn.'_ods']) )
	{
	  $result=get_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access,$ascending,$limit);
	  metric_as_ods($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}
    }
  ob_flush();
  flush();
}

function jobstats_output_bucketed_metric($name,$fn,$db,$system,$start_date,$end_date,$datelogic,$limit_access=false)
{
  
  if (    isset($_POST[$fn.'_graph'])
       || isset($_POST[$fn.'_table'])
       || isset($_POST[$fn.'_csv'])
       || isset($_POST[$fn.'_xls']) 
       || isset($_POST[$fn.'_ods'])
       )
    {
      echo "<H2>".$name."</H2>\n";
      
      if ( isset($_POST[$fn.'_graph']) )
	{
	  $result=get_bucketed_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access);
	  metric_as_graph($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}
      
      if ( isset($_POST[$fn.'_table']) )
	{
	  $result=get_bucketed_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access);
	  metric_as_table($result,xaxis($fn),metric($fn));
	}

      if ( isset($_POST[$fn.'_csv']) )
	{
	  $result=get_bucketed_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access);
	  metric_as_csv($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}

      if ( isset($_POST[$fn.'_xls']) )
	{
	  $result=get_bucketed_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access);
	  metric_as_xls($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}

      if ( isset($_POST[$fn.'_ods']) )
	{
	  $result=get_bucketed_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date,$datelogic,$limit_access);
	  metric_as_ods($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}
    }
  ob_flush();
  flush();
}


function jobstats_summary($db,$system,$start_date,$end_date,$datelogic="during")
{
  $result=get_metric($db,$system,"","cpuhours",$start_date,$end_date,$datelogic);
  $result->fetchInto($row);
  $jobs=$row[0];
  $cpuhours=$row[1];
  echo "<P><B>".$jobs." jobs run<BR>\n";
  echo $cpuhours." CPU-hours consumed";
  $nproc=nprocs($system);
  if ( $nproc>0 )
    {
      $data=ndays($db,$system,$start_date,$end_date);
      $ndays = $data[0];
      $cpuhours_avail = $data[1];
      if ( $ndays>0 )
	{
	  $avgutil=100.0*$cpuhours/$cpuhours_avail;
	  printf(" (avg. %6.2f%% utilization over %d days)",
		 $avgutil,$ndays);
	}
    }
  echo "<BR>\n";
  $usercount=get_metric($db,$system,"","usercount",$start_date,$end_date,$datelogic);
  $usercount->fetchInto($counts);
  $nusers=$counts[1];
  $ngroups=$counts[2];
  $naccts=$counts[3];
  echo $nusers." distinct users, ".$ngroups." distinct groups, ".$naccts." distinct accounts";
  echo "</B></P>\n";
  ob_flush();
  flush();
}
?>
