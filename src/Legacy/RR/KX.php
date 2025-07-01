<?php /** @noinspection PhpClassNamingConventionInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\RR\RR;


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
 * KX Resource Record - RFC2230 section 3.1
 *
 * This class is almost identical to MX, except that the exchanger
 * domain is not compressed, it's added as a label
 *
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                  PREFERENCE                   |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   /                   EXCHANGER                   /
 *   /                                               /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class KX extends RR {


    /** @var int Preference for this mail exchanger */
    public int $preference;

    /** @var string Hostname of the mail exchanger */
    public string $exchange;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->preference = (int) array_shift( $i_rData );
        $this->exchange = $this->cleanString( array_shift( $i_rData ) );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->exchange ) > 0 ) {

            $data = pack( 'nC', $this->preference, strlen( $this->exchange ) ) .
                $this->exchange;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Parse the preference
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'npreference', $this->rdata );
            $this->preference = $parse[ 'preference' ];

            # Get the exchange entry server.
            $offset = $i_packet->offset + 2;
            $this->exchange = $i_packet->labelEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->preference . ' ' . $this->cleanString( $this->exchange ) . '.';
    }


}
