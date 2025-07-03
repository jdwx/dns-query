<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\DOK;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OptionCode;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


/**
 * An EDNSMessage is a DNS Message that supports EDNS (Extension Mechanisms for DNS).
 *
 * EDNS is represented on the wire as an OPT pseudo-RR in the additional section,
 * but this class abstracts that implementation detail and provides a cleaner API
 * for working with EDNS features.
 */
class EDNSMessage extends Message {


    /** Default UDP payload size for EDNS messages */
    public const int DEFAULT_PAYLOAD_SIZE = 4096;


    private int $uPayloadSize;

    private EDNSVersion $version;

    private DOK $do;

    /** @var list<Option> */
    private array $options;


    /**
     * @param HeaderInterface $header
     * @param list<QuestionInterface> $question
     * @param list<ResourceRecordInterface> $answer
     * @param list<ResourceRecordInterface> $authority
     * @param list<ResourceRecordInterface> $additional
     * @param int $uPayloadSize
     * @param EDNSVersion|int $version
     * @param DOK|bool|int $do
     * @param list<Option> $options
     */
    public function __construct( HeaderInterface $header,
                                 array           $question = [],
                                 array           $answer = [],
                                 array           $authority = [],
                                 array           $additional = [],
                                 int             $uPayloadSize = self::DEFAULT_PAYLOAD_SIZE,
                                 int|EDNSVersion $version = 0,
                                 bool|int|DOK    $do = false,
                                 array           $options = [] ) {

        # Go through the additional records and drop any OPT records.
        $additionalKeep = [];
        foreach ( $additional as $rr ) {
            if ( ! RecordType::OPT->is( $rr ) ) {
                $additionalKeep[] = $rr;
            }
        }
        $additional = $additionalKeep;
        $header->setARCount( count( $additional ) + 1 );

        parent::__construct( $header, $question, $answer, $authority, $additional );

        $this->setPayloadSize( $uPayloadSize );
        $this->version = EDNSVersion::normalize( $version );
        $this->do = DOK::normalize( $do );
        $this->options = $options;
    }


    /**
     * Create an EDNS request message.
     */
    public static function ednsRequest( string|QuestionInterface $domain,
                                        int|string|RecordType    $type = RecordType::ANY,
                                        int|string|RecordClass   $class = RecordClass::IN,
                                        int                      $payloadSize = self::DEFAULT_PAYLOAD_SIZE,
                                        DOK|bool                 $dnssecOK = false ) : self {
        $msg = parent::request( $domain, $type, $class );

        return new self(
            $msg->header(),
            $msg->getQuestion(),
            [],
            [],
            [],
            $payloadSize,
            0,
            $dnssecOK
        );
    }


    /**
     * Create an EDNS response message.
     */
    public static function ednsResponse( MessageInterface      $i_request,
                                         int|string|ReturnCode $i_rc = ReturnCode::NOERROR,
                                         int                   $payloadSize = self::DEFAULT_PAYLOAD_SIZE ) : self {
        $header = Header::response( $i_request->header(), $i_rc );

        // If the request was EDNS, preserve its settings
        if ( $i_request instanceof EDNSMessage ) {
            return new self(
                $header,
                $i_request->getQuestion(),
                [],
                [],
                [],
                $payloadSize,
                $i_request->getVersion(),
                $i_request->getDo()
            );
        }

        // Otherwise create a new EDNS response
        return new self(
            $header,
            $i_request->getQuestion(),
            [],
            [],
            [],
            $payloadSize
        );
    }


    /**
     * Convert a regular Message to an EDNSMessage.
     */
    public static function fromMessage( Message $message, ?ResourceRecordInterface $i_opt = null ) : self {

        if ( $i_opt instanceof ResourceRecordInterface ) {
            assert( $i_opt->isType( RecordType::OPT ), 'Record passed as OPT was ' . $i_opt->type() );
            return self::fromOptRecord(
                $message->header(),
                $message->getQuestion(),
                $message->getAnswer(),
                $message->getAuthority(),
                $message->getAdditional(),
                $i_opt
            );
        }

        foreach ( $message->getAdditional() as $rr ) {
            if ( $rr->isType( RecordType::OPT ) ) {
                // Found an OPT record, use it
                return self::fromOptRecord(
                    $message->header(),
                    $message->getQuestion(),
                    $message->getAnswer(),
                    $message->getAuthority(),
                    $message->getAdditional(),
                    $rr
                );
            }
        }

        // No OPT record, create with defaults
        return new self(
            $message->header(),
            $message->getQuestion(),
            $message->getAnswer(),
            $message->getAuthority(),
            $message->getAdditional(),
        );
    }


