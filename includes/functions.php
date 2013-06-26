<?php
# functions used all over these pages. 

function webHeader ($title='', $left="yes", $icon="default") {
    $clock          = date("l F d Y");
    $title          = str_replace("_", " ", $title);

    print "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<LINK REL='SHORTCUT ICON' HREF='http://".DOMAIN_NAME.SITE_ICON."' /> 
<link rel='stylesheet' href='includes/styles.css'>
<title>$title</title>
</head>
<body>
<table width='100%' cellpadding='2' cellspacing='0'>
  <tr>
    <td class='blue' width='15%'><a href='".WhichLocal."'>
        <img src='".SITE_LOGO."' alt='".LOGO_ALT."'></a></td>
    <td class='blue' width='85%'><h2><font color='white' size='30pt'>$title</font></a></h2></td>
    <td class='blue'><a href='http://www.wunderground.com/US/GA/Saint_Marys.html?bannertypeclick=sunandmoon150' target='_blank'><img src='http://banners.wunderground.com/weathersticker/sunandmoon150/language/www/US/GA/Saint_Marys.gif' border='0' alt='Saint Marys, GA Forecast' height='50' width='150'></a></td>
  </tr>
  <tr>
    <td class='red' style='text-align: left;'>";

    if (strlen($_SESSION['username']) > 1) {
        print $_SESSION['username']." | 
        <a href='".WhichLocal."?action=logout'>logout</a> | 
        <a href='".WhichLocal."?action=editprofile'>profile</a>";
        if (strpos(SiteAdmin, $_SESSION['username'].",") !== false) {
            print " | <a href='".WhichLocal."?action=admin'>admin</a>"; }
    } else {
        print "&nbsp;<a href='".WhichLocal."?action=login'>login</a>";
        if (ALLOW_REGISTRATION > 0) { 
            print " | <a href='".WhichLocal."?action=register'>register</a>\n";
        }
    }
    print "</td>\n";
    if ($_GET['action'] == "photos") {       # Photo Gallery breadcrumb trail 
        print "<td class='red'><strong>Your Path:</strong>";
        $trail = explode("/", $_GET['dir']);
        $dir   = "";
        $where = "&nbsp;&nbsp;<a href='".WhichLocal."?action=photos";
        print $where . "'>Home</a>\n";
        foreach ($trail as $t) { 
            print "&nbsp;&nbsp;/".$where."&dir=".$dir.$t."'>$t</a>\n";
            $dir .= $t . "/";
        }
        print "</td>\n    <td";
    } else {
        print "    <td colspan='2'";
    }

    print " class='red' style='text-align: right;'>$clock</td>\n";
    print "  </tr>\n<!-- left column (menus) --> \n  <tr>\n    <td width='20%' valign='top'>";
    print "<div id='lftmnu'>\n";
    print "<table id='lftmnu' width='100%' cellspacing='0' cellpadding='0' border='0'>\n";

    # load the left menu into an array// imgs are p=pipe, t, m, b denoting where horizantal is on vertical
    $lfm = file("includes/LeftMenu.txt");
    foreach ($lfm as $l) {
        if (substr($l, 0, 1) == "#") { continue; }  # comment line
        if (strlen(trim($l)) < 1)    { continue; }  # errant blank lines are ignored
        if (substr($l, 0, 2) == "[]") {             # close section
            $x = "";
        } elseif (substr($l, 0, 1) == "[") {        # indicates section header
            $x = substr($l, 1, strpos($l, "]") -1); 
        } else {                                    # else link is inside a section
            $y = substr($l, 0, strpos($l, "="));
            if (strlen($x) > 1) {
                $menuArray[$x][$y] = substr($l, strpos($l, "=") +1);
            } else {
                $menuArray[$y] = substr($l, strpos($l, "=") +1);
            }
        }
    }
    tableArray($menuArray, 0);
    print "</table>\n";
    print "<br/><table>".calendar()."</table>\n";
?>
    <br/><br/>
    Donations are not tax deductable but they help me keep this site running...
    <br/><br/>
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="4ZGRD2Q52DYF2">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>

<?php
    print "</div id='lftmnu'>\n";
    # remove 'colspan='2' to have right side column. Then you figure out how to do it... I just want to put the photo breadcrumb trial in the 
    #       the read header line instead of on the page body.
    print "  </td>\n    <td valign='top' colspan='2'>\n    <!-- right column (body) -->\n";
    flush();
    collectGarbage();
    return true;
}    
    
    
function tableArray($menuArray, $indentLvl) {
    global $cs;
    $maLength  = count($menuArray);     // $maLength is length of array. $maPointer is index.
    $maPointer = 1;                     //  When $maPointer == $maLength display bottom icon.
    foreach ($menuArray as $k => $v) {
        // use 'Option Base = 1' for array and indent counts. Too confusing to use 0 and 1.
        // determine which indent lvl we are on and display pipes in cols up to current lvl. For this lvl, display
        //      Mid or Bot icon, then display menu Key as prompt w/ Val as link
        print "<tr>";
        if ($indentLvl > 0) {     // determine what indent lvl this is.  Topmost gets Pipes all the way down.
            if ($maPointer <= $maLength) { 
                print "<td><img src='img/p.gif'></td>"; }
            // somehow, we need to look at next element. If it is an array, 
            for ($i=1; $i<$indentLvl; $i++) { 
                if ($maPointer >= $maLength) {
                    print "<td>&nbsp;</td>";
                } else {
                    print "<td><img src='img/p.gif'></td>";
                }
            }
        }
   
        // determine where in the array this elem is. if last, show Bot, else show Mid
        if ($k == "Home") {         // cheat and know that Home is always 1st elem of 1st column,
            $p = "t";               //      no indent.
            $maPointer++;
        } else {                    // 
            $maPointer++;
            if ($maPointer > $maLength) { $p = "b"; } else { $p = "m"; }
        }
        print "<td><img src='img/".$p.".gif'></td>";
   
        // if this key's value is an array, print the key as text and recurse to load the children.
        // ?? If 1st elem has no key, print as Val to this key, shift array and then recurse??
        if (is_array($v)) { 
            print "<td colspan='$cs' nowrap class='bold'>$k</td></tr>\n";
            tableArray($v, ($indentLvl +1)); 
        } else {
            print "<td colspan='".($cs-$indentLvl)."' nowrap>&nbsp;";
            //print "\n    <td colspan='".($cs-$indentLvl)."' nowrap>$indentLvl:$maPointer:$maLength";
            if (substr($v, 0, 5) == "link=") { 
                print "<a href='".substr($v, 5)."' target='_blank'>$k</a>";
            } elseif (substr($v, 0, 7) == "mailto:") {
                print "<a href='$v'>$k</a>";
            } elseif (substr($v, 0, 4) == "pdf=") {
                print "<a href='".WhichLocal."/".substr($v, 4).".pdf' target='_blank'>$k</a>";
            } elseif (substr(trim($v), 0, 4) == "http") { 
                print "<a href='$v' target='_blank'>$k</a>";
            } else {
                print "<a href='".WhichLocal."/?$v'>$k</a>";
            }
            print "</td></tr>\n";
        }
    }   
}


