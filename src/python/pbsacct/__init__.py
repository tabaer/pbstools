#!/usr/bin/python
# This module provides functions for parsing PBS accounting logs for use by 
# various scripts
#
# Copyright 2016, 2017, 2018 Ohio Supercomputer Center
# Authors:  Aaron Maharry
#           Troy Baer <troy@osc.edu>
#
# License:  GNU GPL v2, see ../COPYING for details.

import copy
import datetime
import gzip
import logging
import os
import re
import sys
import unittest

# Needed to support logging in Python 2.6
class NullHandler(logging.Handler):
    def emit(self, record):
        pass

logger = logging.getLogger(__name__)
try:
    logging_null_handler = logging.NullHandler()
except AttributeError:
    logging_null_handler = NullHandler()
logger.addHandler(logging_null_handler)

def getLogger():
    return logger

def setLogger(newLogger):
    logger = newLogger

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
        if ( "system" not in self._resources ):
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
        for key in list(set(self.get_resource_keys()) | set(other.get_resource_keys())):
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
        if ( self.start_count()>0 ):
            output += "\tstart_count = %d\n" % self.start_count()
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

    def unset_resource(self,key):
        if ( key in self._resources ):
            del self._resources[key]

    def has_resource(self,key):
        return key in self._resources

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
            return datetime.datetime.fromtimestamp(self.ctime_ts())
        else:
            raise RuntimeError("Job "+self._jobid+" has no ctime set (aborted before started?)")

    def qtime(self):
        if ( self.has_resource("qtime") ):
            return datetime.datetime.fromtimestamp(self.qtime_ts())
        else:
            raise RuntimeError("Job "+self._jobid+" has no qtime set (aborted before started?)")

    def etime(self):
        if ( self.has_resource("etime") ):
            return datetime.datetime.fromtimestamp(self.etime_ts())
        else:
            raise RuntimeError("Job "+self._jobid+" has no etime set (aborted before started?)")

    def start(self):
        if ( self.has_resource("start") ):
            return datetime.datetime.fromtimestamp(self.start_ts())
        else:
            raise RuntimeError("Job "+self._jobid+" has no start time set (aborted before started?)")

    def end(self):
        if ( self.has_resource("end") ):
            return datetime.datetime.fromtimestamp(self.end_ts())
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

    def start_count(self):
        if ( self.has_resource("start_count") ):
            return int(self.get_resource("start_count"))
        else:
            return 0

    def nodes(self):
        return self.get_resource("Resource_List.nodes")

    def tasks(self):
        return self.get_resource("tasks")

    def nodes_used(self):
        nodes = []
        if ( self.has_resource("exec_host") ):
            for node_and_procs in self.get_resource("exec_host").split("+"):
                (node,procs) = node_and_procs.split("/")
                if ( node not in nodes ):
                    nodes.append(node)
        return nodes

    def num_nodes(self):
        nnodes = 0
        if ( self.has_resource("unique_node_count")):
            # Added in TORQUE 4.2.9
            nnodes = int(self.get_resource("unique_node_count"))
        elif ( self.has_resource("Resource_List.nodect")):
            nnodes = int(self.get_resource("Resource_List.nodect"))
        elif ( len(self.nodes_used())>0 ):
            nnodes = len(self.nodes_used())
        elif ( self.has_resource("Resource_List.nodes") ):
            for node in self.get_resource("Resource_List.nodes").split("+"):
                nodes_and_ppn = node.split(":")
                try:
                    n = int(nodes_and_ppn[0])
                except:
                    n = 1
                nnodes = nnodes+n
        elif ( self.has_resource("Resource_List.neednodes") ):
            for node in self.get_resource("Resource_List.neednodes").split("+"):
                nodes_and_ppn = node.split(":")
                try:
                    n = int(nodes_ppn_prop[0])
                except ValueError:
                    n = 1
                nnodes = nnodes+n
        return nnodes

    def num_processors(self):
        """ Returns the total number of processors the job requires """
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
            return processors
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
        return max(processors,ncpus)

    def num_gpus(self):
        ngpus = 0
        # sadly, there doesn't appear to be a more elegant way to do this
        if ( self.nodes() is not None and "gpus=" in self.nodes() ):
            # Compute the nodes requested and the processors per node
            for nodelist in self.nodes().split("+"):
                nodes_and_props = nodelist.split(":")
                try:
                    nodes = int(nodes_and_props[0])
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

    def other(self):
        return self.get_resource("Resource_List.other")

    def qos(self):
        return self.get_resource("Resource_List.qos")

    def software(self):
        return self.get_resource("Resource_List.software")

    def mem_used_kb(self):
        """ Return the amount of memory (in kb) used by the job """
        if ( self.has_resource("resources_used.mem") ):
            return mem_to_kb(self.get_resource("resources_used.mem"))
        else:
            return 0

    def vmem_used_kb(self):
        """ Return the amount of virtual memory (in kb) used by the job """
        if ( self.has_resource("resources_used.vmem") ):
            return mem_to_kb(self.get_resource("resources_used.vmem"))
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
            if ( time_to_sec(self.get_resource("resources_used.walltime"))>=0 ):
                return time_to_sec(self.get_resource("resources_used.walltime"))
            else:
                return self.end_ts()-self.start_ts()
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

    def write_last_accounting_record(self,fd):
        """
        Write a the equivalent of the last accounting record for this job in the "standard" PBS format
        """
        datestamp = self.get_update_time().strftime(self._updatetimefmt)
        state = self.get_state()
        jobid = self.jobid()
        resources = self.get_resources()
        record = (jobid,datestamp,state,resources)
        write_record_to_accounting_log(record,fd)

