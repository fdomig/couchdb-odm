<?php

namespace Doctrine\ODM\CouchDB\Types;

class ArrayType extends Type
{
    public function convertToCouchDBValue(array $value)
    {
        return json_encode($value);
    }

    public function convertToPHPValue($value)
    {
        return json_decode($value);
    }
}
