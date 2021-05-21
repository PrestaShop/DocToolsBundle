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

namespace Tests\Integration\CommandBus\Printer;

use Generator;
use PrestaShop\DocToolsBundle\CommandBus\Parser\CommandHandlerCollection;
use PrestaShop\DocToolsBundle\CommandBus\Printer\CommandDefinitionPrinter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Resources\Domain\Manufacturer\Command\AddManufacturerCommand;
use Tests\Resources\Domain\Manufacturer\Command\EditManufacturerCommand;
use Tests\Resources\Domain\Manufacturer\CommandHandler\AddManufacturerHandler;
use Tests\Resources\Domain\Manufacturer\CommandHandler\EditManufacturerHandler;
use Tests\Resources\Domain\Manufacturer\Query\GetManufacturerForEditing;
use Tests\Resources\Domain\Manufacturer\QueryHandler\GetManufacturerForEditingHandler;
use Tests\Resources\Domain\Tax\Command\AddTaxCommand;
use Tests\Resources\Domain\Tax\Command\EditTaxCommand;
use Tests\Resources\Domain\Tax\CommandHandler\AddTaxHandler;
use Tests\Resources\Domain\Tax\CommandHandler\EditTaxHandler;
use Tests\Resources\Domain\Tax\Query\GetTaxForEditing;
use Tests\Resources\Domain\Tax\QueryHandler\GetTaxForEditingHandler;

class CommandDefinitionPrinterTest extends KernelTestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $tempFolder;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->filesystem = new Filesystem();
        $this->tempFolder = sys_get_temp_dir() . '/cqrs_printer';
        $this->filesystem->remove($this->tempFolder);
        $this->filesystem->mkdir($this->tempFolder);
    }

    /**
     * @dataProvider getPrintData
     *
     * @param array $handlerAssociations
     */
    public function testPrint(array $handlerAssociations, array $expectedFiles): void
    {
        $handlersByDomain = $this->getHandlersByDomain($handlerAssociations);

        /** @var CommandDefinitionPrinter $printer */
        $printer = self::$container->get('prestashop.doc_tools.command_bus.printer.command_definition_printer');
        $printer->printDefinitionsDocumentation(
            $handlersByDomain,
            $this->tempFolder,
            true
        );

        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue($this->filesystem->exists($this->tempFolder . '/' . $expectedFile));
        }
    }

    public function getPrintData(): Generator
    {
        yield [
            [
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                EditTaxHandler::class => EditTaxCommand::class,
                AddTaxHandler::class => AddTaxCommand::class,
                EditManufacturerHandler::class => EditManufacturerCommand::class,
                AddManufacturerHandler::class => AddManufacturerCommand::class,
                GetManufacturerForEditingHandler::class => GetManufacturerForEditing::class,
            ],
            [
                '_index.md',
                'manufacturer.md',
                'tax.md',
            ],
        ];
    }

    /**
     * @dataProvider getPrintReplaceData
     *
     * @param array $handlerAssociations
     */
    public function testPrintReplace(array $handlerAssociations, array $existingFiles, array $expectedFiles): void
    {
        foreach ($existingFiles as $existingFile) {
            $this->filesystem->touch($this->tempFolder . '/' . $existingFile);
            $this->assertEquals('', file_get_contents($this->tempFolder . '/' . $existingFile));
        }

        $handlersByDomain = $this->getHandlersByDomain($handlerAssociations);

        /** @var CommandDefinitionPrinter $printer */
        $printer = self::$container->get('prestashop.doc_tools.command_bus.printer.command_definition_printer');
        $printer->printDefinitionsDocumentation(
            $handlersByDomain,
            $this->tempFolder,
            false
        );

        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue($this->filesystem->exists($this->tempFolder . '/' . $expectedFile));
            if (in_array($expectedFile, $existingFiles)) {
                $this->assertEquals('', file_get_contents($this->tempFolder . '/' . $expectedFile));
            } else {
                $this->assertNotEquals('', file_get_contents($this->tempFolder . '/' . $expectedFile));
            }
        }
    }

    public function getPrintReplaceData(): Generator
    {
        yield [
            [
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                EditTaxHandler::class => EditTaxCommand::class,
                AddTaxHandler::class => AddTaxCommand::class,
                EditManufacturerHandler::class => EditManufacturerCommand::class,
                AddManufacturerHandler::class => AddManufacturerCommand::class,
                GetManufacturerForEditingHandler::class => GetManufacturerForEditing::class,
            ],
            [
                '_index.md',
                'manufacturer.md',
                'tax.md',
            ],
            [
                '_index.md',
                'manufacturer.md',
                'tax.md',
            ],
        ];

        yield [
            [
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                EditTaxHandler::class => EditTaxCommand::class,
                AddTaxHandler::class => AddTaxCommand::class,
                EditManufacturerHandler::class => EditManufacturerCommand::class,
                AddManufacturerHandler::class => AddManufacturerCommand::class,
                GetManufacturerForEditingHandler::class => GetManufacturerForEditing::class,
            ],
            [
                'manufacturer.md',
                'tax.md',
            ],
            [
                '_index.md',
                'manufacturer.md',
                'tax.md',
            ],
        ];

        yield [
            [
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                EditTaxHandler::class => EditTaxCommand::class,
                AddTaxHandler::class => AddTaxCommand::class,
                EditManufacturerHandler::class => EditManufacturerCommand::class,
                AddManufacturerHandler::class => AddManufacturerCommand::class,
                GetManufacturerForEditingHandler::class => GetManufacturerForEditing::class,
            ],
            [
                '_index.md',
            ],
            [
                '_index.md',
                'manufacturer.md',
                'tax.md',
            ],
        ];
    }

    private function getHandlersByDomain(array $handlerAssociations): array
    {
        /** @var CommandHandlerCollection $collection */
        $collection = self::$container->get('prestashop.doc_tools.command_bus.parser.command_handler_collection');

        return $collection->getDefinitionsByDomain($handlerAssociations);
    }
}
