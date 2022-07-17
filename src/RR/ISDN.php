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
 * ISDN Resource Record - RFC1183 section 3.2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    ISDN-address               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    SA                         /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class ISDN extends RR {


    /** @var string ISDN Number */
    public string $isdnAddress;

    /** @var string Sub-Address */
    public string $sa;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $data = $this->buildString( $i_rData );
        if ( count( $data ) >= 1 ) {

            $this->isdnAddress = $data[ 0 ];
            if ( isset( $data[ 1 ] ) ) {
                $this->sa = $data[ 1 ];
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->isdnAddress ) > 0 ) {

            $data = chr( strlen( $this->isdnAddress ) ) . $this->isdnAddress;
            if ( ! empty( $this->sa ) ) {
                $data .= chr( strlen( $this->sa ) );
                $data .= $this->sa;
            }

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            $this->isdnAddress = $i_packet->labelEx( $i_packet->offset );

            # Look for a SA (sub address) - it's optional.
            if ( ( strlen( $this->isdnAddress ) + 1 ) < $this->rdLength ) {
                $this->sa = $i_packet->labelEx( $i_packet->offset );
            } else {
                $this->sa = '';
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->formatString( $this->isdnAddress ) . ' ' .
            $this->formatString( $this->sa );
    }


}
