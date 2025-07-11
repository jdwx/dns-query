<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Codecs\Codec;
use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Codecs\RFC1035Decoder;
use JDWX\DNSQuery\Codecs\RFC1035Encoder;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Transport\Pool\TransportPool;
use JDWX\DNSQuery\Transport\TransportBuffer;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * A DNS client that uses a connection pool to reuse transports
 * and remembers connection failures.
 */
class PooledClient extends AbstractTimedClient {


    private TransportPool $pool;

    private CodecInterface $codec;

    private ?TransportInterface $currentTransport = null;

    private string $defaultTransportType = 'udp';


    public function __construct( ?TransportPool  $pool = null,
                                 ?CodecInterface $codec = null,
                                 ?int            $i_nuDefaultTimeoutSeconds = null,
                                 ?int            $i_nuDefaultTimeoutMicroSeconds = null ) {
        $this->pool = $pool ?? TransportPool::default();
        $this->codec = $codec ?? new Codec( new RFC1035Encoder(), new RFC1035Decoder() );
        parent::__construct( $i_nuDefaultTimeoutSeconds, $i_nuDefaultTimeoutMicroSeconds );
    }


    /**
     * Clean up and release transport when done.
     */
    public function __destruct() {
        $this->release();
    }


    /**
     * Get the underlying transport pool.
     */
    public function getPool() : TransportPool {
        return $this->pool;
    }


    /**
     * Release the current transport back to the pool.
     */
    public function release() : void {
        if ( $this->currentTransport !== null ) {
            $this->pool->release( $this->currentTransport );
            $this->currentTransport = null;
        }
    }


    /**
     * Send a request using the default or previously used transport.
     */
    public function sendRequest( MessageInterface $i_request ) : void {
        if ( $this->currentTransport === null ) {
            throw new \RuntimeException( 'No transport available. Use sendRequestTo() first.' );
        }

        $this->currentTransport->send( $this->codec->encodeMessage( $i_request )->end() );
    }


    /**
     * Send a request to the specified nameserver, using pooled connections.
     */
    public function sendRequestTo( MessageInterface $request, string $nameserver, int $port = 53,
                                   ?string          $transportType = null ) : void {
        $transportType ??= $this->defaultTransportType;

        // Release any previously acquired transport
        if ( $this->currentTransport !== null ) {
            $this->pool->release( $this->currentTransport );
        }

        // Acquire a transport from the pool
        $this->currentTransport = $this->pool->acquire( $transportType, $nameserver, $port );
        $this->currentTransport->send( $this->codec->encodeMessage( $request )->end() );
    }


    /**
     * Set the default transport type for new connections.
     */
    public function setDefaultTransportType( string $type ) : void {
        $this->defaultTransportType = $type;
    }


    protected function receiveAnyResponse() : ?MessageInterface {
        if ( $this->currentTransport === null ) {
            return null;
        }

        $buffer = new TransportBuffer( $this->currentTransport );
        return $this->codec->decodeMessage( $buffer );
    }


}