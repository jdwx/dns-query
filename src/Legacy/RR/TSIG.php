<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\RR;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Lookups;
use JDWX\DNSQuery\Legacy\Packet\Packet;
use JDWX\DNSQuery\Legacy\Packet\RequestPacket;
use JDWX\DNSQuery\Legacy\RR\RR;


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
 * TSIG Resource Record - RFC 2845
 *
 *      0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7 0 1 2 3 4 5 6 7
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     /                          algorithm                            /
 *     /                                                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |                          time signed                          |
 *     |                               +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |                               |              fudge            |
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |            mac size           |                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               /
 *     /                              mac                              /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |           original id         |              error            |
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *     |          other length         |                               /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+                               /
 *     /                          other data                           /
 *     +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 *
 */
class TSIG extends RR {


    # TSIG Algorithm Identifiers
    public const string HMAC_MD5    = 'hmac-md5.sig-alg.reg.int';      # RFC 2845, required

    public const string GSS_TSIG    = 'gss-tsig';                      # unsupported, optional

    public const string HMAC_SHA1   = 'hmac-sha1';                    # RFC 4635, required

    public const string HMAC_SHA224 = 'hmac-sha224';                # RFC 4635, optional

    public const string HMAC_SHA256 = 'hmac-sha256';                # RFC 4635, required

    public const string HMAC_SHA384 = 'hmac-sha384';                # RFC 4635, optional

    public const string HMAC_SHA512 = 'hmac-sha512';                # RFC 4635, optional

    /** @var array<string, string> Map of hash values to names */
    public static array $hashAlgorithms = [
        self::HMAC_MD5 => 'md5',
        self::HMAC_SHA1 => 'sha1',
        self::HMAC_SHA224 => 'sha224',
        self::HMAC_SHA256 => 'sha256',
        self::HMAC_SHA384 => 'sha384',
        self::HMAC_SHA512 => 'sha512',
    ];

    /** @var string Algorithm used; only supports HMAC-MD5 */
    public string $algorithm;

    /** @var int Time it was signed */
    public int $timeSigned;

    /** @var int Allowed offset from the time signed */
    public int $fudge;

    /** @var int Size of the digest */
    public int $macSize;

    /** @var string Digest data */
    public string $mac;

    /** @var int Original id of the request */
    public int $originalId;

    /** @var int Additional error code */
    public int $error;

    /** @var int Length of the "other" data, should only ever be 0 when there is
     * no error, or 6 when there is the error RCODE_BADTIME
     */
    public int $otherLength;

    /** @var string Other data; should only ever be a timestamp when there is
     * the error RCODE_BADTIME
     */
    public string $otherData;

    /** @var string Key to use for signing - passed in, not included in the rdata */
    public string $key;


    /** @inheritDoc */
    protected function rrFromString( array $i_rData ) : bool {

        # The only value passed in is the key
        #
        # This assumes it's passed in base64 encoded.
        $this->key = preg_replace( '/\s+/', '', array_shift( $i_rData ) );

        # The rest of the data is set to default.
        $this->algorithm = self::HMAC_MD5;
        $this->timeSigned = time();
        $this->fudge = 300;
        $this->macSize = 0;
        $this->mac = '';
        $this->originalId = 0;
        $this->error = 0;
        $this->otherLength = 0;
        $this->otherData = '';

        # Per RFC 2845 section 2.3.
        $this->class = 'ANY';
        $this->ttl = 0;

        return true;
    }


