<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Buffer\ReadBuffer;
use JDWX\DNSQuery\Buffer\WriteBuffer;
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
        $buffer = new ReadBuffer( $packet );
        return $this->codec->decodeMessage( $buffer );
    }


    protected function sendMessage( MessageInterface $i_msg ) : void {
        $wri = new WriteBuffer();
        $this->codec->encodeMessage( $wri, $i_msg );
        $this->send( $wri->end() );
    }


}
