<?php
include('twitter.lib.php');

$twitter = new Twitter('justinpoliey', 'freestyle');

$res = $twitter->getFollowerIDs(array('id'=>'nicluciano'));

echo $res . "\n";
?>