function webFooter () {
    print "\n    <!-- Page contents end here -->
    </td>
    </tr>
    <tr><td colspan='2'><hr width='85%'></td></tr>
    <tr><td colspan='2'><center>
        <a href='".WhichLocal."/?page=Site_Policy'>Site Policy</a> &nbsp; &nbsp; 
        email <a href='mailto:".ADMIN_EMAIL."'>Webmaster</a> &nbsp; &nbsp; 
        Hosted by <a href='http://page-zone.com'>page-zone.com</a> &nbsp; &nbsp; 
    </center></td></tr>
</table>
</body>
</html>\n";
    return true;
}


function calendar ($whereto="./index.php") {
    # mktime (hour, minute, second, month, day, year, is_dst)
    global $_GET, $_SERVER;
    $currMonth = ($_GET['month']) ? $_GET['month'] : date("n"); # numeric calendar month
    $currYear  = ($_GET['year'])  ? $_GET['year']  : date("Y");
    $tm   = mktime(0, 0, 0, ($currMonth +$shifted), 1, $currYear);
    $dim  = date("t", $tm);    # days in month
    $dow  = date("w", $tm);    # day of week month starts on
    $txm  = date("F", $tm);    # text name of month
    $pMon = date("M", mktime(0, 0, 0, $currMonth-1, 1, $currYear));
    $nMon = date("M", mktime(0, 0, 0, $currMonth+1, 1, $currYear));
    $cal .= "  <tr><td><br/></td></tr>\n<tr><th>$txm $currYear</th></tr>\n<tr><td>";
    $cal .= "<table class='cal' width='100%'>\n  <tr>";
    foreach (array("S","M","T","W","T","F","S") as $k => $v) { $cal .= "<th>$v</th>"; }
    $cal .= "</tr>\n";
    $day  = " ";
    for ($row=0; $row<6; $row++) {
        $cal .= "  <tr>";
        for ($col=0; $col<7; $col++) {
            if (($row == 0) && ($col == $dow)) { $day = 1; }
            if (is_numeric($day)) {
                $dday = ($day < 10) ? "0$day" : $day;
                $link = "?date=$currYear-".(($currMonth<10)?"0":"")."$currMonth-$dday";
                $cal .= "<td><a href='$link'>$dday</a></td>";
                $day++;
            } else {
                $cal .= "<td>&nbsp;</td>";
            }
            if ($day > $dim) { $day = "&nbsp;"; }
        }
        $cal .= "</tr>\n";
        if ($day >= $dim) { break; }
    }
    $cal .= "  <tr>\n    <td colspan='7' nowrap>\n";
    $cal .= "      <a href='$whereto?year=".($currYear-1)."'>".substr(($currYear-1),2)."</a>&nbsp;\n";
    $cal .= "      <a href='$whereto?month=".((($currMonth -1) < 1)?12:$currMonth -1)."&year=" .((($currMonth -1) < 1)?$currYear -1:$currYear)."'>$pMon</a>";
    if (!(($currMonth == date("n")) && ($currYear == date("Y")))) {
        $cal .= "&nbsp;<a href='$whereto?month=".date("n")."&year=".date("Y")."'>Home</a>";
    } else {
        $cal .= "&nbsp;&nbsp;--&nbsp;&nbsp;";
    }
    $cal .= "&nbsp;\n      <a href='$whereto?month=".((($currMonth +1) > 12)?1:$currMonth +1)."&year=".((($currMonth +1) > 12)?$currYear +1:$currYear)."'>$nMon</a>&nbsp;\n";
    $cal .= "      <a href='$whereto?year=".($currYear+1)."'>".substr(($currYear+1),2)."</a>\n";
    $cal .= "    </td>\n   </tr>\n</table>\n";
    $cal .= "<!-- end calendar -->\n";
    return $cal;
}


