<?php
/**
 * Registers all actions and filters for the plugin.
 *
 * Maintains a list of all hooks registered with WordPress and executes them
 * when `run()` is called by the Plugin class.
 *
 * @package TechrappySEO
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO;

/**
 * Class Loader
 *
 * Collects WordPress actions and filters and registers them in bulk.
 * Keeps the Plugin class clean by separating hook registration concerns.
 */
class Loader {

	/**
	 * Array of actions to register.
	 *
	 * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
	 */
	private array $actions = array();

	/**
	 * Array of filters to register.
	 *
	 * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
	 */
	private array $filters = array();

	/**
	 * Add a new action to the collection.
	 *
	 * @param string $hook          The WordPress action hook name.
	 * @param object $component     The object instance that owns the callback.
	 * @param string $callback      The method name on $component.
	 * @param int    $priority      Hook priority. Default 10.
	 * @param int    $accepted_args Number of arguments the hook passes. Default 1.
	 *
	 * @return void
	 */
	public function add_action(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection.
	 *
	 * @param string $hook          The WordPress filter hook name.
	 * @param object $component     The object instance that owns the callback.
	 * @param string $callback      The method name on $component.
	 * @param int    $priority      Hook priority. Default 10.
	 * @param int    $accepted_args Number of arguments the hook passes. Default 1.
	 *
	 * @return void
	 */
	public function add_filter(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Internal helper: appends a hook entry to a collection array.
	 *
	 * @param array  $hooks         Existing hooks array.
	 * @param string $hook          Hook name.
	 * @param object $component     Owning object instance.
	 * @param string $callback      Method name.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Accepted argument count.
	 *
	 * @return array Updated hooks array.
	 */
	private function add(
		array $hooks,
		string $hook,
		object $component,
		string $callback,
		int $priority,
		int $accepted_args
	): array {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register all collected actions and filters with WordPress.
	 *
	 * Called once by Plugin::run() after all hooks have been declared.
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
