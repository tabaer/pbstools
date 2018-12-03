#!/usr/bin/env python

from setuptools import setup

# From https://packaging.python.org/guides/making-a-pypi-friendly-readme/
from os import path
this_directory = path.abspath(path.dirname(__file__))
with open(path.join(this_directory, 'README.rst')) as f:
    long_description = f.read()

setup (name = "pbsacct",
       version = "3.4.5",
       description = "Python library for parsing PBS accounting logs",
       long_description = long_description,
       long_description_content_type = 'text/x-rst',
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
                      'Topic :: Scientific/Engineering :: Information Analysis',
                      'Topic :: Software Development :: Libraries :: Python Modules'
                     ]
      )
