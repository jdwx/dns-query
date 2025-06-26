<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\Message;


interface ClientInterface {


    public function query( Message $i_request ) : ?Message;


    public function receiveResponse( ?int $i_id = null, ?int $i_nuTimeoutSeconds = null,
                                     ?int $i_nuTimeoutMicroSeconds = null ) : ?Message;


    public function sendRequest( Message $i_request ) : void;


}

