<?php

define ('C_SCRSIZE', 240*40);
define ('C_VADDR_0', 0120050);
define ('C_VADDR_1', C_VADDR_0+C_SCRSIZE);
define ('C_VTAB_0', 073006);
define ('C_VTAB_1', 075406);

define ('C_BITS', 16);
define ('C_WIDTH', 20);
define ('C_HEIGHT', 240);
define ('C_MAXPOS', C_WIDTH*C_HEIGHT);

define ('SECTORS', 63);
define ('TRACKSIZE', SECTORS*512);

define ('TICKS_IN_SEC', 6250000);
define ('AUDIO_HZ', 44100);
define ('VIDEO_FPS', 25);

define ('T_NONE', 0);
define ('T_AUDIO', 1);
define ('T_VIDEO', 2);

    // paths
    $dir_png = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/../data/pngw/';
    $dir_out = pathinfo(__FILE__, PATHINFO_DIRNAME) . '/../';

    // times in ticks for track reading cycle
    $timetable = Array();

////////////////////////////////////////////////////////////////////////////////

// add time table entry
function addt ($t, $x = T_NONE)
{
    global $timetable;
    array_push($timetable, Array($t, $x));
}

// makes one track time table
function makeTimetable ()
{
    addt(44);
    for ($r1=0; $r1<SECTORS-1; $r1++)
    {
        addt(68); addt(20); addt(44);
        for ($r2=0; $r2<17; $r2++) {
            addt(50, T_AUDIO); addt(90, T_VIDEO); addt(90, T_VIDEO);
            addt(50, T_AUDIO); addt(90, T_VIDEO); addt(90, T_VIDEO);
            addt(50, T_AUDIO); addt(90, T_VIDEO); addt(90, T_VIDEO);
            addt(40); // sob r2, 10$
        }
        addt(52, T_AUDIO);
        addt(40); // sob r1, sector
    }
    // last sector
    addt(68); addt(20); addt(44);
    for ($r2=0; $r2<16; $r2++) {
        addt(50, T_AUDIO); addt(90, T_VIDEO); addt(90, T_VIDEO);
        addt(50, T_AUDIO); addt(90, T_VIDEO); addt(90, T_VIDEO);
        addt(50, T_AUDIO); addt(90, T_VIDEO); addt(90, T_VIDEO);
        addt(40); // sob r2, 20$
    }
    addt(64); addt(20);
    // still 16 words
    addt(50, T_AUDIO); addt(90, T_VIDEO);
    addt(80); addt(44);
    addt(50, T_AUDIO); addt(90, T_VIDEO);
    addt(68); addt(68);
    addt(50, T_AUDIO); addt(90, T_VIDEO);
    addt(64);
    addt(50, T_AUDIO); 
    addt(20); addt(20); addt(20); addt(20);
    addt(20); addt(44);
    addt(50, T_AUDIO); addt(90, T_VIDEO);
    addt(20); addt(44);
    addt(50, T_AUDIO);
    addt(64); addt(44); 
    addt(50, T_AUDIO);
    addt(44); addt(20); addt(44);
    addt(50, T_AUDIO);
    addt(68);
    addt(68); // bit #200
    addt(20); // beq
    addt(52); // jmp Track
}

// write word to output and increase size variable
function writeWord ($w)
{
    global $g, $gsize;
    $gsize += 2;
    fwrite($g, chr($w & 0xFF));
    fwrite($g, chr(($w >> 8) & 0xFF));
}

// get pixels word data from image
function getImgWord ( $pos, $img )
{
    $y = intval($pos / C_WIDTH);
    $x = C_BITS * ($pos % C_WIDTH);
    $res = 0;
    for ($j=$x; $j<$x+C_BITS; $j++)
    {
        $val = imagecolorat($img, $j, $y) & 0xFF; // images are grayscale
        $res = $res >> 1;
        if ($val > 0x38) $res |= 0x8000;
    }
    return $res & 0xFFFF;
}

// get screen array from image
function getScreen ( $img )
{
    if (!$img) return null;
    $arr = Array();
    for ($pos=0; $pos<C_MAXPOS; $pos++) array_push($arr, getImgWord($pos, $img));
    return $arr;
}

// compare two screen arrays and get diff array
function compareScreens ($arr1, $arr2)
{
    $arr = Array();
    if ($arr2 == null) return $arr;
    for ($pos=0; $pos<C_MAXPOS; $pos++)
        if ($arr1[$pos] != $arr2[$pos]) array_unshift($arr, Array($pos, $arr2[$pos]));
    // shuffle($arr);
    return $arr;
}

// get audio word by tick
function getAudio ($tick)
{
    global $audio, $audio_len;
    $i = intval(($tick*AUDIO_HZ/TICKS_IN_SEC)/1.014);
    // out of data
    if ($i >= $audio_len) return 0xFF;
    $b = ord($audio[$i]);
    // fade out
    if ($i > ($audio_len-10000)) {
        $b = 0xFF - (0xFF-$b)*(1.0-($i-$audio_len+10000)/10000.0);
        if ($b > 0xFF) $b = 0xFF;
        return $b;
    }
    // fade in
    if ($i < 10000) {
        $b = 0xFF - intval($i*128.0/10000.0);
        if ($b > 0xFF) $b = 0xFF;
        return $b;
    }
    return $b;
}


