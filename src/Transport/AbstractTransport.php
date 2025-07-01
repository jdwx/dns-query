<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Message\Message;


abstract class AbstractTransport implements TransportInterface {


    public function __construct( private readonly CodecInterface $codec ) {}


    public function receiveRequest() : ?Message {
        return $this->receiveMessage();
    }


    public function receiveResponse() : ?Message {
        return $this->receiveMessage();
    }


    public function sendRequest( Message $i_request ) : void {
        $this->sendMessage( $i_request );
    }


    public function sendResponse( Message $i_response ) : void {
        $this->sendMessage( $i_response );
    }


    protected function receiveMessage() : ?Message {
        $packet = $this->receive();
        if ( ! is_string( $packet ) ) {
            return null;
        }
        $buffer = new Buffer( $packet );
        return $this->codec->decode( $buffer );
    }


    protected function sendMessage( Message $i_msg ) : void {
        $packet = $this->codec->encode( $i_msg );
        $this->send( $packet );
    }


}
