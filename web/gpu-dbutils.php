<?php
# Copyright 2006, 2008 Ohio Supercomputer Center
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/dbutils.php $
# $Revision: 136 $
# $Date: 2006-07-12 18:28:58 -0400 (Wed, 12 Jul 2006) $

# Database abstraction module to reduce the duplication of DB access code
# in the various other reports.
require_once 'DB.php';

function db_connect()
{
  $cfg=array();
  $cfgfile = "gpu-db.cfg";
  if ( is_readable($cfgfile) )
    {
      $fp = fopen($cfgfile,"r");
      while ( $line = fgets($fp) )
	{
	  if ( !preg_match('/^#/',$line) )
	    {
	      $token = preg_split('/ *= */',$line);
	      if ( count($token)==2 )
		{
		  $cfg[rtrim($token[0])] = rtrim($token[1]);
		}
	    }
	}
      fclose($fp);
    }
  $db_type="pgsql";
  if ( isset($cfg['dbtype']) )
    {
      $db_type=$cfg['dbtype'];
    }
  $db_host="kidserv2.nics.utk.edu";
  if ( isset($cfg['dbhost']) )
    {
      $db_host=$cfg['dbhost'];
    }
  $db_database="keeneland_stats";
  if ( isset($cfg['database']) )
    {
      $db_database=$cfg['database'];
    }
  $db_user="tsamuel";
  if ( isset($cfg['dbuser']) )
    {
      $db_user=$cfg['dbuser'];
    }
  if ( $db_type!="" && $db_host!="" && $db_database!="" && $db_user!="" )
    {
      #$db = DB::connect($db_type."://".$db_user."@".$db_host."/".$db_database, FALSE);
      #echo '$db = DB::connect($db_type."://".$db_user."@".$db_host."/".$db_database, FALSE);';
      $db = DB::connect("pgsql"."://"."tsamuel"."@"."kidserv2.nics.utk.edu"."/"."keeneland_stats", FALSE);
      
      #$db = DB:connect("pgsql://tsamuel@kidserv2.nics.utk.edu/keeneland_stats", FALSE);
      if ( DB::isError($db) )
	{
	  die($db->getMessage());
	}
      else
	{
	  return $db;
	}
    }
  else
    {
      die("Incomplete configuration!");
    }
}

function db_query($db,$query)
{
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


function db_disconnect($db)
{
  $db->disconnect();
}

?>
