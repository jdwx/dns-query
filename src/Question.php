<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


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
 * This class handles parsing and constructing the question section of DNS
 * packets.
 *
 * This is referred to as the "zone" for update per RFC2136
 *
 * DNS question format - RFC1035 section 4.1.2
 *
 *      0  1  2  3  4  5  6  7  8  9  0  1  2  3  4  5
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                                               |
 *    /                     QNAME                     /
 *    /                                               /
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                     QTYPE                     |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                     QCLASS                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Question
{
    /*
     * The name of the question
     *
     * referred to as "zname" for updates per RFC2136
     *
     */
    public string $qname;

    /*
     * The RR type for the question
     *
     * referred to as "ztype" for updates per RFC2136
     *
     */
    public string $qtype;
    
    /*
     * The RR class for the question
     *
     * referred to as "zclass" for updates per RFC2136
     *
     */
    public string $qclass;

    /**
     * Constructor - builds a new Net_DNS2_Question object
     *
     * @param ?Packet $packet either a Net_DNS2_Packet object, or null to
     *                       build an empty object
     *
     * @throws Exception
     * @access public
     *
     */
    public function __construct( ?Packet $packet = null )
    {
        if (!is_null($packet)) {

            $this->set($packet);
        } else {

            $this->qname    = '';
            $this->qtype    = 'A';
            $this->qclass   = 'IN';
        }
    }

    /**
     * magic __toString() function to return the Net_DNS2_Question object as a string
     *
     * @return string
     * @access public
     *
     */
    public function __toString()
    {
        return ";;\n;; Question:\n;;\t " . $this->qname . '. ' . 
            $this->qtype . ' ' . $this->qclass . "\n";
    }

    /**
     * builds a new Net_DNS2_Header object from a Net_DNS2_Packet object
     *
     * @param Packet &$packet a Net_DNS2_Packet object
     *
     * @return bool
     * @throws Exception
     * @access public
     *
     */
    public function set( Packet $packet) : bool
    {
        //
        // expand the name
        //
        $this->qname = $packet->expandEx( $packet->offset );

        if ($packet->rdlength < ($packet->offset + 4)) {
            throw new Exception(
                'invalid question section: to small',
                Lookups::E_QUESTION_INVALID
            );
        }

        //
        // unpack the type and class
        //
        $type   = ord($packet->rdata[$packet->offset++]) << 8 | 
            ord($packet->rdata[$packet->offset++]);
        $class  = ord($packet->rdata[$packet->offset++]) << 8 | 
            ord($packet->rdata[$packet->offset++]);

        //
        // validate it
        //
        $type_name  = Lookups::$rr_types_by_id[$type];
        $class_name = Lookups::$classes_by_id[$class];

        if ( (!isset($type_name)) || (!isset($class_name)) ) {

            throw new Exception(
                'invalid question section: invalid type (' . $type . 
                ') or class (' . $class . ') specified.',
                Lookups::E_QUESTION_INVALID
            );
        }

        //
        // store it
        //
        $this->qtype     = $type_name;
        $this->qclass    = $class_name;

        return true;
    }

    /**
     * returns a binary packed Net_DNS2_Question object
     *
     * @param Packet &$packet the Net_DNS2_Packet object this question is
     *                                 part of. This needs to be passed in so that
     *                                 the compressed qname value can be packed in
     *                                 with the names of the other parts of the 
     *                                 packet.
     *
     * @return string
     * @throws Exception
     * @access public
     *
     */
    public function get( Packet $packet) : string
    {
        //
        // validate the type and class
        //
        $type  = Lookups::$rr_types_by_name[$this->qtype];
        $class = Lookups::$classes_by_name[$this->qclass];

        if ( (!isset($type)) || (!isset($class)) ) {

            throw new Exception(
                'invalid question section: invalid type (' . $this->qtype . 
                ') or class (' . $this->qclass . ') specified.',
                Lookups::E_QUESTION_INVALID
            );
        }

        $data = $packet->compress($this->qname, $packet->offset);

        $data .= chr($type >> 8) . chr($type) . chr($class >> 8) . chr($class);
        $packet->offset += 4;

        return $data;
    }
}
