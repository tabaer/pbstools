#include <stdio.h>
#include <stdlib.h>
#include <sys/stat.h>
#include <getopt.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/dir.h>
#include <string.h>
#include <sys/param.h>

#ifndef OTHER_DEFS
#define FALSE 0
#define TRUE !FALSE
#define MAX_PATH 1024
typedef unsigned char int8 ;
#endif /* OTHER_DEFS*/

/*usage: Prints the usage of the scatter command */
void usage(void) {

  (void) fprintf(stderr,		 
		 " Usage:  pbsdcp [-g|-s] [options] srcfile [...srcfiles...] target\n"
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
  if (!stat(cp, &stb)) {
    if (S_ISDIR(stb.st_mode))
      return 1;
    else {
      errno = ENOTDIR;      
      return 0;    
    }
    
  } 
  
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

/*striptrailingslashes: Strips the arguments of the trailing slashes if any*/
void striptrailingslashes(int argc, char ***argv) {
  
  int count ;
  char temppath[MAX_PATH] ;
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

