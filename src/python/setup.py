#!/usr/bin/env python

from setuptools import setup

setup (name = "pbsacct",
       version = "3.4.2rc1",
       description = "Python library for parsing PBS accounting logs",
       author = "Troy Baer",
       author_email = "tabaer@gmail.com",
       url = "https://github.com/tabaer/pbstools",
       packages = ['pbsacct'],
       zip_safe = False,
       license = "GPL v2",
       classifiers = [
                      'Development Status :: 4 - Beta',
                      'Intended Audience :: System Administrators',
                      'License :: OSI Approved :: GNU General Public License v2 (GPLv2)',
                      'Operating System :: POSIX',
                      'Topic :: Scientific/Engineering :: Information Analysis'
                      'Topic :: Software Development :: Libraries :: Python Modules'
                     ]
      )
