<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


abstract class AbstractRData implements RDataInterface {


    public function count() : int {
        return count( $this->validKeys() );
    }


    /**
     * @param string $offset
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetExists( mixed $offset ) : bool {
        return in_array( $offset, $this->validKeys(), true );
    }


    /**
     * @param string $offset
     * @suppress PhanTypeMismatchDeclaredParamNullable
     */
    public function offsetUnset( mixed $offset ) : void {
        throw new \LogicException( 'Cannot unset RData values.' );
    }


    /** @return list<string> */
    abstract protected function validKeys() : array;


}
