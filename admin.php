<?php 
/*Admin.php - screens w/ rtns to admin Sparrow sites without using host's phpMyAdmin or control panel 

Access management:
- Sets rights to access documents or directories.
- use document or dir name as target. name is arbitrary (can be file or dir but doesn't have to be). 
- access records can be editted by SiteAdmin or any accEdit user. Only SiteAdmin can add accEdit users?
    When user accesses this Admin page, see if user has Edit rights. No one else can access Acccess records. 
    group records by users  ??
    Edit rights (display only recs username can edit). 

    
If ALLOW_REGISTRATION==1, folks can see the 'register' link and create an account. if ==0, only admin created accounts allowed. 
codes for registered users:
blank - normal user, maybe existence allows access to 'member' sections. This is the default for created accounts.
A - upload, maintain articles , can edit articles/articles.txt
C - calendar editor
H - homepage.html editor
J - journal editor
M - moderate comments and guestbook (show|hide|delete)
P - photo descriptions editor
X - inactive, suspended, anything bad - can't see member areas. Also username can't be used again
1..n  - access to certain sections, like board member vs regular member, 
  selected forums
*/
$accPrompt = "Articles, Calendar, Homepage, Journal, Moderator, Photos, X = suspend, 1..n";
/*
    Upon login, load member.access_codes into $_SESSION[access]. Access to any page or action will cause a lookup into the access table. access holds the page name and what access is required,. If member is in access, allow access.
    
Access.
    accName
    accTarget - dir or filename
    accLogin - if NOT NULL must be logged in to view
    accView - set of usernames that have View rights
    accEdit - set of usernames that can edit/upload

    
Member management:
- user must be Admin!
- list users from database w/ 'Delete',Suspend  and 'Edit' buttons
- screen for single user (use login.php:displayProfile()?) w/ additional fields


Left Menu mamagement:
- Only SiteAdmin can access
-  'Save' will automatically cause redraw so changes are noticed immediatly.


Config management:
- Only SiteAdmin can access this facility
- display rules at top of screen, then display config in < textarea >
- display 'Save' button on bottom
*/
# sooth possible GET vars.

if (strlen($_SESSION[username]) < 1) {
    myErr("You must login before you an use this facility.");
} elseif ($_SESSION[status] < 1) {
    myErr("User ".$_SESSION['username']." does not have access to use this page.");
} elseif (strpos(SiteAdmin, $_SESSION['username'].",") === false) {
#if (strpos(SiteAdmin, $_SESSION['username']) === false) {
    myErr("Sorry, you are not authorized to access this facility.");
} else {
    print "Edit <a href='?action=admin&file=includes/config.php'>Configuration</a> &nbsp; 
        <a href='?action=admin&file=includes/LeftMenu.txt'>Left Menu</a>, &nbsp; 
        <a href='?action=admin&table=members'>Members</a>&nbsp; or &nbsp;
        <a href='?action=admin&table=access'>Access Rights</a><br/><br/>\n";
    foreach (scandir("./") as $k) { 
        if ((substr($k, -4) == ".txt") || (substr($k, -5) == ".html")) {
            print "<a href='?action=admin&file=$k'>".str_replace("_"," ",$k)."</a>, &nbsp; ";
        }
    }
}

# File edit 
if ($_POST['submit'] == "Save File") {
    file_put_contents($_POST['filename'], addslashes($_POST['fileText']));
} elseif (strlen($_GET['file']) > 1) {  # If you get here, the world is open to editing!!
    print "<form method='POST' action='?action=admin'>\n<br/><br/>\n";
    print "<textarea name='fileText' rows='25' cols='68'>";
    print stripslashes(file_get_contents($_GET['file']));
    print "</textarea>\n<br/><input type='submit' name='submit' value='Save File'>\n";
    print "<input type='hidden' name='filename' value='".$_GET['file']."'>\n</form>\n";
}


# Members table
if ($_GET['table'] == "members") {
    # POST elements that are not changed in the db. Assumes we know what columns are coming back... 
    $mbrIgnore = array("rowID","CreateDate","password","loginDateTime","loginIP","lastPageTime","submit");
    if ($_POST['submit'] == "Save User") {
        foreach ($_POST as $k => $v) {
            if (in_array($k, $mbrIgnore)) { continue; }
            $flds .= $k."='".(($k == "phone") ? stripPhone($v) : addslashes($v))."',";
        }
        myQuery("UPDATE members SET ".substr($flds,0,-1)." WHERE rowID=".$_POST['rowID']);
        $_SESSION['access'] = $_POST['mbrAccess'];
        $_GET['mid'] = "";  # cause display of member list
    }
    if ($_GET['mid'] > 0) {
        print "<form method='POST' action='".$_SERVER['REQUEST_URI']."'>\n";
        print "<input type='hidden' name='rowID' value='".$_GET['mid']."'>\n<table>\n";
        $res = myQuery("SELECT * FROM members WHERE rowID=".$_GET['mid'].";");
        foreach (mysql_fetch_assoc($res) as $k => $v) {
            if (in_array($k, $mbrIgnore)) { continue; }
            if ($k == "username") {
                print "<tr><td>$k</td><td><strong>$v</strong></td></tr>\n";
            } else {
                if ($k == "mbrAccess") {
                    print "<tr><td colspan='2'>$spc - $accPrompt</td></tr>\n"; }
                print "<tr><td>$k</td><td><input name='$k' value='$v' size='25' maxlength='255'></td></tr>\n";
            }
        }
        print "<tr><td> </td><td><input type='submit' name='submit' value='Save User'></td></tr>\n";
        print "</table>\n</form>\n";
    } elseif (substr($_POST['submit'], 0, 4) == "Del ") {
        # Delete a member!! Be careful - ask for confirmation? set status rather than delete?
    } else {
        $res = myQuery("SELECT * FROM members ORDER BY username");
        print "<form method='POST' action='".$_SERVER['REQUEST_URI']."'>";
        print "<table>\n";
        $y = array("fullname","email","phone","mbrAccess");
        print "<tr><th>username</th>";
        foreach ($y as $x) { print "<th>$x</th>"; }
        print "</tr>\n";
        while($row = mysql_fetch_array($res)) {
            $color = (($color == BAR_COLOR_1) ? BAR_COLOR_2 : BAR_COLOR_1);
            print "<tr style='background: $color;'>\n";
            print "   <td><a href='?action=admin&table=members&mid=".$row['rowID']."'>".$row['username']."</a></td>\n";
            foreach ($y as $x) { print "   <td>".$row[$x]."</td>\n"; }
            print "</tr>\n";
        }
        print "</table>\n</form>\n";
    }
}

