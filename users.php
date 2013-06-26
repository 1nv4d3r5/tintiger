<?php
# lusers.php - handle login, logout, registration, profile edit functions.
/*
GET action = 
    login - go to login screen
    logout - logout, display message
    register  - goto blank profile screen 
    edit - edit profile
    
POST submit = 
    login - validate username, password, set session
    register - validate blank profile, create user record
    email psw - gen new psw, store, mail to, return to login screen
    check username  - check for username uniqness, return to profile screen
    edit profile - retrieve username record, display profile screen (w/o username)
    save profile  - validate all fields, update username record w/ all fields except username
  
- register - open screen to display all profile fields - validate and save new|changed user info
- change profile element - display all elements at once. 
- email password - no clear psw so gen new one and send to registered email
    - email required to register account
- check for username uniqueness
- login
- logout - 

# screens:
- login - only username and password fields, buttons: create account, email password, login
- profile - all fields displayed (dual psw), buttons: Register user, Save changes, 
- logout  - 'thanks for visiting message

# login | create links in header - 

? ? ? 
loginTime - logout after 18hrs anyway, (shopping cart goes to wishlist(prices removed))  
loginIP - destroy session if change
lastPageTime - log out if > 1 hr
*/
soothGet($_GET['action']);
$minUsernameLength = 3;      # Minimum username character length
$edProfPrmpt = "Save Yourself";
print "<div class='login'>\n";

# These first 3 ifs all use the Login screen.
if ($_GET['action'] == "login") {
    if ($_POST['submit'] == "Email password") {   # if found, gen new psw and replace. email this to username's email 
        $res = myQuery("SELECT email, fullname FROM members WHERE username='".$_POST['username']."';");
        if (mysql_num_rows($res) > 0) {
            $row = mysql_fetch_assoc($res);
            $psw = generatePassword(8);
            myQuery("UPDATE members SET password='".md5($psw)."' WHERE username='".$_POST['username']."';");
            $msg = wordwrap("Dear ".$row['fullname'].",\n\nUsername: ".$_POST['username']."\n\nPassword: $psw\n\n Enter password exactly as displayed. You can change it after login from the Profile screen.\n", 70);
            $hdr = 'From: '.ADMIN_EMAIL."\r\n".'Reply-To: '.ADMIN_EMAIL."\r\n".'X-Mailer: PHP/'.phpversion();
            ini_set("SMTP", SMTP_SERVER );
            ini_set('sendmail_from', ADMIN_EMAIL);
            mail($row['email'], "Password Reset", $msg, $hdr);
            #myDebug($row['email']."|Password Reset<br/> $msg,<br/> $hdr");
        } else {
            myErr("Username '".$_POST['username']."' not found.");
        }
        loginScreen();
    } elseif ($_POST['submit'] == "Login") {
        if ((strlen($_POST['username']) > 1) && (strlen($_POST['password']) > 1)) {
            $res = myQuery("SELECT username, password, mbrStatus, mbrAccess FROM members WHERE username='".$_POST['username']."';");
            if (mysql_num_rows($res) < 1) {
                myErr("Username '".$_POST['username']."' not found.");
                $_POST['password'] = "";
                loginScreen();
            } else {
                $row = mysql_fetch_assoc($res);
                if ($row['password'] == md5($_POST['password'])) { # Successful login
                    $_SESSION['username'] = $_POST['username'];
                    $_SESSION['status']   = $row['mbrStatus'];
                    $_SESSION['access']   = $row['mbrAccess'];
                    myMsg("Thank you for logging in, ".$_SESSION['username']."!");
                    myQuery("UPDATE members SET loginDateTime=NOW() WHERE username='".$_SESSION['username']."';");
                } else {
                    myErr("Incorrect password for ".$_POST['username'].".");
                    $_POST['password'] = "";
                }
            }
        } else {
            if (strlen($_POST['username']) < 2) { myErr("Username missing."); }
            if (strlen($_POST['password']) < 2) { myErr("Password missing."); }
            loginScreen();
        } 
    } else {
        loginScreen();
    }
   
# The following ifs use the Profile screen    
} elseif ($_GET['action'] == "register") { 
    if (ALLOW_REGISTRATION != 1) { myDie("Registration not available. Contact webmaster."); }
    if ($_POST['submit'] == "check for availability") {
        # check for quotes in the username field. None are ever allowed. 
        if ((strpos($_POST['username'], "'") !== false) || (strpos($_POST['username'], "\"") !== false)) {
            myErr("Username can not contain quotes of either kind.");
            showProfile("re");
        } elseif (strlen($_POST['username']) > $unLength) {
            $res = myQuery("SELECT username FROM members WHERE username='".$_POST['username']."';");
            if (mysql_num_rows($res) > 0) {
                myErr("Username '".$_POST['username']."' already used. Try another.");
            } else {
                myMsg("Username not found. You can use it.");
            }
            showProfile("re");
        } else {
            myErr("Username missing or less than $minUsernameLength characters long.");
            showProfile("re");
        } 
    } elseif (($_POST['submit'] == "Register") && validateProfile()) {
        myMsg("Thank you for signing up, ".$_POST['fullname']."!");
        $_GET['action'] = "login";
        loginScreen("n");   # Leam doesn't want to see 'create account' link after registration...
    } else {
        showProfile("re");
    }
} elseif ($_GET['action'] == "editprofile") {
    if (($_POST['submit'] == $edProfPrmpt) && validateProfile()) {
        # Fullname, username, psw, email are required. All else optional.  If password has data, psw2 must be the same. 
        #   Req'd for new, optional for change. username can not change once set. uniqueness required.
        myMsg("Update successful!"); 
    } else {
        $res = myQuery("SELECT * FROM members WHERE username='".$_SESSION['username']."';");
        foreach (mysql_fetch_array($res) as $k => $v) { $_POST[$k] = stripslashes($v); }
        $_POST['password'] = '';
        showProfile("ep");
    }
    
# Logout has no screen. It only displays a success message.
} elseif ($_GET['action'] == "logout") {
    myQuery("UPDATE members SET lastPageTime=NOW() WHERE username='".$_SESSION['username']."';");
    myMsg("Thanks for visiting, ".$_SESSION['username'].". Ya'll come back soon, ya' hear!");
    session_destroy();
}
print "</div id='login'>\n";


