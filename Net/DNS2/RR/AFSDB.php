<?php
declare( strict_types = 1 );

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
 * AFSDB Resource Record - RFC1183 section 1
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                    SUBTYPE                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    HOSTNAME                   /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_AFSDB extends Net_DNS2_RR
{
    /*
     * The AFSDB subtype
     */
    public string $subtype;

    /*
     * The AFSDB hostname
     */
    public string $hostname;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->subtype . ' ' . $this->cleanString($this->hostname) . '.';
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
        $this->subtype  = array_shift($rdata);
        $this->hostname = $this->cleanString(array_shift($rdata));

        return true;
    }

    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Net_DNS2_Packet $packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet(Net_DNS2_Packet $packet) : bool
    {
        if ($this->rdlength > 0) {
            
            //
            // unpack the subtype
            //
            $x = unpack('nsubtype', $this->rdata);

            $this->subtype  = $x['subtype'];
            $offset         = $packet->offset + 2;

            $this->hostname = Net_DNS2_Packet::expand($packet, $offset);

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Net_DNS2_Packet $packet a Net_DNS2_Packet packet to use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet(Net_DNS2_Packet $packet) : ?string
    {
        if (strlen($this->hostname) > 0) {
            
            $data = pack('n', $this->subtype);
            $packet->offset += 2;
            
            $data .= $packet->compress($this->hostname, $packet->offset);

            return $data;
        }
        
        return null; 
    }
}
