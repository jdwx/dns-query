<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Exceptions;


use JDWX\DNSQuery\Legacy\Packet\RequestPacket;
use JDWX\DNSQuery\Legacy\Packet\ResponsePacket;


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
 * Exception type used by DNSQuery
 *
 */
class Exception extends \Exception {


    private ?RequestPacket $request;

    private ?ResponsePacket $response;


    /**
     * Constructor - overload the constructor so we can pass in the request
     *               and response object (when it's available)
     *
     * @param string $i_message the exception message
     * @param int $i_code the exception code
     * @param ?\Exception $i_previous the previous Exception object, if one exists
     * @param ?RequestPacket $i_request the RequestPacket object for this request, if available
     * @param ?ResponsePacket $i_response the ResponsePacket object for this request, if available
     */
    public function __construct(
        string          $i_message = '',
        int             $i_code = 0,
        ?\Exception     $i_previous = null,
        ?RequestPacket  $i_request = null,
        ?ResponsePacket $i_response = null
    ) {
        # Store the request/response objects (if passed).
        $this->request = $i_request;
        $this->response = $i_response;

        # Call the parent constructor.
        parent::__construct( $i_message, $i_code, $i_previous );
    }


    /**
     * returns the RequestPacket object (if available)
     *
     * @return ?RequestPacket object
     * @since  function available since release 1.3.1
     *
     */
    public function getRequest() : ?RequestPacket {
        return $this->request;
    }


    /**
     * returns the ResponsePacket object (if available)
     *
     * @return ?ResponsePacket object
     * @since  function available since release 1.3.1
     *
     */
    public function getResponse() : ?ResponsePacket {
        return $this->response;
    }


}