class jobinfoTestCase(unittest.TestCase):
    def __init__(self,methodName='runTest'):
        super(jobinfoTestCase,self).__init__(methodName)
        # don't replicate our fake test job in every test method
        self.testjob = jobinfo('123456.fakehost.lan',
                               '02/13/2009 18:31:30',
                               'E',
                               {'user': 'foo',
                                'group': 'bar',
                                'owner':  'foo@login1.fakehost.lan',
                                'jobname': 'job',
                                'ctime': '1234567890',
                                'qtime': '1234567890',
                                'etime': '1234567890',
                                'start': '1234567890',
                                'end': '1234567890',
                                'start_count': '1',
                                'queue': 'batch',
                                'Resource_List.nodes': '2:ppn=4',
                                'Resource_List.cput': '2:00:00',
                                'Resource_List.walltime': '1:00:00',
                                'Resource_List.mem': '1GB',
                                'Resource_List.vmem': '1GB',
                                'resources_used.cput': '00:00:02',
                                'resources_used.walltime': '00:00:01',
                                'resources_used.mem':  '1024kb',
                                'resources_used.vmem':  '2048kb',
                                'exec_host': 'node01/1+node02/2',
                                'exit_status': '0'})
    def test_eq(self):
        j1 = copy.deepcopy(self.testjob)
        j2 = copy.deepcopy(self.testjob)
        self.assertEqual(j1==j2,True)
        j3 = copy.deepcopy(j1)
        j3.set_resource("exit_status","-1")
        self.assertEqual(j1==j3,False)
    def test_numeric_jobid(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.numeric_jobid(),123456)
    def test_user(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.user(),"foo")
    def test_group(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.group(),"bar")
    def test_account(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.account(),None)
        j2 = copy.deepcopy(self.testjob)
        j2.set_resource("account","fnord")
        self.assertEqual(j2.account(),"fnord")
    def test_submithost(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.submithost(),"login1.fakehost.lan")
    def test_queue(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.queue(),"batch")
    def test_start_count(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.start_count(),1)
    def test_nodes(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.nodes(),'2:ppn=4')
    def test_tasks(self):
        acctdata = "10/25/2018 17:02:14;S;13512.pitzer-batch.ten.osc.edu;user=troy group=PZS0708 account=PZS0708 jobname=ht-test-allowthreads-wholenode queue=newsyntax ctime=1540501291 qtime=1540501291 etime=1540501291 start_count=1 start=1540501334 owner=troy@pitzer-login01.hpc.osc.edu exec_host=p0221/0-79 Resource_List.walltime=01:00:00 Resource_List.gattr=sysp Resource_List.advres=ht-test.224004 Resource_Request_2.0=-L tasks=1:lprocs=80:memory=100GB:allowthreads \n"
        acctdata += "10/25/2018 17:02:48;E;13512.pitzer-batch.ten.osc.edu;user=troy group=PZS0708 account=PZS0708 jobname=ht-test-allowthreads-wholenode queue=newsyntax ctime=1540501291 qtime=1540501291 etime=1540501291 start_count=1 start=1540501334 owner=troy@pitzer-login01.hpc.osc.edu exec_host=p0221/0-79 Resource_List.walltime=01:00:00 Resource_List.gattr=sysp Resource_List.advres=ht-test.224004 Resource_Request_2.0=-L tasks=1:lprocs=80:memory=100GB:allowthreads session=149510 total_execution_slots=80 unique_node_count=1 end=1540501368 Exit_status=0 resources_used.cput=253 resources_used.vmem=159171128kb resources_used.walltime=00:00:33 resources_used.mem=15448300kb resources_used.energy_used=0\n"
        acctdata += "11/02/2018 10:04:27;S;16057.pitzer-batch.ten.osc.edu;user=troy group=PZS0708 account=PZS0708 jobname=twotasks queue=newsyntax ctime=1541167460 qtime=1541167460 etime=1541167460 start_count=1 start=1541167467 owner=troy@pitzer-login01.hpc.osc.edu exec_host=p0034/0+p0070/0 Resource_List.walltime=01:00:00 Resource_List.gattr=sysp Resource_Request_2.0=-L tasks=1:lprocs=1:usecores:memory=4GB -L tasks=1:lprocs=1:usecores:memory=4GB \n"
        acctdata += "11/02/2018 10:05:58;E;16057.pitzer-batch.ten.osc.edu;user=troy group=PZS0708 account=PZS0708 jobname=twotasks queue=newsyntax ctime=1541167460 qtime=1541167460 etime=1541167460 start_count=1 start=1541167467 owner=troy@pitzer-login01.hpc.osc.edu exec_host=p0034/0+p0070/0 Resource_List.walltime=01:00:00 Resource_List.gattr=sysp Resource_Request_2.0=-L tasks=1:lprocs=1:usecores:memory=4GB -L tasks=1:lprocs=1:usecores:memory=4GB session=94063 total_execution_slots=2 unique_node_count=2 end=1541167558 Exit_status=271 resources_used.cput=0 resources_used.energy_used=0 resources_used.mem=1776kb resources_used.vmem=292048kb resources_used.walltime=00:01:24 \n"
        from tempfile import mkstemp
        (tmpfd,tmpfile) = mkstemp()
        tmpfh = os.fdopen(tmpfd,'w')
        tmpfh.write(acctdata)
        tmpfh.flush()
        tmpfh.close()
        jobs = jobs_from_file(tmpfile)
        if ( "13512.pitzer-batch.ten.osc.edu" not in jobs ):
            self.fail()
        else:
            j1 = jobs["13512.pitzer-batch.ten.osc.edu"]
            self.assertEqual(j1.tasks(),"1:lprocs=80:memory=100GB:allowthreads")
        if ( "16057.pitzer-batch.ten.osc.edu" not in jobs ):
            self.fail()
        else:
            j2 = jobs["16057.pitzer-batch.ten.osc.edu"]
            self.assertEqual(j2.tasks(),"1:lprocs=1:usecores:memory=4GB+1:lprocs=1:usecores:memory=4GB")
        try:
            os.unlink(tmpfile)
        except:
            self.fail()            
    def test_nodes_used(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.nodes_used(),['node01','node02'])
    def test_num_nodes(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.num_nodes(),2)
        j2 = copy.deepcopy(self.testjob)
        j2.set_resource("Resource_List.nodes","4:ppn=28")
        j2.set_resource("Resource_List.neednodes","4:ppn=28")
        j2.set_resource("exec_host","o0799/0-27+o0797/0-27+o0786/0-27+o0795/0-27")
        self.assertEqual(j2.num_nodes(),4)
        j3 = copy.deepcopy(self.testjob)
        j3.set_resource("Resource_List.nodes","3:ppn=28")
        j3.set_resource("Resource_List.neednodes","3:ppn=28")
        j3.set_resource("Resource_List.nodect","3")
        j3.unset_resource("exec_host")
        self.assertEqual(j3.num_nodes(),3)
        j4 = copy.deepcopy(self.testjob)
        j4.set_resource("Resource_List.nodes","5:ppn=28")
        j4.set_resource("Resource_List.neednodes","5:ppn=28")
        j4.set_resource("unique_node_count","5")
        j4.unset_resource("exec_host")
        self.assertEqual(j4.num_nodes(),5)
        j5 = copy.deepcopy(self.testjob)
        j5.set_resource("Resource_List.nodes","2:ppn=28+2:ppn=1")
        j5.set_resource("Resource_List.neednodes","2:ppn=28+2:ppn=1")
        j5.unset_resource("exec_host")
        self.assertEqual(j5.num_nodes(),4)
    def test_num_processors(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.num_processors(),8)
        j2 = copy.deepcopy(self.testjob)
        j2.set_resource('Resource_List.nodes','2:ppn=4+4:ppn=1')
        self.assertEqual(j2.num_processors(),12)
        j3 = copy.deepcopy(self.testjob)
        j3.set_resource("Resource_List.nodes","3:ppn=28")
        j3.set_resource("Resource_List.neednodes","3:ppn=28")
        j3.set_resource("total_execution_slots","84")
        self.assertEqual(j3.num_processors(),84)
    def test_num_gpus(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.num_gpus(),0)
        j2 = copy.deepcopy(self.testjob)
        j2.set_resource('Resource_List.nodes','2:ppn=4:gpus=2')
        self.assertEqual(j2.num_gpus(),4)
        j3 = copy.deepcopy(self.testjob)
        j3.set_resource('Resource_List.nodes','2:ppn=4:gpus=2+4:ppn=1:gpus=1')
        self.assertEqual(j3.num_gpus(),8)
    def test_mem_limit_kb(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.mem_limit_kb(),1024*1024)        
    def test_mem_used_kb(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.mem_used_kb(),1024)        
    def test_vmem_limit_kb(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.vmem_limit_kb(),1024*1024)        
    def test_vmem_used_kb(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.vmem_used_kb(),2048)
    def test_cput_limit_sec(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.cput_limit_sec(),7200)
    def test_cput_used_sec(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.cput_used_sec(),2)
    def test_walltime_limit_sec(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.walltime_limit_sec(),3600)
    def test_walltime_used_sec(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.walltime_used_sec(),1)
        # bogus time value, should use end-start (in this case 0)
        j2 = copy.deepcopy(self.testjob)
        j2.set_resource("resources_used.walltime","-342359:-42:-21")
        self.assertEqual(j2.walltime_used_sec(),0)
    def test_software(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.software(),None)
        j2 = copy.deepcopy(self.testjob)
        j2.set_resource('Resource_List.software','abaqus+2')
        self.assertEqual(j2.software(),"abaqus+2")
    def test_system(self):
        j1 = copy.deepcopy(self.testjob)
        self.assertEqual(j1.system(),None)
        j2 = copy.deepcopy(self.testjob)
        j2.set_resource('system','fakehost')
        self.assertEqual(j2.system(),"fakehost")
    def test_write_last_accounting_record(self):
        # hack to handle class namespace change in python 3.x
        if ( sys.version_info<(3,0) ):
            import StringIO
            fd1 = StringIO.StringIO()
        else:
            import io
            fd1 = io.StringIO()
        j1 = copy.deepcopy(self.testjob)
        j1.write_last_accounting_record(fd1)
        self.assertEqual(fd1.getvalue(),"02/13/2009 18:31:30;E;123456.fakehost.lan;Resource_List.cput=2:00:00 Resource_List.mem=1GB Resource_List.nodes=2:ppn=4 Resource_List.vmem=1GB Resource_List.walltime=1:00:00 ctime=1234567890 end=1234567890 etime=1234567890 exec_host=node01/1+node02/2 exit_status=0 group=bar jobname=job owner=foo@login1.fakehost.lan qtime=1234567890 queue=batch resources_used.cput=00:00:02 resources_used.mem=1024kb resources_used.vmem=2048kb resources_used.walltime=00:00:01 start=1234567890 start_count=1 system=None user=foo\n")
        fd1.close()


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
            time, record_type, jobid, resources = line.rstrip('\n').split(";",3)
        except ValueError:
            print("ERROR:  Invalid number of fields (requires 4).  Unable to parse entry: %s" % (str(line.split(";",3))))
            continue
        
        # Create a dict for the various resources
        resources_dict = dict()
        for resource in resources.split(" "):
            match = re.match("^([^=]*)=(.+)", resource)
            if match:
                key = match.group(1)
                value = match.group(2)
                if key in ["ctime", "qtime", "etime", "start", "end"]:
                    value = int(value)
                if key in []:
                    value = float(value)
                # The "tasks" resource can appear more than once due to the ...interesting
                # way the new NUMA-aware syntax implements multi-req jobs.  So, if there's
                # more than one tasks=[...] entry, compound them with pluses (similar to
                # how compound nodes= requests are done).
                if ( key=="tasks" and "tasks" in resources_dict.keys() ):
                    resources_dict[key] = resources_dict[key]+"+"+value
                else:
                    resources_dict[key] = value
            elif ( resource=="-L" ):
                # This is detritus from how multiple -L tasks=[...] are included in accounting logs,
                # so ignore it.
                pass
            elif ( resource!="" ):
                logger.warn("filename=%s, jobid=%s:  Malformed resource \"%s\"" % (filename,jobid,resource))
        
        # Store the data in the output
        output.append((jobid, time, record_type, resources_dict))
        #break
    acct_data.close()
    return output


