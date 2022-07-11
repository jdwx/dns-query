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
 * @category  Networking
 * @package   Net_DNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 1.4.1
 *
 */

/**
 * CSYNC Resource Record - RFC 7477 section 2.1.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                  SOA Serial                   |
 *    |                                               |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    Flags                      |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                 Type Bit Map                  /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class CSYNC extends RR
{
    /*
     * serial number
     */
    public int $serial;

    /*
     * flags
     */
    public int $flags;

    /** @var string[] array of RR type names */
    public array $type_bit_maps = [];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        $out = $this->serial . ' ' . $this->flags;

        //
        // show the RRs
        //
        foreach ($this->type_bit_maps as $rr) {

            $out .= ' ' . strtoupper($rr);
        }

        return $out;
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param string[] $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrFromString(array $rdata) : bool
    {
        $this->serial   = (int) array_shift($rdata);
        $this->flags    = (int) array_shift($rdata);

        $this->type_bit_maps = $rdata;

        return true;
    }

    /**
     * parses the rdata of the Packet object
     *
     * @param Packet $packet a Packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            //
            // unpack the serial and flags values
            //
            /** @noinspection SpellCheckingInspection */
            $x = unpack('@' . $packet->offset . '/Nserial/nflags', $packet->rdata);

            $this->serial   = $x[ 'serial' ];
            $this->flags    = $x[ 'flags' ];

            //
            // parse out the RR bitmap                 
            //
            $this->type_bit_maps = BitMap::bitMapToArray(
                substr($this->rdata, 6)
            );

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet $packet a Packet to use for compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        //
        // pack the serial and flags values
        //
        $data = pack('Nn', $this->serial, $this->flags);

        //
        // convert the array of RR names to a type bitmap
        //
        $data .= BitMap::arrayToBitMap($this->type_bit_maps);

        //
        // advance the offset
        //
        $packet->offset += strlen($data);

        return $data;
    }
}
