<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Packet\Packet;
use JetBrains\PhpStorm\ArrayShape;


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
 * MX Resource Record - RFC1035 section 3.3.9
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  PREFERENCE                   |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   EXCHANGE                    /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class MX extends RR {


    /** @var int Preference for this mail exchanger */
    public int $preference;

    /** @var string The hostname of the mail exchanger. */
    public string $exchange;


    /** @inheritDoc
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[ArrayShape( [ 'pri' => "int", 'target' => "string" ] )] public function getPHPRData() : array {
        return [
            'pri' => $this->preference,
            'target' => $this->exchange,
        ];
    }


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->preference = (int) array_shift( $i_rData );
        $this->exchange = $this->cleanString( array_shift( $i_rData ) );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->exchange ) > 0 ) {

            $data = pack( 'n', $this->preference );
            $i_packet->offset += 2;

            $data .= $i_packet->compress( $this->exchange, $i_packet->offset );
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

            # Get the exchange entry server.
            $offset = $i_packet->offset + 2;
            $this->exchange = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->preference . ' ' . $this->cleanString( $this->exchange ) . '.';
    }
}
