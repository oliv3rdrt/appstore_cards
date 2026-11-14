<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Settings;

use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Settings\AdminSettings;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\IAppConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUser;
use OCP\Settings\DeclarativeSettingsTypes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AdminSettingsTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IReferenceManager&MockObject $referenceManager;
	private AdminSettings $settings;

	protected function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$repository = $this->createMock(CatalogueRepository::class);
		$repository->method('count')->willReturn(552);
		$repository->method('yieldAll')->willReturnCallback(static function (): \Generator {
			foreach (['deck', 'forms'] as $appId) {
				$entry = new CatalogueEntry();
				$entry->appId = $appId;
				yield $entry;
			}
		});
		$this->referenceManager = $this->createMock(IReferenceManager::class);
		$formatter = $this->createMock(IDateTimeFormatter::class);
		$formatter->method('formatDateTime')->with(1_800_000_000)->willReturn('15 January 2027 at 08:00:00');
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));

		$this->settings = new AdminSettings($this->appConfig, $repository, $formatter, $this->referenceManager, $l10n);
	}

	public function testOffersAScreenshotSwitchInTheAdminSettings(): void {
		$schema = $this->settings->getSchema();

		// The settings page strips the app prefix from form IDs when saving, so the ID must not have one.
		$this->assertStringStartsNotWith('appstore_cards_', $schema['id']);
		$this->assertSame(DeclarativeSettingsTypes::SECTION_TYPE_ADMIN, $schema['section_type']);
		$this->assertSame(DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL, $schema['storage_type']);
		$this->assertSame('show_screenshots', $schema['fields'][0]['id']);
		$this->assertSame(DeclarativeSettingsTypes::CHECKBOX, $schema['fields'][0]['type']);
		$this->assertTrue($schema['fields'][0]['default']);
	}

	public function testDescribesTheCatalogueStatus(): void {
		$this->appConfig->method('getValueInt')->willReturn(1_800_000_000);

		$this->assertSame(
			'The catalogue has 552 apps and was last checked on 15 January 2027 at 08:00:00. It refreshes once a day.',
			$this->settings->getSchema()['description'],
		);
	}

	public function testSaysWhenTheCatalogueHasNotBeenDownloadedYet(): void {
		$this->appConfig->method('getValueInt')->willReturn(0);

		$this->assertStringStartsWith('The catalogue has not been downloaded yet.', $this->settings->getSchema()['description']);
	}

	public function testReadsTheSwitchAsABoolean(): void {
		$this->appConfig->method('getValueBool')->with('appstore_cards', 'show_screenshots', true)->willReturn(false);

		$this->assertFalse($this->settings->getValue('show_screenshots', $this->createMock(IUser::class)));
	}

	/**
	 * @return array<string, array{mixed, bool}>
	 */
	public static function submittedValues(): array {
		return [
			'true' => [true, true],
			'false' => [false, false],
			'string true' => ['true', true],
			'string false' => ['false', false],
			'one' => ['1', true],
			'zero' => ['0', false],
		];
	}

	#[DataProvider('submittedValues')]
	public function testStoresTheSwitchAsABoolean(mixed $submitted, bool $stored): void {
		$this->appConfig->method('getValueBool')->willReturn(!$stored);
		$this->appConfig->expects($this->once())->method('setValueBool')->with('appstore_cards', 'show_screenshots', $stored);

		$this->settings->setValue('show_screenshots', $submitted, $this->createMock(IUser::class));
	}

	public function testRefreshesCachedCardsWhenTheSwitchChanges(): void {
		$this->appConfig->method('getValueBool')->willReturn(true);
		$invalidated = [];
		$this->referenceManager->method('invalidateCache')->willReturnCallback(static function (string $prefix) use (&$invalidated): void {
			$invalidated[] = $prefix;
		});

		$this->settings->setValue('show_screenshots', false, $this->createMock(IUser::class));

		$this->assertSame(['deck', 'forms'], $invalidated);
	}

	public function testLeavesCachedCardsAloneWhenNothingChanges(): void {
		$this->appConfig->method('getValueBool')->willReturn(true);
		$this->appConfig->expects($this->never())->method('setValueBool');
		$this->referenceManager->expects($this->never())->method('invalidateCache');

		$this->settings->setValue('show_screenshots', true, $this->createMock(IUser::class));
	}

	public function testRejectsUnknownSettings(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->settings->setValue('catalogue_etag', 'forged', $this->createMock(IUser::class));
	}
}
