<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;




use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;


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
 * Exception handler used by Net_DNS2
 * 
 */
class Exception extends \Exception
{
    private ?RequestPacket $_request;
    private ?ResponsePacket $_response;

    /**
     * Constructor - overload the constructor so we can pass in the request
     *               and response object (when it's available)
     *
     * @param string                    $message  the exception message
     * @param int                       $code     the exception code
     * @param ?Exception                $previous the previous Exception object
     * @param ?RequestPacket  $request  the Net_DNS2_Packet_Request object for this request
     * @param ?ResponsePacket $response the Net_DNS2_Packet_Response object for this request
     *
     * @access public
     *
     */
    public function __construct(
        string                    $message = '',
        int                       $code = 0,
        ?Exception                $previous = null,
        ?RequestPacket  $request = null,
        ?ResponsePacket $response = null
    ) {
        //
        // store the request/response objects (if passed)
        //
        $this->_request = $request;
        $this->_response = $response;

        //
        // call the parent constructor
        //
        parent::__construct($message, $code, $previous);
    }

    /**
     * returns the Net_DNS2_Packet_Request object (if available)
     *
     * @return ?RequestPacket object
     * @access public
     * @since  function available since release 1.3.1
     *
     */
    public function getRequest() : ?RequestPacket
    {
        return $this->_request;
    }

    /**
     * returns the Net_DNS2_Packet_Response object (if available)
     *
     * @return ?ResponsePacket object
     * @access public
     * @since  function available since release 1.3.1
     *
     */
    public function getResponse() : ?ResponsePacket
    {
        return $this->_response;
    }
}
