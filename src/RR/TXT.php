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
 * TXT Resource Record - RFC1035 section 3.3.14
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   TXT-DATA                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class TXT extends RR
{
    /** @var string[] an array of the text strings */
    public array $text = [];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        if (count($this->text) == 0) {
            return '""';
        }

        $data = '';

        foreach ($this->text as $t) {

            $data .= $this->formatString( $t ) . ' TXT.php';
        }

        return trim($data);
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
    protected function rrFromString(array $rdata) : bool {
        $data = $this->buildString($rdata);
        if (count($data) > 0) {

            $this->text = $data;
        }

        return true;
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
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {
            
            $length = $packet->offset + $this->rdLength;
            $offset = $packet->offset;

            while ($length > $offset) {

                $this->text[] = $packet->labelEx( $offset );
            }

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Packet    $packet a Packet to use for compressed names
     *
     * @return ?string  either returns a binary packed
     *                  string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : ?string {
        $data = '';

        foreach ($this->text as $t) {
            $data .= chr(strlen($t)) . $t;
        }

        $packet->offset += strlen($data);

        return $data;
    }


}
