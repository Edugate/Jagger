<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ArrayType;

/**
 * Doctrine "array" type tolerant to ArrayCollection objects serialized by doctrine/collections < 1.3
 * (private property "_elements" instead of "elements").
 * On PHP >= 8.2 unserializing such data creates a deprecated dynamic property and DBAL ArrayType throws ConversionException.
 */
class LegacyArrayType extends ArrayType
{
    const LEGACY_ELEMENTS = 's:54:"' . "\0" . 'Doctrine\Common\Collections\ArrayCollection' . "\0" . '_elements"';
    const CURRENT_ELEMENTS = 's:53:"' . "\0" . 'Doctrine\Common\Collections\ArrayCollection' . "\0" . 'elements"';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }
        if (is_string($value)) {
            $value = str_replace(self::LEGACY_ELEMENTS, self::CURRENT_ELEMENTS, $value);
        }

        return parent::convertToPHPValue($value, $platform);
    }
}
