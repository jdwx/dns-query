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
 * ISDN Resource Record - RFC1183 section 3.2
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    ISDN-address               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                    SA                         /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class ISDN extends RR
{
    /*
     * ISDN Number
     */
    public string $isdnAddress;
    
    /*
     * Sub-Address
     */
    public string $sa;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        return $this->formatString( $this->isdnAddress ) . ' ' .
            $this->formatString($this->sa);
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
        if (count($data) >= 1) {

            $this->isdnAddress = $data[0];
            if (isset($data[1])) {
                
                $this->sa = $data[1];
            }

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
     * @throws Exception
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            $this->isdnAddress = $packet->labelEx($packet->offset );

            //
            // look for a SA (sub address) - it's optional
            //
            if ( (strlen($this->isdnAddress) + 1) < $this->rdLength) {

                $this->sa = $packet->labelEx( $packet->offset );
            } else {
            
                $this->sa = '';
            }

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet &$packet a Net_DNS2_Packet packet use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string
    {
        if (strlen($this->isdnAddress) > 0) {

            $data = chr(strlen($this->isdnAddress)) . $this->isdnAddress;
            if (!empty($this->sa)) {

                $data .= chr(strlen($this->sa));
                $data .= $this->sa;
            }

            $packet->offset += strlen($data);

            return $data;
        }
        
        return null; 
    }
}
