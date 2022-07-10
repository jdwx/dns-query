<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


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
 * @since     File available since Release 1.1.0
 *
 */

/**
 * ATMA Resource Record
 *
 *   0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 * +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 * |          FORMAT       |                       |
 * |                       +--+--+--+--+--+--+--+--+
 * /                    ADDRESS                    /
 * |                                               |
 * +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class ATMA extends RR
{
    /*
     * One octet that indicates the format of ADDRESS. The two possible values 
     * for FORMAT are value 0 indicating ATM End System Address (AESA) format
     * and value 1 indicating E.164 format
     */
    public int $format;

    /*
     * The IPv4 address in quad-dotted notation
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
     * @param array $rdata a string split line of values for the rdata
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrFromString(array $rdata) : bool
    {
        $value = array_shift($rdata);

        if ( ctype_xdigit( $value ) ) {
            
            $this->format   = 0;
            $this->address  = $value;

        } elseif ( is_numeric( $value ) ) {

            $this->format   = 1;
            $this->address  = $value;

        } else {

            return false;
        }

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
        if ($this->rdLength > 0) {

            //
            // unpack the format
            //
            $x = unpack('Cformat/N*address', $this->rdata);

            $this->format = $x['format'];

            if ($this->format == 0) {

                $a = unpack('@1/H*address', $this->rdata);

                $this->address = $a['address'];

            } elseif ($this->format == 1) {

                $this->address = substr($this->rdata, 1, $this->rdLength - 1);

            } else {

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     * 
     * @param Packet $packet a Net_DNS2_Packet packet use for
     *                                 compressed names
     *
     * @return ?string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     * 
     */
    protected function rrGet( Packet $packet) : ?string
    {
        $data = chr($this->format);

        if ($this->format == 0) {

            $data .= pack('H*', $this->address);

        } elseif ($this->format == 1) {

            $data .= $this->address;

        } else {

            return null;
        }

        $packet->offset += strlen($data);
        
        return $data;
    }
}
