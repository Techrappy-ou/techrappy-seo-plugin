<?php
/**
 * Estimation du coût des appels OpenAI.
 *
 * @package TechrappySEO\Utils
 */

declare( strict_types=1 );

namespace TechrappySEO\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CostEstimator
 */
class CostEstimator {

    /**
     * Tarifs en $ pour 1 000 tokens (input / output) par modèle.
     *
     * @var array<string, array{input: float, output: float}>
     */
    private static array $pricing = [
        // GPT-4o series
        'gpt-4o'             => [ 'input' => 0.005,    'output' => 0.015   ],
        'gpt-4o-mini'        => [ 'input' => 0.00015,  'output' => 0.0006  ],
        // GPT-4.1 series (2025)
        'gpt-4.1'            => [ 'input' => 0.002,    'output' => 0.008   ],
        'gpt-4.1-mini'       => [ 'input' => 0.0004,   'output' => 0.0016  ],
        'gpt-4.1-nano'       => [ 'input' => 0.0001,   'output' => 0.0004  ],
        // o-series (raisonnement)
        'o3'                 => [ 'input' => 0.01,     'output' => 0.04    ],
        'o3-mini'            => [ 'input' => 0.0011,   'output' => 0.0044  ],
        'o4-mini'            => [ 'input' => 0.0011,   'output' => 0.0044  ],
        'o1'                 => [ 'input' => 0.015,    'output' => 0.06    ],
        'o1-mini'            => [ 'input' => 0.003,    'output' => 0.012   ],
        // Héritage
        'gpt-4-turbo'        => [ 'input' => 0.01,     'output' => 0.03    ],
        'gpt-3.5-turbo'      => [ 'input' => 0.0005,   'output' => 0.0015  ],
    ];

    /**
     * Estime le coût d'un appel en dollars.
     *
     * @param string $model         Nom du modèle OpenAI.
     * @param int    $input_tokens  Nombre de tokens en entrée.
     * @param int    $output_tokens Nombre de tokens en sortie.
     *
     * @return float Coût estimé en dollars (arrondi à 6 décimales).
     */
    public static function estimate( string $model, int $input_tokens, int $output_tokens ): float {
        $rates = self::$pricing[ $model ] ?? self::$pricing['gpt-4o'];
        $cost  = ( $input_tokens / 1000 * $rates['input'] ) + ( $output_tokens / 1000 * $rates['output'] );
        return round( $cost, 6 );
    }

    /**
     * Vérifie si le coût dépasse le seuil d'alerte configuré.
     *
     * @param float $cost Coût estimé.
     *
     * @return bool True si le seuil est dépassé.
     */
    public static function exceeds_threshold( float $cost ): bool {
        $threshold = (float) \TechrappySEO\Settings\SettingsRepository::get( 'cost_alert_threshold', 1.00 );
        return $cost >= $threshold;
    }
}
