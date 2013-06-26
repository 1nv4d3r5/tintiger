<?php
/* forums.php - discussion board. 
- requires login to post but anyone can read.  - users login in normal LeftMenu area, handled outside this facility.
    - Groups also determines who can view/post
    - Admins can view/kill any
    - Guests, non-members, get default Profile
- a single level forum is a comments thread. This can be attached to anything - photos, blogs, articles, applications, etc.
- pipes '|' are used as delimiters.

# There are 3 kinds of access rights - View, Post and Admin. members.frmCanView is pipe-delimited list of groups this user can see.
# members.frmCanPost is the list of groups this user can post to. members.frmCanView and members.frmCanPost of 'Admin' can do 
#       anything at any time

Main screen {
    # check entry type - Can be coming from initial entry, expand thread, save new subject, preview new post, save new post
    if action contains 'post' {
        # preview redraws screen and adds edit box contents in post format twixt prev post and edit box
        if savePost button clicked
            clean input of malicious code
            increment indentLevel
            store new forum_posts record
                if forum_subjects.start_post id is empty() this is 1st post
                    store forum_posts.rowID in forum_subjects start_rowID column
            # Once the new post has been saved with a parentID & indentLvl, we can drop any pointers to it. The main
            #       thread code will pick it up and display it in the appropriate place. Thread display should jump to
            #       parent of new post to restart display where user left off.
            # At this point, nothing has been displayed but URL used must contain elements to get back to thread 
            #       (<a name=888>)  # ?SID&threadID=999&postID=888&post=save
        else
            display post top bar and text.
            if editBox contains data, display it in post format
            display a <textarea>, mcedit (HTML rules, jscript editor, )
            display preview and save buttons, 
                Preview redraws same page 
                save returns to thread page (position screen to parent at top of page via '<a name=>'?)
        } else if action contains 'thread' {
            # display all posts in this forum, posts indented under post they comment,
            open forum_text table
            query for all records where forumID=passed order by rowID, createDateTime 
            foreach record
                if indentLevel > prev display <ul><li> 
                elseif iLvl < prev && >= 0 display </ul>
                else display <li> (when iLvl same as prev)
                previLvl = iLvl
                display <a name='rowID'> label for returns
                display bar w/ subject, username, info, create date of post
                display post text
                display 'reply' button, Admin 'kill' & 'mask' buttons (May fit in top bar)
                    - reply: goto Reply screen
                    - kill: deletes comment and children 
                    - mask: replaces text with 'this post removed' text & preserves children
         } 
         if action == save New Subject {
            look for malicious code, 
            store new forum_subject
            set action to empty()
         } 
         if action is empty() {
            # Display forum subject lines on one row with some stats
            # click a subject line to open that tree of posts on a new page
            if user is logged in, get the list of groups this user is a member of
            else groups is set to empty()
            open forum_subjects table
            if new Subject button clicked{
                # First subject row becomes line to create a new post.
                display <textarea>s for Subject and Description.
                instead of stats, display dropdown for access control
                    guest, loggedin/list of groups. multi select allowed
                    if guest and any other, available to all, if loggedin and groups, groups only
                display 'Save' button - URL = ?SID&action=saveNewSubject
         }    
          query for forums where groupID is null or groupID is in (memberGroupIDList) in newest first order
          foreach record
                display row of forum_subject columns, subject as link to thread screen, group, # posts
            if user allowed to create new forum (per forumCanCreate var)
                display link to 'start new subject',   
        }
    }
*/
myDebug($_GET,"GET:"); myDebug($_POST,"PST:");
# initialize variables
$loggedin  = true; $username = "kknerr"; $forumCanCreate = true;
$forumURL  = $PHP_SELF."?".SID."&action=forums";
$sbjFields = "txtSubject,txtDescription,start_rowID,createDateTime,createUsername,commentsSw,   
    publicSw,frStatus,groupID";
$usrCanViewGrps = $usrCanPostGrps = array(0);  # 0 is Guest; default
$prevIndentLvl = 0;

