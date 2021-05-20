<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\DocToolsBundle\CommandBus\Printer;

use PrestaShop\DocToolsBundle\Util\String\StringModifier;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class CommandDefinitionPrinter
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var StringModifier
     */
    private $stringModifier;

    /**
     * @param Filesystem $filesystem
     * @param Environment $twig
     * @param StringModifier $stringModifier
     */
    public function __construct(
        Filesystem $filesystem,
        Environment $twig,
        StringModifier $stringModifier
    ) {
        $this->filesystem = $filesystem;
        $this->twig = $twig;
        $this->stringModifier = $stringModifier;
    }

    public function printDefinitionsDocumentation(
        array $definitions,
        string $destinationDir,
        bool $forceRefresh
    ): void {
        if ($forceRefresh) {
            $this->filesystem->remove($destinationDir);
        }

        foreach ($definitions as $domain => $definitionsByType) {
            $content = $this->twig->render('@PrestaShopDocTools/Commands/CQRS/cqrs-commands-list.md.twig', [
                'domain' => $domain,
                'definitionsByType' => $definitionsByType,
            ]);

            $this->filesystem->dumpFile($this->getDestinationFilePath($destinationDir, $domain), $content);
        }

        $indexFileContent = $this->twig->render('@PrestaShopDocTools/Commands/CQRS/cqrs-commands-index.md.twig');
        $this->filesystem->dumpFile(sprintf('%s/_index.md', $destinationDir), $indexFileContent);
    }

    /**
     * @param string $targetDir
     * @param string $domain
     *
     * @return string
     */
    private function getDestinationFilePath(string $targetDir, string $domain): string
    {
        return sprintf(
            '%s/%s.md',
            $targetDir,
            $this->stringModifier->convertCamelCaseToKebabCase($domain)
        );
    }
}
