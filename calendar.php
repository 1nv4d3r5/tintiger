<?php
/* calendar.php - do things with the neat little calendar I wrote. Appointments, journal, birthday and such reminders.
right now, cells are as small as possible. Make them fit via config.txt key=var?

- boat yard:
    enter a boat name, auto fill Inspect, Air out, 
    get work todo, storage end date, 
    - schedule work to do (paint takes some time to dry)
    - auto gen pricing
    
Admin screen
- bill pay 
- birthdays/anniverseries




*/

print calendar();

function calendar ($whereto="./index.php") {
    # mktime (hour, minute, second, month, day, year, is_dst)
    global $_GET, $_SERVER;
    $currMonth = ($_GET['month']) ? $_GET['month'] : date("n"); # numeric calendar month
    $currYear  = ($_GET['year'])  ? $_GET['year']  : date("Y");
    $tm   = mktime(0, 0, 0, ($currMonth +$shifted), 1, $currYear);
    $dim  = date("t", $tm);    # days in month
    $dow  = date("w", $tm);    # day of week month starts on
    $txm  = date("F", $tm);    # text name of month
    $pMon = date("M", mktime(0, 0, 0, $currMonth-1, 1, $currYear));
    $nMon = date("M", mktime(0, 0, 0, $currMonth+1, 1, $currYear));
    $cal .= "  <tr><td><br/></td></tr>\n<tr><th>$txm $currYear</th></tr>\n<tr><td>";
    $cal .= "<table class='cal' width='15%'>\n  <tr>";
    foreach (array("S","M","T","W","T","F","S") as $k => $v) { $cal .= "<th>$v</th>"; }
    $cal .= "</tr>\n";
    $day  = " ";
    for ($row=0; $row<6; $row++) {
        $cal .= "  <tr>";
        for ($col=0; $col<7; $col++) {
            if (($row == 0) && ($col == $dow)) { $day = 1; }
            if (is_numeric($day)) {
                $dday = ($day < 10) ? "0$day" : $day;
                $link = "?date=$currYear-".(($currMonth<10)?"0":"")."$currMonth-$dday";
                $cal .= "<td><a href='$link'>$dday</a></td>";
                $day++;
            } else {
                $cal .= "<td>&nbsp;</td>";
            }
            if ($day > $dim) { $day = "&nbsp;"; }
        }
        $cal .= "</tr>\n";
        if ($day >= $dim) { break; }
    }
    $cal .= "  <tr>\n    <td colspan='7' nowrap>\n";
    $cal .= "      <a href='?year=".($currYear-1)."'>".substr(($currYear-1),2)."</a>&nbsp;\n";
    $cal .= "      <a href='?month=".((($currMonth -1) < 1)?12:$currMonth -1)."&year=" .((($currMonth -1) < 1)?$currYear -1:$currYear)."'>$pMon</a>";
    if (!(($currMonth == date("n")) && ($currYear == date("Y")))) {
        $cal .= "&nbsp;<a href='?month=".date("n")."&year=".date("Y")."'>Home</a>";
    } else {
        $cal .= "&nbsp;&nbsp;--&nbsp;&nbsp;";
    }
    $cal .= "&nbsp;\n      <a href='?month=".((($currMonth +1) > 12)?1:$currMonth +1)."&year=".((($currMonth +1) > 12)?$currYear +1:$currYear)."'>$nMon</a>&nbsp;\n";
    $cal .= "      <a href='?year=".($currYear+1)."'>".substr(($currYear+1),2)."</a>\n";
    $cal .= "    </td>\n   </tr>\n</table>\n";
    $cal .= "<!-- end calendar -->\n";
    return $cal;
}
