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
<br />Restrictions: HDD logical CHS must be with 16 heads per cylinder, 63 sectors per track. Demo is using these numbers when reading data in ATA PIO CHS mode.
<br />Files: [bappwd.img](/release/bappwd.img?raw=true) - HDD WD disk image (inverted data)
<br />Modified UKNCBTL emulator executable: [ukncbtl.exe](/emulator/ukncbtl.exe?raw=true)
<br />It was modified to reduce sector reading timeouts (buffer readiness on IDE 0x1F7 port).
Take other files from /emulator folder if you want, though they aren't modified.
<br />Attach ide_wdromv0110.bin as cartridge ROM and bappwd.img as disk image. Boot MS0511 from option 2.
<br />If you want to keep RT-11 partitions - write bappwd.img on HDD somewhere after them 
on addr *aligned with full track* (63 sectors - 32256 bytes). Use [bappwd.sav](/release/bappwd.sav?raw=true) launcher from RT-11.
<br /> 
<br />Floppy version picture
<br />![Screenshot 1](/screenshots/bappmz_1.png?raw=true)
