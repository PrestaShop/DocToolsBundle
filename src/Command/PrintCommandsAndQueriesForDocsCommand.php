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

namespace PrestaShop\DocToolsBundle\Command;

use PrestaShop\DocToolsBundle\CommandBus\Parser\CommandHandlerCollection;
use PrestaShop\DocToolsBundle\CommandBus\Printer\CommandDefinitionPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Prints all existing commands and queries to .md file for documentation
 */
class PrintCommandsAndQueriesForDocsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'prestashop:doc-tools:print-commands-and-queries';

    /**
     * Option name for providing destination directory path
     */
    private const DOCS_DIR_OPTION_NAME = 'docs-dir';

    /**
     * Option name for forcing command (remove all confirmations)
     */
    private const FORCE_OPTION_NAME = 'force';

    /**
     * @param CommandHandlerCollection $handlerDefinitionCollection
     * @param CommandDefinitionPrinter $commandDefinitionPrinter
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly CommandHandlerCollection $handlerDefinitionCollection,
        private readonly CommandDefinitionPrinter $commandDefinitionPrinter,
        private readonly Filesystem               $filesystem,
        private readonly string                   $internalCQRSFolder,
        private readonly ?string                  $defaultDocsFolder,
        private readonly array                    $commandsAndQueries,
    )
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $description = 'Prints available CQRS commands and queries to a file prepared for documentation';
        $example = sprintf(
            'Example: php ./bin/console %s --dir=/path/to/doc_project/src',
            self::$defaultName
        );
        $this
            ->setDescription($description)
            ->setHelp($description . PHP_EOL . PHP_EOL . $example)
            ->addOption(
                self::DOCS_DIR_OPTION_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to docs folder'
            )
            ->addOption(
                self::FORCE_OPTION_NAME,
                null,
                InputOption::VALUE_NONE,
                'Forces command to be executed without confirmations'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $destinationDir = $this->getDestinationDir($input);

        $force = $input->getOption(self::FORCE_OPTION_NAME);
        if ($force) {
            $output->writeln('<comment>Force erasing existing files</comment>');
        }

        $definitions = $this->handlerDefinitionCollection->getDefinitionsByDomain($this->commandsAndQueries);
        $this->commandDefinitionPrinter->printDefinitionsDocumentation($definitions, $destinationDir, $force);

        $output->writeln(sprintf('<info>Dumped commands & queries to %s</info>', $destinationDir));

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getDestinationDir(InputInterface $input): string
    {
        $docsPath = $input->getOption(self::DOCS_DIR_OPTION_NAME);
        if (!$docsPath) {
            if (null === $this->defaultDocsFolder) {
                throw new InvalidOptionException(sprintf(
                    'Option --%s is not provided. You must provide it or configure doc_tools_doc_path parameter',
                    self::DOCS_DIR_OPTION_NAME
                ));
            }
            $docsPath = rtrim($this->defaultDocsFolder, '/');
        }

        $destinationPath = $docsPath . '/' . ltrim($this->internalCQRSFolder, '/');
        if (!$this->filesystem->isAbsolutePath($destinationPath)) {
            throw new InvalidOptionException(sprintf(
                'Destination path %s invalid it must be an absolute path to a destination directory',
                $destinationPath
            ));
        }

        if ($this->filesystem->exists($destinationPath) && !is_dir($destinationPath)) {
            throw new InvalidOptionException(sprintf(
                '"%s" is not a directory',
                $destinationPath
            ));
        }

        return $destinationPath;
    }
}
