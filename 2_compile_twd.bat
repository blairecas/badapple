@echo off

rem compile hdd binary
php -f ../scripts/preprocess.php testwd.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
..\scripts\macro11 -ysl 32 -yus -m ..\scripts\sysmac.sml -l _testwd.lst _testwd.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
php -f ../scripts/lst2bin.php _testwd.lst _testwd.bin bin 0 61000
if %ERRORLEVEL% NEQ 0 ( exit /b )

rem compile .sav
php -f ../scripts/preprocess.php bappwd.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
..\scripts\macro11 -ysl 32 -yus -m ..\scripts\sysmac.sml -l _bappwd.lst _bappwd.mac
if %ERRORLEVEL% NEQ 0 ( exit /b )
php -f ../scripts/lst2bin.php _bappwd.lst ./release/bappwd.sav sav
if %ERRORLEVEL% NEQ 0 ( exit /b )

rem create video data
rem php -f ./scripts/make_dataw.php
rem if %ERRORLEVEL% NEQ 0 ( exit /b )

rem make and invert image
copy /b block0.img+_testwd.bin+_bappwd.bin .\release\bappwd.img >NUL
php -f ../scripts/invert.php ./release/bappwd.img
if %ERRORLEVEL% NEQ 0 ( exit /b )

rem remove not needed files
rem del _bappwd.bin
del _testwd.bin
del _testwd.mac
rem del _testwd.lst
del _bappwd.mac
del _bappwd.lst

rem run modified emulator
emulator\ukncbtl
