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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use PrestaShop\DocToolsBundle\CommandBus\Parser\CommandHandlerDefinitionParser;
use PrestaShop\DocToolsBundle\CommandBus\Parser\RegexpDomainParser;
use ReflectionException;
use Tests\Resources\Domain\Manufacturer\Command\AddManufacturerCommand;
use Tests\Resources\Domain\Manufacturer\Command\EditManufacturerCommand;
use Tests\Resources\Domain\Manufacturer\CommandHandler\AddManufacturerHandler;
use Tests\Resources\Domain\Manufacturer\CommandHandler\AddManufacturerHandlerInterface;
use Tests\Resources\Domain\Manufacturer\CommandHandler\EditManufacturerHandler;
use Tests\Resources\Domain\Manufacturer\CommandHandler\EditManufacturerHandlerInterface;
use Tests\Resources\Domain\Manufacturer\Query\GetManufacturerForEditing;
use Tests\Resources\Domain\Manufacturer\QueryHandler\GetManufacturerForEditingHandler;
use Tests\Resources\Domain\Manufacturer\QueryHandler\GetManufacturerForEditingHandlerInterface;
use Tests\Resources\Domain\Supplier\Command\AddSupplierCommand;
use Tests\Resources\Domain\Supplier\Command\EditSupplierCommand;
use Tests\Resources\Domain\Supplier\CommandHandler\AddSupplierHandler;
use Tests\Resources\Domain\Supplier\CommandHandler\EditSupplierHandler;
use Tests\Resources\Domain\Tax\Command\AddTaxCommand;
use Tests\Resources\Domain\Tax\Command\EditTaxCommand;
use Tests\Resources\Domain\Tax\CommandHandler\AddTaxHandler;
use Tests\Resources\Domain\Tax\CommandHandler\AddTaxHandlerInterface;
use Tests\Resources\Domain\Tax\CommandHandler\EditTaxHandler;
use Tests\Resources\Domain\Tax\CommandHandler\EditTaxHandlerInterface;
use Tests\Resources\Domain\Tax\Query\GetTaxForEditing;
use Tests\Resources\Domain\Tax\QueryHandler\GetTaxForEditingHandler;
use Tests\Resources\Domain\Tax\QueryHandler\GetTaxForEditingHandlerInterface;
use Tests\Resources\Domain\Tax\QueryResult\EditableTax;
use Tests\Resources\Domain\Tax\ValueObject\TaxId;

