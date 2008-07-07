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

#define MAX_PATH 1024

struct FileAttr {
  unsigned char pathname[MAX_PATH] ;
  unsigned int mode ;
  unsigned long long int filesize ;    
  unsigned long long int atime ;
  unsigned long long int mtime ;
  unsigned long long int ctime ;
} ;

MPI_Datatype MPI_FileAttr ; //The corresponding MPI Datatype

/* extern int mpi_fileattr_define() ;/*Defines the MPI Datatype corresponding to 
                    * the FileAttr type. Returns 1 on successful definition */
#endif	/* _FILEATTR_H */

