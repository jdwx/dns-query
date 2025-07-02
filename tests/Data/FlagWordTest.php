<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Data;


use JDWX\DNSQuery\Data\AA;
use JDWX\DNSQuery\Data\FlagWord;
use JDWX\DNSQuery\Data\OpCode;
use JDWX\DNSQuery\Data\QR;
use JDWX\DNSQuery\Data\RA;
use JDWX\DNSQuery\Data\RD;
use JDWX\DNSQuery\Data\ReturnCode;
use JDWX\DNSQuery\Data\TC;
use JDWX\DNSQuery\Data\ZBits;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


#[CoversClass( FlagWord::class )]
final class FlagWordTest extends TestCase {


    public function testAuthoritativeResponse() : void {
        // Authoritative response
        $flagWord = new FlagWord(
            QR::RESPONSE,
            OpCode::QUERY,
            AA::AUTHORITATIVE,
            TC::NOT_TRUNCATED,
            RD::RECURSION_DESIRED,
            RA::RECURSION_AVAILABLE,
            new ZBits( 0 ),
            ReturnCode::NOERROR
        );

        // Should be 0x8580 (QR + AA + RD + RA)
        self::assertSame( 0x8580, $flagWord->value() );
        self::assertSame( 'qr aa rd ra', $flagWord->flagString() );
    }


    public function testConstructorWithAllParameters() : void {
        $flagWord = new FlagWord(
            QR::RESPONSE,
            OpCode::STATUS,
            AA::AUTHORITATIVE,
            TC::TRUNCATED,
            RD::RECURSION_NOT_DESIRED,
            RA::RECURSION_AVAILABLE,
            new ZBits( 7 ),
            ReturnCode::NXDOMAIN
        );

        self::assertSame( QR::RESPONSE, $flagWord->qr );
        self::assertSame( OpCode::STATUS, $flagWord->opCode );
        self::assertSame( AA::AUTHORITATIVE, $flagWord->aa );
        self::assertSame( TC::TRUNCATED, $flagWord->tc );
        self::assertSame( RD::RECURSION_NOT_DESIRED, $flagWord->rd );
        self::assertSame( RA::RECURSION_AVAILABLE, $flagWord->ra );
        self::assertSame( 7, $flagWord->zBits->bits );
        self::assertSame( ReturnCode::NXDOMAIN, $flagWord->rCode );
    }


