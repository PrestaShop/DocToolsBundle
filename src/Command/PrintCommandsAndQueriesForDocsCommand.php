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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Prints all existing commands and queries to .md file for documentation
 */
class PrintCommandsAndQueriesForDocsCommand extends ContainerAwareCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'prestashop:doc-tools:print-commands-and-queries';

    /**
     * Option name for providing destination directory path
     */
    private const DESTINATION_DIR_OPTION_NAME = 'dir';

    /**
     * Option name for forcing command (remove all confirmations)
     */
    private const FORCE_OPTION_NAME = 'force';

    /**
     * @var CommandHandlerCollection
     */
    private $handlerDefinitionCollection;

    /**
     * @var CommandDefinitionPrinter
     */
    private $commandDefinitionPrinter;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $internalCQRSFolder;

    /**
     * @var string
     */
    private $defaultDocsFolder;

    /**
     * @param CommandHandlerCollection $handlerDefinitionCollection
     * @param CommandDefinitionPrinter $commandDefinitionPrinter
     * @param Filesystem $filesystem
     */
    public function __construct(
        CommandHandlerCollection $handlerDefinitionCollection,
        CommandDefinitionPrinter $commandDefinitionPrinter,
        Filesystem $filesystem,
        string $internalCQRSFolder,
        ?string $defaultDocsFolder
    ) {
        parent::__construct();
        $this->handlerDefinitionCollection = $handlerDefinitionCollection;
        $this->commandDefinitionPrinter = $commandDefinitionPrinter;
        $this->filesystem = $filesystem;
        $this->internalCQRSFolder = $internalCQRSFolder;
        $this->defaultDocsFolder = $defaultDocsFolder;
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $description = 'Prints available CQRS commands and queries to a file prepared for documentation';
        $example = sprintf(
            'Example: php ./bin/console %s --dir=/path/to/doc_project/src/content/1.7/development/architecture/domain/references',
            self::$defaultName
        );
        $this
            ->setDescription($description)
            ->setHelp($description . PHP_EOL . PHP_EOL . $example)
            ->addOption(
                self::DESTINATION_DIR_OPTION_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to file into which all commands and queries should be printed'
            )
            ->addOption(
                self::FORCE_OPTION_NAME,
                null,
                InputOption::VALUE_NONE,
                'Forces command to be executed without confirmations'
            )
        ;
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

        if (!$this->confirmExistingFileWillBeLost($destinationDir, $input, $output)) {
            $output->writeln('<comment>Cancelled</comment>');

            return null;
        }

        $handlerAssociations = $this->getContainer()->getParameter('doc_tools.commands_and_queries');
        $definitions = $this->handlerDefinitionCollection->getDefinitionsByDomain($handlerAssociations);

        $force = $input->getOption(self::FORCE_OPTION_NAME);
        $this->commandDefinitionPrinter->printDefinitionsDocumentation($definitions, $destinationDir, $force);

        $output->writeln(sprintf('<info>dumped commands & queries to %s</info>', $destinationDir));

        return 0;
    }

    /**
     * @param string $targetDir
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    private function confirmExistingFileWillBeLost(string $targetDir, InputInterface $input, OutputInterface $output): bool
    {
        $force = $input->getOption(self::FORCE_OPTION_NAME);

        if (null === $force || !$this->filesystem->exists($targetDir)) {
            return true;
        }

        $helper = $this->getHelper('question');
        $confirmation = new ConfirmationQuestion(sprintf(
            '<question>All data in directory "%s" will be lost. Proceed?</question>',
            $targetDir
        ));

        return (bool) $helper->ask($input, $output, $confirmation);
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getDestinationDir(InputInterface $input): string
    {
        $destinationPath = $input->getOption(self::DESTINATION_DIR_OPTION_NAME);
        if (!$destinationPath) {
            if (null === $this->defaultDocsFolder) {
                throw new InvalidOptionException(sprintf(
                    'Option --%s is not provided. You must provide it or configure doc_tools_doc_path parameter',
                    self::DESTINATION_DIR_OPTION_NAME
                ));
            }
            $destinationPath = rtrim('/', $this->defaultDocsFolder) . '/' . ltrim('/', $this->internalCQRSFolder);
        }

        if (!$this->filesystem->isAbsolutePath($destinationPath)) {
            throw new InvalidOptionException(sprintf(
                'Desination path %s invalid it must be an absolute path to a destination directory',
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