function showProfile ($parm1='re') { # All fields displayed for entry
    global $edProfPrmpt;
    $redStar = "<font color='red'>*</font>";
    print "<form action='?action=".$_GET['action']."' method='POST'>\n<table>\n";
    if ($parm1 == "re") {
        print "<tr><td> </td><td><br/>Fields marked with $redStar are required.<br/></td></tr>\n";
    }
    print "<tr><td><br/><br/></td></tr>\n";
    print "<tr><td>Full Name(s)";
    if ($parm1 == "re") { print " $redStar"; }
    print ": </td><td colspan='2'><input name='fullname' size='30' maxlength='255' value='".$_POST['fullname']."'></td></tr>\n";
    print "<tr><td>Address: </td><td colspan='2'><input name='address' size='48' maxlength='255' value='".$_POST['address']."'></td></tr>\n";
    print "<tr><td>City: </td><td colspan='2'><input name='city' size='48' maxlength='255' value='".$_POST['city']."'></td></tr>\n";
    print "<tr><td>State, Country, Zip: </td><td  colspan='2'>
        <input name='state' size='3' maxlength='255' value='".$_POST['state']."'> &nbsp; 
        <input name='country' size='12' maxlength='255' value='".$_POST['country']."'> &nbsp;
        <input name='zip' size='9' maxlength='255' value='".$_POST['zip']."'></td></tr>\n";
    print "<tr><td>Phone: </td><td><input name='phone' size='12' maxlength='255' value='";
    if (strlen($_POST['phone']) > 1) { print formatPhone($_POST['phone']); }
    print "'></td></tr>\n";
    print "<tr><td>Email Address";
    if ($parm1 == "re") { print " $redStar"; }
    print ": </td><td colspan='2'><input name='email' size='30' maxlength='255' value='".$_POST['email']."'></td></tr>\n";
    print "<tr><td>Website: </td><td colspan='2'><input name='website' size='48' maxlength='255' value='".$_POST['website']."'></td></tr>\n";
    print "<tr><td>Boat Name: </td><td colspan='2'><input name='boatname' size='48' maxlength='255' value='".$_POST['boatname']."'></td></tr>\n";
    print "<tr><td>Hailing Port: </td><td colspan='2'><input name='hailingport' size='48' maxlength='255' value='".$_POST['hailingport']."'></td></tr>\n";
    print "<tr><td><br/><br/></td></tr>\n";
    if ($parm1 != "ep") {      # Can't change uid once set in create account
        print "<tr><td>Username";
        if ($parm1 == "re") { print " $redStar"; }
        print ": </td><td><input name='username' size='14' value='".$_POST['username']."'></td>"; 
        # gotta have this one in here... Only new accounts need a unique username - it's the record key.
        if (($_POST['submit'] == "Create Account") || ($_GET['action'] == "register")) {
            print "<td><input type='submit' name='submit' value='check for availability'></td>";
        }
        print "</tr>\n";
    }
    print "<tr><td>Password";
    if ($parm1 == "re") { print " $redStar"; }
    print ": </td>
        <td><input name='password' size='14' value='".$_POST['password']."' type='password'></td>
        <td>again: <input name='password2' size='14' value='".$_POST['password2']."' type='password'> </td></tr>\n";
    if ($parm1 == "ep") {
        print "<tr><td>&nbsp;</td><td><input type='submit' name='submit' value='$edProfPrmpt'></td></tr>\n"; }
    if ($parm1 == "re") {
        print "<tr><td><br/><br/></td></tr>\n";
        print "<tr><td>Enter the letters:</td><td colspan='2'><input name='captcha' size='12'>&nbsp; &nbsp;" . showCaptchaImage(). "</td></tr>\n";
        print "<tr><td>&nbsp;</td><td><input type='submit' name='submit' value='Register'></td><td>$spc <input type='submit' name='foo' value='Click here if letters are unreadable'></td></tr>\n";
    }
    print "</table>\n</form>\n";
}


