<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\Message;


abstract class AbstractClient implements ClientInterface {


    public function query( Message $i_request ) : ?Message {
        $this->sendRequest( $i_request );
        return $this->receiveResponse( $i_request->id );
    }


}
