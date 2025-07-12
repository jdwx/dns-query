<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use Countable;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Transport pool that uses strategy pattern for transport-specific behavior.
 */
class TransportPool implements Countable {


    /** @var array<string, PooledTransport> All connections in the pool */
    private array $connections = [];

    /** @var array<string, bool> Track which connections are in use */
    private array $inUse = [];


    /** @param list<PoolStrategyInterface> $strategies Available strategies */
    public function __construct( private array $strategies ) {}


    /** Create a pool with the default strategies. */
    public static function default() : self {
        return new self( self::defaultStrategies() );
    }


    /** @return list<PoolStrategyInterface> */
    protected static function defaultStrategies() : array {
        return [
            new UdpPoolStrategy(),
            new TcpPoolStrategy(),
            new HttpsPoolStrategy(),
        ];
    }


    /**
     * Acquire a transport from the pool.
     *
     * @param array<string, mixed> $options
     */
    public function acquire( string $type, string $host, ?int $port = null, array $options = [] ) : TransportInterface {
        // Find a strategy that handles this type
        $strategy = null;
        foreach ( $this->strategies as $s ) {
            if ( $s->shouldAttempt( $type, $host, $port ) ) {
                $strategy = $s;
                break;
            }
        }

        if ( $strategy === null ) {
            throw new \RuntimeException(
                "No strategy available for transport type '{$type}' to {$host}" .
                ( $port !== null ? ":{$port}" : '' )
            );
        }

        $key = $strategy->getKey( $host, $port, $options );

        // Try to reuse existing connection
        if ( isset( $this->connections[ $key ] ) && ! isset( $this->inUse[ $key ] ) ) {
            $pooledTransport = $this->connections[ $key ];

            if ( $strategy->canReuse( $pooledTransport ) ) {
                $this->inUse[ $key ] = true;
                $pooledTransport->touch();
                $pooledTransport->attachToPool( $this );
                return $pooledTransport;
            }

            // Connection can't be reused, remove it
            unset( $this->connections[ $key ] );
        }

        // Create new connection
        try {
            $transport = $strategy->createTransport( $host, $port, $options );
        } catch ( \Throwable $e ) {
            $strategy->recordFailure( $host, $port );
            throw $e;
        }

        $pooledTransport = new PooledTransport( $transport, $key, $type, $strategy );
        $this->connections[ $key ] = $pooledTransport;
        $this->inUse[ $key ] = true;
        $pooledTransport->attachToPool( $this );

        return $pooledTransport;
    }


    /**
     * Add a pooling strategy.
     */
    public function addStrategy( PoolStrategyInterface $strategy ) : void {
        $this->strategies[] = $strategy;
    }


    /**
     * Get the number of connections in the pool.
     */
    public function count() : int {
        return count( $this->connections );
    }


    /**
     * Remove all connections from the pool.
     */
    public function flush() : void {
        $this->connections = [];
        $this->inUse = [];
    }


    /**
     * Get the number of active connections.
     */
    public function getActiveCount() : int {
        return count( $this->inUse );
    }


    /**
     * Get the number of idle connections.
     */
    public function getIdleCount() : int {
        return count( $this->connections ) - count( $this->inUse );
    }


    /**
     * Handle an error that occurred with a transport.
     */
    public function handleError( PooledTransport $transport, \Throwable $error ) : void {
        $strategy = $transport->getStrategy();

        if ( ! $strategy->handleError( $transport, $error ) ) {
            // Strategy says connection is no longer usable
            unset( $this->connections[ $transport->getKey() ], $this->inUse[ $transport->getKey() ] );
        }
    }


    /**
     * Release a transport back to the pool.
     */
    public function release( TransportInterface $transport ) : void {
        if ( ! $transport instanceof PooledTransport ) {
            return;
        }

        $key = $transport->getKey();
        unset( $this->inUse[ $key ] );
        $transport->detachFromPool();

        // Clean up old connections periodically
        if ( count( $this->connections ) > 10 && random_int( 1, 10 ) === 1 ) {
            $this->cleanupStaleConnections();
        }
    }


    /**
     * Remove stale connections based on their strategy's rules.
     */
    private function cleanupStaleConnections() : void {
        foreach ( $this->connections as $key => $pooledTransport ) {
            if ( isset( $this->inUse[ $key ] ) ) {
                continue;
            }

            // Find the strategy for this connection type
            $strategy = $pooledTransport->getStrategy();
            if ( ! $strategy->canReuse( $pooledTransport ) ) {
                unset( $this->connections[ $key ] );
            }
        }
    }


}