function myQuery ($sql='', $dbg=false) {
    if ($dbg) { myDebug($sql); }
    if (DB_Type == "mysql") {
        $db   = mysql_connect (DB_Host, DB_User, DB_Pswd) or myDie('DB connect err: ' . mysql_error());
        mysql_select_db (DB_Name, $db) or myDie('DB select err: ' . mysql_error());
        $resp = mysql_query($sql) or myDie($sql."<br/>\n".mysql_error());
    } elseif (DB_Type == "sqlite") {
        $db   = sqlite_open(DB_Name, 0666, $sqliteerror) or myDie($sqliteerror);
        $resp = sqlite_query($db, $sql) or myDie($sql."<br/>\n".sqlite_error_string()); 
    } else {
        myDie("Config database type not set correctly!");
    }
    return $resp;
}



function num_rows ($res) {
    if (DB_Type == "mysql" )        { return mysql_num_rows($res); 
    } elseif (DB_Type == "sqlite" ) { return sqlite_num_rows($res);
    } else {
        myDie("Config: database type not set correctly!");
    }
}
    

function fetch_array ($res) {
    if (DB_Type == "mysql" )        { return mysql_fetch_assoc($res); 
    } elseif (DB_Type == "sqlite" ) { return sqlite_fetch_array($res);
    } else {
        myDie("Config: database type not set correctly!");
    }
}


