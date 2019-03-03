


<?php
include("db.php");
$pageSize = $_GET['pageSize'];
$page = $_GET['page'];
if ($pageSize == null) {
  $pageSize = 100;
}
if ($page == null) {
  $page = 1;
}

$logsQuery = "select timestamp, toStatus, average, targetTemp from status where fromStatus != toStatus order by timestamp desc limit " . $pageSize . " offset " . (($page-1) * $pageSize);

date_default_timezone_set('GMT');
$results = 0;
?>
<div class="logs">
<?php
foreach ($dbh->query($logsQuery) as $row) {
  #($row[1] == -1 ? "OFF" : "ON")
    echo "<div class='logItem'>" . date("Y-m-d H:i:s", $row[0]) . ": " . ($row[1] == -1 ? " OFF" : " ON") . " average:" . $row[2] . " target:" . $row[3] . "</div>";
    $results++;
}
?>
</div>
<div class="logspaging">
<?php
if ($page > 1) {
  echo '<a href="?menuitem=1&pageSize=' . $pageSize . '&page=' . ($page-1) .'"> << </a>';
}
echo " | " . $page . " | ";
if ($results > 0) {
  echo '<a href="?menuitem=1&pageSize=' . $pageSize . '&page=' . ($page+1) .'"> >> </a>';
}
?>
</div>
