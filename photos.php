<?php
# photos.php - display a list of dir names subordinate to a specific base as links. Once a dirname is 
#       elected, display thumbnails of all the photos in the dir. each thumbnail is a link to showphotos.php
#       which displays the full sized photo.
#   Note: Hidden or dotted directories do not show in this list. They are accessable w/ URL: ?dir=.dotted_dir
#   2 lists: $dirs is list of dirs, $pics is list of pics. Display will list dirs along top of screen, then 2-columns
#   of 'pics'. For those (like me) w/ dialup service, display thumbs. User can click a thumb and see the full pic on a new page.
#   If pics don't have a corresponding thumb, create one, store it and display it. 
/*  2009-10-28 k.k - add code to allow admin to add text to pics and sets directly from their display pages.
                - display only the top level and 2nd levels. Maybe indicate there are more lvls.

*/
$getPath     = soothGet($_GET['dir']);
$getFile     = soothGet($_GET['file']);
$PhotoPath   = PhotoDir . "/".$getPath; # . "/"; 
$maxDirCols  = 4;  # columns of dirs per row. 3 if icons used, else use 5
$curDir      = 1; 
$maxFilCols  = 2;  # mxc +1 columns of photos w/ basename below centered.
$colCount    = 0;
$dirs        = $files = array();
$maxPicBytes = 600000;
$rszHeight   = 640;
$rszWidth    = 480;
$valExt      = array(".JPG",".jpg",".PNG",".png",".GIF",".gif");

# process any Admin changes to the descriptive text before displaying it.
$dr  = ((strlen(trim($getDir))  > 0) ? "='".addslashes($getDir)."'"  : " IS NULL");
$fn  = ((strlen(trim($getFile)) > 0) ? "='".addslashes($getFile)."'" : " IS NULL"); 
$sq  = "dirName".$dr." AND fileName".$fn;
if ($_POST['submit'] == 'delete') {
    myQuery("DELETE FROM photos WHERE $sq;");
    $txt = "";
} elseif ($_POST['submit'] == "save") {
    if (strlen($txt) > 1) {         # record exists so update, else create
       $sql = "UPDATE photos SET imageText='".addslashes($_POST['imageText'])."' WHERE $sq;";
       myQuery($sql);
    } else { 
        $sql = "INSERT INTO photos (dirName,";
        if (strlen(addslashes($getFile)) > 0)  { $sql .= "fileName,"; }
        $sql .= "imageText,createDate) VALUES ('".addslashes($getPath)."',";
        if (strlen(addslashes($getFile)) > 0)  { $sql .= "'".addslashes($getFile)."',"; }
        $sql .= "'".addslashes($_POST['imageText'])."',NOW());";
        myQuery($sql);
    }
    $txt = $_POST['imageText'];
}
print "<div id='photos'>\n";

# Build 2 arrays: $dirs contains directory names in ./, $files contains original photo names. Thmbs,  tmps, etc ignored
# If user is not allowed to see something, simply don't load the dir or file in the arrays.
foreach (scan_dir($PhotoPath) as $fileName) {
    if (substr($fileName, 0, 1) == ".") { continue; }   # ignore dotted dirs and hidden files
    if (substr($fileName, -3) == ".db") { continue; }   # ignore 'thumbs.db' from Win systems
    if (is_dir($PhotoPath ."/". $fileName)) {
        if (canAccess($PhotoPath ."/". $fileName)) { $dirs[] = $fileName; }
    } elseif (is_file($PhotoPath."/".$fileName)) {
        # ignore temp files used for rotation and resizing.        # ignore thumbnail files
        if (strpos($fileName, ".tmp.") !== false)              { continue; }
        if (strpos(strtolower($fileName), ".thmb.") !== false) { continue; }
        if (strpos(strtolower($fileName), ".640.")  !== false) { continue; }
        if (in_array(substr($fileName, -4), $valExt)) { 
            if (canAccess($PhotoPath ."/". $fileName)) { $files[] = $fileName; } }
    } else {        # There shouldn't be anything else...       (symlinks?)
        myErr("Unknown dir/file type: $PhotoPath|$fileName|");
    }
}

