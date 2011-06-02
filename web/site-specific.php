<?php
# Copyright 2006, 2007, 2008 Ohio Supercomputer Center
# Copyright 2008, 2009, 2010, 2011 University of Tennessee
# Revision info:
# $HeadURL$
# $Revision$
# $Date$

# The site-specific logic of the reporting system goes here!
# Below are settings for OSC and NICS.

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

# list of all possible 'system' values to do reports on
# NOTE:  This does *NOT* necessary need to have a 1:1 correspondence
#        with the distinct values of the system column in the Jobs DB!
#        Look at the mck and ipf sections of sysselect for examples
#        of how to subset a system based on the hostnames of the compute
#        nodes.
function sys_list()
{
#  return array("krakenpf","kraken","athena","verne");
  return array("amd",
	       "apple",
	       "bale",
	       "coe",
	       "ipf",
	       "ipf-altix",
	       "ipf-noaltix",
	       "ipf-myri",
	       "ipf-oldmyri",
	       "ipf-newmyri",
	       "ipf-bigmem",
	       "ipf-serial",
	       "ipf-parallel",
	       "ipf-smp",
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
	       "opt",
	       "piv",
	       "piv-ib",
	       "piv-noib",
	       "piv-serial",
	       "piv-parallel",
	       "x1");
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
  if ( $system=='krakenpf' ) return 66048;
  if ( $system=='jaguar' ) return 31328;
  if ( $system=='verne' ) return 64;
  return 0;
}

function cpuhours($db,$system)
{
  $retval = "nproc*TIME_TO_SEC(walltime)/3600.0";
  if ( $system=="%" )
    {
      	# get list of systems
	$sql = "SELECT DISTINCT(system) FROM Jobs;";
	$result = db_query($db,$sql);
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
		$retval .= " WHEN '".$thissystem."' THEN ".cpuhours($db,$thissystem)."\n";
	      }
	  }
	$retval .= " END";
    }
  elseif ( $system=="nautilus" )
    {
      $retval = "8*nodect*TIME_TO_SEC(walltime)/3600.0";
    }
  else if ( $system=="kid" )
    {
      $retval = "12*nodect*TIME_TO_SEC(walltime)/3600.0";
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
  return "SUBSTRING(username,1,3) AS institution";
# NICS
#  return "SUBSTRING(account,1,2) AS institution";
}

# bucket sizes
function bucket_maxs($xaxis)
{
  if ( $xaxis=='nproc' ) return array("1","4","8","16","32","64","128","256","512","1024");
  if ( $xaxis=='nproc_norm' ) return array("0.01","0.10","0.25","0.5","0.75");
  if ( $xaxis=='walltime' ) return array("1:00:00","8:00:00","24:00:00","48:00:00","96:00:00","168:00:00","320:00:00");
  if ( $xaxis=='walltime_req' ) return array("1:00:00","8:00:00","24:00:00","48:00:00","96:00:00","168:00:00","320:00:00");
  if ( $xaxis=='qtime' ) return array("1:00:00","4:00:00","24:00:00","48:00:00","96:00:00","168:00:00","320:00:00");
  if ( $xaxis=='mem_kb' ) return array("262144","1048576","4194304","12582912","33554432");
  if ( $xaxis=='vmem_kb' ) return array("262144","1048576","4194304","12582912","33554432");
  return array();
}

