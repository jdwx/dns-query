<?php
/** @noinspection PhpUnused */
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
 * The main dynamic DNS notifier class.
 *
 * This class provides functions to handle DNS notify requests as defined by RFC 1996.
 *
 * This is separate from the Net_DNS2_Resolver class, as while the underlying
 * protocol is the same, the functionality is completely different.
 *
 * Generally, query (recursive) lookups are done against caching server, while
 * notify requests are done against authoritative servers.
 *
 */
class Net_DNS2_Notifier extends Net_DNS2
{
    /** @var Net_DNS2_Packet_Request object used for the notify request */
    private Net_DNS2_Packet_Request $_packet;

    /**
     * Constructor - builds a new Net_DNS2_Notifier objected used for doing 
     * DNS notification for a changed zone
     *
     * @param string $zone    the domain name to use for DNS updates
     * @param ?array  $options an array of config options or null
     *
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function __construct( string $zone, ?array $options = null)
    {
        parent::__construct($options);

        //
        // create the packet
        //
        $this->_packet = new Net_DNS2_Packet_Request(
            strtolower(trim($zone, " \n\r\t.")), 'SOA', 'IN'
        );

        //
        // make sure the opcode on the packet is set to NOTIFY
        //
        $this->_packet->header->opcode = Net_DNS2_Lookups::OPCODE_NOTIFY;
    }

    /**
     * checks that the given name matches the name for the zone we're notifying
     *
     * @param string $name The name to be checked.
     *
     * @return void
     * @throws Net_DNS2_Exception
     * @access private
     *
     */
    private function _checkName( string $name ) : void
    {
        if (!preg_match('/' . $this->_packet->question[0]->qname . '$/', $name)) {
            
            throw new Net_DNS2_Exception(
                'name provided (' . $name . ') does not match zone name (' .
                $this->_packet->question[0]->qname . ')',
                Net_DNS2_Lookups::E_PACKET_INVALID
            );
        }
    }

    /**
     *   3.7 - Add RR to notify
     *
     * @param Net_DNS2_RR $rr the Net_DNS2_RR object to be sent in the "notify" message
     *
     * @return bool
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function add(Net_DNS2_RR $rr) : bool
    {
        $this->_checkName($rr->name);
        //
        // add the RR to the "notify" section
        //
        if (!in_array($rr, $this->_packet->answer)) {
            $this->_packet->answer[] = $rr;
        }
        return true;
    }


    /**
     * add a signature to the request for authentication
     *
     * @param string $key_name the key name to use for the TSIG RR
     * @param string $signature the key to sign the request.
     * @param string $algorithm
     * @return     bool
     * @throws Net_DNS2_Exception
     * @access     public
     * @deprecated function deprecated in 1.1.0
     *
     * @see        Net_DNS2::signTSIG()
     */
    public function signature( string $key_name, string $signature, string $algorithm = Net_DNS2_RR_TSIG::HMAC_MD5 ) : bool
    {
        return $this->signTSIG($key_name, $signature, $algorithm);
    }

    /**
     * returns the current internal packet object.
     *
     * @return Net_DNS2_Packet_Request
     * @access public
     #
     */
    public function packet() : Net_DNS2_Packet_Request
    {
        //
        // take a copy
        //
        $p = $this->_packet;

        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->auth_signature instanceof Net_DNS2_RR_TSIG) 
            || ($this->auth_signature instanceof Net_DNS2_RR_SIG)
        ) {
            $p->additional[] = $this->auth_signature;
        }

        //
        // update the counts
        //
        $p->header->qdcount = count($p->question);
        $p->header->ancount = count($p->answer);
        $p->header->nscount = count($p->authority);
        $p->header->arcount = count($p->additional);

        return $p;
    }

    /**
     * executes the notify request
     *
     * @param ?Net_DNS2_Packet_Response & $response contains a reference to the response object after running
     *
     * @return bool
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function notify( ?Net_DNS2_Packet_Response & $response = null ) : bool
    {
        //
        // check for an authentication method; either TSIG or SIG
        //
        if (   ($this->auth_signature instanceof Net_DNS2_RR_TSIG) 
            || ($this->auth_signature instanceof Net_DNS2_RR_SIG)
        ) {
            $this->_packet->additional[] = $this->auth_signature;
        }

        //
        // update the counts
        //
        $this->_packet->header->qdcount = count($this->_packet->question);
        $this->_packet->header->ancount = count($this->_packet->answer);
        $this->_packet->header->nscount = count($this->_packet->authority);
        $this->_packet->header->arcount = count($this->_packet->additional);

        //
        // make sure we have some data to send
        //
        if ($this->_packet->header->qdcount == 0) {
            throw new Net_DNS2_Exception(
                'empty headers- nothing to send!',
                Net_DNS2_Lookups::E_PACKET_INVALID
            );
        }

        //
        // send the packet and get back the response
        //
        $response = $this->sendPacket($this->_packet, $this->use_tcp);

        //
        // clear the internal packet so if we make another request, we don't have
        // old data being sent.
        //
        $this->_packet->reset();

        //
        // for notifies, we just need to know it worked. we don't actually need to
        // return the response object
        //
        return true;
    }
}