# All photos get eventually get a thumbnail generated. Large pictures also get a smaller version created. Some servers
#       can't handle making thumbs from large pics. So... Generate the smaller image 1st if needed, then generate the 
#       thumbnail from the smaller image.
# ignore .jpgs (case sensitive) named same as an existing dirname. Remove it from $file so proper headers are 
#       displayed later.  These icon files have no thumbs or any processing done to them.
$foo = array();
foreach ($files as $file) {
    $orgPhoto = $PhotoPath."/".$file;
    $fnBase   = substr($file, 0, -4);
    $fnExtn   = substr($file, -4);
    $sixPhoto = $fnBase.".640".$fnExtn;
    $thmPhoto = $PhotoPath."/". $fnBase.".thmb".$fnExtn;
    if (in_array($fnBase, $dirs)) { continue; }
    if (filesize($orgPhoto) > $maxPicBytes) {
        if (!(file_exists($sixPhoto))) { 
            createResizedImage($orgPhoto, $PhotoPath."/".$sixPhoto, $rszWidth, $rszHeight); }
        # For photos this big, generate the thumb from the 640 image instead of the original
        if (!(file_exists($thmPhoto))) { 
            createResizedImage($sixPhoto, $thmPhoto); }
    } else {
        # Generate thumb for this photo if none exists...
        if (!(file_exists($thmPhoto))) { createResizedImage($orgPhoto, $thmPhoto); }
    }
    $foo[] = $file;     # build a temp array of files w/o the dirname icons.
}
$files = $foo; 

# get any relevant image text. May be text for an entire dir (where there is no fileName) or for a specific image where there will be Dir and File args.
$txt = "";
$dr  = ((strlen(trim($getPath)) > 0) ? "='".addslashes($getPath)."'" : " IS NULL");
$fn  = ((strlen(trim($getFile)) > 0) ? "='".addslashes($getFile)."'" : " IS NULL"); 
$res = myQuery("SELECT imageText FROM photos WHERE dirName".$dr." AND fileName".$fn.";");
if (@mysql_num_rows($res) > 0) { 
    $row = mysql_fetch_assoc($res); 
    $txt = stripslashes($row['imageText']);
}
            
print "<br/>";      # this is stuck way down here and alone to seperate from possible error messages.
print "<form action='".$_SERVER['REQUEST_URI']."' method='POST'>\n";
    
