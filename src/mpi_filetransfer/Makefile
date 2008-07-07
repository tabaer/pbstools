build: scatter 

scatter: scatter.c misc.o mpi_file_transfer.o fileattr.o
	mpicc -o scatter scatter.c misc.o mpi_file_transfer.o fileattr.o

misc.o: misc.c
	mpicc -c misc.c

mpi_file_transfer.o: mpi_file_transfer.c fileattr.o
	mpicc -c mpi_file_transfer.c 

fileattr.o: fileattr.c fileattr.h
	mpicc -c fileattr.c

fileattr.h : 

clean : 
	rm -rf scatter mpi_file_transfer *.o
