<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\BitMap;
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
 * @since     File available since Release 0.6.0
 *
 */


/**
 * NSEC3 Resource Record - RFC5155 section 3.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   Hash Alg.   |     Flags     |          Iterations           |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Salt Length  |                     Salt                      /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Hash Length  |             Next Hashed Owner Name            /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  /                         Type Bit Maps                         /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class NSEC3 extends RR {


    /** @var int ID of algorithm to use */
    public int $algorithm;

    /** @var int Flags */
    public int $flags;

    /** @var int Defines the number of additional times the hash is performed */
    public int $iterations;

    /** @var int Length of the salt (not displayed) */
    public int $saltLength;

    /** @var string The salt */
    public string $salt;

    /** @var int Length of the hash value */
    public int $hashLength;

    /** @var string Hashed value of the owner name */
    public string $hashedOwnerName;

    /** @var string[] array of RR type names */
    public array $typeBitMaps = [];


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->algorithm = (int) array_shift( $i_rData );
        $this->flags = (int) array_shift( $i_rData );
        $this->iterations = (int) array_shift( $i_rData );

        # An empty salt is represented as '-' per RFC5155 section 3.3
        $salt = array_shift( $i_rData );
        if ( $salt == '-' ) {

            $this->saltLength = 0;
            $this->salt = '';
        } else {

            $this->saltLength = strlen( pack( 'H*', $salt ) );
            $this->salt = strtoupper( $salt );
        }

        $this->hashedOwnerName = array_shift( $i_rData );
        $this->hashLength = strlen( base64_decode( $this->hashedOwnerName ) );

        $this->typeBitMaps = $i_rData;

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : string {

        # Pull the salt and build the length.
        $salt = pack( 'H*', $this->salt );
        $this->saltLength = strlen( $salt );

        # Pack the algorithm, flags, iterations and salt length.
        $data = pack(
            'CCnC',
            $this->algorithm, $this->flags, $this->iterations, $this->saltLength
        );
        $data .= $salt;

        # Append the hash length and hash.
        $data .= chr( $this->hashLength );
        if ( $this->hashLength > 0 ) {
            $data .= base64_decode( $this->hashedOwnerName );
        }

        # Convert the array of RR names to a type bitmap.
        $data .= BitMap::arrayToBitMap( $this->typeBitMaps );

        $i_packet->offset += strlen( $data );

        return $data;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack the first values.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( 'Calgorithm/Cflags/niterations/CsaltLength', $this->rdata );

            $this->algorithm = $parse[ 'algorithm' ];
            $this->flags = $parse[ 'flags' ];
            $this->iterations = $parse[ 'iterations' ];
            $this->saltLength = $parse[ 'saltLength' ];

            $offset = 5;

            if ( $this->saltLength > 0 ) {

                $parse = unpack( 'H*', substr( $this->rdata, $offset, $this->saltLength ) );
                $this->salt = strtoupper( $parse[ 1 ] );
                $offset += $this->saltLength;
            }

            # Unpack the hash length.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack( '@' . $offset . '/ChashLength', $this->rdata );
            $offset++;

            # Copy out the hash.
            $this->hashLength = $parse[ 'hashLength' ];
            if ( $this->hashLength > 0 ) {

                $this->hashedOwnerName = base64_encode(
                    substr( $this->rdata, $offset, $this->hashLength )
                );
                $offset += $this->hashLength;
            }

            # Parse out the RR bitmap.
            $this->typeBitMaps = BitMap::bitMapToArray(
                substr( $this->rdata, $offset )
            );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->algorithm . ' ' . $this->flags . ' ' . $this->iterations . ' ';

        # Per RFC5155, the salt_length value isn't displayed, and if the salt
        # is empty, the salt is displayed as a hyphen.
        if ( $this->saltLength > 0 ) {
            $out .= $this->salt;
        } else {
            $out .= '-';
        }

        # Per RFC5255 the hash length isn't shown.
        $out .= ' ' . $this->hashedOwnerName;

        # Show the RRs.
        foreach ( $this->typeBitMaps as $rr ) {
            $out .= ' ' . strtoupper( $rr );
        }

        return $out;
    }


}
