#!/usr/bin/python
# This module provides functions for parsing PBS accounting logs for use by 
# various scripts
#
# Copyright 2016, 2017 Ohio Supercomputer Center
# Authors:  Aaron Maharry
#           Troy Baer <troy@osc.edu>
#
# License:  GNU GPL v2, see ../COPYING for details.

import datetime
import gzip
import os
import re
import sys

class jobinfo:
    def __init__(self,jobid,update_time,state,resources,system=None):
        self._jobid = jobid
        self._updatetimefmt = "%m/%d/%Y %H:%M:%S"
        self._updatetime = datetime.datetime.strptime(update_time,self._updatetimefmt)
        self._state = state
        self._resources = {}
        self._nproc = 0
        self._nnodes = 0
        self._ngpus = 0
        for key in resources.keys():
            self._resources[key] = resources[key]
        if ( not self._resources.has_key("system") ):
            self._resources["system"] = system

    def __eq__(self,other):
        if ( not isinstance(other,jobinfo) ):
            return False
        if ( self.jobid()!=other.jobid() or
             self.get_state()!=other.get_state() or
             self.get_update_time()!=other.get_update_time() ):
            return False
        ignore_rsrcs = ["qtime",
                        "Resource_List.other",
                        "resources_used.energy_used",
                        "script",
                        "session",
                        "system",
                        "total_execution_slots",
                        "unique_node_count"]
        for key in list(set(self.resource_keys()) | set(other.resource_keys())):
            if ( key not in ignore_rsrcs ):
                if ( not self.has_resource(key) or 
                     not other.has_resource(key) ):
                    return False
                elif ( key in ["resources_used.cput","resources_used.walltime"] and
                       time_to_sec(self.get_resource(key))!=time_to_sec(other.get_resource(key)) ):
                    return False
                elif ( self.get_resource(key)!=other.get_resource(key) ):
                    return False
        return True

    def __repr__(self):
        output  = "jobid %s {\n" % self.jobid()
        output += "\tlast_state = %s\n" % self.get_state()
        output += "\tlast_update_time = %s (%d)\n" % (str(self.get_update_time()),self.get_update_time_ts())
        output += "\tjobname = %s\n" % self.name()
        output += "\tqueue = %s\n" % self.queue()
        output += "\tuser = %s\n" % self.user()
        output += "\tgroup = %s\n" % self.group()
        output += "\taccount = %s\n" % self.account()
        if ( self.ctime_ts()>0 ):
            output += "\tctime = %s (%d)\n" % (str(self.ctime()),self.ctime_ts())
        if ( self.qtime_ts()>0 ):
            output += "\tqtime = %s (%d)\n" % (str(self.qtime()),self.qtime_ts())
        if ( self.etime_ts()>0 ):
            output += "\tetime = %s (%d)\n" % (str(self.etime()),self.etime_ts())
        if ( self.start_ts()>0 ):
            output += "\tstart = %s (%d)\n" % (str(self.start()),self.start_ts())
        if ( self.end_ts()>0 ):
            output += "\tend = %s (%d)\n" % (str(self.end()),self.end_ts())
        output += "\tnproc = %d\n" % self.num_processors()
        if ( self.nodes() is not None ):
            output += "\tnodes = %s\n" % self.nodes()
            output += "\tnodect = %d\n" % self.num_nodes()
            output += "\tnodes_used = %s\n" % str(self.nodes_used())
        if ( self.num_gpus()>0 ):
            output += "\tngpus = %d\n" % self.num_gpus()
        if ( self.feature() is not None ):
            output += "\tfeature = %s\n" % self.feature()
        if ( self.gattr() is not None ):
            output += "\tgattr = %s\n" % self.gattr()
        if ( self.gres() is not None ):
            output += "\tgres = %s\n" % self.gres()
        if ( self.software() is not None ):
            output += "\tsoftware = %s\n" % self.software()
        if ( self.other() is not None ):
            output += "\tother = %s\n" % self.other()
        if ( self.mem_used_kb()>0 ):
            output += "\tmem_used (kb) = %d\n" % self.mem_used_kb()
            output += "\tmem_limit (kb) = %d\n" % self.mem_limit_kb()
        if ( self.vmem_used_kb()>0 ):
            output += "\tvmem_used (kb) = %d\n" % self.vmem_used_kb()
            output += "\tvmem_limit (kb) = %d\n" % self.vmem_limit_kb()
        if ( self.walltime_used_sec()>0 ):
            output += "\twalltime_used = %s (%d)\n" % (sec_to_time(self.walltime_used_sec()),self.walltime_used_sec())
            output += "\twalltime_limit = %s (%d)\n" % (sec_to_time(self.walltime_limit_sec()),self.walltime_limit_sec())
        if ( self.cput_used_sec()>0 ):
            output += "\tcput_used = %s (%d)\n" % (sec_to_time(self.cput_used_sec()),self.cput_used_sec())
            output += "\tcput_limit = %s (%d)\n" % (sec_to_time(self.cput_limit_sec()),self.cput_limit_sec())
        if ( self.exit_status() is not None ):
            output += "\texit_status = %d\n" % self.exit_status()
        output += "}"
        return output

    def get_update_time(self):
        return self._updatetime

    def get_update_time_ts(self):
        return int(self._updatetime.strftime("%s"))

    def set_update_time(self,update_time):
        self._updatetime = datetime.datetime.strptime(update_time,self._updatetimefmt)

    def get_state(self):
        return self._state

    def set_state(self,state):
        self._state = state

    def get_resources(self):
        return self._resources

    def get_resource_keys(self):
        return self._resources.keys()

    def get_resource(self,key):
        if ( self.has_resource(key) ):
            return self._resources[key]
        else:
            return None

    def set_resource(self,key,value):
        self._resources[key] = value

    def has_resource(self,key):
        return self._resources.has_key(key)

    def add_to_resource(self,key,value):
        supported_time_resources = ["resources_used.cput","resources_used.walltime"]
        if ( key in supported_time_resources and 
             not self.has_resource(key) ):
            self._resources[key] = value
        elif ( key in supported_time_resources ):
            oldval = time_to_sec(self._resources[key])
            incr = time_to_sec(value)
            self._resources[key] = sec_to_time(oldval+incr)
        else:
            raise ValueError("Resource \""+key+"\" not supported for addition")

    def jobid(self):
        return self._jobid

    def numeric_jobid(self):
        """
        Returns the numeric job id (i.e. without the hostname, if any)
        
        Input is of the form: 6072125.oak-batch.osc.edu
        Output is of the form: 6072125
        """
        if ( '[' in self._jobid ):
            # this is an array job (###[#]), so return the "master" jobid
            return int((self._jobid.split(".")[0])[0:self._jobid.index('[')])
        else:
            return int(self._jobid.split(".")[0])

    def system(self):
        return self.get_resource("system")

    def name(self):
        return self.get_resource("jobname")

    def queue(self):
        return self.get_resource("queue")

    def user(self):
        return self.get_resource("user")

    def group(self):
        return self.get_resource("group")

    def account(self):
        return self.get_resource("account")

    def owner(self):
        return self.get_resource("owner")

    def submithost(self):
        owner = self.owner()
        if ( owner is None or 
             '@' not in owner or
             len(owner.split('@'))>2 ):
            return None
        else:
            return owner.split('@',1)[1]

    def ctime(self):
        if ( self.has_resource("ctime") ):
            return datetime.datetime.fromtimestamp(int(self.get_resource("ctime")))
        else:
            raise RuntimeError("Job "+self._jobid+" has no ctime set")

    def qtime(self):
        if ( self.has_resource("qtime") ):
            return datetime.datetime.fromtimestamp(int(self.get_resource("qtime")))
        else:
            raise RuntimeError("Job "+self._jobid+" has no qtime set")

    def etime(self):
        if ( self.has_resource("etime") ):
            return datetime.datetime.fromtimestamp(int(self.get_resource("etime")))
        else:
            raise RuntimeError("Job "+self._jobid+" has no etime set")

    def start(self):
        if ( self.has_resource("start") ):
            return datetime.datetime.fromtimestamp(int(self.get_resource("start")))
        else:
            raise RuntimeError("Job "+self._jobid+" has no start time set")

    def end(self):
        if ( self.has_resource("end") ):
            return datetime.datetime.fromtimestamp(int(self.get_resource("end")))
        else:
            raise RuntimeError("Job "+self._jobid+" has no end time set")

    def ctime_ts(self):
        if ( self.has_resource("ctime") ):
            return int(self.get_resource("ctime"))
        else:
            return 0

    def qtime_ts(self):
        if ( self.has_resource("qtime") ):
            return int(self.get_resource("qtime"))
        else:
            return 0

    def etime_ts(self):
        if ( self.has_resource("etime") ):
            return int(self.get_resource("etime"))
        else:
            return 0

    def start_ts(self):
        if ( self.has_resource("start") ):
            return int(self.get_resource("start"))
        else:
            return 0

    def end_ts(self):
        if ( self.has_resource("end") ):
            return int(self.get_resource("end"))
        else:
            return 0

    def nodes(self):
        return self.get_resource("Resource_List.nodes")

    def nodes_used(self):
        nodes = []
        if ( self.has_resource("exec_host") ):
            for node_and_procs in self.get_resource("exec_host").split("+"):
                (node,procs) = node_and_procs.split("/")
                if ( node not in nodes ):
                    nodes.append(node)
        return nodes

    def num_nodes(self):
        if ( self._nnodes>0 ):
            return self._nnodes>0
        nnodes = 0
        if ( self.has_resource("unique_node_count")):
            # Added in TORQUE 4.2.9
            nnodes = int(self.get_resource("unique_node_count"))
        else:
            nnodes = len(self.nodes_used())
        self._nnodes = nnodes
        return self._nnodes

    def num_processors(self):
        """ Returns the total number of processors the job requires """
        if ( self._nproc>0 ):
            return self._nproc
        processors = 0
        if ( self.has_resource("total_execution_slots") ):
            # Added in TORQUE 4.2.9
            processors = int(self.get_resource("total_execution_slots"))
        elif ( self.has_resource("Resource_List.nodes") ):
            # Compute the nodes requested and the processors per node
            for nodelist in self.get_resource("Resource_List.nodes").split("+"):
                nodes_and_ppn = nodelist.split(":")
                try:
                    nodes = int(nodes_and_ppn[0])
                except:
                    # Handles malformed log values
                    nodes = 1
                if ( len(nodes_and_ppn)>=2 ):
                    try:
                        ppn = int(re.search("ppn=(\d+)", nodes_and_ppn[1]).group(1))
                    except AttributeError:
                        ppn = 1
                else:
                    ppn = 1
                nodes = max(1,nodes)
                ppn = max(1,ppn)
                processors = processors + nodes*ppn
            self._nproc = processors
            return self._nproc
        ncpus = 0
        if ( self.has_resource("Resource_List.ncpus") ):
            ncpus = max(ncpus,int(self.get_resource("Resource_List.ncpus")))
        if ( self.has_resource("resources_used.ncpus") ):
            ncpus = max(ncpus,int(self.get_resource("resources_used.ncpus")))
        if ( self.has_resource("Resource_List.mppssp") or 
             self.has_resource("resources_used.mppssp") or 
             self.has_resource("Resource_List.mppe") or
             self.has_resource("resources_used.mppe") ):
            # Cray SV1/X1 specific code
            # These systems could gang together 4 individual processors (SSPs)
            # in a virtual processor (MSPs).
            # This is admittedly rather weird and only of historical interest.
            ssps = 0
            if ( self.has_resource("Resource_List.mppssp") ):
                ssps = ssps + int(self.get_resource("Resource_List.mppssp"))
            elif ( self.has_resource("resources_used.mppssp") ):
                ssps = ssps + int(self.get_resource("resources_used.mppssp"))
            if ( self.has_resource("Resource_List.mppe") ):
                ssps = ssps + 4*int(self.get_resource("Resource_List.mppe"))
            elif ( self.has_resource("resources_used.mppe") ):
                ssps = ssps + 4*int(self.get_resource("resources_used.mppe"))
            ncpus = max(ncpus,ssps)
        if ( self.has_resource("Resource_List.size") ):
            ncpus = max(ncpus,int(self.get_resource("Resource_List.size")))
        # Return the larger of the two computed values
        self._nproc = max(processors,ncpus)
        return self._nproc

    def num_gpus(self):
        if ( self._ngpus>0 ):
            return self._ngpus
        ngpus = 0
        # sadly, there doesn't appear to be a more elegant way to do this
        if ( self.nodes() is not None and "gpus=" in self.nodes() ):
            # Compute the nodes requested and the processors per node
            for nodelist in self.nodes().split("+"):
                nodes_and_props = nodelist.split(":")
                try:
                    nodes = int(nodes_and_ppn[0])
                except:
                    # Handles malformed log values
                    nodes = 1
                gpn = 0
                if ( len(nodes_and_props)>=2 ):
                    for nodeprop in nodes_and_props[1:]:
                        if ( re.match("^gpus=(\d+)$", nodeprop) ):
                            gpn = int(re.search("^gpus=(\d+)$", nodeprop).group(1))
                nodes = max(1,nodes)
                gpn = max(0,gpn)
                ngpus = ngpus + nodes*gpn 
        elif ( self.gres() is not None and "gpus:" in self.gres() ):
            ngpus = int(re.search("gpus:(\d+)",self.gres()).group(1))
        self._ngpus = ngpus
        return self._ngpus

    def feature(self):
        return self.get_resource("Resource_List.feature")

    def gattr(self):
        return self.get_resource("Resource_List.gattr")

    def gres(self):
        return self.get_resource("Resource_List.gres")

    def other(self):
        return self.get_resource("Resource_List.other")

    def qos(self):
        return self.get_resource("Resource_List.qos")

    def software(self):
        return self.get_resource("Resource_List.software")

    def mem_used_kb(self):
        """ Return the amount of memory (in kb) used by the job """
        if ( self.has_resource("resources_used.mem") ):
            return int(re.sub("kb$", "", self._resources["resources_used.mem"]))
        else:
            return 0

    def vmem_used_kb(self):
        """ Return the amount of virtual memory (in kb) used by the job """
        if ( self.has_resource("resources_used.vmem") ):
            return int(re.sub("kb$", "", self.get_resource("resources_used.vmem")))
        else:
            return 0

    def mem_limit(self):
        if ( self.has_resource("Resource_List.mem") ):
            return self.get_resource("Resource_List.mem")
        else:
            return None

    def vmem_limit(self):
        if ( self.has_resource("Resource_List.vmem") ):
            return self.get_resource("Resource_List.vmem")
        else:
            return None

    def mem_limit_kb(self):
        if ( self.has_resource("Resource_List.mem") ):
            return mem_to_kb(self.get_resource("Resource_List.mem"))
        else:
            return 0

    def vmem_limit_kb(self):
        if ( self.has_resource("Resource_List.vmem") ):
            return mem_to_kb(self.get_resource("Resource_List.vmem"))
        else:
            return 0

    def walltime_used_sec(self):
        if ( self.has_resource("resources_used.walltime") ):
            return time_to_sec(self.get_resource("resources_used.walltime"))
        else:
            return 0

    def walltime_limit_sec(self):
        if ( self.has_resource("Resource_List.walltime") ):
            return time_to_sec(self.get_resource("Resource_List.walltime"))
        else:
            return 0
    
    def cput_used_sec(self):
        if ( self.has_resource("resources_used.cput") ):
            return time_to_sec(self.get_resource("resources_used.cput"))
        else:
            return 0

    def cput_limit_sec(self):
        if ( self.has_resource("Resource_List.cput") ):
            return time_to_sec(self.get_resource("Resource_List.cput"))
        else:
            return 0

    def energy_used(self):
        if ( self.has_resource("resources_used.energy_used") ):
            return int(self.get_resource("resources_used.energy_used"))
        else:
            return 0

    def exit_status(self):
        if ( self.has_resource("Exit_status") ):
            return int(self.get_resource("Exit_status"))
        else:
            return None