# Build the list of groups this user is a member of. pipe delimited list
if ($loggedin) {         
    $res = myQuery("SELECT frmCanView, frmCanPost FROM members WHERE username='$username'");
    if (mysql_num_rows($res) > 0) {
        $row    = mysql_fetch_assoc($res);
        $usrCanViewGrps = explode("|", $row['frmCanView']);
        $usrCanPostGrps = explode("|", $row['frmCanPost']);
        # ignore Admin rights. If 'a' then this user should get everything.
        if (strpos($usrCanViewGrps, "a") === false) {
            foreach ($usrCanViewGrps as $x) { $groups .= ",'".$x."'"; }
        } else { $groups = "a"; }
        $groups = substr($groups, 1);   # strip leading comma
    }
}
# Build groups menu html code
$rc = myQuery("SELECT groupID, grName FROM forum_groups WHERE grStatus='1'");
$selectGroup = "<select name='access[]' size='3' multiple>";
while ($r = mysql_fetch_array($rc)) { 
    $selectGroup .= "<option value='".$r['groupID']."'>".$r['grName']."</option>"; 
}
$selectGroup .= "</select>";

# Store New Subject if it's clear of malicious code...
if ($_POST['saveNewSubject'] == "saveNewSubject") {
    if (strlen($_POST['subject']) > 0) {
        # str_replace() corrects CRs on screen vs <textarea>
        $sql = "INSERT INTO forum_subjects ($sbjFields) VALUES ('".addslashes($_POST['subject'])."','".addslashes(str_replace("\n","<br/>", $_POST['descript']))."', 0, NOW(), '$username'";
        $sql .= ",'".$_POST['Comments']."','".$_POST['Public']."','".$_POST['Status']."'";
        $sql .= ",'".implode("|".$_POST['access'])."'";
        #myDebug($sql,"SQL:");
        myQuery($sql.")");
    }
}

