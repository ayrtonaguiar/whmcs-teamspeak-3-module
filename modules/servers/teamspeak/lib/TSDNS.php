<?php

class TSDNS {

    protected $url = null;
    protected $apiKey = null;

    function __construct($url, $apiKey) {
        $this->url = $url;
        $this->apiKey = $apiKey;
    }

    function getZones() {
        $command = '/list';
        return $this->sendRequest($command);
    }

    function addZone($zone, $target) {
        $command = '/add/' . $zone . '/' . $target;
        return $this->sendRequest($command);
    }

    function deleteZone($zone) {
        $command = '/del/' . $zone;
        return $this->sendRequest($command);
    }

    function getZone($zone) {
        $command = '/get/' . $zone;
        return $this->sendRequest($command);
    }

    function sendRequest($command) {
        $url = $this->url . $command;
        $headers = array('Accept' => 'application/json', 'Authorization' => $this->apiKey);
        Requests::register_autoloader();
        $request = Requests::get($url, $headers);
        return $request;
    }

}
