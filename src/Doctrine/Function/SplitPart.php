<?php

declare(strict_types=1);

namespace App\Doctrine\Function;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * DQL function: SPLIT_PART(string, delimiter, position)
 * Mapeia para a função nativa do PostgreSQL.
 *
 * Uso: SPLIT_PART(a.codigo, '.', 4)
 */
class SplitPart extends FunctionNode
{
    private mixed $string;
    private mixed $delimiter;
    private mixed $position;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->delimiter = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->position = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'SPLIT_PART(%s, %s, %s)',
            $this->string->dispatch($sqlWalker),
            $this->delimiter->dispatch($sqlWalker),
            $this->position->dispatch($sqlWalker)
        );
    }
}