print "<form action='$forumURL' method='POST'>\n";
if (!empty($_GET['postid'])) {
    $res = myQuery("SELECT * FROM forum_posts WHERE rowID=".$_GET['postid']);
    showPost(mysql_fetch_assoc($res));
    createPost();
} elseif (!empty($_GET['threadid'])) {
    # get text of subject to display at the top of the page. Posts (comments) follow.
    # Each post has subject, username, date of post, 'reply' button, then post text.
    if (!is_numeric($_GET['threadid'])) { myDie ("Non numeric Thread ID!"); }
    $res   = myQuery("SELECT * FROM forum_subjects WHERE forumID=".$_GET['threadid'].";");
    $sbj   = mysql_fetch_array($res);
    $khref = "<a href='$forumURL&threadid=".$_GET['threadid'];
    # 
    if ($_POST['submit'] == 'Submit') {
        $rowID = savePost($_POST);
        # if first post, store $rowid into forum_subject, else store $rowid in forum_posts.
        if ($sbj['start_rowid'] == 0) {
            myQuery("UPDATE forum_subjects SET start_rowID=$rowid WHERE forumID=".$_GET['threadid'].";");
        }
    }
    print "<div id='forum_subject'>\n<h2>".$sbj['txtSubject']."</h2><br/>\n";
    print "<p>".$sbj['txtDescription']."</p>\n";
    print "</div id='forum_subject'>\n";
    if ($_POST['submit'] == 'Preview') {
        showPost($_POST);
    }
    
    if ($sbj['parent_rowID'] == 0) {          # no rows means new forum, no posts yet. Open reply window.
        createPost($sbj['txtSubject'], $sbj['txtDescription']);
    } else {
        print $khref."'>Reply</a>\n";
        $res = myQuery("SELECT * FROM forum_posts WHERE forumID=".$_GET['threadid']." 
            ORDER BY createDateTime;");
        while ($row = mysql_fetch_assoc($res)) {
            showPost($row);
        }
    }    
}

if (empty($_GET['threadid'])) {
    # Display forum subject lines on one row with some stats. Click a subject line to open that tree of posts on a new page
    print "<table class='forums'>\n";
    if ($_GET['button'] == 'newSubject') {
        # First subject row becomes line to create a new post. Build list of groups, display input fields and access menu
        print "<tr class='forumSbj'>\n";
        print "    <td><input type='text' name='subject' size='45'  maxlength='255'>\n";
        print "<br/><textarea name='descript' cols='40' rows='4'></textarea></td>\n";
        print "    <td valign='top'>$selectGroup</td>\n";
        print "    <td>".kradio("Comments")."<br/>".kradio("Public")."<br/>".
            kradio("Status","open","closed")."";
        print "<br/><br/><input type='submit' name='saveNewSubject' value='saveNewSubject'></td>\n";
        print "</tr>\n";
    } else { 
        $a = "SELECT * FROM forum_subjects ";
        $b = " ORDER BY createDateTime DESC";
        if ($usrCanViewGrps == "a") { $w = "WHERE groupID IN (NULL, $groups)"; }
        $res = myQuery($a . $w . $b);
        print "<tr class='forumHdr'>\n    <td>Subject</td>\n    <td>Create Date</td>\n</tr>\n";
        while ($row = mysql_fetch_assoc($res)) {
            print "<tr class='forumSbj'>\n";
            print "    <td><a href='$PHP_SELF?".SID."&action=forums&threadid=".$row['forumID']."'>";
            print $row['txtSubject']."</a><br/>".$row['txtDescription']."</td>\n";
            print "    <td valign='top'>".substr($row['createDateTime'], 0, 10)."</td>\n";
            # Admins can change staus, group(s), 
            print "    <td> </td>\n";
            print "</tr>\n";
        }
    }
    print "</table>\n";
    if ($loggedin && $forumCanCreate) {
        print "<p><a href='$forumURL&button=newSubject'>Click here</a> to create a new forum.</p>";
    }
}
print "</form>\n";
# end of mainline code

    
function createPost ($sbj='', $pst='', $type='') {
    print "<div id='createPost'>\n";
    print "<table>\n";
    print "<tr><td>Subject:</td>\n    <td><input type='text' name='inpSubject' size='45' maxlength='256'></td></tr>\n";
    print "<tr><td valign='top'>Description:</td>\n";
    print "    <td><textarea name='txtPost' cols='45' rows='10'></textarea></td></tr>\n";
    print "<tr><td> </td>\n    <td>";
    print "<input type='submit' name='submit' value='Preview'> &nbsp; &nbsp;\n";
    print "        <input type='submit' name='submit' value='Submit'></td></tr>\n";
    print "</table>\n";
    print "<input type='hidden' name='threadid' value='".$_GET['threadid']."'>\n";
    print "</div id='createPost'>\n";
}


function showPost ($row) {
    global $prevIndentLvl, $khref;
    if ($row['indentLevel'] > $prevIndentLvl) {
        print "<ul><li>\n";
    } elseif ($row['indentLevel'] < $prevIndentLvl) {
        print "</ul>\n";
    } elseif ($row['indentLevel'] == $prevIndentLvl) {
        print "<li>\n";
    }
    $prevIndentLvl = $row['indentLevel'];
    print "<strong>".$row['txtSubject']."</strong> &nbsp; - &nbsp; ";
    print $row['createDateTime']." &nbsp; ".$row['createUsername']." &nbsp; ";
    print $khref."&postid=".$row['rowID']."'>Reply</a>\n";
    print "<p>".$row['txtPost']."</p>\n";
}


function savePost($flds) {
    # check for malicious code inthe txt fields. THen save the data to a new record, get the forum_posts rowID and store this in the parent rec.
    # If this is the 1st post to this forum, store this recno in the forum_subjects rec and leave forum_posts parent_rowID == 0.
    $rowid = 0;
    if ((strlen($_POST['inpSubject']) > 0) && (strlen($_POST['txtPost']) > 0)) {
        $new = myQuery("INSERT INTO forum_posts (forumID,indentLevel, txtSubject,txtPost, 
            parent_rowID,createDateTime,createUsername) 
            VALUES (".$_POST['threadid'].",$identLvl,'".
            addslashes($_POST['inpSubject'])."','".
            addslashes($_POST['txtPost'])."',0,NOW(),$loggedin);");
        $rowid = mysql_insert_id($new);
    } 
    return $rowid;
}

function kradio ($prompt, $frstChoice="Y", $scndChoice="N") {
   $rc  = "$prompt: <input type='radio' name='$prompt' value='$frstChoice' checked='checked'> $frstChoice";
   return $rc . " &nbsp; <input type='radio' name='$prompt' value='$scndChoice'> $scndChoice";
}
    
/* files affected by this script:
config.txt:
    forumCanPost=guest/loggedin/group only
    forumCanCreate=guest/loggedin/group only
    forumDefaultFirstLevel=0
 
ALTER TABLE members 
    ADD frmGroups TEXT NOT NULL COMMENT 'pipe delimited string of mbrGroupIDs this user is a member of',
    ADD frmFormat CHAR( 1 ) NOT NULL COMMENT 'flat, threaded',
    ADD frmCanView VARCHAR(255) NOT NULL DEFAULT '0' COMMENT 'list of groups this user can View',
    ADD frmCanPost VARCHAR(255) NOT NULL DEFAULT '0' COMMENT 'list of Groups this user can Post to',
    ADD frmPageLength` INT( 11 ) NOT NULL COMMENT '10, 25, 50, 100, etc. comments per page';
 
CREATE TABLE forum_subjects (
    forumID int(11) NOT NULL auto_increment COMMENT '(rowID) identifies forum',
    txtSubject varchar(255) NOT NULL,
    txtDescription text NOT NULL,
    start_rowID int(11) NOT NULL DEFAULT '0' COMMENT 'forums_posts rowID of 1st post',
    createDateTime datetime NOT NULL,
    createUsername varchar(32) NOT NULL,
    commentsSw char(1) NOT NULL COMMENT 'Y/N are further texts allowed?',
    threadsSw char(1) NOT NULL COMMENT 'Y/N, N == single layer (comments), Y == forum',
    publicSw char(1) NOT NULL COMMENT 'Y/N user must login to create a comment',
    groupID varchar(255) NOT NULL COMMENT 'what group of memberIDs can see this post',
    frStatus char(1) NOT NULL COMMENT 'closed / open / ',
    PRIMARY KEY  (forumID)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 
    COMMENT 'topics list';

CREATE TABLE forum_posts (
    rowID int(11) NOT NULL auto_increment,
    forumID int(11) NOT NULL COMMENT 'groups all posts for a single forum',
    indentLevel int(11) NOT NULL,
    txtSubject varchar(255) NOT NULL,
    txtPost text NOT NULL,
    parent_rowID int(11) NOT NULL COMMENT 'rowID of parent text or 0 if first post of thread',
    createDateTime datetime NOT NULL COMMENT 'sequence when multiple comments to any single parent',
    createUsername varchar(32) NOT NULL,
    PRIMARY KEY  (rowID)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 
    COMMENT 'all text of the posts';

INSERT INTO `forum_posts` ( forumID, indentLevel, txtSubject, txtPost, parent_rowID, createDateTime, createUsername)
    VALUES (0, 0, 'dummy rec', 'forum_subjects needs 1st post rowID > 0.', 0, NOW(), 'install');

CREATE TABLE forum_groups (
    groupID int(11) NOT NULL auto_increment,
    grName varchar(255) NOT NULL,
    createDateTime datetime NOT NULL,
    grStatus char(1) NOT NULL COMMENT 'active / inactive switch',
    grOwner varchar(255) NOT NULL COMMENT 'memberID(s) in control of this group',
    PRIMARY KEY  (groupID)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 
    COMMENT 'can restrict threads to set of users. ex. BoD discussions that members can't see';
      
 */
 ?> 

   
