<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\ArrayCache\ArrayCache;
use JDWX\DNSQuery\Message\MessageInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;


/** Implements a caching interface for response packets using a PSR-6/PSR-16 cache interface
 * as the backend.
 */
class MessageCache extends AbstractCache implements MessageCacheInterface {


    /** @var CacheInterface The backend for storing cached data. */
    protected CacheInterface $cache;


    /**
     * Build a new cache, optionally using an existing PSR-6/PSR-16 cache interface.
     *
     * @param ?CacheInterface $i_cache Existing cache interface to use or null to create a simple array cache.
     */
    public function __construct( ?CacheInterface $i_cache = null ) {
        if ( ! $i_cache instanceof CacheInterface ) {
            $i_cache = new ArrayCache();
        }
        $this->cache = $i_cache;
    }


    /**
     * @throws InvalidArgumentException
     */
    public function get( string|MessageInterface $i_key ) : ?MessageInterface {
        if ( $i_key instanceof MessageInterface ) {
            $i_key = self::hash( $i_key );
        }
        return $this->cache->get( $i_key );
    }


    /**
     * @throws InvalidArgumentException
     */
    public function has( string $i_key ) : bool {
        return $this->cache->has( $i_key );
    }


    /**
     * @throws InvalidArgumentException
     */
    protected function putWithTTL( string $i_key, MessageInterface $i_msg, int $i_ttl ) : void {
        $this->cache->set( $i_key, $i_msg, $i_ttl );
    }


}