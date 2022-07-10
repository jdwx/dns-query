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
 * @since     File available since Release 0.6.0
 *
 */

/**
 * NSEC3PARAM Resource Record - RFC5155 section 4.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   Hash Alg.   |     Flags     |          Iterations           |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Salt Length  |                     Salt                      /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class NSEC3PARAM extends RR
{
    /*
     * Algorithm to use
     *
     * TODO: same as the NSEC3
     */
    public int  $algorithm;

    /*
     * flags
     */
    public int $flags;

    /*
     *  defines the number of additional times the hash is performed.
     */
    public int $iterations;

    /*
     * the length of the salt (not displayed)
     */
    public int $salt_length;

    /*
     * the salt
     */
    public string $salt;

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string
    {
        $out = $this->algorithm . ' ' . $this->flags . ' ' . $this->iterations . ' ';

        //
        // per RFC5155, the salt_length value isn't displayed, and if the salt 
        // is empty, the salt is displayed as "-"
        //        
        if ($this->salt_length > 0) {

            $out .= $this->salt;
        } else {
            
            $out .= '-';
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
        $this->algorithm    = (int) array_shift($rdata);
        $this->flags        = (int) array_shift($rdata);
        $this->iterations   = (int) array_shift($rdata);

        $salt = array_shift($rdata);
        if ($salt == '-') {

            $this->salt_length = 0;
            $this->salt = '';
        } else {

            $this->salt_length = strlen(pack('H*', $salt));
            $this->salt = strtoupper($salt);
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
     */
    protected function rrSet( Packet $packet) : bool
    {
        if ($this->rdLength > 0) {

            /** @noinspection SpellCheckingInspection */
            $x = unpack('Calgorithm/Cflags/niterations/Csalt_length', $this->rdata);

            $this->algorithm    = $x['algorithm'];
            $this->flags        = $x['flags'];
            $this->iterations   = $x['iterations'];
            $this->salt_length  = $x['salt_length'];

            if ($this->salt_length > 0) {

                $x = unpack('H*', substr($this->rdata, 5, $this->salt_length));
                $this->salt = strtoupper($x[1]);
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
        $salt = pack('H*', $this->salt);
        $this->salt_length = strlen($salt);

        $data = pack(
                'CCnC',
                $this->algorithm, $this->flags, $this->iterations, $this->salt_length
            ) . $salt;

        $packet->offset += strlen($data);

        return $data;
    }
}
