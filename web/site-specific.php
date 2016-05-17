<?php
# Copyright 2006, 2007, 2008, 2015, 2016 Ohio Supercomputer Center
# Copyright 2008, 2009, 2010, 2011, 2014 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$

# The site-specific logic of the reporting system goes here!
# Below are settings for OSC.

# PHP4 workaround for use of PHP5 file_put_contents in ods.php
# from http://www.phpbuilder.com/board/showthread.php?t=10292234
if (!function_exists('file_put_contents')) {
    // Define flags related to file_put_contents(), if necessary
    if (!defined('FILE_USE_INCLUDE_PATH')) {
        define('FILE_USE_INCLUDE_PATH', 1);
    }
    if (!defined('FILE_APPEND')) {
        define('FILE_APPEND', 8);
    }

    function file_put_contents($filename, $data, $flags = 0) {
        // Handle single dimensional array data
        if (is_array($data)) {
            // Join the array elements
            $data = implode('', $data);
        }

        // Flags should be an integral value
        $flags = (int)$flags;
        // Set the mode for fopen(), defaulting to 'wb'
        $mode = ($flags & FILE_APPEND) ? 'ab' : 'wb';
        $use_include_path = (bool)($flags & FILE_USE_INCLUDE_PATH);

        // Open file with filename as a string
        if ($fp = fopen("$filename", $mode, $use_include_path)) {
            // Acquire exclusive lock if requested
            if ($flags & LOCK_EX) {
                if (!flock($fp, LOCK_EX)) {
                    fclose($fp);
                    return false;
                }
            }

            // Write the data as a string
            $bytes = fwrite($fp, "$data");

            // Release exclusive lock if it was acquired
            if ($flags & LOCK_EX) {
                flock($fp, LOCK_UN);
            }

            fclose($fp);
            return $bytes; // number of bytes written
        } else {
            return false;
        }
    }
}

# JPGraph sanity check
if ( !defined('APACHE_CACHE_DIR') )
{
  DEFINE('APACHE_CACHE_DIR','pbsacct_cache/');
}
if ( !defined('CACHE_DIR') )
{
  DEFINE('CACHE_DIR','pbsacct_cache/');
}

# list of all possible 'system' values to do reports on
# NOTE:  This does *NOT* necessary need to have a 1:1 correspondence
#        with the distinct values of the system column in the Jobs DB!
#        Look at the mck and ipf sections of sysselect for examples
#        of how to subset a system based on the hostnames of the compute
#        nodes.
function sys_list()
{
# NICS
#  return array("krakenpf","kraken","athena","verne","nautilus","kid","kids","kfs","mars","bcndev","beacon","beacon2","darter","kraken-all","xt4-all","keeneland","beacon-all");
# OSC
#  return array("amd",
#	       "apple",
#	       "bale",
#	       "coe",
#	       "ipf",
#	       "ipf-altix",
#	       "ipf-noaltix",
#	       "ipf-myri",
#	       "ipf-oldmyri",
#	       "ipf-newmyri",
#	       "ipf-bigmem",
#	       "ipf-serial",
#	       "ipf-parallel",
#	       "ipf-smp",
#	       "ipf+mck",
#	       "ipf+mck-altix",
#	       "ipf+mck-noaltix",
#	       "ipf+mck-oldmyri",
#	       "ipf+mck-bigmem",
#	       "mck",
#	       "mck-altix",
#	       "mck-noaltix",
#	       "mck-myri",
#	       "mck-bigmem",
#	       "opt",
#	       "piv",
#	       "piv-ib",
#	       "piv-noib",
#	       "piv-serial",
#	       "piv-parallel",
#	       "x1");
# New OSC
  return array("opt",
	       "oak",
	       "ruby",
	       "bmibucki",
	       "bmiowens",
	       "oak-gpu",
	       "ruby-gpu",
	       "ruby-mic");
}

# system selector
function sysselect($system)
{
  if ( $system=='amd' ) return "system = 'amd'";
  if ( $system=='apple' ) return "system = 'apple'";
  if ( $system=='bale' ) return "system = 'bale'";
  if ( $system=='coe' ) return "system = 'coe'";
  if ( $system=='ipf' ) return "system = 'ipf'";
  if ( $system=='ipf-altix' ) return "system = 'ipf' AND hostlist REGEXP '^ipf50[1-3]'";
  if ( $system=='ipf-noaltix' ) return "system = 'ipf' AND hostlist NOT REGEXP '^ipf50[1-3]'";
  if ( $system=='ipf-oldmyri' ) return "system = 'ipf' AND hostlist REGEXP '^ipf(0[0-9][0-9]|1([0-1][0-9]|2[0-8]))'";
  if ( $system=='ipf-newmyri' ) return "system = 'ipf' AND hostlist REGEXP '^ipf(1(49|[5-9][0-9])|2([0-4][0-9]|5[0-8]))'";
  if ( $system=='ipf-myri' ) return "(".sysselect('ipf-oldmyri').") OR (".sysselect('ipf-newmyri').") ";
  if ( $system=='ipf-bigmem' ) return "system = 'ipf' AND hostlist REGEXP '^ipf1(29|3[0-9]|4[0-8])'";
  if ( $system=='ipf-serial' ) return "system = 'ipf' AND ( queue = 'serial' OR queue = 'bigmem' OR queue = 'smallsmp' )";
  if ( $system=='ipf-parallel' ) return "system = 'ipf' AND ( queue = 'parallel' OR queue = 'dedicated' OR queue = 'gige' )";
  if ( $system=='ipf-smp' ) return "system = 'ipf' AND ( queue = 'hugemem' OR queue = 'smp' OR queue = 'dedicatedsmp' OR queue  = 'sas' OR queue = 'starp' )";
  if ( $system=='ipf+mck' ) return "( ".sysselect('mck')." OR ".sysselect('ipf')." )";
  if ( $system=='ipf+mck-altix' ) return "(".sysselect('mck-altix').") OR (".sysselect('ipf-altix').") ";
  if ( $system=='ipf+mck-noaltix' ) return "( ".sysselect('mck-noaltix')." ) OR ( ".sysselect('ipf-noaltix')." )";
  if ( $system=='ipf+mck-oldmyri' ) return "( ".sysselect('ipf-oldmyri')." ) OR ( ".sysselect('mck-myri')." ) ";
  if ( $system=='ipf+mck-bigmem' ) return "( ".sysselect('ipf-bigmem')." ) OR ( ".sysselect('mck-bigmem')." ) ";
  if ( $system=='mck' ) return "system = 'mck'";
  if ( $system=='mck-altix' ) return "system = 'mck' AND hostlist REGEXP '^mck149'";
  if ( $system=='mck-noaltix' ) return "system = 'mck' AND hostlist NOT REGEXP '^mck149'";
  if ( $system=='mck-myri' ) return "system = 'mck' AND hostlist REGEXP '^mck(0[0-9][0-9]|1([0-1][0-9]|2[0-8]))'";
  if ( $system=='mck-bigmem' ) return "system = 'mck' AND hostlist REGEXP '^mck1(29|3[0-9]|4[0-8])'";
  if ( $system=='opt' ) return "system = 'opt'";
  if ( $system=='piv' ) return "system = 'piv'";
  if ( $system=='piv-ib' ) return "system = 'piv' AND hostlist REGEXP '^piv(0[0-9][0-9]|1(0[0-9]|1[0-2]))'";
  if ( $system=='piv-noib' ) return "system = 'piv' AND hostlist NOT REGEXP '^piv(0[0-9][0-9]|1(0[0-9]|1[0-2]))'";
  if ( $system=='piv-serial' ) return "system = 'piv' AND ( queue = 'serial' OR queue = 'mdce' OR queue = 'sas' )";
  if ( $system=='piv-parallel' ) return "system = 'piv' AND ( queue = 'parallel' OR queue = 'dedicated' OR queue = 'gige' )";
  if ( $system=='x1' ) return "system = 'x1'";
  if ( $system=='kraken-all' ) return "system = 'kraken' OR system = 'krakenpf'";
  if ( $system=='xt4-all' ) return "system = 'kraken' OR system = 'athena'";
  if ( $system=='keeneland' ) return "system = 'kid' OR system = 'kfs' OR system = 'kids'";
  if ( $system=='beacon-all' ) return "system = 'beacon' OR system = 'beacon2' OR system = 'bcndev'";
  if ( $system=='oak-gpu' ) return "system = 'oak' AND hostlist REGEXP '^n0(28[19]|29[0-9]|3[01][0-9]|320|64[1-9]|65[0-9]|660)' ";
  if ( $system=='ruby-gpu' ) return "system = 'ruby' AND hostlist REGEXP '^r02(0[1-9]|1[0-9]|20)' ";
  if ( $system=='ruby-mic' ) return "system = 'ruby' AND hostlist REGEXP '^r0(2(2[1-9]|3[0-9]|40)|50[1-5])' ";
  return "system LIKE '".$system."'";
}

