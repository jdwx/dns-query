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
 * @since     File available since Release 1.3.2
 *
 */


/**
 * EUI48 Resource Record - RFC7043 section 3.1
 *
 *  0                   1                   2                   3
 *  0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 * |                          EUI-48 Address                       |
 * |                               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 * |                               |
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class EUI48 extends RR {


    /** @var string EUI48 address, in hex format */
    public string $address;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $value = array_shift( $i_rData );

        # Re: RFC 7043, the field must be represented as six two-digit hex numbers
        # separated by hyphens.
        $eui = explode( '-', $value );
        if ( count( $eui ) != 6 ) {
            return false;
        }

        # Make sure they're all hex values.
        foreach ( $eui as $hex ) {
            if ( ! ctype_xdigit( $hex ) ) {
                return false;
            }
        }

        # Store it.
        $this->address = strtolower( $value );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        $data = '';

        $eui = explode( '-', $this->address );
        foreach ( $eui as $hex ) {
            $data .= chr( hexdec( $hex ) );
        }

        $i_packet->offset += 6;
        return $data;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {
            $parse = unpack( 'C6', $this->rdata );
            if ( count( $parse ) == 6 ) {
                $this->address = vsprintf( '%02x-%02x-%02x-%02x-%02x-%02x', $parse );
                return true;
            }
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->address;
    }


}
