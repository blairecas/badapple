<?php

function GetArray ($img, $tdx, $tdy)
{
    $arr = Array();
    $arr['last'] = 0;
    $arr['tiles'] = Array();
    $arr['masks'] = Array();
    $tiles_dx = imagesx($img) / $tdx;
    $tiles_dy = imagesy($img) / $tdy;
    for ($i=0; $i<$tiles_dy; $i++) 
    {
        for ($j=0; $j<$tiles_dx; $j++) 
        {
            $tile = Array();
            $mask = Array();
            for ($y=$i*$tdy; $y<$i*$tdy+$tdy; $y++) 
            {
                for ($x8=$j*$tdx; $x8<$j*$tdx+$tdx; $x8+=8) 
                {
                    $res = 0;
                    $mas = 0;
                    for ($x=$x8; $x<$x8+8; $x++) 
                    {
                        $res = ($res >> 1) & 0xFFFFFF;
                        $mas = ($mas >> 1) & 0xFFFFFF;
                        $rgb_index = imagecolorat($img, $x, $y);
                        $rgba = imagecolorsforindex($img, $rgb_index);
                        $r=$rgba['red']; $g=$rgba['green']; $b=$rgba['blue']; $a=$rgba['alpha'];
                        if ($a > 100) { $b=0; $g=0; $r=0; } else $mas = $mas | 0b100000001000000010000000; 
                        if ($b > 127) $res = $res | 0b000000000000000010000000;
                        if ($g > 127) $res = $res | 0b000000001000000000000000;
                        if ($r > 127) $res = $res | 0b100000000000000000000000;
                    }
                    array_push($tile, $res);
                    array_push($mask, $mas);
                    if ($res != 0) $arr['last'] = count($arr['tiles']);
                }
            }
            array_push($arr['tiles'], $tile);
            array_push($arr['masks'], $mask);
        }
    }
    return $arr;
}


function ImgWriteData ($fn, $cstr, $ispack, $tdx, $tdy)
{
    global $f;
    echo "$fn -> $cstr\n";
    $img = imagecreatefrompng(pathinfo(__FILE__, PATHINFO_DIRNAME)."/../graphics/".$fn);
    if ($tdx < 0) $tdx = imagesx($img);
    if ($tdy < 0) $tdy = imagesy($img);
    $arr = GetArray($img, $tdx, $tdy);
    fputs($f, "\n".$cstr.":\n");
    if ($ispack) fputs($f, "@packstart\n");
    for ($t=0; $t<=$arr['last']; $t++)
    {
	$tile = $arr['tiles'][$t];
    	for ($i=0, $n=0, $l=count($tile); $i<$l; $i++)
	{
    	    if ($n==0) fputs($f, "\t.word\t");
	    fputs($f, decoct(($tile[$i] >> 8) & 0xFFFF));
            if ($n<7 && $i<($l-1)) { fputs($f, ", "); $n++; } else { fputs($f, "\n"); $n=0; }
        }
    }
    if ($ispack) fputs($f, "@packend\n\n");
}

    ////////////////////////////////////////////////////////////////////////////

    $f = fopen(pathinfo(__FILE__, PATHINFO_DIRNAME)."/../inc_graphics.mac", "w");
    ImgWriteData("Tiles.png", "TilesData", false/*pack*/, 8, 4);
    fclose($f); 
