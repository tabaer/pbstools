build: scatter 

scatter: scatter.c misc.o  fileattr.o
	mpicc -o scatter scatter.c misc.o fileattr.o

misc.o: misc.c fileattr.h fileattr.o
	mpicc -c misc.c

fileattr.o: fileattr.c fileattr.h
	mpicc -c fileattr.c

fileattr.h : 

clean : 
	rm -rf scatter mpi_file_transfer *.o