# processors per system
function nprocs($system)
{
  if ( $system=='amd' ) return 256;
  if ( $system=='apple' ) return 64;
#  if ( $system=='bale' ) return 110;
  if ( $system=='bale' ) return 174;
  if ( $system=='coe' ) return 60;
  if ( $system=='ipf' ) return nprocs('ipf-noaltix')+nprocs('ipf-altix');
  if ( $system=='ipf-altix' ) return 64;
  if ( $system=='ipf-noaltix' ) return nprocs('ipf-myri')+nprocs('ipf-bigmem');
#  if ( $system=='ipf-oldmyri' ) return 256;
  if ( $system=='ipf-oldmyri' ) return 128;
  if ( $system=='ipf-newmyri' ) return 220;
#  if ( $system=='ipf-bigmem' ) return 40;
  if ( $system=='ipf-bigmem' ) return 16;
  if ( $system=='ipf-myri' ) return nprocs('ipf-oldmyri')+nprocs('ipf-newmyri');
  if ( $system=='ipf+mck' ) return nprocs('ipf');
  if ( $system=='ipf+mck-altix' ) return nprocs('ipf-altix');
  if ( $system=='ipf+mck-noaltix' ) return nprocs('ipf-noaltix');
  if ( $system=='ipf+mck-bigmem' ) return nprocs('ipf-bigmem');
  if ( $system=='ipf+mck-oldmyri' ) return nprocs('ipf-oldmyri');
  if ( $system=='mck' ) return nprocs('mck-noaltix')+nprocs('mck-altix');
  if ( $system=='mck-altix' ) return 32;
  if ( $system=='mck-noaltix' ) return nprocs('mck-myri')+nprocs('mck-bigmem');
  if ( $system=='mck-bigmem' ) return 40;
  if ( $system=='mck-myri' ) return 256;
  if ( $system=='opt' ) return 1392;
  if ( $system=='piv' ) return nprocs('piv-ib')+nprocs('piv-noib');
  if ( $system=='piv-ib' ) return 224;
#  if ( $system=='piv-noib' ) return 288;
  if ( $system=='piv-noib' ) return 56;
  if ( $system=='x1' ) return 48;
  if ( $system=='athena' ) return 18048;
  if ( $system=='kraken' ) return 18032;
  if ( $system=='krakenpf' ) return 112896;
  if ( $system=='jaguar' ) return 31328;
  if ( $system=='verne' ) return 64;
  if ( $system=='nautilus' ) return 1024;
  if ( $system=='kid' ) return 1440;
  if ( $system=='kfs' ) return 4224;
  if ( $system=='beacon' ) return 256;
  if ( $system=='bcndev' ) return 256;
  if ( $system=='beacon2' ) return 768;
  return 0;
}

function bounded_walltime($start_date,$end_date,$datelogic="during")
{
  if ( $datelogic!="during" )
    {
      return "walltime";
    }
  elseif ( isset($start_date) && isset($end_date) )
    {
      return "CASE  WHEN ( start_date>='".$start_date."' AND end_date<='".$end_date."' ) THEN walltime   WHEN ( start_date<='".$start_date."' AND end_date<='".$end_date."' ) THEN ADDTIME(walltime,TIMEDIFF(FROM_UNIXTIME(start_ts),'".$start_date." 00:00:00'))   WHEN ( start_date>='".$start_date."' AND end_date>='".$end_date."' ) THEN ADDTIME(walltime,TIMEDIFF('".$end_date." 23:59:59',FROM_UNIXTIME(end_ts)))   ELSE '00:00:00' END";
    }
  elseif ( isset($start_date) )
    {
      return "CASE  WHEN start_date<'".$start_date."' THEN ADDTIME(walltime,TIMEDIFF(FROM_UNIXTIME(start_ts),'".$start_date." 00:00:00'))   ELSE walltime END";
    }
  elseif ( isset($end_date) )
    {
      return "CASE  WHEN end_date>'".$end_date."' THEN  ADDTIME(walltime,TIMEDIFF('".$end_date." 23:59:59',FROM_UNIXTIME(end_ts)))  ELSE walltime END";
    }
  else
    {
      return "walltime";
    }
}

