#!/usr/bin/python
#
# This module provides functions for parsing PBS accounting logs for use by 
# various scripts

import gzip
import re

def parse_acct_data(filename):
    """
    Parses a file containing multiplt PBS Accoutning entries. Returns a list
    of tuples containing the following information:

    (time, job_type, job_id, resources)

    Resources are returned in a dictionary containing entries for each 
    resource name and corresponding value
    """
    try:
        if re.search("\.gz$", filename):
            acct_data = gzip.open(filename)
        else:
            acct_data = open(filename)
    except IOError as e:
        print "ERROR: Failed to read PBS Accounting file %s" %filename
        return None
    output = []
    for line in acct_data:
        
        # Get the fields from the log entry
        try:
            time, job_type, job_id, resources = line.split(";")
        except ValueError:
            print("ERROR: Invalid number of fields (requires 4). Unable to \
                    parse entry: %s" %line.split(";"))
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
                if key in ["qtime", "start", "end"]:
                    value = float(value)
                resources_dict[key] = value
        
        # Store the data in the output
        output.append((time, job_type, job_id, resources_dict))
        #break
    acct_data.close()
    return output

def strip_job_id(job_id):
    """
    Returns the stripped job id, containing only numbers
    
    Input is of the form: 6072125.oak-batch.osc.edu
    Output is ofthe form: 6072125
    """
    return job_id.split(".")[0]

def get_num_processors(resources):
    """ Returns the total number of processors the job requires """

    # Compute the nodes requested and the processors per node
    nodes_and_ppn = resources["Resource_List.nodes"].split(":")
    try:
        nodes = int(nodes_and_ppn[0])
    except:
        # Handles malformed log values
        nodes = 1
    if len(nodes_and_ppn) == 2:
        try:
            ppn = int(re.search("\w+=(\d+)", nodes_and_ppn[1]).group(1))
        except AttributeError:
            ppn = 1
    else:
        ppn = 1
    nodes = max(1, nodes)
    ppn = max(1, ppn)
    processors = nodes * ppn

    # Compute the number of cpus requested
    num_cpus = 1
    if "Resource_List.ncpus" in resources:
        num_cpus = max(num_cpus, int(resources["Resource_List.ncpus"]))
    if "resources_used.mppssp" in resources and \
        "resources_used.mppssp" in resources:
        num_cpus = max(num_cpus, int(resources["resources_used.mppssp"]) + \
                                    4 * int(resources["resources_used.mppe"]))
    if "Resource_List.size" in resources:
        num_cpus = max(num_cpus, int(resources["Resource_List.size"]))

    # Return the larger of the two computed values
    return max(processors, num_cpus)



def get_mem_used(resources):
    """ Return the amount of memory (in kb) used by the job """
    if "resources_used.mem" in resources:
       return int(re.sub("kb$", "", resources["resources_used.mem"]))
    else:
        return 0



def get_vmem_used(resources):
    """ Return the amount of virtual memory (in kb) used by the job """
    if "resources_used.vmem" in resources:
       return int(re.sub("kb$", "", resources["resources_used.vmem"]))
    else:
        return 0



if __name__ == "__main__":
    import os
    print parse_acct_data(os.path.expanduser("~amaharry/acct-data/20160310"))
