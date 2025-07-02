<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\MessageInterface;


abstract class AbstractClient implements ClientInterface {


    public function query( MessageInterface $i_request ) : ?MessageInterface {
        $this->sendRequest( $i_request );
        return $this->receiveResponse( $i_request->header()->id() );
    }


}
