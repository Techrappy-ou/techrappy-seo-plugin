<?php
/**
 * Textual content analyzer for SEO scoring.
 *
 * @package TechrappySEO\Services\SEO
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\SEO;

/**
 * Class ContentAnalyzer
 *
 * Performs basic, dependency-free textual analysis on a post's content.
 * Results are consumed by SEOEngine to compute the final SEO score.
 *
 * All methods are pure functions: no DB access, no WordPress side-effects.
 * This makes them straightforward to unit-test in isolation.
 */
class ContentAnalyzer {

	/**
	 * Analyze a post's content and return a structured result array.
	 *
	 * @param string $content       Raw post content (may contain HTML).
	 * @param string $focus_keyword The target focus keyword.
	 * @param string $meta_title    The SEO meta title.
	 * @param string $meta_desc     The SEO meta description.
	 *
	 * @return array{
	 *     word_count:            int,
	 *     keyword_in_title:      bool,
	 *     keyword_in_desc:       bool,
	 *     keyword_density:       float,
	 *     has_meta_description:  bool,
	 *     has_meta_title:        bool,
	 *     title_length_ok:       bool,
	 *     desc_length_ok:        bool,
	 *     issues:                string[],
	 * }
	 */
	public function analyze(
		string $content,
		string $focus_keyword,
		string $meta_title,
		string $meta_desc
	): array {
		$plain_content   = wp_strip_all_tags( $content );
		$plain_content_l = mb_strtolower( $plain_content );
		$keyword_l       = mb_strtolower( trim( $focus_keyword ) );

		$word_count  = $this->count_words( $plain_content );
		$issues      = array();

		// Keyword checks.
		$keyword_in_title = $keyword_l && str_contains( mb_strtolower( $meta_title ), $keyword_l );
		$keyword_in_desc  = $keyword_l && str_contains( mb_strtolower( $meta_desc ), $keyword_l );
		$keyword_density  = $this->keyword_density( $plain_content_l, $keyword_l, $word_count );

		// Length checks.
		$title_length      = mb_strlen( trim( $meta_title ) );
		$desc_length       = mb_strlen( trim( $meta_desc ) );
		$title_length_ok   = $title_length >= 30 && $title_length <= 60;
		$desc_length_ok    = $desc_length >= 70 && $desc_length <= 160;
		$has_meta_title    = $title_length > 0;
		$has_meta_desc     = $desc_length > 0;

		// Build issues list.
		if ( $word_count < 300 ) {
			$issues[] = __( 'Le contenu est trop court (moins de 300 mots).', 'techrappy-seo' );
		}
		if ( $keyword_l && ! $keyword_in_title ) {
			$issues[] = __( 'Le mot-clé principal n\'apparaît pas dans le titre SEO.', 'techrappy-seo' );
		}
		if ( $keyword_l && ! $keyword_in_desc ) {
			$issues[] = __( 'Le mot-clé principal n\'apparaît pas dans la méta description.', 'techrappy-seo' );
		}
		if ( ! $has_meta_desc ) {
			$issues[] = __( 'Aucune méta description définie.', 'techrappy-seo' );
		}
		if ( $has_meta_title && ! $title_length_ok ) {
			$issues[] = __( 'Le titre SEO doit faire entre 30 et 60 caractères.', 'techrappy-seo' );
		}
		if ( $has_meta_desc && ! $desc_length_ok ) {
			$issues[] = __( 'La méta description doit faire entre 70 et 160 caractères.', 'techrappy-seo' );
		}
		if ( $keyword_l && $keyword_density > 3.0 ) {
			$issues[] = __( 'Densité de mot-clé trop élevée (keyword stuffing).', 'techrappy-seo' );
		}

		return array(
			'word_count'           => $word_count,
			'keyword_in_title'     => $keyword_in_title,
			'keyword_in_desc'      => $keyword_in_desc,
			'keyword_density'      => $keyword_density,
			'has_meta_description' => $has_meta_desc,
			'has_meta_title'       => $has_meta_title,
			'title_length_ok'      => $title_length_ok,
			'desc_length_ok'       => $desc_length_ok,
			'issues'               => $issues,
		);
	}

	/**
	 * Count the number of words in a plain-text string.
	 *
	 * @param string $text Plain text (no HTML).
	 *
	 * @return int
	 */
	public function count_words( string $text ): int {
		$text = trim( $text );
		if ( '' === $text ) {
			return 0;
		}
		return str_word_count( $text );
	}

	/**
	 * Calculate keyword density as a percentage.
	 *
	 * @param string $content_lower Lowercase plain-text content.
	 * @param string $keyword_lower Lowercase keyword.
	 * @param int    $word_count    Total word count of the content.
	 *
	 * @return float Percentage rounded to 2 decimal places, or 0.0 if not applicable.
	 */
	public function keyword_density( string $content_lower, string $keyword_lower, int $word_count ): float {
		if ( '' === $keyword_lower || 0 === $word_count ) {
			return 0.0;
		}

		$occurrences = substr_count( $content_lower, $keyword_lower );
		$kw_words    = str_word_count( $keyword_lower );

		return round( ( $occurrences * $kw_words / $word_count ) * 100, 2 );
	}
}
