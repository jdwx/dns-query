<?php /** @noinspection PhpUnused */


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Packet\RequestPacket;
use JDWX\DNSQuery\Packet\ResponsePacket;
use JDWX\DNSQuery\RR\ANY;
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
 * The main dynamic DNS updater class.
 *
 * This class provides functions to handle all defined dynamic DNS update
 * requests as defined by RFC 2136.
 *
 * This is separate from the Resolver class. While the underlying
 * protocol is the same, the functionality is completely different.
 *
 * Generally, query (recursive) lookups are done against a caching server, while
 * update requests are done against authoritative servers.
 *
 */
class Updater extends BaseQuery {


    /** @var RequestPacket Object used for the update request */
    private RequestPacket $packet;


    /**
     * Constructor - builds a new Updater objected used for doing
     * dynamic DNS updates
     *
     * @param string $i_zone the domain name to use for DNS updates
     * @param list<string>|string|null $i_nameServers The name server or list of name servers to update.
     *
     * @throws Exception
     */
    public function __construct( string $i_zone, array|string|null $i_nameServers = null ) {
        parent::__construct( $i_nameServers );

        # Create the packet.
        $this->packet = new RequestPacket(
            strtolower( trim( $i_zone, " \n\r\t." ) ), 'SOA', 'IN'
        );

        # Make sure the opcode on the packet is set to UPDATE.
        $this->packet->header->opcode = Lookups::OPCODE_UPDATE;
    }


    /**
     *   2.5.1 - Add To An RR set
     *
     *   RRs are added to the Update Section whose NAME, TYPE, TTL, RDLENGTH
     *   and RDATA are those being added, and CLASS is the same as the zone
     *   class.  Any duplicate RRs will be silently ignored by the primary
     *   master.
     *
     * @param RR $i_rr the RR object to be added to the zone
     *
     * @return void
     * @throws Exception
     */
    public function add( RR $i_rr ) : void {
        $this->_checkName( $i_rr->name );

        # Add the RR to the "update" section.
        if ( ! in_array( $i_rr, $this->packet->authority ) ) {
            $this->packet->authority[] = $i_rr;
        }
    }


    /**
     *   2.4.1 - RR set Exists (Value Independent)
     *
     *   At least one RR with a specified NAME and TYPE (in the zone and class
     *   specified in the Zone Section) must exist.
     *
     *   For this prerequisite, a requestor adds to the section a single RR
     *   whose NAME and TYPE are equal to that of the zone RR set whose
     *   existence is required.  rdLength is zero and RDATA is therefore
     *   empty.  CLASS must be specified as ANY to differentiate this
     *   condition from that of an actual RR whose rdLength is naturally zero
     *   (0) (e.g., NULL).  TTL is specified as zero (0).
     *
     * @param string $i_name the RR name for the prerequisite
     * @param string $i_type the RR type for the prerequisite
     *
     * @return void
     * @throws Exception
     */
    public function checkExists( string $i_name, string $i_type ) : void {
        $this->_checkName( $i_name );

        $class = Lookups::$rrTypesIdToClass[ Lookups::$rrTypesByName[ $i_type ] ];
        if ( ! class_exists( $class ) ) {
            throw new Exception(
                'unknown or unsupported resource record type: ' . $i_type,
                Lookups::E_RR_INVALID
            );
        }

        $rr = new $class();

        $rr->name = $i_name;
        $rr->ttl = 0;
        $rr->class = 'ANY';
        $rr->rdLength = -1;
        $rr->rdata = '';

        # Add the RR to the "prerequisite" section.
        if ( ! in_array( $rr, $this->packet->answer ) ) {
            $this->packet->answer[] = $rr;
        }

    }


    /**
     *   2.4.4 - Name Is In Use
     *
     *   Name is in use.  At least one RR with a specified NAME (in the zone
     *   and class specified by the Zone Section) must exist.  Note that this
     *   prerequisite is NOT satisfied by empty non-terminals.
     *
     *   For this prerequisite, a requestor adds to the section a single RR
     *   whose NAME is equal to that of the name whose ownership of an RR is
     *   required.  RDLENGTH is zero and RDATA is therefore empty.  CLASS must
     *   be specified as ANY to differentiate this condition from that of an
     *   actual RR whose RDLENGTH is naturally zero (0) (e.g., NULL).  TYPE
     *   must be specified as ANY to differentiate this case from that of an
     *   RR set existence test.  TTL is specified as zero (0).
     *
     * @param string $i_name the RR name for the prerequisite
     *
     * @return void
     * @throws Exception
     */
    public function checkNameInUse( string $i_name ) : void {
        $this->_checkName( $i_name );

        # The ANY RR class is just an empty stub class used for these
        # cases only.
        $rr = new ANY();

        $rr->name = $i_name;
        $rr->ttl = 0;
        $rr->type = 'ANY';
        $rr->class = 'ANY';
        $rr->rdLength = -1;
        $rr->rdata = '';

        # Add the RR to the "prerequisite" section.
        if ( ! in_array( $rr, $this->packet->answer ) ) {
            $this->packet->answer[] = $rr;
        }

    }


