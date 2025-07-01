<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\RR;


use JDWX\DNSQuery\Legacy\Packet\Packet;


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
 * @since     File available since Release 1.0.1
 *
 */


/**
 * WKS Resource Record - RFC1035 section 3.4.2
 *
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |                    ADDRESS                    |
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *   |       PROTOCOL        |                       |
 *   +--+--+--+--+--+--+--+--+                       |
 *   |                                               |
 *   /                   <BIT MAP>                   /
 *   /                                               /
 *   +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class WKS extends RR {


    /** @var string IP address of the service */
    public string $address;

    /** @var int Protocol of the service */
    public int $protocol;

    /** @var int[] bitmap */
    public array $bitmap = [];


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->address = strtolower( trim( array_shift( $i_rData ), '.' ) );
        $this->protocol = (int) array_shift( $i_rData );
        $this->bitmap = array_map( intval( ... ), $i_rData );
        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->address ) > 0 ) {

            $data = pack( 'NC', ip2long( $this->address ), $this->protocol );

            $ports = [];

            $maxPort = 0;
            foreach ( $this->bitmap as $port ) {
                $ports[ $port ] = 1;

                if ( $port > $maxPort ) {
                    $maxPort = $port;
                }
            }
            for ( $ii = 0 ; $ii < ceil( $maxPort / 8 ) * 8 ; $ii++ ) {
                if ( ! isset( $ports[ $ii ] ) ) {
                    $ports[ $ii ] = 0;
                }
            }

            ksort( $ports );

            $string = '';
            $maxPort = 0;

            foreach ( $ports as $port ) {

                $string .= $port;
                $maxPort++;

                if ( $maxPort == 8 ) {

                    $data .= chr( bindec( $string ) );
                    $string = '';
                    $maxPort = 0;
                }
            }

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Get the address and protocol value.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Naddress/Cprotocol', $this->rdata );

            $this->address = long2ip( $parse[ 'address' ] );
            $this->protocol = $parse[ 'protocol' ];

            # Unpack the port list bitmap.
            $port = 0;
            foreach ( unpack( '@5/C*', $this->rdata ) as $set ) {

                $bitString = sprintf( '%08b', $set );

                for ( $ii = 0 ; $ii < 8 ; $ii++, $port++ ) {
                    if ( $bitString[ $ii ] == '1' ) {
                        $this->bitmap[] = $port;
                    }
                }
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $data = $this->address . ' ' . $this->protocol;

        foreach ( $this->bitmap as $port ) {
            $data .= ' ' . $port;
        }

        return $data;
    }


}