def raw_data_from_files(filelist,warn_missing=False):
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
        elif ( warn_missing ):
            logger.warn("%s does not exist" % filename)
            continue
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
        if ( jobid not in output ):
            output[jobid] = jobinfo(jobid,update_time,record_type,resources,system)
        # may need an extra case here for jobs with multiple S and E
        # records (e.g. preemption)
        else:
            output[jobid].set_update_time(update_time)
            output[jobid].set_state(record_type)
            for key in resources.keys():
                output[jobid].set_resource(key,resources[key])
    return output

def write_record_to_accounting_log(record,fd):
    """
    Write a raw accounting record in the "standard" PBS format
    """
    jobid = record[0]
    datestamp = record[1]
    state = record[2]
    resources = record[3]
    resourcestring = ""
    for key in sorted(resources.keys()):
        if ( resourcestring=="" ):
            resourcestring = "%s=%s" % (key,resources[key])
        else:
            resourcestring += " %s=%s" % (key,resources[key])
    # format is datestamp;state;jobid;resources_separated_by_whitespace
    fd.write("%s;%s;%s;%s\n" % (datestamp,state,jobid,resourcestring))


def jobs_from_file(filename,system=None,warn_missing=False):
    """
    Parses a file containing multiple PBS accounting log entries.  Returns
    a hash of lightly postprocessed data (i.e. one entry per jobid rather
    than one per record).
    """
    return jobs_from_files([filename],system,warn_missing)


