<?php


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
 * @since     File available since Release 1.3.1
 *
 */


/**
 * L64 Resource Record - RFC6742 section 2.3
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |          Preference           |                               |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               +
 *  |                          Locator64                            |
 *  +                               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |                               |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class L64 extends RR {


    /** @var int Preference */
    public int $preference;

    /** @var string Locator64 field */
    public string $locator64;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->preference = (int) array_shift( $i_rData );
        $this->locator64 = array_shift( $i_rData );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->locator64 ) > 0 ) {

            # Break out the locator64.
            $split = explode( ':', $this->locator64 );

            # Pack the data.
            return pack(
                'n5', $this->preference, hexdec( $split[ 0 ] ), hexdec( $split[ 1 ] ),
                hexdec( $split[ 2 ] ), hexdec( $split[ 3 ] )
            );
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the values.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'npreference/n4locator', $this->rdata );

            $this->preference = $parse[ 'preference' ];

            # Build the locator64.
            $this->locator64 = dechex( $parse[ 'locator1' ] ) . ':' .
                dechex( $parse[ 'locator2' ] ) . ':' .
                dechex( $parse[ 'locator3' ] ) . ':' .
                dechex( $parse[ 'locator4' ] );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->preference . ' ' . $this->locator64;
    }


}

