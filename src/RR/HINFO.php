<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Exception;
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
 * HINFO Resource Record - RFC1035 section 3.3.2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                      CPU                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                       OS                      /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class HINFO extends RR
{
    /*
     * computer information
     */
    public string $cpu;

    /*
     * operating system
     */
    public string $os;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->formatString( $this->cpu ) . ' HINFO.php' . $this->formatString($this->os);
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
        $data = $this->buildString($rdata);
        if (count($data) == 2) {

            $this->cpu  = trim($data[0], '"');
            $this->os   = trim($data[1], '"');

            return true;
        }

        return false;
    }


    /**
     * parses the rdata of the Net_DNS2_Packet object
     *
     * @param Packet &$packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            $offset = $packet->offset;
    
            $this->cpu  = $packet->labelEx( $offset );
            $this->os   = $packet->labelEx( $offset );

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet &$packet a Net_DNS2_Packet packet to use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->cpu) > 0) {

            $data = pack('Ca*Ca*', strlen($this->cpu), $this->cpu, strlen($this->os), $this->os);

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
