FOR /F "tokens=*" %%A IN ('DATE/T') DO FOR %%B IN (%%A) DO SET Today=%%B

FOR /F "tokens=1-3 delims=/-" %%A IN ("%Today%") DO (
    SET DayMonth=%%A
    SET MonthDay=%%B
    SET Year=%%C
)

SET FILENAMELOG=%Year%-%MonthDay%-%DayMonth%
set RUN=C:\Users\ValkUser\Downloads\pdi-ce-8.0.0.0-28\data-integration
set FILE_RUN=C:\Users\ValkUser\Downloads\pdi-ce-8.0.0.0-28\data-integration\src\Deposito\elca\nota_venta
CALL %RUN%\Kitchen.bat /file "%FILE_RUN%\main.kjb" "/param:READPATH=C:\Users\ValkUser\Documents\GitHub\pentaho\Deposito\elca\nota_venta\" /level:Basic > C:\LOGS\elca_nota_venta_%FILENAMELOG%.3.txt 2>&1