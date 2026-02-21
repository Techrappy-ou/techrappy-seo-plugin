\# V5 --- Intégration WordPress (création page/article) \-\-- \##
Fichier 1 : \`includes/WordPress/PostCreationSettings.php\` --- DTO
paramètres WP \`\`\`php \<?php /\*\* \* Paramètres de création WordPress
(DTO). \* \* Encapsule tous les paramètres liés à WordPress nécessaires
\* à la création d\'un post depuis le résultat du moteur SEO. \* \*
\@package TechrappySEO\\WordPress \*/ declare( strict_types=1 );
namespace TechrappySEO\\WordPress; if ( ! defined( \'ABSPATH\' ) ) {
exit; } /\*\* \* Class PostCreationSettings \* \* Value Object ---
immuable après construction. \*/ final class PostCreationSettings { //
───────────────────────────────────────── // Constantes //
───────────────────────────────────────── const POST_TYPE_PAGE =
\'page\'; const POST_TYPE_POST = \'post\'; const STATUS_DRAFT =
\'draft\'; const STATUS_PUBLISH = \'publish\'; const ALLOWED_POST_TYPES
= \[ self::POST_TYPE_PAGE, self::POST_TYPE_POST \]; const
ALLOWED_STATUSES = \[ self::STATUS_DRAFT, self::STATUS_PUBLISH \]; /\*\*
\* Position des liens internes dans le contenu assemblé. \*
\'after_last\' : après la dernière section (défaut) \* \'after_second\'
: après la deuxième section (si disponible) \*/ const
LINKS_POSITION_AFTER_LAST = \'after_last\'; const
LINKS_POSITION_AFTER_SECOND = \'after_second\'; //
───────────────────────────────────────── // Propriétés //
───────────────────────────────────────── /\*\* \@var string \'page\' \|
\'post\' \*/ private string \$post_type; /\*\* \@var string \'draft\' \|
\'publish\' \*/ private string \$post_status; /\*\* \@var int ID du post
parent (pages uniquement, 0 = aucun) \*/ private int \$post_parent;
/\*\* \@var int\[\] IDs des catégories (articles uniquement) \*/ private
array \$category_ids; /\*\* \@var string\[\] Noms des tags (articles
uniquement) \*/ private array \$tag_names; /\*\* \@var string Règle de
position des liens internes \*/ private string \$links_position; /\*\*
\@var string Auteur du post (ID WordPress) \*/ private int
\$post_author; // ───────────────────────────────────────── //
Constructeur & factory // ─────────────────────────────────────────
private function \_\_construct() {} /\*\* \* Crée un
PostCreationSettings depuis un tableau de données brutes. \* \* \@param
array\<string, mixed\> \$data Données brutes (POST ou tableau). \* \*
\@return self \* \* \@throws \\InvalidArgumentException Si les données
sont invalides. \*/ public static function from_array( array \$data ):
self { \$settings = new self(); // ── Post type ── \$post_type =
sanitize_key( \$data\[\'post_type\'\] ?? self::POST_TYPE_PAGE ); if ( !
in_array( \$post_type, self::ALLOWED_POST_TYPES, true ) ) { throw new
\\InvalidArgumentException( sprintf( \'post_type invalide : \"%s\".
Valeurs acceptées : %s\', \$post_type, implode( \', \',
self::ALLOWED_POST_TYPES ) ) ); } \$settings-\>post_type = \$post_type;
// ── Post status ── \$status = sanitize_key( \$data\[\'post_status\'\]
?? self::STATUS_DRAFT ); if ( ! in_array( \$status,
self::ALLOWED_STATUSES, true ) ) { \$status = self::STATUS_DRAFT; }
\$settings-\>post_status = \$status; // ── Post parent (pages
uniquement) ── \$settings-\>post_parent = ( self::POST_TYPE_PAGE ===
\$settings-\>post_type ) ? absint( \$data\[\'post_parent\'\] ?? 0 ) : 0;
// ── Catégories (articles uniquement) ── \$cat_ids =
\$data\[\'category_ids\'\] ?? \[\]; if ( is_string( \$cat_ids ) ) {
\$cat_ids = array_filter( array_map( \'absint\', explode( \',\',
\$cat_ids ) ) ); } \$settings-\>category_ids = ( self::POST_TYPE_POST
=== \$settings-\>post_type ) ? array_values( array_filter( array_map(
\'absint\', (array) \$cat_ids ) ) ) : \[\]; // ── Tags (articles
uniquement) ── \$tag_names = \$data\[\'tag_names\'\] ?? \[\]; if (
is_string( \$tag_names ) ) { \$tag_names = array_filter( array_map(
\'trim\', explode( \',\', \$tag_names ) ) ); } \$settings-\>tag_names =
( self::POST_TYPE_POST === \$settings-\>post_type ) ? array_values(
array_filter( array_map( \'sanitize_text_field\', (array) \$tag_names )
) ) : \[\]; // ── Position des liens internes ── \$links_position =
sanitize_key( \$data\[\'links_position\'\] ??
self::LINKS_POSITION_AFTER_LAST ); \$settings-\>links_position =
in_array( \$links_position, \[ self::LINKS_POSITION_AFTER_LAST,
self::LINKS_POSITION_AFTER_SECOND, \], true ) ? \$links_position :
self::LINKS_POSITION_AFTER_LAST; // ── Auteur ──
\$settings-\>post_author = absint( \$data\[\'post_author\'\] ??
get_current_user_id() ); return \$settings; } //
───────────────────────────────────────── // Accesseurs //
───────────────────────────────────────── public function
get_post_type(): string { return \$this-\>post_type; } public function
get_post_status(): string { return \$this-\>post_status; } public
function get_post_parent(): int { return \$this-\>post_parent; } public
function get_category_ids(): array { return \$this-\>category_ids; }
public function get_tag_names(): array { return \$this-\>tag_names; }
public function get_links_position(): string { return
\$this-\>links_position; } public function get_post_author(): int {
return \$this-\>post_author; } public function is_page(): bool { return
self::POST_TYPE_PAGE === \$this-\>post_type; } public function
is_post(): bool { return self::POST_TYPE_POST === \$this-\>post_type; }
public function is_draft(): bool { return self::STATUS_DRAFT ===
\$this-\>post_status; } /\*\* \* Sérialise en tableau (pour logs). \* \*
\@return array\<string, mixed\> \*/ public function to_array(): array {
return \[ \'post_type\' =\> \$this-\>post_type, \'post_status\' =\>
\$this-\>post_status, \'post_parent\' =\> \$this-\>post_parent,
\'category_ids\' =\> \$this-\>category_ids, \'tag_names\' =\>
\$this-\>tag_names, \'links_position\' =\> \$this-\>links_position,
\'post_author\' =\> \$this-\>post_author, \]; } } \`\`\` \-\-- \##
Fichier 2 : \`includes/WordPress/ContentAssembler.php\` --- Assemblage
HTML \`\`\`php \<?php /\*\* \* Assemblage du contenu HTML final pour
WordPress. \* \* Responsabilité : construire le post_content WordPress
complet \* à partir d\'un GenerationResult, selon les règles
d\'assemblage V1. \* \* Règles de positionnement des liens internes
(documentées) : \* \* - LINKS_POSITION_AFTER_LAST (défaut) : \* Les
liens internes sont placés après la DERNIÈRE section H2. \* Recommandé
pour les pages courtes (\< 4 sections). \* \* -
LINKS_POSITION_AFTER_SECOND : \* Les liens internes sont placés après la
DEUXIÈME section H2. \* Recommandé pour les pages longues (\> 4
sections) pour améliorer \* le maillage avant le bas de page. \* \*
\@package TechrappySEO\\WordPress \*/ declare( strict_types=1 );
namespace TechrappySEO\\WordPress; use
TechrappySEO\\Generation\\GenerationResult; if ( ! defined( \'ABSPATH\'
) ) { exit; } /\*\* \* Class ContentAssembler \*/ class ContentAssembler
{ /\*\* \* Balises HTML autorisées dans le contenu assemblé. \* Utilisé
par wp_kses pour sécuriser le HTML IA. \* \* \@var array\<string,
array\<string, bool\>\> \*/ private array \$allowed_html; /\*\* \*
Constructeur. \*/ public function \_\_construct() {
\$this-\>allowed_html = \$this-\>build_allowed_html_map(); } //
───────────────────────────────────────── // Point d\'entrée principal
// ───────────────────────────────────────── /\*\* \* Assemble le
post_content complet depuis un GenerationResult. \* \* \@param
GenerationResult \$result Résultat du moteur SEO. \* \@param
PostCreationSettings \$settings Paramètres de création WP. \* \*
\@return string HTML assemblé et sécurisé, prêt pour post_content. \*/
public function assemble( GenerationResult \$result,
PostCreationSettings \$settings ): string { \$parts = \[\]; \$sections =
\$result-\>get_sections(); \$links_html =
\$this-\>collect_internal_links( \$sections ); \$links_position =
\$settings-\>get_links_position(); \$links_injected = false; // ── 1.
Introduction ────────────────────────────── \$intro =
\$result-\>get_intro_html(); if ( ! empty( \$intro ) ) { \$parts\[\] =
\$this-\>sanitize_html( \$intro ); } // ── 2. Sections H2
────────────────────────────── foreach ( \$sections as \$index =\>
\$section ) { \$section_parts = \[\]; // H2. if ( ! empty(
\$section\[\'h2\'\] ) ) { \$section_parts\[\] = sprintf(
\'\<h2\>%s\</h2\>\', esc_html( \$section\[\'h2\'\] ) ); } // Corps de la
section. if ( ! empty( \$section\[\'html\'\] ) ) { \$section_parts\[\] =
\$this-\>sanitize_html( \$section\[\'html\'\] ); } \$parts\[\] =
implode( \"\\n\", \$section_parts ); // Injection des liens internes
après la 2e section (si option). if (
PostCreationSettings::LINKS_POSITION_AFTER_SECOND === \$links_position
&& ! \$links_injected && \$index === 1 && ! empty( \$links_html ) ) {
\$parts\[\] = \$links_html; \$links_injected = true; } } // ── 3. Liens
internes (position : après dernière section) ── if (
PostCreationSettings::LINKS_POSITION_AFTER_LAST === \$links_position &&
! empty( \$links_html ) ) { \$parts\[\] = \$links_html; } // Injecter
les liens restants si position \"after_second\" mais // moins de 2
sections disponibles (fallback after_last). if (
PostCreationSettings::LINKS_POSITION_AFTER_SECOND === \$links_position
&& ! \$links_injected && ! empty( \$links_html ) ) { \$parts\[\] =
\$links_html; } // ── 4. CTA ──────────────────────────────────────
\$cta = \$result-\>get_cta_block_html(); if ( ! empty( \$cta ) ) {
\$parts\[\] = \$this-\>sanitize_html( \$cta ); } // ── 5. FAQ HTML
visible ────────────────────────── \$faq_html = \$this-\>build_faq_html(
\$result ); if ( ! empty( \$faq_html ) ) { \$parts\[\] = \$faq_html; }
// ── 6. FAQ JSON-LD (balise script) ─────────────── \$faq_schema =
\$result-\>get_faq_schema(); if ( ! empty( \$faq_schema ) ) {
\$parts\[\] = sprintf( \'\<script
type=\"application/ld+json\"\>%s\</script\>\', // Le JSON-LD ne doit pas
être echappé HTML --- il est déjà validé. \$faq_schema ); } // Filtrer
les parties vides et assembler. \$html = implode( \"\\n\\n\",
array_filter( \$parts, fn( \$p ) =\> \'\' !== trim( \$p ) ) ); return
\$html; } // ───────────────────────────────────────── // Helpers
assemblage // ───────────────────────────────────────── /\*\* \*
Collecte le HTML des liens internes depuis les sections. \* Les liens
internes sont stockés dans section\[\'internal_links_html\'\]. \* \*
\@param array\<int, array{h2: string, html: string, internal_links_html:
string}\> \$sections \* \* \@return string HTML des liens internes
(premier non-vide trouvé). \*/ private function collect_internal_links(
array \$sections ): string { foreach ( \$sections as \$section ) { if (
! empty( \$section\[\'internal_links_html\'\] ) ) { return
\$this-\>sanitize_html( \$section\[\'internal_links_html\'\] ); } }
return \'\'; } /\*\* \* Construit le HTML visible de la FAQ depuis les
items structurés. \* \* \@param GenerationResult \$result Résultat de
génération. \* \* \@return string HTML de la FAQ ou chaîne vide. \*/
private function build_faq_html( GenerationResult \$result ): string {
\$items = \$result-\>get_faq_items(); if ( empty( \$items ) ) { return
\'\'; } \$items_html = \'\'; foreach ( \$items as \$item ) { \$question
= esc_html( \$item\[\'q\'\] ?? \'\' ); \$answer =
\$this-\>sanitize_html( \$item\[\'a_html\'\] ?? \'\' ); if ( empty(
\$question ) \|\| empty( \$answer ) ) { continue; } \$items_html .=
sprintf(
\"\<details\>\\n\<summary\>%s\</summary\>\\n%s\\n\</details\>\",
\$question, \$answer ); } if ( empty( \$items_html ) ) { return \'\'; }
return sprintf( \'\<section class=\"techrappy-faq\"\>\' . \"\\n\" .
\'\<h2\>%s\</h2\>\' . \"\\n\" . \'%s\' . \"\\n\" . \'\</section\>\',
esc_html\_\_( \'Questions fréquentes\', \'techrappy-seo\' ),
\$items_html ); } /\*\* \* Sanitize le HTML généré par l\'IA avec la
liste des balises autorisées. \* Utilise wp_kses() --- plus restrictif
que wp_kses_post() pour la sécurité. \* \* \@param string \$html HTML
brut. \* \* \@return string HTML sécurisé. \*/ private function
sanitize_html( string \$html ): string { return wp_kses( \$html,
\$this-\>allowed_html ); } /\*\* \* Construit le tableau des balises
HTML autorisées. \* \* \@return array\<string, array\<string,
bool\|array\>\> \*/ private function build_allowed_html_map(): array {
return \[ // Titres. \'h2\' =\> \[ \'class\' =\> true, \'id\' =\> true
\], \'h3\' =\> \[ \'class\' =\> true, \'id\' =\> true \], \'h4\' =\> \[
\'class\' =\> true, \'id\' =\> true \], // Texte. \'p\' =\> \[ \'class\'
=\> true \], \'strong\' =\> \[ \'class\' =\> true \], \'em\' =\> \[
\'class\' =\> true \], \'br\' =\> \[\], \'span\' =\> \[ \'class\' =\>
true \], // Listes. \'ul\' =\> \[ \'class\' =\> true \], \'ol\' =\> \[
\'class\' =\> true \], \'li\' =\> \[ \'class\' =\> true \], // Liens.
\'a\' =\> \[ \'href\' =\> true, \'title\' =\> true, \'class\' =\> true,
\'target\' =\> true, \'rel\' =\> true, \], // Structure. \'div\' =\> \[
\'class\' =\> true, \'id\' =\> true \], \'section\' =\> \[ \'class\' =\>
true, \'id\' =\> true \], \'article\' =\> \[ \'class\' =\> true \],
\'details\' =\> \[ \'class\' =\> true, \'open\' =\> true \], \'summary\'
=\> \[ \'class\' =\> true \], // Tableau. \'table\' =\> \[ \'class\' =\>
true \], \'thead\' =\> \[\], \'tbody\' =\> \[\], \'tr\' =\> \[ \'class\'
=\> true \], \'th\' =\> \[ \'class\' =\> true, \'scope\' =\> true \],
\'td\' =\> \[ \'class\' =\> true \], // Média. \'figure\' =\> \[
\'class\' =\> true \], \'figcaption\' =\> \[ \'class\' =\> true \],
\'img\' =\> \[ \'src\' =\> true, \'alt\' =\> true, \'class\' =\> true,
\'width\' =\> true, \'height\' =\> true, \], // CTA. \'button\' =\> \[
\'class\' =\> true, \'type\' =\> true \], \]; } } \`\`\` \-\-- \##
Fichier 3 : \`includes/WordPress/SlugManager.php\` --- Gestion des slugs
\`\`\`php \<?php /\*\* \* Gestionnaire des slugs WordPress. \* \*
Responsabilité : générer un slug SEO-friendly unique, \* détecter les
collisions et proposer des alternatives. \* \* \@package
TechrappySEO\\WordPress \*/ declare( strict_types=1 ); namespace
TechrappySEO\\WordPress; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\*
\* Class SlugManager \*/ class SlugManager { /\*\* \* Génère un slug
unique pour un post WordPress. \* Si le slug suggéré est déjà utilisé,
ajoute un suffixe numérique. \* \* \@param string \$suggested_slug Slug
suggéré par le moteur SEO. \* \@param string \$post_type Type de post
(\'page\' \| \'post\'). \* \@param int \$exclude_id ID du post à exclure
de la vérification (0 = aucun). \* \* \@return array{slug: string,
was_modified: bool, original: string} \*/ public function
generate_unique( string \$suggested_slug, string \$post_type = \'page\',
int \$exclude_id = 0 ): array { // Sanitize le slug de base. \$base_slug
= sanitize_title( \$suggested_slug ); if ( empty( \$base_slug ) ) {
\$base_slug = \'page-seo-\' . time(); } \$final_slug = \$base_slug;
\$was_modified = false; \$counter = 1; // Boucle de recherche
d\'unicité. while ( \$this-\>slug_exists( \$final_slug, \$post_type,
\$exclude_id ) ) { \$counter++; \$final_slug = \$base_slug . \'-\' .
\$counter; \$was_modified = true; // Garde-fou : max 50 tentatives. if (
\$counter \> 50 ) { \$final_slug = \$base_slug . \'-\' . time();
\$was_modified = true; break; } } return \[ \'slug\' =\> \$final_slug,
\'was_modified\' =\> \$was_modified, \'original\' =\> \$base_slug, \]; }
/\*\* \* Vérifie si un slug est déjà utilisé dans la base WordPress. \*
\* \@param string \$slug Slug à vérifier. \* \@param string \$post_type
Type de post. \* \@param int \$exclude_id ID à exclure (0 = aucun). \*
\* \@return bool True si le slug est déjà utilisé. \*/ public function
slug_exists( string \$slug, string \$post_type = \'page\', int
\$exclude_id = 0 ): bool { global \$wpdb; \$query = \$wpdb-\>prepare(
\"SELECT COUNT(ID) FROM {\$wpdb-\>posts} WHERE post_name = %s AND
post_type = %s AND post_status NOT IN (\'trash\', \'auto-draft\')\",
\$slug, \$post_type ); // Exclure un post spécifique (mise à jour). if (
\$exclude_id \> 0 ) { \$query = \$wpdb-\>prepare( \"SELECT COUNT(ID)
FROM {\$wpdb-\>posts} WHERE post_name = %s AND post_type = %s AND
post_status NOT IN (\'trash\', \'auto-draft\') AND ID != %d\", \$slug,
\$post_type, \$exclude_id ); } return (int) \$wpdb-\>get_var( \$query )
\> 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared } /\*\* \*
Sanitize et normalise un slug pour WordPress. \* Gère les caractères
accentués français. \* \* \@param string \$raw Texte brut (mot-clé,
H1...). \* \* \@return string Slug normalisé. \*/ public function
sanitize( string \$raw ): string { return sanitize_title(
remove_accents( \$raw ) ); } } \`\`\` \-\-- \## Fichier 4 :
\`includes/WordPress/PostCreator.php\` --- Service de création \`\`\`php
\<?php /\*\* \* Service de création de posts WordPress depuis le JSON du
moteur SEO. \* \* Responsabilité unique : prendre un GenerationResult +
PostCreationSettings, \* assembler le contenu, créer le post WordPress
et retourner son ID. \* \* SANS Yoast, SANS Divi, SANS Action Scheduler.
\* \* \@package TechrappySEO\\WordPress \*/ declare( strict_types=1 );
namespace TechrappySEO\\WordPress; use
TechrappySEO\\Generation\\GenerationResult; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class PostCreator \*/ class PostCreator { //
───────────────────────────────────────── // Dépendances //
───────────────────────────────────────── /\*\* \@var ContentAssembler
\*/ private ContentAssembler \$assembler; /\*\* \@var SlugManager \*/
private SlugManager \$slug_manager; /\*\* \@var Logger \*/ private
Logger \$logger; // ───────────────────────────────────────── //
Constructeur // ───────────────────────────────────────── /\*\* \*
Constructeur. \* \* \@param Logger\|null \$logger Logger externe (null =
logger standalone). \*/ public function \_\_construct( ?Logger \$logger
= null ) { \$this-\>assembler = new ContentAssembler();
\$this-\>slug_manager = new SlugManager(); \$this-\>logger = \$logger ??
new Logger( \'post_creator\_\' . time() ); } //
───────────────────────────────────────── // Point d\'entrée principal
// ───────────────────────────────────────── /\*\* \* Crée un post
WordPress depuis un GenerationResult. \* \* C\'est la fonction centrale
demandée : \* create_post_from_seo_json( \$seo_json, \$settings ) -\>
\$post_id \* \* \@param GenerationResult \$result Résultat du moteur
SEO. \* \@param PostCreationSettings \$settings Paramètres WordPress de
création. \* \* \@return PostCreationResult Résultat de la création
(post_id, permalink, logs...). \*/ public function
create_post_from_result( GenerationResult \$result, PostCreationSettings
\$settings ): PostCreationResult { \$this-\>logger-\>info(
\'post_creator\', sprintf( \'Création post --- type: %s --- status: %s
--- H1: \"%s\"\', \$settings-\>get_post_type(),
\$settings-\>get_post_status(), \$result-\>get_h1() ) ); // ── 1.
Générer le slug unique ────────────────────── \$slug_data =
\$this-\>slug_manager-\>generate_unique( \$result-\>get_slug(),
\$settings-\>get_post_type() ); if ( \$slug_data\[\'was_modified\'\] ) {
\$this-\>logger-\>warning( \'post_creator\', sprintf( \'Slug \"%s\" déjà
utilisé --- remplacé par \"%s\".\', \$slug_data\[\'original\'\],
\$slug_data\[\'slug\'\] ) ); } \$final_slug = \$slug_data\[\'slug\'\];
// ── 2. Assembler le post_content HTML ────────────── \$post_content =
\$this-\>assembler-\>assemble( \$result, \$settings ); if ( empty(
\$post_content ) ) { \$this-\>logger-\>warning( \'post_creator\',
\'post_content vide après assemblage.\' ); } // ── 3. Déterminer le
post_title ──────────────────── \$post_title = ! empty(
\$result-\>get_h1() ) ? \$result-\>get_h1() : \$final_slug; // ── 4.
Construire les arguments wp_insert_post ───── \$post_args = \[
\'post_title\' =\> sanitize_text_field( \$post_title ), \'post_content\'
=\> \$post_content, \'post_name\' =\> \$final_slug, \'post_status\' =\>
\$settings-\>get_post_status(), \'post_type\' =\>
\$settings-\>get_post_type(), \'post_author\' =\>
\$settings-\>get_post_author(), \]; // Hiérarchie (pages uniquement). if
( \$settings-\>is_page() && \$settings-\>get_post_parent() \> 0 ) { //
Vérifier que le parent existe. \$parent = get_post(
\$settings-\>get_post_parent() ); if ( \$parent && \'page\' ===
\$parent-\>post_type ) { \$post_args\[\'post_parent\'\] =
\$settings-\>get_post_parent(); } else { \$this-\>logger-\>warning(
\'post_creator\', sprintf( \'post_parent %d invalide ou inexistant ---
ignoré.\', \$settings-\>get_post_parent() ) ); } } // ── 5. Appliquer le
filtre WordPress avant insertion ── \$post_args = apply_filters(
\'techrappy_seo_post_content_before\', \$post_args, \$result, \$settings
); // ── 6. Insérer le post ─────────────────────────────
\$this-\>logger-\>info( \'post_creator\', \'Appel wp_insert_post()...\'
); \$post_id = wp_insert_post( \$post_args, true ); // ── 7. Gérer les
erreurs WordPress ──────────────── if ( is_wp_error( \$post_id ) ) {
\$error_msg = \$post_id-\>get_error_message(); \$this-\>logger-\>error(
\'post_creator\', \'wp_insert_post() échec : \' . \$error_msg ); return
PostCreationResult::error( message: \$error_msg, logs:
\$this-\>logger-\>get_logs() ); } \$this-\>logger-\>info(
\'post_creator\', sprintf( \'Post créé avec ID: %d\', \$post_id ) ); //
── 8. Taxonomies (articles uniquement) ─────────── if (
\$settings-\>is_post() ) { \$this-\>set_categories( \$post_id,
\$settings-\>get_category_ids() ); \$this-\>set_tags( \$post_id,
\$settings-\>get_tag_names() ); } // ── 9. Stocker les métadonnées du
plugin ────────── \$this-\>store_plugin_meta( \$post_id, \$result ); //
── 10. Filtre post-insertion ───────────────────── do_action(
\'techrappy_seo_post_content_after\', \$post_id, \$result, \$settings );
// ── 11. Construire le résultat ───────────────────── \$permalink =
get_permalink( \$post_id ); \$this-\>logger-\>info( \'post_creator\',
sprintf( \'Post créé avec succès --- ID: %d --- Permalink: %s\',
\$post_id, \$permalink ?: \'(non disponible)\' ) ); return
PostCreationResult::success( post_id: \$post_id, permalink: \$permalink
?: \'\', slug: \$final_slug, post_type: \$settings-\>get_post_type(),
status: \$settings-\>get_post_status(), warnings:
\$slug_data\[\'was_modified\'\] ? \[ sprintf( \'Slug modifié : \"%s\" →
\"%s\"\', \$slug_data\[\'original\'\], \$final_slug ) \] : \[\], logs:
\$this-\>logger-\>get_logs() ); } /\*\* \* Fonction alias statique ---
interface demandée par le cahier des charges. \* \* Usage : \* \$result
= PostCreator::create_post_from_seo_json( \$seo_json_array,
\$settings_array ); \* \$post_id = \$result-\>get_post_id(); \* \*
\@param array\<string, mixed\> \$seo_json Tableau issu de
GenerationResult::to_array(). \* \@param array\<string, mixed\>
\$settings Tableau de paramètres pour PostCreationSettings. \* \*
\@return PostCreationResult \*/ public static function
create_post_from_seo_json( array \$seo_json, array \$settings ):
PostCreationResult { try { \$result = GenerationResult::from_array(
\$seo_json ); \$creation_settings = PostCreationSettings::from_array(
\$settings ); } catch ( \\InvalidArgumentException \$e ) { return
PostCreationResult::error( message: \$e-\>getMessage(), logs: \[\] ); }
\$creator = new self(); return \$creator-\>create_post_from_result(
\$result, \$creation_settings ); } //
───────────────────────────────────────── // Helpers privés //
───────────────────────────────────────── /\*\* \* Assigne les
catégories à un article. \* \* \@param int \$post_id ID du post. \*
\@param int\[\] \$category_ids IDs des catégories. \* \* \@return void
\*/ private function set_categories( int \$post_id, array \$category_ids
): void { if ( empty( \$category_ids ) ) { return; } \$result =
wp_set_post_categories( \$post_id, \$category_ids, false ); if (
is_wp_error( \$result ) ) { \$this-\>logger-\>warning( \'post_creator\',
\'Erreur assignation catégories : \' . \$result-\>get_error_message() );
} else { \$this-\>logger-\>info( \'post_creator\', sprintf( \'Catégories
assignées : %s\', implode( \', \', \$category_ids ) ) ); } } /\*\* \*
Assigne les tags à un article. \* \* \@param int \$post_id ID du post.
\* \@param string\[\] \$tag_names Noms des tags. \* \* \@return void \*/
private function set_tags( int \$post_id, array \$tag_names ): void { if
( empty( \$tag_names ) ) { return; } \$result = wp_set_post_tags(
\$post_id, \$tag_names, false ); if ( is_wp_error( \$result ) ) {
\$this-\>logger-\>warning( \'post_creator\', \'Erreur assignation tags :
\' . \$result-\>get_error_message() ); } else { \$this-\>logger-\>info(
\'post_creator\', sprintf( \'Tags assignés : %s\', implode( \', \',
\$tag_names ) ) ); } } /\*\* \* Stocke les métadonnées du plugin sur le
post créé. \* Permet de retrouver les posts générés par Techrappy SEO.
\* \* \@param int \$post_id ID du post. \* \@param GenerationResult
\$result Résultat de génération. \* \* \@return void \*/ private
function store_plugin_meta( int \$post_id, GenerationResult \$result ):
void { // Marquer ce post comme généré par le plugin. update_post_meta(
\$post_id, \'\_techrappy_seo_generated\', \'1\' ); // Stocker l\'ID de
la requête pour traçabilité. update_post_meta( \$post_id,
\'\_techrappy_seo_request_id\', \$result-\>get_request_id() ); //
Stocker le score QA pour référence. \$qa = \$result-\>get_qa_score(); if
( ! empty( \$qa\[\'score_seo\'\] ) ) { update_post_meta( \$post_id,
\'\_techrappy_seo_score_seo\', (int) \$qa\[\'score_seo\'\] );
update_post_meta( \$post_id, \'\_techrappy_seo_score_humain\', (int)
\$qa\[\'score_humain\'\] ); } // Date de génération. update_post_meta(
\$post_id, \'\_techrappy_seo_generated_at\', current_time( \'mysql\' )
); } } \`\`\` \-\-- \## Fichier 5 :
\`includes/WordPress/PostCreationResult.php\` --- DTO résultat création
\`\`\`php \<?php /\*\* \* Résultat de la création d\'un post WordPress
(DTO). \* \* \@package TechrappySEO\\WordPress \*/ declare(
strict_types=1 ); namespace TechrappySEO\\WordPress; if ( ! defined(
\'ABSPATH\' ) ) { exit; } /\*\* \* Class PostCreationResult \* \* Value
Object immuable --- retourné par PostCreator::create_post_from_result().
\*/ final class PostCreationResult { /\*\* \@var bool \*/ private bool
\$success; /\*\* \@var int ID du post créé (0 si erreur) \*/ private int
\$post_id; /\*\* \@var string Permalink du post créé \*/ private string
\$permalink; /\*\* \@var string Slug final utilisé \*/ private string
\$slug; /\*\* \@var string Type de post \*/ private string \$post_type;
/\*\* \@var string Statut du post \*/ private string \$post_status;
/\*\* \@var string Message d\'erreur (vide si succès) \*/ private string
\$error_message; /\*\* \@var string\[\] Avertissements non-bloquants \*/
private array \$warnings; /\*\* \@var array Logs d\'exécution \*/
private array \$logs; private function \_\_construct() {} /\*\* \*
Factory : création réussie. \* \* \@param int \$post_id ID du post créé.
\* \@param string \$permalink URL du post. \* \@param string \$slug Slug
final. \* \@param string \$post_type Type de post. \* \@param string
\$status Statut. \* \@param string\[\] \$warnings Avertissements. \*
\@param array \$logs Logs. \* \* \@return self \*/ public static
function success( int \$post_id, string \$permalink, string \$slug,
string \$post_type, string \$status, array \$warnings = \[\], array
\$logs = \[\] ): self { \$r = new self(); \$r-\>success = true;
\$r-\>post_id = \$post_id; \$r-\>permalink = \$permalink; \$r-\>slug =
\$slug; \$r-\>post_type = \$post_type; \$r-\>post_status = \$status;
\$r-\>error_message = \'\'; \$r-\>warnings = \$warnings; \$r-\>logs =
\$logs; return \$r; } /\*\* \* Factory : création en erreur. \* \*
\@param string \$message Message d\'erreur. \* \@param array \$logs
Logs. \* \* \@return self \*/ public static function error( string
\$message, array \$logs = \[\] ): self { \$r = new self(); \$r-\>success
= false; \$r-\>post_id = 0; \$r-\>permalink = \'\'; \$r-\>slug = \'\';
\$r-\>post_type = \'\'; \$r-\>post_status = \'\'; \$r-\>error_message =
\$message; \$r-\>warnings = \[\]; \$r-\>logs = \$logs; return \$r; }
public function is_success(): bool { return \$this-\>success; } public
function is_error(): bool { return ! \$this-\>success; } public function
get_post_id(): int { return \$this-\>post_id; } public function
get_permalink(): string { return \$this-\>permalink; } public function
get_slug(): string { return \$this-\>slug; } public function
get_post_type(): string { return \$this-\>post_type; } public function
get_status(): string { return \$this-\>post_status; } public function
get_error(): string { return \$this-\>error_message; } public function
get_warnings(): array { return \$this-\>warnings; } public function
get_logs(): array { return \$this-\>logs; } /\*\* \* URL d\'édition WP
Admin pour le post créé. \* \* \@return string \*/ public function
get_edit_url(): string { return \$this-\>post_id \> 0 ?
get_edit_post_link( \$this-\>post_id, \'raw\' ) ?? \'\' : \'\'; } /\*\*
\* Sérialise en tableau (pour réponse AJAX). \* \* \@return
array\<string, mixed\> \*/ public function to_array(): array { return \[
\'success\' =\> \$this-\>success, \'post_id\' =\> \$this-\>post_id,
\'permalink\' =\> \$this-\>permalink, \'slug\' =\> \$this-\>slug,
\'post_type\' =\> \$this-\>post_type, \'post_status\' =\>
\$this-\>post_status, \'edit_url\' =\> \$this-\>get_edit_url(),
\'error_message\' =\> \$this-\>error_message, \'warnings\' =\>
\$this-\>warnings, \]; } } \`\`\` \-\-- \## Fichier 6 :
\`includes/Admin/Ajax/AjaxWordPress.php\` --- Handler AJAX création
\`\`\`php \<?php /\*\* \* Handler AJAX : Création WordPress depuis le
résultat SEO. \* \* Actions : \* - techrappy_create_post : crée le post
depuis le JSON SEO \* - techrappy_preview_content : retourne le HTML
assemblé sans créer \* \* \@package TechrappySEO\\Admin\\Ajax \*/
declare( strict_types=1 ); namespace TechrappySEO\\Admin\\Ajax; use
TechrappySEO\\Generation\\GenerationResult; use
TechrappySEO\\WordPress\\PostCreator; use
TechrappySEO\\WordPress\\PostCreationSettings; use
TechrappySEO\\WordPress\\ContentAssembler; if ( ! defined( \'ABSPATH\' )
) { exit; } /\*\* \* Class AjaxWordPress \*/ class AjaxWordPress { /\*\*
\* Crée le post WordPress depuis le JSON SEO stocké côté client. \* \*
POST params : \* - nonce : \'techrappy_seo_generation\' \* - seo_json :
JSON string (issu de GenerationResult::to_json()) \* - post_type :
\'page\' \| \'post\' \* - post_status : \'draft\' \| \'publish\' \* -
post_parent : int (pages) \* - category_ids : string CSV (articles) \* -
tag_names : string CSV (articles) \* - links_position : \'after_last\'
\| \'after_second\' \* \* \@return void \*/ public function
handle_create_post(): void { \$this-\>verify_request(
\'techrappy_seo_generation\' ); // ── Décoder le JSON SEO ──
\$seo_json_raw = sanitize_textarea_field( wp_unslash(
\$\_POST\[\'seo_json\'\] ?? \'\' ) ); if ( empty( \$seo_json_raw ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'Données SEO manquantes
(seo_json vide).\', \'techrappy-seo\' ), \], 400 ); } \$seo_data =
json_decode( \$seo_json_raw, true ); if ( JSON_ERROR_NONE !==
json_last_error() \|\| ! is_array( \$seo_data ) ) { wp_send_json_error(
\[ \'message\' =\> \_\_( \'JSON SEO invalide : \' .
json_last_error_msg(), \'techrappy-seo\' ), \], 400 ); } // ──
Construire les paramètres WP ── try { \$settings =
PostCreationSettings::from_array( \[ \'post_type\' =\> sanitize_key(
wp_unslash( \$\_POST\[\'post_type\'\] ?? \'page\' ) ), \'post_status\'
=\> sanitize_key( wp_unslash( \$\_POST\[\'post_status\'\] ?? \'draft\' )
), \'post_parent\' =\> absint( \$\_POST\[\'post_parent\'\] ?? 0 ),
\'category_ids\' =\> sanitize_text_field( wp_unslash(
\$\_POST\[\'category_ids\'\] ?? \'\' ) ), \'tag_names\' =\>
sanitize_text_field( wp_unslash( \$\_POST\[\'tag_names\'\] ?? \'\' ) ),
\'links_position\' =\> sanitize_key( wp_unslash(
\$\_POST\[\'links_position\'\] ?? \'after_last\' ) ), \] ); } catch (
\\InvalidArgumentException \$e ) { wp_send_json_error( \[ \'message\'
=\> \$e-\>getMessage(), \], 400 ); } // ── Créer le post via PostCreator
── \$creation_result = PostCreator::create_post_from_seo_json(
\$seo_data, \$settings-\>to_array() ); if (
\$creation_result-\>is_error() ) { wp_send_json_error( \[ \'message\'
=\> \$creation_result-\>get_error(), \'warnings\' =\>
\$creation_result-\>get_warnings(), \], 500 ); } wp_send_json_success(
\$creation_result-\>to_array() ); } /\*\* \* Retourne le HTML assemblé
pour prévisualisation, \* SANS créer le post WordPress. \* \* POST
params : \* - nonce : \'techrappy_seo_generation\' \* - seo_json : JSON
string \* - links_position : \'after_last\' \| \'after_second\' \* -
post_type : \'page\' \| \'post\' \* \* \@return void \*/ public function
handle_preview_content(): void { \$this-\>verify_request(
\'techrappy_seo_generation\' ); \$seo_json_raw =
sanitize_textarea_field( wp_unslash( \$\_POST\[\'seo_json\'\] ?? \'\' )
); if ( empty( \$seo_json_raw ) ) { wp_send_json_error( \[ \'message\'
=\> \_\_( \'seo_json manquant.\', \'techrappy-seo\' ), \], 400 ); }
\$seo_data = json_decode( \$seo_json_raw, true ); if ( JSON_ERROR_NONE
!== json_last_error() \|\| ! is_array( \$seo_data ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'JSON invalide.\',
\'techrappy-seo\' ), \], 400 ); } try { \$result =
GenerationResult::from_array( \$seo_data ); \$settings =
PostCreationSettings::from_array( \[ \'post_type\' =\> sanitize_key(
wp_unslash( \$\_POST\[\'post_type\'\] ?? \'page\' ) ), \'post_status\'
=\> \'draft\', \'links_position\' =\> sanitize_key( wp_unslash(
\$\_POST\[\'links_position\'\] ?? \'after_last\' ) ), \] ); } catch (
\\InvalidArgumentException \$e ) { wp_send_json_error( \[ \'message\'
=\> \$e-\>getMessage() \], 400 ); } \$assembler = new
ContentAssembler(); \$html = \$assembler-\>assemble( \$result,
\$settings ); wp_send_json_success( \[ \'html\' =\> \$html,
\'metatitle\' =\> \$result-\>get_metatitle(), \'metadescription\' =\>
\$result-\>get_metadescription(), \'slug\' =\> \$result-\>get_slug(),
\'h1\' =\> \$result-\>get_h1(), \'sections_count\' =\> count(
\$result-\>get_sections() ), \'faq_items_count\' =\> count(
\$result-\>get_faq_items() ), \'warnings\' =\>
\$result-\>get_warnings(), \] ); } /\*\* \* Vérifie nonce + capacité. \*
\* \@param string \$action Action du nonce. \* \@return void \*/ private
function verify_request( string \$action ): void { check_ajax_referer(
\$action, \'nonce\' ); if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY
) ) { wp_send_json_error( \[ \'message\' =\> \_\_( \'Accès non
autorisé.\', \'techrappy-seo\' ) \], 403 ); } } } \`\`\` \-\-- \##
Fichier 7 : \`views/admin/wizard/step-1-mode.php\` --- UI de génération
complète \`\`\`php \<?php /\*\* \* Vue : Page \"Générer\" ---
formulaire + prévisualisation + création. \* \* \@package TechrappySEO
\*/ if ( ! defined( \'ABSPATH\' ) ) { exit; } // Récupérer les
catégories et pages parentes pour les selects. \$categories =
get_categories( \[ \'hide_empty\' =\> false, \'orderby\' =\> \'name\' \]
); \$parent_pages = get_pages( \[ \'sort_column\' =\> \'post_title\',
\'hierarchical\' =\> false \] ); \$gen_nonce = wp_create_nonce(
\'techrappy_seo_generation\' ); ?\> \<div class=\"wrap
techrappy-seo-wrap\" id=\"techrappy-generate-page\"\> \<h1\>\<?php
esc_html_e( \'Générer un contenu SEO\', \'techrappy-seo\' ); ?\>\</h1\>
\<div id=\"techrappy-gen-notice\" class=\"techrappy-notice\"
aria-live=\"polite\" style=\"display:none;\"\>\</div\> \<?php // ──
Vérification clé API ── ?\> \<?php if ( empty(
\\TechrappySEO\\Settings\\SettingsRepository::get_api_key() ) ) : ?\>
\<div class=\"notice notice-error inline\"\> \<p\> \<?php printf(
esc_html\_\_( \'⚠️ Clé API OpenAI non configurée. %s\',
\'techrappy-seo\' ), sprintf( \'\<a href=\"%s\"\>%s\</a\>\', esc_url(
admin_url( \'admin.php?page=techrappy-seo-settings\' ) ), esc_html\_\_(
\'Configurer maintenant\', \'techrappy-seo\' ) ) ); ?\> \</p\> \</div\>
\<?php endif; ?\> \<div class=\"techrappy-gen-layout\"\> \<?php //
════════════════════════════════════════════ // COLONNE GAUCHE ---
Formulaire de génération // ════════════════════════════════════════════
?\> \<div class=\"techrappy-gen-form-col\"\> \<div
class=\"techrappy-card\"\> \<h2 class=\"techrappy-card-title\"\> 🔧
\<?php esc_html_e( \'Paramètres de génération\', \'techrappy-seo\' );
?\> \</h2\> \<form id=\"techrappy-gen-form\" autocomplete=\"off\"\>
\<?php // ── Type de contenu ── ?\> \<div
class=\"techrappy-field-group\"\> \<label class=\"techrappy-label\"
for=\"gen-type-contenu\"\> \<?php esc_html_e( \'Type de contenu\',
\'techrappy-seo\' ); ?\> \<span class=\"required\"
aria-hidden=\"true\"\>\*\</span\> \</label\> \<div
class=\"techrappy-radio-group\" role=\"radiogroup\"\> \<label
class=\"techrappy-radio-option is-active\" data-value=\"page_seo\"\>
\<input type=\"radio\" name=\"type_contenu\" id=\"gen-type-page\"
value=\"page_seo\" checked\> \<span
class=\"techrappy-radio-icon\"\>📄\</span\> \<span\>\<?php esc_html_e(
\'Page SEO\', \'techrappy-seo\' ); ?\>\</span\> \</label\> \<label
class=\"techrappy-radio-option\" data-value=\"article_seo\"\> \<input
type=\"radio\" name=\"type_contenu\" id=\"gen-type-article\"
value=\"article_seo\"\> \<span
class=\"techrappy-radio-icon\"\>📝\</span\> \<span\>\<?php esc_html_e(
\'Article SEO\', \'techrappy-seo\' ); ?\>\</span\> \</label\> \</div\>
\</div\> \<?php // ── Mot-clé ── ?\> \<div
class=\"techrappy-field-group\"\> \<label class=\"techrappy-label\"
for=\"gen-mot-cle\"\> \<?php esc_html_e( \'Mot-clé principal\',
\'techrappy-seo\' ); ?\> \<span class=\"required\"
aria-hidden=\"true\"\>\*\</span\> \</label\> \<input type=\"text\"
id=\"gen-mot-cle\" name=\"mot_cle\" class=\"regular-text\"
placeholder=\"\<?php esc_attr_e( \'ex : ostéopathe Beauzelle\',
\'techrappy-seo\' ); ?\>\" required\> \<p class=\"description\"\> \<?php
esc_html_e( \'Le mot-clé principal ciblé pour le référencement.\',
\'techrappy-seo\' ); ?\> \</p\> \</div\> \<?php // ── Profession ── ?\>
\<div class=\"techrappy-field-group\"\> \<label
class=\"techrappy-label\" for=\"gen-profession\"\> \<?php esc_html_e(
\'Profession / activité\', \'techrappy-seo\' ); ?\> \</label\> \<input
type=\"text\" id=\"gen-profession\" name=\"profession\"
class=\"regular-text\" placeholder=\"\<?php esc_attr_e( \'ex :
ostéopathe, kinésithérapeute...\', \'techrappy-seo\' ); ?\>\"\> \</div\>
\<?php // ── Options spécifiques : Page ── ?\> \<div
class=\"techrappy-conditional-block\" id=\"block-page-options\"\> \<div
class=\"techrappy-field-group\"\> \<label class=\"techrappy-label\"
for=\"gen-post-parent\"\> \<?php esc_html_e( \'Page parente\',
\'techrappy-seo\' ); ?\> \</label\> \<select id=\"gen-post-parent\"
name=\"post_parent\" class=\"techrappy-select\"\> \<option
value=\"0\"\>--- \<?php esc_html_e( \'Aucune (page racine)\',
\'techrappy-seo\' ); ?\> ---\</option\> \<?php foreach ( \$parent_pages
as \$parent_page ) : ?\> \<option value=\"\<?php echo esc_attr( (string)
\$parent_page-\>ID ); ?\>\"\> \<?php echo esc_html(
\$parent_page-\>post_title ); ?\> \</option\> \<?php endforeach; ?\>
\</select\> \</div\> \</div\> \<?php // ── Options spécifiques : Article
── ?\> \<div class=\"techrappy-conditional-block\"
id=\"block-article-options\" style=\"display:none;\"\> \<div
class=\"techrappy-field-group\"\> \<label class=\"techrappy-label\"
for=\"gen-category\"\> \<?php esc_html_e( \'Catégorie\',
\'techrappy-seo\' ); ?\> \</label\> \<select id=\"gen-category\"
name=\"category_ids\" class=\"techrappy-select\"\> \<option
value=\"\"\>--- \<?php esc_html_e( \'Sans catégorie\', \'techrappy-seo\'
); ?\> ---\</option\> \<?php foreach ( \$categories as \$cat ) : ?\>
\<option value=\"\<?php echo esc_attr( (string) \$cat-\>term_id );
?\>\"\> \<?php echo esc_html( \$cat-\>name ); ?\> (\<?php echo esc_html(
(string) \$cat-\>count ); ?\>) \</option\> \<?php endforeach; ?\>
\</select\> \</div\> \<div class=\"techrappy-field-group\"\> \<label
class=\"techrappy-label\" for=\"gen-tags\"\> \<?php esc_html_e(
\'Tags\', \'techrappy-seo\' ); ?\> \</label\> \<input type=\"text\"
id=\"gen-tags\" name=\"tag_names\" class=\"regular-text\"
placeholder=\"\<?php esc_attr_e( \'tag1, tag2, tag3\', \'techrappy-seo\'
); ?\>\"\> \<p class=\"description\"\> \<?php esc_html_e( \'Séparés par
des virgules.\', \'techrappy-seo\' ); ?\> \</p\> \</div\> \</div\>
\<?php // ── Statut de publication ── ?\> \<div
class=\"techrappy-field-group\"\> \<label class=\"techrappy-label\"\>
\<?php esc_html_e( \'Statut après création\', \'techrappy-seo\' ); ?\>
\</label\> \<select name=\"post_status\" id=\"gen-post-status\"
class=\"techrappy-select\"\> \<option value=\"draft\" selected\> \<?php
esc_html_e( \'Brouillon\', \'techrappy-seo\' ); ?\> \</option\> \<option
value=\"publish\"\> \<?php esc_html_e( \'Publié\', \'techrappy-seo\' );
?\> \</option\> \</select\> \</div\> \<?php // ── Bouton lancer la
génération ── ?\> \<div class=\"techrappy-gen-submit\"\> \<button
type=\"submit\" id=\"btn-generate\" class=\"button button-primary
button-hero\" data-nonce=\"\<?php echo esc_attr( \$gen_nonce ); ?\>\"\>
✨ \<?php esc_html_e( \'Générer le contenu\', \'techrappy-seo\' ); ?\>
\</button\> \<span id=\"gen-spinner\" class=\"techrappy-spinner\"
style=\"display:none;\"\>\</span\> \<span id=\"gen-step-label\"
class=\"techrappy-step-label\" aria-live=\"polite\"\>\</span\> \</div\>
\</form\> \</div\> \</div\>\<!\-- .techrappy-gen-form-col \--\> \<?php
// ════════════════════════════════════════════ // COLONNE DROITE ---
Prévisualisation + Création //
════════════════════════════════════════════ ?\> \<div
class=\"techrappy-gen-preview-col\"\> \<?php // ── Placeholder avant
génération ── ?\> \<div id=\"gen-preview-placeholder\"
class=\"techrappy-preview-placeholder\"\> \<div
class=\"techrappy-placeholder-inner\"\> \<span
class=\"techrappy-placeholder-icon\"\>✨\</span\> \<p\>\<?php
esc_html_e( \'Le contenu généré apparaîtra ici.\', \'techrappy-seo\' );
?\>\</p\> \</div\> \</div\> \<?php // ── Panel de prévisualisation ──
?\> \<div id=\"gen-preview-panel\" class=\"techrappy-preview-panel\"
style=\"display:none;\"\> \<?php // ── Header preview ── ?\> \<div
class=\"techrappy-preview-header\"\> \<h2\>\<?php esc_html_e(
\'Prévisualisation du contenu\', \'techrappy-seo\' ); ?\>\</h2\> \<?php
// ── Stats QA ── ?\> \<div class=\"techrappy-qa-badges\"
id=\"gen-qa-badges\" style=\"display:none;\"\> \<span
class=\"techrappy-qa-badge\" id=\"qa-badge-seo\" title=\"\<?php
esc_attr_e( \'Score SEO\', \'techrappy-seo\' ); ?\>\"\> SEO:
\<strong\>--\</strong\> \</span\> \<span class=\"techrappy-qa-badge\"
id=\"qa-badge-humain\" title=\"\<?php esc_attr_e( \'Score humain\',
\'techrappy-seo\' ); ?\>\"\> Humain: \<strong\>--\</strong\> \</span\>
\</div\> \</div\> \<?php // ── Métas SEO ── ?\> \<div
class=\"techrappy-meta-preview\" id=\"gen-meta-preview\"\> \<div
class=\"techrappy-meta-row\"\> \<span class=\"techrappy-meta-key\"\>Meta
title :\</span\> \<span class=\"techrappy-meta-value\"
id=\"prev-metatitle\"\>---\</span\> \<span class=\"techrappy-meta-len\"
id=\"prev-metatitle-len\"\>\</span\> \</div\> \<div
class=\"techrappy-meta-row\"\> \<span class=\"techrappy-meta-key\"\>Meta
desc :\</span\> \<span class=\"techrappy-meta-value\"
id=\"prev-metadesc\"\>---\</span\> \<span class=\"techrappy-meta-len\"
id=\"prev-metadesc-len\"\>\</span\> \</div\> \<div
class=\"techrappy-meta-row\"\> \<span class=\"techrappy-meta-key\"\>Slug
:\</span\> \<code class=\"techrappy-meta-value\"
id=\"prev-slug\"\>---\</code\> \</div\> \<div
class=\"techrappy-meta-row\"\> \<span class=\"techrappy-meta-key\"\>H1
:\</span\> \<strong class=\"techrappy-meta-value\"
id=\"prev-h1\"\>---\</strong\> \</div\> \</div\> \<?php // ── Onglets
contenu ── ?\> \<div class=\"techrappy-result-tabs\"\> \<button
type=\"button\" class=\"techrappy-result-tab-btn active\"
data-result-tab=\"preview-html\"\> \<?php esc_html_e( \'Contenu HTML\',
\'techrappy-seo\' ); ?\> \</button\> \<button type=\"button\"
class=\"techrappy-result-tab-btn\" data-result-tab=\"preview-raw\"\>
\<?php esc_html_e( \'JSON brut\', \'techrappy-seo\' ); ?\> \</button\>
\<button type=\"button\" class=\"techrappy-result-tab-btn\"
data-result-tab=\"preview-warnings\"\> \<?php esc_html_e( \'Logs\',
\'techrappy-seo\' ); ?\> \<span id=\"preview-warnings-count\"
class=\"techrappy-badge-count\" style=\"display:none;\"\>0\</span\>
\</button\> \</div\> \<div class=\"techrappy-result-tab-content active\"
id=\"result-tab-preview-html\"\> \<div id=\"gen-preview-html\"
class=\"techrappy-preview-html\"\>\</div\> \</div\> \<div
class=\"techrappy-result-tab-content\" id=\"result-tab-preview-raw\"
style=\"display:none;\"\> \<pre id=\"gen-preview-raw\"
class=\"techrappy-result-pre techrappy-result-raw\"\>\</pre\> \</div\>
\<div class=\"techrappy-result-tab-content\"
id=\"result-tab-preview-warnings\" style=\"display:none;\"\> \<div
id=\"gen-preview-warnings\" class=\"techrappy-warnings-list\"\>\</div\>
\</div\> \<?php // ── Actions de création ── ?\> \<div
class=\"techrappy-creation-actions\" id=\"gen-creation-actions\"\> \<div
class=\"techrappy-creation-info\"\> \<span
id=\"creation-post-type-label\" class=\"techrappy-info-chip\"\>\</span\>
\<span id=\"creation-slug-label\"
class=\"techrappy-info-chip\"\>\</span\> \</div\> \<div
class=\"techrappy-creation-buttons\"\> \<button type=\"button\"
id=\"btn-create-draft\" class=\"button button-secondary button-large\"
data-status=\"draft\" data-nonce=\"\<?php echo esc_attr( \$gen_nonce );
?\>\"\> 💾 \<?php esc_html_e( \'Créer en brouillon\', \'techrappy-seo\'
); ?\> \</button\> \<button type=\"button\" id=\"btn-create-publish\"
class=\"button button-primary button-large\" data-status=\"publish\"
data-nonce=\"\<?php echo esc_attr( \$gen_nonce ); ?\>\"\> 🚀 \<?php
esc_html_e( \'Publier\', \'techrappy-seo\' ); ?\> \</button\> \</div\>
\<p class=\"description\"\> \<?php esc_html_e( \'Le post sera créé avec
le contenu affiché ci-dessus. Vous pourrez l\\\'éditer dans
WordPress.\', \'techrappy-seo\' ); ?\> \</p\> \</div\> \<?php // ──
Résultat de création (affiché après création) ── ?\> \<div
id=\"gen-creation-result\" class=\"techrappy-creation-result\"
style=\"display:none;\"\> \<div class=\"techrappy-creation-success\"\>
\<span class=\"dashicons dashicons-yes-alt\"\>\</span\> \<div
class=\"techrappy-creation-success-text\"\> \<strong
id=\"creation-result-title\"\>\</strong\> \<div
class=\"techrappy-creation-links\"\> \<a id=\"creation-link-edit\"
href=\"#\" target=\"\_blank\"\> \<?php esc_html_e( \'Éditer dans
WordPress\', \'techrappy-seo\' ); ?\> \</a\> \<a
id=\"creation-link-view\" href=\"#\" target=\"\_blank\"\> \<?php
esc_html_e( \'Voir la page\', \'techrappy-seo\' ); ?\> \</a\> \</div\>
\</div\> \</div\> \</div\> \</div\>\<!\-- #gen-preview-panel \--\>
\</div\>\<!\-- .techrappy-gen-preview-col \--\> \</div\>\<!\--
.techrappy-gen-layout \--\> \</div\>\<!\-- .techrappy-generate-page
\--\> \`\`\` \-\-- \## Fichier 8 : \`assets/css/admin.css\` --- Styles
UI génération (ajout) \`\`\`css /\*
───────────────────────────────────────── UI GÉNÉRATION
───────────────────────────────────────── \*/ .techrappy-gen-layout {
display: grid; grid-template-columns: 380px 1fr; gap: 24px; align-items:
start; margin-top: 20px; } /\* ── Card ── \*/ .techrappy-card {
background: #fff; border: 1px solid var(\--tr-border); border-radius:
var(\--tr-radius); padding: 20px; } .techrappy-card-title { margin: 0 0
20px; font-size: 15px; padding-bottom: 12px; border-bottom: 1px solid
var(\--tr-border); } /\* ── Labels ── \*/ .techrappy-label { display:
block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color:
var(\--tr-text); } .techrappy-label .required { color:
var(\--tr-danger); margin-left: 2px; } /\* ── Radio options ── \*/
.techrappy-radio-group { display: flex; gap: 10px; }
.techrappy-radio-option { display: flex; align-items: center; gap: 8px;
padding: 10px 16px; border: 2px solid var(\--tr-border); border-radius:
var(\--tr-radius); cursor: pointer; transition: all 0.15s; font-size:
13px; flex: 1; justify-content: center; } .techrappy-radio-option
input\[type=\"radio\"\] { display: none; }
.techrappy-radio-option.is-active,
.techrappy-radio-option:has(input:checked) { border-color:
var(\--tr-primary); background: #f0f6fc; color: var(\--tr-primary);
font-weight: 600; } .techrappy-radio-icon { font-size: 18px; } /\* ──
Submit zone ── \*/ .techrappy-gen-submit { display: flex; align-items:
center; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px
solid var(\--tr-border); } .techrappy-step-label { font-size: 12px;
color: var(\--tr-text-light); font-style: italic; } /\* ── Placeholder
preview ── \*/ .techrappy-preview-placeholder { background: #fff;
border: 2px dashed var(\--tr-border); border-radius: var(\--tr-radius);
min-height: 400px; display: flex; align-items: center; justify-content:
center; } .techrappy-placeholder-inner { text-align: center; color:
var(\--tr-text-light); } .techrappy-placeholder-icon { font-size: 40px;
display: block; margin-bottom: 12px; } /\* ── Panel preview ── \*/
.techrappy-preview-panel { background: #fff; border: 1px solid
var(\--tr-border); border-radius: var(\--tr-radius); overflow: hidden; }
.techrappy-preview-header { display: flex; justify-content:
space-between; align-items: center; padding: 16px 20px; border-bottom:
1px solid var(\--tr-border); background: var(\--tr-bg); }
.techrappy-preview-header h2 { margin: 0; font-size: 15px; } /\* ── QA
badges ── \*/ .techrappy-qa-badges { display: flex; gap: 8px; }
.techrappy-qa-badge { padding: 3px 10px; border-radius: 12px; font-size:
12px; background: #f0f0f0; border: 1px solid var(\--tr-border); }
.techrappy-qa-badge.is-good { background: #d1fae5; border-color:
#34d399; color: #065f46; } .techrappy-qa-badge.is-warn { background:
#fef3c7; border-color: #f59e0b; color: #92400e; }
.techrappy-qa-badge.is-bad { background: #fee2e2; border-color: #f87171;
color: #991b1b; } /\* ── Meta preview ── \*/ .techrappy-meta-preview {
padding: 12px 20px; background: #f8f9fb; border-bottom: 1px solid
var(\--tr-border); } .techrappy-meta-row { display: flex; align-items:
baseline; gap: 8px; padding: 3px 0; font-size: 12px; }
.techrappy-meta-key { font-weight: 600; color: var(\--tr-text-light);
min-width: 80px; flex-shrink: 0; } .techrappy-meta-value { color:
var(\--tr-text); flex: 1; } .techrappy-meta-len { font-size: 10px;
color: var(\--tr-text-light); white-space: nowrap; }
.techrappy-meta-len.is-ok { color: var(\--tr-success); }
.techrappy-meta-len.is-warn { color: var(\--tr-warning); } /\* ── HTML
preview ── \*/ .techrappy-preview-html { padding: 20px; font-size: 14px;
line-height: 1.7; max-height: 500px; overflow-y: auto; color:
var(\--tr-text); } .techrappy-preview-html h1, .techrappy-preview-html
h2, .techrappy-preview-html h3 { margin-top: 1.2em; }
.techrappy-preview-html h2 { font-size: 16px; border-bottom: 1px solid
#eee; padding-bottom: 4px; } /\* ── Warnings list ── \*/
.techrappy-warnings-list { padding: 16px 20px; } .techrappy-warning-item
{ display: flex; gap: 8px; padding: 6px 0; font-size: 12px;
border-bottom: 1px solid #f0f0f0; color: var(\--tr-text); }
.techrappy-warning-item::before { content: \'⚠️\'; } /\* ── Actions
création ── \*/ .techrappy-creation-actions { padding: 16px 20px;
border-top: 1px solid var(\--tr-border); background: #fafafa; }
.techrappy-creation-info { display: flex; gap: 8px; margin-bottom: 12px;
} .techrappy-info-chip { display: inline-block; background: #e8f0fe;
color: var(\--tr-primary); padding: 2px 10px; border-radius: 12px;
font-size: 11px; font-weight: 500; } .techrappy-creation-buttons {
display: flex; gap: 10px; margin-bottom: 8px; } /\* ── Résultat création
── \*/ .techrappy-creation-result { padding: 16px 20px; }
.techrappy-creation-success { display: flex; align-items: flex-start;
gap: 12px; background: #d1fae5; border: 1px solid #34d399;
border-radius: var(\--tr-radius); padding: 14px 16px; }
.techrappy-creation-success .dashicons { color: #065f46; font-size:
24px; width: 24px; height: 24px; flex-shrink: 0; }
.techrappy-creation-success-text { flex: 1; } .techrappy-creation-links
{ display: flex; gap: 12px; margin-top: 6px; font-size: 13px; } \@media
screen and (max-width: 1200px) { .techrappy-gen-layout {
grid-template-columns: 1fr; } } \`\`\` \-\-- \## Fichier 9 :
\`assets/js/wizard.js\` --- JS complet UI génération \`\`\`javascript
/\*\* \* Techrappy SEO --- Wizard de génération \* \* Gère : \* -
Formulaire de génération (type contenu, champs conditionnels) \* - Appel
AJAX pipeline SEO \* - Prévisualisation du résultat (métas, HTML, JSON,
logs) \* - Création du post WordPress (brouillon / publié) \* - Onglets
résultat \* \* Dépend de : jQuery, TechrappySEO (localisé par
AdminAssets) \* \* \@package TechrappySEO \*/ /\* global TechrappySEO,
jQuery \*/ ( function ( \$, config ) { \'use strict\'; //
───────────────────────────────────────── // Config & état global //
───────────────────────────────────────── const AJAX_URL =
config.ajax_url; const NONCES = config.nonces; const I18N = config.i18n;
/\*\* \* Résultat SEO courant retourné par le pipeline. \* Stocké en
mémoire pour la création du post. \* \* \@type {Object\|null} \*/ let
currentSeoResult = null; // ───────────────────────────────────────── //
Initialisation // ───────────────────────────────────────── \$( document
).ready( function () { if ( ! \$( \'#techrappy-generate-page\' ).length
) { return; } initTypeToggle(); initGenerateForm();
initCreationButtons(); initResultTabs(); } ); //
───────────────────────────────────────── // Toggle type de contenu
(Page / Article) // ───────────────────────────────────────── function
initTypeToggle() { // Clic sur les radio options stylisées. \$( document
).on( \'click\', \'.techrappy-radio-option\', function () { const
\$label = \$( this ); const \$input = \$label.find(
\'input\[type=\"radio\"\]\' ); \$( \'.techrappy-radio-option\'
).removeClass( \'is-active\' ); \$label.addClass( \'is-active\' );
\$input.prop( \'checked\', true ); applyTypeVisibility( \$input.val() );
} ); // Initialiser la visibilité au chargement. applyTypeVisibility(
\$( \'input\[name=\"type_contenu\"\]:checked\' ).val() \|\| \'page_seo\'
); } /\*\* \* Affiche/masque les blocs conditionnels selon le type de
contenu. \* \* \@param {string} type \'page_seo\' \| \'article_seo\' \*/
function applyTypeVisibility( type ) { if ( \'page_seo\' === type ) {
\$( \'#block-page-options\' ).show(); \$( \'#block-article-options\'
).hide(); } else { \$( \'#block-page-options\' ).hide(); \$(
\'#block-article-options\' ).show(); } } //
───────────────────────────────────────── // Formulaire de génération //
───────────────────────────────────────── function initGenerateForm() {
\$( \'#techrappy-gen-form\' ).on( \'submit\', function ( e ) {
e.preventDefault(); runGeneration(); } ); } /\*\* \* Lance le pipeline
de génération SEO via AJAX. \*/ function runGeneration() { const motCle
= \$( \'#gen-mot-cle\' ).val().trim(); const type = \$(
\'input\[name=\"type_contenu\"\]:checked\' ).val() \|\| \'page_seo\';
const profession= \$( \'#gen-profession\' ).val().trim(); // Validation
côté client. if ( ! motCle ) { showNotice( I18N.error \|\| \'Le mot-clé
est obligatoire.\', \'error\' ); \$( \'#gen-mot-cle\' ).focus(); return;
} // Réinitialiser l\'état. currentSeoResult = null;
resetPreviewPanel(); const \$btn = \$( \'#btn-generate\' ); const
\$spinner = \$( \'#gen-spinner\' ); const \$label = \$(
\'#gen-step-label\' ); setButtonLoading( \$btn, true );
\$spinner.show(); \$label.text( I18N.loading \|\| \'Génération en
cours...\' ); // Simuler les labels d\'étapes pour l\'UX. const
stepLabels = \[ \'Analyse de l\\\'intention de recherche...\',
\'Génération du plan SEO...\', \'Rédaction de l\\\'introduction...\',
\'Rédaction des sections...\', \'Génération des métas...\', \'Génération
de la FAQ...\', \'Vérification qualité...\', \]; let stepIndex = 0;
const stepInterval = setInterval( function () { if ( stepIndex \<
stepLabels.length ) { \$label.text( stepLabels\[ stepIndex \] );
stepIndex++; } }, 4000 ); \$.post( AJAX_URL, { action:
\'techrappy_run_pipeline\', nonce: NONCES.generation, type_contenu:
type, mot_cle: motCle, profession: profession, } ) .done( function (
response ) { clearInterval( stepInterval ); if ( response.success ) {
currentSeoResult = response.data.result; renderPreviewPanel(
response.data ); } else { const msg = response.data?.message \|\|
I18N.error; // Cas particulier : dépassement de seuil de coût. if (
response.data?.code === \'cost_threshold_exceeded\' ) {
handleCostConfirmation( response.data, motCle, type, profession );
return; } showNotice( msg, \'error\' ); \$label.text( \'\' ); } } )
.fail( function ( xhr ) { clearInterval( stepInterval ); showNotice( (
I18N.error \|\| \'Erreur\' ) + \' (HTTP \' + xhr.status + \')\',
\'error\' ); \$label.text( \'\' ); } ) .always( function () {
setButtonLoading( \$btn, false ); \$spinner.hide(); } ); } /\*\* \*
Demande confirmation à l\'utilisateur si le coût estimé \* dépasse le
seuil configuré, puis relance avec confirmation. \* \* \@param {Object}
data Données de l\'erreur cost_threshold_exceeded. \* \@param {string}
motCle Mot-clé de la requête originale. \* \@param {string} type Type de
contenu. \* \@param {string} profession Profession. \*/ function
handleCostConfirmation( data, motCle, type, profession ) { const msg =
data.message \|\| \'Coût estimé élevé.\'; const confirmed =
window.confirm( msg + \'\\n\\nConfirmer pour continuer ?\' ); if ( !
confirmed ) { \$( \'#gen-step-label\' ).text( \'\' ); return; } //
Relancer avec confirmed_cost = true. const \$btn = \$( \'#btn-generate\'
); const \$spinner = \$( \'#gen-spinner\' ); setButtonLoading( \$btn,
true ); \$spinner.show(); \$.post( AJAX_URL, { action:
\'techrappy_run_pipeline\', nonce: NONCES.generation, type_contenu:
type, mot_cle: motCle, profession: profession, confirmed_cost: \'1\', }
) .done( function ( response ) { if ( response.success ) {
currentSeoResult = response.data.result; renderPreviewPanel(
response.data ); } else { showNotice( response.data?.message \|\|
I18N.error, \'error\' ); } } ) .fail( function () { showNotice(
I18N.error, \'error\' ); } ) .always( function () { setButtonLoading(
\$btn, false ); \$spinner.hide(); \$( \'#gen-step-label\' ).text( \'\'
); } ); } // ───────────────────────────────────────── // Rendu du panel
de prévisualisation // ───────────────────────────────────────── /\*\*
\* Réinitialise le panel de prévisualisation. \*/ function
resetPreviewPanel() { \$( \'#gen-preview-placeholder\' ).show(); \$(
\'#gen-preview-panel\' ).hide(); \$( \'#gen-creation-result\' ).hide();
\$( \'#gen-creation-actions\' ).show(); \$( \'#gen-qa-badges\' ).hide();
} /\*\* \* Affiche le panel de prévisualisation avec les données du
pipeline. \* \* \@param {Object} data Données retournées par l\'action
AJAX. \*/ function renderPreviewPanel( data ) { const result =
data.result \|\| {}; const qaScore = data.qa_score \|\| {}; const
warnings = data.warnings \|\| \[\]; // ── Métas ── renderMetaPreview(
result ); // ── Scores QA ── renderQaScores( qaScore ); // ── Contenu
HTML ── requestHtmlPreview( data.json_output ); // ── JSON brut ── \$(
\'#gen-preview-raw\' ).text( JSON.stringify( result, null, 2 ) ); // ──
Logs / warnings ── renderWarnings( warnings ); // ── Info chips ── const
postType = \$( \'input\[name=\"type_contenu\"\]:checked\' ).val() ===
\'article_seo\' ? \'Article\' : \'Page\'; \$(
\'#creation-post-type-label\' ).text( postType ); \$(
\'#creation-slug-label\' ).text( \'/\' + ( result.slug \|\| \'\' ) ); //
Afficher le panel. \$( \'#gen-preview-placeholder\' ).hide(); \$(
\'#gen-preview-panel\' ).fadeIn( 200 ); // Activer l\'onglet HTML par
défaut. activateResultTab( \'preview-html\' ); showNotice( \'Contenu
généré avec succès. Vérifiez la prévisualisation avant de créer le
post.\', \'success\' ); } /\*\* \* Remplit les champs de
méta-prévisualisation. \* \* \@param {Object} result Données du
GenerationResult. \*/ function renderMetaPreview( result ) { // Meta
title. const metaTitle = result.metatitle \|\| \'\'; \$(
\'#prev-metatitle\' ).text( metaTitle \|\| \'---\' );
updateLengthIndicator( \$( \'#prev-metatitle-len\' ), metaTitle.length,
55, 65, \'chars\' ); // Meta description. const metaDesc =
result.metadescription \|\| \'\'; \$( \'#prev-metadesc\' ).text(
metaDesc \|\| \'---\' ); updateLengthIndicator( \$(
\'#prev-metadesc-len\' ), metaDesc.length, 140, 160, \'chars\' ); //
Slug et H1. \$( \'#prev-slug\' ).text( result.slug \|\| \'---\' ); \$(
\'#prev-h1\' ).text( result.H1 \|\| result.h1 \|\| \'---\' ); } /\*\* \*
Met à jour l\'indicateur de longueur (OK / hors limites). \* \* \@param
{jQuery} \$el Élément cible. \* \@param {number} len Longueur actuelle.
\* \@param {number} min Minimum recommandé. \* \@param {number} max
Maximum recommandé. \* \@param {string} unit Unité affichée. \*/
function updateLengthIndicator( \$el, len, min, max, unit ) { if ( len
=== 0 ) { \$el.text( \'\' ).removeClass( \'is-ok is-warn\' ); return; }
const isOk = len \>= min && len \<= max; \$el .text( \'(\' + len + \'
\' + unit + \')\' ) .removeClass( \'is-ok is-warn\' ) .addClass( isOk ?
\'is-ok\' : \'is-warn\' ); } /\*\* \* Affiche les badges de scores QA.
\* \* \@param {Object} qa Données qa_score. \*/ function renderQaScores(
qa ) { if ( ! qa \|\| ( ! qa.score_seo && ! qa.score_humain ) ) { \$(
\'#gen-qa-badges\' ).hide(); return; } const scoreSeo = qa.score_seo
\|\| 0; const scoreHumain = qa.score_humain \|\| 0; \$( \'#qa-badge-seo
strong\' ).text( scoreSeo + \'/100\' ); \$( \'#qa-badge-humain strong\'
).text( scoreHumain + \'/100\' ); \$( \'#qa-badge-seo\' ) .removeClass(
\'is-good is-warn is-bad\' ) .addClass( getScoreClass( scoreSeo ) ); \$(
\'#qa-badge-humain\' ) .removeClass( \'is-good is-warn is-bad\' )
.addClass( getScoreClass( scoreHumain ) ); \$( \'#gen-qa-badges\'
).show(); } /\*\* \* Retourne la classe CSS selon le score. \* \*
\@param {number} score Score 0-100. \* \@returns {string} \*/ function
getScoreClass( score ) { if ( score \>= 70 ) return \'is-good\'; if (
score \>= 40 ) return \'is-warn\'; return \'is-bad\'; } /\*\* \* Demande
le HTML assemblé via AJAX preview et l\'injecte. \* \* \@param {string}
jsonOutput JSON string du GenerationResult. \*/ function
requestHtmlPreview( jsonOutput ) { if ( ! jsonOutput ) { \$(
\'#gen-preview-html\' ).html( \'\<p
style=\"color:#646970\"\>Prévisualisation HTML non disponible.\</p\>\'
); return; } const postType = \$(
\'input\[name=\"type_contenu\"\]:checked\' ).val() === \'article_seo\' ?
\'post\' : \'page\'; const linksPosition = \'after_last\'; \$.post(
AJAX_URL, { action: \'techrappy_preview_content\', nonce:
NONCES.generation, seo_json: jsonOutput, post_type: postType,
links_position: linksPosition, } ) .done( function ( response ) { if (
response.success ) { \$( \'#gen-preview-html\' ).html(
response.data.html \|\| \'\' ); } else { \$( \'#gen-preview-html\'
).html( \'\<p style=\"color:#d63638\"\>Erreur de prévisualisation : \' +
( response.data?.message \|\| \'\' ) + \'\</p\>\' ); } } ) .fail(
function () { \$( \'#gen-preview-html\' ).html( \'\<p
style=\"color:#d63638\"\>Erreur réseau lors de la
prévisualisation.\</p\>\' ); } ); } /\*\* \* Affiche les avertissements
dans le panel logs. \* \* \@param {string\[\]} warnings Liste des
avertissements. \*/ function renderWarnings( warnings ) { const \$list =
\$( \'#gen-preview-warnings\' ); const \$badge = \$(
\'#preview-warnings-count\' ); \$list.empty(); if ( ! warnings \|\|
warnings.length === 0 ) { \$list.html( \'\<p
style=\"color:#646970;padding:12px;\"\>Aucun avertissement.\</p\>\' );
\$badge.hide(); return; } warnings.forEach( function ( warning ) {
\$list.append( \$( \'\<div\>\' ) .addClass( \'techrappy-warning-item\' )
.text( warning ) ); } ); \$badge.text( warnings.length ).show(); } //
───────────────────────────────────────── // Création du post WordPress
// ───────────────────────────────────────── function
initCreationButtons() { \$( document ).on( \'click\',
\'#btn-create-draft, #btn-create-publish\', function () { const
forceStatus = \$( this ).data( \'status\' ); createWordPressPost(
forceStatus ); } ); } /\*\* \* Crée le post WordPress depuis le résultat
SEO courant. \* \* \@param {string} forceStatus \'draft\' \| \'publish\'
\*/ function createWordPressPost( forceStatus ) { if ( !
currentSeoResult ) { showNotice( \'Aucun contenu généré. Lancez
d\\\'abord la génération.\', \'error\' ); return; } // Confirmation si
publication directe. if ( \'publish\' === forceStatus ) { const
confirmed = window.confirm( \'Publier directement ce contenu sur votre
site ?\\n\\n\' + \'Vous pourrez l\\\'éditer après création.\' ); if ( !
confirmed ) { return; } } const \$btnDraft = \$( \'#btn-create-draft\'
); const \$btnPublish = \$( \'#btn-create-publish\' ); const \$active =
\'draft\' === forceStatus ? \$btnDraft : \$btnPublish; setButtonLoading(
\$btnDraft, true ); setButtonLoading( \$btnPublish, true ); // Récupérer
les paramètres WP du formulaire. const postType = \$(
\'input\[name=\"type_contenu\"\]:checked\' ).val() === \'article_seo\' ?
\'post\' : \'page\'; const postData = { action:
\'techrappy_create_post\', nonce: NONCES.generation, seo_json:
JSON.stringify( currentSeoResult ), post_type: postType, post_status:
forceStatus, post_parent: \$( \'#gen-post-parent\' ).val() \|\| \'0\',
category_ids: \$( \'#gen-category\' ).val() \|\| \'\', tag_names: \$(
\'#gen-tags\' ).val() \|\| \'\', links_position: \'after_last\', };
\$.post( AJAX_URL, postData ) .done( function ( response ) { if (
response.success ) { renderCreationSuccess( response.data, forceStatus
); } else { showNotice( response.data?.message \|\| I18N.error,
\'error\' ); } } ) .fail( function ( xhr ) { showNotice( I18N.error + \'
(HTTP \' + xhr.status + \')\', \'error\' ); } ) .always( function () {
setButtonLoading( \$btnDraft, false ); setButtonLoading( \$btnPublish,
false ); } ); } /\*\* \* Affiche le résultat d\'une création réussie. \*
\* \@param {Object} data Données de PostCreationResult::to_array(). \*
\@param {string} postStatus Statut demandé (\'draft\' \| \'publish\').
\*/ function renderCreationSuccess( data, postStatus ) { const
statusLabel = \'publish\' === postStatus ? \'publié\' : \'créé en
brouillon\'; const typeLabel = \'post\' === data.post_type ? \'Article\'
: \'Page\'; // Titre du résultat. \$( \'#creation-result-title\' ).text(
typeLabel + \' \' + statusLabel + \' avec succès !\' ); // Liens. if (
data.edit_url ) { \$( \'#creation-link-edit\' ).attr( \'href\',
data.edit_url ).show(); } else { \$( \'#creation-link-edit\' ).hide(); }
if ( data.permalink ) { \$( \'#creation-link-view\' ).attr( \'href\',
data.permalink ).show(); } else { \$( \'#creation-link-view\' ).hide();
} // Afficher le résultat, masquer les boutons. \$(
\'#gen-creation-actions\' ).hide(); \$( \'#gen-creation-result\'
).fadeIn( 300 ); // Avertissements slug modifié. if ( data.warnings &&
data.warnings.length \> 0 ) { showNotice( \'⚠️ \' + data.warnings.join(
\' \| \' ), \'warning\' ); } else { showNotice( typeLabel + \' \' +
statusLabel + \' : \' + ( data.permalink \|\| \'\' ), \'success\' ); }
// Réinitialiser le résultat courant pour éviter une double création.
currentSeoResult = null; } // ─────────────────────────────────────────
// Onglets résultat (réutilise le pattern du Prompt Studio) //
───────────────────────────────────────── function initResultTabs() {
\$( document ).on( \'click\', \'.techrappy-result-tab-btn\', function ()
{ const tab = \$( this ).data( \'result-tab\' ); if ( tab ) {
activateResultTab( tab ); } } ); } /\*\* \* Active un onglet résultat.
\* \* \@param {string} tab Identifiant de l\'onglet. \*/ function
activateResultTab( tab ) { \$( \'.techrappy-result-tab-btn\' )
.removeClass( \'active\' ) .filter( \'\[data-result-tab=\"\' + tab +
\'\"\]\' ) .addClass( \'active\' ); \$(
\'.techrappy-result-tab-content\' ).hide(); \$( \'#result-tab-\' + tab
).show(); } // ───────────────────────────────────────── // Helpers UI
(partagés avec prompt-studio) //
───────────────────────────────────────── /\*\* \* Affiche une notice en
haut de la page. \* \* \@param {string} message Message. \* \@param
{string} type \'success\' \| \'error\' \| \'warning\' \*/ function
showNotice( message, type ) { type = type \|\| \'success\'; const
\$notice = \$( \'#techrappy-gen-notice\' ); \$notice .removeClass(
\'is-success is-error is-warning\' ) .addClass( \'is-\' + type ) .text(
message ) .fadeIn( 200 ); if ( \'error\' !== type ) { setTimeout(
function () { \$notice.fadeOut( 400 ); }, 5000 ); } // Scroll vers la
notice. \$( \'html, body\' ).animate( { scrollTop: (
\$notice.offset()?.top ?? 0 ) - 40 }, 200 ); } /\*\* \* Active /
désactive l\'état chargement d\'un bouton. \* \* \@param {jQuery} \$btn
Bouton jQuery. \* \@param {boolean} loading True pour activer. \*/
function setButtonLoading( \$btn, loading ) { if ( loading ) { \$btn
.prop( \'disabled\', true ) .data( \'original-text\', \$btn.text() )
.text( I18N.loading \|\| \'Chargement...\' ); } else { \$btn .prop(
\'disabled\', false ) .text( \$btn.data( \'original-text\' ) \|\|
\$btn.text() ); } } } )( jQuery, window.TechrappySEO \|\| {} ); \`\`\`
\-\-- \## Fichier 10 : Enregistrement dans \`Plugin.php\` (delta)
\`\`\`php // Dans includes/Core/Plugin.php --- méthode
define_ajax_hooks() // Ajouter après le bloc AjaxGeneration : \$ajax_wp
= new \\TechrappySEO\\Admin\\Ajax\\AjaxWordPress();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_create_post\',
\$ajax_wp, \'handle_create_post\' ); \$this-\>loader-\>add_action(
\'wp_ajax_techrappy_preview_content\', \$ajax_wp,
\'handle_preview_content\' ); \`\`\` \-\-- \## Fichier 11 :
\`AdminAssets.php\` --- Ajout nonce \`generation\` (delta) \`\`\`php //
Dans includes/Admin/AdminAssets.php --- méthode get_localized_data() //
Le nonce \'generation\' doit couvrir les nouvelles actions WP. //
Vérifier qu\'il est bien présent (déjà ajouté en V1) : \'generation\'
=\> wp_create_nonce( \'techrappy_seo_generation\' ), \`\`\` \-\-- \##
Fichier 12 : \`includes/Admin/AdminMenu.php\` --- Mise à jour hook
wizard (delta) \`\`\`php // Dans includes/Admin/AdminMenu.php // La
méthode render_wizard_page() existante charge déjà : // require_once
TECHRAPPY_SEO_PATH . \'views/admin/wizard/step-1-mode.php\'; // La vue a
été remplacée par le formulaire complet --- aucun changement de hook
nécessaire. \`\`\` \-\-- \## Récapitulatif V5 --- Intégration WordPress
\`\`\` includes/WordPress/ ├── PostCreationSettings.php ✅ DTO
paramètres WP │ post_type, post_status, post_parent, │ category_ids,
tag_names, links_position │ ├── ContentAssembler.php ✅ Assemblage HTML
post_content │ Règles de positionnement liens internes : │ after_last →
après dernière section (défaut) │ after_second → après 2e section (pages
longues) │ wp_kses() sur tout le HTML IA │ ├── SlugManager.php ✅ Slug
unique + anti-collision │ generate_unique() → suffixe -2, -3... │
slug_exists() → vérification DB │ ├── PostCreator.php ✅ Service
principal de création │ create_post_from_result() ← méthode d\'instance
│ create_post_from_seo_json() ← fonction statique demandée │ +
métadonnées plugin sur chaque post créé │ └── PostCreationResult.php ✅
DTO résultat création success/error, post_id, permalink, edit_url,
warnings, logs includes/Admin/Ajax/ └── AjaxWordPress.php ✅ 2 handlers
AJAX sécurisés handle_create_post() → crée le post WP
handle_preview_content() → HTML sans créer views/admin/wizard/ └──
step-1-mode.php ✅ UI complète : formulaire génération +
prévisualisation + boutons création (brouillon / publier)
assets/js/wizard.js ✅ JS complet : toggle type, pipeline AJAX, preview,
métas + QA badges, création WP, résultat avec liens edit/view \`\`\`
\*\*Flux complet :\*\* \`\`\` \[Form submit\] → runGeneration() →
wp_ajax_techrappy_run_pipeline → GenerationEngine::run() →
renderPreviewPanel( result ) ├── renderMetaPreview() → métatitle +
metadesc + slug + H1 ├── renderQaScores() → badges SEO/humain ├──
requestHtmlPreview() → wp_ajax_techrappy_preview_content │ →
ContentAssembler::assemble() → HTML sécurisé wp_kses └──
renderWarnings() \[Bouton \"Créer en brouillon\" / \"Publier\"\] →
createWordPressPost( status ) → wp_ajax_techrappy_create_post →
PostCreator::create_post_from_seo_json( seo_json, settings ) ├──
SlugManager::generate_unique() → slug sans collision ├──
ContentAssembler::assemble() → post_content HTML ├── wp_insert_post()
├── set_categories() + set_tags() └── store_plugin_meta() →
\_techrappy_seo_generated etc. → renderCreationSuccess() ├── Lien
\"Éditer dans WordPress\" └── Lien \"Voir la page\" \`\`\`
