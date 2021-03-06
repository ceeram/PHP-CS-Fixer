<?php

/*
 * This file is part of the Symfony CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\Symfony;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 */
class RemoveLinesBetweenUsesFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);

        $this->removeLineBreaksBetweenUseStatements($tokens);

        return $tokens->generateCode();
    }

    private function removeLineBreaksBetweenUseStatements(Tokens $tokens)
    {
        $namespacesImports = $tokens->getImportUseIndexes(true);

        if (!count($namespacesImports)) {
            return;
        }

        foreach ($namespacesImports as $uses) {
            $uses = array_reverse($uses);
            $this->fixLineBreaksPerImportGroup($tokens, $uses);
        }
    }

    /**
     * For each use token reach the nearest ; and ensure every
     * token after has one \n before next non empty token (next line).
     * It skips the first pass from the bottom.
     */
    private function fixLineBreaksPerImportGroup(Tokens $tokens, array $uses)
    {
        foreach ($uses as $key => $index) {
            $endIndex = $tokens->getNextTokenOfKind($index, array(';'));
            $afterSemicolonIndex = $tokens->getNextNonWhitespace($endIndex);

            if (null !== $afterSemicolonIndex && !$tokens[$afterSemicolonIndex]->isGivenKind(T_USE)) {
                continue;
            }

            $nextToken = $tokens[$endIndex + 1];
            if ($nextToken->isWhitespace()) {
                $nextToken->setContent(preg_replace('/\n{2,}/', "\n", $nextToken->getContent()));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // should be run before OrderedUseFixer
        return -5;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Removes line breaks between use statements.';
    }
}
