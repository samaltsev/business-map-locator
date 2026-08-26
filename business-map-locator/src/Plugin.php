<?php
declare(strict_types=1);

namespace BusinessMapLocator;

use BusinessMapLocator\Application\Location\SearchLocationsHandler;
use BusinessMapLocator\Infrastructure\Cache\LocationCache;
use BusinessMapLocator\Infrastructure\Database\LocationRepository;
use BusinessMapLocator\Import\ImportCleanupScheduler;
use BusinessMapLocator\Legacy\LegacyClassLoader;
use BusinessMapLocator\Rest\LocationResponseFactory;
use BusinessMapLocator\Rest\LocationsController;
use BusinessMapLocator\Lifecycle\Activator;
use BusinessMapLocator\Lifecycle\Deactivator;
use BusinessMapLocator\Migration\AreaMigrationService;
use BusinessMapLocator\Migration\AreaRollbackService;
use BusinessMapLocator\Migration\MigrationSnapshotStore;
use BusinessMapLocator\Settings\Settings;
use BusinessMapLocator\WordPress\BlockRegistrar;
use BusinessMapLocator\WordPress\ContentTypes;
use BusinessMapLocator\WordPress\MetaRegistrar;
use BusinessMapLocator\WordPress\PrivacyPolicy;
use BusinessMapLocator\WordPress\TextDomain;

final class Plugin
{
    private static ?self $instance = null;

    private bool $booted = false;

    private Container $container;

