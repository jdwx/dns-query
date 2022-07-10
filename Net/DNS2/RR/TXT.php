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
 * TXT Resource Record - RFC1035 section 3.3.14
 *
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    /                   TXT-DATA                    /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Net_DNS2_RR_TXT extends Net_DNS2_RR
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

            $data .= $this->formatString($t) . ' ';
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
     * @param Net_DNS2_Packet &$packet a Net_DNS2_Packet packet to parse the RR from
     *
     * @return bool
     * @access protected
     *
     */
    protected function rrSet(Net_DNS2_Packet $packet) : bool {
        if ($this->rdlength > 0) {
            
            $length = $packet->offset + $this->rdlength;
            $offset = $packet->offset;

            while ($length > $offset) {

                $this->text[] = Net_DNS2_Packet::label($packet, $offset);
            }

            return true;
        }

        return false;
    }

    /**
     * returns the rdata portion of the DNS packet
     *
     * @param Net_DNS2_Packet &$packet a Net_DNS2_Packet packet use for
     *                                 compressed names
     *
     * @return null|string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet(Net_DNS2_Packet $packet) : ?string {
        $data = null;

        foreach ($this->text as $t) {

            $data .= chr(strlen($t)) . $t;
        }

        $packet->offset += strlen($data);

        return $data;
    }
}
