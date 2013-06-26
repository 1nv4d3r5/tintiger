<?php
if ($db = sqlite_open('lighthorse', 0666, $sqliteerror)) { 
/*    sqlite_query($db, 'DROP TABLE photos;');
    sqlite_query($db, 'CREATE TABLE photos (
        create_date date current_date,
        dir_name varchar(255),
        file_name varchar(255),
        image_text glob)');
*/        
    sqlite_query($db, "INSERT INTO photos VALUES (DATETIME('NOW'), 'photos/FOOBOO', 'asdasdasdasd',
        'description forthcoming about this image...')");
    sqlite_query($db, "INSERT INTO photos VALUES (DATETIME('NOW'), 'photos/LEGO', 'DSCN0691.jpg',
        'Might even have something interesting to say about this one, too.')");
        $result = sqlite_query($db, 'select * from photos');
        while ($row = sqlite_fetch_array($result)) {
            print "--".$row['dir_name']."/".$row['file_name']." contains ".$row['image_text']."<br/><br/>\n";
        }
} else {        
    die($sqliteerror);
}


#phpinfo();
/*$a = array("A"=>"a\"aa","B"=>123,"C"=>"asd\'asd","D"=>"flop\"sy","E"=>"Now\'s the time.");
#foreach ($a as $b) { stripslashes($a[$b]); }
$b = array_map(stripslashes, $a);
print_r($b);

function strippers ($x) {
    $from = array("\'");
    $to   = array("'");
    return str_replace($from, $to, $x);
}
/* include("./includes/functions_base.php");
for ($i=1; $i<=8; $i++) { $r = generatePassword(8); print "$i - $r\t-\t".md5($r)."<br/>"; }

/* 
session_start();  
echo "<form method='POST' action=''>";

if ($_POST['login'] == "Login") {
    # check password
    $_SESSION['username'] = $_POST['username'];    
} elseif ($_POST['login'] == "Logout") {
    session_destroy();
}

if(isset($_SESSION['username'])) {
    echo "Welcome back ".$_SESSION['username'];
} else {
    echo "username <input name='username' size='20' value='".$_POST['username']."'><br/>"; 
    echo "password <input name='password' size='20' value='".$_POST['password']."'><br/>"; 
    echo "<input type='submit' name='login' value='Login'> &nbsp; ";
    echo "<input type='submit' name='login' value='Logout'>";
}
echo "<br/>username = ". $_SESSION['username']; 
echo "<br/><input type='submit' name='login' value='Logout'>";
echo "</form>";
#phpinfo();
/*
# rent, util, phone,
$m = (850+150+160) * 4.3;
#car, ins, cel card, boat rents 
$b = ($m * 12);

echo $m."|".$b."|".($b/2000);
#echo "DBG:".phpversion()."|<br/>\n"

/*
echo 'SCRIPT_FILENAME: ' . getenv('SCRIPT_FILENAME') . "<br/>";
echo 'REQUEST_URI: ' .  getenv('REQUEST_URI') . "<br/>";
echo 'SCRIPT_NAME: ' .  getenv('SCRIPT_NAME')  . "<br/>";
echo getcwd() . "<br/>";
echo 'PHP_SELF: ' . getenv('PHP_SELF') . '<br/>';

/*
if (!file_exists("./photos/GoodCoffee-BrownFoam.thmb.jpg")) {
    echo "FIle NOT here"; 
} else {
    echo "File IS here.";
}

#echo "SOFTWARE:".$_SERVER["SERVER_SOFTWARE"];
#echo "<br/>SIGNATURE:".$_SERVER["SERVER_SIGNATURE"];

/*print "<h2><font color='red'>This is a test page. Nothing on it works as advertized. GO AWAY!!</font></h2>\n\n";

$websiteWidgetArgs = array(
  'widgetID' => 3,
	'method' => 'local',
	'whoisWidget' => 0,
	'urlORpath' => '/home/tintige/public_html/modernbill/app-modernbill-order'
);
include("/home/tintige/public_html/modernbill/app-modernbill-order/website_widget_creator.php");
chdir(dirname(__FILE__));
#echo time();
#echo "<br/>";
#echo microtime();
#echo "<br/>sssssssssss";

#$theTime = str_replace(".","",array_sum(explode(" " , microtime())));
#echo $theTime;
//phpinfo();

/*
echo "PHP version: ".phpversion();
echo "<br/>GD version:  FATAL";

#foreach (gd_info() as $key => $val) {
#    print "<br/>$key = $val";
#}

if (imagetypes() & IMG_GIF) {
    echo "GIF Support is enabled";
}
if (imagetypes() & IMG_PNG) {
    echo "<br/>PNG Support is enabled";
}
if (imagetypes() & IMG_JPG) {
    echo "<br/>JPG Support is enabled";
}
if (imagetypes() & IMG_WBMP) {
    echo "<BR/>WBMP Support is enabled";
}
if (imagetypes() & IMG_XPM) {
    echo "<BR/>XPM Support is enabled";
}
*/
?>
