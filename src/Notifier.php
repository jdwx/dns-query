<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\RR\RR;
use JDWX\DNSQuery\RR\SIG;
use JDWX\DNSQuery\RR\TSIG;


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
 * The main dynamic DNS notifier class.
 *
 * This class provides functions to handle DNS notify requests as defined by RFC 1996.
 *
 * This is separate from the Resolver class, as while the underlying
 * protocol is the same, the functionality is completely different.
 *
 * Generally, query (recursive) lookups are done against caching server, while
 * notify requests are done against authoritative servers.
 *
 */
class Notifier extends BaseQuery {


    /** @var RequestPacket object used for the notify request */
    private RequestPacket $packet;


    /**
     * Constructor - builds a new Notifier objected used for doing
     * DNS notification for a changed zone
     *
     * @param string $i_zone the domain name to use for DNS updates
     *
     * @throws Exception
     */
    public function __construct( string $i_zone, array|string|null $i_nameServers = null, ?string $i_resolvConf = null ) {
        parent::__construct( $i_nameServers, $i_resolvConf );

        # Create the packet.
        $this->packet = new RequestPacket(
            strtolower( trim( $i_zone, " \n\r\t." ) ), 'SOA', 'IN'
        );

        # Make sure the opcode on the packet is set to NOTIFY.
        $this->packet->header->opcode = Lookups::OPCODE_NOTIFY;
    }


    /**
     *   3.7 - Add RR to notify
     *
     * @param RR $rr the RR object to be sent in the "notify" message
     *
     * @return bool
     * @throws Exception
     */
    public function add( RR $rr ) : bool {
        $this->_checkName( $rr->name );

        # Add the RR to the "notify" section
        if ( ! in_array( $rr, $this->packet->answer ) ) {
            $this->packet->answer[] = $rr;
        }
        return true;
    }


    /**
     * executes the notify request
     *
     * @param ?ResponsePacket $response contains a reference to the response object after running
     *
     * @return bool
     * @throws Exception
     */
    public function notify( ?ResponsePacket &$response = null ) : bool {
        # Check for an authentication method (either TSIG or SIG).
        if ( ( $this->authSignature instanceof TSIG )
            || ( $this->authSignature instanceof SIG )
        ) {
            $this->packet->additional[] = $this->authSignature;
        }

        # Update the counts.
        $this->packet->header->qdCount = count( $this->packet->question );
        $this->packet->header->anCount = count( $this->packet->answer );
        $this->packet->header->nsCount = count( $this->packet->authority );
        $this->packet->header->arCount = count( $this->packet->additional );

        # Make sure we have some data to send.
        if ( $this->packet->header->qdCount == 0 ) {
            throw new Exception(
                'empty headers- nothing to send!',
                Lookups::E_PACKET_INVALID
            );
        }

        # Send the packet and get back the response.
        $response = $this->sendPacket( $this->packet, $this->useTCP );

        # Clear the internal packet so we don't have
        # old data being sent if we make another request.
        $this->packet->reset();

        # For notifies, we just need to know it worked. we don't actually need to
        # return the response object.
        return true;
    }


    /**
     * returns the current internal packet object.
     *
     * @return RequestPacket The current internal packet object.
     */
    public function packet() : RequestPacket {
        # Take a copy
        $packet = clone $this->packet;

        # Check for an authentication method: either TSIG or SIG.
        if ( ( $this->authSignature instanceof TSIG )
            || ( $this->authSignature instanceof SIG )
        ) {
            $packet->additional[] = $this->authSignature;
        }

        # Update the counts.
        $packet->header->qdCount = count( $packet->question );
        $packet->header->anCount = count( $packet->answer );
        $packet->header->nsCount = count( $packet->authority );
        $packet->header->arCount = count( $packet->additional );

        return $packet;
    }


    /**
     * checks that the given name matches the name for the zone we're notifying
     *
     * @param string $name The name to be checked.
     *
     * @return void
     * @throws Exception
     */
    private function _checkName( string $name ) : void {
        if ( ! preg_match( '/' . $this->packet->question[ 0 ]->qName . '$/', $name ) ) {

            throw new Exception(
                'name provided (' . $name . ') does not match zone name (' .
                $this->packet->question[ 0 ]->qName . ')',
                Lookups::E_PACKET_INVALID
            );
        }
    }
}
