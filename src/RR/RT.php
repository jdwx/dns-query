<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Packet\Packet;


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 0.6.0
 *
 */


/**
 * RT Resource Record - RFC1183 section 3.3
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                preference                     |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /             intermediate-host                 /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class RT extends RR {


    /** @var int Preference of this route */
    public int $preference;

    /** @var string Host which will serve as an intermediate in reaching the owner host */
    public string $intermediateHost;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->preference = (int) $i_rData[ 0 ];
        $this->intermediateHost = $this->cleanString( $i_rData[ 1 ] );
        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->intermediateHost ) > 0 ) {

            $data = pack( 'n', $this->preference );
            $i_packet->offset += 2;

            $data .= $i_packet->compress( $this->intermediateHost, $i_packet->offset );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the preference.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'npreference', $this->rdata );

            $this->preference = $parse[ 'preference' ];
            $offset = $i_packet->offset + 2;

            $this->intermediateHost = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->preference . ' ' .
            $this->cleanString( $this->intermediateHost ) . '.';
    }


}
