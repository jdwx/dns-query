<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Packet;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Question;
use JDWX\DNSQuery\RR\RR;


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
 * This class handles building new DNS response packets; it parses binary packed
 * packets that come off the wire
 * 
 */
class ResponsePacket extends Packet
{
    /*
     * The name servers that this response came from
     */
    public string $answer_from;

    /*
     * The socket type the answer came from (TCP/UDP)
     */
    public int $answer_socket_type;

    /*
     * The query response time in microseconds
     */
    public float $response_time = 0.0;

    /**
     * Constructor - builds a new Net_DNS2_Packet_Response object
     *
     * @param string $data binary DNS packet
     * @param int    $size the length of the DNS packet
     *
     * @throws Exception
     * @access public
     *
     */
    public function __construct( string $data, int $size )
    {
        $this->set($data, $size);
    }

    /**
     * builds a new Net_DNS2_Packet_Response object
     *
     * @param string $data binary DNS packet
     * @param int    $size the length of the DNS packet
     *
     * @return bool
     * @throws Exception
     * @access public
     *
     */
    public function set( string $data, int $size) : bool
    {
        //
        // store the full packet
        //
        $this->rdata    = $data;
        $this->rdlength = $size;

        //
        // parse the header
        // 
        // we don't bother checking the size earlier, because the first thing the
        // header class does is check the size and throw and exception if it's
        // invalid.
        //
        $this->header = new Header($this);

        //
        // if the truncation bit is set, then just return right here, because the
        // rest of the packet is probably empty; and there's no point in processing
        // anything else.
        //
        // we also don't need to worry about checking to see if the header is
        // null or not, since the Net_DNS2_Header() constructor will throw an 
        // exception if the packet is invalid.
        //
        if ($this->header->tc == 1) {

            return false;
        }

        //
        // parse the questions
        //
        for ($x = 0; $x < $this->header->qdcount; ++$x) {

            $this->question[$x] = new Question($this);
        }

        //
        // parse the answers
        //
        for ($x = 0; $x < $this->header->ancount; ++$x) {

            $o = RR::parse($this);
            if (!is_null($o)) {

                $this->answer[] = $o;
            }
        } 

        //
        // parse the authority section
        //
        for ($x = 0; $x < $this->header->nscount; ++$x) {

            $o = RR::parse($this);
            if (!is_null($o)) {

                $this->authority[] = $o;  
            }
        }

        //
        // parse the additional section
        //
        for ($x = 0; $x < $this->header->arcount; ++$x) {

            $o = RR::parse($this);
            if (!is_null($o)) {

                $this->additional[] = $o; 
            }
        }

        return true;
    }
}
