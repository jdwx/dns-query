<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Question\QuestionInterface;


/** The interface for response packet caching. */
interface MessageCacheInterface {


    /** Create cache hash key for a request packet.
     *
     * @param MessageInterface|QuestionInterface $i_msg Request message to hash
     *
     * @return string The hashed key
     */
    public static function hash( MessageInterface|QuestionInterface $i_msg ) : string;


    /**
     * Determine if an RR type is cacheable by this implementation.
     *
     * @param int|string|MessageInterface|QuestionInterface|RecordType $i_type The RR type (e.g. "A" or "OPT")
     *
     * @return bool True if the RR type is cacheable, otherwise false
     */
    public static function isTypeCacheable( int|string|MessageInterface|QuestionInterface|RecordType $i_type ) : bool;


    /**
     * Retrieves a cached response packet based on the provided key.
     *
     * @param string|MessageInterface $i_key Key to look up in the cache
     *
     * @return ?Message The cached response if found, otherwise null
     */
    public function get( string|MessageInterface $i_key ) : ?MessageInterface;


    /**
     * Same as get() but throws an exception if the key is not found.
     *
     * @param string $i_key Key to look up in the local cache
     *
     * @return MessageInterface The cached response
     *
     * @throws Exception If the key is not found
     */
    public function getEx( string $i_key ) : MessageInterface;


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
     * @param MessageInterface $i_msg Response to store in cache
     *
     * @return void
     */
    public function put( string $i_key, MessageInterface $i_msg ) : void;


}