    /**
     *   2.4.5 - Name Is Not In Use
     *
     *   Name is not in use.  No RR of any type is owned by a specified NAME.
     *   Note that this prerequisite IS satisfied by empty non-terminals.
     *
     *   For this prerequisite, a requestor adds to the section a single RR
     *   whose NAME is equal to that of the name whose non-ownership of any RRs
     *   is required.  RDLENGTH is zero and RDATA is therefore empty.  CLASS
     *   must be specified as NONE.  TYPE must be specified as ANY.  TTL must
     *   be specified as zero (0).
     *
     * @param string $i_name the RR name for the prerequisite
     *
     * @return void
     * @throws Exception
     */
    public function checkNameNotInUse( string $i_name ) : void {
        $this->_checkName( $i_name );

        # The ANY RR class is just an empty stub class used for these
        # cases only.
        $rr = new ANY();

        $rr->name = $i_name;
        $rr->ttl = 0;
        $rr->type = 'ANY';
        $rr->class = 'NONE';
        $rr->rdLength = -1;
        $rr->rdata = '';

        # Add the RR to the "prerequisite" section.
        if ( ! in_array( $rr, $this->packet->answer ) ) {
            $this->packet->answer[] = $rr;
        }

    }


    /**
     *   2.4.3 - RR set Does Not Exist
     *
     *   No RRs with a specified NAME and TYPE (in the zone and class denoted
     *   by the Zone Section) can exist.
     *
     *   For this prerequisite, a requestor adds to the section a single RR
     *   whose NAME and TYPE are equal to that of the RR set whose nonexistence
     *   is required.  The RDLENGTH of this record is zero (0), and RDATA
     *   field is therefore empty.  CLASS must be specified as NONE in order
     *   to distinguish this condition from a valid RR whose RDLENGTH is
     *   naturally zero (0) (for example, the NULL RR).  TTL must be specified
     *   as zero (0).
     *
     * @param string $name the RR name for the prerequisite
     * @param string $type the RR type for the prerequisite
     *
     * @return void
     * @throws Exception
     */
    public function checkNotExists( string $name, string $type ) : void {
        $this->_checkName( $name );

        $class = Lookups::$rrTypesIdToClass[ Lookups::$rrTypesByName[ $type ] ];
        if ( ! class_exists( $class ) ) {
            throw new Exception(
                'unknown or unsupported resource record type: ' . $type,
                Lookups::E_RR_INVALID
            );
        }

        $rr = new $class();

        $rr->name = $name;
        $rr->ttl = 0;
        $rr->class = 'NONE';
        $rr->rdLength = -1;
        $rr->rdata = '';

        # Add the RR to the "prerequisite" section.
        if ( ! in_array( $rr, $this->packet->answer ) ) {
            $this->packet->answer[] = $rr;
        }

    }


    /**
     *   2.4.2 - RR set Exists (Value Dependent)
     *
     *   A set of RRs with a specified NAME and TYPE exists and has the same
     *   members with the same rData values as the RR set specified here in this
     *   section.  While RR set ordering is undefined and therefore not
     *   significant to this comparison, the sets be identical in their
     *   extent.
     *
     *   For this prerequisite, a requestor adds to the section an entire
     *   RR set whose preexistence is required.  NAME and TYPE are that of the
     *   RR set being denoted.  CLASS is that of the zone.  TTL must be
     *   specified as zero (0) and is ignored when comparing RR sets for
     *   identity.
     *
     * @param RR $rr the RR object to be used as a prerequisite
     *
     * @return bool
     * @throws Exception
     */
    public function checkValueExists( RR $rr ) : bool {
        $this->_checkName( $rr->name );

        $rr->ttl = 0;

        # Add the RR to the "prerequisite" section.
        if ( ! in_array( $rr, $this->packet->answer ) ) {
            $this->packet->answer[] = $rr;
        }

        return true;
    }


    /**
     *   2.5.4 - Delete An RR From An RR set
     *
     *   RRs to be deleted are added to the Update Section.  The NAME, TYPE,
     *   rdLength and RDATA must match the RR being deleted.  TTL must be
     *   specified as zero (0) and will otherwise be ignored by the primary
     *   master.  CLASS must be specified as NONE to distinguish this from an
     *   RR addition.  If no such RRs exist, then this Update RR will be
     *   silently ignored by the primary master.
     *
     * @param RR $rr the RR object to be deleted from the zone
     *
     * @return bool
     * @throws Exception
     */
    public function delete( RR $rr ) : bool {
        $this->_checkName( $rr->name );

        $rr->ttl = 0;
        $rr->class = 'NONE';

        # Add the RR to the "update" section.
        if ( ! in_array( $rr, $this->packet->authority ) ) {
            $this->packet->authority[] = $rr;
        }

        return true;
    }


