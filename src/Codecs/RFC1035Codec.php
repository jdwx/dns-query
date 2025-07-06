<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Buffer\ReadBufferInterface;
use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Message\Header;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\Message;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\Question;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\OpaqueRData;
use JDWX\DNSQuery\ResourceRecord\RData;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\RDataValueInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecord;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;


class RFC1035Codec implements CodecInterface {


    /** @var array<string, int> $rLabelMap */
    public function __construct( private array $rLabelMap = [], private int $uOffset = 0 ) {}


    public static function decodeRDataHexBinary( ReadBufferInterface $i_buffer, int $i_uEndOfRData ) : string {
        return bin2hex( $i_buffer->consume( $i_uEndOfRData - $i_buffer->tell() ) );
    }


    public static function decodeRDataOption( ReadBufferInterface $i_buffer ) : Option {
        $uCode = $i_buffer->consumeUINT16();
        $uLength = $i_buffer->consumeUINT16();
        $data = $i_buffer->consume( $uLength );
        return new Option( $uCode, $data );
    }


    /** @return list<Option> */
    public static function decodeRDataOptionList( ReadBufferInterface $i_buffer, int $i_uEndOfRData ) : array {
        $rOut = [];
        while ( $i_buffer->tell() < $i_uEndOfRData ) {
            $rOut[] = self::decodeRDataOption( $i_buffer );
        }
        return $rOut;
    }


    public function decodeHeader( ReadBufferInterface $i_buffer ) : HeaderInterface {
        $uId = $i_buffer->consumeUINT16();
        $uFlagWord = $i_buffer->consumeUINT16();
        $qCount = $i_buffer->consumeUINT16();
        $aCount = $i_buffer->consumeUINT16();
        $auCount = $i_buffer->consumeUINT16();
        $adCount = $i_buffer->consumeUINT16();
        return new Header(
            $uId,
            $uFlagWord,
            $qCount,
            $aCount,
            $auCount,
            $adCount
        );
    }


    public function decodeMessage( ReadBufferInterface $i_buffer ) : ?MessageInterface {

        if ( ! $i_buffer->readyCheck() ) {
            return null;
        }

        $header = self::decodeHeader( $i_buffer );
        $question = [];
        $answer = [];
        $authority = [];
        $additional = [];
        $opt = null;

        for ( $ii = 0 ; $ii < $header->getQDCount() ; ++$ii ) {
            $question[] = self::decodeQuestion( $i_buffer );
        }

        for ( $ii = 0 ; $ii < $header->getANCount() ; ++$ii ) {
            $answer[] = self::decodeResourceRecord( $i_buffer );
        }

        for ( $ii = 0 ; $ii < $header->getNSCount() ; ++$ii ) {
            $authority[] = self::decodeResourceRecord( $i_buffer );
        }

        for ( $ii = 0 ; $ii < $header->getARCount() ; ++$ii ) {
            $rr = self::decodeResourceRecord( $i_buffer );
            if ( RecordType::OPT->is( $rr->typeValue() ) ) {
                if ( $opt !== null ) {
                    throw new RecordException( 'Multiple OPT records found in message.' );
                }
                $opt = $rr;
            }
            $additional[] = $rr;
        }

        // If we found an OPT record, create an EDNSMessage
        if ( $opt instanceof ResourceRecordInterface ) {
            return EDNSMessage::fromOptRecord(
                $header,
                $question,
                $answer,
                $authority,
                $additional,
                $opt
            );
        }

        // Otherwise create a regular Message
        return new Message(
            $header,
            $question,
            $answer,
            $authority,
            $additional
        );
    }


    public function decodeQuestion( ReadBufferInterface $i_buffer ) : QuestionInterface {
        $rName = $i_buffer->consumeNameArray();
        $uType = $i_buffer->consumeUINT16();
        $uClass = $i_buffer->consumeUINT16();
        return new Question( $rName, $uType, $uClass );
    }


