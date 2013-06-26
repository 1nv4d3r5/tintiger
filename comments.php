<?php
/* comments.php (voments.php) - flat, single-level 

Journal: J+entrydate -  '2009-08-27    Add  a comment|View 9999 comments ' w/ link s
   -  on a journal entry page, if no comments, display 'Add a comment' else display 'View comments' 
        with the number of comments as links
   - if Add  - display journal.entry.$entry_date text, then  open entry section
    - if View - display entry then related Comments in date order, then Add comment section.
 Photos: P+dir/filename - displayed with attached text below, then any comments, then Add comment section
             S, dirname -  sets, then any text, then cols of thumbs, then comments, then Add comment section
Guestbook: G,  display all entries in DESCending createDate sort order.

# table schema is described in /install/createAllTables.sql

Process:
If Journal or Pic, display entry or picture(s) then display existing comments, then display comment entry form
if Guestbook,  display the comment entry form first, then display a pagefull of comments in reverse order
    - comment entry form display is adjusted for the type of comment  - 'website' is only displayed for Guestbook
    - if the user is an admin, display a 'delete' and 'hide' or 'show' button on each comment.
Upon click of 'D|H/S' button, action is immediate, status of comment is set. 
    - deleted comments aren't actually deleted, they are set to never show again but are saved.
    - Hide and Show are mutually exclusive.
Preview displays the comment as it would appear if saved sans validation
Save validates the comment fields and CAPTCHA, creates a record for it and displays normally

    */
$type    = substr($_GET['key'], 0, 1);
$key     = urldecode(substr($_GET['key'], 1));
$spc     = "&nbsp; &nbsp;";
$rowCntr = 0;   # used by Admins for array index to RowID for Show/Hide/Delete comments buttons
    
# Journal cmts will have to get the entry for display. Others already displayed by caller
if ($type == "J") { 
    $res = myQuery("SELECT entry, CreateDate FROM entry WHERE CreateDate='$key';");
    $row = array_map(stripslashes, mysql_fetch_assoc($res));
    print "<strong>".substr($row['CreateDate'], 0, 10)."</strong><br/>";
    print "<br/>".$row['entry']."<br/><br/>";
}
print "<form action='".$_SERVER['REQUEST_URI']."' method='POST'>\n";

if ($_POST['cmtSubmit'] == "Preview") {
    dispOneComment($type, $_POST);
} elseif ($_POST['cmtSubmit'] == "Save") {
    $err = 0;           # Only Name, Comment text, and CAPTCHA  are required. 
    if (strlen($_POST['cmtName']) < 3) { $err++; myErr("Name is required."); }
    if (strlen($_POST['cmtText']) < 3) { $err++; myErr("Write something in the comments section."); }
    if (md5($_POST['captcha']) != $_POST['captchaword']) { $err++; myErr("CAPTCHA letters incorrect"); }
    if (($_POST['replyNotify'] == "Y") && !isEmail($_POST['cmtEmail'])) {
        $err++; myErr("Notification of replies requires an email address to notify."); }
    if ($err < 1) {
        foreach ($_POST as $k => $v) {
            if (in_array($k, array("rowIDs","cmtSubmit","captcha","captchaword"))) { continue; }
            $flds .= $k.",";
            $vals .= "'".addslashes($v)."',";
        }
        $flds .= "createDateTime,cmtType,cmtKey,cmtStatus";
        $vals .= "NOW(),'$type','".addslashes($key)."','S'";
        myQuery("INSERT INTO comments ($flds) VALUES ($vals);");
    }
} elseif ($_POST['cmtSubmit'] == "Cancel") {
    unset($_POST);
} elseif (($_SESSION['status'] == "1") && (in_array(substr($_POST['cmtSubmit'], 0, 4), array("dele","hide","show")))) {
    $sql = "";
    $ty  = substr($_POST['cmtSubmit'], 0, 5);
    $id  = substr($_POST['cmtSubmit'], 5);
    $dl  = $_POST['rowIDs'][$id];
    if ($ty == "dele_") {
        if (DELETE_NO_SAVE == "1") { 
            $sql = "DELETE FROM comments WHERE rowID=$dl;";
        } else {
            $sql = "UPDATE comments SET cmtStatus='D' WHERE rowID=$dl;";
        }
    } elseif ($ty == "show_") {
        $sql = "UPDATE comments SET cmtStatus='S' WHERE rowID=$dl;";
    } elseif ($ty == "hide_") {
        $sql = "UPDATE comments SET cmtStatus='H' WHERE rowID=$dl;";
    }
    if (strlen($sql) > 1) { myQuery($sql); }
}
if ($type == "G") {
    commentForm($_POST); displayComments($type, $key); 
} else {    
    displayComments($type, $key); commentForm($_POST);
}
print "</form>\n";


