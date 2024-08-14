FOR /F "tokens=*" %%A IN ('DATE/T') DO FOR %%B IN (%%A) DO SET Today=%%B

FOR /F "tokens=1-3 delims=/-" %%A IN ("%Today%") DO (
    SET DayMonth=%%A
    SET MonthDay=%%B
    SET Year=%%C
)

SET FILENAMELOG=%Year%-%MonthDay%-%DayMonth%
set RUN=C:\Users\ValkUser\Downloads\pdi-ce-8.0.0.0-28\data-integration
set FILE_RUN=C:\Users\ValkUser\Downloads\pdi-ce-8.0.0.0-28\data-integration\src\Deposito\orien\ingreso

net use > C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1
net use T: \\192.168.0.200\OrienInterfaces /persistent:no >>C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1
net use >> C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1
echo "paso 1" >> C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1
cd c:\Users\Public\InterfacesOrien\T_EntradaOut >> C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1
rem dir >> C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1
echo "paso 2" >> C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1


rem %RUN%\Kitchen.bat /file "%FILE_RUN%\main.kjb"  "/param:SOURCE_PATH=c:\Users\Public\InterfacesOrien\T_EntradaOut" >> C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1
%RUN%\Kitchen.bat /file "%FILE_RUN%\main.kjb"   >> C:\LOGS\orien_ingreso_%FILENAMELOG%.3.txt 2>&1