def raw_data_from_file(filename):
    """
    Parses a file containing multiple PBS accounting log entries. Returns a list
    of tuples containing the following information:

    (jobid, time, record_type, resources)

    Resources are returned in a dictionary containing entries for each 
    resource name and corresponding value
    """
    try:
        if re.search("\.gz$", filename):
            acct_data = gzip.open(filename)
        else:
            acct_data = open(filename)
    except IOError as e:
        print("ERROR: Failed to read PBS accounting log %s" % (filename))
        return []
    output = []
    for line in acct_data:
        
        # Get the fields from the log entry
        try:
            time, record_type, jobid, resources = line.split(";",3)
        except ValueError:
            print("ERROR:  Invalid number of fields (requires 4).  Unable to parse entry: %s" % (str(line.split(";",3))))
            continue
        
        # Create a dict for the various resources
        resources_dict = dict()
        for resource in resources.split(" "):
            match = re.match("^([^=]*)=(.*)", resource)
            if match:
                key = match.group(1)
                value = match.group(2)
                if key in ["ctime", "qtime", "etime", "start", "end"]:
                    value = int(value)
                if key in []:
                    value = float(value)
                resources_dict[key] = value
        
        # Store the data in the output
        output.append((jobid, time, record_type, resources_dict))
        #break
    acct_data.close()
    return output