class CommandHandlerDefinitionParserTest extends TestCase
{
    /**
     * @var CommandHandlerDefinitionParser
     */
    private $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new CommandHandlerDefinitionParser(
            new RegexpDomainParser(RegexpDomainParser::TEST_DOMAIN_REGEXP)
        );
    }

    /**
     * @dataProvider getDataForInterfacesAssertion
     *
     * @param string $handler
     * @param string $command
     * @param array $expectedInterfaces
     */
    public function testItProvidesCorrectInterfacesWhenExistingHandlerIsGiven(string $handler, string $command, array $expectedInterfaces): void
    {
        $definition = $this->parser->parseDefinition($handler, $command);
        $this->assertEquals($expectedInterfaces, $definition->getHandlerInterfaces());
    }

    /**
     * @return Generator
     */
    public function getDataForInterfacesAssertion(): Generator
    {
        // Strong typing
        yield [EditTaxHandler::class, EditTaxCommand::class, [EditTaxHandlerInterface::class]];
        yield [AddTaxHandler::class, AddTaxCommand::class, [AddTaxHandlerInterface::class]];
        yield [GetTaxForEditingHandler::class, GetTaxForEditing::class, [GetTaxForEditingHandlerInterface::class]];

        // PHPDoc typing and inherited doc
        yield [EditManufacturerHandler::class, EditManufacturerCommand::class, [EditManufacturerHandlerInterface::class]];
        yield [AddManufacturerHandler::class, AddManufacturerCommand::class, [AddManufacturerHandlerInterface::class]];
        yield [GetManufacturerForEditingHandler::class, GetManufacturerForEditing::class, [GetManufacturerForEditingHandlerInterface::class]];
    }

    /**
     * @dataProvider getDataForClassNamesAssertion
     *
     * @param string $handler
     * @param string $command
     */
    public function testItProvidesCorrectClassNames(string $handler, string $command): void
    {
        $definition = $this->parser->parseDefinition($handler, $command);

        Assert::assertSame($handler, $definition->getHandlerClass());
        Assert::assertSame($command, $definition->getCommandClass());
    }

    /**
     * @return Generator
     */
    public function getDataForClassNamesAssertion(): Generator
    {
        // Strong typing
        yield [EditTaxHandler::class, EditTaxCommand::class];
        yield [AddTaxHandler::class, AddTaxCommand::class];
        yield [GetTaxForEditingHandler::class, GetTaxForEditing::class];

        // PHPDoc typing and inherited doc
        yield [EditManufacturerHandler::class, EditManufacturerCommand::class];
        yield [AddManufacturerHandler::class, AddManufacturerCommand::class];
        yield [GetManufacturerForEditingHandler::class, GetManufacturerForEditing::class];
    }

    /**
     * @dataProvider getDataForTypeAssertion
     *
     * @param string $handler
     * @param string $command
     * @param string $expectedType
     */
    public function testItProvidesCorrectType(string $handler, string $command, string $expectedType): void
    {
        $definition = $this->parser->parseDefinition($handler, $command);

        $this->assertEquals($expectedType, $definition->getType());
    }

    /**
     * @return Generator
     */
    public function getDataForTypeAssertion(): Generator
    {
        // Strong typing
        yield [EditTaxHandler::class, EditTaxCommand::class, 'command'];
        yield [AddTaxHandler::class, AddTaxCommand::class, 'command'];
        yield [GetTaxForEditingHandler::class, GetTaxForEditing::class, 'query'];

        // PHPDoc typing and inherited doc
        yield [EditManufacturerHandler::class, EditManufacturerCommand::class, 'command'];
        yield [AddManufacturerHandler::class, AddManufacturerCommand::class, 'command'];
        yield [GetManufacturerForEditingHandler::class, GetManufacturerForEditing::class, 'query'];
    }

    /**
     * @dataProvider getDataForDescriptionAssertion
     *
     * @param string $handler
     * @param string $command
     * @param string $expectedDescription
     */
    public function testItProvidesCorrectDescription(string $handler, string $command, string $expectedDescription): void
    {
        $definition = $this->parser->parseDefinition($handler, $command);

        $this->assertEquals($expectedDescription, $definition->getDescription());
    }

    public function getDataForDescriptionAssertion(): Generator
    {
        // Strong typing
        yield [EditTaxHandler::class, EditTaxCommand::class, 'Edits given tax with provided data (uses strong typing)'];
        yield [AddTaxHandler::class, AddTaxCommand::class, 'Adds given tax with provided data (uses strong typing)'];
        yield [GetTaxForEditingHandler::class, GetTaxForEditing::class, 'Gets tax for editing in Back Office (uses strong typing)'];

        // PHPDoc typing and inherited doc
        yield [EditManufacturerHandler::class, EditManufacturerCommand::class, 'Edits given manufacturer with provided data (uses PHPDoc typing)'];
        yield [AddManufacturerHandler::class, AddManufacturerCommand::class, 'Adds given manufacturer with provided data (uses PHPDoc typing)'];
        yield [GetManufacturerForEditingHandler::class, GetManufacturerForEditing::class, 'Gets manufacturer for editing in Back Office (uses PHPDoc typing)'];
    }

    /**
     * @dataProvider getDataForReflectionExceptionAssertion
     *
     * @param string $handler
     * @param string $command
     */
    public function testItThrowsExceptionWhenNonExistingCommandOrHandlerNameIsGiven(string $handler, string $command): void
    {
        $this->expectException(ReflectionException::class);

        $this->parser->parseDefinition($handler, $command);
    }

    /**
     * @return Generator
     */
    public function getDataForReflectionExceptionAssertion(): Generator
    {
        yield [EditTaxHandler::class, 'randomNoSuchClass'];
        yield ['randomNoSuchHandlerclass', AddManufacturerCommand::class];
    }

    /**
     * @dataProvider getDataForReturnTypeAssertion
     *
     * @param string $handler
     * @param string $command
     * @param string|null $returnType
     */
    public function testItProvidesCorrectReturnType(string $handler, string $command, ?string $returnType): void
    {
        $definition = $this->parser->parseDefinition($handler, $command);
        Assert::assertSame($returnType, $definition->getReturnType());
    }

    /**
     * @return Generator
     */
    public function getDataForReturnTypeAssertion(): Generator
    {
        // Strong typing
        yield [EditTaxHandler::class, EditTaxCommand::class, 'void'];
        yield [AddTaxHandler::class, AddTaxCommand::class, TaxId::class];
        yield [GetTaxForEditingHandler::class, GetTaxForEditing::class, EditableTax::class];

        // PHPDoc typing and inherited doc
        yield [EditManufacturerHandler::class, EditManufacturerCommand::class, 'void'];
        yield [AddManufacturerHandler::class, AddManufacturerCommand::class, 'ManufacturerId'];
        yield [GetManufacturerForEditingHandler::class, GetManufacturerForEditing::class, 'EditableManufacturer'];
    }

    /**
     * @dataProvider getDataForConstructorParamsAssertion
     *
     * @param string $handler
     * @param string $command
     * @param array $expectedParams
     */
    public function testItProvidesCorrectCommandConstructorParams(string $handler, string $command, array $expectedParams): void
    {
        $definition = $this->parser->parseDefinition($handler, $command);

        Assert::assertSame($expectedParams, $definition->getCommandConstructorParams());
    }

    /**
     * @return Generator
     */
    public function getDataForConstructorParamsAssertion(): Generator
    {
        // Using strong types
        yield [AddTaxHandler::class, AddTaxCommand::class, ['array $localizedNames', 'float $rate', 'bool $enabled']];
        yield [EditTaxHandler::class, EditTaxCommand::class, ['int $taxId']];
        yield [GetTaxForEditingHandler::class, GetTaxForEditing::class, ['int $taxId']];

        yield [AddSupplierHandler::class, AddSupplierCommand::class, [
            'string $name',
            'string $address',
            'string $city',
            'int $countryId',
            'bool $enabled',
            'array $localizedDescriptions',
            'array $localizedMetaTitles',
            'array $localizedMetaDescriptions',
            'array $localizedMetaKeywords',
            'array $shopAssociation',
            '?string $address2 = NULL',
            '?string $postCode = NULL',
            '?int $stateId = NULL',
            '?string $phone = NULL',
            'string $mobilePhone = \'\'',
            '?string $dni = NULL',
            '?int $zipCode = 0',
        ]];

        // Using PHPDoc
        yield [AddManufacturerHandler::class, AddManufacturerCommand::class, [
            'string $name',
            'bool $enabled',
            'string[] $localizedShortDescriptions',
            'string[] $localizedDescriptions',
            'string[] $localizedMetaTitles',
            'string[] $localizedMetaDescriptions',
            'string[] $localizedMetaKeywords',
            'array $shopAssociation',
        ]];
        yield [EditManufacturerHandler::class, EditManufacturerCommand::class, ['int $manufacturerId']];
        yield [GetManufacturerForEditingHandler::class, GetManufacturerForEditing::class, ['int $manufacturerId']];

        yield [EditSupplierHandler::class, EditSupplierCommand::class, [
            'int $supplierId',
            'string $name',
            'string $address',
            'string $city',
            'int $countryId',
            'bool $enabled',
            'string[] $localizedDescriptions',
            'string[] $localizedMetaTitles',
            'string[] $localizedMetaDescriptions',
            'array $localizedMetaKeywords',
            'array $associatedShops',
            '?string $address2 = NULL',
            '?string $postCode = NULL',
            '?int $stateId = NULL',
            '?string $phone = NULL',
            // Null accepted since there is no strict type, but default value is empty string
            '?string $mobilePhone = \'\'',
            '?string $dni = NULL',
            '?int $zipCode = 0',
        ]];
    }
}
