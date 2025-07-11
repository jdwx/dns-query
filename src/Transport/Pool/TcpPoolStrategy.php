<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use JDWX\DNSQuery\Transport\TransportFactory;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Pooling strategy for TCP transports.
 *
 * TCP is stateful, so:
 * - Connections must be discarded after any error
 * - Connections should time out after 2 minutes (RFC recommendation)
 * - Need to detect closed connections
 */
class TcpPoolStrategy implements PoolStrategyInterface {


    private const int MAX_IDLE_SECONDS = 120; // 2 minutes per RFC


    public function canReuse( PooledConnection $connection ) : bool {
        // Check if connection has errors
        if ( $connection->getErrorCount() > 0 ) {
            return false;
        }

        // Check if connection has been idle too long
        if ( $connection->getIdleSeconds() > self::MAX_IDLE_SECONDS ) {
            return false;
        }

        // TODO: Could add socket state check here if the Socket class supports it
        // e.g., check if socket is still connected

        return true;
    }


    /** @param list<mixed> $options */
    public function createTransport( string $host, int $port, array $options = [] ) : TransportInterface {
        return TransportFactory::tcp( $host, $port, ...$options );
    }


    public function getKey( string $host, int $port, array $options = [] ) : string {
        return sprintf( 'tcp:%s:%d', $host, $port );
    }


    public function getMaxIdleTime() : int {
        return self::MAX_IDLE_SECONDS;
    }


    public function handleError( PooledConnection $connection, \Throwable $error ) : bool {
        $connection->recordError();

        // For TCP, ANY error invalidates the connection because we can't
        // know what state the stream is in. This includes:
        // - ConnectionException: Stream is corrupted
        // - ProtocolException: Connection refused, but also might have partial data
        // - NetworkException: Timeout might leave partial data in buffers
        // - Any other error: Can't trust the stream state

        return false;
    }


    public function recordFailure( string $host, int $port ) : void {
        // TCP failures are usually transient (connection refused, timeout)
        // so we don't need to remember them long-term
    }


    public function shouldAttempt( string $host, int $port ) : bool {
        // TCP is generally available, though some servers might not support it
        return true;
    }


}