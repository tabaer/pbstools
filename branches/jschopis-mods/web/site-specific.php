<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$

# The site system logic of the reporting system goes here!

# list of all possible 'system' values to do reports on
# NOTE:  This does *NOT* necessary need to have a 1:1 correspondence
#        with the distinct values of the system column in the Jobs DB!
#        Look at the mck and ipf sections of sysselect for examples
#        of how to subset a system based on the hostnames of the compute
#        nodes.
function sys_list()
{
  return array("amd",
	       "apple",
	       "coe",
	       "ipf",
	       "ipf-altix",
	       "ipf-noaltix",
	       "ipf-myri",
	       "ipf-oldmyri",
	       "ipf-newmyri",
	       "ipf-bigmem",
	       "ipf+mck",
	       "ipf+mck-altix",
	       "ipf+mck-noaltix",
	       "ipf+mck-oldmyri",
	       "ipf+mck-bigmem",
	       "mck",
	       "mck-altix",
	       "mck-noaltix",
	       "mck-myri",
	       "mck-bigmem",
	       "piv",
	       "piv-ib",
	       "piv-noib",
	       "x1");
}

# system selector
function sysselect($system)
{
  if ( $system=='amd' ) return "system = 'amd'";
  if ( $system=='apple' ) return "system = 'apple'";
  if ( $system=='coe' ) return "system = 'coe'";
  if ( $system=='ipf' ) return "system = 'ipf'";
  if ( $system=='ipf-altix' ) return "system = 'ipf' AND hostlist REGEXP '^ipf50[1-3]'";
  if ( $system=='ipf-noaltix' ) return "system = 'ipf' AND hostlist NOT REGEXP '^ipf50[1-3]'";
  if ( $system=='ipf-oldmyri' ) return "system = 'ipf' AND hostlist REGEXP '^ipf(0[0-9][0-9]|1([0-1][0-9]|2[0-8]))'";
  if ( $system=='ipf-newmyri' ) return "system = 'ipf' AND hostlist REGEXP '^ipf(1(49|[5-9][0-9])|2([0-4][0-9]|5[0-8]))'";
  if ( $system=='ipf-myri' ) return "(".sysselect('ipf-oldmyri').") OR (".sysselect('ipf-newmyri').") ";
  if ( $system=='ipf-bigmem' ) return "system = 'ipf' AND hostlist REGEXP '^ipf1(29|3[0-9]|4[0-8])'";
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
  if ( $system=='piv' ) return "system = 'piv'";
  if ( $system=='piv-ib' ) return "system = 'piv' AND hostlist REGEXP '^piv(0[0-9][0-9]|1(0[0-9]|1[0-2]))'";
  if ( $system=='piv-noib' ) return "system = 'piv' AND hostlist NOT REGEXP '^piv(0[0-9][0-9]|1(0[0-9]|1[0-2]))'";
  if ( $system=='x1' ) return "system = 'x1'";
  return "system LIKE '".$system."'";
}

# processors per system
function nprocs($system)
{
  if ( $system=='amd' ) return 256;
  if ( $system=='apple' ) return 64;
  if ( $system=='coe' ) return 60;
  if ( $system=='ipf' ) return nprocs('ipf-noaltix')+nprocs('ipf-altix');
  if ( $system=='ipf-altix' ) return 64;
  if ( $system=='ipf-noaltix' ) return nprocs('ipf-myri')+nprocs('ipf-bigmem');
  if ( $system=='ipf-oldmyri' ) return 256;
  if ( $system=='ipf-newmyri' ) return 220;
  if ( $system=='ipf-bigmem' ) return 40;
  if ( $system=='ipf-myri' ) return nprocs('ipf-oldmyri')+nprocs('ipf-newmyri');
  if ( $system=='ipf+mck' ) return nprocs('ipf');
  if ( $system=='ipf+mck-altix' ) return nprocs('ipf-altix');
  if ( $system=='ipf+mck-noaltix' ) return nprocs('ipf-noaltix');
  if ( $system=='ipf+mck-bigmem' ) return nprocs('ipf-bigmem');
  if ( $system=='ipf+mck-oldmyri' ) return nprocs('ipf-oldmyri');
  if ( $system=='mck' ) return 328;
  if ( $system=='mck-altix' ) return 32;
  if ( $system=='mck-noaltix' ) return nprocs('mck-myri')+nprocs('mck-bigmem');
  if ( $system=='mck-bigmem' ) return 40;
  if ( $system=='mck-myri' ) return 256;
  if ( $system=='piv' ) return nprocs('piv-ib')+nprocs('piv-noib');
  if ( $system=='piv-ib' ) return 224;
  if ( $system=='piv-noib' ) return 288;
  if ( $system=='x1' ) return 48;
  return 0;
}

