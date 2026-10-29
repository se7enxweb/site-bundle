<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use ReflectionMethod;
use Twig\Environment as BaseEnvironment;
use Twig\Source;
use Twig\Template;

use function sprintf;
use function str_replace;

final class Environment extends BaseEnvironment
{
    public function compileSource(Source $source): string
    {
        $compiledSource = parent::compileSource($source);

        if (!$this->isDebug()) {
            return $compiledSource;
        }

        $className = DebugTemplate::class;
        // Twig before 3.12 declares getTemplateName() without a return type. Ask Twig itself instead of the
        // installed version: se7enxweb/twig replaces twig/twig, whose version may then be unknown or a branch name.
        if (!(new ReflectionMethod(Template::class, 'getTemplateName'))->hasReturnType()) {
            $className = LegacyDebugTemplate::class;
        }

        return str_replace(
            ' extends Template',
            sprintf(' extends %s', $className),
            $compiledSource,
        );
    }
}
