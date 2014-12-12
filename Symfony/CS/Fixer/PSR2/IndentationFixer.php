<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\PSR2;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * Fixer for rules defined in PSR2 ¶2.4.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class IndentationFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);

        $this->convertTabs($tokens);
        $this->ensureFourSpaces($tokens);

        return $tokens->generateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 50;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Code MUST use an indent of 4 spaces, and MUST NOT use tabs for indenting.';
    }

    protected function convertTabs($tokens)
    {
        foreach ($tokens as $index => $token) {
            if ($token->isComment()) {
                $content = preg_replace('/^(?:(?<! ) {1,3})?\t/m', '\1    ', $token->getContent(), -1, $count);

                // Also check for more tabs.
                while ($count !== 0) {
                    $content = preg_replace('/^(\ +)?\t/m', '\1    ', $content, -1, $count);
                }

                $tokens[$index]->setContent($content);
                continue;
            }

            if ($token->isWhitespace()) {
                $tokens[$index]->setContent(preg_replace('/(?:(?<! ) {1,3})?\t/', '    ', $token->getContent()));
            }
        }
    }

    private function ensureFourSpaces(Tokens $tokens)
    {
        $level = 0;
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_OPEN_TAG)) {
                if ($tokens[$index + 1]->isWhitespace()) {
                    if ($this->calculateIndent($tokens[$index + 1]->getContent())) {
                        ++$level;
                    } else {
                        strpos($tokens[$index + 1]->getContent(), " ") === false ? : ++$level;
                    }

                    $indent = str_pad("", 4 * $level);
                    $tokens[$index + 1]->setContent(rtrim($tokens[$index + 1]->getContent(), " ") . $indent);
                }
                continue;
            }

            $type = Tokens::detectBlockType($token);
            if ($type) {
                $other = $tokens->findBlockEnd($type['type'], $index, $type['isStart']);
                while ($other !== $index) {
                    if ($this->hasNewline($tokens[$other])) {
                        $type['isStart'] ? ++$level : --$level;
                        break;
                    }

                    $type['isStart'] ? --$other : ++$other;
                }
            }

            $indent = str_pad("", 4 * $level);
            if ($this->hasNewline($token)) {
                $token->setContent(trim($token->getContent(), " ").$indent);
            }
            if ($token->isGivenKind(T_DOC_COMMENT)) {
                $token->setContent($this->fixDocBlock($token->getContent(), $indent));
            }
        }
    }

    /**
     * Calculate indentation from whitespace token.
     *
     * @param string $content Whitespace token content
     *
     * @return string
     */
    private function calculateIndent($content)
    {
        return ltrim(strrchr(str_replace(array("\r\n", "\r"), "\n", $content), 10), "\n");
    }

    /**
     * Check if token contains newline(s)
     *
     * @param string $token
     *
     * @return boolean
     */
    private function hasNewline($token)
    {
        return $token->isWhitespace() && !$token->isWhitespace(array('whitespaces' => " "));
    }

    /**
     * Fix indentation of Docblock.
     *
     * @param string $content Docblock contents
     * @param string $indent  Indentation to apply
     *
     * @return string Dockblock contents including correct indentation
     */
    private function fixDocBlock($content, $indent)
    {
        return ltrim(preg_replace('/^[ \t]*/m', $indent.' ', $content));
    }
}
