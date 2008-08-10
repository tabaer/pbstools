#include "fileattr.h"

int basedir_jump ; // The number of characters to jump in the given argument to get to basedir
char basedir[PATH_MAX] ; //The base directory for recursive directory scans
char targetdir[PATH_MAX] ; //The target directory 
char targetpath[PATH_MAX] ; //The target path
int pflag=0, iamrecursive=0 ; //Flags which determine if the file permissions are to be preserved and if directories are to be recursively copied.
int BLKSIZE = 32768; //The block size to use when reading files.
long MEM_AVL ; //Total Physical memory on the system.
extern int alphasort();

int dirwalk_nfiles(char *pathname, int procID, int nproc) { 
  /*Returns the total number of files recursively inside a given directory*/

  struct stat stbuf ;
  int count, i;
  struct direct **files;
  char name[PATH_MAX] ;

  struct FileAttr att_file ;
  int bytecount=0 ; //A count of bytes read or written
  char *filedata ; //Stores the file data as an array of bytes
  struct utimbuf file_times ; //The structure to store the mod & access times

  int filecount=0 ; 
  int cdir_count ; //A file count of the current directory

  int ArgStatus ; 
/*   ArgStatus --> Indicates the status of the curent argument that is being dealt with. */
/*   0  --> Neither a file nor a directory. Can be skipped */
/*   1  --> File  */
/*   2  --> Directory */

  int file_select(struct direct *); //Function to decide on selection of files to copy
  int file_d ; //A file descriptor
  int file_d_read, file_d_write ; // File descriptors to read and write files ;
  long int file_read, file_rem ; //Size of part of file to read and the remaining part 

  if(procID == 0) {
    cdir_count = scandir(pathname, &files, file_select, &alphasort);
    printf("Total number of files in %s is %d \n", pathname, cdir_count) ;
  } 
  if(MPI_Barrier(MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;
  if(MPI_Bcast(&cdir_count, 1, MPI_INT, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;  
  
  /* If no files found, make a non-selectable menu item */
  if (cdir_count <= 0) {
    printf("No files in this directory \n");
    return 0 ; 
  }

  for (i=0; i<cdir_count; ++i) {
    if(procID == 0) {
      sprintf(name, "%s/%s", pathname, files[i]->d_name);
      sprintf(targetpath, "%s/%s", targetdir, name+basedir_jump) ;
      printf("Target is %s \n", targetpath) ;   
      if(stat(name, &stbuf)==0) {     
	ArgStatus = argument_status(&stbuf) ;
      } else {	
	ArgStatus = 0 ;
	printf("Skipping irregular file - %s \n", name);
      }
      
      if(ArgStatus != 0) {
	if(getfileattr(stbuf, &att_file)) strcpy((char *) att_file.pathname, targetpath);
      }
    }

    if(MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;      
    if(MPI_Bcast(&ArgStatus, 1, MPI_INT, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;

    if(ArgStatus == 2) {        
      mkdir((char *) att_file.pathname, 00755) ;

      filecount += dirwalk_nfiles(name, procID, nproc) ;      
      if(pflag) {
	file_times.actime = att_file.atime ;
	file_times.modtime = att_file.mtime ;
	utime((char *)att_file.pathname, &file_times) ;
	chmod((char *)att_file.pathname, att_file.mode);
      }
    } 

    else if(ArgStatus == 1) {
      if(MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;
      if(procID == 0) file_d_read = open(name, O_RDONLY) ;
      file_d_write = creat((char *) att_file.pathname, 00644) ;
      filedata = (char *) malloc ((BLKSIZE)*sizeof(char)) ;
      file_rem = att_file.filesize ;

      while(file_rem > 0) {
	file_read = (file_rem < BLKSIZE  ? file_rem : BLKSIZE) ;	
	if(procID == 0) bytecount += read(file_d_read, &filedata[0], file_read) ;      
	if(MPI_Bcast(filedata, file_read, MPI_UNSIGNED_CHAR, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;	  	
	bytecount += write(file_d_write, &filedata[0], file_read) ;
	file_rem -= file_read ;
      }

      if(procID == 0) close(file_d_read) ;
      close(file_d_write) ;
      
      if(pflag) {
	file_times.actime = att_file.atime ;
	file_times.modtime = att_file.mtime ;
	utime((char *)att_file.pathname, &file_times);
	chmod((char *)att_file.pathname, att_file.mode) ;
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
  int b ;
  int singlefileflag = 0 ;
  int targetshouldbedir, targetisdir; 
  int ArgStatus ;   
  
/*   ArgStatus --> Indicates the status of the curent argument that is being dealt with. */
/*   0  --> Neither a file nor a directory. Can be skipped */
/*   1  --> File  */
/*   2  --> Directory */

  int filecount = 0 ;
  int i ; //Iteration variable
  char filename[PATH_MAX] ;
  int file_d ; // A file descriptor ;
  int file_d_read, file_d_write ; // File descriptors to read and write files ;
  long int file_read, file_rem ; //Size of part of file to read and the remaining part 

  struct FileAttr att_file ;
  int bytecount=0, count=0 ;
  char *filedata ;
  int rd_blocks ;
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
  while ((ch = getopt(argc, argv, "prhgs")) != -1)
	  switch (ch) {
		  /* User-visible flags. */
	  case 'p':
		  pflag = 1;
		  break;
	  case 'r':
		  iamrecursive = 1;
		  break;
	  case 'h':
                  usage();
                  break ;
	  case 'g':
		  printf("Gather mode not supported here \n");
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

  striptrailingslashes(argc, &argv) ;

  if(MPI_Init(&argc, &argv) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER); // Initialize MPI
  if(MPI_Comm_rank(MPI_COMM_WORLD, &procID) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ; // Get process rank    
  if(MPI_Comm_size(MPI_COMM_WORLD, &nproc) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER); // Get total number of processes specificed at start of run

  umask(022) ; //Setting the umask to 022   
  //  MEM_AVL = sysconf(_SC_PAGESIZE)*sysconf(_SC_AVPHYS_PAGES) ; //Determining total memory of the system  
  MEM_AVL = 1024*1024*1024 ;
  
  if (argc < 2) 
    usage();
  else if(argc == 2) {
    if(verifydir(argv[argc-1])) singlefileflag = 2 ;
    else {
      strncpy(targetdir, argv[argc-1], strrchr(argv[argc-1],'/') - argv[argc-1]) ;	
      if(verifydir(targetdir)) singlefileflag = 1 ;
      else {
	printf("Given target path %s does not exist \n", targetdir);
	MPI_Abort(MPI_COMM_WORLD, 1) ;
      }
    }
  }
  else if(argc > 2) {
    targetshouldbedir = 1;    
    if(verifydir(argv[argc-1])) {
      targetisdir = 1 ;
      strcpy(targetdir, argv[argc-1]) ; //Fixing the target directory 
    }
    else {
      if(procID == 0) {
	printf("Target must be a directory if more than 1 files are to be transferred\n");
	usage() ;
      }
      MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER);
    }
  }  

  if(mpi_fileattr_define() != MPI_SUCCESS) {
    printf("Error in constructing MPI datatype\n") ;
    MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER);
  }

  strcpy(targetdir, argv[argc-1]) ; //Fixing the target directory or file 
  while(argc-- > 1) {
    if (procID == 0) {
      if(stat(*argv, &stbuf)==0) {     
      //      printf("P: %d %s \n", procID, *argv) ;
	ArgStatus = argument_status(&stbuf) ;
      } else ArgStatus = 0 ;
    }
    
    if(MPI_Bcast(&ArgStatus, 1, MPI_INT, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;
  
    if(ArgStatus == 2) {
      if(iamrecursive == 1) {
	if(procID == 0) {
	  if(strrchr(*argv,'/') == NULL) {
	    basedir_jump = 0 ;
	    sprintf(basedir,"/%s", *argv)  ;
	    //	  strcpy(*argv, basedir)  ;	  
	  }
	  else {
	    strcpy(basedir,strrchr(*argv,'/')) ;
	    basedir_jump = strrchr(*argv,'/') - *argv ;
	  }
	  printf("The base directory is %s \n", basedir) ;	  
	  sprintf(targetpath, "%s%s", targetdir, basedir) ;
	  if(getfileattr(stbuf, &att_file)) strcpy((char *) att_file.pathname, targetpath);
	}	
	
	if(MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;
	mkdir((char *) att_file.pathname, 00755) ;
	
	filecount += dirwalk_nfiles(*argv, procID, nproc) ;
	
	if(pflag) {
	  file_times.actime = att_file.atime ;
	  file_times.modtime = att_file.mtime ;
	  utime((char *)att_file.pathname, &file_times) ;
	  chmod((char *)att_file.pathname, att_file.mode) ;      
	}
      } else printf("Skipping directory %s \n",*argv) ;
      
    }
    
    if(ArgStatus == 1) {
      if(procID == 0)     {
	if(singlefileflag == 1) strcpy(targetpath, targetdir) ;
	else {
	  if(strrchr(*argv,'/') == NULL) sprintf(targetpath, "%s/%s", targetdir,*argv) ;
	  else sprintf(targetpath, "%s%s", targetdir,strrchr(*argv,'/')) ;	      
	}
	if(getfileattr(stbuf, &att_file)) {
	  strcpy((char *) att_file.pathname, targetpath);
	  file_d_read = open(*argv, O_RDONLY) ;
	}
      }

      if(MPI_Bcast(&att_file,1,MPI_FileAttr, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;
      file_d_write = creat((char *) att_file.pathname, 00644) ;
      file_rem = att_file.filesize ;
      filedata = (char *) malloc ((BLKSIZE)*sizeof(char)) ;	

      while(file_rem > 0) {
	file_read = (file_rem < BLKSIZE ? file_rem : BLKSIZE) ;
	if(procID == 0) bytecount += read(file_d_read, &filedata[0], BLKSIZE) ;	      
	if(MPI_Bcast(filedata, file_read, MPI_UNSIGNED_CHAR, 0, MPI_COMM_WORLD) != MPI_SUCCESS) MPI_Abort(MPI_COMM_WORLD, MPI_ERR_OTHER) ;	  
	bytecount += write(file_d_write, &filedata[0], file_read) ;
	file_rem -= file_read ;
      }

      if(procID == 0) close(file_d_read) ;
      close(file_d_write) ;

      if(pflag) {
	file_times.actime = att_file.atime ;
	file_times.modtime = att_file.mtime ;
	utime((char *)att_file.pathname, &file_times) ;
	chmod((char *)att_file.pathname, att_file.mode) ;
      }
	
    }   
    
    argv++ ;
    filecount++ ;
  }
  
  if(procID == 0) printf("The total number of files is %d \n", filecount) ;
  
  MPI_Finalize();
  
  return 0 ;
  
}





