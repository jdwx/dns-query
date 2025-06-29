<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Server;


use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\ResourceRecordInterface;
use JDWX\DNSQuery\Transport\TransportInterface;
use JDWX\DNSQuery\Transport\UdpTransport;


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

    private int $uDefaultTimeoutSeconds = 5;

    private int $uDefaultTimeoutMicroSeconds = 0;


    public function __construct( private readonly TransportInterface $transport ) {}


    /**
     * Create a UDP server bound to the specified address and port.
     */
    public static function createUdp( string $i_stBindAddress = '127.0.0.1', int $i_uPort = 53 ) : self {
        $transport = new UdpTransport( $i_stBindAddress, $i_uPort );
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
            $server = new self( new UdpTransport( '127.0.0.1' ) ); // Dummy transport
            $response = $server->createDefaultResponse( $request );

            // Add the provided records as answers
            foreach ( $i_rRecords as $record ) {
                $response->answer[] = $record;
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
    public function handleRequests( int $maxRequests = 1, ?int $timeoutSeconds = null, ?int $timeoutMicroSeconds = null ) : int {
        $handledCount = 0;

        while ( $handledCount < $maxRequests ) {
            if ( ! $this->handleSingleRequest( $timeoutSeconds, $timeoutMicroSeconds ) ) {
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
    public function handleSingleRequest( ?int $timeoutSeconds = null, ?int $timeoutMicroSeconds = null ) : bool {
        $timeoutSeconds ??= $this->uDefaultTimeoutSeconds;
        $timeoutMicroSeconds ??= $this->uDefaultTimeoutMicroSeconds;

        $request = $this->transport->receiveRequest( $timeoutSeconds, $timeoutMicroSeconds );
        if ( $request === null ) {
            return false; // Timeout
        }

        $response = $this->processRequest( $request );
        if ( $response !== null ) {
            $this->transport->sendResponse( $response );
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
     * Set default timeout values for receiving requests.
     */
    public function setTimeout( int $seconds, int $microSeconds = 0 ) : void {
        $this->uDefaultTimeoutSeconds = $seconds;
        $this->uDefaultTimeoutMicroSeconds = $microSeconds;
    }


    /**
     * Create a default response for a request.
     * This creates a basic response that echoes the question back with no answers.
     */
    protected function createDefaultResponse( Message $request ) : Message {
        $response = new Message();
        $response->id = $request->id;
        $response->qr = QR::RESPONSE;
        $response->rd = $request->rd;
        $response->ra = $request->ra;
        $response->opcode = $request->opcode;

        // Copy questions to response
        $response->question = $request->question;

        return $response;
    }


    /**
     * Process a request and generate a response.
     */
    protected function processRequest( Message $request ) : ?Message {
        if ( $this->requestHandler !== null ) {
            return call_user_func( $this->requestHandler, $request );
        }

        return $this->createDefaultResponse( $request );
    }


}
