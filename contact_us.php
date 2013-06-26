<?php
/* contact_us.php - form for site visitors to use to send us an email for any purpose. Uses CAPTCHA.
*/
$type    = substr(soothGet($_GET['key']), 0, 1);
$key     = urldecode(substr($_GET['key'], 1));
$defSbj  = "Site contact form";

print "<br/><br/>\n";
include("contact_info.html");
print "<br/><br/>\n";

if ($_POST['submit'] == "Send") {
    $err = 0;           # Only Name, Comment text, and CAPTCHA  are required. 
    if (strlen($_POST['cmtName']) < 3)  { $err++; myErr("A Name is required."); }
    if (strlen($_POST['cmtEmail']) < 3) { $err++; myErr("An Email address is required."); }
    if (!isEmail($_POST['cmtEmail']))   { $err++; myErr("Email addres doesn't appear to be valid."); }
    if (strlen($_POST['cmtText']) < 3)  { $err++; myErr("Write something in the comments section."); }
    if (md5($_POST['captcha']) != $_POST['captchaword']) { $err++; myErr("CAPTCHA letters incorrect"); }
    if ($err < 1) {
        if (strlen($_POST['cmtSubject']) < 1) { $_POST['cmtSubject'] = $defSbj; }
        $msg = wordwrap($_POST['cmtText'], 70);
        $hdr = 'From: '.$_POST['cmtEmail']."\r\n".'Reply-To: '.adminEmail."\r\n".'X-Mailer: PHP/'.phpversion();
        ini_set("SMTP", SMTP_SERVER );
        ini_set('sendmail_from', cuEmail);
        mail(adminEmail, $_POST['cmtSubject'], $msg, $hdr);
        myMsg(cuMessage);
        if ($_POST['sndCopy'] == "Y") {
            $hdr = 'From: '.adminEmail."\r\n".'X-Mailer: PHP/'.phpversion();
            $msg = "Copy of text submitted to ". WhichLocal.":\n\n".$msg;
            mail($_POST['cmtEmail'], $_POST['cmtSubject'], $msg, $hdr); 
        }
    } else {
        contactForm();
    }
} else {
    contactForm();
}    


function contactForm () {
    global $_POST;
    $maxRows = 5;
    $maxCols = 65;
    $stSize  = 30;
    print "<form action='?page=contact_us' method='POST'>\n";
    print "<fieldset><legend>&nbsp; Contact Form &nbsp;</legend>\n";
    print "<table>\n";
    print "<tr><td style='text-align: right;'>Name: </td>";
    print "<td><input name='cmtName' size='$stSize' maxlength='256' value='".$_POST['cmtName']."'>";
    print "</td></tr>\n<tr><td style='text-align: right;'>Email: </td>";
    print "<td><input name='cmtEmail' size='$stSize' maxlength='256' value='".$_POST['cmtEmail'].
        "'>"; 
        print "<tr><td style='text-align: right;'>Subject: </td>";
    print "<td><input name='cmtSubject' size='$stSize' maxlength='256' value='".$_POST['cmtSubject']."'></td></tr>\n";
    print "<tr><td colspan='2'><textarea name='cmtText' rows='$maxRows' cols='$maxCols'>".$_POST['cmtText']."</textarea></td></tr>\n";
    print "<tr><td>&nbsp;</td><td><input type='checkbox' name='sndCopy' value='Y'> Check to have a copy sent to your email</td></tr>\n";
    print "<tr><td colspan='2'><br/></td></tr>\n";
    print "<tr><td colspan='2'>";
    print "Enter the letters: <input name='captcha' size='12'>&nbsp; &nbsp;".showCaptchaImage();
    print "</td></tr>\n";
    print "<tr><td colspan='2'><br/></td></tr>\n";
    print "<tr><td> </td><td><input type='submit' name='submit' value='Send'> &nbsp; &nbsp; ";
    print " <input type='submit' name='submit' value='Click if Captcha unreadable'></td></tr>\n";
    print "</table>\n</fieldset>\n</form>\n</center>\n";
}

?>
