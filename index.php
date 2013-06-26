<?php

$maxPage = 25000;       # max characters per blog page
$today   = date("Y-m-d 23:59:59");
$cs      = 12;                    //colspan= value (too big is good...)
$valExt  = array(".JPG",".jpg",".PNG",".png",".GIF",".gif");
$spc     = "&nbsp; &nbsp;";
session_start();
if (!isset($_SESSION['last_access']) || (time() -$_SESSION['last_access']) > 60) {
    $_SESSION['last_access'] = time(); # Session times out eventually. Updates to any session var reset timer...
}
require_once("./includes/functions.php");

// load global definitions for this server. These are key=value pairs. // denotes comment and is ignored.
#require("includes/config.php");
$fh  = fopen("includes/config.php","r");
$cmt = $sec = 0;        # section parsing switches: comments, [] braced overrides resp.
while (!feof($fh)) {            
    $l = fgets($fh);
    if (substr($l, 0, 2) == "/*") { $cmt = 1; continue; }  // start comment section
    if (substr($l, 0, 2) == "*/") { $cmt = 0; continue; }  // end comment section
    if ($cmt == 1)                { continue; }  // in comment section
    if (substr($l, 0, 2) == "//") { continue; }  // comment line
    if (substr($l, 0, 2) == "#")  { continue; }  // comment line
    if (strlen(trim($l)) < 2)     { continue; }  // blank lines are ignored, too
    if (substr($l, 0, 1) == "[")  { $sec = 1; continue; }  // new section header (Not used yet)
    $c = explode("=", $l);  
    define($c[0], trim($c[1]));     
} 
if (($cmt == 1) && feof($fh)) { myDie ("Still in comments section at end of config!"); }
#if (($sec == 1) && feof($fh)) { myDie ("Still in a section at end of config!"); }
fclose($fh);

if (strlen($_POST['lastdate']) > 0) {
    webHeader("Journal");
} elseif (strlen($_GET['page']) > 0){
    webHeader(substr($_GET['page'], strpos("/",$_GET['page'])));
} elseif ($_GET['action'] == "articles") {
    if (strlen($_GET['title']) > 0) { webHeader($_GET['title']);
    } else { webHeader($_GET['action']); }
} else {
    webHeader("Metali'Cat");
}

/*
page        - .txt, .html. if string has '/', page title = what's past last '/'. essays in html format of text and photos.
link         - <a href='' target='_blank'>
action      - goto a .php script
mailto      - <a href='mailto:'>
*/
#myDebug($_POST,"PST:"); print "<br/>\n"; myDebug($_GET,"GET:"); print "<br/>\n"; 
#myDebug($_SESSION,"SES:"); print "<br/>\n";

if (strlen($_GET['action']) > 1) {
    # These 4 'action's get into the login.php code. Look there for explainations and process.
    if (in_array($_GET['action'], array("login", "register", "logout", "editprofile"))) {
        include("users.php");
        #include("./includes/login.php");
    # Other basic functions 
    } elseif ($_GET['action'] == "guestbook") {
        $_GET['key'] = "G".date("Ymdhi");
        include("comments.php");
    } elseif (file_exists(soothGet($_GET['action']).'.php')) {
        include($_GET['action'].'.php');
    } else {
        myMissing("action", soothGet($_GET['action']));
    }
} elseif (strlen($_GET['page']) > 1) {
    # - before loading any page=, look for access.accTarget record
    #    if not found, display  
    #    else look at grpAccess. 
    #       # checking stops when username found so View+Edit would never get to Edit check
    #        if Edit NOT NULL and user is in list, open for edit/upload, 
    #                Display < textarea> at top, then upload bar, then normal display page at bottom
    #        if View NOT NULL and username is in list, display. 
    #        if Login NOT NULL, and user loggedin ($_SESSION[username] != NULL) display
    #        else display error.

    $res = myQuery("SELECT * FROM access where accTarget='".$_GET['page']."';");
    if (mysql_num_rows($res) > 0) {
        $row = mysql_fetch_array($res);
        if (($row['accLogin'] != "") && (strlen($_SESSION['username'] > 0))) {
            print loadPage($_GET['page']);
        } elseif (strpos($row['accView'], $_SESSION['username']) !== false) {
            print loadPage($_GET['page']);
        } elseif (strpos($row['accEdit'], $_SESSION['username']) !== false) {
            $page = loadPage($_GET['page']);
            print "<form method='POST' action='/?action='savePage'>\n";
            print "<textarea rows='25' cols='68' name='pageText'>$page</textarea>\n";
            print "<br/><input type='submit' name='submit' value='Save Page'>\n</form>\n";
            uploadPage($page); 
            print "<br/><br/>$page";
        } else {
            myErr("Not authorized to access page: ".$_GET['page']."!");
        }
    } else { 
        print loadPage($_GET['page']);
    }
#} elseif ($_POST['submit'] == "Save Page") {
} else { include("./journal.php"); }                  # Journal section
 
print "    <!-- end right column -->\n";
webFooter();


function loadPage($file='') {
    if (file_exists($file.".html")) {
        $html = array_map('rtrim', file($file.".html"));
        foreach ($html as $k => $l) { $page .= stripslashes($l); };
    } elseif (file_exists($file.".txt")) {
        $page = "<pre>\n";
        foreach (file($file.".txt") as $k => $l) { $page.= wordwrap($l, 90, "\n"); }
        $page.= "</pre>\n";
    } else {
        myMissing("page", $file);
    }
    return $page;
}

?>
