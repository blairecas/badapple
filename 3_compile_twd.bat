@echo off

echo.
echo ===========================================================================
echo Compiling 
echo ===========================================================================
php -f ../scripts/preprocess.php testwd.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
..\scripts\macro11 -ysl 32 -yus -m ..\scripts\sysmac.sml -l _testwd.lst _testwd.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
php -f ../scripts/lst2bin.php _testwd.lst _testwd.bin bin 0 76000
if %ERRORLEVEL% NEQ 0 ( exit /b )
rem php -f ./scripts/make_dataw.php
rem if %ERRORLEVEL% NEQ 0 ( exit /b )

copy /b block0.img+_testwd.bin+_bappwd.bin bappwd.img >NUL

php -f ../scripts/invert.php bappwd.img
if %ERRORLEVEL% NEQ 0 ( exit /b )

rem del _bappwd.bin
rem del _testwd.bin
del _testwd.mac
rem del _testwd.lst

cd emulator
ukncbtl
