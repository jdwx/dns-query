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
 * AFSDB Resource Record - RFC1183 section 1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    SUBTYPE                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    HOSTNAME                   /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class AFSDB extends RR {


    /** @var int The AFSDB subtype */
    public int $subtype;

    /** @var string The AFSDB hostname */
    public string $hostname;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->subtype = (int) array_shift( $i_rData );
        $this->hostname = $this->cleanString( array_shift( $i_rData ) );
        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->hostname ) > 0 ) {

            $data = pack( 'n', $this->subtype );
            $i_packet->offset += 2;

            $data .= $i_packet->compress( $this->hostname, $i_packet->offset );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the subtype.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'nsubtype', $this->rdata );

            $this->subtype = $parse[ 'subtype' ];
            $offset = $i_packet->offset + 2;

            $this->hostname = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->subtype . ' ' . $this->cleanString( $this->hostname ) . '.';
    }


}
