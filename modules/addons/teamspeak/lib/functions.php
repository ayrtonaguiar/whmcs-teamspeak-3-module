<?php
require_once __DIR__ . '/../../../../init.php';
use Illuminate\Database\Capsule\Manager as Capsule;
$servers = Capsule::table('tblservers')->where('type', 'teamspeak')->get();
$listservers = array();
foreach ($servers as $server) {
    $listservers[$server->name] = array('ip' => $server->ipaddress, 'port' => ($server->port ? $server->port : 10011));
}
if (isset($_GET['host'])) {
    $host = $_GET['host'];
    if (isset($listservers[$host])) {
        header('Content-Type: application/json');
        $return = array(
            'status' => test($listservers[$host]),
        );
        echo json_encode($return);
        exit;
    } else {
        header("HTTP/1.1 404 Not Found");
    }
}
function test($server)
{
    $socket = @fsockopen($server['ip'], $server['port'], $errorNo, $errorStr, 3);
    if ($errorNo == 0) {
        return true;
    } else {
        return false;
    }
}
function in_array_r($needle, $haystack, $strict = false)
{
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }
    return false;
}
function secondsToTime($seconds)
{
    if ($seconds) {
        $dtF = new DateTime("@0");
        $dtT = new DateTime("@$seconds");

        return $dtF->diff($dtT)->format('%ad %hh %im');
    } else {
        return null;
    }
}