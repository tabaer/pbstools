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
#include <unistd.h>

#define FALSE 0
#define TRUE !FALSE

/*  ArgStatus --> Indicates the status of the curent argument that is being dealt with. */
#define ARG_NOT_REG_FILE 0 // Neither a file nor a directory. Can be skipped
#define ARG_IS_FILE      1 // File
#define ARG_IS_DIR       2 // Directory


struct FileAttr {
  unsigned char pathname[PATH_MAX] ;
  mode_t mode ;
  off_t filesize ;    
  time_t atime ;
  time_t mtime ;
  time_t ctime ;
} ;

MPI_Datatype MPI_FileAttr ; //The corresponding MPI Datatype

#endif	/* _FILEATTR_H */

