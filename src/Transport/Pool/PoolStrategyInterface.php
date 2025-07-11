<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Interface for transport-specific pooling strategies.
 */
interface PoolStrategyInterface {


    /**
     * Check if a transport can be reused.
     */
    public function canReuse( PooledConnection $connection ) : bool;


    /**
     * Create a new transport instance.
     *
     * @param list<mixed> $options
     */
    public function createTransport( string $host, int $port, array $options = [] ) : TransportInterface;


    /**
     * Get a unique key for this transport configuration.
     *
     * @param list<mixed> $options
     */
    public function getKey( string $host, int $port, array $options = [] ) : string;


    /**
     * Get the maximum idle time for this transport type in seconds.
     */
    public function getMaxIdleTime() : int;


    /**
     * Handle an error that occurred with this transport.
     * Returns true if the transport can still be reused, false if it should be discarded.
     */
    public function handleError( PooledConnection $connection, \Throwable $error ) : bool;


    /**
     * Record a failed connection attempt.
     */
    public function recordFailure( string $host, int $port ) : void;


    /**
     * Check if we should even attempt to connect to this host with this transport type.
     */
    public function shouldAttempt( string $host, int $port ) : bool;


}