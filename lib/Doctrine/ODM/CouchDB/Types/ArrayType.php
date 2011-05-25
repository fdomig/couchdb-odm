<?php

namespace Doctrine\ODM\CouchDB\Types;

class ArrayType extends Type
{
    public function convertToCouchDBValue($value)
    {
		return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value;
    }
}
