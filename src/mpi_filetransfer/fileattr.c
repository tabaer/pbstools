#include "fileattr.h"

/*mpi_fileattr_define: Defines the MPI Datatype corresponding to the FileAttr 
                       type. Returns 1 on successful definition */
int mpi_fileattr_define() {    
    
    struct FileAttr file1 ;
    /* length, displacement, and type arrays used to describe an MPI derived type */
    /* their size reflects the number of components in SparseElt */
    int          lena[6]; 
    MPI_Aint     loca[6]; 
    MPI_Datatype typa[6];

    MPI_Aint     baseaddress;
   
    MPI_Address(&file1, &baseaddress);
    lena[0] = PATH_MAX;    /* file1.pathname has length of PATH_MAX chars*/
    if(MPI_Address(&file1.pathname,&loca[0]) != MPI_SUCCESS) return MPI_ERR_OTHER ; 
    loca[0] -= baseaddress;  /* byte address relative to start of structure */
    typa[0] = MPI_UNSIGNED_CHAR;
   
    lena[1] = 1;    /* file1.mode has length of 1 unsigned int*/
    if(MPI_Address(&file1.mode,&loca[1]) != MPI_SUCCESS) return MPI_ERR_OTHER ; 
    loca[1] -= baseaddress;  /* byte address relative to start of structure */
    typa[1] = MPI_UNSIGNED;
    
    lena[2] = 1;    /* file1.filesize has length of 1 unsigned long int*/
    if(MPI_Address(&file1.filesize,&loca[2]) != MPI_SUCCESS) return MPI_ERR_OTHER; 
    loca[2] -= baseaddress;  /* byte address relative to start of structure */
    typa[2] = MPI_UNSIGNED_LONG;
   
    lena[3] = 1;    /* file1.atime has length of 1 unsigned long int*/
    if(MPI_Address(&file1.atime,&loca[3]) != MPI_SUCCESS) return MPI_ERR_OTHER; 
    loca[3] -= baseaddress;  /* byte address relative to start of structure */
    typa[3] = MPI_UNSIGNED_LONG;
    
    lena[4] = 1;    /* file1.mtime has length of 1 unsigned long int*/
    if(MPI_Address(&file1.mtime,&loca[4]) != MPI_SUCCESS) return MPI_ERR_OTHER; 
    loca[4] -= baseaddress;  /* byte address relative to start of structure */
    typa[4] = MPI_UNSIGNED_LONG;
    
    lena[5] = 1;    /* file1.ctime has length of 1 unsigned long int*/
    if(MPI_Address(&file1.ctime,&loca[5]) != MPI_SUCCESS) return MPI_ERR_OTHER; 
    loca[5] -= baseaddress;  /* byte address relative to start of structure */
    typa[5] = MPI_UNSIGNED_LONG;    
    
    if(MPI_Type_struct(6, lena, loca, typa, &MPI_FileAttr) != MPI_SUCCESS) return MPI_ERR_OTHER;
    if(MPI_Type_commit(&MPI_FileAttr) != MPI_SUCCESS) return MPI_ERR_OTHER;

    return MPI_SUCCESS ;
}