    private function __construct()
    {
        self::loadLegacy();
        $this->container = new Container();
        $this->registerServices();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function activate(): void
    {
        $plugin = self::instance();
        $plugin->container->get(Activator::class)->run();
    }

    public static function deactivate(): void
    {
        $plugin = self::instance();
        $plugin->container->get(Deactivator::class)->run();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerCoreHooks();
        $this->container->get(\BusinessMapLocator\Admin\AdminModule::class)->hooks();
        $this->bootLegacyModules();

        $this->booted = true;
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function registerServices(): void
    {
        $this->container->set(Settings::class, static fn (): Settings => new Settings());
        $this->container->set(ContentTypes::class, static fn (): ContentTypes => new ContentTypes());
        $this->container->set(MetaRegistrar::class, static fn (): MetaRegistrar => new MetaRegistrar());
        $this->container->set(BlockRegistrar::class, static fn (): BlockRegistrar => new BlockRegistrar());
        $this->container->set(TextDomain::class, static fn (): TextDomain => new TextDomain());
        $this->container->set(PrivacyPolicy::class, static fn (): PrivacyPolicy => new PrivacyPolicy());
        $this->container->set(ImportCleanupScheduler::class, static fn (): ImportCleanupScheduler => new ImportCleanupScheduler());
        $this->container->set(MigrationSnapshotStore::class, static fn (): MigrationSnapshotStore => new MigrationSnapshotStore());
        $this->container->set(AreaMigrationService::class, static fn (Container $container): AreaMigrationService => new AreaMigrationService($container->get(MigrationSnapshotStore::class)));
        $this->container->set(AreaRollbackService::class, static fn (Container $container): AreaRollbackService => new AreaRollbackService($container->get(MigrationSnapshotStore::class)));

        $this->container->set(
            Activator::class,
            static fn (Container $container): Activator => new Activator(
                $container->get(Settings::class),
                $container->get(ContentTypes::class)
            )
        );
        $this->container->set(Deactivator::class, static fn (): Deactivator => new Deactivator());

        $this->container->set(LocationRepository::class, static fn (): LocationRepository => new LocationRepository());
        $this->container->set(LocationCache::class, static fn (): LocationCache => new LocationCache());
        $this->container->set(
            SearchLocationsHandler::class,
            static fn (Container $container): SearchLocationsHandler => new SearchLocationsHandler(
                $container->get(LocationRepository::class),
                $container->get(LocationCache::class)
            )
        );
        $this->container->set(LocationResponseFactory::class, static fn (): LocationResponseFactory => new LocationResponseFactory());
        $this->container->set(\BusinessMapLocator\Rest\LocationDetailResponseFactory::class, static fn (): \BusinessMapLocator\Rest\LocationDetailResponseFactory => new \BusinessMapLocator\Rest\LocationDetailResponseFactory());
        $this->container->set(
            LocationsController::class,
            static fn (Container $container): LocationsController => new LocationsController(
                $container->get(SearchLocationsHandler::class),
                $container->get(LocationResponseFactory::class),
                $container->get(\BusinessMapLocator\Rest\LocationDetailResponseFactory::class),
                $container->get(LocationRepository::class)
            )
        );

        $this->container->set(\BML_Provider_Registry::class, static fn (): \BML_Provider_Registry => new \BML_Provider_Registry());

        $this->container->set(\BML_Location_Index::class, static fn (): \BML_Location_Index => new \BML_Location_Index());
        $this->container->set(\BusinessMapLocator\Import\Job\ImportJobRepository::class, static fn (): \BusinessMapLocator\Import\Job\ImportJobRepository => new \BusinessMapLocator\Import\Job\ImportJobRepository());
        $this->container->set(\BusinessMapLocator\Admin\Shared\AdminShell::class, static fn (): \BusinessMapLocator\Admin\Shared\AdminShell => new \BusinessMapLocator\Admin\Shared\AdminShell());
        $this->container->set(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class, static fn (): \BusinessMapLocator\Admin\Shared\AdminActionResponder => new \BusinessMapLocator\Admin\Shared\AdminActionResponder());
        $this->container->set(\BusinessMapLocator\Admin\Request\AdminRequest::class, static fn (): \BusinessMapLocator\Admin\Request\AdminRequest => new \BusinessMapLocator\Admin\Request\AdminRequest());
        $this->container->set(\BusinessMapLocator\Domain\Location\LocationServiceCatalog::class, static fn (): \BusinessMapLocator\Domain\Location\LocationServiceCatalog => new \BusinessMapLocator\Domain\Location\LocationServiceCatalog());
        $this->container->set(\BusinessMapLocator\Admin\Location\View\LocationTableRenderer::class, static fn (): \BusinessMapLocator\Admin\Location\View\LocationTableRenderer => new \BusinessMapLocator\Admin\Location\View\LocationTableRenderer());
        $this->container->set(\BusinessMapLocator\Admin\Location\LocationTableDataProvider::class, static fn (): \BusinessMapLocator\Admin\Location\LocationTableDataProvider => new \BusinessMapLocator\Admin\Location\LocationTableDataProvider());
        $this->container->set(\BusinessMapLocator\Admin\Taxonomy\View\TermSelectRenderer::class, static fn (): \BusinessMapLocator\Admin\Taxonomy\View\TermSelectRenderer => new \BusinessMapLocator\Admin\Taxonomy\View\TermSelectRenderer());
        $this->container->set(\BusinessMapLocator\Admin\Taxonomy\TaxonomyOptionsProvider::class, static fn (): \BusinessMapLocator\Admin\Taxonomy\TaxonomyOptionsProvider => new \BusinessMapLocator\Admin\Taxonomy\TaxonomyOptionsProvider());
        $this->container->set(\BusinessMapLocator\Admin\Dashboard\DashboardPage::class, static fn (Container $c): \BusinessMapLocator\Admin\Dashboard\DashboardPage => new \BusinessMapLocator\Admin\Dashboard\DashboardPage($c->get(\BusinessMapLocator\Admin\Shared\AdminShell::class), $c->get(\BusinessMapLocator\Admin\Location\View\LocationTableRenderer::class), $c->get(\BusinessMapLocator\Admin\Location\LocationTableDataProvider::class)));
        $this->container->set(\BusinessMapLocator\Admin\Location\LocationsPage::class, static fn (Container $c): \BusinessMapLocator\Admin\Location\LocationsPage => new \BusinessMapLocator\Admin\Location\LocationsPage($c->get(\BusinessMapLocator\Admin\Shared\AdminShell::class), $c->get(\BusinessMapLocator\Admin\Location\View\LocationTableRenderer::class), $c->get(\BusinessMapLocator\Admin\Location\LocationTableDataProvider::class), $c->get(\BusinessMapLocator\Admin\Taxonomy\View\TermSelectRenderer::class), $c->get(\BusinessMapLocator\Admin\Taxonomy\TaxonomyOptionsProvider::class)));
        $this->container->set(\BusinessMapLocator\Admin\Location\LocationCompleteness::class, static fn (): \BusinessMapLocator\Admin\Location\LocationCompleteness => new \BusinessMapLocator\Admin\Location\LocationCompleteness());
        $this->container->set(\BusinessMapLocator\Admin\Location\LocationEditorPage::class, static fn (Container $c): \BusinessMapLocator\Admin\Location\LocationEditorPage => new \BusinessMapLocator\Admin\Location\LocationEditorPage($c->get(\BusinessMapLocator\Domain\Location\LocationServiceCatalog::class), $c->get(\BusinessMapLocator\Admin\Location\LocationCompleteness::class)));
        $this->container->set(\BusinessMapLocator\Admin\Ajax\LocationEditorAjaxController::class, static fn (): \BusinessMapLocator\Admin\Ajax\LocationEditorAjaxController => new \BusinessMapLocator\Admin\Ajax\LocationEditorAjaxController());
        $this->container->set(\BusinessMapLocator\Admin\Taxonomy\TaxonomyPage::class, static fn (Container $c): \BusinessMapLocator\Admin\Taxonomy\TaxonomyPage => new \BusinessMapLocator\Admin\Taxonomy\TaxonomyPage($c->get(\BusinessMapLocator\Admin\Shared\AdminShell::class)));
        $this->container->set(\BusinessMapLocator\Admin\Import\ImportPage::class, static fn (Container $c): \BusinessMapLocator\Admin\Import\ImportPage => new \BusinessMapLocator\Admin\Import\ImportPage($c->get(\BusinessMapLocator\Admin\Shared\AdminShell::class), $c->get(\BusinessMapLocator\Import\Job\ImportJobRepository::class)));
        $this->container->set(\BusinessMapLocator\Admin\Settings\View\SettingsRenderer::class, static fn (): \BusinessMapLocator\Admin\Settings\View\SettingsRenderer => new \BusinessMapLocator\Admin\Settings\View\SettingsRenderer());
        $this->container->set(\BusinessMapLocator\Admin\Settings\SettingsPage::class, static fn (Container $c): \BusinessMapLocator\Admin\Settings\SettingsPage => new \BusinessMapLocator\Admin\Settings\SettingsPage($c->get(\BusinessMapLocator\Admin\Shared\AdminShell::class), $c->get(\BusinessMapLocator\Admin\Settings\View\SettingsRenderer::class)));
        $this->container->set(\BusinessMapLocator\Admin\Menu\AdminTitleRegistry::class, static fn (): \BusinessMapLocator\Admin\Menu\AdminTitleRegistry => new \BusinessMapLocator\Admin\Menu\AdminTitleRegistry());
        $this->container->set(\BusinessMapLocator\Admin\Menu\AdminMenu::class, static fn (Container $c): \BusinessMapLocator\Admin\Menu\AdminMenu => new \BusinessMapLocator\Admin\Menu\AdminMenu(
            $c->get(\BusinessMapLocator\Admin\Menu\AdminTitleRegistry::class), $c->get(\BusinessMapLocator\Admin\Dashboard\DashboardPage::class), $c->get(\BusinessMapLocator\Admin\Location\LocationsPage::class), $c->get(\BusinessMapLocator\Admin\Location\LocationEditorPage::class), $c->get(\BusinessMapLocator\Admin\Taxonomy\TaxonomyPage::class), $c->get(\BusinessMapLocator\Admin\Import\ImportPage::class), $c->get(\BusinessMapLocator\Admin\Settings\SettingsPage::class)
        ));
        $this->container->set(\BusinessMapLocator\Admin\Assets\AdminAssets::class, static fn (): \BusinessMapLocator\Admin\Assets\AdminAssets => new \BusinessMapLocator\Admin\Assets\AdminAssets());
        $this->container->set(\BusinessMapLocator\Import\ImportManager::class, static fn (): \BusinessMapLocator\Import\ImportManager => new \BusinessMapLocator\Import\ImportManager());
        $this->container->set(\BusinessMapLocator\Admin\Ajax\ImportAjaxController::class, static fn (Container $c): \BusinessMapLocator\Admin\Ajax\ImportAjaxController => new \BusinessMapLocator\Admin\Ajax\ImportAjaxController($c->get(\BusinessMapLocator\Import\ImportManager::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Location\Action\SaveLocationAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Location\Action\SaveLocationAction => new \BusinessMapLocator\Admin\Location\Action\SaveLocationAction(
            $c->get(\BusinessMapLocator\Domain\Location\LocationServiceCatalog::class),
            $c->get(\BML_Location_Index::class),
            $c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class),
            $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)
        ));
        $this->container->set(\BusinessMapLocator\Admin\Location\Action\DeleteLocationAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Location\Action\DeleteLocationAction => new \BusinessMapLocator\Admin\Location\Action\DeleteLocationAction($c->get(\BML_Location_Index::class), $c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Location\Action\DuplicateLocationAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Location\Action\DuplicateLocationAction => new \BusinessMapLocator\Admin\Location\Action\DuplicateLocationAction($c->get(\BML_Location_Index::class), $c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Location\Action\BulkLocationsAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Location\Action\BulkLocationsAction => new \BusinessMapLocator\Admin\Location\Action\BulkLocationsAction($c->get(\BML_Location_Index::class), $c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Taxonomy\Action\SaveTermAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Taxonomy\Action\SaveTermAction => new \BusinessMapLocator\Admin\Taxonomy\Action\SaveTermAction($c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Taxonomy\Action\DeleteTermAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Taxonomy\Action\DeleteTermAction => new \BusinessMapLocator\Admin\Taxonomy\Action\DeleteTermAction($c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Taxonomy\Action\DuplicateTermAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Taxonomy\Action\DuplicateTermAction => new \BusinessMapLocator\Admin\Taxonomy\Action\DuplicateTermAction($c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Settings\Action\SaveSettingsAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Settings\Action\SaveSettingsAction => new \BusinessMapLocator\Admin\Settings\Action\SaveSettingsAction($c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Demo\TermAssigner::class, static fn (): \BusinessMapLocator\Admin\Demo\TermAssigner => new \BusinessMapLocator\Admin\Demo\TermAssigner());
        $this->container->set(\BusinessMapLocator\Admin\Demo\InstallDemoAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Demo\InstallDemoAction => new \BusinessMapLocator\Admin\Demo\InstallDemoAction($c->get(\BusinessMapLocator\Admin\Demo\TermAssigner::class), $c->get(\BusinessMapLocator\Admin\Shared\AdminActionResponder::class)));
        $this->container->set(\BusinessMapLocator\Export\LocationCsvExporter::class, static fn (): \BusinessMapLocator\Export\LocationCsvExporter => new \BusinessMapLocator\Export\LocationCsvExporter());
        $this->container->set(\BusinessMapLocator\Admin\Export\ExportCsvAction::class, static fn (Container $c): \BusinessMapLocator\Admin\Export\ExportCsvAction => new \BusinessMapLocator\Admin\Export\ExportCsvAction($c->get(\BusinessMapLocator\Export\LocationCsvExporter::class), $c->get(\BusinessMapLocator\Admin\Request\AdminRequest::class)));
        $this->container->set(\BusinessMapLocator\Admin\Notice\AdminNotices::class, static fn (): \BusinessMapLocator\Admin\Notice\AdminNotices => new \BusinessMapLocator\Admin\Notice\AdminNotices());
        $this->container->set(\BusinessMapLocator\Admin\AdminModule::class, static fn (Container $c): \BusinessMapLocator\Admin\AdminModule => new \BusinessMapLocator\Admin\AdminModule(
            $c->get(\BusinessMapLocator\Admin\Menu\AdminMenu::class), $c->get(\BusinessMapLocator\Admin\Assets\AdminAssets::class), $c->get(\BusinessMapLocator\Admin\Location\Action\SaveLocationAction::class), $c->get(\BusinessMapLocator\Admin\Location\Action\DeleteLocationAction::class), $c->get(\BusinessMapLocator\Admin\Location\Action\DuplicateLocationAction::class), $c->get(\BusinessMapLocator\Admin\Location\Action\BulkLocationsAction::class), $c->get(\BusinessMapLocator\Admin\Taxonomy\Action\SaveTermAction::class), $c->get(\BusinessMapLocator\Admin\Taxonomy\Action\DeleteTermAction::class), $c->get(\BusinessMapLocator\Admin\Taxonomy\Action\DuplicateTermAction::class), $c->get(\BusinessMapLocator\Admin\Settings\Action\SaveSettingsAction::class), $c->get(\BusinessMapLocator\Admin\Demo\InstallDemoAction::class), $c->get(\BusinessMapLocator\Admin\Export\ExportCsvAction::class), $c->get(\BusinessMapLocator\Admin\Ajax\ImportAjaxController::class), $c->get(\BusinessMapLocator\Admin\Ajax\LocationEditorAjaxController::class), $c->get(\BusinessMapLocator\Admin\Notice\AdminNotices::class)
        ));

        $this->container->set(
            \BML_REST::class,
            static fn (Container $container): \BML_REST => new \BML_REST(
                $container->get(LocationsController::class)
            )
        );
        $this->container->set(\BML_Cache_Invalidator::class, static fn (): \BML_Cache_Invalidator => new \BML_Cache_Invalidator());
        $this->container->set(\BML_Migrator::class, static fn (): \BML_Migrator => new \BML_Migrator());
        $this->container->set(\BML_Location_Indexer::class, static fn (): \BML_Location_Indexer => new \BML_Location_Indexer());
        $this->container->set(
            \BML_Frontend::class,
            static fn (Container $container): \BML_Frontend => new \BML_Frontend(
                $container->get(\BML_Provider_Registry::class)
            )
        );
        $this->container->set(\BML_Shortcode::class, static fn (): \BML_Shortcode => new \BML_Shortcode());
    }

    private function registerCoreHooks(): void
    {
        $contentTypes = $this->container->get(ContentTypes::class);
        $meta = $this->container->get(MetaRegistrar::class);
        $blocks = $this->container->get(BlockRegistrar::class);
        $textDomain = $this->container->get(TextDomain::class);
        $privacyPolicy = $this->container->get(PrivacyPolicy::class);
        $importCleanup = $this->container->get(ImportCleanupScheduler::class);

        add_action('init', [$contentTypes, 'register']);
        add_action('init', [$meta, 'register']);
        add_action('init', [$blocks, 'register']);
        add_action('init', [$textDomain, 'load']);
        add_action('admin_init', [\BML_Capabilities::class, 'maybeInstall'], 1);
        add_action('admin_init', [$privacyPolicy, 'register']);
        $importCleanup->register();
        add_action('after_setup_theme', static function (): void {
            add_image_size('bml_category_icon', 64, 64, true);
        });
    }

    private function bootLegacyModules(): void
    {
        foreach ([
            \BML_REST::class,
            \BML_Cache_Invalidator::class,
            \BML_Migrator::class,
            \BML_Location_Indexer::class,
            \BML_Frontend::class,
            \BML_Shortcode::class,
        ] as $serviceId) {
            $module = $this->container->get($serviceId);

            if (method_exists($module, 'hooks')) {
                $module->hooks();
            }
        }
    }

    private static function loadLegacy(): void
    {
        LegacyClassLoader::register(BML_DIR);
    }
}
