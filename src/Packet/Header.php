<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Packet;


use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Lookups;


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
 * DNS Packet Header class
 *
 * This class handles parsing and constructing DNS Packet Headers as defined
 * by section 4.1.1 of RFC1035.
 *
 *  DNS header format - RFC1035 section 4.1.1
 *  DNS header format - RFC4035 section 3.2
 *
 *      0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                      ID                       |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |QR|   Opcode  |AA|TC|RD|RA| Z|AD|CD|   rCode   |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    QDCount                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    ANCount                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    NSCount                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    ARCount                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Header {


    /** @var int Identifier (16 bits) */
    public int $id;

    /** @var int 0 = query, 1 = response (1 bit) */
    public int $qr;

    /** @var int Op code (4 bits) */
    public int $opcode;

    /** @var int Authoritative Answer "AA" flag (1 bit) */
    public int $aa;

    /** @var int Truncation "TC" flag (1 bit) */
    public int $tc;

    /** @var int Recursion Desired "RD" flag (1 bit) */
    public int $rd;

    /** @var int Recursion Available "RA" flag (1 bit) */
    public int $ra;

    /** @var int Reserved (1 bit) */
    public int $zero;

    /** @var int Authentic Data "AD" flag (RFC4035) (1 bit) */
    public int $ad;

    /** @var int Checking Disabled "CD" flag (RFC4035) (1 bit) */
    public int $cd;

    /** @var int Response code (4 bits) */
    public int $rCode;

    /** @var int Question count (16 bits) */
    public int $qdCount;

    /** @var int Answer count (16 bits) */
    public int $anCount;

    /** @var int Authority count (16 bits) */
    public int $nsCount;

    /** @var int Additional count (16 bits) */
    public int $arCount;


    /**
     * Constructor - builds a new Header object
     *
     * @param string|Packet|null $source a Packet object, packed data in a string, or null for a default query packet
     *
     * @throws Exception If unpacking encounters an error
     * @throws \Exception If getting a packet ID fails
     */
    public function __construct( string|Packet|null $source = null ) {
        if ( is_null( $source ) ) {
            $this->setDefaultQuery();
            return;
        }
        if ( is_string( $source ) ) {
            $this->unpack( $source );
            return;
        }
        $this->set( $source );
    }


    /**
     * Return the header as a string
     *
     * The format used here is inspired by the output of the dig/drill command
     * line utility, but doesn't need to be an exact match.
     *
     * @return    string
     */
    public function __toString() : string {
        $output = ';; ->>HEADER<<- opcode: ' . Lookups::$opcodeTags[ $this->opcode ]
            . ', status: ' . ReturnCode::from( $this->rCode )->name
            . ', id: ' . $this->id . "\n";
        $output .= ';; flags: ';
        if ( $this->qr ) {
            $output .= 'qr ';
        }
        if ( $this->aa ) {
            $output .= 'aa ';
        }
        if ( $this->tc ) {
            $output .= 'tc ';
        }
        if ( $this->rd ) {
            $output .= 'rd ';
        }
        if ( $this->ra ) {
            $output .= 'ra ';
        }
        if ( $this->zero ) {
            $output .= 'z ';
        }
        if ( $this->ad ) {
            $output .= 'ad ';
        }
        if ( $this->cd ) {
            $output .= 'cd ';
        }
        $output = trim( $output );
        $output .= '; QUERY: ' . $this->qdCount
            . ', ANSWER: ' . $this->anCount
            . ', AUTHORITY: ' . $this->nsCount
            . ', ADDITIONAL: ' . $this->arCount . "\n";

        return $output;
    }


    /**
     * returns a binary packed DNS Header, offsetting a Packet accordingly.
     *
     * @param Packet $packet Packet to offset
     *
     * @return    string   Binary packed DNS Header
     */
    public function get( Packet $packet ) : string {
        $packet->offset += Lookups::DNS_HEADER_SIZE;
        return $this->pack();
    }


    /**
     * returns a binary packed DNS Header
     *
     * @return    string  Binary packed DNS Header
     */
    public function pack() : string {
        $flags =
            $this->qr << 15 |
            $this->opcode << 11 |
            $this->aa << 10 |
            $this->tc << 9 |
            $this->rd << 8 |
            $this->ra << 7 |
            $this->zero << 6 |
            $this->ad << 5 |
            $this->cd << 4 |
            $this->rCode;

        return pack( 'n*', $this->id, $flags, $this->qdCount, $this->anCount, $this->nsCount, $this->arCount );
    }


    /**
     * constructs a Header from a Packet object
     *
     * @param Packet $i_packet Packet to use for source data
     *
     * @return void
     * @throws Exception
     */
    public function set( Packet $i_packet ) : void {

        $this->unpack( substr( $i_packet->rdata, 0, Lookups::DNS_HEADER_SIZE ) );

        # Increment the packet's internal offset.
        $i_packet->offset += Lookups::DNS_HEADER_SIZE;

    }


    /**
     * Initialize header values to useful defaults for making a query.
     *
     * @return void
     * @throws Exception
     */
    public function setDefaultQuery() : void {

        $this->id = Lookups::nextPacketId();  # TODO: should be random
        $this->qr = Lookups::QR_QUERY;
        $this->opcode = Lookups::OPCODE_QUERY;
        $this->aa = 0;
        $this->tc = 0;
        $this->rd = 1;
        $this->ra = 0;
        $this->zero = 0;
        $this->ad = 0;
        $this->cd = 0;
        $this->rCode = ReturnCode::NOERROR->value;
        $this->qdCount = 1;
        $this->anCount = 0;
        $this->nsCount = 0;
        $this->arCount = 0;

    }


    /**
     * Populate the header based on raw wire-format data.
     *
     * @param string $i_packedData The raw wire-format data to unpack.
     *
     * @return void
     * @throws Exception
     */
    public function unpack( string $i_packedData ) : void {

        # The header must be at least 12 bytes long.
        if ( strlen( $i_packedData ) < Lookups::DNS_HEADER_SIZE ) {
            throw new Exception(
                'invalid header data provided; too small',
                Lookups::E_HEADER_INVALID
            );
        }


        /** @noinspection SpellCheckingInspection */
        $shorts = unpack( 'nid/nflags/nqd/nan/nns/nar', $i_packedData );

        $this->id = $shorts[ 'id' ];

        $this->qr = $shorts[ 'flags' ] >> 15 & 0x1;
        $this->opcode = $shorts[ 'flags' ] >> 11 & 0xf;
        $this->aa = $shorts[ 'flags' ] >> 10 & 0x1;
        $this->tc = $shorts[ 'flags' ] >> 9 & 0x1;
        $this->rd = $shorts[ 'flags' ] >> 8 & 0x1;
        $this->ra = $shorts[ 'flags' ] >> 7 & 0x1;
        $this->zero = $shorts[ 'flags' ] >> 6 & 0x1;
        $this->ad = $shorts[ 'flags' ] >> 5 & 0x1;
        $this->cd = $shorts[ 'flags' ] >> 4 & 0x1;
        $this->rCode = $shorts[ 'flags' ] & 0xf;

        $this->qdCount = $shorts[ 'qd' ];
        $this->anCount = $shorts[ 'an' ];
        $this->nsCount = $shorts[ 'ns' ];
        $this->arCount = $shorts[ 'ar' ];

    }


}
