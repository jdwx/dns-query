<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\Buffer;
use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Message\MessageInterface;


abstract class AbstractTransport implements TransportInterface {


    public function __construct( private readonly CodecInterface $codec ) {}


    public function receiveRequest() : ?MessageInterface {
        return $this->receiveMessage();
    }


    public function receiveResponse() : ?MessageInterface {
        return $this->receiveMessage();
    }


    public function sendRequest( MessageInterface $i_request ) : void {
        $this->sendMessage( $i_request );
    }


    public function sendResponse( MessageInterface $i_response ) : void {
        $this->sendMessage( $i_response );
    }


    protected function receiveMessage() : ?MessageInterface {
        $packet = $this->receive();
        if ( ! is_string( $packet ) ) {
            return null;
        }
        $buffer = new Buffer( $packet );
        return $this->codec->decode( $buffer );
    }


    protected function sendMessage( MessageInterface $i_msg ) : void {
        $packet = $this->codec->encode( $i_msg );
        $this->send( $packet );
    }


}