def raw_data_from_files(filelist):
    """
    Parses a list of files containing multiple PBS accounting log entries.
    Returns a list of tuples containing the following information:

    (jobid, time, record_type, resources)

    Resources are returned in a dictionary containing entries for each 
    resource name and corresponding value
    """
    rawdata = []
    for filename in filelist:
        if ( os.path.exists(filename) ):
            for record in raw_data_from_file(filename):
                rawdata.append(record)
    return rawdata


def records_to_jobs(rawdata,system=None):
    """
    Processes an array containing multiple PBS accounting log entries.  Returns
    a hash of lightly postprocessed data (i.e. one entry per jobid rather
    than one per record).
    """
    output = {}
    for record in rawdata:
        jobid = record[0]
        update_time = record[1]
        record_type = record[2]
        resources = record[3]
        if ( not output.has_key(jobid) ):
            output[jobid] = jobinfo(jobid,update_time,record_type,resources,system)
        # may need an extra case here for jobs with multiple S and E
        # records (e.g. preemption)
        else:
            output[jobid].set_update_time(update_time)
            output[jobid].set_state(record_type)
            for key in resources.keys():
                output[jobid].set_resource(key,resources[key])
    return output


def jobs_from_file(filename,system=None):
    """
    Parses a file containing multiple PBS accounting log entries.  Returns
    a hash of lightly postprocessed data (i.e. one entry per jobid rather
    than one per record).
    """
    return jobs_from_files([filename],system)