def jobs_from_files(filelist,system=None,warn_missing=False):
    """
    Parses a list of files containing multiple PBS accounting log entries.
    Returns a hash of lightly postprocessed data (i.e. one entry per jobid
    rather than one per record).
    """
    return records_to_jobs(raw_data_from_files(filelist,warn_missing),system)


def jobinfo_from_epilogue(jobid,reqlist="",usedlist="",queue=None,account=None,exit_status=0,system=None):
    """
    Create jobinfo from the information provided to the TORQUE epilogue.
    """
    update_time = datetime.datetime.strftime(datetime.datetime.now(),"%m/%d/%Y %H:%M:%S")
    rsrc = {}
    rsrc["exit_status"] = exit_status
    if ( queue is not None ):
        rsrc["queue"] = queue
    if ( account is not None ):
        rsrc["account"] = account
    for req in reqlist.split(','):
        try:
            (key,value) = req.split('=',1)
            rsrc["Resource_List."+key] = value
        except:
            pass
    for used in usedlist.split(','):
        try:
            (key,value) = used.split('=',1)
            rsrc["resources_used."+key] = value
        except:
            pass
    return jobinfo(jobid,update_time,"E",rsrc,system)


def time_to_sec(timestr):
    """
    Convert string time into seconds.
    """
    if ( timestr is None ):
        return 0
    elif ( isinstance(timestr,int) ):
        return timestr
    if ( not re.match("[\d\-:]+",timestr) ):
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
    # from https://stackoverflow.com/questions/775049/python-time-seconds-to-hms
    m, s = divmod(seconds, 60)
    h, m = divmod(m, 60)
    return "%02d:%02d:%02d" % (h,m,s)


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


