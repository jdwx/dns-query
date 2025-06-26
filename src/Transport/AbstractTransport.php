<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Message\Message;


abstract class AbstractTransport implements TransportInterface {


    use UnifiedTransportTrait;


    public function __construct( private readonly CodecInterface $codec ) {}


    public function receiveRequest( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?Message {
        return $this->receiveMessage( $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds );
    }


    public function receiveResponse( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?Message {
        return $this->receiveMessage( $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds );
    }


    public function sendRequest( Message $i_request ) : void {
        $this->sendMessage( $i_request );
    }


    public function sendResponse( Message $i_response ) : void {
        $this->sendMessage( $i_response );
    }


    protected function receiveMessage( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?Message {
        $packet = $this->receivePacket( $i_uTimeoutSeconds, $i_uTimeoutMicroSeconds );
        if ( ! is_string( $packet ) ) {
            return null;
        }
        return $this->codec->decode( $packet );
    }


    abstract protected function receivePacket( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : ?string;


    protected function sendMessage( Message $i_msg ) : void {
        $packet = $this->codec->encode( $i_msg );
        $this->sendPacket( $packet );
    }


    abstract protected function sendPacket( string $packet ) : void;


}
