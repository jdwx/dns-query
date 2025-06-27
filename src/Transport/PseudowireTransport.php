<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Message\Message;
use RuntimeException;


/**
 * Simple transport useful for testing and emulation.
 */
class PseudowireTransport implements TransportInterface {


    /** @var list<string> */
    private array $rRequests = [];

    /** @var list<string> */
    private array $rResponses = [];


    public function __construct( private readonly CodecInterface $codec ) {}


    public function receiveRequest( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : Message {
        if ( empty( $this->rRequests ) ) {
            throw new RuntimeException( 'No requests available.' );
        }
        return $this->codec->decode( array_shift( $this->rRequests ) );
    }


    public function receiveResponse( int $i_uTimeoutSeconds, int $i_uTimeoutMicroSeconds ) : Message {
        if ( empty( $this->rResponses ) ) {
            throw new RuntimeException( 'No responses available.' );
        }
        return $this->codec->decode( array_shift( $this->rResponses ) );
    }


    public function sendRequest( Message $i_request ) : void {
        $this->rRequests[] = $this->codec->encode( $i_request );
    }


    public function sendResponse( Message $i_response ) : void {
        $this->rResponses[] = $this->codec->encode( $i_response );
    }


}
