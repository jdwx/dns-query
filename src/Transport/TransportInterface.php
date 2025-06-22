<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Message\Message;


interface TransportInterface {


    public function receiveRequest() : Message;


    public function receiveResponse() : Message;


    public function sendRequest( Message $i_request ) : void;


    public function sendResponse( Message $i_response ) : void;


}