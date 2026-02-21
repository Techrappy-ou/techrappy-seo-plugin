<?php
/**
 * Meta box template — displayed on the post/page edit screen.
 *
 * Variables available in this template:
 *   $post  \WP_Post  Current post object.
 *
 * @package TechrappySEO\Admin
 */

defined( 'ABSPATH' ) || exit;

// Retrieve stored meta values.
$focus_keyword    = get_post_meta( $post->ID, '_techrappy_seo_focus_keyword', true );
$meta_title       = get_post_meta( $post->ID, '_techrappy_seo_meta_title', true );
$meta_description = get_post_meta( $post->ID, '_techrappy_seo_meta_description', true );
$seo_score        = get_post_meta( $post->ID, '_techrappy_seo_score', true );
$analyzed_at      = get_post_meta( $post->ID, '_techrappy_seo_analyzed_at', true );
?>

<div class="techrappy-seo-meta-box" id="techrappy-seo-meta-box">

	<?php if ( '' !== $seo_score ) : ?>
	<div class="techrappy-seo-score-wrap">
		<strong><?php esc_html_e( 'Score SEO :', 'techrappy-seo' ); ?></strong>
		<span class="techrappy-seo-score"><?php echo absint( $seo_score ); ?> / 100</span>
		<?php if ( $analyzed_at ) : ?>
		<span class="techrappy-seo-analyzed-at">
			<?php
			printf(
				/* translators: %s: date/time of last analysis */
				esc_html__( '(analysé le %s)', 'techrappy-seo' ),
				esc_html( $analyzed_at )
			);
			?>
		</span>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<table class="form-table techrappy-seo-fields">
		<tr>
			<th scope="row">
				<label for="techrappy_seo_focus_keyword">
					<?php esc_html_e( 'Mot-clé principal', 'techrappy-seo' ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					id="techrappy_seo_focus_keyword"
					name="techrappy_seo_focus_keyword"
					value="<?php echo esc_attr( $focus_keyword ); ?>"
					class="large-text"
					placeholder="<?php esc_attr_e( 'Entrez votre mot-clé principal', 'techrappy-seo' ); ?>"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="techrappy_seo_meta_title">
					<?php esc_html_e( 'Titre SEO', 'techrappy-seo' ); ?>
				</label>
			</th>
			<td>
				<input
					type="text"
					id="techrappy_seo_meta_title"
					name="techrappy_seo_meta_title"
					value="<?php echo esc_attr( $meta_title ); ?>"
					class="large-text"
					maxlength="60"
					placeholder="<?php esc_attr_e( 'Titre personnalisé (max. 60 caractères)', 'techrappy-seo' ); ?>"
				/>
				<p class="description">
					<?php esc_html_e( 'Laissez vide pour utiliser le titre de l\'article.', 'techrappy-seo' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="techrappy_seo_meta_description">
					<?php esc_html_e( 'Méta description', 'techrappy-seo' ); ?>
				</label>
			</th>
			<td>
				<textarea
					id="techrappy_seo_meta_description"
					name="techrappy_seo_meta_description"
					rows="3"
					class="large-text"
					maxlength="160"
					placeholder="<?php esc_attr_e( 'Description SEO (max. 160 caractères)', 'techrappy-seo' ); ?>"
				><?php echo esc_textarea( $meta_description ); ?></textarea>
			</td>
		</tr>
	</table>

	<div class="techrappy-seo-actions">
		<button
			type="button"
			id="techrappy-seo-analyze-btn"
			class="button button-primary"
			data-post-id="<?php echo absint( $post->ID ); ?>"
		>
			<?php esc_html_e( 'Analyser avec IA', 'techrappy-seo' ); ?>
		</button>
		<span class="techrappy-seo-spinner spinner"></span>
	</div>

	<?php
	$suggestions = get_post_meta( $post->ID, '_techrappy_seo_suggestions', true );
	if ( ! empty( $suggestions ) && is_array( $suggestions ) ) :
	?>
	<div class="techrappy-seo-suggestions">
		<h4><?php esc_html_e( 'Suggestions IA', 'techrappy-seo' ); ?></h4>
		<ul>
			<?php foreach ( $suggestions as $suggestion ) : ?>
			<li><?php echo esc_html( $suggestion ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

</div>
