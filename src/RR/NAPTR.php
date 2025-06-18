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
 * NAPTR Resource Record - RFC2915
 *
 *      0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                     ORDER                     |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                   PREFERENCE                  |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                     FLAGS                     /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                   SERVICES                    /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                    REGEXP                     /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                  REPLACEMENT                  /
 *   /                                               /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class NAPTR extends RR {


    /** @var int Order in which the NAPTR records MUST be processed */
    public int $order;

    /** @var int Specifies the order in which NAPTR records with equal "order"
     * values SHOULD be processed
     */
    public int $preference;

    /** @var string Rewrite flags */
    public string $flags;

    /** @var string Specifies the service(s) available down this rewrite path */
    public string $services;

    /** @var string Regular expression */
    public string $regexp;

    /** @var string The next NAME to query for NAPTR, SRV, or address records
     * depending on the value of the flags field */
    public string $replacement;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->order = (int) array_shift( $i_rData );
        $this->preference = (int) array_shift( $i_rData );

        $data = $this->buildString( $i_rData );
        if ( count( $data ) == 4 ) {

            $this->flags = $data[ 0 ];
            $this->services = $data[ 1 ];
            $this->regexp = $data[ 2 ];
            $this->replacement = $this->cleanString( $data[ 3 ] );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( ( isset( $this->order ) ) && ( strlen( $this->services ) > 0 ) ) {

            $data = pack( 'nn', $this->order, $this->preference );

            $data .= chr( strlen( $this->flags ) ) . $this->flags;
            $data .= chr( strlen( $this->services ) ) . $this->services;
            $data .= chr( strlen( $this->regexp ) ) . $this->regexp;

            $i_packet->offset += strlen( $data );

            $data .= $i_packet->compress( $this->replacement, $i_packet->offset );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the order and preference.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'norder/npreference', $this->rdata );

            $this->order = $parse[ 'order' ];
            $this->preference = $parse[ 'preference' ];

            $offset = $i_packet->offset + 4;

            $this->flags = $i_packet->labelEx( $offset );
            $this->services = $i_packet->labelEx( $offset );
            $this->regexp = $i_packet->labelEx( $offset );

            $this->replacement = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->order . ' ' . $this->preference . ' ' .
            static::formatString( $this->flags ) . ' ' .
            static::formatString( $this->services ) . ' ' .
            static::formatString( $this->regexp ) . ' ' .
            static::cleanString( $this->replacement ) . '.';
    }


}

