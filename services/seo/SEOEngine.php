<?php
/**
 * SEO analysis orchestrator.
 *
 * @package TechrappySEO\Services\SEO
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\SEO;

use TechrappySEO\Services\AI\AIClient;

/**
 * Class SEOEngine
 *
 * Top-level entry point for analysing a WordPress post.
 *
 * Responsibilities:
 * 1. Load the post and its stored SEO meta.
 * 2. Delegate content analysis to ContentAnalyzer.
 * 3. Request AI-generated suggestions via AIClient (if available).
 * 4. Compute a numeric SEO score.
 * 5. Persist the results as post meta.
 */
class SEOEngine {

	/**
	 * @var AIClient
	 */
	private AIClient $ai_client;

	/**
	 * @var ContentAnalyzer
	 */
	private ContentAnalyzer $analyzer;

	/**
	 * @param AIClient $ai_client AI client facade.
	 */
	public function __construct( AIClient $ai_client ) {
		$this->ai_client = $ai_client;
		$this->analyzer  = new ContentAnalyzer();
	}

	/**
	 * Run a full SEO analysis for a given post and save the results.
	 *
	 * @param int $post_id WordPress post ID to analyse.
	 *
	 * @return array{score: int, issues: string[], suggestions: string[], analyzed_at: string}
	 *              Analysis result, also persisted as post meta.
	 */
	public function analyze( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return $this->empty_result();
		}

		// Gather inputs.
		$content       = $post->post_content;
		$focus_keyword = (string) get_post_meta( $post_id, '_techrappy_seo_focus_keyword', true );
		$meta_title    = (string) get_post_meta( $post_id, '_techrappy_seo_meta_title', true );
		$meta_desc     = (string) get_post_meta( $post_id, '_techrappy_seo_meta_description', true );

		// Allow integrations (Divi, etc.) to supply the analyzed content.
		$content = apply_filters( 'techrappy_seo_analyze_content', $content, $post_id );

		// Run textual analysis.
		$analysis = $this->analyzer->analyze( $content, $focus_keyword, $meta_title, $meta_desc );

		// Compute score.
		$score = $this->compute_score( $analysis );

		// Get AI suggestions if client is available.
		$suggestions = $this->fetch_ai_suggestions( $content, $focus_keyword, $analysis['issues'] );

		$analyzed_at = current_time( 'mysql' );

		// Persist results as post meta.
		update_post_meta( $post_id, '_techrappy_seo_score', $score );
		update_post_meta( $post_id, '_techrappy_seo_suggestions', $suggestions );
		update_post_meta( $post_id, '_techrappy_seo_analyzed_at', $analyzed_at );
		update_post_meta( $post_id, '_techrappy_seo_ai_used', $this->ai_client->is_available() );

		$result = array(
			'score'       => $score,
			'issues'      => $analysis['issues'],
			'suggestions' => $suggestions,
			'analyzed_at' => $analyzed_at,
		);

		do_action( 'techrappy_seo_after_analyze', $post_id, $result );

		return $result;
	}

	/**
	 * Compute a SEO score (0–100) from the analysis data.
	 *
	 * Scoring grid (V1, subject to change):
	 *   + 25 pts  word count ≥ 300
	 *   + 20 pts  keyword in title
	 *   + 20 pts  keyword in meta description
	 *   + 15 pts  meta description present and correct length
	 *   + 10 pts  meta title present and correct length
	 *   + 10 pts  keyword density between 0.5% and 3.0%
	 *
	 * @param array<string, mixed> $analysis Output of ContentAnalyzer::analyze().
	 *
	 * @return int Score between 0 and 100.
	 */
	private function compute_score( array $analysis ): int {
		$score = 0;

		if ( $analysis['word_count'] >= 300 ) {
			$score += 25;
		}

		if ( $analysis['keyword_in_title'] ) {
			$score += 20;
		}

		if ( $analysis['keyword_in_desc'] ) {
			$score += 20;
		}

		if ( $analysis['has_meta_description'] && $analysis['desc_length_ok'] ) {
			$score += 15;
		} elseif ( $analysis['has_meta_description'] ) {
			$score += 7;
		}

		if ( $analysis['has_meta_title'] && $analysis['title_length_ok'] ) {
			$score += 10;
		} elseif ( $analysis['has_meta_title'] ) {
			$score += 5;
		}

		$density = $analysis['keyword_density'];
		if ( $density >= 0.5 && $density <= 3.0 ) {
			$score += 10;
		}

		return min( 100, max( 0, $score ) );
	}

	/**
	 * Request AI-generated improvement suggestions for a post.
	 *
	 * Returns an empty array if the AI client is not configured.
	 *
	 * @param string   $content       Plain-text post content.
	 * @param string   $focus_keyword Focus keyword.
	 * @param string[] $issues        List of identified SEO issues.
	 *
	 * @return string[] Up to 5 suggestion strings.
	 */
	private function fetch_ai_suggestions( string $content, string $focus_keyword, array $issues ): array {
		if ( ! $this->ai_client->is_available() ) {
			return array();
		}

		$excerpt = mb_substr( wp_strip_all_tags( $content ), 0, 500 );
		$issues_text = implode( "\n- ", $issues );

		$prompt = sprintf(
			/* translators: variables are injected into an AI prompt — do not translate */
			"Tu es un expert SEO. Voici un extrait d'article WordPress :\n\n\"%s\"\n\nMot-clé principal : %s\n\nProblèmes SEO détectés :\n- %s\n\nDonne 3 à 5 suggestions courtes et actionnables pour améliorer le SEO de cet article. Réponds uniquement avec une liste à puces.",
			$excerpt,
			$focus_keyword ?: '(non défini)',
			$issues_text ?: '(aucun problème détecté)'
		);

		$raw = $this->ai_client->generate( $prompt );

		if ( '' === $raw ) {
			return array();
		}

		// Parse bullet-point list from the AI response.
		$lines = array_filter(
			array_map( 'trim', explode( "\n", $raw ) ),
			fn( $line ) => '' !== $line
		);

		$suggestions = array();
		foreach ( $lines as $line ) {
			$clean = ltrim( $line, '-•*0123456789.) ' );
			if ( $clean ) {
				$suggestions[] = $clean;
			}
		}

		return array_slice( $suggestions, 0, 5 );
	}

	/**
	 * Return an empty analysis result structure.
	 *
	 * @return array{score: int, issues: string[], suggestions: string[], analyzed_at: string}
	 */
	private function empty_result(): array {
		return array(
			'score'       => 0,
			'issues'      => array(),
			'suggestions' => array(),
			'analyzed_at' => '',
		);
	}
}