# list of software packages to look for in job scripts
function software_list()
{
  $list=array(
	      "3dcavity",
	      "3dh",
	      "a_out",
	      "abaqus",
	      "abinit",
	      "accorrsf",
	      "aces2",
	      "aces3",
	      "adf",
	      "agk",
	      "AliEn",
	      "amber",
	      "anolis",
	      "ansys",
	      "arps",
	      "ash",
	      "autodock",
	      "awm",
	      "bam",
	      "blat",
	      "bolztran",
	      "brams-opt",
	      "bugget",
	      "cactus",
	      "calc_group_stats",
	      "cam",
	      "cando",
	      "casino",
	      "cbl",
	      "ccsm",
	      "cctm",
	      "cdp",
	      "cfd++",
	      "cfl3d",
	      "charmm",
	      "chemshell",
	      "chimera",
	      "chroma",
	      "cilk",
	      "cluster",
	      "coarsen",
	      "cpmd",
	      "cql3d",
	      "crystal",
	      "csurfs",
	      "dasquad",
#	      "decypher",
	      "delphi",
	      "delta5d",
	      "dissens",
	      "distuf",
              "dlpoly",
	      "dns2d",
	      "dock",
	      "dolt",
	      "dtms",
	      "enzo",
	      "esmf",
	      "eulacc",
	      "ex_e",
	      "f-plane",
	      "falkon",
	      "fd3d",
	      "fedvr",
	      "fidap",
	      "fdl3di",
	      "flotran",
	      "flow3d",
	      "fluent",
	      "foam",
	      "foxexe",
              "fsweep",
	      "ftb",
	      "gadget",
	      "gamess",
	      "gaussian",
	      "gdl",
	      "geosgcm",
	      "glast",
	      "GreenSolver",
	      "gromacs",
	      "gtc",
	      "h2mol",
	      "h3d",
	      "halo",
	      "harness",
	      "hd_nonuma",
	      "hfodd",
	      "hmc",
	      "hmmer",
	      "homme",
	      "hpcc",
	      "hpl",
	      "hsi",
	      "hy3s",
	      "idl",
	      "ifs",
	      "imb",
	      "inca",
	      "intestine3d",
	      "jaguar",
	      "lammps",
	      "lmf",
	      "lsdyna",
	      "lsms",
	      "m2md",
	      "madness",
	      "mathematica",
	      "matlab",
	      "mcrothers",
	      "mctas",
	      "mddriver",
	      "mdsim",
	      "measurements",
	      "meep",
	      "meta",
	      "mhd3d",
	      "mhdam",
              "milc",
	      "mitgcmuv",
	      "mm5",
	      "molaf3di",
	      "molcas",
	      "moldife",
	      "moldive",
	      "molpro",
	      "mpcugles",
	      "mpi_dgels",
	      "mpi_dgesv",
	      "mpi_dpos"
	      "mpi-multi",
	      "mpiasm",
	      "mpiblast",
	      "mrbayes",
	      "nag",
	      "mrobb6dipzz",
	      "mykim9dgt",
	      "namd",
	      "ncbi",
	      "nektar",
	      "newseriesrun",
	      "nga_cfb",
	      "nicam",
	      "ntsolve",
	      "nwchem",
	      "octave",
	      "omen",
	      "onepartm",
#	      "openeye",
	      "overlap",
#	      "param",
	      "paratec",
	      "paup",
	      "pcg",
	      "perseus",
	      "phasta"
	      "pkdgrav",
	      "pmcl3d",
              "polly",
	      "pop",
	      "preps"
	      "prog_ccm_sph",
	      "prog_hf",
              "propagators",
	      "psolve",
	      "pstg",
	      "pwscf",
	      "python",
	      "qb",
              "qchem",
	      "qmc",
	      "qwalk",
	      "R",
	      "radhyd",
	      "rosetta",
	      "root",
	      "s3d",
	      "sable",
	      "sas",
	      "scalapack",
	      "sddt",
	      "sfeles",
	      "sigma",
	      "simpleio",
	      "sne3d",
	      "sord",
	      "sovereign",
	      "spdcp",
	      "sses",
	      "stata",
	      "stationaryAccretionShock3D",
	      "sweqx",
	      "tdcc2d",
	      "tdse",
	      "tetradpost",
	      "testpio",
	      "tfe",
	      "tsc",
	      "turbo",
	      "turbomole",
	      "upc",
	      "vasp",
	      "vbc",
	      "vhone",
	      "visit",
	      "vmd",
	      "vpic",
              "wrf",
	      "xgc",
	      "xmfdn"
              "yt",
	      "zeus",
	      "zk3",
	      "zNtoM"
	      );
  
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
      $pkgmatch[$pkg]="script LIKE '%".$pkg."%' OR software LIKE '%".$pkg."%'";
    }

  # exceptions
  # REGEXP match is ***MUCH*** slower than regular LIKE matching
  # in MySQL, so don't use REGEXP unless you really need it.
  $pkgmatch['3dh']="script LIKE '%./3dh%'";
  $pkgmatch['a_out']="script LIKE '%a.out%'";
  $pkgmatch['abaqus']="( script LIKE '%abaqus%' OR script LIKE '%abaqus%' )";
  $pkgmatch['abinit']="( script LIKE '%abinis%' OR script LIKE '%abinip%' )";
  $pkgmatch['aces2']="script LIKE '%xaces2%'";
  $pkgmatch['adf']="script LIKE '%adf%'";
  $pkgmatch['AliEn']="( script LIKE '%aliroot%' OR script LIKE '%agent.startup%' )";
  $pkgmatch['amber']="( script LIKE '%amber%' OR script LIKE '%sander%' OR script LIKE '%pmemd%' OR script LIKE '%sviol%' OR script LIKE '%SingleJob%' OR script LIKE '%MINJob%' OR script LIKE '%run_md_mpi.csh%' OR script LIKE '%./MD%' )";
  $pkgmatch['ash']="script LIKE '%/ash%'";
  $pkgmatch['blat']="script LIKE '%blat %'";
  $pkgmatch['boltztran']="(script LIKE '%boltzpar%')";
  $pkgmatch['cbl']="( script LIKE '% cbl%' OR script LIKE '%pcbl%' OR script LIKE '%biolib%' )";
  $pkgmatch['ccsm']="( script LIKE '%ccsm%' OR script LIKE '%cpl%csim%clm%pop%cam%' )";
  $pkgmatch['chemshell']="script LIKE '%chemsh%'";
  $pkgmatch['cpmd']="script LIKE '%cpmd.x%'";
  $pkgmatch['crystal']="script LIKE '%Pcrystal%'";
  $pkgmatch['decypher']="script REGEXP '(decypher|dc_(target|make|blast|phrap)|TimeLogic)'";
  $pkgmatch['dissens']="script LIKE '%dissens.x%'";
  $pkgmatch['dns2d']="( script LIKE '%DNS2d.x%' OR script LIKE '%DNS2d_%.x%' OR script LIKE '%code2.x%' OR script LIKE '%spcal2d.x%' )";
  $pkgmatch['dock']="( script LIKE '%dock5%' OR script LIKE '%dock6%' OR script LIKE '%sphgen%' OR script LIKE '%mopac%' )";
  $pkgmatch['ex_e']="script LIKE '%ex.e%'";
  $pkgmatch['fluent']="( script like '%fluent%' OR software LIKE '%fluent%' )";
  $pkgmatch['fsweep']="( script LIKE '%fsweep.exe%' OR script LIKE '%fsweep2.exe%' )";
  $pkgmatch['gamess']="( script LIKE '%gamess%' OR script LIKE '%rungms%' OR script LIKE '%rungmx%' )";
  $pkgmatch['gaussian']="( script LIKE '%g98%' OR script LIKE '%g03%' OR script LIKE '%g09%' )";
  $pkgmatch['glast']="( script LIKE '%glast%' OR script LIKE '%gp run%' )";
  $pkgmatch['gromacs']="( script LIKE '%gromacs%' OR script LIKE '%grompp%' OR script LIKE '%mdrun%' OR script LIKE '%rgmx%' )";
  $pkgmatch['gtc']="( script LIKE '%gtc%' OR script LIKE '%gts%' )";
  $pkgmatch['harness']="script LIKE '%test_harness_driver.py%'";
  $pkgmatch['hmmer']="( script LIKE '%hmmer%' OR script LIKE '%hmmp%' )";
  $pkgmatch['hpl']="script LIKE '%xhpl%'";
  $pkgmatch['ifs']="script LIKE '%ifsMASTER%'";
  $pkgmatch['imb']="script LIKE '%IMB-%'";
  $pkgmatch['lammps']="( script LIKE '%lammps%' OR script LIKE '% lmp_%' OR script LIKE '%/lmp_%' )";
  $pkgmatch['madness']="( script LIKE '%m-a-d-n-e-s-s%' OR script LIKE '%slda%' )";
  $pkgmatch['matlab']="( script LIKE '%matlab%' OR software LIKE '%matlab%' )";
  $pkgmatch['meta']="( script LIKE '%anti.meta%' OR script LIKE '%para.meta%' OR script LIKE '%xray.meta%' )";
  $pkgmatch['mm5']="( SCRIPT LIKE '%mm5%' AND NOT SCRIPT LIKE '%womm5%' )";
  $pkgmatch['mrbayes']="( script LIKE '%mrbayes%' OR script LIKE '%mb-parallel%' )";
  $pkgmatch['ncbi']="( script LIKE '%ncbi%' OR script LIKE '%blastall%' OR script LIKE '%blastpgp%' OR script LIKE '%fastacmd%' OR script LIKE '%formatdb%' OR script LIKE '%rpsblast%' OR script LIKE '%seqtest%' )";
  $pkgmatch['openeye']="( script LIKE '%babel3%' OR script LIKE '%checkcff%' OR script LIKE '%chunker%' OR script LIKE '%fred2%' OR script LIKE '%fredPA%' OR script LIKE '%ligand_info%' OR script LIKE '%makefraglib%' OR script LIKE '%makerocsdb%' OR script LIKE '%nam2mol%' OR script LIKE '%omega2%' OR script LIKE '%szybki%' )";
  $pkgmatch['pwscf']="( script LIKE '%pwscf%' OR script LIKE '%pw.x%' OR script LIKE '%dos.x%' )";
  $pkgmatch['rosetta']="( script LIKE '%rosetta.%' OR script LIKE '%/rr %' )";
  $pkgmatch['root']="script LIKE '%\nroot -q%'";
  $pkgmatch['sable']="( script LIKE '%sable%' AND script NOT LIKE '%DISABLE%' )";
  $pkgmatch['sas']="( script LIKE '%\nsas%' OR software LIKE '%sas%' OR queue  LIKE '%sas%' )";
  $pkgmatch['tbms']="( script LIKE '%tbms%dvm%' OR script LIKE '%distr%dvm%' OR script LIKE '%jac%dvm%' OR script LIKE '%mt%dvm%' )";
  $pkgmatch['turbo']="script LIKE '%pturbo.x%'";
  $pkgmatch['upc']="script LIKE '%upcrun%'";
  $pkgmatch['vasp']="script LIKE '%vasp%'";
  $pkgmatch['vpic']="( script LIKE '%npic%' OR script LIKE '%open.cxx.op%' )";
  $pkgmatch['zNtoM']="( script LIKE '%z1to3%' OR script LIKE '%z4to6%' OR script LIKE '%z7to9%' OR script LIKE '%z10to12%' OR script LIKE '%z13to15%' )";

