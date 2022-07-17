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
 * @since     File available since Release 0.6.0
 *
 */


/**
 * NSEC Resource Record - RFC3845 section 2.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                      Next Domain Name                         /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                   List of Type Bit Map(s)                     /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class NSEC extends RR {


    /** @var string The next owner name */
    public string $nextDomainName;

    /** @var string[] Identifies the RR set types that exist at the NSEC RR owner name */
    public array $typeBitMaps = [];


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->nextDomainName = $this->cleanString( array_shift( $i_rData ) );
        $this->typeBitMaps = $i_rData;

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->nextDomainName ) > 0 ) {

            $data = $i_packet->compress( $this->nextDomainName, $i_packet->offset );
            $bitmap = BitMap::arrayToBitMap( $this->typeBitMaps );

            $i_packet->offset += strlen( $bitmap );

            return $data . $bitmap;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Expand the next domain name.
            $offset = $i_packet->offset;
            $this->nextDomainName = $i_packet->expandEx( $offset );

            # Parse out the RRs from the bitmap.
            $this->typeBitMaps = BitMap::bitMapToArray(
                substr( $this->rdata, $offset - $i_packet->offset )
            );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $data = $this->cleanString( $this->nextDomainName ) . '.';

        foreach ( $this->typeBitMaps as $rr ) {
            $data .= ' ' . $rr;
        }

        return $data;
    }


}
