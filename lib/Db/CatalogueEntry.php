<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Db;

use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Entity;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\DB\Schema\ColumnType;

/**
 * One app from the App Store catalogue, reduced to the fields a card needs.
 *
 * The ORM creates entities without arguments and reads and writes their properties directly.
 *
 * @psalm-api
 * @psalm-suppress MissingConstructor
 */
#[Entity(name: 'appstore_cards_apps')]
final class CatalogueEntry {
	#[Id]
	#[Column(name: 'id', type: ColumnType::Bigint)]
	public ?int $id = null;

	#[Column(name: 'app_id', type: ColumnType::String, length: 64)]
	public string $appId;

	#[Column(name: 'name', type: ColumnType::String, length: 255)]
	public string $name;

	#[Column(name: 'summary', type: ColumnType::String, length: 512)]
	public string $summary;

	#[Column(name: 'latest_version', type: ColumnType::String, length: 64)]
	public string $latestVersion;

	/** Nextcloud versions the latest release supports, such as ">=33 <=35". */
	#[Column(name: 'platform_spec', type: ColumnType::String, length: 64)]
	public string $platformSpec;

	/** @var list<string> */
	#[Column(name: 'categories', type: ColumnType::Json)]
	public array $categories = [];

	/** Average rating from 0 to 1. */
	#[Column(name: 'rating', type: ColumnType::Float)]
	public float $rating;

	#[Column(name: 'rating_count', type: ColumnType::Integer)]
	public int $ratingCount;

	#[Column(name: 'screenshot_url', type: ColumnType::String, length: 1024, nullable: true)]
	public ?string $screenshotUrl = null;

	#[Column(name: 'last_modified', type: ColumnType::DatetimeImmutable)]
	public \DateTimeImmutable $lastModified;
}
