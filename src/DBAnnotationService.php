<?php
namespace BrainDiminished\DBAnnotation;

use BrainDiminished\DBAnnotation\Parser\DBAnnotationParser;
use BrainDiminished\DBAnnotation\Parser\DBAnnotationParserInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

class DBAnnotationService
{
    /** @var DBAnnotationParserInterface */
    protected $parser;

    public function __construct(DBAnnotationParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param Column $column
     * @return DBAnnotation[]
     */
    final public function getColumnAnnotations(Column $column): array
    {
        $comment = $column->getComment();
        if (empty($comment)) {
            return [];
        } else {
            return $this->parser->parse($comment);
        }
    }

    /**
     * @param Table $table
     * @return DBAnnotation[]
     */
    final public function getTableAnnotations(Table $table): array
    {
        if (!$table->hasOption('comment')) {
            return [];
        }

        $comment = $table->getOption('comment');
        return $this->parser->parse($comment);
    }
}