////////////////////////////////////////////////////////////////////////////////

    // time table make and check
    makeTimetable();
    $trackTicks = 0; $c = 0;
    for ($i=0; $i<count($timetable); $i++) {
        $trackTicks += $timetable[$i][0];
        $c += 2*$timetable[$i][1];
    }
    $trackSec = $trackTicks / TICKS_IN_SEC;
    echo "Ticks in track: $trackTicks, sec: $trackSec\n";
    echo "Bytes in track: ".TRACKSIZE.", sectors: ".SECTORS."\n";
    echo "Checked track bytes in timetable: $c\n";
    if ($c != TRACKSIZE) {
        echo "(!) ERROR: track bytes count not equal\n";
        exit(1);
    }

    // read sound file
    $hname = $dir_out . 'data/bad_apple.wav';
    $h = fopen($hname, 'r');
    fseek($h, 0x30, SEEK_SET);
    $audio = fread($h, filesize($hname)-0x30);
    fclose($h);
    $audio_len = strlen($audio);

    // output file
    $gname = $dir_out . '_bappwd.bin';
    $g = fopen($gname, 'w');
    $gsize = 0;

    $tick = 0;

    // two screens
    // it's slowing down video output, but needed to remove tearing 
    $scr_cur[0] = array_fill(0, C_MAXPOS, 0x00);
    $scr_cur[1] = array_fill(0, C_MAXPOS, 0x00);
    $scr_new = getScreen(imagecreatefrompng($dir_png . 'out0001.png'));
    $scr_new_num = 1;
    $cur_arr_num = 1;
    $diff[0] = Array();
    $diff[1] = compareScreens($scr_cur[0], $scr_new);
    $vtabs = Array(C_VTAB_0, C_VTAB_1);
    $vaddrs = Array(C_VADDR_0, C_VADDR_1);
    $adj_frames_num = 0;

    $vid_used_count  = 0;
    $vid_empty_count = 0;
    $aud_used_count  = 0;
    $not_end = true;

    while ($not_end) 
    {
        // one track
        for ($t=0; $t<count($timetable); $t++)
        {
            $evt = $timetable[$t];
            switch ($evt[1])
            {
                //
                case T_NONE: 
                    break;
                //
                case T_AUDIO:
                    writeWord(getAudio($tick));
                    $aud_used_count++;
                    break;
                //
                case T_VIDEO:
                    $a = array_pop($diff[$cur_arr_num]);
                    // diffs buffer is empty, write switch screen lines table
                    if ($a == null) {
                        writeWord($vtabs[$cur_arr_num]);
                        writeWord(0272);
                        $vid_empty_count += 2;
                    // have data in diffs buf
                    } else {
                        $p = $a[0];
                        $w = $a[1];
                        writeWord($w);
                        if ($p < C_MAXPOS) {
                            writeWord($vaddrs[$cur_arr_num] + 2*$p);
                            $scr_cur[$cur_arr_num][$p] = $w;
                        } else {
                            writeWord($p-C_MAXPOS);
                        }
                        $vid_used_count += 2;
                    }
                    break;
                //
                default:
                    echo "\n(!) ERROR: unknown time table event\n";
                    exit(1);
            }
            // advance ticks
            $tick += $evt[0];
            // check if we need to rebuild diff array
            $n = intval($tick*VIDEO_FPS/TICKS_IN_SEC) + 1;
            if (($n-$adj_frames_num) > 5477) { $n = 5477+$adj_frames_num; $not_end = false; }
            if ($n != $scr_new_num) 
            {
                $scr_new_num = $n;
                $ld = count($diff[$cur_arr_num]);
                // big frame - continue spooling it
                if ($ld > 0) {
                    echo "$scr_new_num:$ld ";
                    $adj_frames_num++;
                } else {
                    // advance buffer num
                    $cur_arr_num = ($cur_arr_num + 1) % 2;
                    // read new frame
                    $pname = $dir_png . 'out' . str_pad(''.($scr_new_num-$adj_frames_num), 4, "0", STR_PAD_LEFT) . '.png';
                    $scr_new = getScreen(imagecreatefrompng($pname));
                    // compare with previous in appropriate queue
                    $diff[$cur_arr_num] = compareScreens($scr_cur[$cur_arr_num], $scr_new);
                    array_push($diff[$cur_arr_num], Array(C_MAXPOS+0272, $vtabs[1-$cur_arr_num]));
                }
            }
        }
    }

    // put pair of video(177777,077776) with a size of track, audio must be 0xFF (0x00 inverted)
    // it's a mark for 'end of demo'
    for ($t=0; $t<count($timetable); $t++) {
        $evt = $timetable[$t];
        if ($evt[1] == T_AUDIO) { writeWord(0x00FF); continue; }
        if ($evt[1] == T_VIDEO) { writeWord(0177777); writeWord(0077776); continue; }
    }

    // that's all
    fclose($g);

    echo "\n";
    echo "Video words used: $vid_used_count, empty: $vid_empty_count\n";
    echo "Audio words used: $aud_used_count\n";
    echo "Frames too big: $adj_frames_num\n";
