<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Question;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\DNSQuery\Question\Question;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( Question::class )]
final class OpaqueQuestionTest extends TestCase {


    public function testAllStandardRecordClasses() : void {
        $classes = [
            RecordClass::IN->value,
            RecordClass::CH->value,
            RecordClass::HS->value,
            RecordClass::NONE->value,
            RecordClass::ANY->value,
        ];

        foreach ( $classes as $class ) {
            $question = new Question( 'example.com', RecordType::A->value, $class );
            self::assertSame( $class, $question->classValue() );
            self::assertInstanceOf( RecordClass::class, $question->getClass() );
        }
    }


    public function testAllStandardRecordTypes() : void {
        $types = [
            RecordType::A->value,
            RecordType::NS->value,
            RecordType::CNAME->value,
            RecordType::SOA->value,
            RecordType::PTR->value,
            RecordType::MX->value,
            RecordType::TXT->value,
            RecordType::AAAA->value,
            RecordType::SRV->value,
            RecordType::OPT->value,
        ];

        foreach ( $types as $type ) {
            $question = new Question( 'example.com', $type, RecordClass::IN->value );
            self::assertSame( $type, $question->typeValue() );
            self::assertInstanceOf( RecordType::class, $question->getType() );
        }
    }


    public function testCaseSensitiveDomainName() : void {
        $question = new Question( 'Example.COM', RecordType::A->value, RecordClass::IN->value );

        // Domain names are normalized to lowercase
        self::assertSame( [ 'example', 'com' ], $question->getName() );
        self::assertSame( 'example.com', $question->name() );
    }


    public function testClass() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::HS->value );

        self::assertSame( 'HS', $question->class() );
    }


    public function testClassWithUnknownValue() : void {
        $question = new Question( 'example.com', RecordType::A->value, 65535 );

        $this->expectException( RecordClassException::class );
        $question->class();
    }


    public function testConstructorWithArrayName() : void {
        $question = new Question( [ 'www', 'example', 'com' ], RecordType::AAAA->value, RecordClass::CH->value );

        self::assertSame( [ 'www', 'example', 'com' ], $question->getName() );
        self::assertSame( 'www.example.com', $question->name() );
        self::assertSame( RecordType::AAAA->value, $question->typeValue() );
        self::assertSame( RecordClass::CH->value, $question->classValue() );
    }


    public function testConstructorWithStringName() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        self::assertSame( [ 'example', 'com' ], $question->getName() );
        self::assertSame( 'example.com', $question->name() );
        self::assertSame( RecordType::A->value, $question->typeValue() );
        self::assertSame( RecordClass::IN->value, $question->classValue() );
    }


    public function testConstructorWithUnknownClass() : void {
        $question = new Question( 'example.com', RecordType::A->value, 65535 );
        self::assertSame( 65535, $question->classValue() );
    }


    public function testConstructorWithUnknownType() : void {
        $question = new Question( 'example.com', 65535, RecordClass::IN->value );
        self::assertSame( 65535, $question->typeValue() );
    }


    public function testEmptyLabelHandling() : void {
        $question = new Question( [], RecordType::A->value, RecordClass::IN->value );

        self::assertSame( [], $question->getName() );
        self::assertSame( '', $question->name() );  // Empty string for empty labels
    }


    public function testGetClass() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::CH->value );

        $class = $question->getClass();
        self::assertInstanceOf( RecordClass::class, $class );
        self::assertSame( RecordClass::CH, $class );
    }


    public function testGetClassWithUnknownValue() : void {
        $question = new Question( 'example.com', RecordType::A->value, 65535 );

        $this->expectException( RecordClassException::class );
        $this->expectExceptionMessage( 'Invalid record class: 65535' );

        $question->getClass();
    }


    public function testGetType() : void {
        $question = new Question( 'example.com', RecordType::MX->value, RecordClass::IN->value );

        $type = $question->getType();
        self::assertInstanceOf( RecordType::class, $type );
        self::assertSame( RecordType::MX, $type );
    }


    public function testGetTypeWithUnknownValue() : void {
        $question = new Question( 'example.com', 65535, RecordClass::IN->value );

        $this->expectException( RecordTypeException::class );
        $this->expectExceptionMessage( 'Invalid record type: 65535' );

        $question->getType();
    }


    public function testMultipleSetters() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setType( RecordType::MX );
        $question->setClass( RecordClass::CH );
        $question->setName( 'mail.example.org' );

        self::assertSame( 'mail.example.org', $question->name() );
        self::assertSame( 'MX', $question->type() );
        self::assertSame( 'CH', $question->class() );
    }


    public function testRootDomain() : void {
        $question = new Question( '.', RecordType::NS->value, RecordClass::IN->value );

        self::assertSame( [], $question->getName() );
        self::assertSame( '', $question->name() );  // Empty string for root domain
    }


    public function testSetClassWithEnum() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setClass( RecordClass::CH );

        self::assertSame( RecordClass::CH->value, $question->classValue() );
        self::assertSame( 'CH', $question->class() );
    }


    public function testSetClassWithInt() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setClass( RecordClass::HS->value );

        self::assertSame( RecordClass::HS->value, $question->classValue() );
        self::assertSame( 'HS', $question->class() );
    }


    public function testSetClassWithString() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setClass( 'NONE' );

        self::assertSame( RecordClass::NONE->value, $question->classValue() );
        self::assertSame( 'NONE', $question->class() );
    }


    public function testSetName() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setName( 'new.example.org' );

        self::assertSame( [ 'new', 'example', 'org' ], $question->getName() );
        self::assertSame( 'new.example.org', $question->name() );
    }


    public function testSetNameWithArray() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setName( [ 'mail', 'example', 'net' ] );

        self::assertSame( [ 'mail', 'example', 'net' ], $question->getName() );
        self::assertSame( 'mail.example.net', $question->name() );
    }


    public function testSetTypeWithEnum() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setType( RecordType::NS );

        self::assertSame( RecordType::NS->value, $question->typeValue() );
        self::assertSame( 'NS', $question->type() );
    }


    public function testSetTypeWithInt() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setType( RecordType::PTR->value );

        self::assertSame( RecordType::PTR->value, $question->typeValue() );
        self::assertSame( 'PTR', $question->type() );
    }


    public function testSetTypeWithString() : void {
        $question = new Question( 'example.com', RecordType::A->value, RecordClass::IN->value );

        $question->setType( 'SOA' );

        self::assertSame( RecordType::SOA->value, $question->typeValue() );
        self::assertSame( 'SOA', $question->type() );
    }


    public function testToString() : void {
        $question = new Question( 'example.com', RecordType::MX->value, RecordClass::IN->value );

        self::assertSame( 'example.com IN MX', (string) $question );
    }


    public function testToStringWithUnknownTypeAndClass() : void {
        $question = new Question( 'test.example.com', 12345, 67890 );

        // This will throw exceptions when trying to get the string representations
        $this->expectException( RecordClassException::class );

        $x = (string) $question;
        unset( $x );
    }


    public function testTrailingDot() : void {
        $question = new Question( 'example.com.', RecordType::A->value, RecordClass::IN->value );

        self::assertSame( [ 'example', 'com' ], $question->getName() );
        self::assertSame( 'example.com', $question->name() );
    }


    public function testType() : void {
        $question = new Question( 'example.com', RecordType::CNAME->value, RecordClass::IN->value );

        self::assertSame( 'CNAME', $question->type() );
    }


    public function testTypeWithUnknownValue() : void {
        $question = new Question( 'example.com', 65535, RecordClass::IN->value );

        $this->expectException( RecordTypeException::class );
        $question->type();
    }


}