    public function testConstructorWithBooleanValues() : void {
        $flagWord = new FlagWord(
            true,    // QR = RESPONSE
            i_aa: true,    // AA = AUTHORITATIVE
            i_tc: false,   // TC = NOT_TRUNCATED
            i_rd: true,    // RD = RECURSION_DESIRED
            i_ra: false    // RA = RECURSION_NOT_AVAILABLE
        );

        self::assertSame( QR::RESPONSE, $flagWord->qr );
        self::assertSame( AA::AUTHORITATIVE, $flagWord->aa );
        self::assertSame( TC::NOT_TRUNCATED, $flagWord->tc );
        self::assertSame( RD::RECURSION_DESIRED, $flagWord->rd );
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, $flagWord->ra );
    }


    public function testConstructorWithDefaults() : void {
        $flagWord = new FlagWord();

        self::assertSame( QR::QUERY, $flagWord->qr );
        self::assertSame( OpCode::QUERY, $flagWord->opCode );
        self::assertSame( AA::NON_AUTHORITATIVE, $flagWord->aa );
        self::assertSame( TC::NOT_TRUNCATED, $flagWord->tc );
        self::assertSame( RD::RECURSION_DESIRED, $flagWord->rd );
        self::assertSame( RA::RECURSION_NOT_AVAILABLE, $flagWord->ra );
        self::assertSame( 0, $flagWord->zBits->bits );
        self::assertSame( ReturnCode::NOERROR, $flagWord->rCode );
    }


    public function testConstructorWithMixedIntegerValues() : void {
        // Test with mixed integer types - some boolean-like (0/1), some actual values
        $flagWord = new FlagWord(
            true,  // QR = RESPONSE (using boolean for clarity)
            2,     // OpCode = STATUS
            true,  // AA = AUTHORITATIVE (using boolean)
            true,  // TC = TRUNCATED (using boolean)
            false, // RD = RECURSION_NOT_DESIRED (using boolean)
            true,  // RA = RECURSION_AVAILABLE (using boolean)
            3,     // Z bits
            3      // ReturnCode = NXDOMAIN
        );

        self::assertSame( QR::RESPONSE, $flagWord->qr );
        self::assertSame( OpCode::STATUS, $flagWord->opCode );
        self::assertSame( AA::AUTHORITATIVE, $flagWord->aa );
        self::assertSame( TC::TRUNCATED, $flagWord->tc );
        self::assertSame( RD::RECURSION_NOT_DESIRED, $flagWord->rd );
        self::assertSame( RA::RECURSION_AVAILABLE, $flagWord->ra );
        self::assertSame( 3, $flagWord->zBits->bits );
        self::assertSame( ReturnCode::NXDOMAIN, $flagWord->rCode );
    }


    public function testConstructorWithStringValues() : void {
        $flagWord = new FlagWord(
            'RESPONSE',
            'IQUERY',
            'Authoritative',
            'truncated',
            'recursion_not_desired',
            'recursion_available',
            i_rc: 'SERVFAIL'
        );
        
        self::assertSame( QR::RESPONSE, $flagWord->qr );
        self::assertSame( OpCode::IQUERY, $flagWord->opCode );
        self::assertSame( AA::AUTHORITATIVE, $flagWord->aa );
        self::assertSame( TC::TRUNCATED, $flagWord->tc );
        self::assertSame( RD::RECURSION_NOT_DESIRED, $flagWord->rd );
        self::assertSame( RA::RECURSION_AVAILABLE, $flagWord->ra );
        self::assertSame( ReturnCode::SERVFAIL, $flagWord->rCode );
    }


    public function testFlagString() : void {
        // Test query with no flags
        $flagWord = new FlagWord(
            QR::QUERY,
            OpCode::QUERY,
            AA::NON_AUTHORITATIVE,
            TC::NOT_TRUNCATED,
            RD::RECURSION_NOT_DESIRED,
            RA::RECURSION_NOT_AVAILABLE
        );
        self::assertSame( '', $flagWord->flagString() );

        // Test response with all flags
        $flagWord = new FlagWord(
            QR::RESPONSE,
            OpCode::QUERY,
            AA::AUTHORITATIVE,
            TC::TRUNCATED,
            RD::RECURSION_DESIRED,
            RA::RECURSION_AVAILABLE
        );
        self::assertSame( 'qr aa tc rd ra', $flagWord->flagString() );

        // Test with some flags
        $flagWord = new FlagWord(
            QR::RESPONSE,
            OpCode::QUERY,
            AA::NON_AUTHORITATIVE,
            TC::NOT_TRUNCATED,
            RD::RECURSION_DESIRED,
            RA::RECURSION_AVAILABLE
        );
        self::assertSame( 'qr rd ra', $flagWord->flagString() );
    }


    public function testFromFlagWord() : void {
        // Test with a known flag word value
        // QR=1, OpCode=0, AA=1, TC=0, RD=1, RA=1, Z=0, RCODE=0
        // Binary: 1000 0101 1000 0000 = 0x8580 = 34176
        $flagWord = FlagWord::fromFlagWord( 0x8580 );

        self::assertSame( QR::RESPONSE, $flagWord->qr );
        self::assertSame( OpCode::QUERY, $flagWord->opCode );
        self::assertSame( AA::AUTHORITATIVE, $flagWord->aa );
        self::assertSame( TC::NOT_TRUNCATED, $flagWord->tc );
        self::assertSame( RD::RECURSION_DESIRED, $flagWord->rd );
        self::assertSame( RA::RECURSION_AVAILABLE, $flagWord->ra );
        self::assertSame( 0, $flagWord->zBits->bits );
        self::assertSame( ReturnCode::NOERROR, $flagWord->rCode );
    }


    public function testFromFlagWordWithAllBitsSet() : void {
        // Test with various bits set
        // QR=1, OpCode=15 (invalid), AA=1, TC=1, RD=1, RA=1, Z=7, RCODE=15
        // Binary: 1111 1111 1111 1111 = 0xFFFF = 65535

        // OpCode 15 is invalid and will throw an exception
        self::expectException( \InvalidArgumentException::class );
        self::expectExceptionMessage( 'Unknown opcode in flag word: 15' );

        FlagWord::fromFlagWord( 0xFFFF );
    }


    public function testFromFlagWordWithValidMaximumValues() : void {
        // Test with maximum valid values for each field
        // QR=1, OpCode=6 (DSO - highest valid), AA=1, TC=1, RD=1, RA=1, Z=7, RCODE=5 (REFUSED)
        // Binary: 1011 0111 1111 0101 = 0xB7F5 = 47093
        $flagWord = FlagWord::fromFlagWord( 0xB7F5 );

        self::assertSame( QR::RESPONSE, $flagWord->qr );
        self::assertSame( OpCode::DSO, $flagWord->opCode );
        self::assertSame( AA::AUTHORITATIVE, $flagWord->aa );
        self::assertSame( TC::TRUNCATED, $flagWord->tc );
        self::assertSame( RD::RECURSION_DESIRED, $flagWord->rd );
        self::assertSame( RA::RECURSION_AVAILABLE, $flagWord->ra );
        self::assertSame( 7, $flagWord->zBits->bits );
        self::assertSame( ReturnCode::REFUSED, $flagWord->rCode );
    }


    public function testNormalize() : void {
        // Test with integer
        $flagWord = FlagWord::normalize( 0x8580 );
        self::assertInstanceOf( FlagWord::class, $flagWord );
        self::assertSame( QR::RESPONSE, $flagWord->qr );
        self::assertSame( AA::AUTHORITATIVE, $flagWord->aa );

        // Test with FlagWord instance
        $original = new FlagWord( QR::RESPONSE );
        $normalized = FlagWord::normalize( $original );
        self::assertSame( $original, $normalized );
    }


    public function testOpCodeValues() : void {
        // Test different OpCode values
        $opCodes = [
            OpCode::QUERY,
            OpCode::IQUERY,
            OpCode::STATUS,
        ];

        foreach ( $opCodes as $opCode ) {
            $flagWord = new FlagWord( i_opCode: $opCode );
            self::assertSame( $opCode, $flagWord->opCode );

            // Verify the opcode is in the correct position (bits 11-14)
            $value = $flagWord->value();
            $extractedOpCode = ( $value >> 11 ) & 0x0F;
            self::assertSame( $opCode->value, $extractedOpCode );
        }
    }


    public function testReturnCodeValues() : void {
        // Test different ReturnCode values
        $returnCodes = [
            ReturnCode::NOERROR,
            ReturnCode::FORMERR,
            ReturnCode::SERVFAIL,
            ReturnCode::NXDOMAIN,
            ReturnCode::NOTIMP,
            ReturnCode::REFUSED,
        ];

        foreach ( $returnCodes as $rCode ) {
            $flagWord = new FlagWord( i_rc: $rCode );
            self::assertSame( $rCode, $flagWord->rCode );

            // Verify the return code is in the correct position (bits 0-3)
            $value = $flagWord->value();
            $extractedRCode = $value & 0x0F;
            self::assertSame( $rCode->value, $extractedRCode );
        }
    }


    public function testRoundTrip() : void {
        // Create a FlagWord with known values
        $original = new FlagWord(
            QR::RESPONSE,
            OpCode::IQUERY,
            AA::AUTHORITATIVE,
            TC::NOT_TRUNCATED,
            RD::RECURSION_DESIRED,
            RA::RECURSION_AVAILABLE,
            new ZBits( 3 ),
            ReturnCode::NOTIMP
        );

        // Convert to integer
        $value = $original->value();

        // Convert back to FlagWord
        $restored = FlagWord::fromFlagWord( $value );

        // Compare all fields
        self::assertSame( $original->qr, $restored->qr );
        self::assertSame( $original->opCode, $restored->opCode );
        self::assertSame( $original->aa, $restored->aa );
        self::assertSame( $original->tc, $restored->tc );
        self::assertSame( $original->rd, $restored->rd );
        self::assertSame( $original->ra, $restored->ra );
        self::assertSame( $original->zBits->bits, $restored->zBits->bits );
        self::assertSame( $original->rCode, $restored->rCode );
    }


    public function testSetQR() : void {
        $flagWord = new FlagWord();

        // Test with enum
        $result = $flagWord->setQR( QR::RESPONSE );
        self::assertSame( $flagWord, $result ); // Test fluent interface
        self::assertSame( QR::RESPONSE, $flagWord->qr );

        // Test with boolean
        $flagWord->setQR( false );
        self::assertSame( QR::QUERY, $flagWord->qr );

        // Test with integer
        $flagWord->setQR( 1 );
        self::assertSame( QR::RESPONSE, $flagWord->qr );

        // Test with string
        $flagWord->setQR( 'QUERY' );
        self::assertSame( QR::QUERY, $flagWord->qr );
    }


    public function testSetRCode() : void {
        $flagWord = new FlagWord();

        // Test with enum
        $result = $flagWord->setRCode( ReturnCode::NXDOMAIN );
        self::assertSame( $flagWord, $result ); // Test fluent interface
        self::assertSame( ReturnCode::NXDOMAIN, $flagWord->rCode );

        // Test with integer
        $flagWord->setRCode( 2 );
        self::assertSame( ReturnCode::SERVFAIL, $flagWord->rCode );

        // Test with string
        $flagWord->setRCode( 'REFUSED' );
        self::assertSame( ReturnCode::REFUSED, $flagWord->rCode );
    }


    public function testTruncatedResponse() : void {
        // Truncated response
        $flagWord = new FlagWord(
            QR::RESPONSE,
            OpCode::QUERY,
            AA::NON_AUTHORITATIVE,
            TC::TRUNCATED,
            RD::RECURSION_DESIRED,
            RA::RECURSION_AVAILABLE,
            new ZBits( 0 ),
            ReturnCode::NOERROR
        );

        // Should be 0x8380 (QR + TC + RD + RA)
        self::assertSame( 0x8380, $flagWord->value() );
        self::assertSame( 'qr tc rd ra', $flagWord->flagString() );
    }


    public function testTypicalDnsQuery() : void {
        // Standard recursive query
        $flagWord = new FlagWord(
            QR::QUERY,
            OpCode::QUERY,
            AA::NON_AUTHORITATIVE,
            TC::NOT_TRUNCATED,
            RD::RECURSION_DESIRED,
            RA::RECURSION_NOT_AVAILABLE,
            new ZBits( 0 ),
            ReturnCode::NOERROR
        );

        // Should be 0x0100 (RD bit set)
        self::assertSame( 0x0100, $flagWord->value() );
        self::assertSame( 'rd', $flagWord->flagString() );
    }


    public function testTypicalDnsResponse() : void {
        // Standard recursive response
        $flagWord = new FlagWord(
            QR::RESPONSE,
            OpCode::QUERY,
            AA::NON_AUTHORITATIVE,
            TC::NOT_TRUNCATED,
            RD::RECURSION_DESIRED,
            RA::RECURSION_AVAILABLE,
            new ZBits( 0 ),
            ReturnCode::NOERROR
        );

        // Should be 0x8180 (QR + RD + RA)
        self::assertSame( 0x8180, $flagWord->value() );
        self::assertSame( 'qr rd ra', $flagWord->flagString() );
    }


    public function testValue() : void {
        // Test default values - should give us RD bit set (bit 8 = 256)
        $flagWord = new FlagWord();
        self::assertSame( 256, $flagWord->value() );

        // Test with all flags set to their "1" values
        $flagWord = new FlagWord(
            QR::RESPONSE,           // bit 15 = 32768
            OpCode::QUERY,          // bits 11-14 = 0
            AA::AUTHORITATIVE,      // bit 10 = 1024
            TC::TRUNCATED,          // bit 9 = 512
            RD::RECURSION_DESIRED,  // bit 8 = 256
            RA::RECURSION_AVAILABLE,// bit 7 = 128
            new ZBits( 7 ),         // bits 4-6 = 112
            ReturnCode::NOERROR     // bits 0-3 = 0
        );
        // 32768 + 1024 + 512 + 256 + 128 + 112 = 34800
        self::assertSame( 34800, $flagWord->value() );
    }


    public function testZBitsHandling() : void {
        // Test with different Z bit values
        for ( $i = 0 ; $i < 8 ; $i++ ) {
            $flagWord = new FlagWord(
                i_z: $i
            );

            self::assertSame( $i, $flagWord->zBits->bits );

            // Verify the bits are in the correct position (bits 4-6)
            $value = $flagWord->value();
            $extractedZ = ( $value >> 4 ) & 0x07;
            self::assertSame( $i, $extractedZ );
        }
    }


}