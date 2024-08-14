
:: Directorio donde se encuentran los archivos
set "DIRECTORIO=C:\LOGS"

:: N�mero de d�as (30 d�as)
set "DIAS=30"

echo "comienza borrado"
:: Comando forfiles para borrar archivos .txt modificados en los �ltimos 30 d�as
forfiles /p "%DIRECTORIO%" /s /m *.txt /d -%DIAS% /c "cmd /c del @path"

echo Archivos .txt modificados en los �ltimos %DIAS% d�as han sido eliminados.
pause