function cpuhours($db,$system,$start_date,$end_date,$datelogic="during")
{
  $retval = "nproc*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
  if ( $system=="%" || $system=="keeneland" || $system=="kraken-all" || $system=="xt4-all" )
    {
      	# get list of systems
	$sql = "SELECT DISTINCT(system) FROM Jobs;";
	$result = db_query($db,$sql);
	if ( PEAR::isError($result) )
	  {
	    echo "<PRE>".$result->getMessage()."</PRE>\n";
	  }
	$systems = array();
	while ($result->fetchInto($row))
	  {
	    $rkeys = array_keys($row);
	    foreach ($rkeys as $rkey)
	      {
		$systems[] = $row[$rkey];
	      }
	  }
	# build case statement
	$retval = "CASE system ";
	foreach ($systems as $thissystem)
	  {
	    if ( $thissystem!="%" )
	      {
		$retval .= " WHEN '".$thissystem."' THEN ".cpuhours($db,$thissystem,$start_date,$end_date,$datelogic)."\n";
	      }
	  }
	$retval .= " END";
    }
  elseif ( $system=="nautilus" )
    {
      $retval = "8*nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  else if ( $system=="kid" || $system=="kids" )
    {
      $retval = "12*nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  else if ( $system=="bcndev" || $system=="beacon" || $system=="beacon2" || $system=="kfs" )
    {
      $retval = "16*nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  else if ( $system=="x1" )
    {
      $retval = "TIME_TO_SEC(cput)/3600.0";
    }
  return $retval;
}

function nodehours($db,$system,$start_date,$end_date,$datelogic="during")
{
  $retval = "nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
  if ( $system=="%" || $system=="keeneland" || $system=="kraken-all" || $system=="xt4-all" )
    {
      	# get list of systems
	$sql = "SELECT DISTINCT(system) FROM Jobs;";
	$result = db_query($db,$sql);
	if ( PEAR::isError($result) )
	  {
	    echo "<PRE>".$result->getMessage()."</PRE>\n";
	  }
	$systems = array();
	while ($result->fetchInto($row))
	  {
	    $rkeys = array_keys($row);
	    foreach ($rkeys as $rkey)
	      {
		$systems[] = $row[$rkey];
	      }
	  }
	# build case statement
	$retval = "CASE system ";
	foreach ($systems as $thissystem)
	  {
	    if ( $thissystem!="%" )
	      {
		$retval .= " WHEN '".$thissystem."' THEN ".nodehours($db,$thissystem,$start_date,$end_date,$datelogic)."\n";
	      }
	  }
	$retval .= " END";
    }
  else if ( $system=="kraken" || $system=="athena" )
    {
      $retval = "(nproc/4)*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  else if ( $system=="krakenpf" )
    {
      $retval = "(nproc/12)*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  else if ( $system=="darter" )
    {
      $retval = "(nproc/16)*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  return $retval;
}

function charges($db,$system,$start_date,$end_date,$datelogic="during")
{
  $retval = cpuhours($db,$system,$start_date,$end_date,$datelogic);
  if ( $system=="%" || $system=="keeneland" || $system=="kraken-all" || $system=="xt4-all" )
    {
      	# get list of systems
	$sql = "SELECT DISTINCT(system) FROM Jobs;";
	$result = db_query($db,$sql);
	if ( PEAR::isError($result) )
	  {
	    echo "<PRE>".$result->getMessage()."</PRE>\n";
	  }
	$systems = array();
	while ($result->fetchInto($row))
	  {
	    $rkeys = array_keys($row);
	    foreach ($rkeys as $rkey)
	      {
		$systems[] = $row[$rkey];
	      }
	  }
	# build case statement
	$retval = "CASE system ";
	foreach ($systems as $thissystem)
	  {
	    if ( $thissystem!="%" )
	      {
		$retval .= " WHEN '".$thissystem."' THEN ".charges($db,$thissystem,$start_date,$end_date,$datelogic)."\n";
	      }
	  }
	$retval .= " END";
    }
  else if ( $system=="bcndev" | $system=="beacon" | $system=="beacon2" )
    {
      $retval = "nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  else if ( $system=="kid" | $system=="kids" | $system=="kfs" )
    {
      $retval = "3*nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
    }
  else if ( $system=="darter" )
    {
      $retval = "2*".cpuhours($db,$system,$start_date,$end_date,$datelogic);
    }
  else if ( $system=="opt" )
    {
      $retval = "0.1*".cpuhours($db,$system,$start_date,$end_date,$datelogic);
    }
  else if ( $system=="oak" )
    {
      $retval  = "CASE queue";
      $retval .= " WHEN 'serial' THEN 0.1*nproc*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
      $retval .= " WHEN 'parallel' THEN 0.1*12*nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
      $retval .= " WHEN 'hugemem' THEN 0.1*32*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
      $retval .= " ELSE 0.1*".cpuhours($db,$system,$start_date,$end_date,$datelogic);
      $retval .= " END";
    }
  else if ( $system=="ruby" )
    {
      $retval  = "CASE queue";
      $retval .= " WHEN 'hugemem' THEN 0.1*32*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
      $retval .= " ELSE 0.1*20*nodect*TIME_TO_SEC(".bounded_walltime($start_date,$end_date,$datelogic).")/3600.0";
      $retval .= " END";
    }
  else if ( $system=="bmibucki" | $system=="bmiowens" | $system=="quick" )
    {
      $retval = "0.0";
    }
  return $retval;
}

# sorting criteria for each metric
# here mostly as an example of what's possible
function sort_criteria($fn)
{
  #  if ( $fn=='cpuhours_vs_groupname' ) return "ORDER BY cpuhours DESC";
  if ( isset($_POST['order']) ) return "ORDER BY ".$_POST['order']." DESC";
  if ( xaxis($fn)=="institution" ) return "ORDER BY institution";
  return "";
}

# site-specific logic for determining institution
function institution_match()
{
# OSC
  $case  = "CASE";
  $case .= " WHEN system='bmibucki' THEN 'osu'";
  $case .= " WHEN system='bmiowens' THEN 'osu'";
  $case .= " WHEN username REGEXP '^[a-z]{4}[0-9]{3,4}$' THEN SUBSTRING(username,1,4)";
  $case .= " WHEN username REGEXP '^[a-z]{3}[0-9]{3,4}$' THEN SUBSTRING(username,1,3)";
  $case .= " WHEN username REGEXP '^an[0-9]{3,4}$' THEN 'awe'";
  $case .= " WHEN username='nova' THEN 'ucn'";
  $case .= " ELSE 'osc'";
  $case .= " END";
  return $case." AS institution";
# NICS
#  return "SUBSTRING(account,1,2) AS institution";
}

# bucket sizes
function bucket_maxs($xaxis)
{
#  if ( $xaxis=='nproc' ) return array("1","4","8","16","32","64","128","256","512","1024");
  if ( $xaxis=='nproc' ) return array("512","2048","8192","16384","32768","65536");
  if ( $xaxis=='nproc_norm' ) return array("0.01","0.10","0.25","0.5","0.75");
  if ( $xaxis=='walltime' ) return array("1:00:00","4:00:00","8:00:00","12:00:00","16:00:00","24:00:00","48:00:00","96:00:00","168:00:00","320:00:00");
  if ( $xaxis=='walltime_req' ) return array("1:00:00","4:00:00","8:00:00","12:00:00","16:00:00","24:00:00","48:00:00","96:00:00","168:00:00","320:00:00");
  if ( $xaxis=='qtime' ) return array("1:00:00","4:00:00","8:00:00","12:00:00","16:00:00","24:00:00","48:00:00","96:00:00","168:00:00","320:00:00");

  if ( $xaxis=='mem_kb' ) return array("262144","1048576","4194304","12582912","33554432");
  if ( $xaxis=='vmem_kb' ) return array("262144","1048576","4194304","12582912","33554432");
  return array();
}

function user_groups($user = NULL)
{
  if ( is_null($user) )
    {
      return array();
    }
  # OSC/NICS ASSUMPTION:  user accounts exist on web server host
  $groupstr = chop(`id -Gn $user`,"\n");
  return explode(" ",$groupstr);
}

function user_accounts($user = NULL)
{
  if ( is_null($user) )
    {
      return array();
    }
  $accts = array();
  # OSC ASSUMPTION:  accounts are groups fitting a particular pattern
  $groups = user_groups($user);
  # staff projects that have a different name in LDAP than in the USDB
  $wonky_groups = array();
  $wonky_groups['appl'] = "PZS0002";
  $wonky_groups['gsi'] = "PZS0420";
  $wonky_groups['oscgen'] = "PZS0200";
  $wonky_groups['oscguest'] = "PZS0205";
  $wonky_groups['oscsys'] = "PZS0201";
  $wonky_groups['sysp'] = "PZS0090";
  foreach ( $groups as $group )
    {
      if ( preg_match('/^P[A-Z]{2,3}\d{4}$/',$group)==1 )
	{
	  array_push($accts,$group);
	}
      else if ( isset($wonky_groups[$group]) )
	{
	  array_push($accts,$wonky_groups[$group]);
	}
    }
  # NICS ASSUMPTION:  user->account mappings can be groveled out of
  # /nics/e/admin/userprojects
  #$fp = fopen("/nics/e/admin/userprojects","r") or die("Unable to open /nics/e/admin/userprojects");
  #while ( !feof($fp) )
  #  {
  #    $elt = preg_split("/\s+/",chop(fgets($fp),"\n"));
  #    if ( strcmp($elt[0],$user)==0 )
  #      {
  #        $theseaccts = explode(",",$elt[4]);
  #        foreach ($theseaccts as $thisacct)
  #          {
  #            if ( ! in_array($thisacct,$accts) )
  #              {
  #                array_push($accts,$thisacct);
  #              }
  #          }
  #      }
  #  }
  #fclose($fp);
  return $accts;
}

function limit_user_access($user = NULL)
{
  if ( is_null($user) )
    {
      return "username IS NULL";
    }
  else
    {
      $acl = "username='".$user."'";
      foreach (user_groups($user) as $group)
	{
	  $acl .= " OR groupname='".$group."'";
	}
      foreach (user_accounts($user) as $acct)
	{
	  $acl .= " OR account='".$acct."'";
	}
      return "( ".$acl." )";
    }
}

# list of software packages to look for in job scripts
function software_list($db = NULL)
{
  if ( is_null($db) )
    {
      # if we don't have access to the DB, return a static list
      $list=array(
		  "3dcavity",
		  "3dh",
		  "55_x",
		  "a_out",
		  "abaqus",
		  "abinit",
		  "accorrsf",
		  "aces2",
		  "aces3",
		  "activeharmony",
		  "adcprep",
		  "adda",
		  "adf",
		  "aflow",
		  "afma",
		  "agk",
		  "aims",
		  "airebo",
		  "AliEn",
		  "amber",
		  "amg2006",
		  "AnalyzePathP",
		  "anolis",
		  "ansys",
		  "anton",
		  "armcibench",
		  "arps",
		  "arts",
		  "ash",
		  "astrobear",
		  "athena",
		  "atmc",
		  "ausam",
		  "autodock",
		  "awm",
		  "bam",
		  "beast",
		  "berkeleygw",
		  "bgw",
		  "bicg_solver",
		  "bigdft",
		  "blat",
		  "bolztran",
		  "boots",
		  "brams-opt",
		  "bugget",
		  "cactus",
		  "calc1",
		  "calc_group_stats",
		  "cam",
		  "cando",
		  "casino",
		  "castro3d",
		  "cbl",
		  "ccsm",
		  "cctm",
		  "cdns",
		  "cdo",
		  "cdp",
		  "cfd++",
		  "cfl3d",
		  "changa",
		  "charmm",
		  "charles",
		  "chemkin",
		  "chemshell",
		  "chg",
		  "chimera",
		  "chroma",
		  "cilk",
		  "clover_inverter",
		  "cluster",
		  "clustalo",
		  "clustalw",
		  "cm1",
		  "coarsen",
		  "comm-bench",
		  "compaware",
		  "comsol",
		  "condor",
		  "consensus",
		  "convectionimr",
		  "cp2k",
		  "cpmd",
		  "cql3d",
		  "crime",
		  "crystal",
		  "css2sld",
		  "csurfs",
		  "cube",
		  "cudac",
		  "cvm",
		  "da_update_bc",
		  "dalexec",
		  "dam",
		  "darshan",
		  "dasquad",
		  #"decypher",
		  "delphi",
		  "delta5d",
		  "desmond",
		  "dfdx",
		  "dghbc",
		  "dhybrid",
		  "dissens",
		  "distuf",
		  "dlmonte",
		  "dlpoly",
		  "dns2d",
		  "dock",
		  "dolt",
		  "doublebeta",
		  "dplasma",
		  "drone",
		  "dtms",
		  "dv72",
		  "dws_mpi",
		  "eddy",
		  "eden",
		  "eigen.x",
		  "elk",
		  "energyplus",
		  "enkf",
		  "ens4dvar",
		  "enzo",
		  "epfem",
		  "episimdemics",
		  "esmf",
		  "esp",
		  "eulacc",
		  "ex_e",
		  "examl",
		  "f-plane",
		  "falkon",
		  "fd3d",
		  "fdl3di",
		  "featureComputation",
		  "fedvr",
		  "fidap",
		  "flash2",
		  "flash4",
		  "flotran",
		  "flowsolver",
		  "flow3d",
		  "fluent",
		  "foam",
		  "force_free",
		  "foxexe",
		  "fsweep",
		  "ftb",
		  "ftes",
		  "gaac",
		  "gadget",
		  "gamess",
		  "garli",
		  "gaussian",
		  "gc",
		  "gdl",
		  "gen.v4",
		  "geodict",
		  "genlatmu",
		  "geosgcm",
		  "glast",
		  "gpaw",
		  "GreenSolver",
		  "grads",
		  "grib",
		  "grbplot",
		  "grmhd",
		  "gromacs",
		  "gromov",
		  "grouper",
		  "gsi.exe",
		  "gtc",
		  "gvksx",
		  "gyro",
		  "h2mol",
		  "h3d",
		  "hadoop",
		  "hall3d",
		  "halo",
		  "harness",
		  "harris",
		  "hchbm",
		  "hd",
		  "hd_nonuma",
		  "hdfsubdomain",
		  "hf",
		  "hf2",
		  "hfb",
		  "hfodd",
		  "hmc",
		  "hmmer",
		  "homme",
		  "hoomd",
		  "hpcc",
		  "hpl",
		  "hsi",
		  "hsphere",
		  "hwtpost",
		  "hybrid-gsi",
		  "hydro",
		  "hy3s",
		  "idl",
		  "ifs",
		  "imb",
		  "inca",
		  "intestine3d",
		  "ior",
		  "iplmcfd",
		  "isodata",
		  "jaguar",
		  "jet_02",
		  "jobgrd",
		  "josephson",
		  "jrmc",
		  "k2r2",
		  "ker_filter_par",
		  "kmeans",
		  "lammps",
		  "lautrec",
		  "les_mpi",
		  "les_spike",
		  "lesmpi",
		  "lfm",
		  "liso",
		  "lkh",
		  "lmf",
		  "lodn",
		  "lsdyna",
		  "lsms",
		  "lu_lesh",
		  "m2md",
		  "madness",
		  "maestro",
		  "masa",
		  "mathematica",
		  "matlab",
		  "mcnp",
		  "mcrothers",
		  "mcsim",
		  "mctas",
		  "md_ab21",
		  "md_xx",
		  "mddriver",
		  "mdsim",
		  "measurements",
		  "meep",
		  "meta",
		  "mfc",
		  "mhd_1",
		  "mhd3d",
		  "mhdam",
		  "milc",
		  "mitgcmuv",
		  "mkelly",
		  "mkl_mm",
		  "mlane",
		  "mm5",
		  "molaf3di",
		  "molcas",
		  "moldife",
		  "moldive",
		  "molpro",
		  "mothur",
		  "mpcugles",
		  "mpi_dgels",
		  "mpi_dgesv",
		  "mpi_dpos",
		  "mpi_helium",
		  "mpi-multi",
		  "mpiasm",
		  "mpiblast",
		  "mrbayes",
		  "mrobb6dipzz",
		  "mtrsassi",
		  "music",
		  "mykim9dgt",
		  "myq",
		  #"nag",
		  "namd",
		  "nb",
		  "ncbi",
		  "ncl",
		  "nek5000",
		  "nektar",
		  "nemd",
		  "nested",
		  "newseriesrun",
		  "nga_cfb",
		  "nicam",
		  "nmm3d",
		  "npb",
		  "npemd",
		  "nplqcd",
		  "nsbsintheat",
		  "nscale",
		  "nsmpicuf",
		  "ntsolve",
		  "nu-segment",
		  "nwchem",
		  "ocore",
		  "octave",
		  "omega",
		  "omen",
		  "omgred",
		  "onepartm",
		  #"openeye",
		  "opt_exe",
		  "optics",
		  "overlap",
		  "p3dfft",
		  "p3ripple",
		  "p4extract",
		  "padc",
		  "parallelEAM",
		  "parallelqp",
		  #"param",
		  "paratec",
		  "paraview",
		  "parflow",
		  "parody",
		  "parsec",
		  "partadv",
		  "paup",
		  "pbar",
		  "pbohmd",
		  "pcg",
		  "pencil",
		  "perseus",
		  "phasta",
		  "phits",
		  "phonon",
		  "pic-star",
		  "pimd",
		  "pkdgrav",
		  "pluto",
		  "pmcl3d",
		  "polarpigs",
		  "polly",
		  "polmc",
		  "pop",
		  "preps",
		  "preqx",
		  "prog_ccm_sph",
		  "prog_hf",
		  "prop_rotation",
		  "propagators",
		  "proto2",
		  "pse",
		  "psolve",
		  "pstg",
		  "pulsar",
		  "pwscf",
		  "python",
		  "qb",
		  "qchem",
		  "qmc",
		  "qrpacc",
		  "qwalk",
		  "R",
		  "r_out",
		  "radhyd",
		  "raxml",
		  "readall_parallel",
		  "reduce",
		  "reflect",
		  "res",
		  "rho_pion_corre",
		  "root",
		  "rosenbrock",
		  "rosetta",
		  "rotbouss",
		  "roth",
		  "rtp",
		  "run_1kmd",
		  "run_all_de_novo",
		  "run_flexible",
		  "run_hyd",
		  "run_im",
		  "run_lprlx",
		  "run_xyzvort",
		  "run1s-5th-NL",
		  "s-param",
		  "s3d",
		  #"sable",
		  "sam_adv_um5",
		  "sas",
		  "sauron",
		  "scalapack",
		  "sddt",
		  "seissol",
		  "sfeles",
		  "sgf",
		  "shadowfax",
		  "sickle",
		  "siesta",
		  "sigma",
		  "simfactory",
		  "simpleio",
		  "sleuth",
		  "sms",
		  "sne3d",
		  "SOAPdenovo",
		  "sord",
		  "sovereign",
		  "spdcp",
		  "srad",
		  "sses",
		  "stagyy",
		  "starcd",
		  "starccm",
		  "stata",
		  "stationaryAccretionShock3D",
		  "sus",
		  "swarthmore",
		  "sweqx",
		  "swh1b",
		  "swift",
		  "tacoma",
		  "tantalus",
		  "tbms",
		  "tdcc2d",
		  "tdse",
		  "terachem",
		  "testpio",
		  "tetradpost",
		  "tfe",
		  "thickdisk",
		  "tmdmpi",
		  "tornado_friction",
		  "track",
		  "translate",
		  "trinityrnaseq",
		  "tristan-mp3d",
		  "tsc",
		  "ttmmdmpi",
		  "turbo",
		  "turbomole",
		  "two_phase",
		  "ukh2d",
		  "upc",
		  "vasp",
		  "velvet",
		  "vbc",
		  "vdac",
		  "vecadd",
		  "vhone",
		  "vida",
		  "visit",
		  "vli",
		  "vorpal",
		  "vmd",
		  "vpic",
		  "walksat",
		  "wave_packet",
		  "wmc",
		  "wrf",
		  "xgc",
		  "xmfdn",
		  "xplot3d",
		  "xtest",
		  "xvicar3d",
		  "xx",
		  "yt",
		  "zeus",
		  "zk3",
		  "zNtoM"
		  );
  
      return $list;
    }
  else
    {
      # if we do have access to the DB, query out all the known packages
      $list = array();
      # do the sort and filter out null here rather than in the DB
      #$sql = "SELECT DISTINCT(sw_app) FROM Jobs";
      $sql = "SELECT sw_app FROM Jobs GROUP BY sw_app";
      #echo "<PRE>".htmlspecialchars($sql)."</PRE>";

      $result = db_query($db,$sql);
      if ( PEAR::isError($result) )
	{
	  echo "<PRE>".$result->getMessage()."</PRE>\n";
	}
      while ($result->fetchInto($row))
	{
	  foreach ($row as $element)
	    {
              if ( $element!="" )
		{
                  #echo "<PRE>$element</PRE>\n";
		  array_push($list,$element);
		}
	    }
	}

      natsort($list);
      return $list;
    }
}

# patterns to identify particular software packages in job scripts
# if a pattern is not specified, the package name from software_list()
# is searched for instead
function software_match_list($db = NULL)
{
  # default to "( script LIKE '%pkgname%' OR ( software IS NOT NULL AND software LIKE 'pkgname%' ) )"
  foreach (software_list($db) as $pkg)
    {
      $pkgmatch[$pkg]="( script LIKE '%".$pkg."%' OR ( software IS NOT NULL AND software LIKE '%".$pkg."%' ) )";
#      $pkgmatch[$pkg]="( script LIKE '%".$pkg."%' )";
    }

  # exceptions
  # REGEXP match is ***MUCH*** slower than regular LIKE matching
  # in MySQL, so don't use REGEXP unless you really need it.
  $pkgmatch['3dh'] = "script LIKE '%./3dh%'";
  $pkgmatch['55_x'] = "script LIKE '%55.x%'";
  $pkgmatch['aims'] = "( script LIKE '%aims%' AND NOT ( script LIKE '%aims/vasp%' ) )";
  $pkgmatch['a_out'] = "( script LIKE '%a.out %' OR script LIKE '%a.out\n%' )";
  $pkgmatch['abinit'] = "( script LIKE '%abinit%' OR script LIKE '%abinis%' OR script LIKE '%abinip%' )";
  $pkgmatch['aces2'] = "script LIKE '%xaces2%'";
  $pkgmatch['adda'] = "( script LIKE '%adda%' AND NOT ( script LIKE '%FindRadDat%' ) )";
  $pkgmatch['adf'] = "( script LIKE '%adf%' AND NOT ( script LIKE '%radfile%' ) AND NOT ( script LIKE '%adfs%' ) )";
  $pkgmatch['AliEn'] = "( script LIKE '%aliroot%' OR script LIKE '%agent.startup%' )";
  $pkgmatch['arts'] = "( script LIKE '%arts%' AND script NOT LIKE '%starts%' )";
  $pkgmatch['ash'] = "( script LIKE '%ash_1%' OR script LIKE '%ash_2%' OR script LIKE '%ash_fd%' )";
  $pkgmatch['athena'] = "script LIKE '%/athena %'";
  $pkgmatch['blat'] = "script LIKE '%blat %'";
  $pkgmatch['boltztran'] = "(script LIKE '%boltzpar%')";
  $pkgmatch['cbl'] = "( script LIKE '% cbl%' OR script LIKE '%pcbl%' OR script LIKE '%biolib%' )";
  $pkgmatch['charles'] = "script like '%charles.exe%'";
  $pkgmatch['chemshell'] = "script LIKE '%chemsh%'";
  $pkgmatch['chg'] = "script LIKE '%/chg %'";
  $pkgmatch['cluster'] = "script LIKE '%/cluster %'";
  $pkgmatch['crystal'] = "script LIKE '%Pcrystal%'";
  $pkgmatch['cube'] = "( script LIKE '%/cube %' OR script LIKE '%/intelcube %' )";
  $pkgmatch['dam'] = "script LIKE '%/dam %'";
  $pkgmatch['decypher'] = "script REGEXP '(decypher|dc_(target|make|blast|phrap)|TimeLogic)'";
  $pkgmatch['desmond'] = "( script LIKE '%desmond%' AND NOT ( username LIKE '%desmond%' ) )";
  $pkgmatch['dipole'] = "script LIKE '%.cxx.op%'";
  $pkgmatch['dissens'] = "script LIKE '%dissens.x%'";
  $pkgmatch['dns2d'] = "( script LIKE '%DNS2d.x%' OR script LIKE '%DNS2d_%.x%' OR script LIKE '%code2.x%' OR script LIKE '%spcal2d.x%' )";
  $pkgmatch['dock'] = "( script LIKE '%dock5%' OR script LIKE '%dock6%' OR script LIKE '%sphgen%' OR script LIKE '%mopac%' )";
  $pkgmatch['esp'] = "script LIKE '%/esp %'";
  $pkgmatch['ex_e'] = "script LIKE '%ex.e%'";
  $pkgmatch['fluent'] = "( script LIKE '%fluent%' OR ( software IS NOT NULL AND software LIKE '%fluent%' ) )";
  $pkgmatch['fsweep'] = "( script LIKE '%fsweep.exe%' OR script LIKE '%fsweep2.exe%' )";
  $pkgmatch['gamess'] = "( script LIKE '%gamess%' OR script LIKE '%rungms%' OR script LIKE '%rungmx%' )";
  $pkgmatch['gaussian'] = "( script LIKE '%g98%' OR script LIKE '%g03%' OR script LIKE '%g09%' )";
  $pkgmatch['gc'] = "script LIKE '%kland_gc%'";
  $pkgmatch['glast'] = "( script LIKE '%glast%' OR script LIKE '%gp run%' )";
  $pkgmatch['harness'] = "script LIKE '%test_harness_driver.py%'";
  $pkgmatch['harris'] = "script LIKE '%harris.cxx.op%'";
  $pkgmatch['hd'] = "script LIKE '%/HD %'";
  $pkgmatch['hf'] = "script LIKE '%hf/hf%'";
  $pkgmatch['hf2'] = "script LIKE '%/hf2%'";
  $pkgmatch['hmmer'] = "( script LIKE '%hmmer%' OR script LIKE '%hmmp%' )";
  $pkgmatch['hpl'] = "script LIKE '%xhpl%'";
  $pkgmatch['hydro'] = "script LIKE '%./hydro %'";
  $pkgmatch['idl']="( script LIKE '%module load idl%' OR script LIKE '%module add idl%' OR script LIKE '%\nidl%' OR ( software IS NOT NULL AND software LIKE '%idl%' ) )";
  $pkgmatch['hsi'] = "( script LIKE '%hsi%' OR script LIKE '%htar%' OR queue='hpss' )";
  $pkgmatch['imb'] = "script LIKE '%IMB-%'";
  $pkgmatch['lammps'] = "( script LIKE '%lammps%' OR script LIKE '% lmp_%' OR script LIKE '%/lmp_%' )";
  $pkgmatch['liso'] = "script LIKE '%/liso %'";
  $pkgmatch['madness'] = "( script LIKE '%m-a-d-n-e-s-s%' OR script LIKE '%slda%' )";
  $pkgmatch['md_xx'] = "script LIKE '%md.xx%'";
  $pkgmatch['meta'] = "( script LIKE '%anti.meta%' OR script LIKE '%para.meta%' OR script LIKE '%xray.meta%' )";
  $pkgmatch['mhd_1'] = "( script LIKE '%mhd_1%' OR script LIKE '%mhd_2%' OR script LIKE '%mhd_3%' OR script LIKE '%mhd_4%' OR script LIKE '%rmhd%' OR script LIKE '% mhd %' )";
  $pkgmatch['mhd_vec'] = "( script LIKE '%mhd_vec%' OR script LIKE '%mhd_pvec%' )";
  $pkgmatch['mm5'] = "( script LIKE '%mm5%' AND NOT SCRIPT LIKE '%womm5%' )";
  $pkgmatch['mrbayes'] = "( script LIKE '%mrbayes%' OR script LIKE '%mb-parallel%' )";
  $pkgmatch['nb'] = "script LIKE '%NB/CODES%'";
  $pkgmatch['ncbi'] = "( script LIKE '%ncbi%' OR script LIKE '%blastall%' OR script LIKE '%blastpgp%' OR script LIKE '%fastacmd%' OR script LIKE '%formatdb%' OR script LIKE '%rpsblast%' OR script LIKE '%seqtest%' )";
  $pkgmatch['nga_fb'] = "( script LIKE '%nga_fb%' OR script LIKE '%nga_cfb%' )";
  $pkgmatch['omega'] = "script LIKE '%omega.exe%'";
  $pkgmatch['openeye'] = "( script LIKE '%babel3%' OR script LIKE '%checkcff%' OR script LIKE '%chunker%' OR script LIKE '%fred2%' OR script LIKE '%fredPA%' OR script LIKE '%ligand_info%' OR script LIKE '%makefraglib%' OR script LIKE '%makerocsdb%' OR script LIKE '%nam2mol%' OR script LIKE '%omega2%' OR script LIKE '%szybki%' )";
  $pkgmatch['opt_exe'] = "( script LIKE '%opt_exe%' OR script LIKE '%scriptLaunchAll%' )";
  $pkgmatch['paraview'] = "script LIKE '%pvserver%'";
  $pkgmatch['pse'] = "( script LIKE '%/PSE\n' OR script LIKE '%/PSE2\n' )";
  $pkgmatch['r_out'] = "( script LIKE '%/r.out %' OR script LIKE '%/r.out\n%' )";
  $pkgmatch['radhyd'] = "( script LIKE '%radhyd%' OR script LIKE '%rhd_hyb%' OR script LIKE '%orion2%' )";
  $pkgmatch['reduce'] = "( script LIKE '%reduce_1%' OR script LIKE '%reduce_eta%' )";
  $pkgmatch['reflect'] = "script LIKE '%/reflect\n%'";
  $pkgmatch['root'] = "script LIKE '%\nroot -q%'";
  $pkgmatch['rosetta'] = "( script LIKE '%rosetta.%' OR script LIKE '%/rr %' )";
  $pkgmatch['roth'] = "script LIKE '%/ROTH%'";
  $pkgmatch['rtp'] = "( script LIKE '%rtp%' AND NOT ( script like '%RestartP%' ) AND NOT ( script LIKE '%addpertpath%' ) )";
  $pkgmatch['run_xyzvort'] = "( script LIKE '%run_xvort%' OR script LIKE '%run_yvort%' OR script LIKE '%run_zvort%' OR script LIKE '%run_thpert%' OR script LIKE '%run_u%' OR script LIKE '%run_v%' OR script LIKE '%run_w%' OR script LIKE '%run_dBZ%' )";
  $pkgmatch['sable'] = "( script LIKE '%sable%' AND script NOT LIKE '%DISABLE%' )";
  $pkgmatch['sas'] = "( script LIKE '%\nsas%' OR ( software IS NOT NULL AND software LIKE '%sas%' ) OR queue  LIKE '%sas%' )";
  $pkgmatch['tbms'] = "( script LIKE '%tbms%dvm%' OR script LIKE '%distr%dvm%' OR script LIKE '%jac%dvm%' OR script LIKE '%mt%dvm%' )";
  $pkgmatch['track'] = "script LIKE '%TRACKdir%'";
  $pkgmatch['turbo'] = "script LIKE '%pturbo.x%'";
  $pkgmatch['upc'] = "script LIKE '%upcrun%'";
  $pkgmatch['vasp'] = "script LIKE '%vasp%'";
  $pkgmatch['visit'] = "( script LIKE '%visit%' AND script NOT LIKE '%revisit%' )";
  $pkgmatch['vpic'] = "( script LIKE '%npic%' OR script LIKE '%open.cxx.op%' )";
  $pkgmatch['xtest'] = "script LIKE '%/xtest%'";
  $pkgmatch['xx'] = "script LIKE '%./xx\n%'";
  $pkgmatch['zeus'] = "( script LIKE '%/zeus%' OR script LIKE '%/pglobal%' )";
  $pkgmatch['zNtoM'] = "( script LIKE '%z1to3%' OR script LIKE '%z4to6%' OR script LIKE '%z7to9%' OR script LIKE '%z10to12%' OR script LIKE '%z13to15%' )";

# package matches with dependencies on other package matches
  $pkgmatch['R'] = "( ( script LIKE '%\nR %' OR script LIKE '%Rscript %' ) AND NOT ( ".$pkgmatch['gaussian']." ) AND NOT ( ".$pkgmatch['adf']." ) )";
  $pkgmatch['agk'] = "( script LIKE '%agk%' AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['mhd_1']." ) )";
  $pkgmatch['amber'] = "( ( script LIKE '%amber%' OR script LIKE '%sander%' OR script LIKE '%pmemd%' OR script LIKE '%sviol%' OR script LIKE '%SingleJob%' OR script LIKE '%MINJob%' OR script LIKE '%run_md_mpi.csh%' ) AND NOT ( ".$pkgmatch['cctm']." ) AND NOT ( ".$pkgmatch['cvm']." ) AND NOT ( ".$pkgmatch['idl']." ) AND NOT ( ".$pkgmatch['qmc']." ) AND NOT ( ".$pkgmatch['sigma']." ) AND NOT ( ".$pkgmatch['tantalus']." ) AND NOT ( ".$pkgmatch['tfe']." ) )";
  $pkgmatch['arps'] = "( script LIKE '%arps%' AND NOT ( ".$pkgmatch['adf']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['matlab']." ) )";
  $pkgmatch['bugget'] = "( script LIKE '%bugget%' AND NOT ( ".$pkgmatch['halo']." ) AND NOT ( ".$pkgmatch['simpleio']." ) )";
  $pkgmatch['cactus'] = "( script LIKE '%cactus%' AND NOT ( ".$pkgmatch['cdo']." ) AND NOT ( ".$pkgmatch['simfactory']." ) )";
  $pkgmatch['cam'] = "( script LIKE '%cam%' AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['grads']." ) AND NOT ( ".$pkgmatch['hsi']." ) )";
  $pkgmatch['ccsm'] = "( ( script LIKE '%ccsm%' OR script LIKE '%cpl%csim%clm%pop%cam%' ) AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['mm5']." ) AND NOT ( ".$pkgmatch['swift']." ) )";
  $pkgmatch['charmm'] = "( script LIKE '%charmm%' AND NOT ( ".$pkgmatch['chemshell']." ) )";
  $pkgmatch['cpmd'] = "( script LIKE '%cpmd%' AND NOT ( ".$pkgmatch['a_out']." ) AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['inca']." ) AND NOT ( ".$pkgmatch['vasp']." ) )";
  $pkgmatch['cvm'] = "( script LIKE '%cvm%' AND NOT ( ".$pkgmatch['cpmd']." ) AND NOT ( ".$pkgmatch['psolve']." ) )";
  $pkgmatch['eden'] = "( script LIKE '%eden%' AND NOT ( ".$pkgmatch['matlab']." ) )";
  $pkgmatch['enzo'] = "( script LIKE '%enzo%' AND NOT ( ".$pkgmatch['rtp']." ) )";
  $pkgmatch['f-plane'] = "( script LIKE '%f-plane%' AND NOT ( ".$pkgmatch['hsi']." ) )";
  $pkgmatch['gadget'] = "( script LIKE '%gadget%' AND NOT ( ".$pkgmatch['hsi']." ) )";
  $pkgmatch['gdl'] = "( script LIKE '%gdl%' AND NOT ( ".$pkgmatch['rotbouss']." ) )";
  $pkgmatch['grib'] = "( script LIKE '%grib%' AND NOT ( ".$pkgmatch['cdo']." ) AND NOT ( ".$pkgmatch['mm5']." ) AND NOT ( ".$pkgmatch['sgf']." ) AND NOT ( ".$pkgmatch['sigma']." ) )";
  $pkgmatch['gromacs'] = "( ( script LIKE '%gromacs%' OR script LIKE '%grompp%' OR script LIKE '%mdrun%' OR script LIKE '%rgmx%' ) AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['cpmd']." ) AND NOT ( ".$pkgmatch['sigma']." ) AND NOT ( ".$pkgmatch['tantalus']." ) )";
  $pkgmatch['gtc'] = "( ( script LIKE '%gtc%' OR script LIKE '%gts%' ) AND NOT ( ".$pkgmatch['cctm']." ) AND NOT ( ".$pkgmatch['pmcl3d']." ) )";
  $pkgmatch['halo'] = "( script LIKE '%halo%' AND NOT ( ".$pkgmatch['enzo']." ) AND NOT ( ".$pkgmatch['gadget']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['simpleio']." ) AND NOT ( ".$pkgmatch['yt']." ) )";
  $pkgmatch['hfb'] = "( script LIKE '%hfb%' AND NOT ( ".$pkgmatch['vbc']." ) )";
  $pkgmatch['hfodd'] = "( script LIKE '%hfodd%' AND NOT ( ".$pkgmatch['cdo']." ) )";
  $pkgmatch['hmc'] = "( script LIKE '%hmc%' AND NOT ( ".$pkgmatch['chroma']." ) AND NOT ( ".$pkgmatch['gadget']." ) AND NOT ( ".$pkgmatch['nplqcd']." ) AND NOT ( ".$pkgmatch['tantalus']." ) AND NOT ( ".$pkgmatch['terachem']." ) )";
  $pkgmatch['hsphere'] = "( script LIKE '%hsphere%' AND NOT ( ".$pkgmatch['lfm']." ) )";
  $pkgmatch['hy3s'] = "( ( script LIKE '%SSA%' OR script LIKE '%HyJCMSS-%' ) AND NOT ( ".$pkgmatch['arps']." ) AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['bugget']." ) AND NOT ( ".$pkgmatch['cactus']." ) AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['ccsm']." ) AND NOT ( ".$pkgmatch['cdo']." ) AND NOT ( ".$pkgmatch['charmm']." ) AND NOT ( ".$pkgmatch['energyplus']." ) AND NOT ( ".$pkgmatch['enzo']." ) AND NOT ( ".$pkgmatch['grmhd']." ) AND NOT ( ".$pkgmatch['halo']." ) AND NOT ( ".$pkgmatch['hchbm']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['milc']." ) AND NOT ( ".$pkgmatch['ncl']." ) AND NOT ( ".$pkgmatch['nwchem']." ) AND NOT ( ".$pkgmatch['simpleio']." ) AND NOT ( ".$pkgmatch['sses']." )  AND NOT ( ".$pkgmatch['tfe']." ) )";
  $pkgmatch['ifs'] = "( script LIKE '%ifsMASTER%' AND NOT ( ".$pkgmatch['cdp']." ) AND NOT ( ".$pkgmatch['hsi']." ) )";
  $pkgmatch['inca'] = "( script LIKE '%inca%' AND NOT ( ".$pkgmatch['vasp']." ) )";
  $pkgmatch['ior'] = "( script LIKE '%ior%' AND NOT ( username LIKE '%ior%' ) AND NOT ( script LIKE '%prior%' ) AND NOT ( ".$pkgmatch['a_out']." ) AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['lammps']." ) AND NOT ( ".$pkgmatch['namd']." ) AND NOT ( ".$pkgmatch['swift']." ) )";
  $pkgmatch['meep'] = "( script LIKE '%meep%' AND NOT ( ".$pkgmatch['sigma']." ) )";
  $pkgmatch['milc'] = "( ( script LIKE '%milc%' OR script LIKE '%su3_%' OR script LIKE '%switch%.csh%' ) AND NOT ( ".$pkgmatch['nicam']." ) AND NOT ( ".$pkgmatch['hmc']." ) )";
  $pkgmatch['measurements'] = " ( script LIKE '%measurements%' ) AND NOT ( ".$pkgmatch['milc']." )";
  $pkgmatch['nag'] = "( script LIKE '%nag%' AND NOT ( ".$pkgmatch['cctm']." ) AND NOT ( ".$pkgmatch['mpi_helium']." ) )";
  $pkgmatch['namd'] = "( ( script LIKE '%namd%' OR script LIKE '%md.sh%' OR SCRIPT LIKE '%rem_mono_npt4.sh%') AND NOT ( ".$pkgmatch['a_out']." ) AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['cactus']." ) AND NOT ( ".$pkgmatch['charmm']." ) AND NOT ( ".$pkgmatch['cdo']." ) AND NOT ( ".$pkgmatch['gromacs']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['hmc']." ) AND NOT ( ".$pkgmatch['hy3s']." ) AND NOT ( ".$pkgmatch['ior']." ) )";
  $pkgmatch['ncl'] = "( script LIKE '%ncl%' AND NOT ( script LIKE '%include%' ) AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['gen.v4']." ) AND NOT ( ".$pkgmatch['grmhd']." ) AND NOT ( ".$pkgmatch['swift']." ) )";
  $pkgmatch['nested'] = "( script LIKE '%nested%' AND NOT ( ".$pkgmatch['enzo']." ) AND NOT ( ".$pkgmatch['grib']." ) )";
  $pkgmatch['nicam'] = "( script LIKE '%nicam%' AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['grads']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['hy3s']." ) )";
  $pkgmatch['npb'] = "( script LIKE '%npb%' AND NOT ( script LIKE '%npbs.%' ) AND NOT ( script LIKE '%snsnpb%' ) AND NOT ( ".$pkgmatch['milc']." ) AND NOT ( ".$pkgmatch['vorpal']." ) )";
  $pkgmatch['omen'] = "( script LIKE '%omen%' AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['GreenSolver']." ) AND NOT ( ".$pkgmatch['milc']." ) )";
  $pkgmatch['overlap']="( script LIKE '%overlap_%' AND NOT ( ".$pkgmatch['grib']." ) AND NOT ( ".$pkgmatch['hfb']." ) AND NOT ( ".$pkgmatch['nicam']." ) AND NOT ( ".$pkgmatch['simfactory']." ) )";
  $pkgmatch['paratec'] = "( script LIKE '%paratec%' AND NOT ( ".$pkgmatch['sigma']." ) )";
  $pkgmatch['pcg'] = "( script LIKE '%pcg%' AND script NOT LIKE '%request%' AND NOT ( ".$pkgmatch['gen.v4']." ) )";
  $pkgmatch['pop'] = "( script LIKE '%pop%' AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['ccsm']." ) AND NOT ( ".$pkgmatch['cp2k']." ) AND NOT ( ".$pkgmatch['charmm']." ) AND NOT ( ".$pkgmatch['grib']." ) AND NOT ( ".$pkgmatch['gromacs']." ) AND NOT ( ".$pkgmatch['hmc']." ) AND NOT ( ".$pkgmatch['namd']." ) AND NOT ( ".$pkgmatch['nwchem']." ) AND NOT ( ".$pkgmatch['run_im']." ) AND NOT ( ".$pkgmatch['sses']." ) )";
  $pkgmatch['propagators'] = "( script LIKE '%propagators%' AND NOT ( ".$pkgmatch['milc']." ) )";
  $pkgmatch['python'] = "( script LIKE '%python%' AND NOT ( ".$pkgmatch['hoomd']." ) )";
  $pkgmatch['qb'] = "( script LIKE '%qb%' AND NOT ( ".$pkgmatch['hfb']." ) AND NOT ( ".$pkgmatch['milc']." ) AND NOT ( ".$pkgmatch['amber']." ) )";
  $pkgmatch['qrpacc']  = "( script LIKE '%qrpacc%' AND NOT ( ".$pkgmatch['vbc']." ) )";
  $pkgmatch['quest'] = "( script LIKE '%quest%' AND script NOT LIKE '%request%' AND NOT ( ".$pkgmatch['gen.v4']." ) )";
  $pkgmatch['radhyd'] = "( script LIKE '%radhyd%' AND NOT ( ".$pkgmatch['chimera']." ) )";
  $pkgmatch['res'] = "( script LIKE '%/res_%' AND NOT ( ".$pkgmatch['enzo']." ) AND NOT ( ".$pkgmatch['grib']." ) AND NOT ( ".$pkgmatch['lammps']." ) )";
  $pkgmatch['run_im'] = "( script LIKE '%run_im%' AND NOT ( ".$pkgmatch['aims']." ) AND NOT ( ".$pkgmatch['flash4']." ) AND NOT ( ".$pkgmatch['ncl']." ) AND NOT ( ".$pkgmatch['wrf']." ) )";
  $pkgmatch['s3d'] = "( script LIKE '%s3d%' AND NOT ( ".$pkgmatch['adf']." ) AND NOT ( ".$pkgmatch['arps']." ) AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['cctm']." ) )";
  $pkgmatch['sgf'] = "( script LIKE '%sgf%' AND NOT ( ".$pkgmatch['lsdyna']." ) AND NOT ( ".$pkgmatch['sigma']." ) )";
  $pkgmatch['sord'] = "( script LIKE '%sord%' AND NOT ( ".$pkgmatch['namd']." ) )";
  $pkgmatch['sses'] = "( script LIKE '%sses%' AND NOT ( script LIKE '%subprocess%' ) AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['arps']." ) AND NOT ( ".$pkgmatch['cactus']." ) AND NOT ( ".$pkgmatch['cdo']." ) AND NOT ( ".$pkgmatch['enzo']." ) AND NOT ( ".$pkgmatch['grib']." ) AND NOT ( ".$pkgmatch['milc']." ) AND NOT ( ".$pkgmatch['namd']." ) AND NOT ( ".$pkgmatch['python']." ) AND NOT ( ".$pkgmatch['qb']." ) AND NOT ( ".$pkgmatch['vasp']." ) AND NOT ( ".$pkgmatch['vbc']." ) )";
  $pkgmatch['sus'] = "( script LIKE '%sus%' AND NOT ( ".$pkgmatch['cam']." ) AND NOT ( ".$pkgmatch['consensus']." ) AND NOT ( ".$pkgmatch['stata']." ) )";
  $pkgmatch['tsc'] = "( script LIKE '%tsc%' AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['arps']." ) AND NOT ( ".$pkgmatch['cactus']." ) AND NOT ( ".$pkgmatch['foam']." ) AND NOT ( ".$pkgmatch['simfactory']." ) AND NOT ( ".$pkgmatch['swift']." ) )";
  $pkgmatch['turbomole'] = "( script LIKE '%turbomole%' AND NOT ( ".$pkgmatch['chemshell']." ) )";
  $pkgmatch['ukh2d'] = "( ( script LIKE '%ukh2d%' OR script LIKE '%ukh.cxx.op%' ) AND NOT ( ".$pkgmatch['h3d']." ) )";
  $pkgmatch['wrf'] = "( script LIKE '%wrf%' AND NOT ( ".$pkgmatch['arps']." ) AND NOT ( ".$pkgmatch['ccsm']." ) AND NOT ( ".$pkgmatch['grib']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['hy3s']." ) AND NOT ( ".$pkgmatch['milc']." ) AND NOT ( ".$pkgmatch['mm5']." ) AND NOT ( ".$pkgmatch['sgf']." ) AND NOT ( ".$pkgmatch['sigma']." ) )";
  $pkgmatch['vmd'] = "( script LIKE '%vmd%' AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['cpmd']." ) AND NOT ( ".$pkgmatch['cvm']." ) AND NOT ( ".$pkgmatch['gromacs']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['hmc']." ) AND NOT ( ".$pkgmatch['namd']." ) AND NOT ( ".$pkgmatch['pop']." ) )";
  $pkgmatch['xgc'] = "( script LIKE '%xgc%' AND NOT ( ".$pkgmatch['agk']." ) AND NOT ( ".$pkgmatch['hsi']." ) )";
  $pkgmatch['yt'] = "( script LIKE '%yt%' AND NOT ( ".$pkgmatch['amber']." ) AND NOT ( ".$pkgmatch['cactus']." ) AND NOT ( ".$pkgmatch['cdo']." ) AND NOT ( ".$pkgmatch['gen.v4']." ) AND NOT ( ".$pkgmatch['grib']." ) AND NOT ( ".$pkgmatch['grmhd']." ) AND NOT ( ".$pkgmatch['hoomd']." ) AND NOT ( ".$pkgmatch['hsi']." ) AND NOT ( ".$pkgmatch['hy3s']." ) AND NOT ( ".$pkgmatch['lammps']." ) AND NOT ( ".$pkgmatch['lfm']." ) AND NOT ( ".$pkgmatch['matlab']." ) AND NOT ( ".$pkgmatch['namd']." ) AND NOT ( ".$pkgmatch['nwchem']." ) AND NOT ( ".$pkgmatch['pop']." ) AND NOT ( ".$pkgmatch['python']." ) AND NOT ( ".$pkgmatch['stata']." ) AND NOT ( ".$pkgmatch['sses']." ) AND NOT ( ".$pkgmatch['sord']." ) AND NOT ( ".$pkgmatch['swift']." ) AND NOT ( ".$pkgmatch['sus']." ) AND NOT ( ".$pkgmatch['vasp']." ) AND NOT ( ".$pkgmatch['vorpal']." ) )";
  

  return $pkgmatch;
}

?>
