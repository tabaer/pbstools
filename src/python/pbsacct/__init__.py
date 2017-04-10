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
    def __init__(self,jobid,update_time,state,resources):
        self._jobid = jobid
        self._updatetimefmt = "%m/%d/%Y %H:%M:%S"
        self._updatetime = datetime.datetime.strptime(update_time,self._updatetimefmt)
        self._state = state
        self._resources = {}
        for key in resources.keys():
            self._resources[key] = resources[key]

    def __repr__(self):
        output  = "jobid %s {\n" % self.jobid()
        output += "\tlast_state = %s\n" % self.get_state()
        output += "\tlast_update_time = %s (%d)\n" % (str(self.get_update_time()),self.get_update_time_ts())
        output += "\tjobname = %s\n" % self.name()
        output += "\tqueue = %s\n" % self.queue()
        output += "\tuser = %s\n" % self.user()
        output += "\tgroup = %s\n" % self.group()
        output += "\taccount = %s\n" % self.account()
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
        if ( self._resources.has_key(key) ):
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
             not self._resources.has_key(key) ):
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

    def qtime(self):
        if ( self._resources.has_key("qtime") ):
            return datetime.datetime.fromtimestamp(int(self._resources["qtime"]))
        else:
            raise RuntimeError("Job "+self._jobid+" has no qtime set")

    def etime(self):
        if ( self._resources.has_key("etime") ):
            return datetime.datetime.fromtimestamp(int(self._resources["etime"]))
        else:
            raise RuntimeError("Job "+self._jobid+" has no etime set")

    def start(self):
        if ( self._resources.has_key("start") ):
            return datetime.datetime.fromtimestamp(int(self._resources["start"]))
        else:
            raise RuntimeError("Job "+self._jobid+" has no start time set")

    def end(self):
        if ( self._resources.has_key("end") ):
            return datetime.datetime.fromtimestamp(int(self._resources["end"]))
        else:
            raise RuntimeError("Job "+self._jobid+" has no end time set")

    def qtime_ts(self):
        if ( self._resources.has_key("qtime") ):
            return int(self._resources["qtime"])
        else:
            return 0

    def etime_ts(self):
        if ( self._resources.has_key("etime") ):
            return int(self._resources["etime"])
        else:
            return 0

    def start_ts(self):
        if ( self._resources.has_key("start") ):
            return int(self._resources["start"])
        else:
            return 0

    def end_ts(self):
        if ( self._resources.has_key("end") ):
            return int(self._resources["end"])
        else:
            return 0

    def nodes(self):
        return self.get_resource("Resource_List.nodes")

    def nodes_used(self):
        nodes = []
        if ( self._resources.has_key("exec_host") ):
            for node_and_procs in self._resources["exec_host"].split("+"):
                (node,procs) = node_and_procs.split("/")
                if ( node not in nodes ):
                    nodes.append(node)
        return nodes

    def num_nodes(self):
        nnodes = 0
        if ( self._resources.has_key("unique_node_count")):
            # Added in TORQUE 4.2.9
            nnodes = int(self._resources["unique_node_count"])
        else:
            nnodes = len(self.nodes_used())
        return nnodes

    def num_processors(self):
        """ Returns the total number of processors the job requires """
        processors = 0
        if ( self._resources.has_key("total_execution_slots") ):
            # Added in TORQUE 4.2.9
            processors = int(self._resources["total_execution_slots"])
        elif ( self._resources.has_key("Resource_List.nodes") ):
            # Compute the nodes requested and the processors per node
            for nodelist in self._resources["Resource_List.nodes"].split("+"):
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
            return processors
        ncpus = 0
        if ( self._resources.has_key("Resource_List.ncpus") ):
            ncpus = max(ncpus,int(self._resources["Resource_List.ncpus"]))
        if ( self._resources.has_key("resources_used.ncpus") ):
            ncpus = max(ncpus,int(self._resources["resources_used.ncpus"]))
        if ( self._resources.has_key("Resource_List.mppssp") or 
             self._resources.has_key("resources_used.mppssp") or 
             self._resources.has_key("Resource_List.mppe") or
             self._resources.has_key("resources_used.mppe") ):
            # Cray SV1/X1 specific code
            # These systems could gang together 4 individual processors (SSPs)
            # in a virtual processor (MSPs).
            # This is admittedly rather weird and only of historical interest.
            ssps = 0
            if ( self._resources.has_key("Resource_List.mppssp") ):
                ssps = ssps + int(self._resources["Resource_List.mppssp"])
            elif ( self._resources.has_key("resources_used.mppssp") ):
                ssps = ssps + int(self._resources["resources_used.mppssp"])
            if ( self._resources.has_key("Resource_List.mppe") ):
                ssps = ssps + 4*int(self._resources["Resource_List.mppe"])
            elif ( self._resources.has_key("resources_used.mppe") ):
                ssps = ssps + 4*int(self._resources["resources_used.mppe"])
            ncpus = max(ncpus,ssps)
        if ( self._resources.has_key("Resource_List.size") ):
            ncpus = max(ncpus,int(self._resources["Resource_List.size"]))
        # Return the larger of the two computed values
        return max(processors,ncpus)

    def num_gpus(self):
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
        return ngpus

    def feature(self):
        return self.get_resource("Resource_List.feature")

    def gattr(self):
        return self.get_resource("Resource_List.gattr")

    def gres(self):
        return self.get_resource("Resource_List.gres")

    def software(self):
        return self.get_resource("Resource_List.software")

    def other(self):
        return self.get_resource("Resource_List.other")

    def mem_used_kb(self):
        """ Return the amount of memory (in kb) used by the job """
        if ( self._resources.has_key("resources_used.mem") ):
            return int(re.sub("kb$", "", self._resources["resources_used.mem"]))
        else:
            return 0

    def vmem_used_kb(self):
        """ Return the amount of virtual memory (in kb) used by the job """
        if ( self._resources.has_key("resources_used.vmem") ):
            return int(re.sub("kb$", "", self._resources["resources_used.vmem"]))
        else:
            return 0

    def mem_limit_kb(self):
        if ( self._resources.has_key("Resource_List.mem") ):
            return mem_to_kb(self._resources["Resource_List.mem"])
        else:
            return 0

    def vmem_limit_kb(self):
        if ( self._resources.has_key("Resource_List.vmem") ):
            return mem_to_kb(self._resources["Resource_List.vmem"])
        else:
            return 0

    def walltime_used_sec(self):
        if ( self._resources.has_key("resources_used.walltime") ):
            return time_to_sec(self._resources["resources_used.walltime"])
        else:
            return 0

    def walltime_limit_sec(self):
        if ( self._resources.has_key("Resource_List.walltime") ):
            return time_to_sec(self._resources["Resource_List.walltime"])
        else:
            return 0
    
    def cput_used_sec(self):
        if ( self._resources.has_key("resources_used.cput") ):
            return time_to_sec(self._resources["resources_used.cput"])
        else:
            return 0

    def cput_limit_sec(self):
        if ( self._resources.has_key("Resource_List.cput") ):
            return time_to_sec(self._resources["Resource_List.cput"])
        else:
            return 0

    def energy_used(self):
        if ( self._resources.has_key("resources_used.energy_used") ):
            return int(self._resources["resources_used.energy_used"])
        else:
            return 0

    def exit_status(self):
        if ( self._resources.has_key("Exit_status") ):
            return int(self._resources["Exit_status"])
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
                if key in []:
                    value = int(value)
                if key in ["qtime", "etime", "start", "end"]:
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


