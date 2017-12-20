<?php
namespace BrainDiminished\DBAnnotation\Parser;

use BrainDiminished\DBAnnotation\DBAnnotation;

interface DBAnnotationParserInterface
{
    /**
     * @param string $text
     * @return DBAnnotation[]
     */
    public function parse(string $text): array;
}
