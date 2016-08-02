/* Copyright 2008 Ohio Supercomputer Center */

/* Distribution of this program is governed by the GNU GPL.  See
   ../COPYING for details. */

/*
   Usage:  mpiexec [-n #] [arg] parallel-command-processor cfgfile
      OR:  mpiexec [-n #] [arg] parallel-command-processor << EOF
           cmd1
           cmd2
           ...
           cmdM
           EOF
 */

#include <limits.h>
#include <mpi.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

#define REQUEST_TAG  0
#define STATUS_TAG   1
#define DATA_TAG     2

void remove_EOL(char *string)
{
  int len = strlen(string);
  for ( int i=0 ; i<len ; i++ )
    {
      if ( string[i]=='\n' ) string[i]=='\0';
    }
}

void minion(int rank)
{
  char cmd[LINE_MAX+1];
  int cont;
  MPI_Status mystat;
  int ncmds = 0;

  /* setup */
  cont = 1;

  /* initial distribution */
  MPI_Barrier(MPI_COMM_WORLD);

  /* main loop */
  while ( cont==1 )
    {
      int nbytes;
      int retcode;
      memset(cmd,'\0',(size_t)LINE_MAX);
      MPI_Recv(cmd,LINE_MAX,MPI_CHAR,0,DATA_TAG,MPI_COMM_WORLD,&mystat);
      /* do your thing */
      if ( strlen(cmd)>0 )
	{
#ifdef DEBUG
	  printf("Rank %d:  executing \"%s\"\n",rank,cmd);
#endif /* DEBUG */
	  retcode = system(cmd);
#ifdef DEBUG
	  ncmds++;
#endif /* DEBUG */
	}
      MPI_Send(&retcode,1,MPI_INT,0,REQUEST_TAG,MPI_COMM_WORLD);
      MPI_Recv(&cont,1,MPI_INT,0,STATUS_TAG,MPI_COMM_WORLD,&mystat);
    }

  /* cleanup */
  MPI_Barrier(MPI_COMM_WORLD);
#ifdef DEBUG
  MPI_Reduce(&ncmds,NULL,1,MPI_INT,MPI_SUM,0,MPI_COMM_WORLD);
#endif /* DEBUG */
}

void mastermind(int nminions, FILE *input)
{
  int cont = 1;
  int stop = 0;
  int ncmds = 0;
  char cmd[LINE_MAX+1];

  /* setup */

  /* initial distribution */
  MPI_Barrier(MPI_COMM_WORLD);
  while ( ncmds<nminions && !feof(input) )
    {
      memset(cmd,'\0',(size_t)LINE_MAX);
      fgets(cmd,LINE_MAX,input);
      while ( ( strlen(cmd)==0 || cmd[0]=='#' ) && !feof(input) )
	{
	  memset(cmd,'\0',(size_t)LINE_MAX);
	  fgets(cmd,LINE_MAX,input);
	  if ( strlen(cmd)>0 ) remove_EOL(cmd);
	}
      if ( strlen(cmd)>0 )
	{
	  MPI_Send(cmd,strlen(cmd)-1,MPI_CHAR,ncmds+1,DATA_TAG,MPI_COMM_WORLD);
	  ncmds++;
	}
    }

  /* main loop */
  while ( !feof(input) )
    {
      int retcode;
      int next;
      MPI_Status mystat;

      MPI_Recv(&retcode,1,MPI_INT,MPI_ANY_SOURCE,REQUEST_TAG,MPI_COMM_WORLD,
	       &mystat);
      next = mystat.MPI_SOURCE;
 #ifdef DEBUG
      printf("Rank 0:  rank %d returned code %d\n",next,retcode);
#endif /* DEBUG */
      MPI_Send(&cont,1,MPI_INT,next,STATUS_TAG,MPI_COMM_WORLD);
      memset(cmd,'\0',(size_t)LINE_MAX);
      fgets(cmd,LINE_MAX,input);
      while ( ( strlen(cmd)==0 || cmd[0]=='#') && !feof(input) )
	{
	  memset(cmd,'\0',(size_t)LINE_MAX);
	  fgets(cmd,LINE_MAX,input);
	  if ( strlen(cmd)>0 ) remove_EOL(cmd);
	}
      if ( strlen(cmd)>0 )
	{
	  MPI_Send(cmd,strlen(cmd)-1,MPI_CHAR,next,DATA_TAG,
		   MPI_COMM_WORLD);
	}
    }

  /* cleanup */
  MPI_Request *req;
  req = (MPI_Request *)calloc((size_t)(2*nminions),sizeof(MPI_Request));
  for ( int i = 1 ; i <= nminions ; i++ )
    {
      char exitcmd[] = "exit";
      MPI_Isend(exitcmd,strlen(exitcmd),MPI_CHAR,i,DATA_TAG,MPI_COMM_WORLD,
		req+(size_t)(2*i)*sizeof(MPI_Request));
      MPI_Isend(&stop,1,MPI_INT,i,STATUS_TAG,MPI_COMM_WORLD,
		req+(size_t)(2*i+1)*sizeof(MPI_Request));
    }
  MPI_Barrier(MPI_COMM_WORLD);
#ifdef DEBUG
  ncmds=0;
  MPI_Reduce(&stop,&ncmds,1,MPI_INT,MPI_SUM,0,MPI_COMM_WORLD);
  printf("Executed %ld commands\n",ncmds);
#endif /* DEBUG */
  free(req);
}

int main(int argc, char *argv[])
{
  int rank;
  int nproc;
  FILE *input;

  MPI_Init(&argc,&argv);
  MPI_Comm_rank(MPI_COMM_WORLD,&rank);
  MPI_Comm_size(MPI_COMM_WORLD,&nproc);
  if ( nproc<2 && rank==0 )
    {
      fprintf(stderr,"%s:  At least 2 MPI processes required!\n",argv[0]);
      exit(-1);
    }
  if ( rank==0 )
    {
      if ( argc>1 )
	{
	  input = fopen(argv[1],"r");
	}
      else
	{
	  input = stdin;
	}
      mastermind(nproc-1,input);
    }
  else
    {
      minion(rank);
    }
  MPI_Finalize();
  return(0);
}
