<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use JDWX\DNSQuery\Packet\Packet;
use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\PrivateKey;


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
 * This file contains code based off the Net::DNS::SEC Perl module by Olaf M. Kolkman
 *
 * This is the copyright notice from the PERL Net::DNS::SEC module:
 *
 * Copyright (c) 2001 - 2005  RIPE NCC.  Author Olaf M. Kolkman
 * Copyright (c) 2007 - 2008  NLnet Labs.  Author Olaf M. Kolkman
 * <olaf@net-dns.org>
 *
 * All Rights Reserved
 *
 * Permission to use, copy, modify, and distribute this software and its
 * documentation for any purpose and without fee is hereby granted,
 * provided that the above copyright notice appear in all copies and that
 * both that copyright notice and this permission notice appear in
 * supporting documentation, and that the name of the author not be
 * used in advertising or publicity pertaining to distribution of the
 * software without specific, written prior permission.
 *
 * THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE, INCLUDING
 * ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS; IN NO EVENT SHALL
 * AUTHOR BE LIABLE FOR ANY SPECIAL, INDIRECT OR CONSEQUENTIAL DAMAGES OR ANY
 * DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN
 * AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
 */


/**
 * SIG Resource Record - RFC2535 section 4.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |        Type Covered           |  Algorithm    |     Labels    |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |                         Original TTL                          |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |                      Signature Expiration                     |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |                      Signature Inception                      |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |            Key Tag            |                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+         Signer's Name         /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                            Signature                          /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class SIG extends RR {


    /** @var ?PrivateKey Instance of a PrivateKey object */
    public ?PrivateKey $privateKey = null;

    /** @var string RR type covered by this signature */
    public string $typeCovered;

    /** @var int Algorithm used for the signature */
    public int $algorithm;

    /** @var int Number of labels in the name */
    public int $labels;

    /** @var int Original TTL */
    public int $origTTL;

    /** @var string Signature expiration */
    public string $sigExpiration;

    /** @var string Inception of the signature */
    public string $sigInception;

    /** @var int Keytag used */
    public int $keytag;

    /** @var string Signer's name */
    public string $signName;

    /** @var string Signature */
    public string $signature;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {
        $this->typeCovered = strtoupper( array_shift( $i_rData ) );
        $this->algorithm = (int) array_shift( $i_rData );
        $this->labels = (int) array_shift( $i_rData );
        $this->origTTL = (int) array_shift( $i_rData );
        $this->sigExpiration = array_shift( $i_rData );
        $this->sigInception = array_shift( $i_rData );
        $this->keytag = (int) array_shift( $i_rData );
        $this->signName = $this->cleanString( array_shift( $i_rData ) );

        $this->signature = '';
        foreach ( $i_rData as $line ) {
            $this->signature .= $line;
        }

        $this->signature = trim( $this->signature );

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        # Parse the values out of the dates
        preg_match(
            '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $this->sigExpiration, $exp
        );
        preg_match(
            '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $this->sigInception, $inc
        );

        # Pack the value
        /** @noinspection SpellCheckingInspection */
        $data = pack(
            'nCCNNNn',
            Lookups::$rrTypesByName[ $this->typeCovered ],
            $this->algorithm,
            $this->labels,
            $this->origTTL,
            gmmktime( (int) $exp[ 4 ], (int) $exp[ 5 ], (int) $exp[ 6 ], (int) $exp[ 2 ], (int) $exp[ 3 ], (int) $exp[ 1 ] ),
            gmmktime( (int) $inc[ 4 ], (int) $inc[ 5 ], (int) $inc[ 6 ], (int) $inc[ 2 ], (int) $inc[ 3 ], (int) $inc[ 1 ] ),
            $this->keytag
        );

        # The signer name is special; it's not allowed to be compressed
        # (see section 3.1.7).
        $names = explode( '.', strtolower( $this->signName ) );
        foreach ( $names as $name ) {
            $data .= chr( strlen( $name ) );
            $data .= $name;
        }

        $data .= chr( 0 );

        # If the signature is empty, and $this->private_key is an instance of a
        # private key object, and we have access to openssl, then assume this
        # is a SIG(0), and generate a new signature.
        if ( ( strlen( $this->signature ) == 0 )
            && ( $this->privateKey instanceof PrivateKey )
            && ( extension_loaded( 'openssl' ) === true )
        ) {

            # Create a new packet for the signature.
            $newPacket = new RequestPacket( 'example.com', 'SOA', 'IN' );

            # Copy the packet data over.
            $newPacket->copy( $i_packet );

            # Remove the SIG object from the additional list.
            array_pop( $newPacket->additional );
            $newPacket->header->arCount = count( $newPacket->additional );

            # Copy out the data.
            $sigData = $data . $newPacket->get();

            # Based on the algorithm
            $algorithm = match ( $this->algorithm ) {
                Lookups::DNSSEC_ALGORITHM_RSAMD5 => OPENSSL_ALGO_MD5,
                Lookups::DNSSEC_ALGORITHM_RSASHA1 => OPENSSL_ALGO_SHA1,
                Lookups::DNSSEC_ALGORITHM_RSASHA256 => OPENSSL_ALGO_SHA256,
                Lookups::DNSSEC_ALGORITHM_RSASHA512 => OPENSSL_ALGO_SHA512,
                default => throw new Exception(
                    'invalid or unsupported algorithm',
                    Lookups::E_OPENSSL_INV_ALGO
                ),
            };

            # Sign the data.
            if ( ! openssl_sign( $sigData, $this->signature, $this->privateKey->instance, $algorithm ) ) {

                throw new Exception(
                    openssl_error_string(),
                    Lookups::E_OPENSSL_ERROR
                );
            }

            # Build the signature value.
            switch ( $this->algorithm ) {

                # RSA- add it directly.
                case Lookups::DNSSEC_ALGORITHM_RSAMD5:
                case Lookups::DNSSEC_ALGORITHM_RSASHA1:
                case Lookups::DNSSEC_ALGORITHM_RSASHA256:
                case Lookups::DNSSEC_ALGORITHM_RSASHA512:

                    $this->signature = base64_encode( $this->signature );
                    break;
            }
        }

        # Add the signature.
        $data .= base64_decode( $this->signature );

        $i_packet->offset += strlen( $data );

        return $data;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack(
                'ntc/Calgorithm/Clabels/NorigTTL/NsigExp/NsigInc/nkeytag',
                $this->rdata
            );

            $this->typeCovered = Lookups::$rrTypesById[ $parse[ 'tc' ] ];
            $this->algorithm = $parse[ 'algorithm' ];
            $this->labels = $parse[ 'labels' ];
            $this->origTTL = $parse[ 'origTTL' ];

            # The dates are in GM time.
            $this->sigExpiration = gmdate( 'YmdHis', $parse[ 'sigExp' ] );
            $this->sigInception = gmdate( 'YmdHis', $parse[ 'sigInc' ] );

            # Get the keytag.
            $this->keytag = $parse[ 'keytag' ];

            # Get the signer's name and signature.
            $offset = $i_packet->offset + 18;
            $sigOffset = $offset;

            $this->signName = strtolower( $i_packet->expandEx( $sigOffset ) );
            $this->signature = base64_encode(
                substr( $this->rdata, 18 + ( $sigOffset - $offset ) )
            );

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        return $this->typeCovered . ' ' . $this->algorithm . ' ' .
            $this->labels . ' ' . $this->origTTL . ' ' .
            $this->sigExpiration . ' ' . $this->sigInception . ' ' .
            $this->keytag . ' ' . $this->cleanString( $this->signName ) . '. ' .
            $this->signature;
    }
}
