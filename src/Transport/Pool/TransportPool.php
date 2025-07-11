<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use Countable;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Transport pool that uses strategy pattern for transport-specific behavior.
 */
class TransportPool implements Countable {


    /** @var array<string, PooledConnection> All connections in the pool */
    private array $connections = [];

    /** @var array<string, bool> Track which connections are in use */
    private array $inUse = [];


    /** @param array<string, PoolStrategyInterface> $strategies Strategies by transport type */
    public function __construct( private array $strategies ) {}


    /** Create a pool with the default strategies. */
    public static function default() : self {
        return new self( self::defaultStrategies() );
    }


    /** @return array<string, PoolStrategyInterface> */
    protected static function defaultStrategies() : array {
        return [
            'udp' => new UdpPoolStrategy(),
            'tcp' => new TcpPoolStrategy(),
        ];
    }


    /**
     * Acquire a transport from the pool.
     *
     * @param list<mixed> $options
     */
    public function acquire( string $type, string $host, int $port = 53, array $options = [] ) : TransportInterface {
        $strategy = $this->getStrategy( $type );

        // Check if we should even attempt this transport type for this server
        if ( ! $strategy->shouldAttempt( $host, $port ) ) {
            throw new \RuntimeException(
                "Transport type '{$type}' has previously failed for {$host}:{$port}"
            );
        }

        $key = $strategy->getKey( $host, $port, $options );

        // Try to reuse existing connection
        if ( isset( $this->connections[ $key ] ) && ! isset( $this->inUse[ $key ] ) ) {
            $connection = $this->connections[ $key ];

            if ( $strategy->canReuse( $connection ) ) {
                $this->inUse[ $key ] = true;
                $connection->touch();
                return new PooledTransportWrapper( $connection, $this );
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

        $connection = new PooledConnection( $transport, $key, $type );
        $this->connections[ $key ] = $connection;
        $this->inUse[ $key ] = true;

        return new PooledTransportWrapper( $connection, $this );
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
    public function handleError( PooledTransportWrapper $transport, \Throwable $error ) : void {
        $connection = $transport->getConnection();
        $strategy = $this->getStrategy( $connection->getType() );

        if ( ! $strategy->handleError( $connection, $error ) ) {
            // Strategy says connection is no longer usable
            unset( $this->connections[ $connection->getKey() ] );
            unset( $this->inUse[ $connection->getKey() ] );
        }
    }


    /**
     * Register a pooling strategy for a transport type.
     */
    public function registerStrategy( string $type, PoolStrategyInterface $strategy ) : void {
        $this->strategies[ $type ] = $strategy;
    }


    /**
     * Release a transport back to the pool.
     */
    public function release( TransportInterface $transport ) : void {
        if ( ! $transport instanceof PooledTransportWrapper ) {
            return;
        }

        $key = $transport->getKey();
        unset( $this->inUse[ $key ] );

        // Clean up old connections periodically
        if ( count( $this->connections ) > 10 && mt_rand( 1, 10 ) === 1 ) {
            $this->cleanupStaleConnections();
        }
    }


    /**
     * Remove stale connections based on their strategy's rules.
     */
    private function cleanupStaleConnections() : void {
        foreach ( $this->connections as $key => $connection ) {
            if ( isset( $this->inUse[ $key ] ) ) {
                continue;
            }

            $strategy = $this->getStrategy( $connection->getType() );
            if ( ! $strategy->canReuse( $connection ) ) {
                unset( $this->connections[ $key ] );
            }
        }
    }


    /**
     * Get strategy for a transport type.
     */
    private function getStrategy( string $type ) : PoolStrategyInterface {
        if ( ! isset( $this->strategies[ $type ] ) ) {
            throw new \InvalidArgumentException( "No pooling strategy registered for type: {$type}" );
        }
        return $this->strategies[ $type ];
    }


}