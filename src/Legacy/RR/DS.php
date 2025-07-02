<?php /** @noinspection PhpClassNamingConventionInspection */


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
 * DS Resource Record - RFC4034 section 5.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |           Key Tag             |  Algorithm    |  Digest Type  |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Digest                             /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class DS extends RR {


    /** @var int Key Tag */
    public int $keytag;

    /** @var int Algorithm number */
    public int $algorithm;

    /** @var int Algorithm used to construct the digest */
    public int $digestType;

    /** @var string Digest data */
    public string $digest;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->keytag = (int) array_shift( $i_rData );
        $this->algorithm = (int) array_shift( $i_rData );
        $this->digestType = (int) array_shift( $i_rData );
        $this->digest = implode( '', $i_rData );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->digest ) > 0 ) {

            $data = pack( 'nCCH*', $this->keytag, $this->algorithm, $this->digestType, $this->digest );

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the keytag, algorithm and digest type.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'nkeytag/Calgorithm/CdigestType/H*digest', $this->rdata );

            $this->keytag = $parse[ 'keytag' ];
            $this->algorithm = $parse[ 'algorithm' ];
            $this->digestType = $parse[ 'digestType' ];
            $this->digest = $parse[ 'digest' ];

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->keytag . ' ' . $this->algorithm . ' ' . $this->digestType . ' ' . $this->digest;
    }


}
