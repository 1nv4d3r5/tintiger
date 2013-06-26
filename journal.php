<?php 
/* journal.php

k.k 2010-3-29 add a <private> tag that when encountered, don't display contents of an entry to anyone but the 'jnlEditor='. 
        This is stuff I want to remember but there are too many folks reading this thing now-a-days to make it public.
 Also, add a link to each old entry to allow editor to edit it. This shortcuts the list and causes only that entry to come up in the 
        <textarea> box w/ the save button.
 - Use CreateDate in the Edit link on the main page cause rowID is a switch to cause saving of the entry.
 k.k 10-4-18 did I fix the NExt button fubar? 
*/
$spc = "&nbsp; &nbsp;";

# Verify/scrub all possible GET vars used by this script.
if (strlen($_GET['entry']) > 0) {
    if ((substr($_GET['entry'],4,1).substr($_GET['entry'],7,1) != "--") 
        || (strlen($_GET['entry']) != 10)) { myPowned(); }
} elseif (strlen($_GET['prevdate']) > 0) {
    if ((substr($_GET['prevdate'],4,1).substr($_GET['prevdate'],7,1) != "--") 
        || (strlen($_GET['prevdate']) != 10)) { myPowned();  }
}

if ($_POST['journal'] == "Save") {
    if (isset($_POST['rowID'])) { # record already exists so UPDT instead of INSERT
        myQuery("UPDATE entry SET entry='".addslashes($_POST['entry'])."' WHERE ID=".$_POST['rowID'].";");
    } else {
        myQuery("INSERT INTO entry (CreateDate,UserID,Entry) VALUES (NOW(),'".jnlEditor."','".addslashes($_POST['entry'])."');");
    }
}
print "<form action='".WhichLocal."/' method='POST'>\n<br />";
print "<div id='blog'>\n";

      # display a page of entries. If Editor, also display edit box for today.

      $contentLength = 0;
# journal editor gets a screen of text when logged in. THis is current days entry. Old entries can not be editted.
if ($_SESSION['username'] == jnlEditor) {
    # If this is the editor designate signed in, some entry is going to ber editted. If no 'entry' date is oassed, use $today.
    # Go look for that entry. If exists, load it first and set hidden rowID for return.
    $date = ((strlen($_GET['entry']) > 1) ? $_GET['entry'] : date("Y-m-d"));
    $res  = myQuery("SELECT entry,ID,CreateDate FROM entry WHERE CreateDate='$date';");
    if (mysql_num_rows($res) > 0) { 
        $row = mysql_fetch_assoc($res); 
        # rowID shows which row to update and indicates in Save rtn that there is already a record to save to.
        print "<input type='hidden' name='rowID' value='".$row['ID']."'>\n";
    }
    $row['entry'] .= "\n".((strlen($row['entry']) > 1) ? "<p>" : "")."- ".date("H:i")."\n<br/>";
    print "<textarea name='entry' rows='24' cols='72'>".stripslashes($row['entry'])."</textarea>\n";
    print "<br/> &nbsp; <input type='submit' name='journal' value='Save'>\n";
}
$firstEntry = ((strlen($_GET['entry']) > 0) ? $_GET['entry'] : $today); # 'Top' or nothing will use $today.
$res = myQuery("SELECT * FROM entry WHERE CreateDate <= '$firstEntry' ORDER BY CreateDate DESC");
while ($row = mysql_fetch_assoc($res)) {
    $cntr = 0;
    $cmtr = myQuery("SELECT COUNT(rowID) AS ctr FROM comments WHERE cmtType='J' AND cmtKey='".$row['CreateDate']."' AND cmtStatus != 'D'".(($_SESSION['status'] != "1") ? " AND cmtStatus='S'" : ""));
    $cmt  = mysql_fetch_assoc($cmtr);
    $foo  = "<a href='?action=comments&key=J".urlencode($row['CreateDate'])."'><img style='border: 0;' src='";
    print "<div class='jdate'><strong>".substr($row['CreateDate'], 0, 10)."</strong>$spc$spc\n";
    if ($cmt['ctr'] < 1) {
        print $foo."img/writing_pen.png'> Add</a> a comment";
    } else {
        print $foo."img/comment_balloon.png'> View</a> ".$cmt['ctr']." comments";
    }
    if ($_SESSION['username'] == jnlEditor) { # allow edit of older entries....
        print " &nbsp; <a href='?action=journal&entry=".$row['CreateDate']."'>Edit</a>";
        $row['Entry'] = str_replace("<private>",  "<<< ", $row['Entry']);
        $row['Entry'] = str_replace("</private>", " >>>", $row['Entry']);
    } else {
        # parse <private> section out of entry unless this is the journal editor. $a holds everything up to 1st tag. $b holds
        #       everything after 1st closing tag. Join the 2 parts. Rinse and repeat until all occurrances removed. Seems to work
        #       even when there is no closing tag.
        while (strpos($row['Entry'], "<private>") !== false) {
            $a = substr($row['Entry'], 0, strpos($row['Entry'], "<private>"));
            if (strpos($row['Entry'], "</private>") !== false) {
                $b = substr($row['Entry'], strpos($row['Entry'], "</private>") +10);
            } else {
                $b="";
            }
            $row['Entry'] = $a . $b;
        } 
    }
    print "</div>\n";
    print "<div id='entry'>\n".stripslashes($row['Entry'])."</div>\n<br />\n";
    $contentLength = $contentLength + strlen($row['Entry']);
    if ($contentLength > $maxPage) { break; }

}
print "<strong>\n";
if ($firstEntry != $today) {
    print "<a href='?action=journal&entry=".$_GET['prevdate']."'><input type='button' value='Prev'></a>$spc\n";
    print "<a href='?action=journal'><input type='button' value='Home'></a>$spc\n";
}
$row = mysql_fetch_assoc($res); 
if ($row['CreateDate'] != "") {
    print "<a href='?action=journal&entry=".$row['CreateDate']."&prevdate=".substr($firstEntry,0,10)."'><input type='button' value='Next'></a>\n";
}
print "\n</strong>\n</div id='blog'>\n";
print "</form>\n</td>\n";
?>
