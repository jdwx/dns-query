<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use Cache\Adapter\PHPArray\ArrayCachePool;
use JDWX\DNSQuery\Packet\ResponsePacket;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;


class Cache extends BaseCache {


    protected CacheInterface $cache;


    public function __construct( ?CacheInterface $i_cache = null ) {
        if ( ! $i_cache instanceof CacheInterface ) {
            $i_cache = new ArrayCachePool();
        }
        $this->cache = $i_cache;
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
    public function get( string $i_key ) : ?ResponsePacket {
        return $this->cache->get( $i_key );
    }


    /**
     * @throws InvalidArgumentException
     */
    protected function putWithTTL( string $i_key, ResponsePacket $i_data, int $i_ttl ) : void {
        $this->cache->set( $i_key, $i_data, $i_ttl );
    }


}