@echo off

echo.
echo ===========================================================================
echo Compiling 
echo ===========================================================================
php -f ../../scripts/preprocess.php hdboot.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
..\..\scripts\macro11 -ysl 32 -yus -m ..\..\scripts\sysmac.sml -l _hdboot.lst _hdboot.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
php -f ../../scripts/lst2bin.php _hdboot.lst hdboot.bin bin 0 1000
if %ERRORLEVEL% NEQ 0 ( exit /b )

del _hdboot.mac
del _hdboot.lst

cl hddsender.cpp
del hddsender.obj

hddsender.exe
