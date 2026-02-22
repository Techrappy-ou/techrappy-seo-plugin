<?php
/**
 * Étape du pipeline : Suggestion de maillage interne.
 *
 * @package TechrappySEO\AI\Pipeline\Steps
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline\Steps;

use TechrappySEO\AI\AIClient;
use TechrappySEO\AI\Pipeline\StepInterface;
use TechrappySEO\AI\PromptManager;
use TechrappySEO\AI\PromptRenderer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class StepInternalLinks
 *
 * Étape 8 : propose 3 à 5 liens internes pertinents à partir des pages
 * publiées du site WordPress. L'IA choisit parmi la liste fournie.
 */
class StepInternalLinks implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'internal_links' );
        if ( ! $prompt_data ) {
            $logger->error( 'internal_links', 'Prompt "internal_links" introuvable en base.' );
            return [];
        }

        // Construire la liste des pages publiées du site.
        $pages_json = $this->get_site_pages_json( $logger );

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'         => $job['keyword'] ?? '',
            'pages_site_json' => $pages_json,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'internal_links', 'Aucune réponse de l\'API OpenAI.' );
            return [];
        }

        // L'API retourne un tableau de liens (pas un objet).
        // On l'encapsule pour compatibilité avec le format steps[key]['data'].
        if ( isset( $result[0] ) ) {
            return [ 'links' => $result ];
        }

        return $result;
    }

    /**
     * Récupère les pages et articles publiés du site en JSON.
     *
     * @param \TechrappySEO\Utils\Logger $logger Logger.
     *
     * @return string JSON des pages disponibles.
     */
    private function get_site_pages_json( \TechrappySEO\Utils\Logger $logger ): string {
        $posts = get_posts( [
            'post_type'      => [ 'page', 'post' ],
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ] );

        if ( empty( $posts ) ) {
            $logger->warning( 'internal_links', 'Aucune page publiée trouvée pour le maillage interne.' );
            return '[]';
        }

        $pages = array_map( static function ( \WP_Post $post ): array {
            return [
                'title' => get_the_title( $post ),
                'url'   => get_permalink( $post ),
            ];
        }, $posts );

        return wp_json_encode( $pages ) ?: '[]';
    }

    public function get_name(): string {
        return 'internal_links';
    }
}
