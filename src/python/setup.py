#!/usr/bin/env python

from setuptools import setup

setup (name = "pbsacct",
       version = "githead",
       description = "Python library for parsing PBS accounting logs",
       author = "Troy Baer",
       author_email = "troy@osc.edu",
       url = "https://github.com/tabaer/pbstools",
       packages = ['pbsacct'],
       zip_safe = False,
       license = "GPL v2",
      )
