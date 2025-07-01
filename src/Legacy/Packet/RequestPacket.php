<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\Packet;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\BaseQuery;
use JDWX\DNSQuery\Legacy\LegacyQuestion;
use JDWX\DNSQuery\Legacy\Lookups;


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
 * This class handles building new DNS request packets; packets used for DNS
 * queries and updates.
 *
 */
class RequestPacket extends Packet {


    /**
     * Constructor - builds a new RequestPacket object
     *
     * @param string $i_name the domain name for the packet
     * @param ?string $i_type the DNS RR type for the packet
     * @param ?string $i_class the DNS class for the packet
     *
     * @throws Exception
     */
    public function __construct( string $i_name, ?string $i_type = null, ?string $i_class = null ) {
        $this->set( $i_name, $i_type, $i_class );
    }


    /**
     * Build a new RequestPacket object
     *
     * @param string $i_name Domain name for the packet
     * @param ?string $i_type RR-type for the packet
     * @param ?string $i_class DNS class for the packet
     *
     * @return bool
     * @throws Exception
     */
    public function set( string $i_name, ?string $i_type = null, ?string $i_class = null ) : bool {
        if ( ! is_string( $i_type ) ) {
            $i_type = 'A';
        }

        if ( ! is_string( $i_class ) ) {
            $i_class = 'IN';
        }

        # Generate a new header.
        $this->header = new Header();

        # Add a new question.
        $question = new LegacyQuestion();

        # Allow queries directly to . for the root name servers.
        if ( $i_name != '.' ) {
            $i_name = trim( strtolower( $i_name ), " \t\n\r\0\x0B." );
        }

        $i_type = strtoupper( trim( $i_type ) );
        $i_class = strtoupper( trim( $i_class ) );

        # Check that the input string has some data in it.
        if ( empty( $i_name ) ) {
            throw new Exception(
                'empty query string provided',
                Lookups::E_PACKET_INVALID
            );
        }

        # If the type is "*" rename it to "ANY" since both are acceptable.
        if ( $i_type == '*' ) {
            $i_type = 'ANY';
        }

        # Check that the type and class are valid.
        if ( ! RecordType::isValidName( $i_type ) || ! RecordClass::isValidName( $i_class ) ) {
            throw new Exception(
                'invalid type (' . $i_type . ') or class (' . $i_class . ') specified.',
                Lookups::E_PACKET_INVALID
            );
        }

        if ( $i_type == 'PTR' ) {

            # If it's a PTR request for an IP address, then make sure we tack on
            # the arpa domain.
            #
            # there are other types of PTR requests, so if an IP address doesn't match,
            # then just let it flow through and assume it's a hostname
            if ( BaseQuery::isIPv4( $i_name ) ) {

                # IPv4
                $i_name = implode( '.', array_reverse( explode( '.', $i_name ) ) );
                $i_name .= '.in-addr.arpa';

            } elseif ( BaseQuery::isIPv6( $i_name ) ) {

                # IPv6
                $ipv6 = BaseQuery::expandIPv6( $i_name );
                $i_name = implode(
                    '.', array_reverse( str_split( str_replace( ':', '', $ipv6 ) ) )
                );

                $i_name .= '.ip6.arpa';

            }
        }

        # Store the data.
        $question->qName = $i_name;
        $question->qType = $i_type;
        $question->qClass = $i_class;

        $this->question[] = $question;

        # The answer, authority and additional are empty; they can be modified
        # after the request is created for UPDATE requests if needed.
        $this->answer = [];
        $this->authority = [];
        $this->additional = [];

        return true;
    }


}
