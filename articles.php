<?php 
/* articles.php - An article is a document containing text and possibly images (or YouTube like movie links?). There is an index
    page listing these articles as links. All of theses pieces are located in one directory. This script will parse and build the 
    appropriate HTML to display and navigate all of these parts.

# This may one day be useful....
# <a href="javascript:openImage('/index.php?id=33&prod=682&showimg=1',106,70)"><img src="/site/img/prod/large/682.jpg" border="0"></a>

*/
$dir = ArticleDir . soothGet($_GET['dir']);
$art = soothGet($_GET['article']);

/*
Articles requires 3 elements: 
    A directory named ./articles located directly off the website root. The ./articles directory contains the article index 
        page and a sub-folder for each article. 
        ? Future versions should allow use of other folders  (like recipes?)
    In this folder is a file named articles.txt.  This index contains a description of and link(s) to the articles. This page is 
        a static html page or a text file of parsable elements, but not both. The name must be the same as the folder and use 
        the .txt extension.
         - If static html, strip all but <body> contents, so folks can still use that *@#$ FrontPage or the like.
         - add a switch to admin to ignore files not defined in the index or display these in a Misc column.
    Each article can be in one of 2 formats.
        if the article contains images or more than text is contained in a folder subordinate to articles/. 
            The name of the folder is the name of the article. 
            The article text is named index.html|htm|txt|etc. Images can be of type JPG|GIF|PNG|etc.  
        if the article contains only text, it can be a single file located subordinate to articles/.
            The name of the file is the article title and must end with .[htm|html|txt].
        
The article page can contain either a mix of html and/or directives mixed within the text to be displayed. 
    - Each article is displayed in a 2 column table. Text is spread over the 2 columns to any image. 
    The image is displayed with text in the other column (unless centered) .
     - By default, images are displayed in the left column with text flowing to the right or left and below in both columns.
    - no text beside 'centered' images but [desc] directive* displayed centered below image
    - Any html tags will be displayed as normal.
    - The paragraph will be closed via </p> tag when a blank line, HTML </p> tag, or >=4 spaces preceed text 
        on the current line is to be displayed. Automatically generate closing <p>aragraph tags.
    ? >= 4 blanks on a line or tab character (? hex) indents  at the start of a line cause a new paragraph.
    - path to images?

Article Title Page Directives:
* The parsable directive elements are directives to allow for dynamic generation of the page. 
    - Generated HTML will obey any defined CSS style sheets.
    - article folder must have the column_title prefix and have case and underlines the same.
    - Blanks are allowed. underlines are replaced with spaces.
    - The directives are:
        [column_title prefix= desc=] - displayed centered in <h2> element. 
            When prefix used, scandir for list of prefixes to display under title, prefix stripped. 
            When description used, it is centered in italics and centered under the 'column title'.
        article_title file= desc=
            article_title is the displayed value and the filename. case and spaces are preserved.
            When used, file= is filename of article. This overrides the article_title in the <a> link
            When used, desc= it is italisized text displayed beside article_title. 
        directoryname - place article at this position in list
        [nc] - new column.  Forces start of a new page column

 Admin page:
 - switch for articles not directly referenced in index - either ignore or include in a Misc category
 - upload article and images - 
    - upload the article file
    - create corresponding image dir and upload images to it.
 - tweek articles.txt - use <textarea> with Save button like config.txt and leftmenu.txt
 - switch to force red 'new' alert for recently added articles. 1|2 weeks, months, etc. setting
 */

