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

use _PHPStan_b8e553790\Nette\Neon\Exception;
use PrestaShop\DocToolsBundle\CommandBus\Parser\CommandHandlerDefinition;
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
     * @var string
     */
    private $cqrsFolder;

    /**
     * @var string
     */
    private $partialFolder;

    /**
     * @var bool
     */
    private $forceRefresh;

    /**
     * @var string
     */
    private $destinationDir;

    /**
     * @param Filesystem $filesystem
     * @param Environment $twig
     * @param StringModifier $stringModifier
     * @param string $cqrsFolder
     */
    public function __construct(
        Filesystem $filesystem,
        Environment $twig,
        StringModifier $stringModifier,
        string $cqrsFolder,
        string $partialFolder
    ) {
        $this->filesystem = $filesystem;
        $this->twig = $twig;
        $this->stringModifier = $stringModifier;
        $this->cqrsFolder = $cqrsFolder;
        $this->partialFolder = $partialFolder;
    }

    public function printDefinitionsDocumentation(
        array $definitions,
        string $destinationDir,
        bool $forceRefresh
    ): void {
        $this->forceRefresh = $forceRefresh;
        $this->destinationDir = $destinationDir;
        if ($forceRefresh) {
            $this->filesystem->remove($destinationDir);
        }

        foreach ($definitions as $domain => $domainDefinitions) {
            $this->printDomainFile($domain, $domainDefinitions);
            foreach ($domainDefinitions[CommandHandlerDefinition::TYPE_COMMAND] as $definition) {
                $this->printDefinitionFile($definition, $domain);
            }
            foreach ($domainDefinitions[CommandHandlerDefinition::TYPE_QUERY] as $definition) {
                $this->printDefinitionFile($definition, $domain);
            }
        }

        $indexFilePath = sprintf('%s/_index.md', $destinationDir);
        if (!$forceRefresh && $this->filesystem->exists($indexFilePath)) {
            return;
        }
        $indexFileContent = $this->twig->render('@PrestaShopDocTools/Commands/CQRS/cqrs-commands-index.md.twig');
        $this->filesystem->dumpFile($indexFilePath, $indexFileContent);
    }

    /**
     * @param CommandHandlerDefinition $definition
     * @param string $domain
     */
    private function printDefinitionFile(CommandHandlerDefinition $definition, string $domain): void
    {
        $definitionFilePath = $this->getDefinitionFilePath($definition, $domain);
        if (!$this->forceRefresh && $this->filesystem->exists($definitionFilePath)) {
            return;
        }

        if ($definition->getType() === CommandHandlerDefinition::TYPE_QUERY) {
            $templatePath = '@PrestaShopDocTools/Commands/CQRS/cqrs-query.md.twig';
        } else {
            $templatePath = '@PrestaShopDocTools/Commands/CQRS/cqrs-command.md.twig';
        }

        $content = $this->twig->render($templatePath, [
            'definition' => $definition,
        ]);

        $this->filesystem->dumpFile($definitionFilePath, $content);
    }

    /**
     * @param string $domain
     * @param array $domainDefinitions
     */
    private function printDomainFile(
        string $domain,
        array $domainDefinitions
    ): void {
        $domainFilePath = $this->getDomainFilePath($domain);
        if (!$this->forceRefresh && $this->filesystem->exists($domainFilePath)) {
            return;
        }

        foreach ($domainDefinitions as $type => $definitions) {
            foreach ($definitions as $definition) {
                $definitionFilePath = $this->getDefinitionFilePath($definition, $domain);
                if (!$this->filesystem->exists($definitionFilePath)) {
                    if (!defined('_PS_VERSION_')) {
                        throw new Exception('Please define _PS_VERSION_ constant');
                    }
                    $definition->minver = _PS_VERSION_;
                }
            }
        }

        $content = $this->twig->render('@PrestaShopDocTools/Commands/CQRS/cqrs-domain.md.twig', [
            'domain' => $domain,
            'domainDefinitions' => $domainDefinitions,
            'partialFolder' => $this->getPartialFolderPath($domain),
        ]);

        $this->filesystem->dumpFile($domainFilePath, $content);
    }

    /**
     * @param CommandHandlerDefinition $definition
     * @param string $domain
     *
     * @return string
     */
    private function getDefinitionFilePath(CommandHandlerDefinition $definition, string $domain): string
    {
        return sprintf(
            '%s/%s/_partials/%s.md',
            $this->destinationDir,
            $this->stringModifier->convertCamelCaseToKebabCase($domain),
            $this->stringModifier->convertCamelCaseToKebabCase($definition->getSimpleCommandClass())
        );
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    private function getDomainFilePath(string $domain): string
    {
        return sprintf(
            '%s/%s/index.md',
            $this->destinationDir,
            $this->stringModifier->convertCamelCaseToKebabCase($domain)
        );
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    private function getPartialFolderPath(string $domain): string
    {
        return sprintf(
            '%s/%s/_partials',
            $this->partialFolder,
            $this->stringModifier->convertCamelCaseToKebabCase($domain)
        );
    }
}