# package matches with dependencies on other package matches
  $pkgmatch['R']="( script LIKE '%\nR %' AND NOT ( ".$pkgmatch['gaussian'].
    " OR ".$pkgmatch['adf']." ) )";
  $pkgmatch['arps'] = "( script LIKE '%arps%' AND NOT ( ".$pkgmatch['wrf']." ) )";
  $pkgmatch['cam'] = "( script LIKE '%cam%' AND NOT ( ".$pkgmatch['ccsm']." ) )";
  $pkgmatch['cdp'] = "( script LIKE '%cdp%' AND NOT ( ".$pkgmatch['ifs']." ) )";
  $pkgmatch['charmm'] = "( script LIKE '%charmm%' AND NOT ( ".$pkgmatch['chemshell']." ) )";
  $pkgmatch['hmc']="( ( script LIKE '%hmc%' OR script LIKE '%./run_%.sh%' ) AND NOT ( ".$pkgmatch['namd']." ) )";
  $pkgmatch['hsi']="( ( script LIKE '%hsi %' OR script LIKE '%htar %' ) AND NOT ( ".$pkgmatch['nicam']." ) AND NOT ( ".$pkgmatch['ifs']." ) )";
  $pkgmatch['hy3s']="( ( script LIKE '%SSA%' OR script LIKE '%HyJCMSS-%' ) AND NOT ( ".$pkgmatch['ccsm']." ) AND NOT ( ".$pkgmatch['milc']." ) AND NOT ( ".$pkgmatch['wrf']." ) AND NOT ( ".$pkgmatch['nicam']." ) )";
  $pkgmatch['milc']="( ( script LIKE '%milc%' OR script LIKE '%su3_%' OR script LIKE '%switch%.csh%' ) AND NOT ( ".$pkgmatch['nicam']." ) )";
  $pkgmatch['omen'] = "( script LIKE '%omen%' AND NOT ( ".$pkgmatch['milc']." ) )";
  $pkgmatch['overlap']="( script LIKE '%overlap_%' AND NOT ( ".$pkgmatch['nicam']." ) )";
  $pkgmatch['pop'] = "( script LIKE '%pop%' AND NOT ( ".$pkgmatch['ccsm']." ) AND NOT ( ".$pkgmatch['hmc']." ) )";
  $pkgmatch['qb'] = "( script LIKE '%qb%' AND NOT ( ".$pkgmatch['milc']." ) AND NOT ( ".$pkgmatch['amber']." ) )";
  $pkgmatch['radhyd'] = "( script LIKE '%radhyd%' AND NOT ( ".$pkgmatch['chimera']." ) )";
  $pkgmatch['turbomole'] = "( script LIKE '%turbomole%' AND NOT ( ".$pkgmatch['chemshell']." ) )";
  
  return $pkgmatch;
}

?>
