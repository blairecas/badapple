@echo off

rem echo.
rem echo ===========================================================================
rem echo Graphics
rem echo ===========================================================================
rem php -f ./scripts/create_tiles.php
rem if %ERRORLEVEL% NEQ 0 ( exit /b )
rem php -f ./scripts/create_tables.php
rem if %ERRORLEVEL% NEQ 0 ( exit /b )
rem php -f ./scripts/conv_graphics.php
rem if %ERRORLEVEL% NEQ 0 ( exit /b )

echo.
echo ===========================================================================
echo Compiling 
echo ===========================================================================
php -f ../scripts/preprocess.php dmain.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
..\scripts\macro11 -ysl 32 -yus -l _dmain.lst _dmain.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
php -f ../scripts/lst2bin.php _dmain.lst ./dmain.bin bin 0
if %ERRORLEVEL% NEQ 0 ( exit /b )

echo.
echo ===========================================================================
echo Make data and DSK
echo ===========================================================================
php -f ./scripts/make_data.php
if %ERRORLEVEL% NEQ 0 ( exit /b )
copy /b dmain.bin+data.bin bappmz.dsk >NUL

del dmain.bin
del data.bin
del _dmain.mac
del _dmain.lst

echo.
start ..\..\ukncbtl\ukncbtl /autostart /disk0:..\badapple\BAPPMZ.DSK /boot1
