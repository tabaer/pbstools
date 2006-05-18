<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'DB.php';
require_once '/var/rw/www/html/jpgraph/jpgraph.php';
require_once '/var/rw/www/html/jpgraph/jpgraph_bar.php';
require_once '/var/rw/www/html/jpgraph/jpgraph_error.php';
require_once 'site-specific.php';

function xaxis($fn)
{
  return preg_replace('/^.*_vs_/','',$fn);
}

function xaxis_column($x)
{
  if ( $x=="month" )
    {
      return "EXTRACT(YEAR_MONTH FROM FROM_UNIXTIME(start_ts))";
    }
  elseif ( $x=="institution" )
    {
      return "SUBSTRING(username,1,3)";
    }
  else
    {
      return $x;
    }
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
function dateselect($start_date,$end_date)
{
    if ( isset($start_date) && isset($end_date) &&
	 $start_date!="" && $end_date!="" )
      {
	return "FROM_UNIXTIME(start_ts) >= '".$start_date." 00:00:00' AND FROM_UNIXTIME(end_ts) <= '".$end_date." 23:59:59'";
      }
    else if ( isset($start_date) && $start_date!="" )
      {
	return "FROM_UNIXTIME(start_ts) >= '".$start_date." 00:00:00'";
      }
    else if ( isset($end_date) && $end_date!="" )
      {
	return "FROM_UNIXTIME(start_ts) <= '".$_POST['end_date']." 23:59:59'";
      }
    else
      {
	return "submit_ts IS NOT NULL AND start_ts IS NOT NULL AND end_ts IS NOT NULL";
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
  $query="SELECT DATEDIFF(".$end.",".$begin.") FROM Jobs WHERE (".
    sysselect($system).") AND (".dateselect($start_time,$end_time).");";
  #echo "<PRE>".$query."</PRE><BR>\n";
  $result = $db->query($query);
  $result->fetchInto($row);
  $ndays = $row[0];

  return $ndays+1;
}


// metric -> column mapping
function columns($metric,$system)
{
  if ( $metric=='cpuhours' ) 
    {
      if ( $system=='x1' )
	return "SUM(TIME_TO_SEC(cput)/3600) AS cpuhours";
      else
	return "SUM(nproc*TIME_TO_SEC(walltime)/3600) AS cpuhours";
    }
  if ( $metric=='qtime' ) return "SEC_TO_TIME(MIN(start_ts-submit_ts)) AS 'MIN(qtime)',SEC_TO_TIME(MAX(start_ts-submit_ts)) AS 'MAX(qtime)',SEC_TO_TIME(AVG(start_ts-submit_ts)) AS 'AVG(qtime)',SEC_TO_TIME(STDDEV(start_ts-submit_ts))  AS 'STDDEV(qtime)'";
  if ( $metric=='mem_kb' ) return "MIN(mem_kb),MAX(mem_kb),AVG(mem_kb),STDDEV(mem_kb)";
  if ( $metric=='vmem_kb' ) return "MIN(vmem_kb),MAX(vmem_kb),AVG(vmem_kb),STDDEV(vmem_kb)";
  if ( $metric=='walltime' ) return "SEC_TO_TIME(MIN(TIME_TO_SEC(walltime))) AS 'MIN(walltime)',SEC_TO_TIME(MAX(TIME_TO_SEC(walltime))) AS 'MAX(walltime)',SEC_TO_TIME(AVG(TIME_TO_SEC(walltime))) AS 'AVG(walltime)',SEC_TO_TIME(STDDEV(TIME_TO_SEC(walltime))) AS 'STDDEV(walltime)'";
  if ( $metric=='cput' ) return "SEC_TO_TIME(MIN(TIME_TO_SEC(cput))) AS 'MIN(cput)',SEC_TO_TIME(MAX(TIME_TO_SEC(cput))) AS 'MAX(cput)',SEC_TO_TIME(AVG(TIME_TO_SEC(cput))) AS 'AVG(cput)',SEC_TO_TIME(STDDEV(TIME_TO_SEC(cput))) AS 'STDDEV(cput)'";
  if ( $metric=='cputime' )
    if ( $system=='x1' )
      return "SEC_TO_TIME(MIN(TIME_TO_SEC(cput))) AS 'MIN(cputime)',SEC_TO_TIME(MAX(TIME_TO_SEC(cput))) AS 'MAX(cputime)',SEC_TO_TIME(AVG(TIME_TO_SEC(cput))) AS 'AVG(cputime)',SEC_TO_TIME(STDDEV(TIME_TO_SEC(cput))) AS 'STDDEV(cputime)'";      
    else
      return "SEC_TO_TIME(MIN(nproc*TIME_TO_SEC(walltime))) AS 'MIN(cputime)',SEC_TO_TIME(MAX(nproc*TIME_TO_SEC(walltime))) AS 'MAX(cputime)',SEC_TO_TIME(AVG(nproc*TIME_TO_SEC(walltime))) AS 'AVG(cputime)',SEC_TO_TIME(STDDEV(nproc*TIME_TO_SEC(walltime))) AS 'STDDEV(cputime)'";
  if ( $metric=='walltime_acc' ) return "MIN(TIME_TO_SEC(walltime)/TIME_TO_SEC(walltime_req)) AS 'MIN(walltime_acc)',MAX(TIME_TO_SEC(walltime)/TIME_TO_SEC(walltime_req)) AS 'MAX(walltime_acc)',AVG(TIME_TO_SEC(walltime)/TIME_TO_SEC(walltime_req)) AS 'AVG(walltime_acc)',STDDEV(TIME_TO_SEC(walltime)/TIME_TO_SEC(walltime_req)) AS 'STDDEV(walltime_acc)'";
  if ( $metric=='cpu_eff' ) return "MIN(TIME_TO_SEC(cput)/(nproc*TIME_TO_SEC(walltime))),MAX(TIME_TO_SEC(cput)/(nproc*TIME_TO_SEC(walltime))),AVG(TIME_TO_SEC(cput)/(nproc*TIME_TO_SEC(walltime))),STDDEV(TIME_TO_SEC(cput)/(nproc*TIME_TO_SEC(walltime)))";
  if ( $metric=='usercount' ) return "COUNT(DISTINCT(username)) AS 'users',COUNT(DISTINCT(groupname)) AS 'groups'";
  if ( $metric=='backlog' ) return "SEC_TO_TIME(SUM(nproc*TIME_TO_SEC(walltime))) AS cpuhours, SEC_TO_TIME(SUM(start_ts-submit_ts)) AS 'SUM(qtime)'";
  if ( $metric=='xfactor' ) return "1+(SUM(start_ts-submit_ts))/(SUM(TIME_TO_SEC(walltime))) AS 'xfactor'";
  return "";
}


// column namings
function columnnames($metric)
{
  if ( $metric=='cpuhours' ) return array("cpuhours");
  if ( $metric=='qtime' ) return array("MIN(qtime)","MAX(qtime)","AVG(qtime)","STDDEV(qtime)");
  if ( $metric=='mem_kb' ) return array("MIN(mem_kb)","MAX(mem_kb)","AVG(mem_kb)","STDDEV(mem_kb)");
  if ( $metric=='vmem_kb' ) return array("MIN(vmem_kb)","MAX(vmem_kb)","AVG(vmem_kb)","STDDEV(vmem_kb)");
  if ( $metric=='walltime' ) return array("MIN(walltime)","MAX(walltime)","AVG(walltime)","STDDEV(walltime)");
  if ( $metric=='cput' ) return array("MIN(cput)","MAX(cput)","AVG(cput)","STDDEV(cput)");
  if ( $metric=='cputime' ) return array("MIN(cputime)","MAX(cputime)","AVG(cputime)","STDDEV(cputime)");
  if ( $metric=='walltime_acc' ) return array("MIN(walltime_acc)","MAX(walltime_acc)","AVG(walltime_acc)","STDDEV(walltime_acc)");
  if ( $metric=='cpu_eff' ) return array("MIN(cpu_eff)","MAX(cpu_eff)","AVG(cpu_eff)","STDDEV(cpu_eff)");
  if ( $metric=='usercount' ) return array("users","groups");
  if ( $metric=='backlog' ) return array("cpuhours","SUM(qtime)");
  if ( $metric=='xfactor' ) return array("xfactor");
  return array();
}


function get_metric($db,$system,$xaxis,$metric,$start_date,$end_date)
{
  $query = "SELECT ";
   if ( $xaxis!="" )
    { 
      $query .= xaxis_column($xaxis).",";
    }
   $query .= "COUNT(jobid) AS jobcount";
   if ( columns($metric,$system)!="" )
     {
       $query .= ",".columns($metric,$system);
     }
   $query .= " FROM Jobs WHERE (".sysselect($system).") AND (".
     dateselect($start_date,$end_date).")";
  if ( $xaxis!="" )
    {
      if ( $xaxis=="institution" )
	{
	  $query .= " AND ( username IS NOT NULL AND username REGEXP '[A-z]{3,4}[0-9]{3,4}' )";
	}
      $query .= " AND (".xaxis_column($xaxis)." IS NOT NULL) GROUP BY ".xaxis_column($xaxis)." ".sort_criteria($metric."_vs_".$xaxis);
    }
  $query .= ";";

  #echo "<PRE>".$query."</PRE>\n";
  $result = $db->query($query);
  if ( DB::isError($db) )
      {
	die ($db->getMessage());
      }
  else
    {
      return $result;
    }
}


function metric_as_graph($result,$xaxis,$metric,$system,$start_date,$end_date)
{
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
  if ( $metric=='jobcount' )
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
  elseif ( $metric=='cpuhours' || $metric=='xfactor' )
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
  $jpgcache = APACHE_CACHE_DIR;
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
  if ( $metric!="jobcount" && $metric!="cpuhours" && 
       $metric!="backlog" && $metric!="xfactor" )
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
  if ( $metric!="jobcount" && $metric!="cpuhours" && 
       $metric!="backlog" && $metric!="xfactor" )
    {
      $ybar->SetLegend("Mean");
    }
  else if ( $metric=="backlog" )
    {
      $ybar->SetLegend("Queue Hours");      
    }  
  $graph->Add($ybar);
  if ( $metric!="jobcount" && $metric!="cpuhours" &&
       $metric!="backlog" && $metric!="xfactor" )
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
  echo "<img src='{$jpgcache}{$plot}'>\n";
}


function metric_as_table($result,$xaxis,$metric)
{
  $myresult=$result;
  echo "<TABLE border=\"1\">\n";
  echo "<TR>\n  <TH>".$xaxis."</TH>\n  <TH>jobcount</TH>\n";
  foreach (columnnames($metric) as $header)
    {
      echo "  <TH>".$header."</TH>\n";
    }
  while ($myresult->fetchInto($row))
    {
      echo "<TR valign=\"top\">";
      $keys=array_keys($row);
      foreach ($keys as $key)
	{
	  $data=array_shift($row);
	  echo "<TD align=\"right\"><PRE>".$data."</PRE></TD>";
	}
      echo "</TR>\n";
    }
  echo "</TABLE>\n";
}


function jobstats_input_metric($name,$fn)
{
    echo "<TR>\n";
    echo "  <TD>".$name."</TD>";
    echo "  <TD align=\"center\"><INPUT type=\"checkbox\" name=\"".$fn."_graph\" value=\"1\"></TD>\n";
    echo "  <TD align=\"center\"><INPUT type=\"checkbox\" name=\"".$fn."_table\" value=\"1\">\n";
    echo "</TR>\n";
}


function jobstats_output_metric($name,$fn,$db,$system,$start_date,$end_date)
{
  
  if ( isset($_POST[$fn.'_graph']) || 
       isset($_POST[$fn.'_table']) )
    {
      echo "<H2>".$name."</H2>\n";
      
      if ( isset($_POST[$fn.'_graph']) )
	{
	  $result=get_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date);
	  metric_as_graph($result,xaxis($fn),metric($fn),$system,$start_date,$end_date);
	}
      
      if ( isset($_POST[$fn.'_table']) )
	{
	  $result=get_metric($db,$system,xaxis($fn),metric($fn),$start_date,$end_date);
	  metric_as_table($result,xaxis($fn),metric($fn));
	}
    }
}


function jobstats_summary($db,$system,$start_date,$end_date)
{
  $result=get_metric($db,$system,"","cpuhours",$start_date,$end_date);
  $result->fetchInto($row);
  $jobcount=$row[0];
  $cpuhours=$row[1];
  echo "<P><B>".$jobcount." jobs run<BR>\n";
  echo $cpuhours." CPU-hours consumed";
  $nproc=nprocs($system);
  if ( $nproc>0 )
    {
      $ndays=ndays($db,$system,$start_date,$end_date);
      if ( $ndays>0 )
	{
	  $avgutil=100.0*$cpuhours/($nproc*24.0*$ndays);
	  printf(" (avg. %6.2f%% utilization over %d days on %d processors)",
		 $avgutil,$ndays,$nproc);
	}
    }
  echo "<BR>\n";
  $usercount=get_metric($db,$system,"","usercount",$start_date,$end_date);
  $usercount->fetchInto($counts);
  $nusers=$counts[1];
  $ngroups=$counts[2];
  echo $nusers." distinct users, ".$ngroups." distinct groups";
  echo "</B></P>\n";
}
?>