    /**
     * @param array<string, RDataType> $i_rDataMap
     * @param ReadBufferInterface $i_buffer
     * @param int $i_uEndOfRData
     * @return RDataInterface
     */
    public function decodeRData( array $i_rDataMap, ReadBufferInterface $i_buffer, int $i_uEndOfRData ) : RDataInterface {
        $rData = [];

        /** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
        foreach ( $i_rDataMap as $stName => $rDataType ) {
            $rData[ $stName ] = self::decodeRDataValue( $rDataType, $i_buffer, $i_uEndOfRData );
        }
        $uOffset = $i_buffer->tell();
        assert( $uOffset === $i_uEndOfRData,
            "Offset after reading RData ({$uOffset}) does not equal end of RData ({$i_uEndOfRData})."
        );
        return new RData( $i_rDataMap, $rData );
    }


    /** @return list<string> */
    public function decodeRDataCharacterStringList( ReadBufferInterface $i_buffer, int $i_uEndOfRData ) : array {
        $rOut = [];
        while ( $i_buffer->tell() < $i_uEndOfRData ) {
            $rOut[] = $i_buffer->consumeLabel();
        }
        return $rOut;
    }


    public function decodeRDataValue( RDataType $i_rdt, ReadBufferInterface $i_buffer,
                                      int       $i_uEndOfRData ) : mixed {
        return match ( $i_rdt ) {
            RDataType::CharacterString => $i_buffer->consumeLabel(),
            RDataType::CharacterStringList => $this->decodeRDataCharacterStringList( $i_buffer, $i_uEndOfRData ),
            RDataType::DomainName => $i_buffer->consumeNameArray(),
            RDataType::HexBinary => self::decodeRDataHexBinary( $i_buffer, $i_uEndOfRData ),
            RDataType::IPv4Address => $i_buffer->consumeIPv4(),
            RDataType::IPv6Address => $i_buffer->consumeIPv6(),
            RDataType::Option => self::decodeRDataOption( $i_buffer ),
            RDataType::OptionList => self::decodeRDataOptionList( $i_buffer, $i_uEndOfRData ),
            RDataType::UINT8 => $i_buffer->consumeUINT8(),
            RDataType::UINT16 => $i_buffer->consumeUINT16(),
            RDataType::UINT32 => $i_buffer->consumeUINT32(),
        };
    }


    public function decodeResourceRecord( ReadBufferInterface $i_buffer ) : ResourceRecordInterface {
        $r = [
            'name' => $i_buffer->consumeName(),
            'type' => $i_buffer->consumeUINT16(),
            'class' => $i_buffer->consumeUINT16(),
            'ttl' => $i_buffer->consumeUINT32(),
        ];
        $uRDLength = $i_buffer->consumeUINT16();
        $uRDOffset = $i_buffer->tell();

        $map = RDataMaps::tryMap( $r[ 'type' ] );
        if ( is_array( $map ) ) {
            $rData = $this->decodeRData( RDataMaps::map( $r[ 'type' ] ), $i_buffer, $uRDOffset + $uRDLength );
        } else {
            $stData = $i_buffer->consume( $uRDLength );
            $rData = new OpaqueRData( $stData );
        }

        assert( $i_buffer->tell() - $uRDOffset === $uRDLength );
        $r[ 'rdata' ] = $rData;

        return ResourceRecord::fromArray( $r );
    }


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
        self::encodeHeader( $i_buffer, $i_msg->header() );
        assert( $this->uOffset === $i_buffer->length() );

        foreach ( $i_msg->getQuestion() as $q ) {
            self::encodeQuestion( $i_buffer, $q );
        }
        assert( $this->uOffset === $i_buffer->length(), "{$this->uOffset} !== {$i_buffer->length()}" );

        foreach ( $i_msg->getAnswer() as $rr ) {
            self::encodeResourceRecord( $i_buffer, $rr );
        }
        assert( $this->uOffset === $i_buffer->length() );

        foreach ( $i_msg->getAuthority() as $rr ) {
            self::encodeResourceRecord( $i_buffer, $rr );
        }
        assert( $this->uOffset === $i_buffer->length() );

        foreach ( $i_msg->getAdditional() as $rr ) {
            self::encodeResourceRecord( $i_buffer, $rr );
        }
        assert( $this->uOffset === $i_buffer->length() );

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


    public function encodeRDataValueDomainName( array $i_domainName ) : string {
        return Binary::packLabels( $i_domainName, $this->rLabelMap, $this->uOffset );
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
