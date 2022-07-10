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
 * NSEC3 Resource Record - RFC5155 section 3.2
 *
 *   0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |   Hash Alg.   |     Flags     |          Iterations           |
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Salt Length  |                     Salt                      /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  |  Hash Length  |             Next Hashed Owner Name            /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *  /                         Type Bit Maps                         /
 *  +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class NSEC3 extends RR
{
    /*
     * Algorithm to use
     */
    public string $algorithm;
 
    /*
     * flags
     */
    public string $flags;
 
    /*
     *  defines the number of additional times the hash is performed.
     */
    public string $iterations;
 
    /*
     * the length of the salt (not displayed)
     */
    public int $salt_length;
 
    /*
     * the salt
     */
    public string $salt;

    /*
     * the length of the hash value
     */
    public int $hash_length;

    /*
     * the hashed value of the owner name
     */
    public string $hashed_owner_name;

    /** @var string[] array of RR type names */
    public array $type_bit_maps = [];

    /**
     * method to return the rdata portion of the packet as a string
     *
     * @return  string
     * @access  protected
     *
     */
    protected function rrToString() : string {
        $out = $this->algorithm . ' ' . $this->flags . ' ' . $this->iterations . ' ';
 
        //
        // per RFC5155, the salt_length value isn't displayed, and if the salt
        // is empty, the salt is displayed as '-'
        //
        if ($this->salt_length > 0) {
  
            $out .= $this->salt;
        } else {     
 
            $out .= '-';
        }

        //
        // per RFC5255 the hash length isn't shown
        //
        $out .= ' ' . $this->hashed_owner_name;
 
        //
        // show the RRs
        //
        foreach ($this->type_bit_maps as $rr) {
    
            $out .= ' ' . strtoupper($rr);
        }

        return $out;
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
        $this->algorithm    = array_shift($rdata);
        $this->flags        = array_shift($rdata);
        $this->iterations   = array_shift($rdata);
     
        //
        // an empty salt is represented as '-' per RFC5155 section 3.3
        //
        $salt = array_shift($rdata);
        if ($salt == '-') {

            $this->salt_length = 0;
            $this->salt = '';
        } else {
    
            $this->salt_length = strlen(pack('H*', $salt));
            $this->salt = strtoupper($salt);
        }

        $this->hashed_owner_name = array_shift($rdata);
        $this->hash_length = strlen(base64_decode($this->hashed_owner_name));

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
    protected function rrSet( Packet $packet) : bool {
        if ($this->rdLength > 0) {
        
            //
            // unpack the first values
            //
            $x = unpack('Calgorithm/Cflags/niterations/Csalt_length', $this->rdata);
        
            $this->algorithm    = $x['algorithm'];
            $this->flags        = $x['flags'];
            $this->iterations   = $x['iterations'];
            $this->salt_length  = $x['salt_length'];

            $offset = 5;

            if ($this->salt_length > 0) {
 
                $x = unpack('H*', substr($this->rdata, $offset, $this->salt_length));
                $this->salt = strtoupper($x[1]);
                $offset += $this->salt_length;
            }

            //
            // unpack the hash length
            //
            $x = unpack('@' . $offset . '/Chash_length', $this->rdata);
            $offset++;

            //
            // copy out the hash
            //
            $this->hash_length  = $x['hash_length'];
            if ($this->hash_length > 0) {

                $this->hashed_owner_name = base64_encode(
                    substr($this->rdata, $offset, $this->hash_length)
                );
                $offset += $this->hash_length;
            }

            //
            // parse out the RR bitmap
            //
            $this->type_bit_maps = BitMap::bitMapToArray(
                substr($this->rdata, $offset)
            );

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
     * @return string                   either returns a binary packed
     *                                 string or null on failure
     * @access protected
     *
     */
    protected function rrGet( Packet $packet) : string {
        //
        // pull the salt and build the length
        //
        $salt = pack('H*', $this->salt);
        $this->salt_length = strlen($salt);
            
        //
        // pack the algorithm, flags, iterations and salt length
        //
        $data = pack(
            'CCnC',
            $this->algorithm, $this->flags, $this->iterations, $this->salt_length
        );
        $data .= $salt;

        //
        // add the hash length and hash
        //
        $data .= chr($this->hash_length);
        if ($this->hash_length > 0) {

            $data .= base64_decode($this->hashed_owner_name);
        }

        //
        // conver the array of RR names to a type bitmap
        //
        $data .= BitMap::arrayToBitMap($this->type_bit_maps);

        $packet->offset += strlen($data);
     
        return $data;
    }
}