if (!isset($_GET['dir'])) {
    print "<table>\n";
    foreach (getDirectoryTree(PhotoDir) as $d => $a) {
        print "<tr><td colspan='$maxDirCols'><h2><a href='?action=photos&dir=$d'>".str_replace("_"," ",$d)."</a></h2></td></tr>\n";
        if (count($a) > 0) { dispDirs($a, "$d/"); }
    }
    print "</table>\n";
    if (count($files) > 0) {
        $colCnt = 0;
        print "<br/><center><hr width='75%'><br/>\n<table>\n<tr>";
        foreach ($files as $file) {
            if ($colCnt > $maxFilCols) { $colCnt = 0; print "</tr>\n<tr>"; } else { $colCnt++; }
            print "<td><a href='".WhichLocal."?action=photos&dir=".$getPath ."&file=".$file."'>";
            print "\n<img border='0' src='".$PhotoPath."/".substr($file,0,-4).".thmb".$fnExtn."'></a>";
            print "<br/><center>".substr($file, 0, -4)."</center></td>";  
        }
        print "</tr>\n</table\n</center>\n";
    }    
} else {
    /*# Bread crumb trail displayed everywhere but on Home/Album screen
    print "<strong>Breadcrumbs:</strong>";
    $trail = explode("/", $_GET['dir']);
    $d     = "";
    $where = "&nbsp;&nbsp;<a href='".WhichLocal."?action=photos";
    print $where . "'>Home</a>\n";
    foreach ($trail as $t) { 
        print "&nbsp;&nbsp;/".$where."&dir=".$d.$t."'>$t</a>\n";
        $d .= $t . "/";
    }
    print "<br/>";
    */
    if (strlen($_GET['file']) > 0) {
        # This prints the current pic and sets the links for 'prev' and 'next'  pics. Also sets a 'Back' link to return 
        #       to the thumbnails page for  this lot of pics.  Next|Prev links set Size and Orientation to default. 
        $getFile = soothGet($_GET['file']);
        $currpic = array_search($getFile, $files);
        $prevpic = $currpic -1;
        $nextpic = $currpic +1;
        $preLink = "&nbsp;<a href='".WhichLocal."?action=photos&dir=".$getPath;
        $photo   = $PhotoPath . "/" . $getFile;
        $ndx     = count($files);
        # ToDo:   $addLink = "&zoom=$resize&orient=$orient";
        print "<strong>Navigate:</strong>&nbsp;";
        print (($prevpic < 0) ? "First" : $preLink."&file=".$files[$prevpic] . "'>< Prev</a>");
        print "\n&nbsp;<a href='".WhichLocal."?action=photos&dir=$getPath'> Back </a>&nbsp;\n";
        print (($nextpic >= $ndx)?"&nbsp;Last":$preLink."&file=".$files[$nextpic] . "'>Next ></a>");
        # add 1 for OPTION BASE 1 effect. arrays start count at 0 but $ndx holds true number of images!
        print "\n&nbsp;(" . ($currpic +1) . "&nbsp;of&nbsp;" . $ndx . ")\n";
        # display the single photo. If the pic is too large, show the smaller version w/ a link to the full sized else display the pic.
        if (filesize($photo) > $maxPicBytes) {
            print "<a href='$photo' target='_blank'>Full sized</a> image (".filesize($photo)." bytes)"; 
            $photo = substr($photo, 0, -4) . ".640". substr($photo, -4);
        }
        print "<br/><br/><br/><center>\n<img border='0' src='$photo'><br/><br/>\n";
        if ($_SESSION['status'] == "1") {
            print "<textarea name='imageText' rows='5' cols='60'>$txt</textarea>";
            print "<br/><input type='submit' name='submit' value='save'> &nbsp; &nbsp;";
            print "<input type='submit' name='submit' value='delete'>\n";
        } elseif (strlen($txt) > 0) {
            print "<center>".wordwrap($txt, (($maxFilCols+1)*25))."</center>"; 
        } else {
            print substr($files[$currpic], 0, -4)."\n";
        }
        // If there is associated text in the photos table, display it here.
        print "</center>\n";
    } else {
        if (count($dirs) > 0) {     # Display the directories first horizantally along the top of the page.
            print "<table>\n";      #       if there is a .jpg named as the dir, display it under the dir name
            print "  <tr><td nowrap><strong>Photo Sets:</strong>&nbsp;&nbsp;</td><td>&nbsp;</td></tr>\n";
            dispDirs($dirs, $getPath ,"val");
            print "</table>\n";
        }    
        print "<br/><center>";
        if ($_SESSION['status'] == "1") {
            print "<textarea name='imageText' rows='5' cols='60'>$txt</textarea>";
            print "<br/><input type='submit' name='submit' value='save'> &nbsp; &nbsp;";
            print "<input type='submit' name='submit' value='delete'>\n";
        } elseif (strlen(trim($txt)) > 0) { 
            print "".wordwrap($txt,(($maxFilCols+1)*30),"<br/>"); 
        }
        print "</center>";
        if (count($files) > 0) {
            # Now display the photos. Original filename sans extension displayed under pic
            print "<br/><br/>(Click on any thumbnail for a larger image...)<br/><br/>\n";
            print "<table>\n";
            foreach ($files as $file) {
                # if pic is same name as a dirname, ignore it as it was displayed in the Photo Sets dirname list.
                if (in_array(substr($file, 0, strpos($file, ".")), $dirs)) { continue; }
                if ($colCount < 1) { print "<tr>"; }
                $fnBase = substr(basename(PhotoDir . $file),0, -4);
                $fnExtn = substr($file, -4);
                # Then display columns of thumbs, not the original big pictures.
                print "  <td width='30%'><center><a href='".WhichLocal. "?action=photos";
                print "&dir=".$getPath . "&file=".$file."'>";
                print "\n<img border='0' src='".$PhotoPath."/".$fnBase.".thmb".$fnExtn."'></a>";
                print "<br/>".$fnBase."</center><br /><br /></td>\n";
                $colCount++;
                if ($colCount > $maxFilCols) { print "</tr>\n"; $colCount = 0; }
            }
            if ($colCount > 0) { print "</tr>\n"; }
            print "</table>";
        }
    }
    # Get ready for comments. build the query key and get the text only if there are $files in this directory.
    if (count($files) > 0) {
        $_GET['key'] = "F".urlencode($_GET['dir']."|".$_GET['file']);
        include("comments.php");
    } 
}
print "<br/><br/>";
print "</tr></table>\n";
print "</form>\n";
print "<!-- Page contents end here -->\n";
print "</div id='photos'>\n";


