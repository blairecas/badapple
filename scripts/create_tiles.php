<?php

$dir_out = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/../';

function imgChunk ($img, $c, $x, $y)
{
    $cols = Array(
        0 => Array(
            0x000000, 0x000000, 0x000000, 0x000000,
            0x000000, 0x000000, 0x000000, 0x000000
        ),
//        1 => Array(
//            0xFF0000, 0x000000, 0xFF0000, 0x000000,
//            0x000000, 0xFF0000, 0x000000, 0xFF0000
//        ),
        1 => Array(
            0xFFFFFF, 0x000000, 0xFFFFFF, 0x000000,
            0x000000, 0xFFFFFF, 0x000000, 0xFFFFFF
        ),
        2 => Array(
            0xFFFFFF, 0xFFFFFF, 0xFFFFFF, 0xFFFFFF,
            0xFFFFFF, 0xFFFFFF, 0xFFFFFF, 0xFFFFFF
        )
    );
    $k = 0;
    for ($i=0; $i<2; $i++)
    for ($j=0; $j<4; $j++) imagesetpixel($img, $x+$j, $y+$i, $cols[$c][$k++]);
}

////////////////////////////////////////////////////////////////////////////////

    $img = imagecreatetruecolor(128, 64);  
    $y = 0;  
    $x = 0;
    for ($b=0; $b<255; $b++)
    {
        $c = $b & 0x7F;
        if ($c <= 80) {
            $ch1 = $c % 3;
            $c = intval($c / 3);
            $ch2 = $c % 3;
            $c = intval($c / 3);
            $ch3 = $c % 3;
            $c = intval($c / 3);
            $ch4 = $c;
            imgChunk($img, $ch1, $x+0, $y+0);
            imgChunk($img, $ch2, $x+4, $y+0);
            imgChunk($img, $ch3, $x+0, $y+2);
            imgChunk($img, $ch4, $x+4, $y+2);
        }
        $x+=8; if ($x >= 128) { $x=0; $y+=4; }
    }
    $g = fopen($dir_out . 'graphics/Tiles.png', "w");
    imagepng($img, $g);
