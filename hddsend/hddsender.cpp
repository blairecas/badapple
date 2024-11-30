#include <windows.h>
#include <stdio.h>

    char sfname[1024] = "hdd.img";
    char sport[256] = "COM3";
    unsigned int speed = 57600;

    HANDLE hport = NULL;
    DCB dcb = {};

    FILE *f;
    unsigned char *hdboot;
    unsigned long bootsize;

    unsigned char b;
    unsigned long dw;


// wait for command from MS0511
//
unsigned char getMsCommand ()
{
    printf("Waiting for command from MS0511 (ctrl-C quit)... ");
    while (true) {
        if (!ReadFile(hport, &b, 1, &dw, NULL)) continue;
        if (dw == 0) continue;
        if (b != 0x00) break;
    }
    printf("got 0x%X\n", b);
    return b;
}


// send bootloader and main program there
//
void sendBoot ()
{
    printf("Sending bootloader... ");
    for (int i=0; i<512; i++) {
        if (!WriteFile(hport, hdboot+i, 1, &dw, NULL)) {
            printf("(!) error while sending hdboot.bin\n");
            exit(1);
        }
    }
    PurgeComm(hport, PURGE_RXCLEAR | PURGE_TXCLEAR);
    Sleep(2000); // (!) delay is important for USB-RS232 adapters
    printf("first block of bootloader is sent\n");
    // we can send rest of bootloader then
    printf("Sending rest of bootloader, %i bytes... ", bootsize-512+2);
    // (!) without check for errors, sending size as MSB
    unsigned long l = bootsize - 512;
    b = (l & 0xFF00) >> 8; 
    WriteFile(hport, &b, 1, &dw, NULL);
    b = l & 0xFF; 
    WriteFile(hport, &b, 1, &dw, NULL);
    // send rest of program
    for (int i=0; i<l; i++) {
        if (!WriteFile(hport, hdboot+512+i, 1, &dw, NULL)) { 
            printf("(!) error while sending hdboot.bin\n"); 
            exit(1); 
        }
    }
    PurgeComm(hport, PURGE_RXCLEAR | PURGE_TXCLEAR);
    Sleep(3000);
    printf("sent\n\n");
}


// read sectors from MS0511 HDD
//
void readSectors ()
{
    unsigned int i = 0, l = 0;
    // reading 3 bytes sectors count MSB
    ReadFile(hport, &b, 1, &dw, NULL);
    l = l | (b << 16);
    ReadFile(hport, &b, 1, &dw, NULL);
    l = l | (b << 8);
    ReadFile(hport, &b, 1, &dw, NULL);
    l = l | b;
    // reading data
    f = fopen(sfname, "wb");
    printf("Opening %s to get HDD image from MS0511\n", sfname);
    while (i < l) {
        printf("Reading blocks <- MS0511... %i of %i\r", i+1, l);
        for (int j=0; j<512; j++) {
            ReadFile(hport, &b, 1, &dw, NULL);
            fwrite(&b, 1, 1, f);
        }
        i++;
    }
    fclose(f);
    printf("\n");
    PurgeComm(hport, PURGE_RXCLEAR | PURGE_TXCLEAR);
    CloseHandle(hport);
    Sleep(1000);
}


// write one block
//
void writeBlock ()
{
    // writing
    for (int j=0; j<512; j++) {
        if (!feof(f)) fread(&b, 1, 1, f); else b = 0x00;
        WriteFile(hport, &b, 1, &dw, NULL);
    }
    Sleep(10); // it's needed, our speed is way too fast
    while (true) { // wait for acknowledge 
        if (!ReadFile(hport, &b, 1, &dw, NULL)) continue;
        if (dw == 0) continue;
        if (b == 0xAA) break;
    }
}