if (strlen(soothGet($_GET['article'])) > 0) {
    print "<div id='articles'>\n";
    if (is_dir($dir."/".$art)) {
        foreach (file($dir."/".$art."/index.html") as $f) {
            if (trim(strpos($f, "[img") !== false)) {
                $src = $wdt = $len = $des = "";
                $src = parseTag($f, "file=");
                $wdt = parseTag($f, "width=");
                $hgt = parseTag($f, "height=");
                $des = parseTag($f, "desc=", "]");
                print "<p><img src='./".$dir."/".$_GET['article']."/$src' width='$wdt' height='$hgt' /><i>$des</i></p>\n";
            } elseif (trim(strpos($f, "<img") !== false)) {
                # src= may contain bogon path. Strip out the filename and append it to proper path. Leave all other args as is.
                #$src = parseTag($f, "src="); - the str_replace() precludes using this function...
                $src = substr($f, strpos($f, "src=") +4);
                $src = str_replace(array("\"", "'"), "", $src);
                $src = $nfl = trim(substr($src, 0, strpos($src, " ")));
                if (strpos($nfl, "/") !== false) { $nfl = substr($nfl, strripos($nfl, "/") +1); }
                print str_replace($src, "./".$dir."/".$_GET['article']."/$nfl", $f);
            } else {
                $f = str_replace("[br]","<br/>",$f);
                if (strlen($f) < 3) { $f = "<br/><br/>".trim($f)."\n"; }
                print $f;
            }
        }
    } else {
        if (strtolower(substr($art, -4)) == ".txt") { 
            print "<pre>"; 
            foreach (file($dir."/".$art) as $r) { 
                print wordwrap($r, 80, "<br/>"); }
            print "</pre>";
        } else {
            include($dir."/".$art); 
        }
    }
    print "</div id='articles'>\n";
} else {    # Show file list of base dir as specified by directives in atricles.txt
    $list  = file(ArticleDir."/articles.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $files = scan_dir($dir);
    $arCol = array();
    $col   = $row = 0;
    foreach ($list as $k) {
        $k = stripslashes($k);
        if (substr($k, 0, 1) == "#")    { continue; }
        $row++;
        $titl = $pref = $desc = "";
        if (substr($k, 0, 4) == "[nc]") {         # new column
            $col++; $row = 0;
        } elseif (substr($k, 0, 1) == "#") {      # coment line
            continue;
        } elseif (substr($k, 0, 1) == "[") {      # column title. 
            $titl = trim(str_replace("_", " ", substr($k, 1, trim(strpos($k, "prefix=") -1))));
            $arCol[$col][$row]['title'] = trim($titl);
            $row++;     #title uses 2 output rows ( <td rowspan='2'>) so add a blank row.
            $arCol[$col][$row] = trim($titl);
            if (strpos($k, "prefix=") !== false) {
                $pref = substr($k, strpos($k, "prefix=") +7);
                $pref = preg_split("/[\s\]]/", $pref);
                $pref = $pref[0];           # Changing datatypes on the fly is ugly but fun!
            }
            if (strpos($k, "desc=") !== false) {
                $row++;
                $desc = substr($k, strpos($k, "desc=") +5);
                $desc = preg_split("/[\]]/", $desc);
                $arCol[$col][$row]['desc'] = $desc[0];
            }
            for ($i=0; $i<=count($files); $i++) {
                if (substr($files[$i],0,1) == ".")             { $files[$i] = "XX"; continue; }
                if (substr($files[$i],0,12) == "articles.txt") { $files[$i] = "XX"; continue; }
                if ($files[$i] == "XX")                        { $files[$i] = "XX"; continue; }
                $f = $files[$i];
                if (substr($f, 0, strlen($pref)) == $pref) {
                    $row++;     # strip off the prefix, and extention if a file
                    $g = str_replace("_", " ", trim(substr($f, strlen($pref))));
                    $g = ((strpos($f, ".") !== false) ? substr($g, 0, strpos($g, ".")) : $g);
                    $arCol[$col][$row]['f'] = $f;
                    $arCol[$col][$row]['g'] = $g;
                    $files[$i] = "XX";      # Any elements left at the end of this are orphans.
                }
            }
        } 
    }
    # add articles that don't fit under any of the titles in another column:
    foreach ($files as $f) {
        if ($f != "XX") {
            if ($fndOrphan != true) { 
                $col++; $fndOrphan = true; $row=2;
                $arCol[$col][0] = $arCol[$col][1] = "";
            }
            $arCol[$col][$row]['f'] = $f;
            $g = ((strpos($f, ".") !== false) ? substr($f, 0, strpos($f, ".")) : $f);
            $arCol[$col][$row]['g'] = str_replace("_", " ", $g);
            $row++;
        }
    }
    # index columns built in $arCol[] 
    $ndx = $row = 0;
    # if $col > 0 then we have multi column index page. THis will require a table. Else, just list everything in order.
    # find longest column. This drives length of index table. Title has rowspan=2 - ignore 
    foreach ($arCol as $c => $d) { $maxRows = max($maxRows, count($d)); }
    print "<table width='90%'>\n<center>\n";
    for ($r=1; $r<=$maxRows; $r++) {
        print "<tr>\n";
        for ($c=0; $c<=$col; $c++) {
            if (count($arCol[$c]) < $r)    { print "  <td> </td>\n"; continue; }
            if (!is_array($arCol[$c][$r])) { continue; }
            if (strlen($arCol[$c][$r]['title']) > 0) {
                $title[$c] = $arCol[$c][$r]['title'];
                print "  <td rowspan='2'><h2>".$title[$c]."</h2></td>\n";
            } elseif (strlen($arCol[$c][$r]['desc']) > 0) {
                print "  <td><i>".$arCol[$c][$r]['desc']."</i></td>\n";
            } elseif (strlen($arCol[$c][$r]['f']) > 0) {
                print "  <td><a href='?action=articles&article=".$arCol[$c][$r]['f']."&title=".$title[$c]."' style='text-decoration: none;'>".$arCol[$c][$r]['g']."</a></td>\n";
            }
        }
        print "</tr>\n";
    }    
    print "</table>\n</center>\n";
}


function parseTag ($row, $val, $end=' ') {   
    # strip value off an HTML tag or directive. Look for the $end value in $row and start strip on next character. 
    #   Return all characters up to the 1st $end character encountered or NULL.
    $out = "";
    $out = substr($row, strpos($row, $val) +(strlen($val)));
    $out = trim(substr($out, 0, strpos($out, $end)));
    return $out;
}
?>