function columnNames ($res) {
    if (DB_Type == "mysql") {
        $foo = mysql_num_fields($res);
        for ($i=0; $i<$foo; $i++) {
            $names[] = mysql_field_name($res, $i);
        }
        return $names;
    } elseif (DB_Type == "sqlite" ) {
        myDie("functions.php:columnNames() not configured for sqlite usage...");
    } else {
        myDie("functions.php:columnNames() not configured for this database type.");
    }
}
    
/* 8-27-2010 k.k mark this function for deletion. All this incorporated into myQuery().
function &dbOpen ($table="entry") {
    $db = mysql_connect (DB_Host, DB_User, DB_Pswd) 
        or die('DB connect err: ' . mysql_error());
    mysql_select_db (DB_Name, $db) or die('DB select err: ' . mysql_error());
    return $db;
}
*/


function radio_choice($prmpt, $kvar, $pr2="yes", $pr3="no", $yes="y", $no="n") {
    global $$kvar;
    print "     <td><b>$prmpt</b></td>\n  <td>";
    print "  &nbsp;&nbsp;$pr2<input type='radio' name='$kvar' value='$yes'";
    if (${$kvar} == $yes) { print " checked"; }
    print " onChange=submit();>\n";
    print "  &nbsp;&nbsp;$pr3<input type='radio' name='$kvar' value='$no'";
    if (${$kvar} == $no) { print " checked"; }
    print " onChange=submit();\n";
}


function collectGarbage () {
    # This function will perform background clean ups. These processes will be moved to a cronjob eventually...
    # Delete CAPTCHA images older than 1 hour.
    if(!($dh = @opendir("captcha"))) { return false; }
    while (($obj = readdir($dh)) !== false) {
        if(substr($obj, -4) != ".png") { continue; }
        @unlink("captcha/" . $obj);
    }
    closedir($dh);  
    # logout any users that have not loaded a page in the last hour
    

    return true; 
}

/* prepare to remove. commented May 10-2010.
function dirlist ($dnam="./", $mask="null") {
    # returns a list of all directory entries in the passed path. $mask can be 
    # check path: photo/ must proceed and path can't contain '..'
    $dirs = $files = array();
    $handle = opendir(soothGet($dnam));
    while (false  !== ($input = readdir($handle))) { 
        if (substr($input, 0, 1) == ".")  { continue; }
        if (is_dir($dnam.$input)) { $dirs[] = $input; continue; }
        if (($mask == "null") || (strpos($input, $mask) !== false)) {
            $files[] = $input;
        }
    }
    closedir($handle);
    sort($dirs); sort($files); 
    return array($dirs, $files);
}
*/

function soothGet ($dir="./") {
    if (strpos($dir, "..") !==false) { myDie("Invalid directory designation."); }
    # strip any malicious pathing from passed vars.
    return str_replace(array("./","../","//","\\\\"), array("","","/","/"), $dir);
}


function scan_dir ($dir) {
    $handle   = opendir(soothGet($dir));
    #$handle   = opendir('./photos/'.soothGet($_GET['dir']));
    #$handle   = opendir('./photos/'.stripslashes($_GET['dir']));
    while (false !== ($file = readdir($handle))) { $files[] = $file; }
    closedir($handle);
    sort($files);
    return $files;
}


