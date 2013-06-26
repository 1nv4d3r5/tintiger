<?php
# listphotos.php - display a list of dir names subordinate to a specific base as links. Once a dirname is 
#       elected, display thumbnails of all the photos in the dir. each thumbnail is a link to showphotos.php
#       which displays the full sized photo.
#   Note: Hidden or dotted directories do not show in this list. They are accessable w/ URL: ?dir=.dotted_dir
#   2 lists: $dirs is list of dirs, $pics is list of pics. Display will list dirs along top of screen, then 2-columns
#   of 'pics'. For those (like me) w/ dialup service, display thumbs. User can click a thumb and see the full pic on a new page.
#   Create thumbs on the fly. If pics don't have corresponding thumb, create one, store it and display it. 
$getPath    = soothGet($_GET['dir']);
$PhotoPath  = PhotoDir . $getPath; # . "/"; 
$absPath    = $AbsoluteRef . PhotoDir . $getPath;
$maxFilCols = 2;  # mxc +1 columns of photos w/ basename below centered.
$maxDirCols = 5;  # mxc +1 columns of dirs per line
$dirs       = $files = array();

print "<!-- Page contents begins here -->\n";
print "<div id='listpic'>\n";

# Build 2 arrays: $dirs contains directories in ./, $files contains all the pics. Thmbs, .ed, tmps, etc ignored
foreach (scan_dir($PhotoPath) as $fileName) {
    if (substr($fileName, 0, 1) == ".") { continue; }   # ignore dotted dirs and hidden files
    if (substr($fileName, -3) == ".db") { continue; }   # ignore 'thumbs.db' on Win systems
    if (is_dir($PhotoPath ."/". $fileName)) { 
        $dirs[] = $fileName; 
    } elseif (is_file($PhotoPath."/".$fileName)) {
        # ignore temp files used for rotation and resizing.        # ignore thumbnail files
        if (strpos($fileName, ".tmp.") !== false)              { continue; }
        if (strpos(strtolower($fileName), ".thmb.") !== false) { continue; }
        if (in_array(substr($fileName, -4), $valExt)) { 
            $files[] = $fileName;
        }
    } else {        # There shouldn't be anything else...       (symlinks?)
        myErr("Unknown type. Not file or directory: $PhotoPath|$fileName");
    }
}
print "<br/>";      # this is stuck way down here and alone to seperate from possible error messages.

$curDir  = 1;
if (count($dirs) > 0) {     # Display the directories first horizantally along the top of the page.
    print "<table>\n";
    print "  <tr><td><strong>Photo Sets:</strong>&nbsp;&nbsp;</td><td>&nbsp;</td>\n";
    foreach ($dirs as $dir) {
        $curDir++; if ($curDir > $maxDirCols) { 
            print "</tr>\n  <tr>"; $curDir = 1; }
        print "<td><a href='".WhichLocal."?action=listthumbs&dir=";
        print $getPath . (strlen($getPath) > 0 ? "/" : ""). $dir."'>$dir</a></td>\n<td>&nbsp; | &nbsp;</td>\n";
    }
    print "</tr>\n</table>\n";
}    

$txt = " ";      #getImageText($getPath);
if (strlen(trim($txt)) > 0) { print "<p>$txt</p><br/>"; }

if (count($files) > 0) {
    # Now display the photos. Original filename sans extension displayed under pic
    $colCount = 0;
    $newwidth = $newheight = 160;     #120;
    print "<br/><br/>(Click on any thumbnail for a larger image...)<br/><br/>\n";
    print "<table>\n";
    foreach ($files as $file) {
        if ($colCount < 1) { print "<tr>"; }
        $fnBase = substr(basename(PhotoDir . $file),0, -4);
        $fnExtn = substr($file, -4);
        # Generate thumb for this photo if none exists...
        if (!(file_exists(AbsoluteRef.PhotoDir.$getPath.$fnBase."thmb".$fnExtn))) {
            createThumb(AbsoluteRef. PhotoDir .$getPath."/".$file);
        }
        # Then display columns of thumbs, not the original big pictures.
        print "  <td width='30%'><center><a href='".WhichLocal."?action=showphoto";
        print "&dir=".$getPath . "&file=".$file."'>";
        print "\n<img border='0' src='".PhotoDir.$getPath."/".$fnBase.".thmb".strtolower($fnExtn)."'></a>";
        print "<br/>".$fnBase."</center><br /><br /></td>\n";
        $colCount++;
        if ($colCount > $maxFilCols) { print "</tr>\n"; $colCount = 0; }
    }
    if ($colCount > 0) { print "</tr>\n"; }
    print "</table>";
}
print "<br/><br/>";
print "</tr></table>\n";
print "<!-- Page contents end here -->\n";
print "</div id='listpic'>\n";
?>
