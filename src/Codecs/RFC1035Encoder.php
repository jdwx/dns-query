<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\RDataValueInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class RFC1035Encoder implements EncoderInterface {


    /** @param array<string, int> $rLabelMap */
    public function __construct( private array $rLabelMap = [], private int $uOffset = 0 ) {}


    public function encodeHeader( WriteBufferInterface $i_buffer, HeaderInterface $i_hdr ) : void {
        $this->append(
            $i_buffer,
            Binary::packUINT16( $i_hdr->id() ),
            Binary::packUINT16( $i_hdr->flagWord()->value() ),
            Binary::packUINT16( $i_hdr->getQDCount() ),
            Binary::packUINT16( $i_hdr->getANCount() ),
            Binary::packUINT16( $i_hdr->getNSCount() ),
            Binary::packUINT16( $i_hdr->getARCount() )
        );
    }


    public function encodeMessage( WriteBufferInterface $i_buffer, MessageInterface $i_msg ) : void {
        $this->uOffset = 0;
        $this->rLabelMap = []; // Reset label map for each message
        $uBufferStart = $i_buffer->length();
        self::encodeHeader( $i_buffer, $i_msg->header() );
        assert( $this->uOffset === $i_buffer->length() - $uBufferStart,
            "Offset ({$this->uOffset}) & buffer ({$i_buffer->length()} - {$uBufferStart}) don't match" );

        foreach ( $i_msg->getQuestion() as $q ) {
            self::encodeQuestion( $i_buffer, $q );
        }
        assert( $this->uOffset === $i_buffer->length() - $uBufferStart,
            "Offset ({$this->uOffset}) & buffer ({$i_buffer->length()} - {$uBufferStart}) don't match" );

        foreach ( $i_msg->getAnswer() as $rr ) {
            self::encodeResourceRecord( $i_buffer, $rr );
        }
        assert( $this->uOffset === $i_buffer->length() - $uBufferStart,
            "Offset ({$this->uOffset}) & buffer ({$i_buffer->length()} - {$uBufferStart}) don't match" );

        foreach ( $i_msg->getAuthority() as $rr ) {
            self::encodeResourceRecord( $i_buffer, $rr );
        }
        assert( $this->uOffset === $i_buffer->length() - $uBufferStart,
            "Offset ({$this->uOffset}) & buffer ({$i_buffer->length()} - {$uBufferStart}) don't match" );

        foreach ( $i_msg->getAdditional() as $rr ) {
            self::encodeResourceRecord( $i_buffer, $rr );
        }
        assert( $this->uOffset === $i_buffer->length() - $uBufferStart,
            "Offset ({$this->uOffset}) & buffer ({$i_buffer->length()} - {$uBufferStart}) don't match" );

    }


    public function encodeQuestion( WriteBufferInterface $i_buffer, QuestionInterface $i_question ) : void {
        $this->append(
            $i_buffer,
            Binary::packLabels( $i_question->getName(), $this->rLabelMap, $this->uOffset ),
            Binary::packUINT16( $i_question->typeValue() ),
            Binary::packUINT16( $i_question->classValue() )
        );
    }


    public function encodeRData( WriteBufferInterface $i_buffer, RDataInterface $i_rData ) : void {
        $uOffset = $this->append( $i_buffer, Binary::packUINT16( 0 ) );
        foreach ( $i_rData->values() as $rDataValue ) {
            $this->encodeRDataValue( $i_buffer, $rDataValue );
        }
        $uRDLength = $i_buffer->length() - $uOffset - 2;
        $i_buffer->set( $uOffset, Binary::packUINT16( $uRDLength ) );
    }


    public function encodeRDataValue( WriteBufferInterface $i_buffer, RDataValueInterface $i_rDataValue ) : void {
        $st = match ( $i_rDataValue->type() ) {
            RDataType::CharacterString => $this->encodeRDataValueCharacterString( $i_rDataValue->value() ),
            RDataType::CharacterStringList => $this->encodeRDataValueCharacterStringList( $i_rDataValue->value() ),
            RDataType::DomainName => $this->encodeRDataValueDomainName( $i_rDataValue->value() ),
            RDataType::DomainNameUncompressed => $this->encodeRDataValueDomainNameUncompressed( $i_rDataValue->value() ),
            RDataType::HexBinary => $this->encodeRDataValueHexBinary( $i_rDataValue->value() ),
            RDataType::IPv4Address => Binary::packIPv4( $i_rDataValue->value() ),
            RDataType::IPv6Address => Binary::packIPv6( $i_rDataValue->value() ),
            RDataType::Option => self::encodeRDataValueOption( $i_rDataValue->value() ),
            RDataType::OptionList => self::encodeRDataValueOptionList( $i_rDataValue->value() ),
            RDataType::UINT8 => Binary::packUINT8( $i_rDataValue->value() ),
            RDataType::UINT16 => Binary::packUINT16( $i_rDataValue->value() ),
            RDataType::UINT32 => Binary::packUINT32( $i_rDataValue->value() ),
        };
        $this->append( $i_buffer, $st );
    }


    public function encodeRDataValueCharacterString( string $i_st ) : string {
        return Binary::packLabel( $i_st );
    }


    /** @param list<string> $i_rStrings */
    public function encodeRDataValueCharacterStringList( array $i_rStrings ) : string {
        $stOut = '';
        foreach ( $i_rStrings as $stString ) {
            $stOut .= $this->encodeRDataValueCharacterString( $stString );
        }
        return $stOut;
    }


    /** @param list<string> $i_domainName */
    public function encodeRDataValueDomainName( array $i_domainName ) : string {
        return Binary::packLabels( $i_domainName, $this->rLabelMap, $this->uOffset );
    }


    /** @param list<string> $i_domainName */
    public function encodeRDataValueDomainNameUncompressed( array $i_domainName ) : string {
        return Binary::packNameUncompressedArray( $i_domainName );
    }


    public function encodeRDataValueHexBinary( string $i_st ) : string {
        if ( ! preg_match( '/^[0-9a-fA-F]+$/', $i_st ) ) {
            throw new RecordDataException( "Invalid HexBinary value: {$i_st}" );
        }
        return hex2bin( $i_st );
    }


    public function encodeRDataValueOption( Option $i_option ) : string {
        $stOut = Binary::packUINT16( $i_option->code );
        $stOut .= Binary::packUINT16( strlen( $i_option->data ) );
        $stOut .= $i_option->data;
        return $stOut;
    }


    /** @param list<Option> $i_rOptions */
    public function encodeRDataValueOptionList( array $i_rOptions ) : string {
        $stOut = '';
        foreach ( $i_rOptions as $option ) {
            $stOut .= $this->encodeRDataValueOption( $option );
        }
        return $stOut;
    }


    public function encodeResourceRecord( WriteBufferInterface $i_buffer, ResourceRecordInterface $i_rr ) : void {
        $this->append(
            $i_buffer,
            Binary::packLabels( $i_rr->getName(), $this->rLabelMap, $this->uOffset ),
            Binary::packUINT16( $i_rr->typeValue() ),
            Binary::packUINT16( $i_rr->classValue() ),
            Binary::packUINT32( $i_rr->getTTL() ),
        );
        self::encodeRData( $i_buffer, $i_rr->getRData() );
    }


    /** @return array<string, int> */
    public function getLabelMap() : array {
        return $this->rLabelMap;
    }


    public function getOffset() : int {
        return $this->uOffset;
    }


    protected function append( WriteBufferInterface $i_buffer, int|string ...$i_rData ) : int {
        $u = $i_buffer->length();
        foreach ( $i_rData as $data ) {
            $stData = strval( $data );
            $i_buffer->append( $stData );
            $this->uOffset += strlen( $stData );
        }
        return $u;
    }


}
