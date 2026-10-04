<?php
/**
 * Gestionnaire centralisé des hooks WordPress (actions et filtres).
 *
 * Responsabilité : collecter toutes les déclarations de hooks
 * puis les enregistrer en une seule passe via run().
 * Découple les classes métier du système de hooks WordPress.
 *
 * @package TechrappySEO\Core
 */

declare( strict_types=1 );

namespace TechrappySEO\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Loader
 *
 * Registre centralisé des actions et filtres WordPress.
 */
class Loader {

    /**
     * Liste des actions enregistrées.
     *
     * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
     */
    private array $actions = [];

    /**
     * Liste des filtres enregistrés.
     *
     * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
     */
    private array $filters = [];

    /**
     * Ajoute une action à la liste des hooks à enregistrer.
     *
     * @param string $hook          Nom du hook WordPress.
     * @param object $component     Instance de l'objet portant le callback.
     * @param string $callback      Nom de la méthode à appeler.
     * @param int    $priority      Priorité d'exécution (défaut : 10).
     * @param int    $accepted_args Nombre d'arguments acceptés (défaut : 1).
     *
     * @return void
     */
    public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $this->actions[] = $this->build_hook( $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Ajoute un filtre à la liste des hooks à enregistrer.
     *
     * @param string $hook          Nom du hook WordPress.
     * @param object $component     Instance de l'objet portant le callback.
     * @param string $callback      Nom de la méthode à appeler.
     * @param int    $priority      Priorité d'exécution (défaut : 10).
     * @param int    $accepted_args Nombre d'arguments acceptés (défaut : 1).
     *
     * @return void
     */
    public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $this->filters[] = $this->build_hook( $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Enregistre tous les hooks collectés auprès de WordPress.
     * Doit être appelé une seule fois, après avoir déclaré tous les hooks.
     *
     * @return void
     */
    public function run(): void {
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], [ $hook['component'], $hook['callback'] ], $hook['priority'], $hook['accepted_args'] );
        }
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], [ $hook['component'], $hook['callback'] ], $hook['priority'], $hook['accepted_args'] );
        }
    }

    /**
     * Construit le tableau de données d'un hook.
     *
     * @param string $hook          Nom du hook.
     * @param object $component     Composant portant le callback.
     * @param string $callback      Méthode callback.
     * @param int    $priority      Priorité.
     * @param int    $accepted_args Arguments acceptés.
     *
     * @return array{hook: string, component: object, callback: string, priority: int, accepted_args: int}
     */
    private function build_hook( string $hook, object $component, string $callback, int $priority, int $accepted_args ): array {
        return [
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        ];
    }

    /**
     * Retourne le nombre total de hooks enregistrés.
     *
     * @return int
     */
    public function count(): int {
        return count( $this->actions ) + count( $this->filters );
    }
}
