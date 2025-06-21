<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


/** The interface for response packet caching. */
interface ICache {


    /** Create cache hash key for a request packet.
     *
     * @param RequestPacket $i_rsp Request packet to hash
     *
     * @return string The hashed key
     */
    public static function hashRequest( RequestPacket $i_rsp ) : string;


    /**
     * Determine if an RR type is cacheable by this implementation.
     *
     * @param string $i_type The RR type string (e.g. "A" or "OPT")
     *
     * @return bool True if the RR type is cacheable, otherwise false
     */
    public static function isTypeCacheable( string $i_type ) : bool;


    /**
     * Retrieves a cached response packet based on the provided key.
     *
     * @param string $i_key Key to look up in the cache
     *
     * @return ?ResponsePacket The cached response if found, otherwise null
     */
    public function get( string $i_key ) : ?ResponsePacket;


    /**
     * Same as get() but throws an exception if the key is not found.
     *
     * @param string $i_key Key to look up in the local cache
     *
     * @return ResponsePacket The cached response
     *
     * @throws Exception If the key is not found
     */
    public function getEx( string $i_key ) : ResponsePacket;


    /**
     * See if the cache has a given key.
     *
     * @param string $i_key Key to look up in the cache
     *
     * @return bool True if the provided key is defined in the cache, otherwise false.
     *
     */
    public function has( string $i_key ) : bool;


    /**
     * Add a new key/response pair to the cache
     *
     * @param string $i_key Key for the new response
     * @param ResponsePacket $i_rsp Response to store in cache
     *
     * @return void
     */
    public function put( string $i_key, ResponsePacket $i_rsp ) : void;


}

