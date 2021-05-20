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

namespace PrestaShop\DocToolsBundle\CommandBus\Parser;

class CommandHandlerCollection
{
    /**
     * @var CommandHandlerDefinitionParser
     */
    private $handlerDefinitionParser;

    /**
     * @param CommandHandlerDefinitionParser $handlerDefinitionParser
     */
    public function __construct(
        CommandHandlerDefinitionParser $handlerDefinitionParser
    ) {
        $this->handlerDefinitionParser = $handlerDefinitionParser;
    }

    /**
     * @param array $handlerAssociations Array of command classes indexed by associated handler class
     *                                   Example: [
     *                                   'Core/Product/CommandHandler/AddProductHandler' => 'Core/Product/CommandHandler/AddProductCommand',
     *                                   'Core/Product/CommandHandler/EditProductHandler' => 'Core/Product/CommandHandler/EditProductCommand',
     *                                   ]
     *
     * @return array<int, CommandHandlerDefinition>
     */
    public function getDefinitions(array $handlerAssociations): array
    {
        $commandDefinitionsByDomain = $this->getDefinitionsByDomain($handlerAssociations);
        $commandDefinitions = [];
        foreach ($commandDefinitionsByDomain as $commandAndQueries) {
            $commandDefinitions = array_merge($commandDefinitions, $commandAndQueries[CommandHandlerDefinition::TYPE_QUERY]);
            $commandDefinitions = array_merge($commandDefinitions, $commandAndQueries[CommandHandlerDefinition::TYPE_COMMAND]);
        }

        return $commandDefinitions;
    }

    /**
     * @param array $handlerAssociations Array of command classes indexed by associated handler class
     *                                   Example: [
     *                                   'Core/Product/CommandHandler/AddProductHandler' => 'Core/Product/CommandHandler/AddProductCommand',
     *                                   'Core/Product/CommandHandler/EditProductHandler' => 'Core/Product/CommandHandler/EditProductCommand',
     *                                   ]
     *
     * @return array<string, array<string, array<int, CommandHandlerDefinition>>>
     */
    public function getDefinitionsByDomain(array $handlerAssociations): array
    {
        $commandDefinitionsByDomain = [];
        foreach ($handlerAssociations as $handlerClass => $commandClass) {
            $commandDefinition = $this->handlerDefinitionParser->parseDefinition($handlerClass, $commandClass);

            // Always preset command and query fields, this avoids bugs or additional checks in the twig template
            if (!isset($commandDefinitionsByDomain[$commandDefinition->getDomain()])) {
                $commandDefinitionsByDomain[$commandDefinition->getDomain()] = [
                    CommandHandlerDefinition::TYPE_COMMAND => [],
                    CommandHandlerDefinition::TYPE_QUERY => [],
                ];
            }

            $commandDefinitionsByDomain[$commandDefinition->getDomain()][$commandDefinition->getType()][] = $commandDefinition;
            sort($commandDefinitionsByDomain[$commandDefinition->getDomain()][$commandDefinition->getType()]);
        }
        ksort($commandDefinitionsByDomain);

        return $commandDefinitionsByDomain;
    }
}
