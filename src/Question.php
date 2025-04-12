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
 *    |                     qType                     |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *    |                     qClass                    |
 *    +--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+--+
 *
 */
class Question {


    /** @var string The name portion of the question
     *
     * referred to as "zName" for updates per RFC2136
     */
    public string $qName;

    /** @var string The RR type for the question
     *
     * referred to as "zType" for updates per RFC2136
     */
    public string $qType;

    /** @var string The RR class for the question
     *
     * referred to as "zClass" for updates per RFC2136
     */
    public string $qClass;


    /**
     * Constructor - builds a new Question object
     *
     * @param ?Packet $i_packet either a Packet object, or null to
     *                       build an empty object
     *
     * @throws Exception
     */
    public function __construct( ?Packet $i_packet = null ) {
        if ( ! is_null( $i_packet ) ) {

            $this->set( $i_packet );
        } else {

            $this->qName = '';
            $this->qType = 'A';
            $this->qClass = 'IN';
        }
    }


    /**
     * magic __toString() function to return the Question object as a string
     *
     * @return string
     */
    public function __toString() : string {
        return ";;\n;; Question:\n;;\t " . $this->qName . '. ' .
            $this->qType . ' ' . $this->qClass . "\n";
    }


    /**
     * returns a binary packed Question object
     *
     * @param Packet $i_packet Packet object this question is
     *                         part of. This needs to be passed in so that
     *                         the compressed qname value can be packed in
     *                         with the names of the other parts of the
     *                         packet.
     *
     * @return string
     * @throws Exception
     */
    public function get( Packet $i_packet ) : string {

        # Validate the type and class.
        $type = Lookups::$rrTypesByName[ $this->qType ] ?? null;
        $class = Lookups::$classesByName[ $this->qClass ] ?? null;

        if ( ! is_int( $type ) || ! is_int( $class ) ) {
            throw new Exception(
                'invalid question section: invalid type (' . $this->qType .
                ') or class (' . $this->qClass . ') specified.',
                Lookups::E_QUESTION_INVALID
            );
        }

        $data = $i_packet->compress( $this->qName, $i_packet->offset );

        $data .= chr( $type >> 8 ) . chr( $type ) . chr( $class >> 8 ) . chr( $class );
        $i_packet->offset += 4;

        return $data;
    }


    /**
     * Populate this Question object from a Packet object
     *
     * @param Packet $i_packet Packet object
     *
     * @return void
     * @throws Exception
     */
    public function set( Packet $i_packet ) : void {

        # Expand the name.
        $this->qName = $i_packet->expandEx( $i_packet->offset );

        if ( $i_packet->rdLength < ( $i_packet->offset + 4 ) ) {
            throw new Exception(
                'invalid question section: to small',
                Lookups::E_QUESTION_INVALID
            );
        }

        # Unpack the type and class.
        $type = ord( $i_packet->rdata[ $i_packet->offset++ ] ) << 8 |
            ord( $i_packet->rdata[ $i_packet->offset++ ] );
        $class = ord( $i_packet->rdata[ $i_packet->offset++ ] ) << 8 |
            ord( $i_packet->rdata[ $i_packet->offset++ ] );

        # Validate it.
        $typeName = Lookups::$rrTypesById[ $type ] ?? null;
        $className = Lookups::$classesById[ $class ] ?? null;

        if ( ! is_string( $typeName ) || ! is_string( $className ) ) {
            throw new Exception(
                'invalid question section: invalid type (' . $type .
                ') or class (' . $class . ') specified.',
                Lookups::E_QUESTION_INVALID
            );
        }

        # Store it.
        $this->qType = $typeName;
        $this->qClass = $className;

    }


}
