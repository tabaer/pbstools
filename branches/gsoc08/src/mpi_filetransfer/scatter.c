#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <getopt.h>
#include <errno.h>
#include <fcntl.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/dir.h>
#include <string.h>
#include <sys/param.h>
#include <mpi.h>
#include <math.h>
#include <utime.h>
#include "fileattr.h"


#define FALSE 0
#define TRUE !FALSE
#define MAX_PATH 1024
typedef unsigned char int8 ;

char basedir[MAX_PATH] ; //The base directory for recursive directory scans
char targetdir[MAX_PATH] ; //The target directory 
char targetpath[MAX_PATH] ;
int pflag=0, iamrecursive=0 ;

extern  int alphasort();

int dirwalk_nfiles(char *pathname, int procID, int nproc) { 
  /*Returns the total number of files recursively inside a given directory*/

  struct stat stbuf ;
  int count, i;
  struct direct **files;
  char name[MAX_PATH] ;

  struct FileAttr att_file ;
  int bytecount=0 ;
  char *filedata ;
  int rd_blocks ;
  int BLKSIZE=256 ;
  struct utimbuf file_times ;

  int filecount=0 ;
  int cdir_count ;

  int ArgStatus ; 
/*   ArgStatus --> Indicates the status of the curent argument that is being dealt with. */
/*   0  --> Neither a file nor a directory. Can be skipped */
/*   1  --> File  */
/*   2  --> Directory */

  int file_select(struct direct *);
  int file_d ; 

  if(procID == 0) {
    cdir_count = scandir(pathname, &files, file_select, &alphasort);
    filecount = cdir_count ;
    printf("Total number of files in %s is %d \n", pathname, cdir_count) ;
  } 
  MPI_Barrier(MPI_COMM_WORLD) ;
  MPI_Bcast(&cdir_count, 1, MPI_INT, 0, MPI_COMM_WORLD) ;  
  
  /* If no files found, make a non-selectable menu item */
  if (cdir_count <= 0) {
    printf("No files in this directory \n");
    return 0 ; 
  }

  for (i=0; i<cdir_count; ++i) {
    if(procID == 0) {
      sprintf(name, "%s/%s", pathname, files[i]->d_name);
      sprintf(targetpath, "%s/%s", targetdir, strstr(name,basedir+1)) ;
      printf("Target is %s \n", targetpath) ;   
      if(stat(name, &stbuf)==0) {     
	ArgStatus = argument_status(&stbuf) ;
	printf("P: %d %s %d \n", procID, name, ArgStatus) ;
      } else ArgStatus = 0 ;

      if(ArgStatus != 0) {
	if(getfileattr(stbuf, &att_file)) strcpy((char *) att_file.pathname, targetpath);
      }
    }

    MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) ;      
    MPI_Bcast(&ArgStatus, 1, MPI_INT, 0, MPI_COMM_WORLD) ;

    if(ArgStatus == 2) {        
      mkdir((char *) att_file.pathname, S_IRWXU) ;

	//      printf("%d %s \n", mkdir((char *) att_file.pathname, S_IRWXU), strerror(errno)) ;
      filecount += dirwalk_nfiles(name, procID, nproc) ;      
      if(pflag) {
	file_times.actime = att_file.atime ;
	file_times.modtime = att_file.mtime ;
	printf("P: %d %d %s \n", procID, utime((char *)att_file.pathname, &file_times), strerror(errno)) ;
	printf("%d %s \n", chmod((char *)att_file.pathname, att_file.mode), strerror(errno)) ;
      }
    } 

    else if(ArgStatus == 1) {
      if(procID == 0) {
	filedata = (char *) malloc ((att_file.filesize)*sizeof(char)) ;
	file_d = open(name, O_RDONLY) ;
	rd_blocks = (int) (att_file.filesize/BLKSIZE) + 1 ;
	for(count=0;count < rd_blocks; count++) 
	  bytecount += read(file_d, &filedata[count*BLKSIZE], BLKSIZE) ;	      
	close(file_d) ;
      }
      else filedata = (char *) malloc ((att_file.filesize)*sizeof(char)) ;

      MPI_Bcast(filedata, att_file.filesize, MPI_UNSIGNED_CHAR, 0, MPI_COMM_WORLD) ;	  
      
      rd_blocks =  (att_file.filesize/BLKSIZE) + 1 ;
      file_d = creat((char *) att_file.pathname, S_IRWXU) ;
      //      printf("%d %s \n", file_d, strerror(errno)) ;
      bytecount = 0 ;
      for(count=0; count < rd_blocks-1; count++)
	bytecount += write(file_d, &filedata[count*BLKSIZE], BLKSIZE) ;
      bytecount += write(file_d, &filedata[count*BLKSIZE], att_file.filesize - (rd_blocks-1)*BLKSIZE) ;
      close(file_d) ;
      
      if(pflag) {
	file_times.actime = att_file.atime ;
	file_times.modtime = att_file.mtime ;
	printf("%d %s \n", utime((char *)att_file.pathname, &file_times), strerror(errno)) ;
	printf("%d %s \n", chmod((char *)att_file.pathname, att_file.mode), strerror(errno)) ;
      }
      filecount++ ;
    }    
    
  }
  return filecount;
  
}

