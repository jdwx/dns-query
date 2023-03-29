<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Network;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\BaseQuery;


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


/** Network socket management using PHP streams.
 *
 * By and large this class returns true on success and false on failure rather
 * than throwing exceptions because there are many cases in which a failure is
 * not exceptional.  (For example, if we have been provided a list of name
 * servers and we fail to connect to one, we want to try the next one,
 * not abort.)
 */
class Socket {

    /** @const TCP socket type code */
    public const SOCK_STREAM = SOCK_STREAM;

    /** @const UDP socket type code */
    public const SOCK_DGRAM = SOCK_DGRAM;

    /** @var string The last socket error encountered. */
    public string $lastError;

    /** @var float The timestamp when this socket was created. */
    public float $dateCreated;

    /** @var float The timestamp when this socket was last touched. */
    public float $dateLastUsed;

    /** @var ?resource */
    private $sock = null;

    /** @var int The type of this socket (Socket::SOCK_DGRAM or Socket::SOCK_STREAM) */
    private int $type;

    /** @var string The IPv4 or IPv6 address of the far end. */
    private string $remoteAddress;

    /** @var int The IP port number of the far end. */
    private int $remotePort;

    /** @var float The timeout in seconds for reading from this socket. */
    private float $timeout;

    /** @var int The seconds portion of the timeout, used for select. */
    private int $timeoutSeconds;

    /** @var int The microseconds portion of the timeout, used for select. */
    private int $timeoutMicroseconds;

    /** @var ?string The local IPv4 or IPv6 address to connect from (null lets the OS choose) */
    private ?string $localAddress = null;

    /** @var ?int The local port to connect from (null lets the OS choose) */
    private ?int $localPort = null;


    /**
     * constructor - set the port details
     *
     * @param int    $i_type Type of socket to use (i.e., Socket::SOCK_DGRAM or Socket::SOCK_STREAM)
     * @param string $i_remoteAddress IP address to connect to
     * @param int    $i_remotePort IP port to connect to
     * @param float  $i_timeout Timeout value (in seconds) to use for socket functions
     */
    public function __construct( int $i_type, string $i_remoteAddress, int $i_remotePort, float $i_timeout = 5.0 ) {
        $this->type = $i_type;
        $this->remoteAddress = $i_remoteAddress;
        $this->remotePort = $i_remotePort;
        $this->timeout = $i_timeout;
        $this->timeoutSeconds = (int) $i_timeout;
        $this->timeoutMicroseconds = (int) ( ( $i_timeout - $this->timeoutSeconds ) * 1000000 );
        $this->dateCreated = microtime( true );
        $this->dateLastUsed = $this->dateCreated;
    }


    /**
     * Destructor to make sure the socket gets closed.
     */
    public function __destruct() {
        $this->close();
    }


    /**
     * sets the local address/port for the socket to bind to
     *
     * @param ?string $i_localAddress Local IP address to bind to
     *                         or null to let the OS choose
     * @param ?int    $i_localPort Local IP port to bind to, or null to let the
     *                      OS choose
     *
     * @return void
     *
     * @throws Exception If the socket is already open
     */
    public function bindAddress( ?string $i_localAddress = null, ?int $i_localPort = null ) : void {
        if ( is_resource( $this->sock ) ) {
            # The avalanche has already started. It is too late for the pebbles to vote.
            throw new Exception( 'Cannot bind to address after socket has been created' );
        }
        $this->localAddress = $i_localAddress;
        $this->localPort = $i_localPort;
    }


    /**
     * Close the socket connection to the DNS server
     *
     * @return void
     */
    public function close() : void {
        if ( is_resource( $this->sock ) === true ) {
            fclose( $this->sock );
        }
    }


    /**
     * Open a socket connection to the DNS server
     *
     * @return bool True on success, otherwise false
     */
    public function open() : bool {

        # Create a list of options for the context.
        $opts = [ 'socket' => [] ];

        # Bind to a local IP/port if it's set.
        if ( is_string( $this->localAddress ) || $this->localPort != 0 ) {
            /** @noinspection SpellCheckingInspection */
            $opts[ 'socket' ][ 'bindto' ] = ( $this->localAddress ?? '0' ) . ':' . ( $this->localPort ?? 0 );
        }

        # Create the context.
        $context = stream_context_create( $opts );

        # Create socket.
        $errno = 0;
        $errorString = "";

        switch ( $this->type ) {
            case Socket::SOCK_STREAM:

                if ( BaseQuery::isIPv4( $this->remoteAddress ) ) {

                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    $this->sock = @stream_socket_client(
                        'tcp://' . $this->remoteAddress . ':' . $this->remotePort,
                        $errno, $errorString, $this->timeout,
                        STREAM_CLIENT_CONNECT, $context
                    );
                } elseif ( BaseQuery::isIPv6( $this->remoteAddress ) ) {

                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    $this->sock = @stream_socket_client(
                        'tcp://[' . $this->remoteAddress . ']:' . $this->remotePort,
                        $errno, $errorString, $this->timeout,
                        STREAM_CLIENT_CONNECT, $context
                    );
                } else {

                    $this->lastError = 'invalid address type: ' . $this->remoteAddress;
                    return false;
                }

                break;

            case Socket::SOCK_DGRAM:

                if ( BaseQuery::isIPv4( $this->remoteAddress ) ) {

                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    $this->sock = @stream_socket_client(
                        'udp://' . $this->remoteAddress . ':' . $this->remotePort,
                        $errno, $errorString, $this->timeout,
                        STREAM_CLIENT_CONNECT, $context
                    );
                } elseif ( BaseQuery::isIPv6( $this->remoteAddress ) ) {

                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    $this->sock = @stream_socket_client(
                        'udp://[' . $this->remoteAddress . ']:' . $this->remotePort,
                        $errno, $errorString, $this->timeout,
                        STREAM_CLIENT_CONNECT, $context
                    );
                } else {

                    $this->lastError = 'invalid address type: ' . $this->remoteAddress;
                    return false;
                }

                break;

            default:
                $this->lastError = 'Invalid socket type: ' . $this->type;
                return false;
        }

        if ( $this->sock === false ) {
            $this->lastError = $errorString;
            return false;
        }

        # Set it to non-blocking and set the timeout.
        stream_set_blocking( $this->sock, false );
        stream_set_timeout( $this->sock, $this->timeoutSeconds, $this->timeoutMicroseconds );

        return true;
    }


