set RUN=C:\Users\ValkUser\Downloads\pdi-ce-8.0.0.0-28\data-integration
set FILE_RUN=C:\Users\ValkUser\Documents\GitHub\pentaho\Deposito\orien\ingreso
set LOGFILE=T:\T_EntradaOut\log\control.txt
%RUN%\Kitchen.bat /file:"%FILE_RUN%\main.kjb" "/param:SOURCE_PATH=T:\T_EntradaOut" "/param:PROCESSED_PATH=\\192.168.0.200\OrienInterfaces\T_EntradaOut\procesados" "/param:ORIEN_PATH=C:\Users\ValkUser\OneDrive - VACLOG\Orien\recepcion" > %LOGFILE% 2>&1
			