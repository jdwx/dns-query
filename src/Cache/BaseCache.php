<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


abstract class BaseCache implements ICache {


    public function getEx( string $i_key ) : ResponsePacket {
        $xx = $this->get( $i_key );
        if ( ! is_null( $xx ) ) {
            return $xx;
        }
        throw new Exception( "Required key not found in cache: $i_key" );
    }


    public function put( string $i_key, ResponsePacket $i_data ) : void {

        $ttl = 86400 * 365;

        //
        // clear the rdata values
        //
        $i_data->rdata = '';
        $i_data->rdLength = 0;

        //
        // find the lowest TTL, and use that as the TTL for the cached
        // object. The downside to using one TTL for the whole object, is that
        // we'll invalidate entries before they actually expire, causing a
        // real lookup to happen.
        //
        // The upside is that we don't need to require() each RR type in the
        // cache, so we can look at their individual TTLs on each run. we only
        // unserialize the actual RR object when it's get() from the cache.
        //
        foreach ( $i_data->answer as $rr ) {

            if ($rr->ttl < $ttl) {
                $ttl = $rr->ttl;
            }

            $rr->rdata = '';
            $rr->rdLength = 0;
        }
        foreach ( $i_data->authority as $rr ) {

            if ($rr->ttl < $ttl) {
                $ttl = $rr->ttl;
            }

            $rr->rdata = '';
            $rr->rdLength = 0;
        }
        foreach ( $i_data->additional as $rr ) {

            if ($rr->ttl < $ttl) {
                $ttl = $rr->ttl;
            }

            $rr->rdata = '';
            $rr->rdLength = 0;
        }

        $this->putWithTTL( $i_key, $i_data, $ttl );
    }


    abstract protected function putWithTTL( string $i_key, ResponsePacket $i_data, int $i_ttl ) : void;


    /**
     * a simple function to determine if the RR type is cacheable
     *
     * @param string $i_type the RR type string
     *
     * @return bool returns true/false if the RR type if cacheable
     * @access public
     *
     */
    public static function isTypeCacheable( string $i_type ) : bool {
        return match ( $i_type ) {
            'AXFR', 'OPT' => false,
            default => true,
        };
    }


    public static function hashRequest( RequestPacket $i_packet ) : string {
        return sha1(
            $i_packet->question[0]->qname . '|' . $i_packet->question[0]->qtype
        );
    }


}