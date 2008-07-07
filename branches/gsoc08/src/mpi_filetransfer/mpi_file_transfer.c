/* 
 * File:   mpi_file_transfer.c
 * Author: Ganesh V
 *
 * Created on June 24, 2008, 13:00 hours
 */

#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <string.h>
#include <mpi.h>
#include "fileattr.h"

#define BLOCKSIZE 32768 

/*getfileattr: 'stat's the file "filename" and stores the necessary attributes 
 * into f_att */
int getfileattr(struct stat st_buf, struct FileAttr *f_att) {
  
  (*f_att).mode = st_buf.st_mode ;
  (*f_att).filesize = st_buf.st_size ;
  (*f_att).atime = st_buf.st_atime ;
  (*f_att).mtime = st_buf.st_mtime ;
  (*f_att).ctime = st_buf.st_ctime ;  
  
  return 1;
}

/*mpi_file_transfer: Transfers the given file to the targetpath */
int mpi_file_transfer(char *fpathname, char *targetpath) {

    int procID, nproc, file_d ;
    struct FileAttr att_file ;
    struct stat stbuf ;
    int bytecount=0, count=0 ;
    char *filedata ;
    int rd_blocks ;
 
    MPI_Comm_rank(MPI_COMM_WORLD, &procID); // Get process rank    
    MPI_Comm_size(MPI_COMM_WORLD, &nproc); // Get total number of processes specificed at start of run

    if(procID == 0) {        
        if(stat(fpathname, &stbuf) == 0) {
            if(getfileattr(stbuf, &att_file)) {
	      strcpy((char *) att_file.pathname, fpathname);
              filedata = (char *) malloc ((att_file.filesize)*sizeof(char)) ;
              file_d = open(fpathname,O_RDONLY) ;
	      rd_blocks = (int) (att_file.filesize/BLOCKSIZE) + 1 ;
	      for(count=0;count < rd_blocks; count++) 
		bytecount += read(file_d, &filedata[count*BLOCKSIZE], BLOCKSIZE) ;	      

	      close(file_d) ;
            }
        }
        
    }
    
    MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) ;
    //    printf("File size in P:%d is %d \n" , procID, att_file.filesize) ;

    if(procID != 0) filedata = (char *) malloc ((att_file.filesize)*sizeof(char)) ;
    MPI_Bcast(filedata, att_file.filesize, MPI_UNSIGNED_CHAR, 0, MPI_COMM_WORLD) ;

/*     if(procID == 3) { */
/*       rd_blocks = (int) (att_file.filesize/BLOCKSIZE) + 1 ; */
/*       for(count=0; count < rd_blocks-1; count++) */
/* 	bytecount += write(1, &filedata[count*BLOCKSIZE], BLOCKSIZE) ; */
/*       bytecount += write(1, &filedata[count*BLOCKSIZE], att_file.filesize - (rd_blocks-1)*BLOCKSIZE) ; */
/*     } */

    return 1;
}