def jobs_from_files(filelist,system=None):
    """
    Parses a list of files containing multiple PBS accounting log entries.
    Returns a hash of lightly postprocessed data (i.e. one entry per jobid
    rather than one per record).
    """
    return records_to_jobs(raw_data_from_files(filelist),system)


def time_to_sec(timestr):
    """
    Convert string time into seconds.
    """
    if ( timestr is None ):
        return 0
    elif ( isinstance(timestr,int) ):
        return timestr
    if ( not re.match("[\d:]+",timestr) ):
        raise ValueError("Malformed time \""+timestr+"\"")
    sec = 0
    elt = timestr.split(":")
    if ( len(elt)==1 ):
        # raw seconds -- TORQUE 5.1.2 did this on walltime and cput 
        # for some reason
        sec = int(elt[0])
    elif ( len(elt)==2 ):
        # mm:ss -- should be rare to nonexistent in TORQUE
        sec = 60*int(elt[0])+int(elt[1])
    elif ( len(elt)==3 ):
        # hh:mm:ss -- most common case
        sec = 3600*int(elt[0])+60*int(elt[1])+int(elt[2])
    elif ( len(elt)==4 ):
        # dd:hh:mm:ss -- not used in TORQUE, occasionally appears in Moab
        # output
        sec = 3600*(24*int(elt[0])+int(elt[1]))+60*int(elt[2])+int(elt[2])
    else:
        raise ValueError("Malformed time \""+timestr+"\"")
    return sec


def sec_to_time(seconds):
    if ( isinstance(seconds,str) ):
        return seconds
    hours = int(seconds/3600)
    minutes = int(seconds-3600*hours)/60
    sec = int(seconds-(3600*hours+60*minutes))
    return "%02d:%02d:%02d" % (hours,minutes,sec)


def mem_to_kb(memstr):
    match = re.match("^(\d+)([TtGgMmKk])([BbWw])$",memstr)
    if ( match is not None and len(match.groups())==3 ):
        number = int(match.group(1))
        multiplier = 1
        numbytes = 1
        factor = match.group(2)
        if ( factor in ["T","t"] ):
            multiplier = 1024*1024*1024
        elif ( factor in ["G","g"] ):
            multiplier = 1024*1024
        elif ( factor in ["M","m"] ):
            multiplier = 1024
        units = match.group(3)
        if ( units in ["W","w"] ):
            numbytes = 8
        return number*multiplier*numbytes
    elif ( re.match("^(\d+)([BbWw])$",memstr) ):
        match = re.match("^(\d+)([BbWw])$",memstr)
        number = int(match.group(1))
        numbytes = 1
        units = match.group(2)
        if ( units in ["W","w"] ):
            numbytes = 8
        return number*numbytes/1024
    else:
        raise ValueError("Invalid memory expression \""+memstr+"\"")


