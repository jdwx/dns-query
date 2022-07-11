<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


interface ICache {


    /**
     * returns true/false if the provided key is defined in the cache
     *
     * @param string $i_key the key to lookup in the local cache
     *
     * @return bool
     * @access public
     *
     */
    public function has( string $i_key ) : bool;


    /**
     * returns the value for the given key
     *
     * @param string $i_key the key to lookup in the local cache
     *
     * @return ?ResponsePacket returns the cached response on success, null on error
     * @access public
     *
     */
    public function get( string $i_key ) : ?ResponsePacket;

    /**
     * returns the value for the given key or throws an exception if the key
     * is not defined in the cache
     *
     * @param string $i_key the key to lookup in the local cache
     *
     * @return ResponsePacket returns the cached response
     * @access public
     *
     * @throws Exception
     */
    public function getEx( string $i_key ) : ResponsePacket;

    /**
     * adds a new key/value pair to the cache
     *
     * @param string          $i_key  the key for the new cache entry
     * @param ResponsePacket  $i_data the packet to store in cache
     *
     * @return void
     * @access public
     *
     * @throws Exception
     */
    public function put( string $i_key, ResponsePacket $i_data) : void;

    public static function hashRequest( RequestPacket $i_packet ) : string;

    public static function isTypeCacheable( string $i_type ) : bool;


}

