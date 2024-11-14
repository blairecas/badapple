<?php

define ('C_WIDTH', 40);
define ('C_HEIGHT', 30);
define ('C_MAXPOS', C_WIDTH*C_HEIGHT);
define ('C_TDX', 2);
define ('C_TDY', 2);
define ('C_TRACK_SIZE', 10240);

$dir_png = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/../data/png/';
$dir_out = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/../';

////////////////////////////////////////////////////////////////////////////////


function getChunk ( $pos, $img )
{
    $muls = Array(1, 3, 9, 27);
    $y = 2 * intval($pos / C_WIDTH);
    $x = 2 * ($pos % C_WIDTH);
    $res = 0;
    $k = 0;
    for ($i=$y; $i<$y+C_TDY; $i++)
    {
        for ($j=$x; $j<$x+C_TDX; $j++)
        {
            $val = imagecolorat($img, $j, $i) & 0xFF;
            $v = 0;
            if ($val > 0x38) $v = 1;
            if ($val > 0xA8) $v = 2;
            $res = $res + $v * $muls[$k++];
            // $bits = ($rgb_index & 0xFF) >> 6;
            // $res = ($res << 2) | $bits;
            if ($res > 80) {
                echo "\nERROR: getChunk() result overflow!\n";
                exit(1);
            }
        }
    }
    return $res;
}


function getScreen ( $img )
{
    $arr = Array();
    for ($pos=0; $pos<C_MAXPOS; $pos++) $arr[$pos] = getChunk($pos, $img);
    return $arr;
}


function push_len (&$arr, $w)
{
    $w++;
    if ($w >= 0x80) {
        $w1 = (($w >> 8)^0xFF)&0xFF;
        if ($w1 >= 0322 && $w1 <= 0357) {
            echo "\nERROR: bad skip length number!";
            exit(1);
        }
        array_push($arr, $w1);
        $w = $w & 0xFF;
    }
    array_push($arr, $w);
}


function terminateDiff ( $arr )
{
    global $temp1;
        
    $l = count($arr);
    // do not search for repeats in one-char sequence
    if ($l < 2) {
        $arr[$l-1] |= 0x80;
        return $arr;
    }
    // search for repeats 1 1 1 1 1 2 2 3 3 3 3 -> 1 r5 2 r2 3 r4
    //                    0 1 2 3 4 5 
    // max repeats num is 037 (octal), repeats written as 320+repnum
    $arr2 = Array();
    $c1 = -1;
    $rep = 0;
    for ($i=0; $i<$l; $i++)
    {
        $c2 = $arr[$i];
        if ($c2 == $c1) {
            $rep++;
            if ($rep >= 037) {                 
                array_push($arr2, $c1); array_push($arr2, 0320+$rep); $rep = 0; $c1 = -1;
            }
        } else {
            if ($c1 >= 0) array_push($arr2, $c1);
            if ($rep > 1) array_push($arr2, 0320+$rep);
            $c1 = $c2;
            $rep = 1;
        }    
    }
    if ($rep > 0) {
        if ($c1 >= 0) {
            array_push($arr2, $c1 | 0x80);
            if ($rep > 1) array_push($arr2, 0320+$rep);
        }
    } else {
        $arr2[count($arr2)-2] |= 0x80;
    }
    return $arr2;
}


function compareScreens ()
{
    global $arr_curr, $arr_prev;
    $arr = Array();
    $len_same = 0;
    $arr_diff = Array();
    for ($pos=0; $pos<C_MAXPOS; $pos++) 
    {
        if (!isset($arr_prev[$pos])) $b1 = 0x00; else $b1 = $arr_prev[$pos];
        $b2 = $arr_curr[$pos];
        if ($b1 == $b2) {
            if (count($arr_diff) > 0) {
                push_len($arr, $len_same);
                $arr_diff = terminateDiff($arr_diff);
                $arr = array_merge($arr, $arr_diff);
                $arr_diff = Array();
                $len_same = 0;
            }
            $len_same++;
        } else {
            array_push($arr_diff, $b2);            
        }
    }
    if (count($arr_diff) > 0) {
        push_len($arr, $len_same);
        $arr_diff = terminateDiff($arr_diff);
        $arr = array_merge($arr, $arr_diff);
    }
    array_push($arr, 0x00);
    return $arr;
}


function writeArr ($g, $arr)
{
    for ($i=0, $l=count($arr); $i<$l; $i++) fwrite($g, chr($arr[$i]));
}


////////////////////////////////////////////////////////////////////////////////

    $arr_curr = Array();
    $arr_prev = Array();
    $arr_diff = Array();
    
    $num_fram = 0;
    $cnt_diff = 0;
    $max_diff = 0;
    $min_diff = PHP_INT_MAX;

    $dmain_size = filesize('dmain.bin');
    $max_size = 819200 - $dmain_size;

    $track_num = 1;
    $cnt_in_track = 0;
    $fram_in_track = 0;

    $files = scandir($dir_png);
    $g = fopen($dir_out . 'data.bin', "w");

    foreach ($files as $k => $v)
    {
        if (!str_ends_with($v, '.png')) continue;
        // create differences
        $img = imagecreatefrompng($dir_png . $v);
        $arr_curr = getScreen($img);
        $arr_diff = compareScreens();
        $d = count($arr_diff);
        if ($d < 2) continue;
        // write 'load next track' character 
        $cnt_in_track += $d;
        $kali = 0;
        if ($cnt_in_track >= (C_TRACK_SIZE-2))
        {
            echo "$track_num:$fram_in_track ";
            $kali = 2;
            fwrite($g, chr(0x00));
            fwrite($g, chr($track_num));
            for ($i=0; $i<(C_TRACK_SIZE-($cnt_in_track-$d)-2); $i++) { fwrite($g, chr(0xFF)); $kali++; }
            $track_num++;
            $cnt_in_track = $d;
            $fram_in_track = 0;
        }
        $fram_in_track++;        
        // stats
        if ($d < $min_diff) $min_diff = $d;
        if ($d > $max_diff) $max_diff = $d;
        $cnt_diff = $cnt_diff + $d + $kali;
        $num_fram++;
        // write differences
        writeArr($g, $arr_diff);        
        // stop if data size exceeds limit
        if ($cnt_diff > ($max_size - 500)) break;
        $arr_prev = $arr_curr;
    }

    for ($i=0; $i<$max_size-$cnt_diff; $i++) fwrite($g, chr(0x00));
    fclose($g);

    echo "\nFrames: $num_fram, Diff: $cnt_diff, Avg: ".($cnt_diff/$num_fram).", Min: $min_diff, Max: $max_diff\n";
