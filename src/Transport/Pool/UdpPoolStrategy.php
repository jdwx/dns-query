<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use JDWX\DNSQuery\Exceptions\ConnectionException;
use JDWX\DNSQuery\Exceptions\NetworkException;
use JDWX\DNSQuery\Exceptions\ProtocolException;
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


    public function canReuse( PooledTransport $transport ) : bool {
        // UDP transports can always be reused - they're stateless
        return true;
    }


    /** @param array<string, mixed> $options */
    public function createTransport( string $host, ?int $port, array $options = [] ) : TransportInterface {
        // Extract parameters for factory method
        $timeoutSeconds = isset( $options[ 'timeout_seconds' ] ) ? (int) $options[ 'timeout_seconds' ] : null;
        $timeoutMicroseconds = isset( $options[ 'timeout_microseconds' ] ) ? (int) $options[ 'timeout_microseconds' ] : null;
        $localAddress = isset( $options[ 'local_address' ] ) ? (string) $options[ 'local_address' ] : null;
        $localPort = isset( $options[ 'local_port' ] ) ? (int) $options[ 'local_port' ] : null;

        return TransportFactory::udp( $host, $port, $timeoutSeconds, $timeoutMicroseconds, $localAddress, $localPort );
    }


    public function getKey( string $host, ?int $port, array $options = [] ) : string {
        $port ??= 53; // Default to port 53 if not specified
        // For UDP, we can potentially share sockets between different servers
        // if we're not using connect(). But for now, keep them separate.
        return sprintf( 'udp:%s:%d', $host, $port );
    }


    public function getMaxIdleTime() : int {
        // No timeout for UDP connections
        return PHP_INT_MAX;
    }


    public function handleError( PooledTransport $transport, \Throwable $error ) : bool {
        $transport->recordError();

        // For UDP, only connection-level errors invalidate the socket
        if ( $error instanceof ConnectionException ) {
            // Socket is in a bad state, can't reuse
            return false;
        }

        // Protocol exceptions don't affect UDP reusability
        // (UDP is connectionless, each packet is independent)
        if ( $error instanceof ProtocolException ) {
            return true;
        }

        // Network exceptions (timeouts, unreachable) don't affect UDP socket
        if ( $error instanceof NetworkException ) {
            return true;
        }

        // Unknown error type - be conservative
        return false;
    }


    public function recordFailure( string $host, ?int $port ) : void {
        // UDP failures are transient - no need to remember them
    }


    public function shouldAttempt( string $type, string $host, ?int $port ) : bool {
        // Only handle UDP requests
        return $type === 'udp';
    }


}