def records_to_jobs(rawdata):
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
            output[jobid] = jobinfo(jobid,update_time,record_type,resources)
        # may need an extra case here for jobs with multiple S and E
        # records (e.g. preemption)
        else:
            output[jobid].set_update_time(update_time)
            output[jobid].set_state(record_type)
            for key in resources.keys():
                output[jobid].set_resource(key,resources[key])
    return output


def jobs_from_file(filename):
    """
    Parses a file containing multiple PBS accounting log entries.  Returns
    a hash of lightly postprocessed data (i.e. one entry per jobid rather
    than one per record).
    """
    return jobs_from_files([filename])


def jobs_from_files(filelist):
    """
    Parses a list of files containing multiple PBS accounting log entries.
    Returns a hash of lightly postprocessed data (i.e. one entry per jobid
    rather than one per record).
    """
    return records_to_jobs(raw_data_from_files(filelist))


def time_to_sec(timestr):
    """
    Convert string time into seconds.
    """
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
    hours = seconds/3600
    minutes = (seconds-3600*hours)/60
    sec = seconds-(3600*hours+60*minutes)
    return "%d:%02d:%02d" % (hours,minutes,sec)


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


if __name__ == "__main__":
    import os
    print str(jobs_from_file("/users/sysp/amaharry/acct-data/20160310"))
