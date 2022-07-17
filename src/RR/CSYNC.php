<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\BitMap;
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
 * @since     File available since Release 1.4.1
 *
 */


/**
 * CSYNC Resource Record - RFC 7477 section 2.1.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  SOA Serial                   |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    Flags                      |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                 Type Bit Map                  /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class CSYNC extends RR {


    /** @var int Serial number */
    public int $serial;

    /** @var int Flags */
    public int $flags;

    /** @var string[] array of RR type names */
    public array $typeBitMaps = [];


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->serial = (int) array_shift( $i_rData );
        $this->flags = (int) array_shift( $i_rData );

        $this->typeBitMaps = $i_rData;

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {

        # Pack the serial and flags values
        $data = pack( 'Nn', $this->serial, $this->flags );

        # Convert the array of RR names to a type bitmap.
        $data .= BitMap::arrayToBitMap( $this->typeBitMaps );

        # Advance the offset.
        $i_packet->offset += strlen( $data );

        return $data;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {
            # Unpack the serial and flags values
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( '@' . $i_packet->offset . '/Nserial/nflags', $i_packet->rdata );

            $this->serial = $parse[ 'serial' ];
            $this->flags = $parse[ 'flags' ];

            # Parse out the RR bitmap.
            $this->typeBitMaps = BitMap::bitMapToArray(
                substr( $this->rdata, 6 )
            );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->serial . ' ' . $this->flags;

        # Show the RRs.
        foreach ( $this->typeBitMaps as $rr ) {
            $out .= ' ' . strtoupper( $rr );
        }

        return $out;
    }


}
