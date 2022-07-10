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
 * @since     File available since Release 1.2.0
 *
 */

/**
 * URI Resource Record - http://tools.ietf.org/html/draft-faltstrom-uri-06
 *
 *    0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   |          Priority             |          Weight               |
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *   /                                                               /
 *   /                             Target                            /
 *   /                                                               /
 *   +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class URI extends RR
{
    /*
     * The priority of this target host.
     */
    public string $priority;

    /*
     * a relative weight for entries with the same priority
     */
    public string $weight;

    /*
      * The domain name of the target host
     */
    public string $target;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        //
        // presentation format has double quotes (") around the target.
        //
        return $this->priority . ' ' . $this->weight . ' "' . $this->target . '"';
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
        $this->priority = $rdata[0];
        $this->weight   = $rdata[1];
        $this->target   = trim(strtolower(trim($rdata[2])), '"');
        
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
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {
            
            //
            // unpack the priority and weight
            //
            $x = unpack('npriority/nweight/a*target', $this->rdata);

            $this->priority = $x['priority'];
            $this->weight   = $x['weight'];
            $this->target   = $x['target'];

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
    protected function rrGet( Packet $packet) : ?string {
        if (strlen($this->target) > 0) {

            $data = pack('nna*', $this->priority, $this->weight, $this->target);

            $packet->offset += strlen($data);

            return $data;
        }

        return null;
    }
}
