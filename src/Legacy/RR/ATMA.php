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
 * @since     File available since Release 1.1.0
 *
 */


/**
 * ATMA Resource Record
 *
 *   0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 * +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 * |          FORMAT       |                       |
 * |                       +--+--+--+--+--+--+--+--+
 * /                    ADDRESS                    /
 * |                                               |
 * +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class ATMA extends RR {


    /* @var int Format of address
     *
     * One octet that indicates the format of ADDRESS. The two possible values
     * for FORMAT are value 0 indicating ATM End System Address (AESA) format
     * and value 1 indicating E.164 format
     */
    public int $format;

    /** @var string IPv4 address in quad-dotted notation */
    public string $address;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $value = array_shift( $i_rData );

        if ( ctype_xdigit( $value ) ) {
            $this->format = 0;
            $this->address = $value;
        } elseif ( is_numeric( $value ) ) {
            $this->format = 1;
            $this->address = $value;
        } else {
            return false;
        }

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        $data = chr( $this->format );

        if ( $this->format == 0 ) {
            $data .= pack( 'H*', $this->address );
        } elseif ( $this->format == 1 ) {
            $data .= $this->address;
        } else {
            return null;
        }

        $i_packet->offset += strlen( $data );

        return $data;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the format.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Cformat/N*address', $this->rdata );

            $this->format = $parse[ 'format' ];

            if ( $this->format == 0 ) {
                $address = unpack( '@1/H*address', $this->rdata );
                $this->address = $address[ 'address' ];
            } elseif ( $this->format == 1 ) {
                $this->address = substr( $this->rdata, 1, $this->rdLength - 1 );
            } else {
                return false;
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->address;
    }


}
