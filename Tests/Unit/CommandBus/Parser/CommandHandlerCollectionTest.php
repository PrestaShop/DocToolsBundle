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

namespace Tests\Unit\CommandBus\Parser;

use Generator;
use PHPUnit\Framework\TestCase;
use PrestaShop\DocToolsBundle\CommandBus\Parser\CommandHandlerCollection;
use PrestaShop\DocToolsBundle\CommandBus\Parser\CommandHandlerDefinition;
use PrestaShop\DocToolsBundle\CommandBus\Parser\CommandHandlerDefinitionParser;
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

class CommandHandlerCollectionTest extends TestCase
{
    private const TAX_DOMAIN = 'Tax';
    private const MANUFACTURER_DOMAIN = 'Manufacturer';

    /**
     * @var array<string, CommandHandlerDefinition>
     */
    private $handlerDefinitions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handlerDefinitions = [];
        $this->handlerDefinitions[AddTaxHandler::class] = $this->createDefinition(
            AddTaxHandler::class,
            AddTaxCommand::class,
            CommandHandlerDefinition::TYPE_COMMAND,
            self::TAX_DOMAIN
        );
        $this->handlerDefinitions[EditTaxHandler::class] = $this->createDefinition(
            EditTaxHandler::class,
            EditTaxCommand::class,
            CommandHandlerDefinition::TYPE_COMMAND,
            self::TAX_DOMAIN
        );
        $this->handlerDefinitions[GetTaxForEditingHandler::class] = $this->createDefinition(
            GetTaxForEditingHandler::class,
            GetTaxForEditing::class,
            CommandHandlerDefinition::TYPE_QUERY,
            self::TAX_DOMAIN
        );

