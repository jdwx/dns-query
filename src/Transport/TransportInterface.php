<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Message\Message;


interface TransportInterface {


    public function receiveRequest( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?Message;


    public function receiveResponse( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?Message;


    public function sendRequest( Message $i_request ) : void;


    public function sendResponse( Message $i_response ) : void;


}