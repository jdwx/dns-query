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
 * PX Resource Record - RFC2163 section 4
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  PREFERENCE                   |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    MAP822                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    MAP X400                   /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--
 *
 */
class PX extends RR {


    /** @var int Preference */
    public int $preference;

    /** @var string RFC822 part of the MIXER-conformant Global Address Mapping */
    public string $map822;

    /** @var string X.400 part of the MIXER-conformant Global Address Mapping */
    public string $mapX400;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->preference = (int) $i_rData[ 0 ];
        $this->map822 = $this->cleanString( $i_rData[ 1 ] );
        $this->mapX400 = $this->cleanString( $i_rData[ 2 ] );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->map822 ) > 0 ) {

            $data = pack( 'n', $this->preference );
            $i_packet->offset += 2;

            $data .= $i_packet->compress( $this->map822, $i_packet->offset );
            $data .= $i_packet->compress( $this->mapX400, $i_packet->offset );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Parse the preference.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'npreference', $this->rdata );
            $this->preference = $parse[ 'preference' ];

            $offset = $i_packet->offset + 2;

            $this->map822 = $i_packet->expandEx( $offset );
            $this->mapX400 = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->preference . ' ' . $this->cleanString( $this->map822 ) . '. ' .
            $this->cleanString( $this->mapX400 ) . '.';
    }


}
