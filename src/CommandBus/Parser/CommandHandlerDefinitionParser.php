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

use PrestaShop\DocToolsBundle\Util\String\StringModifier;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class CommandHandlerDefinitionParser
{
    private const HANDLER_METHOD_NAME = 'handle';
    private const RETURN_TAG_REGEXP = '/@return\s+([^\s]+)\s/';
    private const PARAM_TAG_REGEXP = '/@param\s+([^\s]+)\s+\$%s\s/';
    private const USE_STATEMENTS_REGEXP = '/use\s+([^\s]+\\\\%s)\s*;/';
    private const IS_ARRAY_REGEXP = '/([^\s]+)\[\]$/';

    /**
     * @var DomainParserInterface
     */
    private $domainParser;

    /**
     * @var StringModifier
     */
    private $stringModifier;

    /**
     * @param DomainParserInterface $domainParser
     * @param StringModifier $stringModifier
     */
    public function __construct(
        DomainParserInterface $domainParser,
        StringModifier $stringModifier
    ) {
        $this->domainParser = $domainParser;
        $this->stringModifier = $stringModifier;
    }

    /**
     * @param string $handlerClass
     * @param string $commandClass
     *
     * @return CommandHandlerDefinition
     */
    public function parseDefinition(string $handlerClass, string $commandClass): CommandHandlerDefinition
    {
        $commandReflection = new ReflectionClass($commandClass);
        $handlerReflection = new ReflectionClass($handlerClass);
        $simpleClass = substr($commandClass, strrpos($commandClass, '\\') + 1);
        $slugName = $this->stringModifier->convertCamelCaseToKebabCase($simpleClass);

        return new CommandHandlerDefinition(
            $this->parseType($commandClass),
            $this->domainParser->parseDomain($commandClass),
            $handlerClass,
            $commandClass,
            $this->parseCommandConstructorParams($commandReflection),
            $this->parseDescription($commandReflection),
            $this->parseReturnType($handlerReflection),
            $handlerReflection->getInterfaceNames(),
            $simpleClass,
            $slugName
        );
    }

    /**
     * @param ReflectionClass $command
     *
     * @return string[]
     */
    private function parseCommandConstructorParams(ReflectionClass $command): array
    {
        if (!$constructor = $command->getConstructor()) {
            return [];
        }

        $params = [];
        foreach ($constructor->getParameters() as $parameter) {
            $param = sprintf('$%s', $parameter->getName());
            $type = $parameter->getType();
            if (!$type) {
                $type = $this->parseConstructorTypeFromDocblock($constructor, $parameter);
            } else {
                /* @phpstan-ignore-next-line ignoring getName() as method is not visible to phpstan */
                $type = $type->getName();
            }

            if ($type) {
                $param = sprintf('%s %s', $type, $param);
            }

            if ($parameter->isOptional()) {
                if ($parameter->allowsNull()) {
                    $param = sprintf('?%s', str_replace('|null', '', $param));
                }
                $param = sprintf('%s = %s', $param, var_export($parameter->getDefaultValue(), true));
            }
            $params[] = $param;
        }

        return $params;
    }

    /**
     * Parses return type from docblock
     *
     * @param ReflectionClass $handlerReflection
     *
     * @return string
     */
    private function parseReturnType(ReflectionClass $handlerReflection): ?string
    {
        $method = $handlerReflection->getMethod(self::HANDLER_METHOD_NAME);

        $interfaceReflection = null;
        foreach ($handlerReflection->getInterfaces() as $interface) {
            if ($interface->hasMethod(self::HANDLER_METHOD_NAME)) {
                $interfaceReflection = $interface;
                $method = $interface->getMethod(self::HANDLER_METHOD_NAME);
                break;
            }
        }

        if ($returnType = $this->parseReturnTypeFromDocblock($method)) {
            return $this->getFullyQualifiedClassName($returnType, $handlerReflection, $interfaceReflection);
        }

        if ($method->hasReturnType()) {
            /* @phpstan-ignore-next-line ignoring getName() as method is not visible to phpstan */
            return $method->getReturnType()->getName();
        }

        return 'void';
    }

    /**
     * @param string $returnType
     * @param ReflectionClass $handlerReflection
     *
     * @return string
     */
    private function getFullyQualifiedClassName(string $returnType, ReflectionClass $handlerReflection, ReflectionClass $interfaceReflection): string
    {
        $isArray = false;
        if (preg_match(self::IS_ARRAY_REGEXP, $returnType, $matches)) {
            $isArray = true;
            $returnType = count($matches) > 1 ? $matches[1] : $returnType;
        }

        if ($fullName = $this->searchFullNameFromUseStatements($handlerReflection, $returnType)) {
            return $fullName . ($isArray ? '[]' : '');
        } elseif ($fullName = $this->searchFullNameFromUseStatements($interfaceReflection, $returnType)) {
            return $fullName . ($isArray ? '[]' : '');
        }

        return $returnType . ($isArray ? '[]' : '');
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param string $returnType
     *
     * @return string|null
     */
    private function searchFullNameFromUseStatements(ReflectionClass $reflectionClass, string $returnType): ?string
    {
        $fileName = $reflectionClass->getFileName();
        $fileCode = file_get_contents($fileName);
        $regepx = preg_replace('/[\\\\]/', '\\\\\\\\\\\\\\\\', $returnType);
        $regexp = sprintf(self::USE_STATEMENTS_REGEXP, $regepx);
        preg_match($regexp, $fileCode, $matches);

        if (count($matches) >= 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return string|null
     */
    private function parseReturnTypeFromDocblock(ReflectionMethod $method): ?string
    {
        $docBlock = $method->getDocComment();
        if (!$docBlock) {
            return null;
        }

        preg_match(self::RETURN_TAG_REGEXP, $docBlock, $matches);
        if (count($matches) > 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return string|null
     */
    private function parseConstructorTypeFromDocblock(ReflectionMethod $method, ReflectionParameter $parameter): ?string
    {
        $docBlock = $method->getDocComment();
        if (!$docBlock) {
            return null;
        }

        $regexp = sprintf(self::PARAM_TAG_REGEXP, $parameter->getName());
        preg_match($regexp, $docBlock, $matches);
        if (count($matches) > 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param ReflectionClass $reflectionClass
     *
     * @return string
     */
    private function parseDescription(ReflectionClass $reflectionClass): string
    {
        if (!$docBlock = $reflectionClass->getDocComment()) {
            return '';
        }

        /**
         * Removes comment symbols, annotations, and line breaks.
         */
        $description = preg_replace("/\/+\*\*|\*+\/|\*|@(\w+)\b(.*)|\n/",
            '',
            $docBlock
        );

        /**
         * Replaces multiple spaces to single space
         */
        $description = preg_replace('/ +/', ' ', $description);

        /*
         * Strips whitespace from the beginning and end
         */
        return trim($description);
    }

    /**
     * Checks whether the command is of type Query or Command by provided name
     *
     * @param string $commandName
     *
     * @return string command|query
     */
    private function parseType(string $commandName): string
    {
        if (strpos($commandName, '\Command\\')) {
            return CommandHandlerDefinition::TYPE_COMMAND;
        }

        return CommandHandlerDefinition::TYPE_QUERY;
    }
}
