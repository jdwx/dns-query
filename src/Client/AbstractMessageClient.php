<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\MessageInterface;


abstract class AbstractMessageClient extends AbstractClient {


    public function queryMessage( MessageInterface $i_msg ) : ?MessageInterface {
        $this->sendRequest( $i_msg );
        return $this->receiveResponse( $i_msg->header()->id() );
    }


    abstract public function receiveResponse( ?int $i_id = null ) : ?MessageInterface;


    abstract public function sendRequest( MessageInterface $i_request ) : void;


}
