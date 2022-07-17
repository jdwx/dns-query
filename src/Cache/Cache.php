<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use Cache\Adapter\PHPArray\ArrayCachePool;
use JDWX\DNSQuery\Packet\ResponsePacket;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;


/** Implements a caching interface for response packets using a PSR-6/PSR-16 cache interface
 * as the backend.
 */
class Cache extends BaseCache implements ICache {


    /** @var CacheInterface The backend for storing cached data. */
    protected CacheInterface $cache;


    /**
     * Build a new cache, optionally using an existing PSR-6/PSR-16 cache interface.
     *
     * @param ?CacheInterface $i_cache Existing cache interface to use or null to create a simple array cache.
     */
    public function __construct( ?CacheInterface $i_cache = null ) {
        if ( ! $i_cache instanceof CacheInterface ) {
            $i_cache = new ArrayCachePool();
        }
        $this->cache = $i_cache;
    }


    /**
     * @throws InvalidArgumentException
     */
    public function get( string $i_key ) : ?ResponsePacket {
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
    protected function putWithTTL( string $i_key, ResponsePacket $i_rsp, int $i_ttl ) : void {
        $this->cache->set( $i_key, $i_rsp, $i_ttl );
    }


}