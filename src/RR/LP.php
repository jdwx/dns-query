<?php /** @noinspection PhpClassNamingConventionInspection */


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
 * @since     File available since Release 1.3.1
 *
 */


/**
 * LP Resource Record - RFC6742 section 2.4
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |          Preference           |                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               /
 *  /                                                               /
 *  /                              FQDN                             /
 *  /                                                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class LP extends RR {


    /** @var int Preference */
    public int $preference;

    /** @var string FQDN field */
    public string $fqdn;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->preference = (int) array_shift( $i_rData );
        $this->fqdn = trim( array_shift( $i_rData ), '.' );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->fqdn ) > 0 ) {

            $data = pack( 'n', $this->preference );
            $i_packet->offset += 2;

            $data .= $i_packet->compress( $this->fqdn, $i_packet->offset );
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

            # Get the hostname.
            $this->fqdn = $i_packet->expandEx( $offset );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->preference . ' ' . $this->fqdn . '.';
    }


}
