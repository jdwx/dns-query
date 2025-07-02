<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\MessageInterface;


interface ClientInterface {


    public function query( MessageInterface $i_request ) : ?MessageInterface;


    public function receiveResponse( ?int $i_id = null, ?int $i_nuTimeoutSeconds = null,
                                     ?int $i_nuTimeoutMicroSeconds = null ) : ?MessageInterface;


    public function sendRequest( MessageInterface $i_request ) : void;


}