/*file_select: Ensures that the current and the parent directory are not read again*/
int file_select(struct direct *entry) {

  if ((strcmp(entry->d_name, ".") == 0) || (strcmp(entry->d_name, "..") == 0))
    return 0;
  else
    return 1;
}


int main(int argc, char **argv) {

  int procID, nproc ;
  struct stat stbuf ;
  int8 *a ;
  int b ;
  int targetshouldbedir, targetisdir; 
  int ArgStatus ; 

/*   ArgStatus --> Indicates the status of the curent argument that is being dealt with. */
/*   0  --> Neither a file nor a directory. Can be skipped */
/*   1  --> File  */
/*   2  --> Directory */

  int filecount = 0 ;
  int i ; //Iteration variable
  char filename[MAX_PATH] ;
  int file_d ; // A file descriptor ;


  struct FileAttr att_file ;
  int bytecount=0, count=0 ;
  char *filedata ;
  int rd_blocks ;
  int BLKSIZE ;
  struct utimbuf file_times ;

  /*
    pflag --> Set as 1 if permissions are to be retained.
    iamrecursive --> Set as 1 if directories are to be copied recursively.
    targetshouldbedirectory --> Set as 1 if the more than 1 file is to be copied.
    targetisdir --> Set as 1 if the target is verified to be a directory
  */
  //Variables required for getopt() 
  extern char *optarg ; //Gives the arguments for the options which mandate an argument
  extern int optind ;  //Gives the total number of options given
  int ch ; //To get the individual options one at a time  


  //Getting arguments
  while ((ch = getopt(argc, argv, "prgs:")) != -1)
	  switch (ch) {
		  /* User-visible flags. */
	  case 'p':
		  pflag = 1;
		  break;
	  case 'r':
		  iamrecursive = 1;
		  break;
	  case 'g':
		  printf("Gather mode not supported here");
		  break;
	  case 's':
		  break;
	  default:
		  usage();
	  }
  
  
  //  printf("The number of options were %d \n ",optind) ;
  argc -= optind;
  argv += optind;
  //  printf("The number of arguments remaining is %d \n", argc) ; 
  
  if (argc < 2) 
    usage();
  else if(argc > 2) {
    targetshouldbedir = 1;    
    if(verifydir(argv[argc-1])) {
      targetisdir = 1 ;
    }
    else {
      printf("Target must be a directory if more than 1 files are to be transferred\n");
      usage() ;
    }
  }  

  striptrailingslashes(argc, &argv) ;

  MPI_Init(&argc, &argv); // Initialize MPI
  MPI_Comm_rank(MPI_COMM_WORLD, &procID); // Get process rank    
  MPI_Comm_size(MPI_COMM_WORLD, &nproc); // Get total number of processes specificed at start of run

  BLKSIZE = 32768 ;
  if(!mpi_fileattr_define()) printf("Error in constructing MPI datatype\n") ;  

  strcpy(targetdir, argv[argc-1]) ;
  while(argc-- > 1) {
    if (procID == 0) {
      if(stat(*argv, &stbuf)==0) {     
      //      printf("P: %d %s \n", procID, *argv) ;
      ArgStatus = argument_status(&stbuf) ;
      } else ArgStatus = 0 ;
    }
    
    MPI_Bcast(&ArgStatus, 1, MPI_INT, 0, MPI_COMM_WORLD) ;
  
    if(ArgStatus == 2) {
      
      if(procID == 0) {
	if(strrchr(*argv,'/') == NULL) {
	  sprintf(basedir,"/%s", *argv)  ;
	  //	  strcpy(*argv, basedir)  ;	  
	}
	else strcpy(basedir,strrchr(*argv,'/')) ;
	printf("The base directory is %s \n", basedir) ;	  
	sprintf(targetpath, "%s%s", targetdir, basedir) ;
	printf("Target is %s ", targetpath) ;
	if(getfileattr(stbuf, &att_file)) strcpy((char *) att_file.pathname, targetpath);
      }

      MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) ;
      mkdir((char *) att_file.pathname, S_IRWXU) ;

      if(iamrecursive) filecount += dirwalk_nfiles(*argv, procID, nproc) ;

      if(pflag) {
	file_times.actime = att_file.atime ;
	file_times.modtime = att_file.mtime ;
	printf("%d %s \n", utime((char *)att_file.pathname, &file_times), strerror(errno)) ;
	printf("%d %s \n", chmod((char *)att_file.pathname, att_file.mode), strerror(errno)) ;      
      }

    }
    
    if(ArgStatus == 1) {
      if(procID == 0)   {
	if(strrchr(*argv,'/') == NULL) sprintf(targetpath, "%s/%s", targetdir,*argv) ;
	else sprintf(targetpath, "%s%s", targetdir,strrchr(*argv,'/')) ;	      
	
	if(getfileattr(stbuf, &att_file)) {
	  strcpy((char *) att_file.pathname, targetpath);
	  filedata = (char *) malloc ((att_file.filesize)*sizeof(char)) ;
	  file_d = open(*argv, O_RDONLY) ;
	  rd_blocks = (int) (att_file.filesize/BLKSIZE) + 1 ;
	  
	  for(count=0;count < rd_blocks; count++) 
	    bytecount += read(file_d, &filedata[count*BLKSIZE], BLKSIZE) ;	      
	  
	  close(file_d) ;
	}	
      }
      
      MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) ;
      
      if(procID != 0) filedata = (char *) malloc ((att_file.filesize)*sizeof(char)) ;
      MPI_Bcast(filedata, att_file.filesize, MPI_UNSIGNED_CHAR, 0, MPI_COMM_WORLD) ;	  
      
      rd_blocks =  (att_file.filesize/BLKSIZE) + 1 ;
      file_d = creat((char *) att_file.pathname, S_IRWXU) ;
      bytecount = 0 ;
      for(count=0; count < rd_blocks-1; count++)
	bytecount += write(file_d, &filedata[count*BLKSIZE], BLKSIZE) ;
      bytecount += write(file_d, &filedata[count*BLKSIZE], att_file.filesize - (rd_blocks-1)*BLKSIZE) ;
      close(file_d) ;

      if(pflag) {
	file_times.actime = att_file.atime ;
	file_times.modtime = att_file.mtime ;
	printf("%d %s \n", utime((char *)att_file.pathname, &file_times), strerror(errno)) ;
	printf("%d %s \n", chmod((char *)att_file.pathname, att_file.mode), strerror(errno)) ;
      }
	
    }   
    
    argv++ ;
    filecount++ ;
  }
  
  
  if(procID == 0) printf("The total number of files is %d \n", filecount) ;
  
  MPI_Finalize();
  
  return 0 ;
  
}





