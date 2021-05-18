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

namespace Tests\Resources\Domain\Supplier\Command;

/**
 * Adds given supplier with provided data (uses strong typing)
 */
class AddSupplierCommand
{
    private $name;
    private $address;
    private $city;
    private $countryId;
    private $enabled;
    private $localizedDescriptions;
    private $localizedMetaTitles;
    private $localizedMetaDescriptions;
    private $localizedMetaKeywords;
    private $shopAssociation;
    private $address2;
    private $postCode;
    private $stateId;
    private $phone;
    private $mobilePhone;
    private $dni;
    private $zipCode;

    public function __construct(
        string $name,
        string $address,
        string $city,
        int $countryId,
        bool $enabled,
        array $localizedDescriptions,
        array $localizedMetaTitles,
        array $localizedMetaDescriptions,
        array $localizedMetaKeywords,
        array $shopAssociation,
        ?string $address2 = null,
        ?string $postCode = null,
        ?int $stateId = null,
        ?string $phone = null,
        string $mobilePhone = '',
        ?string $dni = null,
        ?int $zipCode = 0
    ) {
        $this->name = $name;
        $this->address = $address;
        $this->city = $city;
        $this->countryId = $countryId;
        $this->enabled = $enabled;
        $this->localizedDescriptions = $localizedDescriptions;
        $this->localizedMetaTitles = $localizedMetaTitles;
        $this->localizedMetaDescriptions = $localizedMetaDescriptions;
        $this->localizedMetaKeywords = $localizedMetaKeywords;
        $this->shopAssociation = $shopAssociation;
        $this->address2 = $address2;
        $this->postCode = $postCode;
        $this->stateId = $stateId;
        $this->phone = $phone;
        $this->mobilePhone = $mobilePhone;
        $this->dni = $dni;
        $this->zipCode = $zipCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function getStateId(): ?int
    {
        return $this->stateId;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function getLocalizedDescriptions(): array
    {
        return $this->localizedDescriptions;
    }

    public function getLocalizedMetaTitles(): array
    {
        return $this->localizedMetaTitles;
    }

    public function getLocalizedMetaDescriptions(): array
    {
        return $this->localizedMetaDescriptions;
    }

    public function getLocalizedMetaKeywords(): array
    {
        return $this->localizedMetaKeywords;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getShopAssociation(): array
    {
        return $this->shopAssociation;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function getZipCode(): ?int
    {
        return $this->zipCode;
    }
}