function getDirectoryTree( $outerDir ){
    # recurse a diretory tee. Return a multi-demensional array. Last lvl of each branch is a NULL array.
    $dirs = scan_dir($outerDir);
    $dir_array = array();
    foreach( $dirs as $d ){
        if (substr($d,0,1) == ".")    { continue; }
        if (is_dir($outerDir."/".$d)) { $dir_array[$d] = getDirectoryTree($outerDir."/".$d); }
        #else $dir_array[ $d ] = $d;
    }
    if (count($dir_array) > 0) { return $dir_array; }
} 


function myErr ($msg='Something bad happened...') {
    // Pass only a string or integer. Arrays will mess this thing up. It is not a debugger...
    myDebug("<font color='red'>$msg</font>\n", "<br><br>ERR:");
}


function myDie ($err="Unable to continue...") {
    myDebug("$err", "DIE:", "die");
}


function myDebug ($v, $p='DBG:', $die='no') {
    print "\n".$p." "; 
    if (is_array($v)) { print_r($v); } else { print $v."\n"; }
    flush();   
    if ($die != "no") { die; }
}


function myMsg ($v) { 
    print "<br/><font color='red'><h2>$v</h2></font><br/>\n"; 
}


function myMissing ($type="page", $what="sumptin") {
    myErr("Sorry! ".ucfirst($type)." '$what' not found. Email <a href='mailto:".ADMIN_EMAIL."&subject=".ucfirst($type)."NotFnd:$what'>Webmaster</a> if you care to.");
}


