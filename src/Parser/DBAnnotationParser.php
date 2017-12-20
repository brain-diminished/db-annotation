<?php
namespace BrainDiminished\DBAnnotation\Parser;

use BrainDiminished\DBAnnotation\DBAnnotation;
use BrainDiminished\DBAnnotation\Exception\DBAnnotationException;

class DBAnnotationParser implements DBAnnotationParserInterface
{
    /** @var array<string, string> */
    private $classMap = [];

    /**
     * DBAnnotationParser constructor.
     * @param array<string, string> $classMap
     */
    public function __construct(array $classMap)
    {
        foreach ($classMap as $class) {
            if (!is_subclass_of($class, DBAnnotation::class)) {
                throw new DBAnnotationException("Class `$class` should implement interface `DBAnnotation`.");
            }
        }
        $this->classMap = $classMap;
    }

    /**
     * @param string $comment
     * @return DBAnnotation[]
     */
    final public function parse(string $comment): array
    {
        $annotations = [];
        while (preg_match('(@([^\W\d]\w+))', $comment, $matches, PREG_OFFSET_CAPTURE) !== 0) {
            $type = $matches[1][0];
            $comment = ltrim(substr($comment, $matches[1][1] + strlen($type)));
            $annotations[] = $this->parseAnnotation($type, $comment);
        }

        return $annotations;
    }

    /**
     * @param string $annotationType
     * @param string $stream
     * @return DBAnnotation
     */
    final private function parseAnnotation(string $annotationType, string &$stream): DBAnnotation
    {
        if (!empty($stream) && strpos($stream, '(') === 0) {
            $stream = ltrim(substr($stream, 1));
            $args = $this->parseArgList($stream);
        } else {
            $args = [];
        }

        return $this->instantiate($annotationType, $args);
    }

    /**
     * @param string $annotationType
     * @param array $args
     * @return DBAnnotation
     * @throws DBAnnotationException
     */
    final private function instantiate(string $annotationType, array $args): DBAnnotation
    {
        if (key_exists($annotationType, $this->classMap)) {
            $class = $this->classMap[$annotationType];
        } else {
            throw new DBAnnotationException("DBAnnotation $annotationType not recognized");
        }

        $reflectionClass = new \ReflectionClass($class);
        if ($reflectionClass->getConstructor() !== null) {
            $annotation = $reflectionClass->newInstanceArgs($args);
        } else if (empty($args)) {
            $annotation = $reflectionClass->newInstanceWithoutConstructor();
        } else {
            throw new DBAnnotationException("DBAnnotation $annotationType accepts no arguments");
        }
        if ($annotation instanceof DBAnnotation) {
            return $annotation;
        } else {
            throw new DBAnnotationException("Class `$class` should implement interface `DBAnnotation`.");
        }
    }

    /**
     * @param string $stream
     * @param string $closingChar
     * @return array
     * @throws DBAnnotationException
     */
    final private function parseArgList(string &$stream, string $closingChar = ')'): array
    {
        if (strpos($stream, $closingChar) === 0) {
            return [];
        }
        $args = [];
        while(true) {
            $args[] = $this->parseArg($stream);
            $stream = ltrim($stream);
            if (empty($stream)) {
                throw new DBAnnotationException('Unexpected end of expression: expected `)`');
            } else if ($stream[0] === $closingChar) {
                $stream = ltrim(substr($stream, 1));
                break;
            } else if ($stream[0] === ',') {
                $stream = ltrim(substr($stream, 1));
            } else {
                throw new DBAnnotationException("Unexpected token near `$stream`");
            }
        }

        return $args;
    }

    /**
     * @param $stream
     * @return array|bool|DBAnnotation|float|int|null
     * @throws DBAnnotationException
     */
    final private function parseArg(&$stream)
    {
        switch (true) {
            case strpos($stream, '[') === 0:
                $stream = ltrim(substr($stream, 1));
                return $this->parseArgList($stream, ']');
            case strpos($stream, 'null') === 0:
                $stream = ltrim(substr($stream, 4));
                return null;
            case strpos($stream, 'false') === 0:
                $stream = ltrim(substr($stream, 5));
                return false;
            case strpos($stream, 'true') === 0:
                $stream = ltrim(substr($stream, 4));
                return true;
            case preg_match('(^\d+)', $stream, $matches):
                $stream = ltrim(substr($stream, strlen($matches[0])));
                return intval($matches[0]);
            case preg_match('(^(\d+(\.\d*)?|(\d*\.)?\d+))', $stream, $matches):
                $stream = ltrim(substr($stream, strlen($matches[0])));
                return floatval($matches[0]);
            case preg_match('(^@([^\W\d]\w+))', $stream, $matches):
                $type = $matches[1];
                $stream = ltrim(substr($stream, strlen($matches[0])));
                return $this->parseAnnotation($type, $stream);
        }
        throw new DBAnnotationException("Unexpected token near `$stream`");
    }
}