function createResizedImage($inFile, $outFile, $newheight='160', $newwidth='160') {
    # Creates new pics in the same dir as master photo. $*file is the filename with relative path attached.
    $thumb   = @imagecreatetruecolor($newwidth, $newheight);
    $ext     = strtolower(substr($inFile, -3));   # load lowercase version extension from path/filename
    if ($ext == "jpg")       { $source = imagecreatefromjpeg($inFile);
    } elseif ($ext == "png") { $source = imagecreatefrompng($inFile);
    } elseif ($ext == "gif") { $source = imagecreatefromgif($inFile); }
    list($width, $height)  = getimagesize($inFile);  // Get current image sizes
    imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
    if ($ext == "jpg")       { imagejpeg($thumb, $outFile);
    } elseif ($ext == "png") { imagepng($thumb,  $outFile);
    } elseif ($ext == "gif") { imagegif($thumb,  $outFile); }
    return true;
}


function convertImage($fileName) {
    if (in_array(strtolower(substr($fileName, -4)), array(".gif", ".png"))) {
        if (strtolower(substr($fileName, -4)) == ".gif") { 
            $nil = imagecreatefromgif($relPath.$fileName); }
        if (strtolower(substr($fileName, -4)) == ".png") { 
            $nil = imagecreatefrompng($relPath.$fileName); }
        # generate new JPG filename. If exists, append a counter til unique in dir.
        $baseName = substr($fileName, 0, -4);
        if (file_exists($relPath.$baseName.".jpg")) {
            $ndx = 1;
            $nameLength = strlen($baseName);
            while (file_exists($relPath.$baseName)) { 
                if ($ndx > 999) { myErr("Can't gen unique name for $fileName"); break;}
                $baseName = substr($baseName, 0, $nameLength)."_".++$ndx.".jpg";
            }
        }
        # if no unique name genned, ignore the file. Maybe we'll gen an err eventually.
        if (!file_exists($relPath.$baseName.".jpg")) { 
            imagejpeg($img, $relPath.$baseName.".jpg"); 
            createThumb($relPath.$baseName.".jpg");
        }
    }
    return true;
}


function dispDirs ($dirs, $path, $key='key') {
    global $maxDirCols;
    $curDir = $totDirs = 0;
    print "<tr>\n";
    foreach ($dirs as $dir => $foo) {
        if ($key != "key") { $dir = "/".$foo; }
        $curDir++; $totDirs++;
        $sdir = str_replace(array("_","/")," ",$dir);
        print "<td valign='top'><a href='".WhichLocal."?action=photos&dir=";
        print $path.$dir."'>";
        $foo = PhotoDir."/".$path."/".$dir.".jpg";
        if (file_exists($foo)) { 
            print "<center>$sdir</center><img src='$foo'>"; 
        } else { print "$sdir"; }
        print "</a></td>\n<td>";
        if ($curDir > $maxDirCols) { print "</tr>\n  <tr>"; $curDir = 1; 
        } elseif ($totDirs < count($dirs)) { print "&nbsp; | &nbsp;</td>\n"; }
    }
    print "</tr>\n";
}

?>