# Access table
if ($_GET['table'] == "access") {
    if ($_POST['submit'] == "Save Table") {
        for ($i=0; $i<=count($_POST['accTarget']); $i++) {
            #if rowID > 0 and input cols blanked, delete else update all. If rowID < 1 and input cols blank, ignore, else insert
            if ($_POST['rowID'][$i] > 0) {
                if (strlen(trim($_POST['accLogin'][$i].$_POST['accView'][$i].$_POST['accEdit'][$i])) < 1) {
                    myQuery("DELETE FROM access WHERE rowID=".$_POST['rowID'][$i]);
                } else {
                    myQuery("UPDATE access SET accLogin='".addslashes($_POST['accLogin'][$i])."',accView='".addslashes($_POST['accView'][$i])."',accEdit='".addslashes($_POST['accEdit'][$i])."' WHERE rowID=".$_POST['rowID'][$i]);
                }
            } elseif (strlen(trim($_POST['accLogin'][$i].$_POST['accView'][$i].$_POST['accEdit'][$i])) > 0) {
                # accLogin could be only 1 byte so check for > 0 length on all 3 fields
                myQuery("INSERT INTO access (accTarget, accLogin, accView, accEdit, CreateDate, lastEditDate, lastEditUser) VALUES ('".addslashes($_POST['accTarget'][$i])."','".addslashes($_POST['accLogin'][$i])."','".addslashes($_POST['accView'][$i])."','".addslashes($_POST['accEdit'][$i])."',NOW(),NOW(),'".$_SESSION['username']."');");
            }
        }
    }
    # display a table. Leftmost col is dir/file list. 2nd col is accLogin. Rightmost 2 columns are text boxen for View & Edit username lists.
    # for each row get access record. display 3 cols if found  or blank input fields for new record.
    print "<form method='POST' action='".$_SERVER['REQUEST_URI']."'>\n<table>\n";
    print "<tr><th>Directory or File</th><th>Login</th><th>View</th><th>Edit</th></tr>\n";
    $color = "";
    foreach (scandir("./") as $dir) {     
        if (fooBankTo($dir, $dir, "") && (is_dir("./".$dir))) {
            # Only interested in processing the next level of dirs. After that is taken care of here.
            foreach (scandir($dir) as $d) { 
                if (($dir == "articles") && is_dir($dir."/".$d)) { continue; } # ignore photo dirs
                fooBankTo($d, $dir."/".$d, " &nbsp;|&nbsp; ");
            }
        }
    }
    print "<tr><td colspan='2'></td><td><input type='submit' name='submit' value='Save Table'></td></tr>\n";
    print "</table>\n</form>\n";
}


 
function fooBankTo ($d, $dir, $not) {
    global $color;
    $igNores = array("img", "includes","install","Thumbs.db", "captcha");
    $exTens  = array(".bak",".php",".gif",".jpg", ".png");
    if (substr($d, 0, 1) == ".")                       { return false; }
    if (in_array(substr(strtolower($d), -4), $exTens)) { return false; }
    if (in_array($dir, $igNores))                      { return false; }
    $color = (($color == BAR_COLOR_1) ? BAR_COLOR_2 : BAR_COLOR_1);
    print "<tr style='background: $color'><td>$not$d<input type='hidden' name='accTarget[]' value='$dir'></td>\n";
    $res = myQuery("SELECT rowID,accLogin,accView,accEdit FROM access WHERE accTarget='$dir'");
    $row = mysql_fetch_assoc($res);
    print "    <td><input type='hidden' name='rowID[]' value='".$row['rowID']."'><input name='accLogin[]' value='".$row['accLogin']."'></td>\n";
    print "    <td><input name='accView[]'  value='".$row['accView']."'></td>\n";
    print "    <td><input name='accEdit[]'  value='".$row['accEdit']."'></td>\n";
    print "</tr>\n";
    return true;
}

?>
