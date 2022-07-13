<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Network;


use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


interface ITransport {

    public function getNameServer() : string;

    public function getPort() : int;

    public function getType() : int;

    public function sendRequest( RequestPacket $i_request ) : void;

    public function receiveResponse() : ResponsePacket;

}