    /**
     * Read a response from a DNS server
     *
     * @param int &$o_size (output) The size of the DNS packet read is passed back
     * @param int  $i_maxSize Max data size that the caller wants
     *
     * @return bool|string Binary data from the server on success, otherwise false
     */
    public function read( int &$o_size, int $i_maxSize ) : bool|string {
        assert( is_resource( $this->sock ) );
        $read = [ $this->sock ];
        $write = null;
        $except = null;

        # Update the date last used timestamp.
        $this->dateLastUsed = microtime( true );

        # Make sure our socket is non-blocking.
        stream_set_blocking( $this->sock, false );

        # Wait for the socket to be ready to read.
        $result = stream_select( $read, $write, $except, $this->timeoutSeconds, $this->timeoutMicroseconds );
        if ( $result === false ) {

            $this->lastError = 'error on read select()';
            return false;

        } elseif ( $result == 0 ) {

            $this->lastError = 'timeout on read select()';
            return false;
        }

        $length = $i_maxSize;

        # If it's a TCP socket, then the first two bytes is the length of the DNS
        # packet. We need to read that off first, then use that value to read
        # the whole packet.
        if ( $this->type == Socket::SOCK_STREAM ) {

            if ( ( $data = fread( $this->sock, 2 ) ) === false ) {

                $this->lastError = 'failed on read for data length';
                return false;
            }
            if ( strlen( $data ) == 0 ) {
                $this->lastError = 'failed on read for data length';
                return false;
            }

            $length = ord( $data[ 0 ] ) << 8 | ord( $data[ 1 ] );
        }

        # At this point, we know that there is data on the socket to be read,
        # because we've already extracted the length from the first two bytes.
        # So the easiest thing to do, is just turn off socket blocking, and
        # wait for the data.
        stream_set_blocking( $this->sock, true );

        # Read the data from the socket.

        $data = '';

        # The stream socket is weird for TCP sockets; it doesn't seem to always
        # return all the data properly; but the looping code I added broke UDP
        # packets. My fault.
        #
        # The "sockets" library works much better.
        if ( $this->type == Socket::SOCK_STREAM ) {

            $chunkSize = $length;

            # Loop so we make sure we read all the data.
            while ( 1 ) {

                $chunk = fread( $this->sock, $chunkSize );
                if ( $chunk === false || strlen( $chunk ) == 0 ) {

                    $this->lastError = 'failed on read for data';
                    return false;
                }

                $data .= $chunk;
                $chunkSize -= strlen( $chunk );

                if ( strlen( $data ) >= $length ) {
                    break;
                }
            }

        } else {

            # If it's UDP, it's a single fixed-size frame, and the stream library
            # doesn't seem to have a problem reading it.
            $data = fread( $this->sock, $length );
            if ( $data === false || strlen( $data ) == 0 ) {

                $this->lastError = 'failed on read for data';
                return false;
            }
        }

        $o_size = strlen( $data );

        return $data;
    }


    /**
     * writes the given string to the DNS server socket
     *
     * @param string $i_data Binary packed data representing a DNS packet
     *
     * @return bool True on success, otherwise false
     * @suppress PhanTypeMismatchArgumentInternal
     */
    public function write( string $i_data ) : bool {
        $length = strlen( $i_data );
        if ( $length == 0 ) {
            $this->lastError = 'empty data on write()';
            return false;
        }

        $read = null;
        $write = [ $this->sock ];
        $except = null;

        # Update the date last used timestamp.
        $this->dateLastUsed = microtime( true );

        # Wait for socket to be ready to write.
        $result = stream_select( $read, $write, $except, $this->timeoutSeconds, $this->timeoutMicroseconds );
        if ( $result === false ) {

            $this->lastError = 'failed on write select()';
            return false;

        } elseif ( $result == 0 ) {

            $this->lastError = 'timeout on write select()';
            return false;
        }

        # If it's a TCP socket, then we need to pack and send the length of the
        # data as the first 16 bits of data.
        if ( $this->type == Socket::SOCK_STREAM ) {

            $packetLength = pack( 'n', $length );

            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            if ( @fwrite( $this->sock, $packetLength ) === false ) {
                $this->lastError = 'failed to write 16bit length';
                return false;
            }
        }

        # Write the data to the socket.
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $size = @fwrite( $this->sock, $i_data );
        if ( ( $size === false ) || ( $size != $length ) ) {

            $this->lastError = 'failed to write packet';
            return false;
        }

        return true;
    }


}