class pbsacctTestCase(unittest.TestCase):
    # def test_raw_data_from_file(self):
    # def test_raw_data_from_files(self):
    # def test_records_to_jobs
    # def test_jobs_from_file(self):
    # def test_jobs_from_files(self):
    def test_jobinfo_from_epilogue(self):
        j1 = jobinfo_from_epilogue("123456.testfakehost.lan","cput=2:00:00,mem=1GB,nodes=2:ppn=4,vmem=1GB,walltime=1:00:00,neednodes=2:ppn=4","cput=00:00:02,mem=1024kb,vmem=2048kb,walltime=00:00:01","batch")
        self.assertEqual(j1.numeric_jobid(),123456)
        self.assertEqual(j1.account(),None)
        self.assertEqual(j1.queue(),"batch")
        self.assertEqual(j1.num_nodes(),2)
        self.assertEqual(j1.num_processors(),8)
        self.assertEqual(j1.num_gpus(),0)
        self.assertEqual(j1.cput_limit_sec(),7200)
        self.assertEqual(j1.cput_used_sec(),2)
        self.assertEqual(j1.walltime_limit_sec(),3600)
        self.assertEqual(j1.walltime_used_sec(),1)
        self.assertEqual(j1.mem_limit_kb(),1024*1024)        
        self.assertEqual(j1.mem_used_kb(),1024)        
        self.assertEqual(j1.vmem_limit_kb(),1024*1024)        
        self.assertEqual(j1.vmem_used_kb(),2048)        
        self.assertEqual(j1.software(),None)
    def test_mem_to_kb(self):
        self.assertEqual(mem_to_kb('1000kb'),1000)
        self.assertEqual(mem_to_kb('1000mb'),1000*1024)
        self.assertEqual(mem_to_kb('1000gb'),1000*1024*1024)
        self.assertEqual(mem_to_kb('1000kw'),8*1000)
        self.assertEqual(mem_to_kb('1000mw'),8*1000*1024)
        self.assertEqual(mem_to_kb('1000gw'),8*1000*1024*1024)
    def test_sec_to_time(self):
        self.assertEqual(sec_to_time(1),'00:00:01')
        self.assertEqual(sec_to_time(2),'00:00:02')
        self.assertEqual(sec_to_time(10),'00:00:10')
        self.assertEqual(sec_to_time(60),'00:01:00')
        self.assertEqual(sec_to_time(3600),'01:00:00')
        self.assertEqual(sec_to_time(2*3600),'02:00:00')
        self.assertEqual(sec_to_time(10*3600),'10:00:00')
        self.assertEqual(sec_to_time(24*3600),'24:00:00')
        self.assertEqual(sec_to_time(7*24*3600),'168:00:00')
    def test_time_to_sec(self):
        self.assertEqual(1,time_to_sec('00:00:01'))
        self.assertEqual(2,time_to_sec('00:00:02'))
        self.assertEqual(10,time_to_sec('00:00:10'))
        self.assertEqual(60,time_to_sec('00:01:00'))
        self.assertEqual(3600,time_to_sec('01:00:00'))
        self.assertEqual(2*3600,time_to_sec('02:00:00'))
        self.assertEqual(10*3600,time_to_sec('10:00:00'))
        self.assertEqual(1*24*3600,time_to_sec('1:00:00:00'))
        self.assertEqual(7*24*3600,time_to_sec('7:00:00:00'))
        self.assertEqual(-1232494941,time_to_sec('-342359:-42:-21'))


