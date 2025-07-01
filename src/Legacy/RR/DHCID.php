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
 * @since     File available since Release 0.6.0
 *
 */


/**
 * DHCID Resource Record - RFC4701 section 3.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  ID Type Code                 |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |       Digest Type     |                       /
 *    +--+--+--+--+--+--+--+--+                       /
 *    /                                               /
 *    /                    Digest                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class DHCID extends RR {


    /** @var int Identifier type */
    public int $idType;

    /** @var int Digest type */
    public int $digestType;

    /** @var string Digest */
    public string $digest;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $data = base64_decode( array_shift( $i_rData ) );
        if ( strlen( $data ) > 0 ) {

            # Unpack the id type and digest type.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'nid_type/Cdigest_type', $data );

            $this->idType = $parse[ 'id_type' ];
            $this->digestType = $parse[ 'digest_type' ];

            # Copy out the digest.
            $this->digest = base64_encode( substr( $data, 3, strlen( $data ) - 3 ) );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->digest ) > 0 ) {

            $data = pack( 'nC', $this->idType, $this->digestType ) .
                base64_decode( $this->digest );

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the id type and digest type.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'nid_type/Cdigest_type', $this->rdata );

            $this->idType = $parse[ 'id_type' ];
            $this->digestType = $parse[ 'digest_type' ];

            # Copy out the digest.
            $this->digest = base64_encode(
                substr( $this->rdata, 3, $this->rdLength - 3 )
            );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = pack( 'nC', $this->idType, $this->digestType );
        $out .= base64_decode( $this->digest );

        return base64_encode( $out );
    }


}
