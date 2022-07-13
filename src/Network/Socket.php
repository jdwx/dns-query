<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Network;


use JDWX\DNSQuery\Net_DNS2;


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

/*
 * check to see if the socket defines exist; if they don't, then define them
 */

/**
 * Socket handling class using the PHP Streams
 *
 */
class Socket
{
    /** @var resource */
    private $sock;

    private int $type;
    private string $host;
    private int $port;
    private int $timeout;

    /*
     * the local IP and port we'll send the request from
     */
    private string $localAddress = '';
    private int $localPort = 0;

    /*
     * the last error message on the object
     */
    public string $lastError;

    /*
     * date the socket connection was created, and the date it was last used 
     */
    public float $dateCreated;
    public float $dateLastUsed;

    /*
     * type of sockets
     */
    public const SOCK_STREAM   = SOCK_STREAM;
    public const SOCK_DGRAM    = SOCK_DGRAM;

    /**   
     * constructor - set the port details
     *
     * @param int    $type    the socket type
     * @param string $host    the IP address of the DNS server to connect to
     * @param int    $port    the port of the DNS server to connect to
     * @param int    $timeout the timeout value to use for socket functions
     *
     * @access public
     *       
     */
    public function __construct(int $type, string $host, int $port, int $timeout)
    {
        $this->type         = $type;
        $this->host         = $host;
        $this->port         = $port;
        $this->timeout      = $timeout;
        $this->dateCreated = microtime(true);
    }

    /**
     * destructor
     *
     * @access public
     */
    public function __destruct()
    {
        $this->close();
    }

    /**   
     * sets the local address/port for the socket to bind to
     *
     * @param string $address the local IP address to bind to
     * @param int  $port    the local port to bind to, or 0 to let the socket
     *                        function select a port
     *
     * @return bool
     * @access public
     *       
     */
    public function bindAddress( string $address, int $port = 0 ) : bool
    {
        $this->localAddress = $address;
        $this->localPort = $port;

        return true;
    }

    /**
     * opens a socket connection to the DNS server
     *     
     * @return bool
     * @access public
     *
     */
    public function open() : bool
    {
        //
        // create a list of options for the context 
        //
        $opts = [ 'socket' => [] ];
        
        //
        // bind to a local IP/port if it's set
        //
        if (strlen($this->localAddress) > 0) {
            /** @noinspection SpellCheckingInspection */
            $opts['socket']['bindto'] = $this->localAddress;
            if ($this->localPort > 0) {
                /** @noinspection SpellCheckingInspection */
                $opts['socket']['bindto'] .= ':' . $this->localPort;
            }
        }

        //
        // create the context
        //
        $context = stream_context_create($opts);

        //
        // create socket
        //
        $errno = 0;
        $errorString = "";

        switch($this->type) {
        case Socket::SOCK_STREAM:

            if ( Net_DNS2::isIPv4( $this->host ) ) {

                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $this->sock = @stream_socket_client(
                    'tcp://' . $this->host . ':' . $this->port, 
                    $errno, $errorString, $this->timeout,
                    STREAM_CLIENT_CONNECT, $context
                );
            } elseif ( Net_DNS2::isIPv6( $this->host ) ) {

                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $this->sock = @stream_socket_client(
                    'tcp://[' . $this->host . ']:' . $this->port, 
                    $errno, $errorString, $this->timeout,
                    STREAM_CLIENT_CONNECT, $context
                );
            } else {

                $this->lastError = 'invalid address type: ' . $this->host;
                return false;
            }

            break;
        
        case Socket::SOCK_DGRAM:

            if ( Net_DNS2::isIPv4( $this->host ) ) {

                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $this->sock = @stream_socket_client(
                    'udp://' . $this->host . ':' . $this->port, 
                    $errno, $errorString, $this->timeout,
                    STREAM_CLIENT_CONNECT, $context
                );
            } elseif ( Net_DNS2::isIPv6( $this->host ) ) {

                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $this->sock = @stream_socket_client(
                    'udp://[' . $this->host . ']:' . $this->port, 
                    $errno, $errorString, $this->timeout,
                    STREAM_CLIENT_CONNECT, $context
                );
            } else {

                $this->lastError = 'invalid address type: ' . $this->host;
                return false;
            }

            break;
            
        default:
            $this->lastError = 'Invalid socket type: ' . $this->type;
            return false;
        }

        if ($this->sock === false) {

            $this->lastError = $errorString;
            return false;
        }

        //
        // set it to non-blocking and set the timeout
        //
        stream_set_blocking($this->sock, false);
        stream_set_timeout($this->sock, $this->timeout);

        return true;
    }

