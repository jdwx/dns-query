<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Message\Message;


abstract class AbstractTransport implements TransportInterface {


    public function __construct( private readonly TransportCodecInterface $encoder ) {}


    public function receiveRequest() : Message {
        $packet = $this->receivePacket();
        return $this->encoder->decode( $packet );
    }


    public function receiveResponse() : Message {
        $packet = $this->receivePacket();
        return $this->encoder->decode( $packet );
    }


    public function sendRequest( Message $i_request ) : void {
        $packet = $this->encoder->encode( $i_request );
        $this->sendPacket( $packet );
    }


    public function sendResponse( Message $i_response ) : void {
        $packet = $this->encoder->encode( $i_response );
        $this->sendPacket( $packet );
    }


    abstract protected function receivePacket() : string;


    abstract protected function sendPacket( string $packet ) : void;


}
