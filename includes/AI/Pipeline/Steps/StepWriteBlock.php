<?php
/**
 * Étape du pipeline : Rédaction d'un bloc de contenu H2.
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
 * Class StepWriteBlock
 *
 * Étape 4 : rédige un bloc de contenu (180-260 mots) pour un H2 donné.
 * Appelée en boucle par PipelineRunner, une fois par bloc de blocks_list.
 * Le bloc courant est passé via $job['_current_block'].
 */
class StepWriteBlock implements StepInterface {

    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        $manager  = new PromptManager();
        $renderer = new PromptRenderer();
        $client   = new AIClient();
        $client->set_logger( $logger );

        $prompt_data = $manager->get( 'block_write' );
        if ( ! $prompt_data ) {
            $logger->error( 'block_write', 'Prompt "block_write" introuvable en base.' );
            return [];
        }

        // Le bloc courant est injecté par PipelineRunner::execute_block_write_loop().
        $bloc      = $job['_current_block'] ?? [];
        $bloc_json = ! empty( $bloc ) ? wp_json_encode( $bloc ) : '{}';

        $prompt = $renderer->render( $prompt_data['content'], [
            'mot_cle'    => $job['keyword'] ?? '',
            'profession' => $job['profession'] ?? '',
            'bloc_json'  => $bloc_json,
        ] );

        $result = $client->complete_json( $prompt, $job['_system_prompt'] ?? '' );

        if ( ! $result ) {
            $logger->error( 'block_write', 'Aucune réponse de l\'API OpenAI pour le bloc : ' . ( $bloc['H2'] ?? '?' ) );
            return [];
        }

        return $result;
    }

    public function get_name(): string {
        return 'block_write';
    }
}