function displayComments ($type, $key) {
    global $rowCntr;
  # Guestbook doesn't use cmtKey column, all entries sorted by date descending.
    if ($type != "G") { $sg = " AND cmtKey='$key'"; }
    # Admins see all comments ('S'hown,'H'idden, etc) but others only see 'S'hown.
    if ($_SESSION['status'] != "1") { $sg .= " AND cmtStatus='S'"; }
    $sql = "SELECT * FROM comments WHERE cmtType='$type' AND cmtStatus != 'D' $sg ORDER BY createDateTime";
    if ($type == "G") { $sql .= " DESC"; }  # guestbook wants last LIFO, others use FIFO
    $res     = myQuery($sql);
    $numRows = mysql_num_rows($res);
    if ($numRows < 1) { return 0; }     # Wisdom: Don't do anything if there's nothing to do it with.
    # For Guestbook, $numRows is used as a counter for entry order. 
    print "<div class='comments'>\n";  
    while (($row = mysql_fetch_assoc($res)) !== false) { dispOneComment($type, $row, --$numRows); }
    print "</div id='comments'>\n";
    return $numRows;
}


function dispOneComment ($type, $row, $startNum=0) {
/*
----------------------------------------------------------------------------------------------------------------------------
|   # 9999   _name______________   _create_date___________                                                |
|       ___comment_text__________________________________________________        |
|       ________________________________________________________________       |
|                        . . .                                                                                                                                      |
|       _email_______(at)______(dot)_____       http://_____________________                     |
-----------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------------------------------------------------------------
|   ___subject (bold)_________________________________  . . .                                               |
|    by _name______________  on yy/mm/dd hh:ii                                                                              |
|       ___comment_text__________________________________________________         |
|       ________________________________________________________________        |
|                        . . .                                                                                                                                        |
-----------------------------------------------------------------------------------------------------------------------------
- non-hidden emails obfuscated, Hidden display '(Hidden)'                 
 */
    global $spc, $rowCntr;
    $row   = array_map(stripslashes, str_replace("\\","",$row)); # strip slashes from all elements in $row
    $email = (($row['hideEmail'] != "Y") ? obfuscateEmail($row['cmtEmail']) : "(hidden)");
    print "<div class='comment'>\n";
    if ($type == "G") {     # Guestbook gets differnt display format from all other comments
        print "# ".($startNum +1).$spc.$spc."<img src='img/name.gif'>: ".$row['cmtName']." on ". $row['createDateTime'];
        if (strlen($row['cmtEmail']) > 2) { 
            print "<br/>$spc<img src='img/email.gif'>: ".$email; }
        if (strlen($row['cmtWebsite']) > 0) {
            print $spc."<img src='img/home.gif'>: <a href='http://".$row['cmtWebsite']."' target='_blank'>".$row['cmtWebsite']."</a>\n"; }
    } else {
        if (strlen(trim($row['cmtSubject'])) > 1) { 
            print "<strong><i>".$row['cmtSubject']."</i></strong><br/>"; }
        print "$spc$spc by ".$row['cmtName'];
        if (($row['hideEmail'] != "Y") && (strlen($row['cmtEmail']) > 0)) { print " ($email)"; }
        print $spc." on ".$row['createDateTime'];
    }
    if ($_SESSION['status'] == "1") { # Allow Admins to Delete, Hide, or Show (unHide) any comment.
        print "&nbsp; &nbsp; <input type='submit' name='cmtSubmit' value='dele_$rowCntr'>";
        print " &nbsp; <input type='submit' name='cmtSubmit' value='";
        if ($row['cmtStatus'] == "H") { print "show"; } else { print "hide"; }
        print "_$rowCntr'>"; 
        print "<input type='hidden' name='rowIDs[]' value='".$row['rowID']."'>"; 
    }
    print "<p>".$row['cmtText']."</p>\n</div id='comment'>\n";
    $rowCntr++;    
}


