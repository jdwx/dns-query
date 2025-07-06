<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Codecs;


use JDWX\DNSQuery\Buffer\WriteBufferInterface;
use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\DomainName;
use JDWX\DNSQuery\Message\EDNSMessage;
use JDWX\DNSQuery\Message\HeaderInterface;
use JDWX\DNSQuery\Message\MessageInterface;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\Question\QuestionInterface;
use JDWX\DNSQuery\ResourceRecord\RDataInterface;
use JDWX\DNSQuery\ResourceRecord\RDataValueInterface;
use JDWX\DNSQuery\ResourceRecord\ResourceRecordInterface;
use JDWX\Strict\TypeIs;


class PresentationEncoder implements EncoderInterface {


    public static function escapeString( string $i_value ) : string {
        if ( ! str_contains( $i_value, ' ' ) ) {
            return $i_value;
        }
        $i_value = str_replace( '\\', '\\\\', $i_value );
        $i_value = str_replace( '"', '\\"', $i_value );
        return '"' . $i_value . '"';
    }


    public function encodeHeader( WriteBufferInterface $i_buffer, HeaderInterface $i_hdr ) : void {
        $flagWord = $i_hdr->flagWord();
        $i_buffer->append( ';; ', $flagWord->qr === QR::QUERY ? 'Query' : 'Query Response', "\n" );
        $i_buffer->append( ';; ->>HEADER<<- opcode: ', $i_hdr->opcode(), ', status: ', $i_hdr->rCode(),
            ', id: ', $i_hdr->id(), "\n" );
        $i_buffer->append( ';; flags: ', $flagWord->flagString(), '; z: ', $flagWord->zBits->bits );
    }


    public function encodeMessage( WriteBufferInterface $i_buffer, MessageInterface $i_msg ) : void {
        // This is a placeholder implementation.
        // In a real implementation, you would convert the message to a presentation format.
        $this->encodeHeader( $i_buffer, $i_msg->header() );
        $i_buffer->append( '; QUERY: ', $i_msg->countQuestion(), ', ANSWER: ' . $i_msg->countAnswer(),
            ', AUTHORITY: ', $i_msg->countAuthority(), ', ADDITIONAL: ', $i_msg->countAdditional() . "\n\n" );

        if ( $i_msg instanceof EDNSMessage ) {
            /** @noinspection SpellCheckingInspection */
            $i_buffer->append( ";; OPT PSEUDOSECTION:\n", '; EDNS: version: ', $i_msg->getVersion()->value,
                ', flags: ', trim( $i_msg->getDo()->toFlag() ), '; payload: ' . $i_msg->getPayloadSize() . "\n" );
            $rOptions = $i_msg->getOptions();
            if ( ! empty( $rOptions ) ) {
                $i_buffer->append( ";; Options:\n" );
                foreach ( $i_msg->getOptions() as $option ) {
                    $i_buffer->append( $this->encodeRDataValueOption( $option ) );
                }
            }
        }

        $this->encodeSection( $i_buffer, $i_msg->getQuestion(), 'QUESTION' );
        $this->encodeSection( $i_buffer, $i_msg->getAnswer(), 'ANSWER' );
        $this->encodeSection( $i_buffer, $i_msg->getAuthority(), 'AUTHORITY' );
        $this->encodeSection( $i_buffer, $i_msg->getAdditional(), 'ADDITIONAL' );
    }


    public function encodeQuestion( WriteBufferInterface $i_buffer, QuestionInterface $i_question ) : void {
        $i_buffer->append( ';' . $i_question->name() . '. ' . $i_question->class() . ' ' . $i_question->type() );
    }


    public function encodeRData( WriteBufferInterface $i_buffer, RDataInterface $i_rData ) : void {
        $bFirst = true;
        foreach ( $i_rData->values() as $value ) {
            if ( $bFirst ) {
                $bFirst = false;
            } else {
                $i_buffer->append( ' ' );
            }
            $this->encodeRDataValue( $i_buffer, $value );
        }
    }


    public function encodeRDataValue( WriteBufferInterface $i_buffer, RDataValueInterface $i_rDataValue ) : void {
        $i_buffer->append( match ( $i_rDataValue->type() ) {
            RDataType::DomainName, RDataType::DomainNameUncompressed => $this->encodeRDataValueDomainName( $i_rDataValue->value() ),
            RDataType::CharacterString => self::escapeString( $i_rDataValue->value() ),
            RDataType::CharacterStringList => implode( ' ', array_map(
                [ self::class, 'escapeString' ],
                TypeIs::array( $i_rDataValue->value() )
            ) ),
            RDataType::Option => $this->encodeRDataValueOption( $i_rDataValue->value() ),
            default => strval( $i_rDataValue->value() )
        } );
    }


    /** @param list<string> $i_domainName */
    public function encodeRDataValueDomainName( array $i_domainName ) : string {
        return DomainName::format( $i_domainName ) . '.';
    }


    public function encodeRDataValueOption( Option $i_opt ) : string {
        return ';;   Code ' . $i_opt->code . ': ' . bin2hex( $i_opt->data ) . "\n";
    }


    public function encodeResourceRecord( WriteBufferInterface $i_buffer, ResourceRecordInterface $i_rr ) : void {
        $i_buffer->append( $i_rr->name(), '. ', $i_rr->getTTL(), ' ', $i_rr->class(), ' ', $i_rr->type(), ' ' );
        $this->encodeRData( $i_buffer, $i_rr->getRData() );
    }


    /**
     * @param WriteBufferInterface $i_buffer
     * @param array<QuestionInterface|ResourceRecordInterface> $i_rItems
     * @param string $i_stSectionName
     * @return void
     */
    protected function encodeSection( WriteBufferInterface $i_buffer, array $i_rItems, string $i_stSectionName ) : void {
        if ( empty( $i_rItems ) ) {
            return;
        }
        $i_buffer->append( ";; $i_stSectionName SECTION:\n" );
        foreach ( $i_rItems as $item ) {
            /** @phpstan-ignore-next-line */
            assert( $item instanceof QuestionInterface || $item instanceof ResourceRecordInterface );
            if ( $item instanceof QuestionInterface ) {
                $this->encodeQuestion( $i_buffer, $item );
                $i_buffer->append( "\n" );
                continue;
            } elseif ( $item->isType( 'OPT' ) ) {
                continue;
            }
            $this->encodeResourceRecord( $i_buffer, $item );
            $i_buffer->append( "\n" );
        }
        $i_buffer->append( "\n" );
    }


}