# sorting criteria for each metric
# here mostly as an example of what's possible
function sort_criteria($fn)
{
  #  if ( $fn=='cpuhours_vs_groupname' ) return "ORDER BY cpuhours DESC";
  return "";
}

# list of software packages to look for in job scripts
function software_list()
{
  $list=array("a_out",
	      "abaqus",
	      "adf",
	      "amber",
	      "ansys",
	      "blat",
	      "cbl",
	      "decypher",
	      "fidap",
	      "fdl3di",
	      "flow3d",
	      "fluent",
	      "gaussian",
	      "gamess",
	      "gromacs",
	      "lsdyna",
	      "mathematica",
	      "matlab",
	      "mrbayes",
	      "NAG",
	      "namd",
	      "NCBI",
	      "nwchem",
	      "octave",
	      "R",
	      "sable",
	      "sas",
	      "scalapack",
	      "TURBO",
	      "turbomole",
	      "vasp");
  
  return $list;
}

# REs to identify particular software packages in job scripts
# if a RE is not specified, the package name from software_list()
# is searched for instead
function software_match_list()
{
  # default to "script LIKE '%pkgname%'
  foreach (software_list() as $pkg)
    {
      $pkgmatch[$pkg]="script LIKE '%".$pkg."%'";
    }

  # exceptions
  $pkgmatch['a_out']="script LIKE '%a.out%'";
  $pkgmatch['adf']="script LIKE '%ADF%' OR script LIKE '%adf%'";
  $pkgmatch['amber']="script REGEXP '(amber|sander|pmemd|sviol)'";
  $pkgmatch['cbl']="script REGEXP '(cbl|pcbl|biolib)'";
  $pkgmatch['decypher']="script REGEXP '(decypher|dc_(target|make|blast|phrap)|TimeLogic)'";
  $pkgmatch['gamess']="script LIKE '%gamess%' OR script LIKE '%rungmx%'";
  $pkgmatch['gaussian']="script LIKE '%g98%' OR script LIKE '%g03%'";
  $pkgmatch['gromacs']="script LIKE '%gromacs%' OR script LIKE '%mdrun_d%'";
  $pkgmatch['mrbayes']="script LIKE '%mrbayes%' OR script LIKE '%mb-parallel%'";
  $pkgmatch['NCBI']="script REGEXP '(ncbi|blastall|fastacmd|formatdb|rpsblast|seqtest)'";
  $pkgmatch['R']="script LIKE '%\nR %' AND NOT ( ".$pkgmatch['gaussian'].
    " OR ".$pkgmatch['adf']." )";
  $pkgmatch['sable']="script LIKE '%sable%' AND script NOT LIKE '%DISABLE%'";
  $pkgmatch['sas']="script LIKE '%\nsas%'";
  $pkgmatch['TURBO']="script LIKE '%pturbo.x%'";
  $pkgmatch['vasp']="script LIKE '%VASP%' OR script LIKE '%vasp%'";
  
  return $pkgmatch;
}

?>