function myPowned ($txt) {
    # possible tests: date, datetime, !sql, dir, filename,numeric, 1 char, 
    myDie("<h2 style='color: #FF0000;'>Link contents damaged or powned! You're going to get here 
        every time you fool with the variables in the address bar.</h2><br/><br/>$txt"); 
}


function isEmail ($txt) {
    # returns true if $txt is a valid appearing email address. 
    #return (ereg("/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,5})+$/", $txt) ? true : false);
    return (ereg("^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-zA-Z]{2,6}$", $txt) ? true : false);
}


function obfuscateEmail ($email) {
    if (isEmail($email)) {
        return str_replace(array("@","."), array("(at)","(dot)"), $email);
    } else {
        return "";
    }
}


function isLeapYear($year) {
    if (($year % 4)   != 0) { return false; }
    if (($year % 100) == 0) { 
        if (($year % 400) == 0) { return false; }
    } else { 
        return true; }
    return false;
    #return ((($year % 4) == 0) && (($year % 100) == 0) && !(($year % 400) == 0));
}


function formatPhone ($phn) {
    //  take a string that's supposed to be a phn number, strip out the digits and return them as (999) 999-9999 9*
    $phn = stripPhone($phn);        // first get a string of just numbers
    if (strlen($phn) == 7) {
        return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phn);
    } elseif (strlen($phn) == 10) {
        return preg_replace("/([+0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phn);
    } elseif (strlen($phn) > 10) {
        return preg_replace("/([+0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phn) . " " . substr($phn, 10);
    } else {
        return $phn;
    }
}


function stripPhone ($phn) {
    // strip up to 15 digits from a string and return it as a string.
    return substr(preg_replace("/[^0-9]/", "", $phn), 0, 15);
}


function showCaptchaImage () {
    # generate a CAPTCHA image, store and display it. Also gen the <input tag for holding the correct answer in md5 format.
    #$symbols      = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
    $symbols      = "123456789abcdefghjkmnpqrstuwxyz";
    $captcha_word = '';
    $wordLength   = 4;
    $filename     = "./captcha/".date("Ymdhi").".png";
    
    $captcha_image = imagecreate(200, 40);
    $captcha_image_bgcolor = imagecolorallocate($captcha_image, 44, 250, 0);
    $captcha_image_lcolor[] = imagecolorallocate($captcha_image, 255, rand(230, 240), rand(230, 240));
    $captcha_image_lcolor[] = imagecolorallocate($captcha_image, 255, rand(230, 240), rand(230, 240));
    $captcha_image_lcolor[] = imagecolorallocate($captcha_image, 255, rand(160, 220), rand(160, 220));
    $captcha_image_dcolor[] = imagecolorallocate($captcha_image, rand(50, 100), rand(0, 50), rand(0, 50));
    $captcha_image_dcolor[] = imagecolorallocate($captcha_image, rand(50, 100), rand(0, 50), rand(0, 50));
    $captcha_image_dcolor[] = imagecolorallocate($captcha_image, rand(50, 100), rand(0, 50), rand(0, 50));
    for ($i = 0; $i <= 10; $i++) {
        imagefilledrectangle($captcha_image, $i*20+rand(4, 26), rand(0, 39), $i*20-rand(4, 26), rand(0, 39), $captcha_image_dcolor[rand(0, 2)]); }
    # add random lines to the image
#    for ($i = 0; $i <= 10; $i++) {
#        imageline($captcha_image, $i*20+rand(4, 26), 0, $i*20-rand(4, 26), 39, $captcha_image_lcolor[rand(0, 2)]); }
#    for ($i = 0; $i <= 10; $i++) {
#        imageline($captcha_image, $i*20+rand(4, 26), 39, $i*20-rand(4, 26), 0, $captcha_image_lcolor[rand(0, 2)]); }
#    # add the letters and numbers to the image.
    for ($i = 0; $i <= $wordLength; $i++) { $captcha_word .= substr($symbols, rand(0, strlen($symbols)), 1); }
    for ($i = 0; $i <= $wordLength; $i++) {
        imagettftext($captcha_image, rand(24, 28), rand(-20, 20), $i*rand(30, 36)+rand(2,4), rand(32, 36), $captcha_image_lcolor[rand(0, 2)], "/includes/".rand(1, 3).'.ttf', $captcha_word{$i}); }
    # image built. Now save it as a file and then display it. Kill the file in a garbage collector script later.
    imagepng($captcha_image, $filename);
    imagedestroy($captcha_image);       # cleanup...
    #unlink($filename);                                                             # crap! Image doesn't last long enough.  use GarbageCOllection to delete these
    return "<img src='$filename'><input type='hidden' name='captchaword' value='".md5($captcha_word)."'>";
}


function generatePassword ($len=8) {
    $str = array_merge(range(1,9), range("a","z"), range("A","Z"));
    for ($i=1; $i<=$len; $i++) { $rtr .= $str[(mt_rand(0, count($str)))]; }
    return $rtr;
}


function canAccess ($path='') {
    # function to test if this user can access the target. Looks at the 3 tests andreturns true if all ok else false if any test fails.
    # - before loading any page=, look for access.accTarget record
    #    if not found, display  
    #    else look at grpAccess. 
    #       # checking stops when username found so View+Edit would never get to Edit check
    #        if Edit NOT NULL and user is in list, open for edit/upload, 
    #                Display < textarea> at top, then upload bar, then normal display page at bottom
    #        if View NOT NULL and username is in list, display. 
    #        if Login NOT NULL, and user loggedin, display
    #        else display error.
    $res = myQuery("SELECT * FROM access where accTarget='".addslashes($path)."';");
    if (mysql_num_rows($res) > 0) {
        $row = mysql_fetch_array($res);
        if (($row['accLogin'] != "") && (strlen($_SESSION['username'] > 0))) {
            return true;
        } elseif (strpos($row['accView'], $_SESSION['username']) !== false) {
            return true;
        } elseif (strpos($row['accEdit'], $_SESSION['username']) !== false) {
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
}


function userIsAdmin () {
    # match user's access code string to action=. If in string, user is page admin, else return false.
    if ($_SESSION['access'] == "X") { myErr("You are suspended!"); return false; }
    if (strpos($_SESSION['access'],strtoupper(substr($_GET['action'],0,1))) !== false) { 
        return true; }
    return false;
}
?>
