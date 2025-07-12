<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Client;


use JDWX\DNSQuery\Codecs\Codec;
use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Data\TC;
use JDWX\DNSQuery\Exceptions\ProtocolException;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Transport\Pool\HttpsPoolStrategy;
use JDWX\DNSQuery\Transport\Pool\TransportPool;
use JDWX\DNSQuery\Transport\TransportBuffer;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * A DNS client that forwards all requests to a single resolver,
 * with intelligent transport selection and fallback.
 *
 * - Tries DNS-over-HTTPS first
 * - Falls back to UDP if DoH fails (and remembers the failure)
 * - Automatically retries with TCP if UDP response has TC flag set
 */
class ForwardingClient extends AbstractTimedClient {


    private TransportPool $pool;

    private CodecInterface $codec;

    private string $nameserver;

    private int $port;

    private ?TransportInterface $currentTransport = null;

    private bool $httpsEnabled = true;

    private string $lastTransportType = '';

    private ?MessageInterface $lastRequest = null;


    public function __construct( string          $nameserver,
                                 int             $port = 53,
                                 ?TransportPool  $pool = null,
                                 ?CodecInterface $codec = null,
                                 ?int            $i_nuDefaultTimeoutSeconds = null,
                                 ?int            $i_nuDefaultTimeoutMicroSeconds = null ) {
        $this->nameserver = $nameserver;
        $this->port = $port;
        $this->pool = $pool ?? $this->createDefaultPool();
        $this->codec = $codec ?? Codec::rfc1035();
        parent::__construct( $i_nuDefaultTimeoutSeconds, $i_nuDefaultTimeoutMicroSeconds );
    }


    /**
     * Clean up and release transport when done.
     */
    public function __destruct() {
        $this->release();
    }


    /**
     * Disable DNS-over-HTTPS for this client.
     * Useful if you know the server doesn't support it.
     */
    public function disableHttps() : void {
        $this->httpsEnabled = false;
    }


    /**
     * Enable DNS-over-HTTPS for this client.
     * It will be tried again even if it previously failed.
     */
    public function enableHttps() : void {
        $this->httpsEnabled = true;
        // Clear the pool's memory of HTTPS failures for this server
        HttpsPoolStrategy::clearFailureMemory();
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
     * Send a DNS request using the best available transport.
     */
    public function sendRequest( MessageInterface $i_request ) : void {
        // Store the request in case we need to retry
        $this->lastRequest = $i_request;

        // Release any previous transport
        $this->release();

        // Determine which transport to use
        [ $transportType, $port ] = $this->selectTransportAndPort();
        $this->lastTransportType = $transportType;

        try {
            $this->currentTransport = $this->pool->acquire( $transportType, $this->nameserver, $port );
            $this->currentTransport->send( $this->codec->encodeMessage( $i_request )->end() );
        } catch ( ProtocolException $e ) {
            // If HTTPS fails with protocol exception, disable it
            if ( in_array( $transportType, [ 'https', 'httpsget', 'httpspost' ] ) ) {
                $this->httpsEnabled = false;
                // Retry with UDP
                $this->sendRequest( $i_request );
            } else {
                throw $e;
            }
        }
    }


    protected function receiveAnyResponse() : ?MessageInterface {
        if ( $this->currentTransport === null ) {
            return null;
        }

        $buffer = new TransportBuffer( $this->currentTransport );
        $response = $this->codec->decodeMessage( $buffer );

        // Check for truncation flag if this was a UDP response
        if ( $response !== null && $this->lastTransportType === 'udp' &&
            $this->lastRequest !== null && $response->header()->getTC() === TC::TRUNCATED ) {
            // Response is truncated, retry with TCP
            $this->release();

            try {
                $this->currentTransport = $this->pool->acquire( 'tcp', $this->nameserver, $this->port );
                $this->lastTransportType = 'tcp';

                // Resend the request over TCP
                $this->currentTransport->send( $this->codec->encodeMessage( $this->lastRequest )->end() );

                // Read the TCP response
                $tcpBuffer = new TransportBuffer( $this->currentTransport );
                $tcpResponse = $this->codec->decodeMessage( $tcpBuffer );

                return $tcpResponse ?? $response; // Fall back to truncated response if TCP fails
            } catch ( \Exception ) {
                // TCP failed, return the truncated response
                return $response;
            }
        }

        return $response;
    }


    /**
     * Create a pool with appropriate strategies for forwarding.
     */
    private function createDefaultPool() : TransportPool {
        return TransportPool::default();
    }


    /**
     * Select the best transport type and port to use.
     *
     * @return array{0: string, 1: int} Transport type and port
     */
    private function selectTransportAndPort() : array {
        // Try HTTPS first if enabled
        if ( $this->httpsEnabled ) {
            return [ 'https', 443 ];
        }

        // Default to UDP with configured port
        return [ 'udp', $this->port ];
    }


}