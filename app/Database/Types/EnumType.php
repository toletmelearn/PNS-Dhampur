<?php

namespace App\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumType extends Type
{
    protected $values = [];
    protected $name = '';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return "VARCHAR(255)";
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function getName()
    {
        return $this->name;
    }
}