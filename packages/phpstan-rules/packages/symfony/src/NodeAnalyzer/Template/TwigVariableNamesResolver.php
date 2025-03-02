<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Symfony\NodeAnalyzer\Template;

use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use Symplify\Astral\Naming\SimpleNameResolver;
use Symplify\PHPStanRules\Contract\Templates\UsedVariableNamesResolverInterface;
use Symplify\PHPStanRules\Nette\PhpParser\NodeVisitor\TemplateVariableCollectingNodeVisitor;
use Symplify\PHPStanRules\Nette\PhpParser\ParentNodeAwarePhpParser;
use Symplify\TwigPHPStanCompiler\TwigToPhpCompiler;

final class TwigVariableNamesResolver implements UsedVariableNamesResolverInterface
{
    public function __construct(
        private TwigToPhpCompiler $twigToPhpCompiler,
        private SimpleNameResolver $simpleNameResolver,
        private NodeFinder $nodeFinder,
        private ParentNodeAwarePhpParser $parentNodeAwarePhpParser
    ) {
    }

    /**
     * @return string[]
     */
    public function resolveFromFilePath(string $filePath): array
    {
        $phpFileContent = $this->twigToPhpCompiler->compileContent($filePath, []);
        $stmts = $this->parentNodeAwarePhpParser->parsePhpContent($phpFileContent);

        $templateVariableCollectingNodeVisitor = new TemplateVariableCollectingNodeVisitor(
            ['context', 'macros', 'this', '_parent', 'loop', 'tmp'],
            ['doDisplay'],
            $this->simpleNameResolver,
            $this->nodeFinder
        );

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($templateVariableCollectingNodeVisitor);
        $nodeTraverser->traverse($stmts);

        return $templateVariableCollectingNodeVisitor->getUsedVariableNames();
    }
}