class pbsacctDB:
    def __init__(self, host=None, dbtype="mysql",
                 db="pbsacct", dbuser=None, dbpasswd=None,
                 jobs_table="Jobs", config_table="Config",
                 sw_table="Software",system=None):
        self.setServerName(host)
        self.setType(dbtype)
        self.setName(db)
        self.setUser(dbuser)
        self.setPassword(dbpasswd)
        self.setJobsTable(jobs_table)
        self.setConfigTable(config_table)
        self.setSoftwareTable(sw_table)
        self.setSystem(system)
        self._dbhandle = None
        self._cursor = None

    def setServerName(self, dbhost):
        self._dbhost = dbhost

    def getServerName(self):
        return self._dbhost

    def setType(self, dbtype):
        supported_dbs = ["mysql","pgsql"]
        if ( dbtype in supported_dbs ):
            self._dbtype = dbtype
        else:
            raise RuntimeError("Requested unimplemented database type \"%s\"" % dbtype)

    def getType(self):
        return self._dbtype

    def setName(self, dbname):
        self._dbname = dbname

    def getName(self):
        return self._dbname

    def setUser(self, dbuser):
        self._dbuser = dbuser

    def getUser(self):
        return self._dbuser

    def setPassword(self, dbpasswd):
        self._dbpasswd = dbpasswd

    def setJobsTable(self, jobs_table):
        self._jobstable = jobs_table

    def getJobsTable(self):
        return self._jobstable

    def setConfigTable(self, config_table):
        self._cfgtable = config_table

    def getConfigTable(self):
        return self._cfgtable
        
    def setSoftwareTable(self, sw_table):
        self._swtable = sw_table

    def getSoftwareTable(self):
        return self._swtable

    def setSystem(self, system):
        self._system = system

    def getSystem(self):
        return self._system
        
    def readConfigFile(self, cfgfilename):
        if ( not os.path.exists(cfgfilename) ):
            raise IOError("%s does not exist" % cfgfilename)
        cfgfile = open(cfgfilename)
        for line in cfgfile.readlines():
            if ( not line.startswith("#") and not re.match('^\s*$',line) ):
                try:
                    (keyword,value) = line.rstrip('\n').split("=")
                    if ( keyword=="dbhost" ):
                        self.setServerName(value)
                    elif ( keyword=="dbtype" ):
                        self.setType(value)
                    elif ( keyword=="dbname" ):
                        self.setName(value)
                    elif ( keyword=="dbuser" ):
                        self.setUser(value)
                    elif ( keyword=="dbpasswd" ):
                        self.setPassword(value)
                    elif ( keyword=="jobstable" ):
                        self.setJobsTable(value)
                    elif ( keyword=="configtable" ):
                        self.setConfigTable(value)
                    elif ( keyword=="softwaretable" ):
                        self.setSoftwareTable(value)
                    elif ( keyword=="system" ):
                        self.setSystem(value)
                    else:
                        raise RuntimeError("Unknown keyword \"%s\"" % keyword)
                except Exception as e:
                    sys.stderr.write(str(e))
                    pass

    def connect(self):
        if ( self._dbhandle is not None ):
            return self._dbhandle
        if ( self.getType()=="mysql" ):
            import MySQLdb
            self._dbhandle = MySQLdb.connect(host=self._dbhost,
                                             db=self._dbname,
                                             user=self._dbuser,
                                             passwd=self._dbpasswd)
            return self._dbhandle
        elif ( self.getType()=="pgsql" ):
            import psycopg2
            self._dbhandle = psycopg2.connect(host=self._dbhost,
                                              db=self._dbname,
                                              user=self._dbuser,
                                              passwd=self._dbpasswd)
            return self._dbhandle
        else:
            raise RuntimeError("Unimplemented database type \"%s\"" % self.getType())

    def close(self):
        self.connect().close()
        self._dbhandle = None
        self._cursor = None

    def commit(self):
        self.connect().commit()

    def cursor(self):
        if ( self._cursor is None ):
            self._cursor = self.connect().cursor()
        return self._cursor

    def rollback(self):
        self.connect().rollback()

    def job_exists(self,jobid,append_to_jobid=None):
        myjobid = jobid
        if ( append_to_jobid is not None ):
            myjobid = jobid+append_to_jobid
        sql = "SELECT jobid FROM %s WHERE jobid='%s'" % (self.getJobsTable(),myjobid)
        self.cursor().execute(sql)
        results = self.cursor().fetchall()
        if ( len(results)==0 ):
            return False
        elif ( len(results)==1 ):
            return True
        else:
            raise RuntimeError("More than one result for jobid %s (should not be possible)" % jobid)

    def _job_set_fields(self,job,system=None,oldjob=None,append_to_jobid=None):
        if ( not isinstance(job,jobinfo) ):
            raise TypeError("\"job\" object is of wrong type:  %s" % str(job))            
        if ( oldjob is not None and not isinstance(oldjob,jobinfo) ):
            raise TypeError("\"oldjob\" object is of wrong type:  %s" % str(oldjob))            
        myjobid = job.jobid()
        if ( append_to_jobid is not None ):
            myjobid = job.jobid()+append_to_jobid
        fields_to_set = []
        if ( oldjob is None ):
             fields_to_set.append("jobid='%s'" % myjobid)
        if ( system is not None and 
             ( oldjob is None or job.system()!=oldjob.system() ) ):
             fields_to_set.append("system='%s'" % system)
        if ( job.user() is not None and
             ( oldjob is None or job.user()!=oldjob.user() ) ):
             fields_to_set.append("username='%s'" % job.user())
        if ( job.group() is not None and
             ( oldjob is None or job.group()!=oldjob.group() ) ):
             fields_to_set.append("groupname='%s'" % job.group())
        if ( job.submithost() is not None and
             ( oldjob is None or job.submithost()!=oldjob.submithost() ) ):
             fields_to_set.append("submithost='%s'" % job.submithost())
        if ( job.name() is not None and
             ( oldjob is None or job.name()!=oldjob.name() ) ):
             fields_to_set.append("name='%s'" % job.name())
        if ( job.num_processors()>0 and
             ( oldjob is None or job.num_processors()!=oldjob.num_processors()) ):
             fields_to_set.append("nproc='%d'" % job.num_processors())
        if ( job.num_nodes()>0 and
             ( oldjob is None or job.num_nodes()!=oldjob.num_nodes()) ):
             fields_to_set.append("nodect='%d'" % job.num_nodes())
        if ( job.nodes() is not None and
             ( oldjob is None or job.nodes()!=oldjob.nodes() ) ):
             fields_to_set.append("nodes='%s'" % job.nodes())
        if ( job.num_gpus()>0 and
             ( oldjob is None or job.num_gpus()!=oldjob.num_gpus()) ):
             fields_to_set.append("ngpus='%d'" % job.num_gpus())
        if ( job.feature() is not None and
             ( oldjob is None or job.feature()!=oldjob.feature() ) ):
             fields_to_set.append("feature='%s'" % job.feature())
        if ( job.gattr() is not None and
             ( oldjob is None or job.gattr()!=oldjob.gattr() ) ):
             fields_to_set.append("gattr='%s'" % job.gattr())
        if ( job.gres() is not None and
             ( oldjob is None or job.gres()!=oldjob.gres() ) ):
             fields_to_set.append("gres='%s'" % job.gres())        
        if ( job.queue() is not None  and
             ( oldjob is None or job.queue()!=oldjob.queue() ) ):
             fields_to_set.append("queue='%s'" % job.queue())
        if ( job.qos() is not None  and
             ( oldjob is None or job.qos()!=oldjob.qos() ) ):
             fields_to_set.append("qos='%s'" % job.qos())
        if ( job.qtime_ts()>0 and
              ( oldjob is None or job.qtime_ts()!=oldjob.qtime_ts() ) ):
             fields_to_set.append("submit_ts='%d'" % job.qtime_ts())
             fields_to_set.append("submit_date=DATE(FROM_UNIXTIME('%d'))" % job.qtime_ts())
        if ( job.etime_ts()>0 and
              ( oldjob is None or job.etime_ts()!=oldjob.etime_ts() ) ):
             fields_to_set.append("eligible_ts='%d'" % job.etime_ts())
             fields_to_set.append("eligible_date=DATE(FROM_UNIXTIME('%d'))" % job.etime_ts())
        if ( job.start_ts()>0 and
              ( oldjob is None or job.start_ts()!=oldjob.start_ts() ) ):
             fields_to_set.append("start_ts='%d'" % job.start_ts())
             fields_to_set.append("start_date=DATE(FROM_UNIXTIME('%d'))" % job.start_ts())
        if ( job.end_ts()>0 and
              ( oldjob is None or job.end_ts()!=oldjob.end_ts() ) ):
             fields_to_set.append("end_ts='%d'" % job.end_ts())
             fields_to_set.append("end_date=DATE(FROM_UNIXTIME('%d'))" % job.end_ts())
        if ( job.cput_limit_sec()>0 and
             ( oldjob is None or job.cput_limit_sec()!=oldjob.cput_limit_sec() ) ):
             fields_to_set.append("cput_req='%s'" % sec_to_time(job.cput_limit_sec()))
             fields_to_set.append("cput_req_sec='%d'" % job.cput_limit_sec())
        if ( job.cput_used_sec()>0 and
             ( oldjob is None or job.cput_used_sec()!=oldjob.cput_used_sec() ) ):
             fields_to_set.append("cput='%s'" % sec_to_time(job.cput_used_sec()))
             fields_to_set.append("cput_sec='%d'" % job.cput_used_sec())
        if ( job.walltime_limit_sec()>0 and
             ( oldjob is None or job.walltime_limit_sec()!=oldjob.walltime_limit_sec() ) ):
             fields_to_set.append("walltime_req='%s'" % sec_to_time(job.walltime_limit_sec()))
             fields_to_set.append("walltime_req_sec='%d'" % job.walltime_limit_sec())
        if ( job.walltime_used_sec()>0 and
             ( oldjob is None or job.walltime_used_sec()!=oldjob.walltime_used_sec() ) ):
             fields_to_set.append("walltime='%s'" % sec_to_time(job.walltime_used_sec()))
             fields_to_set.append("walltime_sec='%d'" % job.walltime_used_sec())
        if ( job.mem_limit() is not None and
             ( oldjob is None or job.mem_limit()!=oldjob.mem_limit()) ):
             fields_to_set.append("mem_req='%s'" % job.mem_limit())
        if ( job.mem_used_kb()>0 and
             ( oldjob is None or job.mem_used_kb()!=oldjob.mem_used_kb()) ):
             fields_to_set.append("mem_kb='%d'" % job.mem_used_kb())
        if ( job.vmem_limit() is not None and
             ( oldjob is None or job.vmem_limit()!=oldjob.vmem_limit()) ):
             fields_to_set.append("vmem_req='%s'" % job.vmem_limit())
        if ( job.vmem_used_kb()>0 and
             ( oldjob is None or job.vmem_used_kb()!=oldjob.vmem_used_kb()) ):
             fields_to_set.append("vmem_kb='%d'" % job.vmem_used_kb())
        if ( ( job.has_resource("Resource_List.mppe") or job.has_resource("resources_used.mppe") ) and
             ( oldjob is None or 
               ( job.get_resource("Resource_List.mppe")!=oldjob.get_resource("Resource_List.mppe") or
                 job.get_resource("resources_used.mppe")!=oldjob.get_resource("resources_used.mppe") ) ) ):
             fields_to_set.append("mppe='%d'" % max(int(job.get_resource("Resource_List.mppe")),int(job.get_resource("resources_used.mppe"))))
        if ( ( job.has_resource("Resource_List.mppssp") or job.has_resource("resources_used.mppssp") ) and
             ( oldjob is None or 
               ( job.get_resource("Resource_List.mppssp")!=oldjob.get_resource("Resource_List.mppssp") or
                 job.get_resource("resources_used.mppssp")!=oldjob.get_resource("resources_used.mppssp") ) ) ):
             fields_to_set.append("mppssp='%d'" % max(int(job.get_resource("Resource_List.mppssp")),int(job.get_resource("resources_used.mppssp"))))
        if ( job.has_resource("exec_host") and
             ( oldjob is None or job.get_resource("exec_host")!=oldjob.get_resource("exec_host") ) ):
             fields_to_set.append("hostlist='%s'" % job.get_resource("exec_host"))
        if ( job.exit_status() is not None and
             ( oldjob is None or job.get_resource("Exit_status")!=oldjob.get_resource("Exit_status") ) ):
             fields_to_set.append("exit_status='%d'" % int(job.get_resource("Exit_status")))
        if ( job.software() is not None and
             ( oldjob is None or job.software()!=oldjob.software() ) ):
             fields_to_set.append("software='%s'" % job.software())
        if ( job.account() is not None and
             ( oldjob is None or job.account()!=oldjob.account() ) ):
             fields_to_set.append("account='%s'" % job.account())
        if ( len(fields_to_set)>0 ):
            return ", ".join(fields_to_set)
        else:
            return None

    def insert_job(self,job,system=None,check_existance=True,noop=False,append_to_jobid=None):
        if ( not isinstance(job,jobinfo) ):
            raise TypeError("\"job\" object is of wrong type:  %s" % str(job))
        if ( check_existance and self.job_exists(job.jobid(),append_to_jobid=append_to_jobid) ):
            raise RuntimeError("Job %s already exists in database, cannot insert" % job.jobid())
        delta = self._job_set_fields(job,system,append_to_jobid=append_to_jobid)
        if ( delta is not None ):
            sql = "INSERT INTO %s SET %s" % (self.getJobsTable(),delta)
            if ( noop ):
                sys.stderr.write("%s\n" % sql)
            else:
                self.cursor().execute(sql)

    def update_job(self,job,system=None,check_existance=True,noop=False,append_to_jobid=None):
        if ( not isinstance(job,jobinfo) ):
            raise TypeError("\"job\" object is of wrong type:  %s" % str(job))
        if ( check_existance and not self.job_exists(job.jobid(),append_to_jobid=append_to_jobid) ):
            raise RuntimeError("Job %s does not exist in database, cannot update" % job.jobid())
        myjobid = job.jobid()
        if ( append_to_jobid is not None ):
            myjobid = job.jobid()+append_to_jobid
        oldjob = self.get_job(job.jobid(),append_to_jobid=append_to_jobid)
        if ( job!=oldjob ):
            delta = self._job_set_fields(job,system,oldjob,append_to_jobid=append_to_jobid)
            if ( delta is not None ):
                sql = "UPDATE %s SET %s WHERE jobid='%s'" % (self.getJobsTable(),delta,myjobid)
                if ( noop ):
                    sys.stderr.write("%s\n" % sql)
                else:
                    self.cursor().execute(sql)

    def insert_or_update_job(self,job,system=None,noop=False,append_to_jobid=None):
        if ( not isinstance(job,jobinfo) ):
            raise TypeError("\"job\" object is of wrong type:  %s" % str(job))
        if ( self.job_exists(job.jobid(),append_to_jobid=append_to_jobid) ):
            self.update_job(job,system,check_existance=False,noop=noop,append_to_jobid=append_to_jobid)
        else:
            self.insert_job(job,system,check_existance=False,noop=noop,append_to_jobid=append_to_jobid)

    def get_job(self,jobid,noop=False,append_to_jobid=None):
        myjobid = jobid
        if ( append_to_jobid is not None ):
            myjobid = jobid+append_to_jobid
        if ( self.job_exists(myjobid) ):
            sql = "SELECT * FROM %s WHERE jobid='%s'" % (self.getJobsTable(),myjobid)
            if ( noop ):
                sys.stderr.write("%s\n" % sql)
                return None
            else:
                self.cursor().execute(sql)
                results = self.cursor().fetchall()
                if ( len(results)==0 ):
                    return None
                elif ( len(results)==1 ):
                    columns = []
                    for desc in self.cursor().description:
                        columns.append(desc[0])
                    resources = {}
                    ngpus = 0
                    result = list(results[0])
                    for i in range(len(result)):
                        if ( columns[i] in ["account","jobname","queue","system"] and
                             result[i] is not None ):
                            resources[columns[i]] = str(result[i])
                        elif ( columns[i]=="username" and
                               result[i] is not None ):
                            resources["user"] = str(result[i])
                        elif ( columns[i]=="groupname" and
                               result[i] is not None ):
                            resources["group"] = str(result[i])
                        elif ( columns[i]=="submithost" and
                               result[i] is not None ):
                            if ( resources.has_key("user") ):
                                resources["owner"] = resources["user"]+"@"+str(result[i])
                        elif ( columns[i]=="submit_ts" and
                               result[i]>0 ):
                            resources["ctime"] = str(result[i])
                            resources["qtime"] = str(result[i])
                        elif ( columns[i]=="eligible_ts" and
                               result[i]>0 ):
                            resources["etime"] = str(result[i])
                        elif ( columns[i]=="start_ts" and
                               result[i]>0 ):
                            resources["start"] = str(result[i])
                        elif ( columns[i]=="end_ts" and
                               result[i]>0 ):
                            resources["end"] = str(result[i])
                        elif ( columns[i]=="hostlist" and
                               result[i] is not None ):
                            resources["exec_host"] = str(result[i])
                        elif ( columns[i]=="exit_status" and
                               result[i] is not None ):
                            resources["Exit_status"] = str(result[i])
                        elif ( columns[i]=="cput_req" ):
                            if ( isinstance(result[i],datetime.timedelta) ):
                                if ( result[i].days>0 and result[i].seconds>0 ):
                                    resources["Resource_List.cput"] = sec_to_time(24*3600*result[i].days+result[i].seconds)
                                else:
                                    resources["Resource_List.cput"] = None
                            elif ( isinstance(result[i],int) ):
                                if ( result[i]>0 ):
                                    resources["Resource_List.cput"] = sec_to_time(result[i])
                                else:
                                    resources["Resource_List.cput"] = None
                            else:
                                resources["Resource_List.cput"] = str(result[i])
                        elif ( columns[i]=="cput_req_sec" ):
                            if ( not resources.has_key("Resource_List.cput") or
                                 ( resources.has_key("Resource_List.cput") and
                                   result[i] is not None and
                                   int(result[i])>time_to_sec(resources["Resource_List.cput"]) ) ):
                                resources["Resource_List.cput"] = sec_to_time(result[i])
                        elif ( columns[i]=="feature" and
                               result[i] is not None ):
                            resources["Resource_List.feature"] = str(result[i])
                        elif ( columns[i]=="gattr" and
                               result[i] is not None ):
                            resources["Resource_List.gattr"] = str(result[i])
                        elif ( columns[i]=="gres" and
                               result[i] is not None ):
                            resources["Resource_List.gres"] = str(result[i])
                        elif ( columns[i]=="gres" and
                               result[i] is not None ):
                            resources["Resource_List.gres"] = str(result[i])
                        elif ( columns[i]=="mem_req" and
                               result[i] is not None ):
                            resources["Resource_List.mem"] = str(result[i])
                        elif ( columns[i]=="mppe" and
                               result[i] is not None ):
                            resources["Resource_List.mppe"] = str(result[i])
                            resources["resources_used.mppe"] = str(result[i])
                        elif ( columns[i]=="mppssp" and
                               result[i] is not None ):
                            resources["Resource_List.mppssp"] = str(result[i])
                            resources["resources_used.mppssp"] = str(result[i])
                        elif ( columns[i]=="nodes" and
                               result[i] is not None ):
                            resources["Resource_List.nodes"] = str(result[i])
                            resources["Resource_List.neednodes"] = str(result[i])
                        elif ( columns[i]=="nodect" and
                               result[i]>0 ):
                            resources["Resource_List.nodect"] = str(result[i])
                            resources["unique_node_count"] = str(result[i])
                        elif ( columns[i]=="qos" and
                               result[i] is not None ):
                            resources["Resource_List.qos"] = str(result[i])
                        elif ( columns[i]=="vmem_req" and
                               result[i] is not None ):
                            resources["Resource_List.vmem"] = str(result[i])
                        elif ( columns[i]=="walltime_req" ):
                            if ( not resources.has_key("Resource_List.walltime") ):
                                if ( isinstance(result[i],datetime.timedelta) ):
                                    resources["Resource_List.walltime"] = sec_to_time(24*3600*result[i].days+result[i].seconds)
                                elif ( isinstance(result[i],int) ):
                                    resources["Resource_List.walltime"] = sec_to_time(result[i])
                                else:
                                    resources["Resource_List.walltime"] = str(result[i])
                        elif ( columns[i]=="walltime_req_sec" ):
                            if ( not resources.has_key("Resource_List.walltime") or
                                 ( resources.has_key("Resource_List.walltime") and
                                   result[i] is not None and
                                   int(result[i])>time_to_sec(resources["Resource_List.walltime"]) ) ):
                                resources["Resource_List.walltime"] = sec_to_time(result[i])

                        elif ( columns[i]=="cput" ):
                            if ( not resources.has_key("resources_used.cput") ):
                                if ( isinstance(result[i],datetime.timedelta) ):
                                    resources["resources_used.cput"] = sec_to_time(24*3600*result[i].days+result[i].seconds)
                                elif ( isinstance(result[i],int) ):
                                    resources["resources_used.cput"] = sec_to_time(result[i])
                                else:
                                    resources["resources_used.cput"] = str(result[i])
                        elif ( columns[i]=="cput_sec" ):
                            if ( not resources.has_key("resources_used.cput") or
                                 ( resources.has_key("resources_used.cput") and
                                   result[i] is not None and
                                   int(result[i])>time_to_sec(resources["resources_used.cput"]) ) ):
                                resources["resources_used.cput"] = sec_to_time(result[i])
                        elif ( columns[i]=="energy" ):
                            resources["resources_used.energy_used"] = str(result[i])
                        elif ( columns[i]=="mem_kb" and
                               result[i] is not None ):
                            resources["resources_used.mem"] = str(result[i])+"kb"
                        elif ( columns[i]=="vmem_kb" and
                               result[i] is not None ):
                            resources["resources_used.vmem"] = str(result[i])+"kb"                        
                        elif ( columns[i]=="walltime" ):
                            if ( not resources.has_key("resources_used.walltime") ):
                                if ( isinstance(result[i],datetime.timedelta) ):
                                    resources["resources_used.walltime"] = sec_to_time(24*3600*result[i].days+result[i].seconds)
                                elif ( isinstance(result[i],int) ):
                                    resources["resources_used.walltime"] = sec_to_time(result[i])
                                else:
                                    resources["resources_used.walltime"] = str(result[i])
                        elif ( columns[i]=="walltime_sec" ):
                            if ( not resources.has_key("resources_used.walltime") or
                                 ( resources.has_key("resources_used.walltime") and
                                   result[i] is not None and
                                   int(result[i])>time_to_sec(resources["resources_used.walltime"]) ) ):
                                resources["resources_used.walltime"] = sec_to_time(result[i])
                        elif ( columns[i]=="nproc" ):
                            resources["total_execution_slots"] = str(result[i])
                        elif ( columns[i]=="ngpus" ):
                            resources["ngpus"] = int(result[i])
                        elif ( columns[i]=="script" and result[i] is not None ):
                            resources["script"] = str(result[i])
                    if ( resources.has_key("ctime") ):
                        updatetime = int(resources["ctime"])
                    else:
                        updatetime = 0
                    state = "Q"
                    if ( resources.has_key("start") ):
                        if ( int(resources["start"])>updatetime ):
                            updatetime = resources["start"]
                        state = "S"
                    if ( resources.has_key("end") ):
                        if ( int(resources["end"])>updatetime ):
                            updatetime = int(resources["end"])
                        state = "E"
                    job = jobinfo(myjobid,datetime.datetime.fromtimestamp(float(updatetime)).strftime("%m/%d/%Y %H:%M:%S"),state,resources)
                    if ( resources["ngpus"]>0 ):
                        job._ngpus = resources["ngpus"]
                    return job
                else:
                    raise RuntimeError("More than one result for jobid %s (should not be possible)" % jobid)        


if __name__ == "__main__":
    import glob
    print str(jobs_from_files(glob.glob("/users/sysp/amaharry/acct-data/201603*")))
