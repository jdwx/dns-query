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
 * @since     File available since Release 0.6.0
 *
 */

/**
 * NSEC Resource Record - RFC3845 section 2.1
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                      Next Domain Name                         /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                   List of Type Bit Map(s)                     /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class NSEC extends RR
{
    /*
     * The next owner name
     */
    public string $next_domain_name;

    /*
     * identifies the RRset types that exist at the NSEC RR's owner name.
     */
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
        $data = $this->cleanString($this->next_domain_name) . '.';

        foreach ($this->type_bit_maps as $rr) {

            $data .= ' ' . $rr;
        }

        return $data;
    }

    /**
     * parses the rdata portion from a standard DNS config line
     *
     * @param array $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrFromString(array $rdata) : bool
    {
        $this->next_domain_name = $this->cleanString(array_shift($rdata));
        $this->type_bit_maps = $rdata;
        
        return true;
    }

    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet $packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdlength > 0) {

            //
            // expand the next domain name
            //
            $offset = $packet->offset;
            $this->next_domain_name = Packet::expand($packet, $offset);

            //
            // parse out the RRs from the bitmap
            //
            $this->type_bit_maps = BitMap::bitMapToArray(
                substr($this->rdata, $offset - $packet->offset)
            );

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet $packet a Net_DNS2_Packet packet to use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->next_domain_name) > 0) {

            $data = $packet->compress($this->next_domain_name, $packet->offset);
            $bitmap = BitMap::arrayToBitMap($this->type_bit_maps);
    
            $packet->offset += strlen($bitmap);

            return $data . $bitmap;
        }

        return null;
    }
}
