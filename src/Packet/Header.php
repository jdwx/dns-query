<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Packet;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;


/**
 * DNS Library for handling lookups and updates. 
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @category  Networking
 * @package   Net_DNS2
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
 *    |QR|   Opcode  |AA|TC|RD|RA| Z|AD|CD|   RCODE   |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    QDCOUNT                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    ANCOUNT                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    NSCOUNT                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    ARCOUNT                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Header
{
    public int $id;         // 16 bit - identifier
    public int $qr;         //  1 bit - 0 = query, 1 = response
    public int $opcode;     //  4 bit - op code
    public int $aa;         //  1 bit - Authoritative Answer
    public int $tc;         //  1 bit - Truncation
    public int $rd;         //  1 bit - Recursion Desired
    public int $ra;         //  1 bit - Recursion Available
    public int $z;          //  1 bit - Reserved
    public int $ad;         //  1 bit - Authentic Data (RFC4035)
    public int $cd;         //  1 bit - Checking Disabled (RFC4035)
    public int $rCode;      //  4 bit - Response code
    public int $qdCount;    // 16 bit - entries in the question section
    public int $anCount;    // 16 bit - resource records in the answer section
    public int $nsCount;    // 16 bit - name server resource records in the authority records section
    public int $arCount;    // 16 bit - resource records in the additional records section

    /**
     * Constructor - builds a new Header object
     *
     * @param string|Packet|null $source a Packet object, packed data, or null for a default query packet
     *
     * @throws Exception
     * @access public
     *
     */
    public function __construct( string|Packet|null $source = null) {
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
     * Initialize header values to useful defaults for making a query.
     *
     * @return void
     */
    public function setDefaultQuery() : void {

        $this->id       = Lookups::nextPacketId();  #  TODO: should be random
        $this->qr       = Lookups::QR_QUERY;
        $this->opcode   = Lookups::OPCODE_QUERY;
        $this->aa       = 0;
        $this->tc       = 0;
        $this->rd       = 1;
        $this->ra       = 0;
        $this->z        = 0;
        $this->ad       = 0;
        $this->cd       = 0;
        $this->rCode    = Lookups::RCODE_NOERROR;
        $this->qdCount  = 1;
        $this->anCount  = 0;
        $this->nsCount  = 0;
        $this->arCount  = 0;

    }

    /**
     * magic __toString() method to return the header as a string
     *
     * @return    string
     * @access    public
     *
     */
    public function __toString()
    {
        $output = ";; ->>HEADER<<- opcode: " . Lookups::$opcode_tags[ $this->opcode ]
            . ", status: " . Lookups::$result_code_tags[ $this->rCode ]
            . ", id: " . $this->id . "\n";
        $output .= ";; flags: ";
        if ( $this->qr ) {
            $output .= "qr ";
        }
        if ( $this->aa ) {
            $output .= "aa ";
        }
        if ( $this->tc ) {
            $output .= "tc ";
        }
        if ( $this->rd ) {
            $output .= "rd ";
        }
        if ( $this->ra ) {
            $output .= "ra ";
        }
        if ( $this->z ) {
            $output .= "z ";
        }
        if ( $this->ad ) {
            $output .= "ad ";
        }
        if ( $this->cd ) {
            $output .= "cd ";
        }
        $output = trim( $output );
        $output .= "; QUERY: " . $this->qdCount
            . ", ANSWER: " . $this->anCount
            . ", AUTHORITY: " . $this->nsCount
            . ", ADDITIONAL: " . $this->arCount . "\n";

        return $output;
    }


    /**
     * constructs a Header from a Packet object
     *
     * @param Packet $packet Object
     *
     * @return void
     * @throws Exception
     * @access public
     *
     */
    public function set( Packet $packet ) : void
    {

        $this->unpack( substr( $packet->rdata, 0, Lookups::DNS_HEADER_SIZE ) );

        //
        // increment the packet's internal offset
        //
        $packet->offset += Lookups::DNS_HEADER_SIZE;

    }


    /**
     * returns a binary packed DNS Header, offsetting a Packet accordingly.
     *
     * @param Packet $packet Object
     *
     * @return    string
     * @access    public
     *
     */
    public function get( Packet $packet ) : string
    {
        $packet->offset += Lookups::DNS_HEADER_SIZE;
        return $this->pack();
    }


    /**
     * returns a binary packed DNS Header
     *
     * @return    string
     * @access    public
     *
     */
    public function pack() : string {
        $flags =
            $this->qr << 15 |
            $this->opcode << 11 |
            $this->aa << 10 |
            $this->tc << 9 |
            $this->rd << 8 |
            $this->ra << 7 |
            $this->z << 6 |
            $this->ad << 5 |
            $this->cd << 4 |
            $this->rCode;

        return pack( 'n*', $this->id, $flags, $this->qdCount, $this->anCount, $this->nsCount, $this->arCount );
    }


    /**
     * Populate the header based on raw wire-format data.
     *
     * @param string $i_packedData The raw wire-format data to unpack.
     *
     * @return void
     * @access public
     *
     * @throws Exception
     */
    public function unpack( string $i_packedData ) : void {

        //
        // the header must be at least 12 bytes long.
        //
        if ( strlen( $i_packedData ) < Lookups::DNS_HEADER_SIZE ) {
            throw new Exception(
                'invalid header data provided; too small',
                Lookups::E_HEADER_INVALID
            );
        }


        /** @noinspection SpellCheckingInspection */
        $shorts = unpack( "nid/nflags/nqd/nan/nns/nar", $i_packedData );

        $this->id      = $shorts[ 'id' ];

        $this->qr      = $shorts[ 'flags' ] >> 15 & 0x1;
        $this->opcode  = $shorts[ 'flags' ] >> 11 & 0xf;
        $this->aa      = $shorts[ 'flags' ] >> 10 & 0x1;
        $this->tc      = $shorts[ 'flags' ] >> 9 & 0x1;
        $this->rd      = $shorts[ 'flags' ] >> 8 & 0x1;
        $this->ra      = $shorts[ 'flags' ] >> 7 & 0x1;
        $this->z       = $shorts[ 'flags' ] >> 6 & 0x1;
        $this->ad      = $shorts[ 'flags' ] >> 5 & 0x1;
        $this->cd      = $shorts[ 'flags' ] >> 4 & 0x1;
        $this->rCode   = $shorts[ 'flags' ] & 0xf;

        $this->qdCount = $shorts[ 'qd' ];
        $this->anCount = $shorts[ 'an' ];
        $this->nsCount = $shorts[ 'ns' ];
        $this->arCount = $shorts[ 'ar' ];

    }


}
