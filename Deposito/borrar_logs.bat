
:: Directorio donde se encuentran los archivos
set "DIRECTORIO=C:\LOGS"

:: Número de días (30 días)
set "DIAS=30"

echo "comienza borrado"
:: Comando forfiles para borrar archivos .txt modificados en los últimos 30 días
forfiles /p "%DIRECTORIO%" /s /m *.txt /d -%DIAS% /c "cmd /c del @path"

echo Archivos .txt modificados en los últimos %DIAS% días han sido eliminados.
pause