    /**
     * closes a socket connection to the DNS server  
     *
     * @return bool
     * @access public
     *     
     */
    public function close() : bool
    {
        if (is_resource($this->sock) === true) {
            fclose($this->sock);
        }
        return true;
    }

    /**
     * writes the given string to the DNS server socket
     *
     * @param string $data a binary packed DNS packet
     *   
     * @return bool
     * @access public
     * @suppress PhanTypeMismatchArgumentInternal
     */
    public function write( string $data ) : bool
    {
        $length = strlen($data);
        if ($length == 0) {

            $this->lastError = 'empty data on write()';
            return false;
        }

        $read   = null;
        $write  = [ $this->sock ];
        $except = null;

        //
        // increment the date last used timestamp
        //
        $this->dateLastUsed = microtime(true);

        //
        // select on write
        //

        $result = stream_select($read, $write, $except, $this->timeout);
        if ($result === false) {

            $this->lastError = 'failed on write select()';
            return false;

        } elseif ($result == 0) {

            $this->lastError = 'timeout on write select()';
            return false;
        }

        //
        // if it's a TCP socket, then we need to pack and send the length of the
        // data as the first 16bit of data.
        //        
        if ($this->type == Socket::SOCK_STREAM) {

            $s = pack( 'n', $length );

            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            if (@fwrite($this->sock, $s) === false) {
                $this->lastError = 'failed to write 16bit length';
                return false;
            }
        }

        //
        // write the data to the socket
        //
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $size = @fwrite($this->sock, $data);
        if ( ($size === false) || ($size != $length) ) {
        
            $this->lastError = 'failed to write packet';
            return false;
        }

        return true;
    }


    /**   
     * reads a response from a DNS server
     *
     * @param int &$size    the size of the DNS packet read is passed back
     * @param int  $max_size the max data size returned.
     *
     * @return bool|string         returns the data on success and false on error
     * @access public
     *       
     */
    public function read( int & $size, int $max_size) : bool|string {
        $read   = [ $this->sock ];
        $write  = null;
        $except = null;

        //
        // increment the date last used timestamp
        //
        $this->dateLastUsed = microtime(true);

        //
        // make sure our socket is non-blocking
        //
        stream_set_blocking($this->sock, false );

        //
        // select on read
        //
        $result = stream_select($read, $write, $except, $this->timeout);
        if ($result === false) {

            $this->lastError = 'error on read select()';
            return false;

        } elseif ($result == 0) {

            $this->lastError = 'timeout on read select()';
            return false;
        }

        $length = $max_size;

        //
        // if it's a TCP socket, then the first two bytes is the length of the DNS
        // packet. we need to read that off first, then use that value for the
        // packet read.
        //
        if ($this->type == Socket::SOCK_STREAM) {
    
            if (($data = fread($this->sock, 2)) === false) {
                
                $this->lastError = 'failed on read for data length';
                return false;
            }
            if (strlen($data) == 0)
            {
                $this->lastError = 'failed on read for data length';
                return false;
            }

            $length = ord($data[0]) << 8 | ord($data[1]);
        }

        //
        // at this point, we know that there is data on the socket to be read,
        // because we've already extracted the length from the first two bytes.
        //
        // so the easiest thing to do, is just turn off socket blocking, and
        // wait for the data.
        //
        stream_set_blocking($this->sock, true);

        //
        // read the data from the socket
        //
        $data = '';

        //
        // the stream socket is weird for TCP sockets; it doesn't seem to always
        // return all the data properly; but the looping code I added broke UDP
        // packets. my fault.
        //
        // the "sockets" library works much better.
        //
        if ($this->type == Socket::SOCK_STREAM) {

            $chunk_size = $length;

            //
            // loop so we make sure we read all the data
            //
            while (1) {

                $chunk = fread($this->sock, $chunk_size);
                if ($chunk === false || strlen($chunk) == 0 ) {
            
                    $this->lastError = 'failed on read for data';
                    return false;
                }

                $data .= $chunk;
                $chunk_size -= strlen($chunk);

                if (strlen($data) >= $length) {
                    break;
                }
            }

        } else {

            //
            // if it's UDP, it's a single fixed-size frame, and the stream library
            // doesn't seem to have a problem reading it.
            //
            $data = fread($this->sock, $length);
            if ($data === false || strlen($data) == 0 ) {
            
                $this->lastError = 'failed on read for data';
                return false;
            }
        }
        
        $size = strlen($data);

        return $data;
    }


}