    /** @inheritDoc */
    protected function rrGet( Packet $i_packet ) : ?string {
        if ( strlen( $this->key ) > 0 ) {

            # Create a new packet for the signature.
            $newPacket = new RequestPacket( 'example.com', 'SOA', 'IN' );

            # Copy the packet data over.
            $newPacket->copy( $i_packet );

            # Remove the TSIG object from the additional list.
            array_pop( $newPacket->additional );
            $newPacket->header->arCount = count( $newPacket->additional );

            # Copy out the data.
            $sigData = $newPacket->get();

            # Add the name without compressing.
            $sigData .= Binary::packNameUncompressed( $this->name );

            # Add the class and TTL.
            $sigData .= RecordClass::fromName( $this->class )->toBinary();
            $sigData .= Binary::packUINT32( $this->ttl );

            # Add the algorithm name without compression.
            $sigData .= Binary::packNameUncompressed( strtolower( $this->algorithm ) );

            #  Add the rest of the values.
            /** @noinspection SpellCheckingInspection */
            $sigData .= pack(
                'nNnnn', 0, $this->timeSigned, $this->fudge,
                $this->error, $this->otherLength
            );
            if ( $this->otherLength > 0 ) {

                $sigData .= pack( 'nN', 0, $this->otherData );
            }

            # Sign the data.
            $this->mac = $this->_signHMAC(
                $sigData, base64_decode( $this->key ), $this->algorithm
            );
            $this->macSize = strlen( $this->mac );

            # Compress the algorithm.
            $data = Binary::packNameUncompressed( strtolower( $this->algorithm ) );

            # Pack the time, fudge and mac size.
            $data .= pack(
                'nNnn', 0, $this->timeSigned, $this->fudge, $this->macSize
            );
            $data .= $this->mac;

            # Check the error and other_length.
            if ( $this->error === ReturnCode::BADTIME->value ) {

                $this->otherLength = strlen( $this->otherData );
                if ( $this->otherLength != 6 ) {

                    return null;
                }
            } else {

                $this->otherLength = 0;
                $this->otherData = '';
            }

            # Pack the id, error and other_length.
            $data .= pack(
                'nnn', $i_packet->header->id, $this->error, $this->otherLength
            );
            if ( $this->otherLength > 0 ) {

                $data .= pack( 'nN', 0, $this->otherData );
            }

            $i_packet->offset += strlen( $data );

            return $data;
        }

        return null;
    }


    /** @inheritDoc */
    protected function rrSet( Packet $i_packet ) : bool {
        if ( $this->rdLength > 0 ) {

            # Expand the algorithm.
            $newOffset = $i_packet->offset;
            $this->algorithm = $i_packet->expandEx( $newOffset );
            $offset = $newOffset - $i_packet->offset;

            # Unpack time, fudge and mac_size.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack(
                '@' . $offset . '/ntime_high/Ntime_low/nfudge/nmac_size',
                $this->rdata
            );

            $this->timeSigned = $parse[ 'time_low' ];
            $this->fudge = $parse[ 'fudge' ];
            $this->macSize = $parse[ 'mac_size' ];

            $offset += 10;

            # Copy out the mac.
            if ( $this->macSize > 0 ) {

                $this->mac = substr( $this->rdata, $offset, $this->macSize );
                $offset += $this->macSize;
            }

            # Unpack the original id, error, and other_length values.
            /** @noinspection SpellCheckingInspection */
            $parse = unpack(
                '@' . $offset . '/noriginal_id/nerror/nother_length',
                $this->rdata
            );

            $this->originalId = $parse[ 'original_id' ];
            $this->error = $parse[ 'error' ];
            $this->otherLength = $parse[ 'other_length' ];

            # The only time there is actually any "other data", is when there's
            # a BADTIME error code.
            #
            # The other length should be 6, and the other data field includes the
            # server's current time - per RFC 2845 section 4.5.2
            if ( $this->error === ReturnCode::BADTIME->value ) {

                if ( $this->otherLength != 6 ) {

                    return false;
                }

                # Other data is a 48bit timestamp.
                /** @noinspection SpellCheckingInspection */
                $parse = unpack(
                    'nhigh/nlow',
                    substr( $this->rdata, $offset + 6, $this->otherLength )
                );
                $this->otherData = $parse[ 'low' ];
            }

            return true;
        }

        return false;
    }


    /** @inheritDoc */
    protected function rrToString() : string {
        $out = $this->cleanString( $this->algorithm ) . '. ' .
            $this->timeSigned . ' ' .
            $this->fudge . ' ' . $this->macSize . ' ' .
            base64_encode( $this->mac ) . ' ' . $this->originalId . ' ' .
            $this->error . ' ' . $this->otherLength;

        if ( $this->otherLength > 0 ) {

            $out .= ' ' . $this->otherData;
        }

        return $out;
    }


    /**
     * signs the given data with the given key, and returns the result
     *
     * @param string $data the data to sign
     * @param string $key key to use for signing
     * @param string $algorithm the algorithm to use; defaults to MD5
     *
     * @return string the signed digest
     * @throws Exception
     * @access private
     *
     */
    private function _signHMAC( string $data, string $key, string $algorithm = self::HMAC_MD5 ) : string {
        if ( ! isset( self::$hashAlgorithms[ $algorithm ] ) ) {

            throw new Exception(
                'invalid or unsupported algorithm',
                Lookups::E_PARSE_ERROR
            );
        }

        return hash_hmac( self::$hashAlgorithms[ $algorithm ], $data, $key, true );
    }


}
