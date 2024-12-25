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
php -f ../scripts/preprocess.php bappmz.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
..\scripts\macro11 -ysl 32 -yus -l _bappmz.lst _bappmz.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
php -f ../scripts/lst2bin.php _bappmz.lst ./bappmz.bin bin 0
if %ERRORLEVEL% NEQ 0 ( exit /b )

echo.
echo ===========================================================================
echo Make data and DSK
echo ===========================================================================
php -f ./scripts/make_data.php
if %ERRORLEVEL% NEQ 0 ( exit /b )
copy /b bappmz.bin+data.bin release\bappmz.dsk >NUL

del bappmz.bin
del data.bin
del _bappmz.mac
del _bappmz.lst

echo.
emulator\ukncbtl