    /**
     * @param list<QuestionInterface> $question
     * @param list<ResourceRecordInterface> $answer
     * @param list<ResourceRecordInterface> $authority
     * @param list<ResourceRecordInterface> $additional
     */
    public static function fromOptRecord( HeaderInterface          $header,
                                          array                    $question,
                                          array                    $answer,
                                          array                    $authority,
                                          array                    $additional,
                                          ?ResourceRecordInterface $opt = null ) : self {
        if ( ! $opt instanceof ResourceRecordInterface ) {
            foreach ( $additional as $rr ) {
                if ( $rr->isType( RecordType::OPT ) ) {
                    $opt = $rr;
                    break;
                }
            }
        }
        assert( $opt instanceof ResourceRecordInterface, 'OPT not found.' );
        assert( $opt->isType( RecordType::OPT ), 'Record passed as OPT was ' . $opt->type() );

        $uPayloadSize = $opt->classValue();
        $uTTL = $opt->ttl();
        $version = EDNSVersion::fromFlagTTL( $uTTL );
        $do = DOK::fromFlagTTL( $uTTL );
        $options = $opt->tryGetRDataValue( 'options' ) ?? [];

        # Override the header return code
        $header->setRCode( ReturnCode::fromExtended( $header, $uTTL ) );

        return new self(
            $header,
            $question,
            $answer,
            $authority,
            $additional,
            $uPayloadSize,
            $version,
            $do,
            $options
        );

    }


    public function __toString() : string {

        /** @noinspection SpellCheckingInspection */
        return $this->header()
            . $this->stringSummary()
            . ";; OPT PSEUDOSECTION:\n"
            . '; EDNS: version: ' . $this->version->value . ', flags: ' . $this->stringFlags() . '; payload: '
            . $this->uPayloadSize . "\n"
            . $this->stringOptions()
            . $this->stringRecords();
    }


    /**
     * Add an EDNS option.
     */
    public function addOption( OptionCode|Option $option, ?string $data = null ) : void {
        if ( ! $option instanceof Option ) {
            if ( ! is_string( $data ) ) {
                throw new InvalidArgumentException( 'Option data is missing.' );
            }
            $option = new Option( $option->value, $data );
        }
        $this->options[] = $option;
    }


    public function getAdditional() : array {
        $r = parent::getAdditional();
        $r[] = $this->toOptResourceRecord();
        return $r;
    }


    /**
     * Get the DNSSEC OK bit.
     */
    public function getDo() : DOK {
        return $this->do;
    }


    /**
     * Get all EDNS options.
     *
     * @return list<Option>
     */
    public function getOptions() : array {
        return $this->options;
    }


    /**
     * Get the EDNS payload size.
     */
    public function getPayloadSize() : int {
        return $this->uPayloadSize;
    }


    /**
     * Get the EDNS version.
     */
    public function getVersion() : EDNSVersion {
        return $this->version;
    }


    public function option( int $i_uIndex ) : Option {
        $option = $this->tryOption( $i_uIndex );
        if ( $option instanceof Option ) {
            return $option;
        }
        throw new InvalidArgumentException( "Option at index {$i_uIndex} does not exist." );
    }


    /**
     * Set the DNSSEC OK bit.
     */
    public function setDo( bool|int|DOK $i_do ) : void {
        $this->do = DOK::normalize( $i_do );
    }


    /**
     * Set the EDNS payload size.
     */
    public function setPayloadSize( int $size ) : void {
        if ( $size < 0 ) {
            throw new InvalidArgumentException( 'Payload size must be a non-negative integer.' );
        }
        if ( $size > 65535 ) {
            throw new InvalidArgumentException( 'Payload size must not exceed 65535.' );
        }
        $this->uPayloadSize = $size;
    }


    /**
     * Set the EDNS version.
     */
    public function setVersion( int|EDNSVersion $version ) : void {
        $this->version = EDNSVersion::normalize( $version );
    }


    /**
     * Create an OPT resource record from this EDNS message.
     * This is used internally when encoding the message.
     */
    public function toOptResourceRecord() : ResourceRecordInterface {
        // Build the TTL field from EDNS flags
        $ttl = $this->version->toFlagTTL() | $this->do->toFlagTTL();

        return new ResourceRecord(
            [],                      // Name (always root for OPT)
            RecordType::OPT,         // Type
            $this->uPayloadSize,      // Class field stores payload size
            $ttl,                    // TTL field stores flags
            [ 'options' => $this->options ]
        );
    }


    /**
     * Get a specific option by index.
     */
    public function tryOption( int $index ) : ?Option {
        return $this->options[ $index ] ?? null;
    }


    protected function stringFlags() : string {
        return trim( $this->do->toFlag() );
    }


    protected function stringOptions() : string {
        if ( empty( $this->options ) ) {
            return '';
        }
        $st = ";; Options:\n";
        foreach ( $this->options as $option ) {
            $st .= ';;   Code ' . $option->code . ': ' . bin2hex( $option->data ) . "\n";
        }
        return $st;
    }


}