class pbsacctDB:
    def __init__(self, host=None, dbtype="mysql",
                 db="pbsacct", dbuser=None, dbpasswd=None,
                 jobs_table="Jobs", config_table="Config",
                 sw_table="Software",system=None,sqlitefile=None):
        self.setServerName(host)
        self.setType(dbtype)
        self.setName(db)
        self.setUser(dbuser)
        self.setPassword(dbpasswd)
        self.setJobsTable(jobs_table)
        self.setConfigTable(config_table)
        self.setSoftwareTable(sw_table)
        self.setSystem(system)
        self._sqlitefile = sqlitefile
        self._dbhandle = None
        self._cursor = None

    def setServerName(self, dbhost):
        self._dbhost = dbhost

    def getServerName(self):
        return self._dbhost

    def setType(self, dbtype):
        supported_dbs = ["mysql","pgsql","sqlite2","sqlite3"]
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

    def setSQLiteFile(self,filename):
        self._sqlitefile = filename

    def getSQLiteFile(self):
        return self._sqlitefile
        
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
                    elif ( keyword=="sqlitefile" ):
                        self.setSQLiteFile(value)
                    elif ( keyword=="softwaretable" ):
                        self.setSoftwareTable(value)
                    elif ( keyword=="system" ):
                        self.setSystem(value)
                    else:
                        raise RuntimeError("Unknown keyword \"%s\"" % keyword)
                except Exception as e:
                    logger.warn(str(e))
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
        elif ( self.getType()=="sqlite2" ):
            import pysqlite2.dbapi2 as sqlite
            if ( self.getSQLiteFile() is None ):
                raise RuntimeError("No SQLite database file specified")
            self._dbhandle = sqlite.connect(self.getSQLiteFile())
            return self._dbhandle
        elif ( self.getType()=="sqlite3" ):
            import sqlite3 as sqlite
            if ( self.getSQLiteFile() is None ):
                raise RuntimeError("No SQLite database file specified")
            self._dbhandle = sqlite.connect(self.getSQLiteFile())
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

    def _timestamp_to_date(self,ts):
        if ( self.getType() in ["mysql"] ):
            return "DATE(FROM_UNIXTIME('%d'))" % ts
        elif ( self.getType() in ["pgsql"] ):
            return "DATE(TIMESTAMP 'epoch' + %d * INTERVAL '1 second')" % ts
        elif ( self.getType() in ["sqlite2","sqlite3"] ):
            return "DATE('%d','UNIXEPOCH')" % ts
        else:
            raise RuntimeError("Unable to determine ts->date conversion for database type \"%s\"" % self.getType())

    def _job_set_fields(self,job,system=None,oldjob=None,append_to_jobid=None):
        if ( not isinstance(job,jobinfo) ):
            raise TypeError("\"job\" object is of wrong type:  %s" % str(job))            
        if ( oldjob is not None and not isinstance(oldjob,jobinfo) ):
            raise TypeError("\"oldjob\" object is of wrong type:  %s" % str(oldjob))            
        myjobid = job.jobid()
        if ( append_to_jobid is not None ):
            myjobid = job.jobid()+append_to_jobid
        fields_to_set = {}
        if ( oldjob is None ):
             fields_to_set["jobid"] = "'%s'" % myjobid
        if ( system is not None and 
             ( oldjob is None or job.system()!=oldjob.system() ) ):
             fields_to_set["system"] = "'%s'" % system
        if ( job.user() is not None and
             ( oldjob is None or job.user()!=oldjob.user() ) ):
             fields_to_set["username"] = "'%s'" % job.user()
        if ( job.group() is not None and
             ( oldjob is None or job.group()!=oldjob.group() ) ):
             fields_to_set["groupname"] = "'%s'" % job.group()
        if ( job.submithost() is not None and
             ( oldjob is None or job.submithost()!=oldjob.submithost() ) ):
             fields_to_set["submithost"] = "'%s'" % job.submithost()
        if ( job.name() is not None and
             ( oldjob is None or job.name()!=oldjob.name() ) ):
             fields_to_set["jobname"] = "'%s'" % job.name()
        if ( job.num_processors()>0 and
             ( oldjob is None or job.num_processors()!=oldjob.num_processors()) ):
             fields_to_set["nproc"] = "'%d'" % job.num_processors()
        if ( job.num_nodes()>0 and
             ( oldjob is None or job.num_nodes()!=oldjob.num_nodes()) ):
             fields_to_set["nodect"] = "'%d'" % job.num_nodes()
        if ( job.nodes() is not None and
             ( oldjob is None or job.nodes()!=oldjob.nodes() ) ):
             fields_to_set["nodes"] = "'%s'" % job.nodes()
        # This is a bit hackish, but otherwise ngpus never gets set in the DB for some reason
        #if ( job.num_gpus()>0 and
        #     ( oldjob is None or job.num_gpus()!=oldjob.num_gpus()) ):
        if ( job.num_gpus()>0 ):
             fields_to_set["ngpus"] = "'%d'" % job.num_gpus()
        if ( job.feature() is not None and
             ( oldjob is None or job.feature()!=oldjob.feature() ) ):
             fields_to_set["feature"] = "'%s'" % job.feature()
        if ( job.gattr() is not None and
             ( oldjob is None or job.gattr()!=oldjob.gattr() ) ):
             fields_to_set["gattr"] = "'%s'" % job.gattr()
        if ( job.gres() is not None and
             ( oldjob is None or job.gres()!=oldjob.gres() ) ):
             fields_to_set["gres"] = "'%s'" % job.gres()
        if ( job.queue() is not None  and
             ( oldjob is None or job.queue()!=oldjob.queue() ) ):
             fields_to_set["queue"] = "'%s'" % job.queue()
        if ( job.qos() is not None  and
             ( oldjob is None or job.qos()!=oldjob.qos() ) ):
             fields_to_set["qos"] = "'%s'" % job.qos()
        if ( job.qtime_ts()>0 and
              ( oldjob is None or job.qtime_ts()!=oldjob.qtime_ts() ) ):
             fields_to_set["submit_ts"] = "'%d'" % job.qtime_ts()
             fields_to_set["submit_date"] = self._timestamp_to_date(job.qtime_ts())
        if ( job.etime_ts()>0 and
              ( oldjob is None or job.etime_ts()!=oldjob.etime_ts() ) ):
             fields_to_set["eligible_ts"] = "'%d'" % job.etime_ts()
             fields_to_set["eligible_date"] = self._timestamp_to_date(job.etime_ts())
        if ( job.start_ts()>0 and
              ( oldjob is None or job.start_ts()!=oldjob.start_ts() ) ):
             fields_to_set["start_ts"] = "'%d'" % job.start_ts()
             fields_to_set["start_date"] = self._timestamp_to_date(job.start_ts())
        if ( job.end_ts()>0 and
              ( oldjob is None or job.end_ts()!=oldjob.end_ts() ) ):
             fields_to_set["end_ts"] = "'%d'" % job.end_ts()
             fields_to_set["end_date"] = self._timestamp_to_date(job.end_ts())
        if ( job.start_count()>0 and
              ( oldjob is None or job.start_count()!=oldjob.start_count() ) ):
            fields_to_set["start_count"] = "'%d'" % job.start_count()
        if ( job.cput_limit_sec()>0 and
             ( oldjob is None or job.cput_limit_sec()!=oldjob.cput_limit_sec() ) ):
             fields_to_set["cput_req"] = "'%s'" % sec_to_time(job.cput_limit_sec())
             fields_to_set["cput_req_sec"] = "'%d'" % job.cput_limit_sec()
        if ( job.cput_used_sec()>0 and
             ( oldjob is None or job.cput_used_sec()!=oldjob.cput_used_sec() ) ):
             fields_to_set["cput"] = "'%s'" % sec_to_time(job.cput_used_sec())
             fields_to_set["cput_sec"] = "'%d'" % job.cput_used_sec()
        if ( job.walltime_limit_sec()>0 and
             ( oldjob is None or job.walltime_limit_sec()!=oldjob.walltime_limit_sec() ) ):
             fields_to_set["walltime_req"] = "'%s'" % sec_to_time(job.walltime_limit_sec())
             fields_to_set["walltime_req_sec"] = "'%d'" % job.walltime_limit_sec()
        if ( job.walltime_used_sec()>0 and
             ( oldjob is None or job.walltime_used_sec()!=oldjob.walltime_used_sec() ) ):
             fields_to_set["walltime"] = "'%s'" % sec_to_time(job.walltime_used_sec())
             fields_to_set["walltime_sec"] = "'%d'" % job.walltime_used_sec()
        elif ( job.walltime_used_sec()<0 and
             ( oldjob is None or job.walltime_used_sec()!=oldjob.walltime_used_sec() ) ):
             delta = int(job.end_ts())-int(job.start_ts())
             fields_to_set["walltime"] = "'%s'" % sec_to_time(delta)
             fields_to_set["walltime_sec"] = "'%d'" % delta
        if ( job.mem_limit() is not None and
             ( oldjob is None or job.mem_limit()!=oldjob.mem_limit()) ):
             fields_to_set["mem_req"] = "'%s'" % job.mem_limit()
        if ( job.mem_used_kb()>0 and
             ( oldjob is None or job.mem_used_kb()!=oldjob.mem_used_kb()) ):
             fields_to_set["mem_kb"] = "'%d'" % job.mem_used_kb()
        if ( job.vmem_limit() is not None and
             ( oldjob is None or job.vmem_limit()!=oldjob.vmem_limit()) ):
             fields_to_set["vmem_req"] = "'%s'" % job.vmem_limit()
        if ( job.vmem_used_kb()>0 and
             ( oldjob is None or job.vmem_used_kb()!=oldjob.vmem_used_kb()) ):
             fields_to_set["vmem_kb"] = "'%d'" % job.vmem_used_kb()
        if ( ( job.has_resource("Resource_List.mppe") or job.has_resource("resources_used.mppe") ) and
             ( oldjob is None or 
               ( job.get_resource("Resource_List.mppe")!=oldjob.get_resource("Resource_List.mppe") or
                 job.get_resource("resources_used.mppe")!=oldjob.get_resource("resources_used.mppe") ) ) ):
             fields_to_set["mppe"] = "'%d'" % max(int(job.get_resource("Resource_List.mppe")),int(job.get_resource("resources_used.mppe")))
        if ( ( job.has_resource("Resource_List.mppssp") or job.has_resource("resources_used.mppssp") ) and
             ( oldjob is None or 
               ( job.get_resource("Resource_List.mppssp")!=oldjob.get_resource("Resource_List.mppssp") or
                 job.get_resource("resources_used.mppssp")!=oldjob.get_resource("resources_used.mppssp") ) ) ):
             fields_to_set["mppssp"] = "'%d'" % max(int(job.get_resource("Resource_List.mppssp")),int(job.get_resource("resources_used.mppssp")))
        if ( job.has_resource("exec_host") and
             ( oldjob is None or job.get_resource("exec_host")!=oldjob.get_resource("exec_host") ) ):
             fields_to_set["hostlist"] = "'%s'" % job.get_resource("exec_host")
        if ( job.exit_status() is not None and
             ( oldjob is None or job.get_resource("Exit_status")!=oldjob.get_resource("Exit_status") ) ):
             fields_to_set["exit_status"] = "'%d'" % int(job.get_resource("Exit_status"))
        if ( job.software() is not None and
             ( oldjob is None or job.software()!=oldjob.software() ) ):
             fields_to_set["software"] = "'%s'" % job.software()
        if ( job.account() is not None and
             ( oldjob is None or job.account()!=oldjob.account() ) ):
             fields_to_set["account"] = "'%s'" % job.account()
        # work around MySQL time field limitation -- cannot handle times >= 839:00:00
        if ( self.getType()=="mysql" ):
            for timefield in ["cput","cput_req","walltime","walltime_req"]:
                if ( timefield in fields_to_set and time_to_sec(fields_to_set[timefield].strip("'"))>=839*3600 ):
                    fields_to_set[timefield] = "'838:59:59'"
        if ( len(fields_to_set)>0 ):
            return fields_to_set
        else:
            return None

    def insert_job(self,job,system=None,check_existance=True,noop=False,append_to_jobid=None):
        if ( not isinstance(job,jobinfo) ):
            raise TypeError("\"job\" object is of wrong type:  %s" % str(job))
        if ( check_existance and self.job_exists(job.jobid(),append_to_jobid=append_to_jobid) ):
            raise RuntimeError("Job %s already exists in database, cannot insert" % job.jobid())
        delta = self._job_set_fields(job,system,append_to_jobid=append_to_jobid)
        if ( delta is not None ):
            deltakeys = sorted(delta.keys())
            deltavalues = []
            for key in deltakeys:
                deltavalues.append(delta[key])
            sql = "INSERT INTO %s ( %s ) VALUES ( %s )" % (self.getJobsTable(),",".join(deltakeys),",".join(deltavalues))
            if ( noop ):
                logger.debug("%s" % sql)
            else:
                try:
                    self.cursor().execute(sql)
                    self.commit()
                except Exception as e:
                    logger.debug("%s" % sql)
                    logger.error(str(e))

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
                deltalist = []
                for key in sorted(delta.keys()):
                    deltalist.append("%s=%s" % (key,delta[key]))
                sql = "UPDATE %s SET %s WHERE jobid='%s'" % (self.getJobsTable(),", ".join(deltalist),myjobid)
                if ( noop ):
                    logger.debug("%s" % sql)
                else:
                    try:
                        self.cursor().execute(sql)
                        self.commit()
                    except Exception as e:
                        logger.debug("%s" % sql)
                        logger.error(str(e))

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
                logger.debug("%s" % sql)
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
                            if ( "user" in resources ):
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
                        elif ( columns[i]=="start_count" and
                               result[i]>0 ):
                            resources["start_count"] = str(result[i])
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
                            if ( "Resource_List.cput" not in resources or
                                 ( "Resource_List.cput" in resources and
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
                            if ( "Resource_List.walltime" not in resources ):
                                if ( isinstance(result[i],datetime.timedelta) ):
                                    resources["Resource_List.walltime"] = sec_to_time(24*3600*result[i].days+result[i].seconds)
                                elif ( isinstance(result[i],int) ):
                                    resources["Resource_List.walltime"] = sec_to_time(result[i])
                                else:
                                    resources["Resource_List.walltime"] = str(result[i])
                        elif ( columns[i]=="walltime_req_sec" ):
                            if ( "Resource_List.walltime" not in resources or
                                 ( "Resource_List.walltime" in resources and
                                   result[i] is not None and
                                   int(result[i])>time_to_sec(resources["Resource_List.walltime"]) ) ):
                                resources["Resource_List.walltime"] = sec_to_time(result[i])

                        elif ( columns[i]=="cput" ):
                            if ( "resources_used.cput" not in resources ):
                                if ( isinstance(result[i],datetime.timedelta) ):
                                    resources["resources_used.cput"] = sec_to_time(24*3600*result[i].days+result[i].seconds)
                                elif ( isinstance(result[i],int) ):
                                    resources["resources_used.cput"] = sec_to_time(result[i])
                                else:
                                    resources["resources_used.cput"] = str(result[i])
                        elif ( columns[i]=="cput_sec" ):
                            if ( "resources_used.cput" not in resources or
                                 ( "resources_used.cput" in resources and
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
                            if ( "resources_used.walltime" not in resources ):
                                if ( isinstance(result[i],datetime.timedelta) ):
                                    resources["resources_used.walltime"] = sec_to_time(24*3600*result[i].days+result[i].seconds)
                                elif ( isinstance(result[i],int) ):
                                    resources["resources_used.walltime"] = sec_to_time(result[i])
                                else:
                                    resources["resources_used.walltime"] = str(result[i])
                        elif ( columns[i]=="walltime_sec" ):
                            if ( "resources_used.walltime" not in resources or
                                 ( "resources_used.walltime" in resources and
                                   result[i] is not None and
                                   int(result[i])>time_to_sec(resources["resources_used.walltime"]) ) ):
                                resources["resources_used.walltime"] = sec_to_time(result[i])
                        elif ( columns[i]=="nproc" ):
                            resources["total_execution_slots"] = str(result[i])
                        elif ( columns[i]=="ngpus" ):
                            resources["ngpus"] = int(result[i])
                        elif ( columns[i]=="software" and result[i] is not None ):
                            resources["Resource_List.software"] = str(result[i])
                        elif ( columns[i]=="script" and result[i] is not None ):
                            resources["script"] = str(result[i])
                    if ( "ctime" in resources ):
                        updatetime = int(resources["ctime"])
                    else:
                        updatetime = 0
                    state = "Q"
                    if ( "start" in resources ):
                        if ( int(resources["start"])>updatetime ):
                            updatetime = resources["start"]
                        state = "S"
                    if ( "end" in resources ):
                        if ( int(resources["end"])>updatetime ):
                            updatetime = int(resources["end"])
                        state = "E"
                    job = jobinfo(myjobid,datetime.datetime.fromtimestamp(float(updatetime)).strftime("%m/%d/%Y %H:%M:%S"),state,resources)
                    return job
                else:
                    raise RuntimeError("More than one result for jobid %s (should not be possible)" % jobid)        


# class pbsacctDBTestCase(unittest.TestCase):
#     def test_readConfigFile(self):
#     def test_job_exists(self):
#     def test_insert_or_update_job(self):
#     def test_get_job(self):


if __name__ == "__main__":
    unittest.main()
    #import glob
    #print str(jobs_from_files(glob.glob("/users/sysp/amaharry/acct-data/201603*")))
