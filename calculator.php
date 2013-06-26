<?php
# calculator.php - numeric and action buttons, entry box on left, paper tape on the right, rounding, 
/*
Screen: 
    - input boxes for digits and comment, buttons for sign, checkboxes for decimals on left
    - center column for tape of all entries (# per page, show all...?)
    - Right column for comments (show or hide (as tooltip) checkboxes)
Output:  
    - input boxes blank, last #decimals value selected, show comments Y/N selected
    - center column 'digits sign' aligned as 2 columns
    - right column comments or icon for comments if existing
Input: 
    - digits in entry box, 
    - # decimals checkbox (default - 2), 
    - buttons for signs, 
    - comment text 
    - show or hide comments display
Process:
*/
//$signs  = array(array("sqrt","/","*"),array("-","+","^"), array("=", "c", "ce"));
$signs  = array(array("&#251;","/","*"),array("-","+","^"), array("=", "c", "ce"));
$keys   = array(array("7","8","9"),array("4","5","6"),array("1","2","3"),array("0"));
//$_POST['vals'] = "123|+:234|+:357|=:2|*:3344.336|=";
//$_POST['numDecimals'] = 2;
$where = "http://tintiger.net/calculator.php";
$where = "./index.php?action=calculator";
print_r($_POST);

if (strlen($_POST['input']) > 0) {
    // Strip any digits from entered string. if any digits, add to paperTape. 
    $input = substr(preg_replace("/[^0-9]/", "", $_POST['input']), 0, 15);
    $vals = $_POST['vals'].":".$input."|".$_POST['sign'];
    $vals = ((substr($vals, 0, 1) == ":") ? substr($vals, 1) : $vals); // strip leading colon
}

print "<!-- Page contents begins here -->\n";
print "<br/><form action='$where' method='POST' name='calculator'>\n";
print "<div id='calculator'>\n";
print "<table style='calculator'>\n<tr>\n";
print "<td valign='top'>";
# left side - entry box, numeric and sign buttons, rounding checkbox
print "    <br/><br/>\n";
print "    Enter value (and optional comment) and click sign<br/>\n";
print "    <table>\n";
print "      <tr><td>digits:</td>
      <td> <input type='text' size='30' maxlength='30' name='input'></td></tr>\n";
print "    <br/><br/>\n";
print "    <tr><td>comment:</td>
      <td><input type='text' size='30' maxlength='255' name='comment'></td></tr>\n";
print "</table>\n";
print "    <br/><br/>\n";
print "    <table width=50%>\n<tr>\n";
foreach ($signs as $k => $v) {
    print "    <tr>\n";
    foreach ($v as $a) {
        print "    <td><input type='submit' name='sign' value='$a' onClick=''></td>\n";
    }
    print "    </tr>\n";
}

print "    </table>\n";
print "    <br/><br/>\n";
print "    Round to \n";
foreach (array("0","2","4","8") as $r) {
    $out .= " &nbsp; <input type='radio' name='numDecimals' value='$r'";
    if ($_POST['numDecimals'] == $r) { $out .= " selected"; }
    $out .= "> $r,";
}
print substr($out, 7, -1) . " decimals.\n";
print "    <br/><br/>\n";
print "    <input type='radio' name='showCmts' value='Show'";
if ($_POST['showCmts'] == "Show") { print " selected"; }
print "> Show or <input type='radio' name='showCmts' value='Hide'";
if ($_POST['showCmts'] == "Hide") { print " selected"; }
print "> Hide comments";

print "</td><td>&nbsp;&nbsp;&nbsp;</td><td valign='top' align='right'>\n";
print "    <table border='10px'>\n";
// calculator tape cell/column
if (count($vals) < 1) { 
    $valArray = explode(":", $_POST['vals']); }
$rnd  = $_POST['numDecimals'];
if (count($vals) > 0) {
    foreach ($valArray as $v) {
        list($val, $sign) = explode("|", $v);
        print "      <tr><td align='right'>".number_format($val, $rnd,".",",")."</td><td>$sign</td></tr>\n";
        //print "      <tr><td align='right'>".round($val, $rnd)."</td><td>$sign</td></tr>\n";
    }
}
print "    </table background='white'>\n";
print "</td></tr></table>\n";
print "<input type='hidden' name='vals' value='$rnd'>\n";
print "</div id='calculator'>\n";
print "</form>\n";

?>
