<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Net_DNS2;
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
 * A Resource Record - RFC1035 section 3.4.1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                                               |       
 *    |                                               |       
 *    |                                               |       
 *    |                    ADDRESS                    |       
 *    |                                               |       
 *    |                   (128 bit)                   |       
 *    |                                               |       
 *    |                                               |       
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class AAAA extends RR
{
    /*
     * the IPv6 address in the preferred hexadecimal values of the eight 
     * 16-bit pieces 
     * per RFC1884
     *
     */
    public string $address;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->address;
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
        //
        // expand out compressed formats
        //
        $value = array_shift($rdata);
        if ( Net_DNS2::isIPv6( $value ) ) {

            $this->address = $value;
            return true;
        }
            
        return false;
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
        //
        // must be 8 x 16bit chunks, or 16 x 8bit
        //
        if ($this->rdLength == 16) {

            //
            // PHP's inet_ntop returns IPv6 addresses in their compressed form,
            // but we want to keep with the preferred standard, so we'll parse
            // it manually.
            //
            $x = unpack('n8', $this->rdata);
            if (count($x) == 8) {

                $this->address = vsprintf('%x:%x:%x:%x:%x:%x:%x:%x', $x);
                return true;
            }
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
        $packet->offset += 16;
        return inet_pton($this->address);
    }
}