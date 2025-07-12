<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use DateTimeImmutable;
use JDWX\DNSQuery\Exceptions\ProtocolException;
use JDWX\DNSQuery\Transport\TransportFactory;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Pooling strategy for DNS-over-HTTPS transports.
 *
 * HTTPS has special considerations:
 * - Many servers don't support it at all
 * - Failed attempts should be remembered at the server level
 * - HTTP keep-alive timeouts are typically short (5-15 seconds)
 * - Connection reuse depends on HTTP/2 multiplexing
 * - Handles both GET and POST methods
 */
class HttpsPoolStrategy implements PoolStrategyInterface {


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


    public function canReuse( PooledTransport $transport ) : bool {
        // Check for errors
        if ( $transport->getErrorCount() > 0 ) {
            return false;
        }

        // HTTP connections timeout quickly
        if ( $transport->getIdleSeconds() > self::MAX_IDLE_SECONDS ) {
            return false;
        }

        return true;
    }


    /** @param array<string, mixed> $options */
    public function createTransport( string $host, ?int $port, array $options = [] ) : TransportInterface {
        // Determine which HTTPS method to use based on options
        $method = $options[ 'method' ] ?? 'post';

        // Extract timeout parameters for factory methods
        $timeoutSeconds = isset( $options[ 'timeout_seconds' ] ) ? (int) $options[ 'timeout_seconds' ] : null;
        $timeoutMicroseconds = isset( $options[ 'timeout_microseconds' ] ) ? (int) $options[ 'timeout_microseconds' ] : null;

        if ( is_int( $port ) ) {
            $host .= ':' . $port; // Append port if specified
        }

        if ( $method === 'get' ) {
            return TransportFactory::httpsGet( $host, $timeoutSeconds, $timeoutMicroseconds );
        }

        // Default to POST
        return TransportFactory::httpsPost( $host, $timeoutSeconds, $timeoutMicroseconds );
    }


    /** @param array<string, mixed> $options */
    public function getKey( string $host, ?int $port, array $options = [] ) : string {
        $port ??= 443; // Default to standard HTTPS port
        $method = $options[ 'method' ] ?? 'post';
        return sprintf( 'https-%s:%s:%d', $method, $host, $port );
    }


    public function getMaxIdleTime() : int {
        return self::MAX_IDLE_SECONDS;
    }


    public function handleError( PooledTransport $transport, \Throwable $error ) : bool {
        $transport->recordError();

        // Protocol exceptions indicate HTTPS DNS is not supported at all
        if ( $error instanceof ProtocolException ) {
            // Record this at the server level - this server doesn't support DNS over HTTPS
            $parts = explode( ':', $transport->getKey() );
            if ( count( $parts ) >= 3 ) {
                // Extract host and port from key format: https-method:host:port
                $this->recordFailure( $parts[ 1 ], (int) $parts[ 2 ] );
            }
        }

        // For DoH, we can't trust the connection state after any error
        return false;
    }


    public function recordFailure( string $host, ?int $port ) : void {
        $port ??= 443; // Default to standard HTTPS port
        $key = "{$host}:{$port}";
        self::$serverFailures[ $key ] = new DateTimeImmutable();
    }


    public function shouldAttempt( string $type, string $host, ?int $port ) : bool {
        // Handle various HTTPS transport types
        if ( ! in_array( $type, [ 'https', 'httpsget', 'httpspost' ] ) ) {
            return false;
        }

        // Use port 443 as default for HTTPS
        $port ??= 443;
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