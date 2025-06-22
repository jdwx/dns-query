<?php /** @noinspection PhpClassCanBeReadonlyInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Transport\TransportInterface;


class Client implements ClientInterface {


    public function __construct( private readonly TransportInterface $transport ) {}


    public function query( Message $i_request ) : Message {
        $this->sendRequest( $i_request );
        return $this->receiveResponse();
    }


    public function receiveResponse() : Message {
        return $this->transport->receiveResponse();
    }


    public function sendRequest( Message $i_request ) : void {
        $this->transport->sendRequest( $i_request );
    }


}
