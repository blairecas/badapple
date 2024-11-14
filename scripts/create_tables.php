<?php
    define ('C_WIDTH', 40);
    define ('C_HEIGHT', 30);

    $vaddr = $saddr = 0100000;
    $scrwid = 80;

    $f = fopen(pathinfo(__FILE__, PATHINFO_DIRNAME)."/../inc_tables.mac", "w");

    fputs($f, "CharsTable:\n");
    $arr = Array();
    $n = 0;
    for ($j=0; $j<C_HEIGHT; $j++)
    {
        $vaddr = $saddr;
        for ($i=0; $i<C_WIDTH; $i++)
        {
            if ($n==0) fputs($f, "\t.word\t");
            fputs($f, decoct($vaddr & 0xFFFF));
            if ($n<7) { fputs($f, ", "); $n++; } else { fputs($f, "\n"); $n=0; }
            $vaddr+=2;
        }
        $saddr += ($scrwid * 4);
    }

    fclose($f);