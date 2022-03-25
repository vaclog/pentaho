
echo "Ejecutando chequeo" > C:\LOGS\sql_check.log 
rem net start >> C:\LOGS\sql_check.log
rem wmic service where (PathName like '%sql%') get caption, name, startmode, state, PathName, ProcessId >> c:\LOGS\sql_check.log
rem sc >> C:\LOGS\sql_check.log
rem sc query MSSQL$SQL2008R2EX >> C:\LOGS\sql_check.log
sc query MSSQL$SQL2008R2EX | findstr /I /C:ESTADO | findstr /I /C:RUNNING 

if ERRORLEVEL 1 (echo "hola") else (echo "Run OK") >> C:\LOGS\sql_check.log