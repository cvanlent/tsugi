<?php

require_once "Mersenne_Twister.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;

require_once "names.php";

// Compute the stuff for the output
$code = $USER->id+$LINK->id+$CONTEXT->id;
$MT = new Mersenne_Twister($code);
$my_names = array();
$my_age = array();
$howmany = $MT->getNext(4,6);
for($i=0; $i < $howmany; $i ++ ) {
    $name = $names[$MT->getNext(0,count($names))];
    $age = $MT->getNext(13,40);
    $sha = sha1($name.$age);
    $database[] = array($sha,$name,$age);
}
$sorted = $database;
sort($sorted);
$goodsha = $sorted[0][0];
$oldgrade = $RESULT->grade;

if ( isset($_POST['sha1']) ) {
    if ( $_POST['sha1'] != $goodsha ) {
        $_SESSION['error'] = "Your code did not match";
        header('Location: '.addSession('index.php'));
        return;
    }

    $gradetosend = 1.0;
    $scorestr = "Your answer is correct, score saved.";
    if ( $dueDate->penalty > 0 ) {
        $gradetosend = $gradetosend * (1.0 - $dueDate->penalty);
        $scorestr = "Effective Score = $gradetosend after ".$dueDate->penalty*100.0." percent late penalty";
    }
    if ( $oldgrade > $gradetosend ) {
        $scorestr = "New score of $gradetosend is < than previous grade of $oldgrade, previous grade kept";
        $gradetosend = $oldgrade;
    }

    // Use LTIX to send the grade back to the LMS.
    $debug_log = array();
    $retval = LTIX::gradeSend($gradetosend, false, $debug_log);
    $_SESSION['debug_log'] = $debug_log;

    if ( $retval === true ) {
        $_SESSION['success'] = $scorestr;
    } else if ( is_string($retval) ) {
        $_SESSION['error'] = "Grade not sent: ".$retval;
    } else {
        echo("<pre>\n");
        var_dump($retval);
        echo("</pre>\n");
        die();
    }

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

// echo($goodsha);
if ( $LINK->grade > 0 ) {
    echo('<p class="alert alert-info">Your current grade on this assignment is: '.($LINK->grade*100.0).'%</p>'."\n");
}

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}
?>
<p>
<form method="post">
To get credit for this assignment, perform the instructions below and 
enter the code you get from the instrutions below here (Hint: starts with <?= substr($goodsha,0,3) ?>)<br/>
<input type="text" size="80" name="sha1">
<input type="submit">
</form>
</p>
<h1>Instructions</h1>
<p>
First, create a MySql database or use an existing database and then create a table 
in the database called "Ages":

<pre>
CREATE TABLE Ages ( 
  name VARCHAR(128), 
  age INTEGER
)
</pre>
<p>
Then make sure the table is empty by deleting any rows that 
you previously inserted, and insert these rows and only these rows 
with the following commands:
<pre>
<?php
echo("DELETE FROM Ages;\n");
foreach($database as $row) {
   echo("INSERT INTO Ages (name, age) VALUES ('".$row[1]."', ".$row[2].");\n");
}
?>
</pre>
Once the inserts are done, run the following SQL command:
<pre>
SELECT sha1(CONCAT(name,age)) AS X FROM Ages ORDER BY X
</pre>
Find the <b>first</b> row in the resulting record set and enter the long string that looks like 
<b>254c6127cdbc4c38e065317667340e8b0950046f</b>.
</p>
