build: pbsdcp-scatter 

pbsdcp-scatter: pbsdcp-scatter.c misc.o  fileattr.o
	$(MPICC) -o pbsdcp-scatter pbsdcp-scatter.c misc.o fileattr.o

misc.o: misc.c fileattr.h fileattr.o
	$(MPICC) -c misc.c

fileattr.o: fileattr.c fileattr.h
	$(MPICC) -c fileattr.c

fileattr.h : 

clean : 
	rm -rf pbsdcp-scatter *.o
