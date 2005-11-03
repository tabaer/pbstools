<?php
  // The site system logic of the reporting system goes here!

  // list of all possible 'system' values to do reports on
  // NOTE:  This does *NOT* necessary need to have a 1:1 correspondence
  //        with the distinct values of the system column in the Jobs DB!
  //        Look at the mck and ipf sections of sysselect for examples
  //        of how to subset a system based on the hostnames of the compute
  //        nodes.
function sys_list()
{
  return array("amd","apple","coe","ipf","ipf-altix","ipf-noaltix","mck","mck-altix",
	       "mck-noaltix","piv","x1");
}

  // system selector
function sysselect($system)
{
  if ( $system=='amd' ) return "system = 'amd'";
  if ( $system=='apple' ) return "system = 'apple'";
  if ( $system=='coe' ) return "system = 'coe'";
  if ( $system=='ipf' ) return "system = 'ipf'";
  if ( $system=='ipf-altix' ) return "system = 'ipf' AND hostlist REGEXP '^ipf50[1-3]'";
  if ( $system=='ipf-noaltix' ) return "system = 'ipf' AND hostlist NOT REGEXP '^ipf50[1-3]'";
  if ( $system=='mck' ) return "system = 'mck'";
  if ( $system=='mck-altix' ) return "system = 'mck' AND hostlist REGEXP '^mck149'";
  if ( $system=='mck-noaltix' ) return "system = 'mck' AND hostlist NOT REGEXP '^mck149'";
  if ( $system=='piv' ) return "system = 'piv'";
  if ( $system=='x1' ) return "system = 'x1'";
  return "system LIKE '".$system."'";
}

// processors per system
function nprocs($system)
{
  if ( $system=='amd' ) return 256;
  if ( $system=='apple' ) return 64;
  if ( $system=='coe' ) return 60;
  if ( $system=='ipf' ) return 580;
  if ( $system=='ipf-altix' ) return 64;
  if ( $system=='ipf-noaltix' ) return 516;
  if ( $system=='mck' ) return 328;
  if ( $system=='mck-altix' ) return 32;
  if ( $system=='mck-noaltix' ) return 296;
  if ( $system=='piv' ) return 512;
  if ( $system=='x1' ) return 48;
  return 0;
}

function sort_criteria($fn)
{
  //  if ( $fn=='cpuhours_vs_groupname' ) return "ORDER BY cpuhours DESC";
  return "";
}

?>