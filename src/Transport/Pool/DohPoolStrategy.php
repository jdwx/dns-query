<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use DateTimeImmutable;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Pooling strategy for DNS-over-HTTPS transports.
 *
 * DoH has special considerations:
 * - Many servers don't support it at all
 * - Failed attempts should be remembered at the server level
 * - HTTP keep-alive timeouts are typically short (5-15 seconds)
 * - Connection reuse depends on HTTP/2 multiplexing
 */
class DohPoolStrategy implements PoolStrategyInterface {


    private const int MAX_IDLE_SECONDS       = 30; // Conservative due to typical HTTP timeouts

    private const int FAILURE_MEMORY_SECONDS = 3600; // Remember failures for 1 hour

    /** @var array<string, DateTimeImmutable> Map of host:port to last failure time */
    private static array $serverFailures = [];


    /**
     * Clear all remembered server failures (useful for testing).
     */
    public static function clearFailureMemory() : void {
        self::$serverFailures = [];
    }


    public function canReuse( PooledConnection $connection ) : bool {
        // Check for errors
        if ( $connection->getErrorCount() > 0 ) {
            return false;
        }

        // HTTP connections timeout quickly
        if ( $connection->getIdleSeconds() > self::MAX_IDLE_SECONDS ) {
            return false;
        }

        return true;
    }


    /** @param list<mixed> $options */
    public function createTransport( string $host, int $port, array $options = [] ) : TransportInterface {
        // This will need to be implemented when DoH transport is added
        throw new \RuntimeException( 'DoH transport not yet implemented' );
        // return TransportFactory::doh( $host, $port, ...$options );
    }


    /** @param list<mixed> $options */
    public function getKey( string $host, int $port, array $options = [] ) : string {
        return sprintf( 'doh:%s:%d', $host, $port );
    }


    public function getMaxIdleTime() : int {
        return self::MAX_IDLE_SECONDS;
    }


    public function handleError( PooledConnection $connection, \Throwable $error ) : bool {
        $connection->recordError();
        // For DoH, we can't trust the connection state after any error
        return false;
    }


    public function recordFailure( string $host, int $port ) : void {
        $key = "{$host}:{$port}";
        self::$serverFailures[ $key ] = new DateTimeImmutable();
    }


    public function shouldAttempt( string $host, int $port ) : bool {
        $key = "{$host}:{$port}";

        // Check if this server has recent failures
        if ( isset( self::$serverFailures[ $key ] ) ) {
            $failureAge = time() - self::$serverFailures[ $key ]->getTimestamp();
            if ( $failureAge < self::FAILURE_MEMORY_SECONDS ) {
                return false;
            }
            // Failure is old, remove it
            unset( self::$serverFailures[ $key ] );
        }

        return true;
    }


}