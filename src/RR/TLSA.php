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
 * @since     File available since Release 1.2.5
 *
 */


/**
 * TLSA Resource Record - RFC 6698
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Cert. Usage  |   Selector    | Matching Type |               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+               /
 *  /                                                               /
 *  /                 Certificate Association Data                  /
 *  /                                                               /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class TLSA extends RR {


    /** @var int Certificate Usage Field */
    public int $certUsage;

    /** @var int Selector Field */
    public int $selector;

    /** @var int Matching Type Field */
    public int $matchingType;

    /** @var string Certificate Association Data Field */
    public string $certificate;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->certUsage = (int) array_shift( $i_rData );
        $this->selector = (int) array_shift( $i_rData );
        $this->matchingType = (int) array_shift( $i_rData );
        $this->certificate = base64_decode( implode( '', $i_rData ) );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->certificate ) > 0 ) {

            $data = pack(
                    'CCC', $this->certUsage, $this->selector, $this->matchingType
                ) . $this->certificate;

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the format, keytag and algorithm.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Cusage/Cselector/Ctype', $this->rdata );

            $this->certUsage = $parse[ 'usage' ];
            $this->selector = $parse[ 'selector' ];
            $this->matchingType = $parse[ 'type' ];

            # Copy the certificate.
            $this->certificate = substr( $this->rdata, 3, $this->rdLength - 3 );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->certUsage . ' ' . $this->selector . ' ' .
            $this->matchingType . ' ' . base64_encode( $this->certificate );
    }


}
