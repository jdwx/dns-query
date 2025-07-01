<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Legacy\Network;


use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Lookups;
use JDWX\DNSQuery\Legacy\Packet\ResponsePacket;


/** TCP transport for DNS packets */
class TCPTransport extends IPTransport {


    /**
     * @throws Exception
     */
    public function __construct( string $i_nameserver, int $i_port = 53, ?string $i_localHost = null,
                                 ?int   $i_localPort = null, int $i_timeout = 5,
                                 int    $i_maxSize = 1048576 ) {
        parent::__construct( Socket::SOCK_STREAM, $i_nameserver, $i_port, $i_localHost, $i_localPort,
            $i_timeout, $i_maxSize );
    }


    /**
     * @throws Exception
     */
    public function receiveAXFR() : ResponsePacket {
        $startTime = microtime( true );
        $size = 0;
        $soaCount = 0;
        $response = null;

        while ( 1 ) {

            # Read the data off the socket
            $result = $this->read( $size );

            if ( $size < Lookups::DNS_HEADER_SIZE ) {

                # If we get an error, then keeping this socket around for a future request could cause
                # an error. For example, https://github.com/mikepultz/netdns2/issues/61
                #
                # in this case, the connection was timing out, which once it did finally respond, left
                # data on the socket, which could be captured on a subsequent request.
                #
                # since there's no way to "reset" a socket, the only thing we can do it close it.
                throw new Exception( 'TCPTransport::receiveAXFR() - socket read too short',
                    Lookups::E_NS_SOCKET_FAILED );
            }

            # Parse the first chunk as a packet.
            $chunk = new ResponsePacket( $result, $size );

            # If this is the first packet, then clone it directly, then
            # go through it to see if there are two SOA records
            # (indicating that it's the only packet).
            if ( is_null( $response ) ) {

                $response = clone $chunk;

                # Look for a failed response; if the zone transfer
                # failed, then we don't need to do anything else at this
                # point, and we should just break out.
                if ( $response->header->rCode !== ReturnCode::NOERROR->value ) {
                    break;
                }

                # Go through each answer.
                foreach ( $response->answer as $rr ) {

                    # Count the SOA records.
                    if ( $rr->type == 'SOA' ) {
                        $soaCount++;
                    }
                }

            } else {

                # Go through all these answers, and look for SOA records.
                foreach ( $chunk->answer as $rr ) {

                    # Count the number of SOA records we find.
                    if ( $rr->type == 'SOA' ) {
                        $soaCount++;
                    }

                    # Add the records to a single response object.
                    $response->answer[] = $rr;
                }

            }

            # If we have 2 or more SOA records, then we're done.
            # Otherwise, continue out so we read the rest of the
            # packets off the socket.
            if ( $soaCount >= 2 ) {
                break;
            }

        }

        # Store the query time.
        $response->responseTime = microtime( true ) - $startTime;

        # Add the name server that the response came from to the response object,
        # and the socket type that was used.
        $response->answerFrom = $this->nameServer;
        $response->answerSocketType = Socket::SOCK_STREAM;

        # Return response packet.
        return $response;

    }


    /**
     * @throws Exception
     */
    public function receiveResponse() : ResponsePacket {
        $startTime = microtime( true );
        $size = 0;
        $result = $this->read( $size );

        if ( $size < Lookups::DNS_HEADER_SIZE ) {
            throw new Exception( 'received packet is too small to be a valid DNS packet', Lookups::E_NS_SOCKET_FAILED );
        }

        # Create the packet object.
        $response = new ResponsePacket( $result, $size );

        # Store the query time.
        $response->responseTime = microtime( true ) - $startTime;

        # Add the name server that the response came from to the response object,
        # and the socket type that was used.
        $response->answerFrom = $this->nameServer;
        $response->answerSocketType = Socket::SOCK_STREAM;

        # Return the response packet.
        return $response;
    }


}

