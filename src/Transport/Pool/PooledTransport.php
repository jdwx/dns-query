<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use DateTimeImmutable;
use DateTimeInterface;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * A pooled transport that combines connection metadata with transport interception.
 * 
 * This class serves two purposes:
 * 1. Stores metadata about the connection (creation time, usage, errors)
 * 2. Intercepts transport operations to notify the pool of errors
 * 
 * Previously this was split into PooledConnection (metadata) and 
 * PooledTransportWrapper (interception), but they've been merged to
 * eliminate feature envy and simplify the architecture.
 */
class PooledTransport implements TransportInterface {


    private DateTimeInterface $dateCreated;

    private DateTimeInterface $dateLastUsed;

    private int $useCount = 0;

    private int $errorCount = 0;

    private ?TransportPool $pool = null;


    public function __construct(
        private readonly TransportInterface      $transport,
        private readonly string                  $key,
        private readonly string                  $type,
        private readonly PoolStrategyInterface   $strategy
    ) {
        $this->dateCreated = new DateTimeImmutable();
        $this->dateLastUsed = new DateTimeImmutable();
    }


    public function attachToPool( TransportPool $pool ) : void {
        $this->pool = $pool;
    }


    public function detachFromPool() : void {
        $this->pool = null;
    }


    public function getDateCreated() : DateTimeInterface {
        return $this->dateCreated;
    }


    public function getDateLastUsed() : DateTimeInterface {
        return $this->dateLastUsed;
    }


    public function getErrorCount() : int {
        return $this->errorCount;
    }


    public function getIdleSeconds() : int {
        $now = new DateTimeImmutable();
        return $now->getTimestamp() - $this->dateLastUsed->getTimestamp();
    }


    public function getKey() : string {
        return $this->key;
    }


    public function getStrategy() : PoolStrategyInterface {
        return $this->strategy;
    }


    public function getType() : string {
        return $this->type;
    }


    public function getUseCount() : int {
        return $this->useCount;
    }


    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        try {
            return $this->transport->receive( $i_uBufferSize );
        } catch ( \Throwable $e ) {
            $this->handleError( $e );
            throw $e;
        }
    }


    public function recordError() : void {
        $this->errorCount++;
    }


    public function send( string|WriteBufferInterface $i_data ) : void {
        try {
            $this->transport->send( $i_data );
        } catch ( \Throwable $e ) {
            $this->handleError( $e );
            throw $e;
        }
    }


    public function touch() : void {
        $this->dateLastUsed = new DateTimeImmutable();
        $this->useCount++;
    }


    private function handleError( \Throwable $error ) : void {
        if ( $this->pool !== null ) {
            $this->pool->handleError( $this, $error );
        }
    }


}