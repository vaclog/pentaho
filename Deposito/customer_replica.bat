FOR /F "tokens=*" %%A IN ('DATE/T') DO FOR %%B IN (%%A) DO SET Today=%%B

FOR /F "tokens=1-3 delims=/-" %%A IN ("%Today%") DO (
    SET DayMonth=%%A
    SET MonthDay=%%B
    SET Year=%%C
)

SET FILENAMELOG=%Year%-%MonthDay%-%DayMonth%
rem set RUN=C:\Users\ValkUser\Downloads\pdi-ce-9.2.0.0-290\data-integration
set RUN=C:\Users\ValkUser\Downloads\pdi-ce-8.0.0.0-28\data-integration
set FILE_RUN=C:\Users\ValkUser\Downloads\pdi-ce-8.0.0.0-28\data-integration\src\Deposito\customers
%RUN%\Kitchen.bat /file "%FILE_RUN%\main.kjb"  > C:\LOGS\customers_%FILENAMELOG%.txt 2>&1