// write sectors to MS0511 HDD
//
void writeSectors ()
{
    unsigned int l = 0, lo = 0;
    printf("Opening %s to send HDD image to MS0511\n", sfname);
    f = fopen(sfname, "rb");
    fseek(f, 0L, SEEK_END); lo = ftell(f); fseek(f, 0L, SEEK_SET);
    l = lo / 512;
    if ((lo%512) != 0) l++;
    // send 3 bytes of sectors count MSB
    printf("Sending sectors count (%u)... ", l);
    b = (l & 0xFF0000) >> 16;
    WriteFile(hport, &b, 1, &dw, NULL);
    b = (l & 0xFF00) >> 8; 
    WriteFile(hport, &b, 1, &dw, NULL);
    b = l & 0xFF;
    WriteFile(hport, &b, 1, &dw, NULL);
    printf("sent\n");
    // sending data
    while (l > 0) {
        printf("Writing sectors -> MS0511... %u \r", l);
        writeBlock();
        l--;
    }
    fclose(f);
    printf("\n");
    PurgeComm(hport, PURGE_RXCLEAR | PURGE_TXCLEAR);
    Sleep(3000);
    CloseHandle(hport);
}


// main program entry point
//
void main ( int argc, char* argv[] )
{
    // program arguments and usage info
    if (argc < 2 || argc > 5) {
        printf("MS0511 WD HDD image sender over RS-232\n\n");
        printf("Usage: hddsend [hdd.img] [port] [speed]\n");
        printf("port - default COM3\n");
        printf("speed - default 57600\n\n");
    }
    if (argc >= 2) strncpy(sfname, argv[1], 1023);
    if (argc >= 3) strncpy(sport, argv[2], 255);
    if (argc == 4) {
        speed = atoi(argv[5]);
        if (speed < 100 || speed > 57600) {
            printf("(!) bad speed number, must be [100..57600]\n");
            exit(1);
        }
    }

    // read MS0511 loader
    f = fopen("hdboot.bin", "rb");
    if (!f) {
        printf("(!) unable to open bootloader file hdboot.bin\n");
        exit(1);
    }
    fseek(f, 0L, SEEK_END); bootsize = ftell(f); fseek(f, 0L, SEEK_SET);
    if (bootsize < 02000) {
        printf("(!) invalid hdboot.bin size, must be not less than 1024 bytes\n");
        exit(1);
    }
    hdboot = (unsigned char *) malloc(bootsize);
    fread(hdboot, 1, bootsize, f);
    fclose(f);

    // setup comm port
    hport = CreateFileA(sport, GENERIC_READ | GENERIC_WRITE, 0, NULL, OPEN_EXISTING, 0, NULL);
    if (hport == INVALID_HANDLE_VALUE) {
        printf("(!) unable to open port %s\n", sport);
        exit(1);
    }
    dcb.DCBlength = sizeof(dcb);
    dcb.BaudRate = speed;
    dcb.ByteSize = 8;
    dcb.fBinary = 1;
    dcb.fParity = FALSE;
    dcb.fOutxCtsFlow = TRUE;
    dcb.fOutxDsrFlow = FALSE;
    dcb.fDtrControl = DTR_CONTROL_ENABLE;
    dcb.fDsrSensitivity = FALSE;
    dcb.fTXContinueOnXoff = FALSE;
    dcb.fOutX = dcb.fInX = FALSE;
    dcb.fErrorChar = FALSE;
    dcb.fNull = FALSE;
    dcb.fRtsControl = RTS_CONTROL_HANDSHAKE;
    dcb.fAbortOnError = FALSE;
    dcb.Parity = NOPARITY;
    dcb.StopBits = TWOSTOPBITS;
    if (!SetCommState(hport, &dcb)) {
        printf("(!) unable to set comm port configuration\n");
        exit(1);
    }
    PurgeComm(hport, PURGE_RXCLEAR | PURGE_TXCLEAR);

    while (1)
    {
        switch (getMsCommand())
        {
            case 0x40: // send bootloader
                sendBoot(); 
                break;
            case 0x41: // MS0511 -> PC read sectors
                readSectors();
                exit(0);            
            case 0x42: // PC -> MS0511 write image
                writeSectors();
                exit(0);
        }
    }
}
