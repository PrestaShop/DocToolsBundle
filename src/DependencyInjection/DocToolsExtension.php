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

namespace PrestaShop\DocToolsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DocToolsExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = $this->processConfiguration(new Configuration(), $configs);

        if ($container->hasParameter('doc_tools_doc_path')) {
            $container->setParameter('doc_tools.docs_path', $container->getParameter('doc_tools_doc_path'));
        } else {
            $container->setParameter('doc_tools.docs_path', $configuration['docs_path']);
        }

        if ($container->hasParameter('doc_tools_cqrs_folder')) {
            $container->setParameter('doc_tools.cqrs_folder', $container->getParameter('doc_tools_cqrs_folder'));
        } else {
            $container->setParameter('doc_tools.cqrs_folder', $configuration['cqrs_folder']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $container->setParameter('doc_tools.root_dir', __DIR__ . '/..');
        $container->prependExtensionConfig('twig', [
            'paths' => [
                '%doc_tools.root_dir%/Resources/views' => 'PrestaShopDocTools',
            ],
        ]);
    }
}
