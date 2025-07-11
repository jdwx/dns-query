<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Wrapper that intercepts transport operations to handle pooling concerns.
 */
readonly class PooledTransportWrapper implements TransportInterface {


    public function __construct(
        private PooledConnection $connection,
        private TransportPool    $pool
    ) {}


    public function getConnection() : PooledConnection {
        return $this->connection;
    }


    public function getKey() : string {
        return $this->connection->getKey();
    }


    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        try {
            return $this->connection->getTransport()->receive( $i_uBufferSize );
        } catch ( \Throwable $e ) {
            $this->pool->handleError( $this, $e );
            throw $e;
        }
    }


    public function send( string|WriteBufferInterface $i_data ) : void {
        try {
            $this->connection->getTransport()->send( $i_data );
        } catch ( \Throwable $e ) {
            $this->pool->handleError( $this, $e );
            throw $e;
        }
    }


}