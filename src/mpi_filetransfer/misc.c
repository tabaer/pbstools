#include "fileattr.h"
/*usage: Prints the usage of the scatter command */
void usage(void) {

  printf(" Usage:  pbsdcp [-g|-s] [options] srcfile [...srcfiles...] target\n"
	 " Options:\n"
	 "  -g   gather mode \n"
	 "  -s   scatter mode (default) \n"
	 "  -h   print a help message \n"
	 "  -p   preserve modification times and permissions \n"
	 "  -r   recursive copy \n") ;
  
  exit(1);
}

/*printarguments: Reprints the command and the arguments that were used to run this code*/
void printarguments(int argc, char **argv) {

  int count ;

  printf ("This program was called with \"%s\".\n",argv[0]);
  if (argc > 1)
    {
      for (count = 1; count < argc; count++)
	{
	  printf("argv[%d] = %s\n", count, argv[count]);
	}
    }
  else
    {
      printf("The command had no other arguments.\n");
    }
}

/*verifydir: Verifies if the given file is a directory */
int verifydir(char *cp) {

  struct stat stb;
  if (stat(cp, &stb) == 0) {
    if (S_ISDIR(stb.st_mode))
      return 1;
    else {
      errno = ENOTDIR;      
      return 0;    
    }    
  }
  else return 0 ;
  
  //	run_err("%s: %s", cp, strerror(errno));
  //	killchild(0);
}

/*verifyregfile: Verifies if the given file is a regular file*/
int verifyregfile(char *cp)   {

  struct stat stb;  
  if (!stat(cp, &stb)) {
    if (S_ISREG(stb.st_mode))
      return 1;
  } else return 0 ;
  
}

/*argument_status: Checks if the stat buffer in the argument is a file or directory or neither*/
/*   0  --> Neither a file nor a directory. Can be skipped */
/*   1  --> File  */
/*   2  --> Directory */
int argument_status(struct stat *stbuf) {
  if (S_ISDIR((*stbuf).st_mode)) return ARG_IS_DIR;
  else if(S_ISREG((*stbuf).st_mode)) return ARG_IS_FILE;
  else return ARG_NOT_REG_FILE;
}


/*striptrailingslashes: Strips the arguments of the trailing slashes if any*/
void striptrailingslashes(int argc, char ***argv) {
  
  int count ;
  char temppath[PATH_MAX] ;
  int arglength ; 
  
  for(count=0;count<argc;count++) {
    //    printf("Before correction %s ", (*argv)[count]) ;
    if(strrchr( (*argv)[count], '/' ) == NULL) ; //printf("No corrections to be made for %s\n", (*argv)[count]) ;	    
    else if( strlen(strrchr( (*argv)[count], '/' )) == 1) {
      arglength = strlen((*argv)[count]) ;
      memcpy(temppath, (*argv)[count], arglength-1) ;
      temppath[arglength-1]= '\0' ; //Adding the string terminating character
      strcpy((*argv)[count], temppath) ;
      //      printf("After correction %s \n", (*argv)[count]) ;   
      
    }
    else ; //printf("No corrections to be made for %s\n", (*argv)[count]) ;	    

  }
  return ;
}

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