function commentForm($row='', $legend='Add a Comment') {
/*
-----------------------------------------------------------------------
|       Name:   _________________________                |
|       Email:   _________________________   [] Hide  |
|       Website: ________________________   [] Hide  |      <- don't display for any but Geustbook
|      Subject: _________________________                |
|       ----------------------------------------------------------------   |
|       |                                                                                   |   |
|       |                                                                                   |   |
|       ----------------------------------------------------------------   |
|       Type the word :     'CAPTCHA'    __________           |        
|                                                                                                |
|               [Preview]       [Submit]        [Cancel]                     |
---------------------------------------------------------------------------
-  Preview, even if View mode, only display top text and Added comment, not other Comments
 - Submit - validate, INSERT a record
- Cancel - clear all vars then redisplay
*/
    global $type, $spc;
    $maxRows = 5;
    $maxCols = 65;
    $stSize  = 30;
    $captcha = showCaptchaImage();
    print "<br/><br/>\n<fieldset>\n<legend>&nbsp; $legend &nbsp;</legend>\n";
    print "<table>\n";
    print "<tr><td>Name:</td><td><input name='cmtName' size='$stSize' maxlength='256' value='".$row['cmtName']."'></td></tr>\n";
    print "<tr><td>Email:</td><td><input name='cmtEmail' size='$stSize' maxlength='256' value='".$row['cmtEmail']."'>$spc<input type='checkbox' name='hideEmail' value='Y' ".(($row['hideEmail'] == "Y") ? "checked" : "")."> hide</td></tr>\n";
    if ($type == "G") {
        print "<tr><td>Website:</td><td><input name='cmtWebsite' size='$stSize' maxlength='256' value='".$row['cmtWebsite']."'>$spc<input type='checkbox' name='hideWebsite' value='Y' ".(($row['hideWebsite'] == "Y") ? "checked" : "")."> hide</td></tr>\n";
    }
    print "<tr><td>Subject:</td><td><input name='cmtSubject' size='$stSize' maxlength='256' value='".$row['cmtSubject']."'></td></tr>\n";
    print "<tr><td colspan='2'><textarea name='cmtText' rows='$maxRows' cols='$maxCols'>".$row['cmtText']."</textarea></td></tr>\n";
    if ($type != "G") {
        print "<tr><td align='right'><input type='checkbox' name='replyNotify' value='Y' ".(($row['replyNotify'] == "Y") ? "checked" : "")."></td><td>$spc Notify me of followup comments via email</td></tr>\n";
    }
    print "<tr><td colspan='2'><br/></td></tr>\n";
    print "<tr><td colspan='2'>";
    print "Enter the letters: <input name='captcha' size='12'>$spc$captcha</td></tr>\n";
    print "<tr><td colspan='2'><br/></td></tr>\n";
    print "<tr><td colspan='2'>
        <input type='submit' name='cmtSubmit' value='Preview'>$spc$spc
        <input type='submit' name='cmtSubmit' value='Save'>$spc$spc
        <input type='submit' name='cmtSubmit' value='Cancel'>$spc$spc
        <input type='submit' name='foo' value='Click here if letters are unreadable'></td></tr>\n";
        # all the last does is load another CAPTCHA pic. All vars are passed in $_POST[]
    print "</table>\n</fieldset>\n";
}

?>
