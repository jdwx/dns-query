<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Message\Message;


/** Contains the caching functionality that is independent of what type of cache is being
 * used, but specific to the project needs, like hashing requests into keys and computing
 * the cache TTL of a response packet.
 */
abstract class AbstractCache implements MessageCacheInterface {


    /**
     * Compute the TTL for a response packet.
     *
     * Uses the lowest TTL of any RR in the response.
     *
     * The downside to using one TTL for the whole object, is that
     * we'll invalidate entries before they actually expire, causing a
     * real lookup to happen.
     *
     * The upside is that we don't need to require() each RR type in the
     * cache, so we can look at their individual TTLs on each run. we only
     * unserialize the actual RR object when it's get() from the cache.
     *
     * @param Message $i_msg Response packet to compute the TTL
     *
     * @return int TTL for the response packet in seconds
     */
    public static function calculateTTL( Message $i_msg ) : int {
        $ttl = 86400 * 365;
        foreach ( $i_msg->answer as $rr ) {
            if ( $rr->ttl < $ttl ) {
                $ttl = $rr->ttl;
            }
        }

        foreach ( $i_msg->authority as $rr ) {
            if ( $rr->ttl < $ttl ) {
                $ttl = $rr->ttl;
            }
        }

        foreach ( $i_msg->additional as $rr ) {
            if ( $rr->ttl < $ttl ) {
                $ttl = $rr->ttl;
            }
        }

        return $ttl;
    }


    /** @inheritDoc */
    public static function hashRequest( Message $i_msg ) : string {
        $st = '';
        foreach ( $i_msg->question as $q ) {
            $st .= "{$q->stName}|{$q->type->value}|{$q->class->value}&";
        }
        return hash( 'sha256', $st );
    }


    /** @inheritDoc */
    public static function isTypeCacheable( int|string|RecordType $i_type ) : bool {
        $i_type = RecordType::normalize( $i_type );
        return match ( $i_type ) {
            RecordType::AXFR, RecordType::OPT => false,
            default => true,
        };
    }


    /** @inheritDoc */
    public function getEx( string $i_key ) : Message {
        $xx = $this->get( $i_key );
        if ( ! is_null( $xx ) ) {
            return $xx;
        }
        throw new Exception( "Required key not found in cache: {$i_key}" );
    }


    /** @inheritDoc */
    public function put( string $i_key, Message $i_msg ) : void {
        $this->putWithTTL( $i_key, $i_msg, self::calculateTTL( $i_msg ) );
    }


    /**
     * Store a response in the cache with a precalculated time-to-live (TTL).
     *
     * @param string $i_key Key for the new response
     * @param Message $i_msg Response to cache
     * @param int $i_ttl TTL in seconds to cache this response
     *
     * @return void
     */
    abstract protected function putWithTTL( string $i_key, Message $i_msg, int $i_ttl ) : void;


}