Spark can be used as an alternative to expensive SQL queries for
maintaining the sw_app field.  This directory will contain some
scripts that can be used to maintain a shadow copy of the pbsacct DB
in Spark parquet files.  

The sw_app-index script will be updated with an option to use the
parquet files instead of directly connecting to the DB.  This
alternative code path will perform the following.

1. Read the location of the Spark parquet file from a configuration
   file.

2. Load the file, and the temporary view to allow spark.sql queries.

3. Update the sw_app field, and write the updated data out to the
   parquet version of the data.

4. Write a CSV file with the jobid, and sw_app value for every entry
   that's changed.  This file is suitable for a simple script to
   perform the updates to the DB.  Eventually this intermediate file,
   and script should be replaced with performing updates directly to
   the DB.
