<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL: https://svn.osc.edu/repos/pbstools/trunk/web/dbutils.php $
# $Revision: 136 $
# $Date: 2006-07-12 18:28:58 -0400 (Wed, 12 Jul 2006) $

# Database abstraction module to reduce the duplication of DB access code
# in the various other reports.
require_once 'DB.php';

function db_connect()
{  
  $db_type="mysql";
  $db_host="localhost";
  $db_database="pbsacct";
  $db_user="webapp";
  $db = DB::connect($db_type."://".$db_user."@".$db_host."/".$db_database, FALSE);
  if ( DB::isError($db) )
    {
      die ($db->getMessage());
    }
  else
    {
      return $db;
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