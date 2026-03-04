<?php
/**
 * Logique exécutée lors de l'activation du plugin.
 *
 * Responsabilité : créer les tables custom, initialiser les options
 * par défaut et seeder les prompts initiaux.
 *
 * @package TechrappySEO\Core
 */

declare( strict_types=1 );

namespace TechrappySEO\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Activator
 *
 * Exécuté une seule fois lors de l'activation du plugin via
 * register_activation_hook().
 */
class Activator {

    /**
     * Point d'entrée de l'activation.
     *
     * @return void
     */
    public static function activate(): void {
        // 1. Créer / mettre à jour les tables custom.
        Installer::create_tables();

        // 2. Initialiser les options par défaut du plugin.
        \TechrappySEO\Settings\SettingsRepository::init_defaults();

        // 3. Seeder / synchroniser les prompts par défaut en base.
        \TechrappySEO\Prompts\DefaultPrompts::sync();

        // 4. Stocker la version installée pour gestion des migrations futures.
        update_option( 'techrappy_seo_version', TECHRAPPY_SEO_VERSION, false );

        // 5. Forcer le rechargement des règles de réécriture WordPress.
        flush_rewrite_rules();
    }
}
