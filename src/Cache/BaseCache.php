<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


/** Contains the caching functionality that is independent of what type of cache is being
 * used, but specific to the project needs, like hashing requests into keys and computing
 * the cache TTL of a response packet.
 */
abstract class BaseCache implements ICache {


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
     * @param ResponsePacket $i_rsp Response packet to compute the TTL
     *
     * @return int TTL for the response packet in seconds
     */
    public static function calculateTTL( ResponsePacket $i_rsp ) : int {
        $ttl = 86400 * 365;
        foreach ( $i_rsp->answer as $rr ) {
            if ( $rr->ttl < $ttl ) {
                $ttl = $rr->ttl;
            }
        }

        foreach ( $i_rsp->authority as $rr ) {
            if ( $rr->ttl < $ttl ) {
                $ttl = $rr->ttl;
            }
        }

        foreach ( $i_rsp->additional as $rr ) {
            if ( $rr->ttl < $ttl ) {
                $ttl = $rr->ttl;
            }
        }

        return $ttl;
    }


    /** @inheritDoc */
    public static function hashRequest( RequestPacket $i_rsp ) : string {
        return sha1(
            $i_rsp->question[ 0 ]->qName . '|' . $i_rsp->question[ 0 ]->qType
        );
    }


    /** @inheritDoc */
    public static function isTypeCacheable( string $i_type ) : bool {
        return match ( $i_type ) {
            'AXFR', 'OPT' => false,
            default => true,
        };
    }


    /** @inheritDoc */
    public function getEx( string $i_key ) : ResponsePacket {
        $xx = $this->get( $i_key );
        if ( ! is_null( $xx ) ) {
            return $xx;
        }
        throw new Exception( "Required key not found in cache: $i_key" );
    }


    /** @inheritDoc */
    public function put( string $i_key, ResponsePacket $i_rsp ) : void {

        # Clear the rdata values.
        $i_rsp->rdata = '';
        $i_rsp->rdLength = 0;

        foreach ( $i_rsp->answer as $rr ) {
            $rr->rdata = '';
            $rr->rdLength = 0;
        }
        foreach ( $i_rsp->authority as $rr ) {
            $rr->rdata = '';
            $rr->rdLength = 0;
        }
        foreach ( $i_rsp->additional as $rr ) {
            $rr->rdata = '';
            $rr->rdLength = 0;
        }

        $this->putWithTTL( $i_key, $i_rsp, self::calculateTTL( $i_rsp ) );
    }


    /**
     * Store a response in the cache with a precalculated time-to-live (TTL).
     *
     * @param string         $i_key Key for the new response
     * @param ResponsePacket $i_rsp Response to cache
     * @param int            $i_ttl TTL in seconds to cache this response
     *
     * @return void
     */
    abstract protected function putWithTTL( string $i_key, ResponsePacket $i_rsp, int $i_ttl ) : void;


}