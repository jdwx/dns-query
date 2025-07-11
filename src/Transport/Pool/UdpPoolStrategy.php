<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use JDWX\DNSQuery\Transport\TransportFactory;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Pooling strategy for UDP transports.
 *
 * UDP is connectionless, so:
 * - Connections can be reused indefinitely (no timeout)
 * - Most errors don't invalidate the transport
 * - No need to check connection state
 */
class UdpPoolStrategy implements PoolStrategyInterface {


    public function canReuse( PooledConnection $connection ) : bool {
        // UDP transports can always be reused - they're stateless
        return true;
    }


    public function createTransport( string $host, int $port, array $options = [] ) : TransportInterface {
        return TransportFactory::udp( $host, $port, ...$options );
    }


    public function getKey( string $host, int $port, array $options = [] ) : string {
        // For UDP, we can potentially share sockets between different servers
        // if we're not using connect(). But for now, keep them separate.
        return sprintf( 'udp:%s:%d', $host, $port );
    }


    public function getMaxIdleTime() : int {
        // No timeout for UDP connections
        return PHP_INT_MAX;
    }


    public function handleError( PooledConnection $connection, \Throwable $error ) : bool {
        $connection->recordError();

        // For UDP, only connection-level errors invalidate the socket
        if ( $error instanceof \JDWX\DNSQuery\Exceptions\ConnectionException ) {
            // Socket is in a bad state, can't reuse
            return false;
        }

        // Protocol exceptions don't affect UDP reusability
        // (UDP is connectionless, each packet is independent)
        if ( $error instanceof \JDWX\DNSQuery\Exceptions\ProtocolException ) {
            return true;
        }

        // Network exceptions (timeouts, unreachable) don't affect UDP socket
        if ( $error instanceof \JDWX\DNSQuery\Exceptions\NetworkException ) {
            return true;
        }

        // Unknown error type - be conservative
        return false;
    }


    public function recordFailure( string $host, int $port ) : void {
        // UDP failures are transient - no need to remember them
    }


    public function shouldAttempt( string $host, int $port ) : bool {
        // UDP is always worth trying - it's the universal fallback
        return true;
    }


}