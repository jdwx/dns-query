<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport\Pool;


use DateTimeImmutable;
use DateTimeInterface;
use JDWX\DNSQuery\Transport\TransportInterface;


/**
 * Represents a pooled connection with its metadata.
 */
class PooledConnection {


    private DateTimeInterface $dateCreated;

    private DateTimeInterface $dateLastUsed;

    private int $useCount = 0;

    private int $errorCount = 0;


    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string             $key,
        private readonly string             $type
    ) {
        $this->dateCreated = new DateTimeImmutable();
        $this->dateLastUsed = new DateTimeImmutable();
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


    public function getTransport() : TransportInterface {
        return $this->transport;
    }


    public function getType() : string {
        return $this->type;
    }


    public function getUseCount() : int {
        return $this->useCount;
    }


    public function recordError() : void {
        $this->errorCount++;
    }


    public function touch() : void {
        $this->dateLastUsed = new DateTimeImmutable();
        $this->useCount++;
    }


}