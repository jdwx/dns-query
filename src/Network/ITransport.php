<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Network;


use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


/** Interface definition for DNS network transports. */
interface ITransport {

    /** Get the IPv4 or IPv6 address of the name server associated with this transport. */
    public function getNameServer() : string;

    /** Get the (name server's) port number associated with this transport. */
    public function getPort() : int;

    /** Get the type of the transport.
     *
     * @return int Currently either Socket::SOCK_DGRAM or Socket::SOCK_STREAM.
     */
    public function getType() : int;

    /** Send a request over this transport. */
    public function sendRequest( RequestPacket $i_request ) : void;

    /** Read a response from this transport.
     *
     * @return ResponsePacket The response packet received.
     */
    public function receiveResponse() : ResponsePacket;

}