        $this->handlerDefinitions[AddManufacturerHandler::class] = $this->createDefinition(
            AddManufacturerHandler::class,
            AddManufacturerCommand::class,
            CommandHandlerDefinition::TYPE_COMMAND,
            self::MANUFACTURER_DOMAIN
        );
        $this->handlerDefinitions[EditManufacturerHandler::class] = $this->createDefinition(
            EditManufacturerHandler::class,
            EditManufacturerCommand::class,
            CommandHandlerDefinition::TYPE_COMMAND,
            self::MANUFACTURER_DOMAIN
        );
        $this->handlerDefinitions[GetManufacturerForEditingHandler::class] = $this->createDefinition(
            GetManufacturerForEditingHandler::class,
            GetManufacturerForEditing::class,
            CommandHandlerDefinition::TYPE_QUERY,
            self::MANUFACTURER_DOMAIN
        );
    }

    /**
     * @dataProvider getDomainData
     */
    public function testGetDefinitionsByDomain(array $handlerAssociations, array $definitionsByDomain)
    {
        $parser = $this->mockParser();
        $collection = new CommandHandlerCollection($parser);
        $definitions = $collection->getDefinitionsByDomain($handlerAssociations);

        $this->assertEquals(count($definitionsByDomain), count($definitions));
        $this->assertEquals(array_keys($definitionsByDomain), array_keys($definitions));

        foreach ($definitionsByDomain as $expectedDomain => $expectedDomainDefinitions) {
            $domainDefinitions = $definitions[$expectedDomain];
            foreach ($expectedDomainDefinitions as $definitionType => $expectedDefinitionsByType) {
                $definitionsByType = $domainDefinitions[$definitionType];
                $this->assertEquals(count($expectedDefinitionsByType), count($definitionsByType));
                /**
                 * @var int $index
                 * @var CommandHandlerDefinition $definition
                 */
                foreach ($definitionsByType as $index => $definition) {
                    $expectedClass = $expectedDefinitionsByType[$index];
                    $this->assertEquals($expectedClass, $definition->getCommandClass());
                }
            }
        }
    }

    public function getDomainData(): Generator
    {
        yield [
            [
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                EditTaxHandler::class => EditTaxCommand::class,
                AddTaxHandler::class => AddTaxCommand::class,
            ],
            [
                self::TAX_DOMAIN => [
                    CommandHandlerDefinition::TYPE_QUERY => [
                        GetTaxForEditing::class,
                    ],
                    CommandHandlerDefinition::TYPE_COMMAND => [
                        AddTaxCommand::class,
                        EditTaxCommand::class,
                    ],
                ],
            ],
        ];

        yield [
            [
                EditTaxHandler::class => EditTaxCommand::class,
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                AddManufacturerHandler::class => AddManufacturerCommand::class,
                GetManufacturerForEditingHandler::class => GetManufacturerForEditing::class,
            ],
            [
                self::MANUFACTURER_DOMAIN => [
                    CommandHandlerDefinition::TYPE_QUERY => [
                        GetManufacturerForEditing::class,
                    ],
                    CommandHandlerDefinition::TYPE_COMMAND => [
                        AddManufacturerCommand::class,
                    ],
                ],
                self::TAX_DOMAIN => [
                    CommandHandlerDefinition::TYPE_QUERY => [
                        GetTaxForEditing::class,
                    ],
                    CommandHandlerDefinition::TYPE_COMMAND => [
                        EditTaxCommand::class,
                    ],
                ],
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
                self::MANUFACTURER_DOMAIN => [
                    CommandHandlerDefinition::TYPE_QUERY => [
                        GetManufacturerForEditing::class,
                    ],
                    CommandHandlerDefinition::TYPE_COMMAND => [
                        AddManufacturerCommand::class,
                        EditManufacturerCommand::class,
                    ],
                ],
                self::TAX_DOMAIN => [
                    CommandHandlerDefinition::TYPE_QUERY => [
                        GetTaxForEditing::class,
                    ],
                    CommandHandlerDefinition::TYPE_COMMAND => [
                        AddTaxCommand::class,
                        EditTaxCommand::class,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getDefinitionsData
     */
    public function testGetDefinitions(array $handlerAssociations, array $expectedDefinitions): void
    {
        $collection = new CommandHandlerCollection($this->mockParser());
        $definitions = $collection->getDefinitions($handlerAssociations);

        $this->assertEquals(count($expectedDefinitions), count($definitions));
        $index = 0;
        foreach ($expectedDefinitions as $expectedHandler => $expectedCommand) {
            $definition = $definitions[$index];
            $this->assertEquals($expectedHandler, $definition->getHandlerClass());
            $this->assertEquals($expectedCommand, $definition->getCommandClass());
            ++$index;
        }
    }

    public function getDefinitionsData(): Generator
    {
        yield [
            [
                EditTaxHandler::class => EditTaxCommand::class,
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                AddTaxHandler::class => AddTaxCommand::class,
            ],
            [
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                AddTaxHandler::class => AddTaxCommand::class,
                EditTaxHandler::class => EditTaxCommand::class,
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
                GetManufacturerForEditingHandler::class => GetManufacturerForEditing::class,
                AddManufacturerHandler::class => AddManufacturerCommand::class,
                EditManufacturerHandler::class => EditManufacturerCommand::class,
                GetTaxForEditingHandler::class => GetTaxForEditing::class,
                AddTaxHandler::class => AddTaxCommand::class,
                EditTaxHandler::class => EditTaxCommand::class,
            ],
        ];
    }

    /**
     * @param string $handlerClass
     * @param string $commandClass
     * @param string $type
     * @param string $domain
     *
     * @return CommandHandlerDefinition
     */
    private function createDefinition(
        string $handlerClass,
        string $commandClass,
        string $type,
        string $domain
    ): CommandHandlerDefinition {
        return new CommandHandlerDefinition(
            $type,
            $domain,
            $handlerClass,
            $commandClass,
            [],
            '',
            '',
            [],
            '',
            ''
        );
    }

    /**
     * @return CommandHandlerDefinitionParser
     */
    private function mockParser(): CommandHandlerDefinitionParser
    {
        $parserMock = $this
            ->getMockBuilder(CommandHandlerDefinitionParser::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $parserMock
            ->expects($this->atLeastOnce())
            ->method('parseDefinition')
            ->willReturnCallback(function (string $handlerClass) {
                return $this->handlerDefinitions[$handlerClass];
            })
        ;

        return $parserMock;
    }
}
