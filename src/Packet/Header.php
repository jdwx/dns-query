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
    public int $rcode;      //  4 bit - Response code
    public int $qdcount;    // 16 bit - entries in the question section
    public int $ancount;    // 16 bit - resource records in the answer section
    public int $nscount;    // 16 bit - name server resource records in the authority records section
    public int $arcount;    // 16 bit - resource records in the additional records section

    /**
     * Constructor - builds a new Net_DNS2_Header object
     *
     * @param ?Packet $packet either a Net_DNS2_Packet object or null
     *
     * @throws Exception
     * @access public
     *
     */
    public function __construct(?Packet $packet = null)
    {
        if (!is_null($packet)) {

            $this->set($packet);
        } else {

            $this->id       = $this->nextPacketId();
            $this->qr       = Lookups::QR_QUERY;
            $this->opcode   = Lookups::OPCODE_QUERY;
            $this->aa       = 0;
            $this->tc       = 0;
            $this->rd       = 1;
            $this->ra       = 0;
            $this->z        = 0;
            $this->ad       = 0;
            $this->cd       = 0;
            $this->rcode    = Lookups::RCODE_NOERROR;
            $this->qdcount  = 1;
            $this->ancount  = 0;
            $this->nscount  = 0;
            $this->arcount  = 0;
        }
    }

    /**
     * returns the next available packet id
     *
     * @return    int
     * @access    public
     *
     */
    public function nextPacketId() : int
    {
        if (++Lookups::$next_packet_id > 65535) {

            Lookups::$next_packet_id = 1;
        }

        return Lookups::$next_packet_id;
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
        $output = ";;\n;; Header:\n";

        $output .= ";;\t id         = " . $this->id . "\n";
        $output .= ";;\t qr         = " . $this->qr . "\n";
        $output .= ";;\t opcode     = " . $this->opcode . "\n";
        $output .= ";;\t aa         = " . $this->aa . "\n";
        $output .= ";;\t tc         = " . $this->tc . "\n";
        $output .= ";;\t rd         = " . $this->rd . "\n";
        $output .= ";;\t ra         = " . $this->ra . "\n";
        $output .= ";;\t z          = " . $this->z . "\n";
        $output .= ";;\t ad         = " . $this->ad . "\n";
        $output .= ";;\t cd         = " . $this->cd . "\n";
        $output .= ";;\t rcode      = " . $this->rcode . "\n";
        $output .= ";;\t qdcount    = " . $this->qdcount . "\n";
        $output .= ";;\t ancount    = " . $this->ancount . "\n";
        $output .= ";;\t nscount    = " . $this->nscount . "\n";
        $output .= ";;\t arcount    = " . $this->arcount . "\n";

        return $output;
    }

    /**
     * constructs a Net_DNS2_Header from a Net_DNS2_Packet object
     *
     * @param Packet &$packet Object
     *
     * @return bool
     * @throws Exception
     * @access public
     *
     */
    public function set( Packet $packet) : bool
    {
        //
        // the header must be at least 12 bytes long.
        //
        if ($packet->rdLength < Lookups::DNS_HEADER_SIZE) {

            throw new Exception(
                'invalid header data provided; too small',
                Lookups::E_HEADER_INVALID
            );
        }

        $offset = 0;

        //
        // parse the values
        //
        $this->id       = ord($packet->rdata[$offset]) << 8 | 
            ord($packet->rdata[++$offset]);

        ++$offset;
        $this->qr       = (ord($packet->rdata[$offset]) >> 7) & 0x1;
        $this->opcode   = (ord($packet->rdata[$offset]) >> 3) & 0xf;
        $this->aa       = (ord($packet->rdata[$offset]) >> 2) & 0x1;
        $this->tc       = (ord($packet->rdata[$offset]) >> 1) & 0x1;
        $this->rd       = ord($packet->rdata[$offset]) & 0x1;

        ++$offset;
        $this->ra       = (ord($packet->rdata[$offset]) >> 7) & 0x1;
        $this->z        = (ord($packet->rdata[$offset]) >> 6) & 0x1;
        $this->ad       = (ord($packet->rdata[$offset]) >> 5) & 0x1;
        $this->cd       = (ord($packet->rdata[$offset]) >> 4) & 0x1;
        $this->rcode    = ord($packet->rdata[$offset]) & 0xf;
            
        $this->qdcount  = ord($packet->rdata[++$offset]) << 8 | 
            ord($packet->rdata[++$offset]);
        $this->ancount  = ord($packet->rdata[++$offset]) << 8 | 
            ord($packet->rdata[++$offset]);
        $this->nscount  = ord($packet->rdata[++$offset]) << 8 | 
            ord($packet->rdata[++$offset]);
        $this->arcount  = ord($packet->rdata[++$offset]) << 8 | 
            ord($packet->rdata[++$offset]);

        //
        // increment the internal offset
        //
        $packet->offset += Lookups::DNS_HEADER_SIZE;

        return true;
    }

    /**
     * returns a binary packed DNS Header
     *
     * @param Packet &$packet Object
     *
     * @return    string
     * @access    public
     *
     */
    public function get( Packet $packet) : string
    {
        $packet->offset += Lookups::DNS_HEADER_SIZE;

        return pack('n', $this->id) .
            chr(
                ($this->qr << 7) | ($this->opcode << 3) |
                ($this->aa << 2) | ($this->tc << 1) | ($this->rd)
            ) .
            chr(
                ($this->ra << 7) | ($this->ad << 5) | ($this->cd << 4) | $this->rcode
            ) .
            pack('n4', $this->qdcount, $this->ancount, $this->nscount, $this->arcount);
    }
}
