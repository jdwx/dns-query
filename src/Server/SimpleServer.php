<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Server;


use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Codecs\Codec;
use JDWX\DNSQuery\Codecs\CodecInterface;
use JDWX\DNSQuery\Codecs\RFC1035Decoder;
use JDWX\DNSQuery\Codecs\RFC1035Encoder;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use JDWX\DNSQuery\Transport\SocketTransport;
use JDWX\DNSQuery\Transport\TransportBuffer;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * A simple DNS server implementation that can receive requests and send responses
 * over a single transport. Useful for testing transport implementations and
 * creating mock DNS servers to remove the need for querying live DNS servers
 * during tests.
 *
 * NOT intended for production use!
 */
class SimpleServer {


    /** @var callable|null */
    private $requestHandler = null;

    private CodecInterface $codec;

    private ReadBufferInterface $buffer;


    public function __construct( private readonly TransportInterface $transport ) {
        $this->codec = new Codec( new RFC1035Encoder(), new RFC1035Decoder() );
        $this->buffer = new TransportBuffer( $this->transport );
    }


    /**
     * Create a UDP server bound to the specified address and port.
     */
    public static function createUdp( string $i_stBindAddress = '127.0.0.1', int $i_uPort = 53 ) : self {
        $transport = SocketTransport::udp( $i_stBindAddress, $i_uPort );
        return new self( $transport );
    }


    /**
     * Get a simple request handler that returns NXDOMAIN for all queries.
     */
    public static function nxDomainHandler() : callable {
        return function ( Message $request ) : Message {
            return Message::response( $request, ReturnCode::NXDOMAIN );
        };
    }


    /**
     * Get a request handler that adds specific resource records to responses.
     *
     * @param list<ResourceRecordInterface> $i_rRecords
     */
    public static function recordHandler( array $i_rRecords ) : callable {
        return function ( Message $request ) use ( $i_rRecords ) : Message {
            $response = Message::response( $request );

            // Add the provided records as answers
            foreach ( $i_rRecords as $record ) {
                $response->addAnswer( $record );
            }

            return $response;
        };
    }


    /**
     * Get a simple request handler that returns SERVFAIL for all queries.
     */
    public static function servFailHandler() : callable {
        return function ( Message $request ) : Message {
            return Message::response( $request, ReturnCode::SERVFAIL );
        };
    }


    /**
     * Listen for requests in a loop until the specified number of requests
     * have been handled or a timeout occurs.
     */
    public function handleRequests( int $maxRequests = 1 ) : int {
        $handledCount = 0;

        while ( $handledCount < $maxRequests ) {
            if ( ! $this->handleSingleRequest() ) {
                break;
            }
            $handledCount++;
        }

        return $handledCount;
    }


    /**
     * Listen for a single request and handle it.
     * Returns true if a request was handled, false if timeout occurred.
     */
    public function handleSingleRequest() : bool {

        $request = $this->codec->decodeMessage( $this->buffer );
        if ( $request === null ) {
            return false;
        }

        $response = $this->processRequest( $request );
        if ( $response !== null ) {
            $this->transport->send( $this->codec->encodeMessage( $response ) );
        }

        return true;
    }


    /**
     * Set a custom request handler callback.
     * The callback should accept a Message and return a Message response.
     */
    public function setRequestHandler( callable $handler ) : void {
        $this->requestHandler = $handler;
    }


    /**
     * Process a request and generate a response.
     */
    protected function processRequest( MessageInterface $request ) : ?MessageInterface {
        if ( $this->requestHandler !== null ) {
            return call_user_func( $this->requestHandler, $request );
        }

        return Message::response( $request );
    }


}
