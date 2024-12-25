**Bad Apple MS0511**
<br />Guess what ^_^
<br />
<br />*MZ (floppy) version:*
<br />This version is without music. FDD reading code in ROM is heavily used PPU and its timer - so there is no simple way to add .psg or .pt3 music playing routine (but not impossible)
<br />Files: [bappmz.dsk](/release/bappmz.dsk?raw=true) - disk image
<br />MS-0511 (UKNC) emulator: [https://github.com/nzeemin/ukncbtl](https://github.com/nzeemin/ukncbtl)
<br />(can be FD read error on this emulator until FDD bug is resolved)
<br />
<br />*WD HDD version:*
<br />This one is using [HDD controller](https://github.com/nzeemin/ukncbtl-doc/wiki/IDE-HDD-ru) for MS0511 (WD version of firmware is used). 
It's 320x240 25fps, ~24kHz 8-bit mono covox output (LPT port A 177100 or new covox 177372 if detected). 
<br />Files: [bappwd.img](/release/bappwd.img?raw=true) - HDD WD disk image (inverted data)
<br />Modified UKNCBTL emulator executable: [ukncbtl.exe](/emulator/ukncbtl.exe?raw=true)
<br />It was modified to reduce sector reading timeouts (DRQ bit on IDE 0x1F7 port). Also LBA28 support for IDE emulation was added.
Take other files from /emulator folder if you want, though they aren't modified.
<br />Attach ide_wdromv0110.bin as cartridge ROM and bappwd.img as disk image. Boot MS0511 from option 2.
<br />If you want to keep RT-11 partitions - write bappwd.img on HDD right after them. For instance - you have 8 RT-11 partitions with 65535 blocks size each. 8*65535+1 (one for zero sector) = 524281. Write bappwd.img from that sector. Then use [bappwd.sav](/release/bappwd.sav?raw=true) launcher from RT-11.
<br /> 
<br />Floppy version picture
<br />![Screenshot 1](/screenshots/bappmz_1.png?raw=true)
