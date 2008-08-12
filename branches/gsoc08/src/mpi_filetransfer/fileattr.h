/* 
 * File:   fileattr.h
 * Author: Ganesh V
 *
 * Created on May 25, 2008, 10:53 AM
 */

#ifndef _FILEATTR_H
#define	_FILEATTR_H
#include <mpi.h>
#include <time.h>
#include <sys/types.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <string.h>
#include <getopt.h>
#include <errno.h>
#include <sys/dir.h>
#include <sys/param.h>
#include <math.h>
#include <utime.h>

#define FALSE 0
#define TRUE !FALSE

struct FileAttr {
  unsigned char pathname[PATH_MAX] ;
  unsigned int mode ;
  unsigned long long int filesize ;    
  unsigned long long int atime ;
  unsigned long long int mtime ;
  unsigned long long int ctime ;
} ;

MPI_Datatype MPI_FileAttr ; //The corresponding MPI Datatype

#endif	/* _FILEATTR_H */