    /**
     *   2.5.3 - Delete All RR sets From A Name
     *
     *   One RR is added to the Update Section whose NAME is that of the name
     *   to be cleansed of RR sets.  TYPE must be specified as ANY.  TTL must
     *   be specified as zero (0) and is otherwise not used by the primary
     *   master.  CLASS must be specified as ANY.  RDLENGTH must be zero (0)
     *   and RDATA must therefore be empty.  If no such RR sets exist, then
     *   this Update RR will be silently ignored by the primary master.
     *
     * @param string $i_name the RR name to be removed from the zone
     *
     * @return void
     * @throws Exception
     */
    public function deleteAll( string $i_name ) : void {
        $this->_checkName( $i_name );

        # The ANY RR class is just an empty stub class used for these
        # cases only
        $rr = new ANY();

        $rr->name = $i_name;
        $rr->ttl = 0;
        $rr->type = 'ANY';
        $rr->class = 'ANY';
        $rr->rdLength = -1;
        $rr->rdata = '';

        # Add the RR to the "update" section.
        if ( ! in_array( $rr, $this->packet->authority ) ) {
            $this->packet->authority[] = $rr;
        }

    }


    /**
     *   2.5.2 - Delete An RR set
     *
     *   One RR is added to the Update Section whose NAME and TYPE are those
     *   of the RR set to be deleted.  TTL must be specified as zero (0) and is
     *   otherwise not used by the primary master.  CLASS must be specified as
     *   ANY.  RDLENGTH must be zero (0) and RDATA must therefore be empty.
     *   If no such RR set exists, then this Update RR will be silently ignored
     *   by the primary master
     *
     * @param string $i_name the RR name to be removed from the zone
     * @param string $i_type the RR type to be removed from the zone
     *
     * @return void
     * @throws Exception
     */
    public function deleteAny( string $i_name, string $i_type ) : void {
        $this->_checkName( $i_name );

        $class = Lookups::$rrTypesIdToClass[ Lookups::$rrTypesByName[ $i_type ] ];
        if ( ! class_exists( $class ) ) {
            throw new Exception(
                'unknown or unsupported resource record type: ' . $i_type,
                Lookups::E_RR_INVALID
            );
        }

        $rr = new $class();

        $rr->name = $i_name;
        $rr->ttl = 0;
        $rr->class = 'ANY';
        $rr->rdLength = -1;
        $rr->rdata = '';

        # Add the RR to the "update" section.
        if ( ! in_array( $rr, $this->packet->authority ) ) {
            $this->packet->authority[] = $rr;
        }
    }


    /**
     * Return the current internal packet object.
     *
     * @return RequestPacket The current internal packet object
     */
    public function packet() : RequestPacket {

        # Take a copy
        $packet = $this->packet;

        # Check for an authentication method; either TSIG or SIG.
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
     * executes the update request with the object information
     *
     * @param ?ResponsePacket &$o_response ref to the response object or null to ignore response
     * @param-out ResponsePacket $o_response
     *
     * @return bool
     * @throws Exception
     */
    public function update( ?ResponsePacket &$o_response = null ) : bool {

        # Check for an authentication method; either TSIG or SIG.
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
        if ( ( $this->packet->header->qdCount == 0 )
            || ( $this->packet->header->nsCount == 0 )
        ) {
            throw new Exception(
                'empty headers- nothing to send!',
                Lookups::E_PACKET_INVALID
            );
        }

        # Send the packet and get back the response.
        $o_response = $this->sendPacket( $this->packet, $this->useTCP );

        # Clear the internal packet so if we make another request, we don't have
        # old data being sent.
        $this->packet->reset();

        # For updates, we just need to know it worked. we don't actually need to
        # return the response object.
        return true;
    }


    /**
     * checks that the given name matches the name for the zone we're updating
     *
     * @param string $i_name The name to be checked.
     *
     * @return void
     * @throws Exception
     */
    private function _checkName( string $i_name ) : void {
        if ( ! preg_match( '/' . $this->packet->question[ 0 ]->qName . '$/i', $i_name ) ) {

            throw new Exception(
                'name provided (' . $i_name . ') does not match zone name (' .
                $this->packet->question[ 0 ]->qName . ')',
                Lookups::E_PACKET_INVALID
            );
        }
    }


}
