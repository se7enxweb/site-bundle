<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Symfony\Component\Filesystem\Filesystem;
use Twig\Template;

use function dirname;
use function getcwd;
use function implode;
use function mb_stripos;
use function mb_substr;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function trim;

/**
 * Meant to be used as a Twig base template class.
 *
 * Wraps the yield method to:
 * - Inject debug info into template to be able to see in the markup which one is used
 */
abstract class BaseDebugTemplate extends Template
{
    private Filesystem $fileSystem;

    public function yield(array $context, array $blocks = []): iterable
    {
        $this->fileSystem = $this->fileSystem ?? new Filesystem();

        // Get parent result to be able to insert template name as HTML comments if applicable.
        // Layout template name will only appear at the end, to avoid potential quirks with old browsers
        // when comments appear before doctype declaration.
        // PHP 8.0 form of [...parent::yield()]: render once, keep every chunk (a generator repeats its keys)
        $result = parent::yield($context, $blocks);
        $templateResult = implode('', is_array($result) ? $result : iterator_to_array($result, false));

        $templateName = trim($this->fileSystem->makePathRelative($this->getSourceContext()->getPath(), dirname((string) getcwd())), '/');
        $isHtmlTemplate = substr_compare($templateName, 'html.twig', -strlen('html.twig')) === 0;
        $templateName = $isHtmlTemplate ? $templateName . ' (' . $this->getSourceContext()->getName() . ')' : $templateName;

        // Display start template comment, if applicable.
        if ($isHtmlTemplate) {
            if (strpos(trim($templateResult), '<!doctype') !== false) {
                $templateResult = (string) preg_replace(
                    '#(<!doctype[^>]+>)#im',
                    "$1\n<!-- START " . $templateName . ' -->',
                    $templateResult,
                );
            } else {
                yield "\n<!-- START " . $templateName . " -->\n";
            }
        }

        // Display stop template comment after result, if applicable.
        if ($isHtmlTemplate) {
            if (strpos($templateResult, '</body>') !== false) {
                $bodyPos = (int) mb_stripos($templateResult, '</body>');

                // Add layout template name before </body>, to avoid display quirks in some browsers.
                yield mb_substr($templateResult, 0, $bodyPos)
                     . "\n<!-- STOP " . $templateName . " -->\n"
                     . mb_substr($templateResult, $bodyPos);
            } else {
                yield $templateResult;

                yield "\n<!-- STOP " . $templateName . " -->\n";
            }
        } else {
            yield $templateResult;
        }
    }
}
