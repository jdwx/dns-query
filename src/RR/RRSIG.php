<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Data\RecordType;
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
 * RRSIG Resource Record - RFC4034 section 3.1
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
class RRSIG extends RR {


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
        if ( strlen( $this->signature ) > 0 ) {

            # Parse the values out of the dates.
            preg_match(
                '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $this->sigExpiration, $ee
            );
            preg_match(
                '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $this->sigInception, $ii
            );

            # Pack the value.
            /** @noinspection SpellCheckingInspection */
            $data = pack(
                'nCCNNNn',
                RecordType::nameToId( $this->typeCovered ),
                $this->algorithm,
                $this->labels,
                $this->origTTL,
                gmmktime( (int) $ee[ 4 ], (int) $ee[ 5 ], (int) $ee[ 6 ], (int) $ee[ 2 ], (int) $ee[ 3 ], (int) $ee[ 1 ] ),
                gmmktime( (int) $ii[ 4 ], (int) $ii[ 5 ], (int) $ii[ 6 ], (int) $ii[ 2 ], (int) $ii[ 3 ], (int) $ii[ 1 ] ),
                $this->keytag
            );

            # The signer name is special; it's not allowed to be compressed
            # (see section 3.1.7).
            $names = explode( '.', strtolower( $this->signName ) );
            foreach ( $names as $name ) {

                $data .= chr( strlen( $name ) );
                $data .= $name;
            }
            $data .= "\0";

            # Add the signature.
            $data .= base64_decode( $this->signature );

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Unpack.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack(
                'ntc/Calgorithm/Clabels/Norigttl/Nsigexp/Nsigincep/nkeytag',
                $this->rdata
            );

            $this->typeCovered = RecordType::idToName( $parse[ 'tc' ] );
            $this->algorithm = $parse[ 'algorithm' ];
            $this->labels = $parse[ 'labels' ];
            $this->origTTL = $parse[ 'origttl' ];

            # The dates are in GM time.
            $this->sigExpiration = gmdate( 'YmdHis', $parse[ 'sigexp' ] );
            $this->sigInception = gmdate( 'YmdHis', $parse[ 'sigincep' ] );

            # Get the keytag.
            $this->keytag = $parse[ 'keytag' ];

            # Get teh signers name and signature.
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
