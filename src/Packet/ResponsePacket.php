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
class ResponsePacket extends Packet {
    /** @var string The name servers that this response came from. */
    public string $answerFrom;

    /** @var int The socket type the answer came from (TCP/UDP) */
    public int $answerSocketType;

    /** @var float The query response time in microseconds */
    public float $responseTime = 0.0;


    /**
     * Constructor - builds a new ResponsePacket object
     *
     * @param string $i_data binary DNS packet
     * @param int    $i_size the length of the DNS packet
     *
     * @throws Exception
     */
    public function __construct( string $i_data, int $i_size ) {
        $this->set( $i_data, $i_size );
    }


    /**
     * builds a new ResponsePacket object
     *
     * @param string $i_data binary DNS packet
     * @param int    $i_size the length of the DNS packet
     *
     * @return bool
     * @throws Exception
     */
    public function set( string $i_data, int $i_size ) : bool {

        # Store the full packet.
        $this->rdata = $i_data;
        $this->rdLength = $i_size;

        # Parse the header.

        # We don't bother checking the size yet, because the first thing the
        # Header class does is check the size and throw and exception if it's
        # invalid.
        #
        # We also don't need to worry about checking to see if the header is
        # null or not, since the Header() constructor will throw an
        # exception if the packet is invalid.
        $this->header = new Header( $this );

        # If the truncation bit is set, then just return right here, because the
        # rest of the packet is probably empty; and there's no point in processing
        # anything else.
        if ( $this->header->tc == 1 ) {
            return false;
        }

        # Parse the questions.
        for ( $ii = 0 ; $ii < $this->header->qdCount ; ++$ii ) {
            $this->question[ $ii ] = new Question( $this );
        }

        # Parse the answers.
        for ( $ii = 0 ; $ii < $this->header->anCount ; ++$ii ) {
            $rr = RR::parse( $this );
            if ( ! is_null( $rr ) ) {

                $this->answer[] = $rr;
            }
        }

        # Parse the authority section.
        for ( $ii = 0 ; $ii < $this->header->nsCount ; ++$ii ) {
            $rr = RR::parse( $this );
            if ( ! is_null( $rr ) ) {

                $this->authority[] = $rr;
            }
        }

        # Parse the additional section.
        for ( $ii = 0 ; $ii < $this->header->arCount ; ++$ii ) {
            $rr = RR::parse( $this );
            if ( ! is_null( $rr ) ) {

                $this->additional[] = $rr;
            }
        }

        return true;
    }
}