function validateProfile () {
    global $unLength;
    $err  = 0; 
    $flds = $vals = $updt = "";     # build both UPDATE and INSERT sqls. Figger out which to use later
    if (strlen($_POST['fullname']) < 2) { 
        myErr("Full Name field can not be blank."); $err++; }
    # check for quotes in the username field. None are ever allowed. 
    if ((strpos($_POST['username'], "'") !== false) || (strpos($_POST['username'], "\"") !== false)) {
        myErr("Username can not contain quotes of either kind."); $err++; }
    if ($_POST['submit'] == "Register") {
        if (strlen($_POST['username']) <= $unLength) {
            myErr("Username must be at least $unLength characters."); $err++; 
        } else {
            $res = myQuery("SELECT username FROM members WHERE username='".$_POST['username']."';");
            if (mysql_num_rows($res) > 0) {
                myErr("Username already assigned. Try another."); $err++; 
            } else {
                $updt .= "username='".addslashes($_POST['username'])."',";
                $flds .= "username,"; $vals = "'".addslashes($_POST['username'])."',";
            }
        }
    }
    if (strlen($_POST['password']) > 0) {
        if ($_POST['password'] != $_POST['password2']) {
            myErr("Passwords fields must match to change password.");
            $err++;
        } else {
            $updt .= "password='".md5($_POST['password'])."',";
            $flds .= "password,"; $vals .= "'".md5($_POST['password'])."',";
        }
    } elseif ($_GET['action'] == "register") {
        myErr("Password required. Twice."); $err++;
    }
    if (isset($_POST['captchaword'])) {
        if (strlen($_POST['captcha']) < 1) {
            myErr("CAPTCHA letters/numbers required."); $err++;
        } elseif (md5($_POST['captcha']) != $_POST['captchaword']) {
            myErr("CAPTCHA entry incorrect."); $err++; 
        }
    }
    if ($err < 1) {
        $fields = "fullname,address,city,state,country,zip,email,website,boatname,hailingport";
        foreach (explode(",",$fields) as $k) { 
            $updt .= "$k='".addslashes($_POST[$k])."',"; 
            $flds .= "$k,"; $vals .= "'".addslashes($_POST[$k])."',"; 
        }
        $updt .= "phone='".stripPhone($_POST['phone'])."'";
        $flds .= "phone"; $vals .= "'".stripPhone($_POST['phone'])."'";
        if ($_POST['submit'] == "Save Changes") {
            $sql   = "UPDATE members SET $updt WHERE username='".$_POST['username']."';";
        } else {        # [submit] => Register 
            $sql   = "INSERT INTO members ($flds, CreateDate, mbrStatus) VALUES ($vals, NOW(),'0');";
        }
        myQuery($sql);
    }
    return (($err > 0) ? false : true);
}


function loginScreen ($showCreate='y') {
    $_POST['password'] = "";
    print "<form action='?action=".$_GET['action']."' method='POST'>\n<table>\n";
    print "<tr><td><br/><br/></td></tr>\n";
    print "<tr><td>Username: </td><td><input name='username' size='16' value='".$_POST['username']."'></td></tr>\n";
    print "<tr><td>Password: </td><td><input name='password' size='10' type='password'></td></tr>\n";
    print "<tr><td>&nbsp;</td><td><input type='submit' name='submit' value='Login'></td>\n";
    print "    <td><input type='submit' name='submit' value='Email password'></td></tr>\n";
    if ($showCreate == "y") {
        print "<tr><td></td><td><a href='".WhichLocal."?action=register'>Create an account</a></tr>\n";
    }
    print "</table>\n</form>\n";
    return true;
}
    
?>
