\# Techrappy SEO --- Module Intégration Divi Builder \## Architecture
des fichiers livrés \`\`\` techrappy-seo/ ├── modules/ │ └── divi/ │ ├──
DiviTemplateService.php \# Service principal │ ├── DiviTokenReplacer.php
\# Remplacement des tokens │ ├── DiviRepeatableSection.php \# Gestion
sections répétables │ └── DiviTemplateAnalyzer.php \# Analyse des tokens
├── admin/ │ └── partials/ │ └── divi-template-selector.php \# UI
sélecteur template ├── assets/ │ └── js/ │ └── divi-template-admin.js \#
JS admin (AJAX) └── docs/ └── divi-template-guide.md \# Documentation
technique \`\`\` \-\-- \## 1. DiviTemplateAnalyzer.php \`\`\`php \<?php
/\*\* \* DiviTemplateAnalyzer \* \* Analyse un template Divi pour
détecter tous les tokens Techrappy. \* Les tokens sont encadrés par la
syntaxe {{TOKEN_NAME}}. \* \* \@package TechrappySEO\\Modules\\Divi \*/
namespace TechrappySEO\\Modules\\Divi; defined( \'ABSPATH\' ) \|\| exit;
class DiviTemplateAnalyzer { /\*\* \* Regex pour détecter les tokens
{{TOKEN_NAME}}. \* Autorise lettres, chiffres, underscore, point (ex:
{{sections.0.title}}). \*/ const TOKEN_REGEX =
\'/\\{\\{(\[A-Za-z0-9\_.\]+)\\}\\}/\'; /\*\* \* Clé meta Divi contenant
les données builder (JSON ou sérialisé). \*/ const DIVI_META_KEY =
\'\_et_pb_page_layout\'; /\*\* \* Clés meta Divi à analyser pour les
tokens. \* \* \@var array \*/ private static array \$divi_meta_keys = \[
\'\_et_pb_use_builder\', \'\_et_builder_version\',
\'\_et_pb_old_content\', \]; /\*\* \* Analyse un post Divi et retourne
la liste des tokens trouvés. \* \* \@param int \$post_id ID du post
template. \* \@return array { \* \@type string\[\] \$tokens Liste unique
des tokens détectés. \* \@type array \$token_map Token =\> liste des
sources (post_content, meta_key, etc.) \* \@type bool \$has_repeatable
True si un bloc répétable est détecté. \* } \*/ public static function
analyze( int \$post_id ): array { \$result = \[ \'tokens\' =\> \[\],
\'token_map\' =\> \[\], \'has_repeatable\' =\> false, \'errors\' =\>
\[\], \]; \$post = get_post( \$post_id ); if ( ! \$post ) {
\$result\[\'errors\'\]\[\] = sprintf( \'Post introuvable : ID %d\',
\$post_id ); return \$result; } // \-\-- Analyse du post_content
(contient les shortcodes Divi) \-\-- \$sources = \[ \'post_content\' =\>
\$post-\>post_content, \'post_title\' =\> \$post-\>post_title,
\'post_excerpt\' =\> \$post-\>post_excerpt, \]; // \-\-- Analyse des
metas pertinentes \-\-- \$all_metas = get_post_meta( \$post_id );
foreach ( \$all_metas as \$meta_key =\> \$meta_values ) { // On analyse
toutes les metas qui peuvent contenir du contenu Divi foreach (
\$meta_values as \$meta_value ) { if ( is_string( \$meta_value ) &&
strlen( \$meta_value ) \> 0 ) { \$sources\[ \'meta:\' . \$meta_key \] =
\$meta_value; } } } // \-\-- Détection des tokens dans chaque source
\-\-- foreach ( \$sources as \$source_name =\> \$content ) { \$found =
self::extract_tokens_from_string( \$content ); foreach ( \$found as
\$token ) { \$result\[\'tokens\'\]\[\] = \$token; if ( ! isset(
\$result\[\'token_map\'\]\[ \$token \] ) ) { \$result\[\'token_map\'\]\[
\$token \] = \[\]; } \$result\[\'token_map\'\]\[ \$token \]\[\] =
\$source_name; } } // \-\-- Dédoublonnage \-\-- \$result\[\'tokens\'\] =
array_values( array_unique( \$result\[\'tokens\'\] ) ); // \-\--
Détection bloc répétable \-\-- \$result\[\'has_repeatable\'\] =
self::detect_repeatable_block( \$post-\>post_content ); return \$result;
} /\*\* \* Extrait tous les tokens {{\...}} d\'une chaîne. \* \* \@param
string \$content \* \@return string\[\] \*/ public static function
extract_tokens_from_string( string \$content ): array { \$matches =
\[\]; preg_match_all( self::TOKEN_REGEX, \$content, \$matches ); return
\$matches\[1\] ?? \[\]; } /\*\* \* Détecte la présence d\'un bloc
répétable via le marqueur HTML. \* Marqueur : \<!\--
TECHRAPPY_REPEAT_START \--\> \... \<!\-- TECHRAPPY_REPEAT_END \--\> \*
\* \@param string \$content \* \@return bool \*/ public static function
detect_repeatable_block( string \$content ): bool { return str_contains(
\$content, \'\<!\-- TECHRAPPY_REPEAT_START \--\>\' ) && str_contains(
\$content, \'\<!\-- TECHRAPPY_REPEAT_END \--\>\' ); } } \`\`\` \-\-- \##
2. DiviTokenReplacer.php \`\`\`php \<?php /\*\* \* DiviTokenReplacer \*
\* Remplace les tokens {{TOKEN_NAME}} dans le contenu d\'un post Divi \*
par les valeurs issues du JSON SEO. \* \* Mapping officiel des tokens :
\* \@see /docs/divi-template-guide.md \* \* \@package
TechrappySEO\\Modules\\Divi \*/ namespace TechrappySEO\\Modules\\Divi;
defined( \'ABSPATH\' ) \|\| exit; class DiviTokenReplacer { /\*\* \*
Mapping token =\> clé dans le tableau \$seo_data. \* La clé peut
utiliser la notation pointée pour accéder aux sous-tableaux. \* Ex:
\"sections.0.title\" =\> \$seo_data\[\'sections\'\]\[0\]\[\'title\'\] \*
\* \@var array\<string, string\> \*/ private array \$token_mapping = \[
// \-\-- Contenu principal \-\-- \'H1\' =\> \'H1\', \'INTRO_HTML\' =\>
\'intro_html\', \'FAQ_BLOCK\' =\> \'faq_block_html\', \'CTA_BLOCK\' =\>
\'cta_block_html\', // \-\-- SEO \-\-- \'METATITLE\' =\> \'metatitle\',
\'METADESCRIPTION\' =\> \'metadescription\', \'SLUG\' =\> \'slug\', //
\-\-- Sections répétables (remplacées par DiviRepeatableSection) \-\--
// \'SECTION_TITLE\' =\> \'sections.N.title\', // \'SECTION_BODY\' =\>
\'sections.N.body\', \]; /\*\* \* Données SEO issues du JSON généré par
le moteur SEO. \* \* \@var array \*/ private array \$seo_data; /\*\* \*
Log des tokens manquants ou en erreur. \* \* \@var array \*/ private
array \$error_log = \[\]; /\*\* \* \@param array \$seo_data Tableau SEO
issu du JSON standardisé. \*/ public function \_\_construct( array
\$seo_data ) { \$this-\>seo_data = \$seo_data; } /\*\* \* Remplace tous
les tokens dans une chaîne de contenu. \* \* \@param string \$content
Contenu brut avec tokens {{\...}}. \* \@return string Contenu avec
tokens remplacés. \*/ public function replace( string \$content ):
string { // Regex capture tous les tokens {{TOKEN}} return
preg_replace_callback( DiviTemplateAnalyzer::TOKEN_REGEX, function (
array \$matches ) { \$token = \$matches\[1\]; // Nom du token sans
accolades \$value = \$this-\>resolve_token( \$token ); // Si null : on
log et on retourne le token original (non-destructif) if ( null ===
\$value ) { \$this-\>log_missing_token( \$token ); return
\$matches\[0\]; // Retourne {{TOKEN}} intact } return \$value; },
\$content ); } /\*\* \* Résout la valeur d\'un token depuis \$seo_data.
\* Supporte la notation pointée : \"sections.0.title\" \* \* \@param
string \$token Nom du token (ex: H1, SLUG, sections.0.title). \*
\@return string\|null Valeur résolue ou null si introuvable. \*/ private
function resolve_token( string \$token ): ?string { // 1. Cherche dans
le mapping officiel if ( isset( \$this-\>token_mapping\[ \$token \] ) )
{ \$data_key = \$this-\>token_mapping\[ \$token \]; return
\$this-\>get_nested_value( \$this-\>seo_data, \$data_key ); } // 2.
Tentative de résolution directe (notation pointée) // Ex:
{{sections.0.title}} non mappé explicitement \$direct =
\$this-\>get_nested_value( \$this-\>seo_data, \$token ); if ( null !==
\$direct ) { return \$direct; } return null; } /\*\* \* Récupère une
valeur imbriquée via notation pointée. \* \* \@param array \$data
Tableau de données. \* \@param string \$path Chemin pointé (ex:
\"sections.0.title\"). \* \@return string\|null \*/ private function
get_nested_value( array \$data, string \$path ): ?string { \$keys =
explode( \'.\', \$path ); \$current = \$data; foreach ( \$keys as \$key
) { if ( is_array( \$current ) && array_key_exists( \$key, \$current ) )
{ \$current = \$current\[ \$key \]; } else { return null; } } //
Conversion en string si scalaire if ( is_scalar( \$current ) ) { return
(string) \$current; } // Si tableau (ex: faq_block_html peut être un
tableau HTML+JSON-LD) if ( is_array( \$current ) && isset(
\$current\[\'html\'\] ) ) { return (string) \$current\[\'html\'\]; }
return null; } /\*\* \* Log un token manquant. \* Utilise error_log pour
ne pas planter. \* \* \@param string \$token \*/ private function
log_missing_token( string \$token ): void { \$message = sprintf(
\'\[TechrappySEO\]\[DiviTokenReplacer\] Token manquant dans SEO data :
{{%s}}\', \$token ); \$this-\>error_log\[\] = \$message; error_log(
\$message ); } /\*\* \* Retourne le log des erreurs pour affichage
admin. \* \* \@return array \*/ public function get_error_log(): array {
return \$this-\>error_log; } /\*\* \* Remplace le contenu sur toutes les
metas pertinentes d\'un post. \* Retourne un tableau \[ meta_key =\>
new_value \] à mettre à jour. \* \* \@param int \$post_id \* \@param
string \$new_content Post content déjà remplacé. \* \@return array Metas
à mettre à jour. \*/ public function build_meta_replacements( int
\$post_id, string \$new_content ): array { \$updates = \[\]; \$all_meta
= get_post_meta( \$post_id ); foreach ( \$all_meta as \$meta_key =\>
\$meta_values ) { foreach ( \$meta_values as \$meta_value ) { if ( !
is_string( \$meta_value ) ) { continue; } // Applique le remplacement
uniquement si la valeur contient des tokens if ( str_contains(
\$meta_value, \'{{\' ) ) { \$replaced = \$this-\>replace( \$meta_value
); if ( \$replaced !== \$meta_value ) { \$updates\[ \$meta_key \] =
\$replaced; } } } } return \$updates; } } \`\`\` \-\-- \## 3.
DiviRepeatableSection.php \`\`\`php \<?php /\*\* \*
DiviRepeatableSection \* \* Gère la duplication et la suppression des
sections répétables \* dans un template Divi. \* \* Marqueurs HTML
attendus dans le template : \* \<!\-- TECHRAPPY_REPEAT_START \--\> \*
\... contenu avec tokens {{SECTION_TITLE}}, {{SECTION_BODY}} \... \*
\<!\-- TECHRAPPY_REPEAT_END \--\> \* \* \@package
TechrappySEO\\Modules\\Divi \*/ namespace TechrappySEO\\Modules\\Divi;
defined( \'ABSPATH\' ) \|\| exit; class DiviRepeatableSection { /\*\* \*
Marqueur de début de bloc répétable. \*/ const MARKER_START = \'\<!\--
TECHRAPPY_REPEAT_START \--\>\'; /\*\* \* Marqueur de fin de bloc
répétable. \*/ const MARKER_END = \'\<!\-- TECHRAPPY_REPEAT_END \--\>\';
/\*\* \* Tokens disponibles dans une section répétable. \* Mapping token
=\> clé dans sections\[i\]. \* \* \@var array\<string, string\> \*/
private array \$section_token_map = \[ \'SECTION_TITLE\' =\> \'title\',
\'SECTION_BODY\' =\> \'body\', \'SECTION_INDEX\' =\> \'index\', // Index
numérique de la section (1-based) \]; /\*\* \* Données SEO (doit
contenir une clé \"sections\" de type array). \* \* \@var array \*/
private array \$seo_data; /\*\* \* Log des erreurs. \* \* \@var array
\*/ private array \$error_log = \[\]; /\*\* \* \@param array \$seo_data
Tableau SEO standardisé. \*/ public function \_\_construct( array
\$seo_data ) { \$this-\>seo_data = \$seo_data; } /\*\* \* Traite le
contenu d\'un post Divi : \* - Détecte le bloc répétable \* - Duplique
selon le nombre de sections dans seo_data \* - Remplace les tokens dans
chaque section \* - Supprime les blocs en trop \* \* \@param string
\$content Post content avec marqueurs TECHRAPPY_REPEAT. \* \@return
string Contenu traité. \*/ public function process( string \$content ):
string { // Vérifie la présence des marqueurs if ( !
DiviTemplateAnalyzer::detect_repeatable_block( \$content ) ) { return
\$content; // Pas de bloc répétable, retour intact } // Extrait le
template de section \$section_template =
\$this-\>extract_section_template( \$content ); if ( null ===
\$section_template ) { \$this-\>log_error( \'Impossible d\\\'extraire le
template de section entre les marqueurs.\' ); return \$content; } //
Récupère les sections depuis seo_data \$sections =
\$this-\>get_sections(); if ( empty( \$sections ) ) {
\$this-\>log_error( \'Aucune section trouvée dans
seo_data\[\"sections\"\]. Le bloc répétable sera supprimé.\' ); //
Supprime le bloc répétable entier si pas de sections return
\$this-\>remove_repeatable_block( \$content ); } // Génère le contenu
répété \$repeated_content = \$this-\>generate_repeated_blocks(
\$section_template, \$sections ); // Remplace le bloc marqué dans le
contenu original return \$this-\>inject_repeated_blocks( \$content,
\$repeated_content ); } /\*\* \* Extrait le contenu entre les marqueurs
START et END. \* \* \@param string \$content \* \@return string\|null
\*/ private function extract_section_template( string \$content ):
?string { \$start_pos = strpos( \$content, self::MARKER_START );
\$end_pos = strpos( \$content, self::MARKER_END ); if ( false ===
\$start_pos \|\| false === \$end_pos ) { return null; } \$offset =
\$start_pos + strlen( self::MARKER_START ); \$template_content = substr(
\$content, \$offset, \$end_pos - \$offset ); return trim(
\$template_content ); } /\*\* \* Génère les blocs répétés en remplaçant
les tokens pour chaque section. \* \* \@param string \$template Template
d\'une section avec tokens. \* \@param array \$sections Tableau de
sections \[ \[\'title\' =\> \..., \'body\' =\> \...\], \... \] \*
\@return string Tous les blocs concaténés. \*/ private function
generate_repeated_blocks( string \$template, array \$sections ): string
{ \$output = \'\'; foreach ( \$sections as \$index =\> \$section ) { if
( ! is_array( \$section ) ) { \$this-\>log_error( sprintf(
\'Section\[%d\] invalide (non-array). Ignorée.\', \$index ) ); continue;
} \$block = \$template; // Remplacement des tokens de section foreach (
\$this-\>section_token_map as \$token =\> \$section_key ) { \$value =
null; if ( \'index\' === \$section_key ) { // Index 1-based pour
affichage utilisateur \$value = (string) ( \$index + 1 ); } elseif (
isset( \$section\[ \$section_key \] ) ) { \$value = (string) \$section\[
\$section_key \]; } if ( null !== \$value ) { \$block = str_replace(
\'{{\' . \$token . \'}}\', \$value, \$block ); } else {
\$this-\>log_error( sprintf( \'Token {{%s}} manquant dans section\[%d\].
Token conservé.\', \$token, \$index ) ); } } // Séparateur entre les
blocs (commentaire HTML invisible) \$output .= \"\\n\" . \$block .
\"\\n\"; } return \$output; } /\*\* \* Remplace le bloc marqué
(START\...END inclus les marqueurs) par le contenu généré. \* \* \@param
string \$content Contenu original. \* \@param string \$repeated_content
Contenu généré à injecter. \* \@return string \*/ private function
inject_repeated_blocks( string \$content, string \$repeated_content ):
string { \$start_marker = self::MARKER_START; \$end_marker =
self::MARKER_END; // Remplace tout le bloc (marqueurs inclus) par le
contenu répété \$pattern = \'/\' . preg_quote( \$start_marker, \'/\' ) .
\'.\*?\' . preg_quote( \$end_marker, \'/\' ) . \'/s\'; \$replaced =
preg_replace( \$pattern, \$repeated_content, \$content ); if ( null ===
\$replaced ) { \$this-\>log_error( \'Échec de preg_replace lors de
l\\\'injection des blocs répétables.\' ); return \$content; } return
\$replaced; } /\*\* \* Supprime entièrement le bloc répétable (cas : 0
sections). \* \* \@param string \$content \* \@return string \*/ private
function remove_repeatable_block( string \$content ): string { return
\$this-\>inject_repeated_blocks( \$content, \'\' ); } /\*\* \* Récupère
le tableau des sections depuis seo_data. \* Valide chaque entrée. \* \*
\@return array \*/ private function get_sections(): array { if ( !
isset( \$this-\>seo_data\[\'sections\'\] ) \|\| ! is_array(
\$this-\>seo_data\[\'sections\'\] ) ) { return \[\]; } return
array_values( \$this-\>seo_data\[\'sections\'\] ); } /\*\* \* Log une
erreur sans planter. \* \* \@param string \$message \*/ private function
log_error( string \$message ): void { \$full_message =
\'\[TechrappySEO\]\[DiviRepeatableSection\] \' . \$message;
\$this-\>error_log\[\] = \$full_message; error_log( \$full_message ); }
/\*\* \* \@return array \*/ public function get_error_log(): array {
return \$this-\>error_log; } } \`\`\` \-\-- \## 4.
DiviTemplateService.php \`\`\`php \<?php /\*\* \* DiviTemplateService \*
\* Service principal du module Divi. \* Orchestre : analyse,
duplication, injection des tokens, gestion des répétables. \* \*
\@package TechrappySEO\\Modules\\Divi \*/ namespace
TechrappySEO\\Modules\\Divi; defined( \'ABSPATH\' ) \|\| exit; class
DiviTemplateService { /\*\* \* Post types compatibles Divi autorisés
comme templates. \* \* \@var string\[\] \*/ private array
\$allowed_post_types = \[ \'page\', \'post\', \'et_pb_layout\' \]; /\*\*
\* Log global des erreurs/warnings. \* \* \@var array \*/ private array
\$service_log = \[\]; //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// ANALYSE //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Analyse un template Divi et retourne les tokens détectés +
infos. \* Point d\'entrée pour l\'action AJAX \"analyser template\". \*
\* \@param int \$template_post_id ID du post template Divi. \* \@return
array { \* \@type bool \$success \* \@type string\[\] \$tokens Tokens
détectés. \* \@type array \$token_map Token =\> sources. \* \@type bool
\$has_repeatable Présence de bloc répétable. \* \@type string
\$post_title Titre du template. \* \@type string\[\] \$errors Erreurs
éventuelles. \* } \*/ public function analyze_template( int
\$template_post_id ): array { // Validation de sécurité : le post existe
et est du bon type \$post = get_post( \$template_post_id ); if ( !
\$post \|\| ! in_array( \$post-\>post_type, \$this-\>allowed_post_types,
true ) ) { return \[ \'success\' =\> false, \'errors\' =\> \[ sprintf(
\'Post ID %d invalide ou type non autorisé.\', \$template_post_id ) \],
\]; } // Vérification Divi activé sur ce post \$uses_divi =
get_post_meta( \$template_post_id, \'\_et_pb_use_builder\', true ); if (
\'on\' !== \$uses_divi ) { \$this-\>log_warning( sprintf( \'Post ID %d
n\\\'utilise pas le Divi Builder (\_et_pb_use_builder != \"on\").\',
\$template_post_id ) ); } \$analysis = DiviTemplateAnalyzer::analyze(
\$template_post_id ); return \[ \'success\' =\> true, \'post_title\' =\>
esc_html( \$post-\>post_title ), \'post_type\' =\> \$post-\>post_type,
\'tokens\' =\> \$analysis\[\'tokens\'\], \'token_map\' =\>
\$analysis\[\'token_map\'\], \'has_repeatable\' =\>
\$analysis\[\'has_repeatable\'\], \'errors\' =\>
\$analysis\[\'errors\'\], \]; } /\*\* \* Retourne la liste des templates
Divi disponibles pour le sélecteur UI. \* \* \@return array Liste de \[
\'id\' =\> int, \'title\' =\> string, \'type\' =\> string \] \*/ public
function get_available_templates(): array { \$templates = \[\]; foreach
( \$this-\>allowed_post_types as \$post_type ) { \$posts = get_posts( \[
\'post_type\' =\> \$post_type, \'post_status\' =\> \[ \'publish\',
\'draft\', \'private\' \], \'posts_per_page\' =\> 100, \'meta_query\'
=\> \[ \[ \'key\' =\> \'\_et_pb_use_builder\', \'value\' =\> \'on\', \],
\], \'orderby\' =\> \'title\', \'order\' =\> \'ASC\', \] ); foreach (
\$posts as \$post ) { \$templates\[\] = \[ \'id\' =\> \$post-\>ID,
\'title\' =\> \$post-\>post_title, \'type\' =\> \$post-\>post_type, \];
} } return \$templates; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// DUPLICATION //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Duplique un post template Divi sans injection (copie brute). \*
Retourne l\'ID du nouveau post (statut \"draft\"). \* \* \@param int
\$source_post_id ID du template source. \* \@param string \$new_title
Titre du nouveau post. \* \@return int\|\\WP_Error ID du nouveau post ou
WP_Error. \*/ public function duplicate_template( int \$source_post_id,
string \$new_title = \'\' ): int\|\\WP_Error { \$source = get_post(
\$source_post_id ); if ( ! \$source ) { return new \\WP_Error(
\'techrappy_divi_source_not_found\', sprintf( \'Post source introuvable
: ID %d\', \$source_post_id ) ); } // Prépare les données du nouveau
post \$new_post_data = \[ \'post_title\' =\> ! empty( \$new_title ) ?
sanitize_text_field( \$new_title ) : \$source-\>post_title . \' ---
Copie\', \'post_content\' =\> \$source-\>post_content, \'post_excerpt\'
=\> \$source-\>post_excerpt, \'post_status\' =\> \'draft\',
\'post_type\' =\> \$source-\>post_type, \'post_author\' =\>
get_current_user_id(), \'post_parent\' =\> 0, // Pas d\'héritage de
parent \]; // Création du post dupliqué \$new_post_id = wp_insert_post(
\$new_post_data, true ); if ( is_wp_error( \$new_post_id ) ) { return
\$new_post_id; } // Copie les metas Divi nécessaires
\$this-\>copy_divi_metas( \$source_post_id, \$new_post_id ); // Copie
les taxonomies \$this-\>copy_taxonomies( \$source_post_id, \$new_post_id
); \$this-\>log_info( sprintf( \'Template dupliqué : source=%d →
nouveau=%d\', \$source_post_id, \$new_post_id ) ); return \$new_post_id;
} /\*\* \* Copie toutes les metas Divi du post source vers le post
destination. \* Exclut les metas WordPress système (\_edit_lock,
\_edit_last, etc.). \* \* \@param int \$source_id ID source. \* \@param
int \$destination_id ID destination. \*/ private function
copy_divi_metas( int \$source_id, int \$destination_id ): void { //
Metas système à exclure \$excluded_meta_keys = \[ \'\_edit_lock\',
\'\_edit_last\', \'\_wp_trash_meta_status\', \'\_wp_trash_meta_time\',
\'\_wp_desired_post_slug\', \]; \$all_metas = get_post_meta( \$source_id
); foreach ( \$all_metas as \$meta_key =\> \$meta_values ) { if (
in_array( \$meta_key, \$excluded_meta_keys, true ) ) { continue; } //
Supprime d\'abord les metas existantes sur la destination
delete_post_meta( \$destination_id, \$meta_key ); foreach (
\$meta_values as \$meta_value ) { // Désérialise si nécessaire
(certaines metas Divi sont sérialisées) \$unserialized =
maybe_unserialize( \$meta_value ); add_post_meta( \$destination_id,
\$meta_key, \$unserialized ); } } \$this-\>log_info( sprintf( \'Metas
Divi copiées : %d metas du post %d vers %d.\', count( \$all_metas ),
\$source_id, \$destination_id ) ); } /\*\* \* Copie les taxonomies du
post source vers le post destination. \* \* \@param int \$source_id \*
\@param int \$destination_id \*/ private function copy_taxonomies( int
\$source_id, int \$destination_id ): void { \$taxonomies =
get_object_taxonomies( get_post_type( \$source_id ) ); foreach (
\$taxonomies as \$taxonomy ) { \$terms = wp_get_object_terms(
\$source_id, \$taxonomy, \[ \'fields\' =\> \'ids\' \] ); if ( !
is_wp_error( \$terms ) && ! empty( \$terms ) ) { wp_set_object_terms(
\$destination_id, \$terms, \$taxonomy ); } } } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// INJECTION COMPLÈTE //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Point d\'entrée principal : duplique un template et injecte les
données SEO. \* \* \@param int \$source_template_id ID du template Divi
source. \* \@param array \$seo_data Données SEO (JSON standardisé
décodé). \* \@param array \$options Options supplémentaires : \* -
\'post_title\' string Titre du nouveau post. \* - \'post_status\' string
Statut (draft/publish). \* - \'post_type\' string Forcer un type de
post. \* \@return array { \* \@type bool \$success \* \@type int\|null
\$post_id ID du post créé. \* \@type string \$edit_url URL d\'édition
admin. \* \@type string \$preview_url URL de prévisualisation. \* \@type
array \$errors Erreurs non-bloquantes. \* \@type array \$warnings
Warnings (tokens manquants, etc.). \* } \*/ public function
create_from_template( int \$source_template_id, array \$seo_data, array
\$options = \[\] ): array { \$this-\>service_log = \[\]; // Reset log
pour ce run // \-\-- Validation des données SEO minimales \-\--
\$validation = \$this-\>validate_seo_data( \$seo_data ); if ( ! empty(
\$validation\[\'errors\'\] ) ) { return \[ \'success\' =\> false,
\'post_id\' =\> null, \'errors\' =\> \$validation\[\'errors\'\], \]; }
// \-\-- Étape 1 : Duplication du template \-\-- \$post_title =
\$options\[\'post_title\'\] ?? ( \$seo_data\[\'H1\'\] ?? \'Page
générée\' ); \$new_post_id = \$this-\>duplicate_template(
\$source_template_id, \$post_title ); if ( is_wp_error( \$new_post_id )
) { return \[ \'success\' =\> false, \'post_id\' =\> null, \'errors\'
=\> \[ \$new_post_id-\>get_error_message() \], \]; } // \-\-- Étape 2 :
Injection des tokens dans post_content \-\-- \$errors = \[\]; \$warnings
= \[\]; \$new_post = get_post( \$new_post_id ); \$post_content =
\$new_post-\>post_content; // 2a. Gestion des sections répétables (avant
le remplacement global) \$repeatable_processor = new
DiviRepeatableSection( \$seo_data ); \$post_content =
\$repeatable_processor-\>process( \$post_content ); \$warnings =
array_merge( \$warnings, \$repeatable_processor-\>get_error_log() ); //
2b. Remplacement global des tokens \$replacer = new DiviTokenReplacer(
\$seo_data ); \$post_content = \$replacer-\>replace( \$post_content );
\$warnings = array_merge( \$warnings, \$replacer-\>get_error_log() ); //
\-\-- Étape 3 : Mise à jour du post_content \-\-- \$update_result =
wp_update_post( \[ \'ID\' =\> \$new_post_id, \'post_content\' =\>
\$post_content, \'post_status\' =\> \$options\[\'post_status\'\] ??
\'draft\', \], true ); if ( is_wp_error( \$update_result ) ) {
\$errors\[\] = \'Erreur mise à jour post_content : \' .
\$update_result-\>get_error_message(); } // \-\-- Étape 4 : Mise à jour
des metas (tokens dans les metas Divi) \-\-- \$meta_updates =
\$replacer-\>build_meta_replacements( \$new_post_id, \$post_content );
foreach ( \$meta_updates as \$meta_key =\> \$meta_value ) {
update_post_meta( \$new_post_id, \$meta_key, \$meta_value ); } // \-\--
Étape 5 : Mise à jour des metas SEO (Yoast / slug) \-\--
\$this-\>apply_seo_metas( \$new_post_id, \$seo_data ); // \-\-- Étape 6
: Mise à jour du slug \-\-- if ( ! empty( \$seo_data\[\'slug\'\] ) ) {
wp_update_post( \[ \'ID\' =\> \$new_post_id, \'post_name\' =\>
sanitize_title( \$seo_data\[\'slug\'\] ), \] ); } // Récupère les URLs
\$edit_url = get_edit_post_link( \$new_post_id, \'raw\' ); \$preview_url
= get_preview_post_link( \$new_post_id ); \$this-\>log_info( sprintf(
\'Page créée depuis template Divi. Post ID: %d\', \$new_post_id ) );
return \[ \'success\' =\> true, \'post_id\' =\> \$new_post_id,
\'edit_url\' =\> \$edit_url, \'preview_url\' =\> \$preview_url,
\'errors\' =\> \$errors, \'warnings\' =\> \$warnings, \]; } /\*\* \*
Applique les metas SEO Yoast et autres sur le nouveau post. \* \*
\@param int \$post_id \* \@param array \$seo_data \*/ private function
apply_seo_metas( int \$post_id, array \$seo_data ): void { // \-\--
Yoast SEO \-\-- if ( ! empty( \$seo_data\[\'metatitle\'\] ) ) {
update_post_meta( \$post_id, \'\_yoast_wpseo_title\',
sanitize_text_field( \$seo_data\[\'metatitle\'\] ) ); } if ( ! empty(
\$seo_data\[\'metadescription\'\] ) ) { update_post_meta( \$post_id,
\'\_yoast_wpseo_metadesc\', sanitize_text_field(
\$seo_data\[\'metadescription\'\] ) ); } // \-\-- Yoast : focus
keyphrase \-\-- if ( ! empty( \$seo_data\[\'focus_keyword\'\] ) ) {
update_post_meta( \$post_id, \'\_yoast_wpseo_focuskw\',
sanitize_text_field( \$seo_data\[\'focus_keyword\'\] ) ); } // \-\--
Flag Techrappy (traçabilité) \-\-- update_post_meta( \$post_id,
\'\_techrappy_generated\', \'1\' ); update_post_meta( \$post_id,
\'\_techrappy_source_template\', (string) 0 ); // sera mis à jour
update_post_meta( \$post_id, \'\_techrappy_generated_at\', current_time(
\'mysql\' ) ); } /\*\* \* Valide les données SEO minimales requises. \*
\* \@param array \$seo_data \* \@return array \[\'errors\' =\>
\[\...\]\] \*/ private function validate_seo_data( array \$seo_data ):
array { \$errors = \[\]; \$required = \[ \'H1\' \]; foreach ( \$required
as \$key ) { if ( empty( \$seo_data\[ \$key \] ) ) { \$errors\[\] =
sprintf( \'Champ SEO requis manquant : \"%s\".\', \$key ); } } return \[
\'errors\' =\> \$errors \]; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// LOG INTERNE //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@param string \$msg \*/ private function log_info( string
\$msg ): void { \$full = \'\[TechrappySEO\]\[DiviTemplateService\] INFO:
\' . \$msg; \$this-\>service_log\[\] = \$full; error_log( \$full ); }
/\*\* \* \@param string \$msg \*/ private function log_warning( string
\$msg ): void { \$full = \'\[TechrappySEO\]\[DiviTemplateService\]
WARNING: \' . \$msg; \$this-\>service_log\[\] = \$full; error_log(
\$full ); } /\*\* \* \@return array \*/ public function
get_service_log(): array { return \$this-\>service_log; } } \`\`\` \-\--
\## 5. Handlers AJAX --- \`class-divi-ajax-handler.php\` \`\`\`php
\<?php /\*\* \* Divi Ajax Handler \* \* Enregistre et traite les actions
AJAX admin pour le module Divi. \* - techrappy_divi_get_templates \* -
techrappy_divi_analyze_template \* - techrappy_divi_create_from_template
\* \* \@package TechrappySEO\\Modules\\Divi \*/ namespace
TechrappySEO\\Modules\\Divi; defined( \'ABSPATH\' ) \|\| exit; class
DiviAjaxHandler { /\*\* \* Capability requise pour utiliser le module.
\*/ const REQUIRED_CAP = \'manage_options\'; /\*\* \* Enregistre les
hooks AJAX WordPress. \*/ public function register(): void { add_action(
\'wp_ajax_techrappy_divi_get_templates\', \[ \$this,
\'ajax_get_templates\' \] ); add_action(
\'wp_ajax_techrappy_divi_analyze_template\', \[ \$this,
\'ajax_analyze_template\' \] ); add_action(
\'wp_ajax_techrappy_divi_create_from_template\', \[ \$this,
\'ajax_create_from_template\' \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* AJAX : Récupère la liste des templates Divi disponibles. \*
Action : techrappy_divi_get_templates \*/ public function
ajax_get_templates(): void { \$this-\>verify_nonce(
\'techrappy_divi_nonce\' ); \$this-\>check_capability(); \$service = new
DiviTemplateService(); \$templates =
\$service-\>get_available_templates(); wp_send_json_success( \[
\'templates\' =\> \$templates \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* AJAX : Analyse un template et retourne les tokens détectés. \*
Action : techrappy_divi_analyze_template \* \* POST params: \* - nonce
string Nonce de sécurité. \* - template_id int ID du post template. \*/
public function ajax_analyze_template(): void { \$this-\>verify_nonce(
\'techrappy_divi_nonce\' ); \$this-\>check_capability(); \$template_id =
isset( \$\_POST\[\'template_id\'\] ) ? absint(
\$\_POST\[\'template_id\'\] ) : 0; if ( ! \$template_id ) {
wp_send_json_error( \[ \'message\' =\> \'template_id manquant ou
invalide.\' \], 400 ); } \$service = new DiviTemplateService(); \$result
= \$service-\>analyze_template( \$template_id ); if ( !
\$result\[\'success\'\] ) { wp_send_json_error( \$result, 422 ); }
wp_send_json_success( \$result ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* AJAX : Crée un post depuis un template Divi en injectant les
données SEO. \* Action : techrappy_divi_create_from_template \* \* POST
params: \* - nonce string Nonce de sécurité. \* - template_id int ID du
template source. \* - seo_data string JSON encodé des données SEO. \* -
post_status string \'draft\' ou \'publish\' (défaut: draft). \*/ public
function ajax_create_from_template(): void { \$this-\>verify_nonce(
\'techrappy_divi_nonce\' ); \$this-\>check_capability(); \$template_id =
isset( \$\_POST\[\'template_id\'\] ) ? absint(
\$\_POST\[\'template_id\'\] ) : 0; if ( ! \$template_id ) {
wp_send_json_error( \[ \'message\' =\> \'template_id manquant.\' \], 400
); } // Décode le JSON SEO \$seo_data_raw = isset(
\$\_POST\[\'seo_data\'\] ) ? wp_unslash( \$\_POST\[\'seo_data\'\] ) :
\'\'; \$seo_data = json_decode( \$seo_data_raw, true ); if ( ! is_array(
\$seo_data ) \|\| empty( \$seo_data ) ) { wp_send_json_error( \[
\'message\' =\> \'seo_data invalide ou vide.\' \], 400 ); } // Sanitize
post_status \$allowed_statuses = \[ \'draft\', \'publish\', \'private\'
\]; \$post_status = isset( \$\_POST\[\'post_status\'\] ) ? sanitize_key(
\$\_POST\[\'post_status\'\] ) : \'draft\'; if ( ! in_array(
\$post_status, \$allowed_statuses, true ) ) { \$post_status = \'draft\';
} \$service = new DiviTemplateService(); \$result =
\$service-\>create_from_template( \$template_id, \$seo_data, \[
\'post_status\' =\> \$post_status \] ); if ( ! \$result\[\'success\'\] )
{ wp_send_json_error( \$result, 422 ); } wp_send_json_success( \$result
); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// HELPERS SÉCURITÉ //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Vérifie le nonce AJAX. Die si invalide. \* \* \@param string
\$action Nom de l\'action nonce. \*/ private function verify_nonce(
string \$action ): void { \$nonce = isset( \$\_POST\[\'nonce\'\] ) ?
sanitize_text_field( wp_unslash( \$\_POST\[\'nonce\'\] ) ) : \'\'; if (
! wp_verify_nonce( \$nonce, \$action ) ) { wp_send_json_error( \[
\'message\' =\> \'Nonce invalide.\' \], 403 ); } } /\*\* \* Vérifie la
capability. Die si insuffisante. \*/ private function
check_capability(): void { if ( ! current_user_can( self::REQUIRED_CAP )
) { wp_send_json_error( \[ \'message\' =\> \'Permission refusée.\' \],
403 ); } } } \`\`\` \-\-- \## 6. UI Admin ---
\`divi-template-selector.php\` \`\`\`php \<?php /\*\* \* Partial :
Sélecteur de template Divi \* Inclus dans l\'écran \"Générer\" du plugin
Techrappy SEO. \* \* Variables attendues dans le scope : \*
\$divi_templates : array Liste des templates \[ id, title, type \] \*
\$nonce_value : string Nonce techrappy_divi_nonce \* \* \@package
TechrappySEO\\Admin \*/ defined( \'ABSPATH\' ) \|\| exit; ?\> \<div
id=\"techrappy-divi-section\" class=\"techrappy-card
techrappy-divi-section\"\> \<h3 class=\"techrappy-card\_\_title\"\>
\<?php esc_html_e( \'🎨 Créer depuis un template Divi\',
\'techrappy-seo\' ); ?\> \</h3\> \<p
class=\"techrappy-card\_\_description\"\> \<?php esc_html_e(
\'Sélectionnez un template Divi existant pour dupliquer sa structure et
y injecter automatiquement les données SEO générées.\',
\'techrappy-seo\' ); ?\> \</p\> \<!\-- Sélecteur de template \--\> \<div
class=\"techrappy-form-group\"\> \<label
for=\"techrappy-divi-template-select\"\> \<?php esc_html_e( \'Template
Divi :\', \'techrappy-seo\' ); ?\> \</label\> \<select
id=\"techrappy-divi-template-select\" name=\"divi_template_id\"
class=\"techrappy-select\"\> \<option value=\"\"\>\<?php esc_html_e(
\'--- Sélectionner un template ---\', \'techrappy-seo\' );
?\>\</option\> \<?php if ( ! empty( \$divi_templates ) ) : ?\> \<?php
foreach ( \$divi_templates as \$template ) : ?\> \<option value=\"\<?php
echo esc_attr( \$template\[\'id\'\] ); ?\>\"\> \<?php echo esc_html(
sprintf( \'%s \[%s\]\', \$template\[\'title\'\], strtoupper(
\$template\[\'type\'\] ) ) ); ?\> \</option\> \<?php endforeach; ?\>
\<?php else : ?\> \<option value=\"\" disabled\> \<?php esc_html_e(
\'Aucun template Divi disponible\', \'techrappy-seo\' ); ?\> \</option\>
\<?php endif; ?\> \</select\> \</div\> \<!\-- Bouton Analyser \--\>
\<div class=\"techrappy-form-actions\"\> \<button type=\"button\"
id=\"techrappy-divi-analyze-btn\" class=\"button button-secondary\"
disabled \> \<?php esc_html_e( \'🔍 Analyser le template\',
\'techrappy-seo\' ); ?\> \</button\> \</div\> \<!\-- Zone résultat
analyse \--\> \<div id=\"techrappy-divi-analysis-result\"
class=\"techrappy-analysis-result\" style=\"display:none;\"\>
\<h4\>\<?php esc_html_e( \'Tokens détectés :\', \'techrappy-seo\' );
?\>\</h4\> \<div id=\"techrappy-divi-token-list\"
class=\"techrappy-token-list\"\> \<!\-- Rempli dynamiquement via JS
\--\> \</div\> \<div id=\"techrappy-divi-repeatable-notice\"
class=\"techrappy-notice techrappy-notice\--info\"
style=\"display:none;\"\> ⚙️ \<?php esc_html_e( \'Ce template contient
un bloc répétable (sections).\', \'techrappy-seo\' ); ?\> \</div\>
\</div\> \<!\-- Séparateur \--\> \<hr class=\"techrappy-divider\"/\>
\<!\-- Options de création \--\> \<div
id=\"techrappy-divi-create-options\" class=\"techrappy-create-options\"
style=\"display:none;\"\> \<div class=\"techrappy-form-group\"\> \<label
for=\"techrappy-divi-post-status\"\> \<?php esc_html_e( \'Statut de
publication :\', \'techrappy-seo\' ); ?\> \</label\> \<select
id=\"techrappy-divi-post-status\" name=\"divi_post_status\"
class=\"techrappy-select\"\> \<option value=\"draft\"\>\<?php
esc_html_e( \'Brouillon\', \'techrappy-seo\' ); ?\>\</option\> \<option
value=\"publish\"\>\<?php esc_html_e( \'Publier\', \'techrappy-seo\' );
?\>\</option\> \<option value=\"private\"\>\<?php esc_html_e( \'Privé\',
\'techrappy-seo\' ); ?\>\</option\> \</select\> \</div\> \<div
class=\"techrappy-form-actions\"\> \<button type=\"button\"
id=\"techrappy-divi-create-btn\" class=\"button button-primary\" \>
\<?php esc_html_e( \'🚀 Créer la page depuis ce template\',
\'techrappy-seo\' ); ?\> \</button\> \</div\> \</div\> \<!\-- Zone
résultat création \--\> \<div id=\"techrappy-divi-create-result\"
class=\"techrappy-create-result\" style=\"display:none;\"\> \<!\--
Rempli dynamiquement via JS \--\> \</div\> \<!\-- Loader \--\> \<div
id=\"techrappy-divi-loader\" class=\"techrappy-loader\"
style=\"display:none;\"\> \<span class=\"spinner is-active\"\>\</span\>
\<span\>\<?php esc_html_e( \'Traitement en cours...\', \'techrappy-seo\'
); ?\>\</span\> \</div\> \<!\-- Nonce caché \--\> \<input
type=\"hidden\" id=\"techrappy-divi-nonce\" value=\"\<?php echo
esc_attr( \$nonce_value ); ?\>\" /\> \</div\> \`\`\` \-\-- \## 7.
JavaScript Admin --- \`divi-template-admin.js\` \`\`\`javascript /\*\*
\* divi-template-admin.js \* \* Gestion de l\'UI du module Divi Builder
dans l\'admin Techrappy SEO. \* - Chargement de la liste des templates
\* - Analyse des tokens d\'un template \* - Création d\'un post depuis
un template avec injection SEO \* \* Dépendances : jQuery (WordPress),
techrappyDiviData (localisé via wp_localize_script) \* \* \@package
TechrappySEO \*/ /\* global jQuery, techrappyDiviData \*/ (function (\$)
{ \'use strict\'; //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Configuration //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
const CONFIG = { ajaxUrl : techrappyDiviData.ajaxUrl, nonce :
techrappyDiviData.nonce, actions : { getTemplates :
\'techrappy_divi_get_templates\', analyzeTemplate :
\'techrappy_divi_analyze_template\', createFromTemplate:
\'techrappy_divi_create_from_template\', }, i18n :
techrappyDiviData.i18n \|\| {}, }; //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Sélecteurs DOM //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
const DOM = { section : \'#techrappy-divi-section\', templateSelect :
\'#techrappy-divi-template-select\', analyzeBtn :
\'#techrappy-divi-analyze-btn\', analysisResult :
\'#techrappy-divi-analysis-result\', tokenList :
\'#techrappy-divi-token-list\', repeatableNotice :
\'#techrappy-divi-repeatable-notice\', createOptions :
\'#techrappy-divi-create-options\', createBtn :
\'#techrappy-divi-create-btn\', postStatus :
\'#techrappy-divi-post-status\', createResult :
\'#techrappy-divi-create-result\', loader : \'#techrappy-divi-loader\',
nonce : \'#techrappy-divi-nonce\', }; //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// État interne //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
const state = { selectedTemplateId : null, analysisData : null, seoData
: null, // Injecté depuis l\'écran principal }; //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Init //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
function init() { bindEvents(); // Expose getSeoData pour que l\'écran
principal puisse pousser les données window.TechrappyDivi = { setSeoData
: setSeoData, getState : () =\> state, }; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Bind Events //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
function bindEvents() { // Activation du bouton Analyser quand un
template est sélectionné \$(document).on(\'change\', DOM.templateSelect,
function () { const templateId = \$(this).val();
state.selectedTemplateId = templateId ? parseInt(templateId, 10) : null;
\$(DOM.analyzeBtn).prop(\'disabled\', !state.selectedTemplateId); //
Reset des zones hideAnalysisResult(); hideCreateOptions();
hideCreateResult(); }); // Bouton Analyser \$(document).on(\'click\',
DOM.analyzeBtn, function () { if (!state.selectedTemplateId) return;
analyzeTemplate(state.selectedTemplateId); }); // Bouton Créer
\$(document).on(\'click\', DOM.createBtn, function () { if
(!state.selectedTemplateId) { showError(\'Veuillez sélectionner un
template.\'); return; } if (!state.seoData) { showError(\'Aucune donnée
SEO disponible. Générez d\\\'abord le contenu SEO.\'); return; } const
postStatus = \$(DOM.postStatus).val() \|\| \'draft\';
createFromTemplate(state.selectedTemplateId, state.seoData, postStatus);
}); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Analyse du template //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Envoie une requête AJAX pour analyser les tokens du template
sélectionné. \* \* \@param {number} templateId \*/ function
analyzeTemplate(templateId) { showLoader(); hideAnalysisResult();
hideCreateOptions(); \$.ajax({ url : CONFIG.ajaxUrl, type : \'POST\',
data : { action : CONFIG.actions.analyzeTemplate, nonce : getNonce(),
template_id: templateId, }, success: function (response) { hideLoader();
if (response.success) { state.analysisData = response.data;
renderAnalysisResult(response.data); showCreateOptions(); } else { const
msg = response.data?.message \|\| \'Erreur lors de l\\\'analyse.\';
showError(msg); } }, error : function (xhr, status, error) {
hideLoader(); showError(\'Erreur réseau : \' + error);
console.error(\'\[TechrappyDivi\] analyzeTemplate error:\', error); },
}); } /\*\* \* Affiche les résultats de l\'analyse dans l\'UI. \* \*
\@param {Object} data Données retournées par le service. \*/ function
renderAnalysisResult(data) { const \$tokenList = \$(DOM.tokenList);
\$tokenList.empty(); if (!data.tokens \|\| data.tokens.length === 0) {
\$tokenList.html( \'\<p class=\"techrappy-notice
techrappy-notice\--warning\"\>\' + \'⚠️ Aucun token détecté dans ce
template.\' + \'\</p\>\' ); } else { const \$ul = \$(\'\<ul
class=\"techrappy-token-tags\"\>\</ul\>\'); data.tokens.forEach(function
(token) { const \$li = \$(\'\<li\>\</li\>\').append( \$(\'\<code
class=\"techrappy-token-tag\"\>\</code\>\').text(\'{{\' + token +
\'}}\') ); \$ul.append(\$li); }); \$tokenList.append(\$ul); } // Notice
bloc répétable if (data.has_repeatable) {
\$(DOM.repeatableNotice).show(); } else {
\$(DOM.repeatableNotice).hide(); } // Affiche la zone
\$(DOM.analysisResult).slideDown(200); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Création depuis template //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Crée un post depuis le template Divi avec injection des données
SEO. \* \* \@param {number} templateId \* \@param {Object} seoData \*
\@param {string} postStatus \*/ function createFromTemplate(templateId,
seoData, postStatus) { showLoader(); hideCreateResult(); \$.ajax({ url :
CONFIG.ajaxUrl, type : \'POST\', data : { action :
CONFIG.actions.createFromTemplate, nonce : getNonce(), template_id:
templateId, seo_data : JSON.stringify(seoData), post_status: postStatus,
}, success: function (response) { hideLoader(); if (response.success) {
renderCreateResult(response.data); } else { const msg =
response.data?.message \|\| \'Erreur lors de la création.\';
showError(msg); } }, error : function (xhr, status, error) {
hideLoader(); showError(\'Erreur réseau : \' + error);
console.error(\'\[TechrappyDivi\] createFromTemplate error:\', error);
}, }); } /\*\* \* Affiche le résultat de la création dans l\'UI. \* \*
\@param {Object} data \*/ function renderCreateResult(data) { const
\$result = \$(DOM.createResult); \$result.empty(); let html = \'\<div
class=\"techrappy-notice techrappy-notice\--success\"\>\'; html +=
\'\<strong\>✅ Page créée avec succès !\</strong\>\<br/\>\'; html +=
\'\<a href=\"\' + escapeHtml(data.edit_url) + \'\" target=\"\_blank\"
class=\"button button-secondary\"\>✏️ Éditer dans Divi\</a\> \'; html +=
\'\<a href=\"\' + escapeHtml(data.preview_url) + \'\" target=\"\_blank\"
class=\"button\"\>👁️ Prévisualiser\</a\>\'; html += \'\</div\>\'; //
Affiche les warnings (tokens manquants, etc.) if (data.warnings &&
data.warnings.length \> 0) { html += \'\<div class=\"techrappy-notice
techrappy-notice\--warning\"\>\'; html += \'\<strong\>⚠️ Avertissements
:\</strong\>\<ul\>\'; data.warnings.forEach(function (w) { html +=
\'\<li\>\' + escapeHtml(w) + \'\</li\>\'; }); html +=
\'\</ul\>\</div\>\'; } // Affiche les erreurs non-bloquantes if
(data.errors && data.errors.length \> 0) { html += \'\<div
class=\"techrappy-notice techrappy-notice\--error\"\>\'; html +=
\'\<strong\>❌ Erreurs non-bloquantes :\</strong\>\<ul\>\';
data.errors.forEach(function (e) { html += \'\<li\>\' + escapeHtml(e) +
\'\</li\>\'; }); html += \'\</ul\>\</div\>\'; }
\$result.html(html).slideDown(200); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Interface publique (appelée par l\'écran principal) //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Reçoit les données SEO générées par le moteur SEO. \* Appelé
depuis l\'écran principal après génération réussie. \* \* \@param
{Object} seoData Données SEO au format JSON standardisé. \*/ function
setSeoData(seoData) { state.seoData = seoData; if
(state.selectedTemplateId) { showCreateOptions(); } } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Helpers UI //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
function showLoader() { \$(DOM.loader).show(); } function hideLoader() {
\$(DOM.loader).hide(); } function hideAnalysisResult(){
\$(DOM.analysisResult).hide(); } function hideCreateOptions() {
\$(DOM.createOptions).hide(); } function hideCreateResult() {
\$(DOM.createResult).hide(); } function showCreateOptions() { if
(state.seoData) { \$(DOM.createOptions).slideDown(200); } } function
showError(message) { const \$result = \$(DOM.createResult); \$result
.html( \'\<div class=\"techrappy-notice techrappy-notice\--error\"\>❌
\' + escapeHtml(message) + \'\</div\>\' ) .slideDown(200); } function
getNonce() { return \$(DOM.nonce).val() \|\| CONFIG.nonce; } /\*\* \*
Échappe les caractères HTML pour l\'affichage sécurisé. \* \* \@param
{string} str \* \@returns {string} \*/ function escapeHtml(str) { if
(!str) return \'\'; return String(str) .replace(/&/g, \'&amp;\')
.replace(/\</g, \'&lt;\') .replace(/\>/g, \'&gt;\') .replace(/\"/g,
\'&quot;\') .replace(/\'/g, \'&#039;\'); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Bootstrap //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
\$(document).ready(init); }(jQuery)); \`\`\` \-\-- \## 8. Enqueue +
Localisation --- À ajouter dans la classe Admin principale \`\`\`php
\<?php /\*\* \* Extrait à ajouter dans la méthode enqueue_scripts() de
la classe Admin existante. \* Enregistre et localise le script du module
Divi. \* \* \@package TechrappySEO\\Admin \*/ // Dans la méthode
enqueue_scripts() ou équivalent : // Enqueue du script Divi (uniquement
sur la page admin du plugin) wp_enqueue_script(
\'techrappy-divi-admin\', TECHRAPPY_SEO_URL .
\'assets/js/divi-template-admin.js\', \[ \'jquery\' \],
TECHRAPPY_SEO_VERSION, true // En pied de page ); // Génération du nonce
(même action que dans DiviAjaxHandler) \$divi_nonce = wp_create_nonce(
\'techrappy_divi_nonce\' ); // Localisation des données pour le JS
wp_localize_script( \'techrappy-divi-admin\', \'techrappyDiviData\', \[
\'ajaxUrl\' =\> admin_url( \'admin-ajax.php\' ), \'nonce\' =\>
\$divi_nonce, \'i18n\' =\> \[ \'selectTemplate\' =\> \_\_( \'---
Sélectionner un template ---\', \'techrappy-seo\' ), \'analyzing\' =\>
\_\_( \'Analyse en cours...\', \'techrappy-seo\' ), \'creating\' =\>
\_\_( \'Création en cours...\', \'techrappy-seo\' ), \'noTokens\' =\>
\_\_( \'Aucun token détecté.\', \'techrappy-seo\' ), \'noSeoData\' =\>
\_\_( \'Générez d\\\'abord le contenu SEO.\', \'techrappy-seo\' ), \],
\] ); // Rendu du partial UI avec les variables nécessaires // Dans la
méthode render() de la page admin \"Générer\" : \$divi_service = new
\\TechrappySEO\\Modules\\Divi\\DiviTemplateService(); \$divi_templates =
\$divi_service-\>get_available_templates(); \$nonce_value =
\$divi_nonce; // Inclusion du partial include TECHRAPPY_SEO_PATH .
\'admin/partials/divi-template-selector.php\'; \`\`\` \-\-- \## 9.
Documentation Technique --- \`docs/divi-template-guide.md\`
\`\`\`\`markdown \# Guide : Créer un template Divi compatible Techrappy
SEO \## Vue d\'ensemble Un template Divi compatible Techrappy SEO
utilise des \*\*tokens\*\* (marqueurs de substitution) et
optionnellement des \*\*marqueurs de section répétable\*\* pour
permettre la génération automatique de pages depuis les données SEO.
\-\-- \## 1. Les Tokens \### Syntaxe \`\`\` {{NOM_DU_TOKEN}} \`\`\` Les
tokens sont \*\*insensibles à la casse dans le nom\*\* mais doivent
respecter exactement la casse définie dans le mapping ci-dessous. \###
Tableau des tokens officiels \| Token \| Remplacé par \| Source JSON SEO
\|
\|\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\|
\| \`{{H1}}\` \| Titre principal H1 \| \`seo_data.H1\` \| \|
\`{{INTRO_HTML}}\` \| Paragraphe d\'introduction HTML \|
\`seo_data.intro_html\` \| \| \`{{METATITLE}}\` \| Titre meta (Yoast) \|
\`seo_data.metatitle\` \| \| \`{{METADESCRIPTION}}\`\| Meta description
(Yoast) \| \`seo_data.metadescription\` \| \| \`{{SLUG}}\` \| Slug de
l\'URL \| \`seo_data.slug\` \| \| \`{{FAQ_BLOCK}}\` \| Bloc FAQ HTML +
JSON-LD \| \`seo_data.faq_block_html\` \| \| \`{{CTA_BLOCK}}\` \| Bloc
CTA HTML \| \`seo_data.cta_block_html\` \| \### Tokens dans les sections
répétables \| Token \| Remplacé par \|
\|\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\|
\| \`{{SECTION_TITLE}}\` \| Titre de la section \| \|
\`{{SECTION_BODY}}\` \| Corps de la section \| \| \`{{SECTION_INDEX}}\`
\| Numéro de section (1, 2, 3...) \| \-\-- \## 2. Exemple de shortcode
Divi avec tokens \`\`\` \[et_pb_text admin_label=\"H1\"\]
\<h1\>{{H1}}\</h1\> \[/et_pb_text\] \[et_pb_text
admin_label=\"Introduction\"\] {{INTRO_HTML}} \[/et_pb_text\]
\[et_pb_text admin_label=\"CTA\"\] {{CTA_BLOCK}} \[/et_pb_text\]
\[et_pb_text admin_label=\"FAQ\"\] {{FAQ_BLOCK}} \[/et_pb_text\] \`\`\`
\-\-- \## 3. Sections répétables \### Marqueurs Pour définir un bloc que
Techrappy SEO dupliquera selon le nombre de sections dans
\`seo_data.sections\`, entourez le shortcode Divi avec : \`\`\` \<!\--
TECHRAPPY_REPEAT_START \--\> \[et_pb_section\] \[et_pb_row\]
\[et_pb_column\]
\[et_pb_text\]\<h2\>{{SECTION_TITLE}}\</h2\>\[/et_pb_text\]
\[et_pb_text\]{{SECTION_BODY}}\[/et_pb_text\] \[/et_pb_column\]
\[/et_pb_row\] \[/et_pb_section\] \<!\-- TECHRAPPY_REPEAT_END \--\>
\`\`\` \### Structure JSON attendue pour les sections \`\`\`json {
\"H1\": \"Ostéopathe à Toulouse\", \"sections\": \[ { \"title\":
\"Déroulement d\'une séance\", \"body\": \"Une séance débute par un
bilan\...\" }, { \"title\": \"Ostéopathie pour bébés\", \"body\": \"Les
nourrissons peuvent bénéficier\...\" } \] } \`\`\` \### Comportement \|
Cas \| Comportement \|
\|\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--\|
\| \`sections\` absent/vide \| Le bloc répétable est supprimé du contenu
\| \| 1 section \| Le bloc est conservé avec les tokens remplacés \| \|
N sections \| Le bloc est dupliqué N fois \| \| Token manquant dans
section\| Le token reste en place, warning loggé \| \-\-- \## 4. Token
manquant Si un token présent dans le template n\'est \*\*pas trouvé\*\*
dans le JSON SEO : - Le token \`{{MON_TOKEN}}\` est \*\*conservé tel
quel\*\* dans le contenu final - Une erreur est loggée dans
\`error_log()\` WordPress - La génération \*\*ne s\'interrompt pas\*\*
\-\-- \## 5. Checklist template compatible - \[ \] Le template utilise
le Divi Builder (\`\_et_pb_use_builder = \"on\"\`) - \[ \] Tous les
tokens sont au format \`{{NOM_EN_MAJUSCULES}}\` - \[ \] Si sections
répétables : les marqueurs \`\<!\-- TECHRAPPY_REPEAT_START/END \--\>\`
sont présents - \[ \] Le template est publié ou en brouillon (pas à la
corbeille) - \[ \] Le template est listé dans : Pages ou Articles ou
Divi Library \-\-- \## 6. Post types supportés comme templates -
\`page\` (Pages WordPress) - \`post\` (Articles WordPress) -
\`et_pb_layout\` (Bibliothèque Divi) \`\`\`\` \-\-- \## Récapitulatif de
l\'architecture livrée \`\`\`
┌─────────────────────────────────────────────────────────┐ │ ÉCRAN
ADMIN \"Générer\" │ │
┌─────────────────────────────────────────────────┐ │ │ │
divi-template-selector.php (partial PHP) │ │ │ │ +
divi-template-admin.js (interactions AJAX) │ │ │
└──────────────┬──────────────────────────────────┘ │ │ │ AJAX (nonce +
capability check) │ │
┌──────────────▼──────────────────────────────────┐ │ │ │
DiviAjaxHandler │ │ │ │ - ajax_get_templates() │ │ │ │ -
ajax_analyze_template() │ │ │ │ - ajax_create_from_template() │ │ │
└──────────────┬──────────────────────────────────┘ │ │ │ │ │
┌──────────────▼──────────────────────────────────┐ │ │ │
DiviTemplateService (orchestrateur) │ │ │ │ get_available_templates() │
│ │ │ analyze_template() ──► DiviTemplateAnalyzer│ │ │ │
duplicate_template() │ │ │ │ create_from_template() ──►
DiviRepeatableSection│ │ │ │ ──► DiviTokenReplacer │ │ │
└─────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘ Flux \"Créer
depuis template\" : 1. Sélectionner template → analyzeTemplate() →
afficher tokens 2. JSON SEO généré → setSeoData() 3. Cliquer \"Créer\" →
createFromTemplate() a. duplicate_template() → nouveau post draft b.
DiviRepeatableSection.process() → gère blocs répétables c.
DiviTokenReplacer.replace() → remplace tous les tokens d.
wp_update_post() → sauvegarde contenu e. update_post_meta() → metas
Divi + Yoast f. Retourne edit_url + preview_url \`\`\` \`\`\` Partie 1
(run précédent) : Services + AJAX + UI partielle ✅ Partie 2 (ce run)
: - Intégration dans le flux WordPress existant (hook sur create_post) -
CSS admin complet - Tests unitaires (PHPUnit) - Gestion edge cases (Divi
désactivé, template corrompu, JSON malformé) - Preview inline avant
création - Nettoyage post-génération (meta cleanup) - Enregistrement du
module dans le plugin principal \`\`\` \-\-- \## Fichiers livrés \`\`\`
techrappy-seo/ ├── modules/ │ └── divi/ │ ├── DiviTemplateService.php ✅
(run précédent) │ ├── DiviTokenReplacer.php ✅ (run précédent) │ ├──
DiviRepeatableSection.php ✅ (run précédent) │ ├──
DiviTemplateAnalyzer.php ✅ (run précédent) │ ├── DiviAjaxHandler.php ✅
(run précédent) │ ├── DiviModule.php 🆕 Point d\'entrée module │ ├──
DiviCompatibilityChecker.php 🆕 Vérification environnement │ ├──
DiviPreviewBuilder.php 🆕 Preview inline │ └── DiviPostCleaner.php 🆕
Nettoyage metas ├── admin/ │ └── partials/ │ ├──
divi-template-selector.php ✅ (run précédent) │ └──
divi-preview-modal.php 🆕 Modal preview ├── assets/ │ ├── js/ │ │ └──
divi-template-admin.js 🆕 Version complète │ └── css/ │ └──
divi-admin.css 🆕 Styles complets └── tests/ └── divi/ ├──
DiviTemplateAnalyzerTest.php 🆕 ├── DiviTokenReplacerTest.php 🆕 └──
DiviRepeatableSectionTest.php 🆕 \`\`\` \-\-- \## 1. DiviModule.php ---
Point d\'entrée du module \`\`\`php \<?php /\*\* \* DiviModule \* \*
Point d\'entrée unique du module Divi. \* Enregistre tous les hooks
WordPress, les assets et les handlers AJAX. \* Doit être instancié
depuis le plugin principal via : \* ( new DiviModule() )-\>register();
\* \* \@package TechrappySEO\\Modules\\Divi \*/ namespace
TechrappySEO\\Modules\\Divi; defined( \'ABSPATH\' ) \|\| exit; class
DiviModule { /\*\* \* Slug de la page admin du plugin (défini dans
l\'architecture existante). \*/ const ADMIN_PAGE_SLUG =
\'techrappy-seo\'; /\*\* \* Handle du script JS du module. \*/ const
SCRIPT_HANDLE = \'techrappy-divi-admin\'; /\*\* \* Handle du style CSS
du module. \*/ const STYLE_HANDLE = \'techrappy-divi-admin-css\'; /\*\*
\* Instance du checker de compatibilité. \* \* \@var
DiviCompatibilityChecker \*/ private DiviCompatibilityChecker
\$compat_checker; /\*\* \* Instance du handler AJAX. \* \* \@var
DiviAjaxHandler \*/ private DiviAjaxHandler \$ajax_handler; public
function \_\_construct() { \$this-\>compat_checker = new
DiviCompatibilityChecker(); \$this-\>ajax_handler = new
DiviAjaxHandler(); } /\*\* \* Enregistre tous les hooks du module. \*
Appelé depuis le plugin principal. \*/ public function register(): void
{ // Vérification de compatibilité Divi (non-bloquant) add_action(
\'admin_notices\', \[ \$this, \'maybe_show_compat_notice\' \] ); //
Enqueue assets admin add_action( \'admin_enqueue_scripts\', \[ \$this,
\'enqueue_assets\' \] ); // Enregistrement des handlers AJAX
\$this-\>ajax_handler-\>register(); // Hook sur la création de post via
le flux principal Techrappy // Permet d\'intercepter la création et de
proposer le flux Divi add_filter(
\'techrappy_seo_post_creation_methods\', \[ \$this,
\'register_creation_method\' \] ); // Hook post-création pour nettoyage
des metas temporaires add_action( \'techrappy_seo_after_post_created\',
\[ \$this, \'on_post_created\' \], 10, 2 ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Assets //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Enqueue CSS et JS uniquement sur la page admin Techrappy. \* \*
\@param string \$hook Hook de la page admin courante. \*/ public
function enqueue_assets( string \$hook ): void { // Vérifie qu\'on est
bien sur la page admin du plugin if ( !
\$this-\>is_techrappy_admin_page( \$hook ) ) { return; } // CSS
wp_enqueue_style( self::STYLE_HANDLE, TECHRAPPY_SEO_URL .
\'assets/css/divi-admin.css\', \[\], TECHRAPPY_SEO_VERSION ); // JS
wp_enqueue_script( self::SCRIPT_HANDLE, TECHRAPPY_SEO_URL .
\'assets/js/divi-template-admin.js\', \[ \'jquery\', \'wp-util\' \],
TECHRAPPY_SEO_VERSION, true ); // Nonce (durée de vie 12h, standard
WordPress) \$nonce = wp_create_nonce( \'techrappy_divi_nonce\' ); //
Données localisées pour le JS wp_localize_script( self::SCRIPT_HANDLE,
\'techrappyDiviData\', \[ \'ajaxUrl\' =\> admin_url( \'admin-ajax.php\'
), \'nonce\' =\> \$nonce, \'diviActive\' =\>
\$this-\>compat_checker-\>is_divi_active() ? \'1\' : \'0\', \'i18n\' =\>
\$this-\>get_i18n_strings(), \] ); // Passe le nonce au partial PHP via
une meta globale // (utilisé dans divi-template-selector.php)
\$GLOBALS\[\'techrappy_divi_nonce\'\] = \$nonce; } /\*\* \* Chaînes i18n
pour le JS. \* \* \@return array \*/ private function
get_i18n_strings(): array { return \[ \'selectTemplate\' =\> \_\_( \'---
Sélectionner un template ---\', \'techrappy-seo\' ), \'analyzing\' =\>
\_\_( \'Analyse en cours...\', \'techrappy-seo\' ), \'creating\' =\>
\_\_( \'Création en cours...\', \'techrappy-seo\' ), \'noTokens\' =\>
\_\_( \'Aucun token détecté.\', \'techrappy-seo\' ), \'noSeoData\' =\>
\_\_( \'Générez d\\\'abord le contenu SEO.\', \'techrappy-seo\' ),
\'confirmCreate\' =\> \_\_( \'Créer la page depuis ce template ?\',
\'techrappy-seo\' ), \'successCreated\' =\> \_\_( \'Page créée avec
succès !\', \'techrappy-seo\' ), \'errorGeneric\' =\> \_\_( \'Une erreur
est survenue. Vérifiez la console.\', \'techrappy-seo\' ),
\'tokenMissing\' =\> \_\_( \'Token non résolu :\', \'techrappy-seo\' ),
\'previewTitle\' =\> \_\_( \'Prévisualisation du contenu injecté\',
\'techrappy-seo\' ), \'editInDivi\' =\> \_\_( \'Éditer dans Divi\',
\'techrappy-seo\' ), \'preview\' =\> \_\_( \'Prévisualiser\',
\'techrappy-seo\' ), \'diviNotActive\' =\> \_\_( \'Divi n\\\'est pas
actif sur ce site.\', \'techrappy-seo\' ), \]; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Hooks flux principal //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Enregistre la méthode de création \"Divi Template\" dans le
flux principal. \* Filtre : techrappy_seo_post_creation_methods \* \*
\@param array \$methods Méthodes existantes. \* \@return array \*/
public function register_creation_method( array \$methods ): array {
\$methods\[\'divi_template\'\] = \[ \'label\' =\> \_\_( \'Depuis un
template Divi\', \'techrappy-seo\' ), \'description\' =\> \_\_(
\'Duplique un template Divi et injecte les données SEO.\',
\'techrappy-seo\' ), \'icon\' =\> \'🎨\', \'available\' =\>
\$this-\>compat_checker-\>is_divi_active(), \]; return \$methods; }
/\*\* \* Hook post-création : nettoie les metas temporaires Techrappy.
\* Action : techrappy_seo_after_post_created \* \* \@param int \$post_id
ID du post nouvellement créé. \* \@param array \$context Contexte de
création (method, source_id, etc.). \*/ public function on_post_created(
int \$post_id, array \$context ): void { if ( ( \$context\[\'method\'\]
?? \'\' ) !== \'divi_template\' ) { return; } \$cleaner = new
DiviPostCleaner( \$post_id ); \$cleaner-\>run(); // Enregistre la source
template pour traçabilité if ( ! empty(
\$context\[\'source_template_id\'\] ) ) { update_post_meta( \$post_id,
\'\_techrappy_source_template\', absint(
\$context\[\'source_template_id\'\] ) ); } } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Notice de compatibilité //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Affiche une notice admin si Divi n\'est pas actif. \*
Uniquement sur la page admin Techrappy. \*/ public function
maybe_show_compat_notice(): void { \$screen = get_current_screen(); if (
! \$screen \|\| ! str_contains( \$screen-\>id, self::ADMIN_PAGE_SLUG ) )
{ return; } \$issues = \$this-\>compat_checker-\>get_issues(); if (
empty( \$issues ) ) { return; } foreach ( \$issues as \$issue ) {
printf( \'\<div class=\"notice notice-warning
is-dismissible\"\>\<p\>\<strong\>Techrappy SEO -- Module Divi
:\</strong\> %s\</p\>\</div\>\', esc_html( \$issue ) ); } } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Helpers //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Vérifie si le hook courant correspond à une page admin
Techrappy. \* \* \@param string \$hook \* \@return bool \*/ private
function is_techrappy_admin_page( string \$hook ): bool { return
str_contains( \$hook, self::ADMIN_PAGE_SLUG ); } /\*\* \* Retourne le
partial HTML du sélecteur Divi (pour inclusion dans la vue). \* \*
\@return string HTML du partial. \*/ public function
render_template_selector(): string { // Prépare les variables pour le
partial \$service = new DiviTemplateService(); \$divi_templates =
\$service-\>get_available_templates(); \$nonce_value =
\$GLOBALS\[\'techrappy_divi_nonce\'\] ?? wp_create_nonce(
\'techrappy_divi_nonce\' ); ob_start(); include TECHRAPPY_SEO_PATH .
\'admin/partials/divi-template-selector.php\'; return ob_get_clean(); }
/\*\* \* Retourne le partial HTML du modal de preview. \* \* \@return
string \*/ public function render_preview_modal(): string { ob_start();
include TECHRAPPY_SEO_PATH . \'admin/partials/divi-preview-modal.php\';
return ob_get_clean(); } } \`\`\` \-\-- \## 2.
DiviCompatibilityChecker.php \`\`\`php \<?php /\*\* \*
DiviCompatibilityChecker \* \* Vérifie que l\'environnement est
compatible avec le module Divi : \* - Divi theme ou plugin actif \* -
Version PHP minimale \* - Post types Divi disponibles \* \* \@package
TechrappySEO\\Modules\\Divi \*/ namespace TechrappySEO\\Modules\\Divi;
defined( \'ABSPATH\' ) \|\| exit; class DiviCompatibilityChecker { /\*\*
\* Version PHP minimale requise. \*/ const MIN_PHP_VERSION = \'8.0\';
/\*\* \* Constante définie par Divi au chargement. \*/ const
DIVI_CONSTANT = \'ET_BUILDER_VERSION\'; /\*\* \* Classe principale du
Divi Builder. \*/ const DIVI_BUILDER_CLASS = \'ET_Builder_Module\';
/\*\* \* Liste des issues détectées. \* \* \@var string\[\] \*/ private
array \$issues = \[\]; /\*\* \* Cache du résultat de vérification. \* \*
\@var bool\|null \*/ private ?bool \$divi_active_cache = null; /\*\* \*
Lance toutes les vérifications et retourne true si tout est OK. \* \*
\@return bool \*/ public function check(): bool { \$this-\>issues =
\[\]; \$this-\>check_php_version(); \$this-\>check_divi_active(); return
empty( \$this-\>issues ); } /\*\* \* Vérifie uniquement si Divi est
actif (version allégée). \* \* \@return bool \*/ public function
is_divi_active(): bool { if ( null !== \$this-\>divi_active_cache ) {
return \$this-\>divi_active_cache; } \$this-\>divi_active_cache = (
defined( self::DIVI_CONSTANT ) \|\| class_exists(
self::DIVI_BUILDER_CLASS ) \|\| \$this-\>is_divi_theme_active() \|\|
\$this-\>is_divi_plugin_active() ); return \$this-\>divi_active_cache; }
/\*\* \* Retourne la liste des issues détectées. \* Lance check() si pas
encore fait. \* \* \@return string\[\] \*/ public function get_issues():
array { if ( empty( \$this-\>issues ) ) { \$this-\>check(); } return
\$this-\>issues; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Vérifications individuelles //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Vérifie la version PHP. \*/ private function
check_php_version(): void { if ( version_compare( PHP_VERSION,
self::MIN_PHP_VERSION, \'\<\' ) ) { \$this-\>issues\[\] = sprintf( \'PHP
%s minimum requis. Version actuelle : %s.\', self::MIN_PHP_VERSION,
PHP_VERSION ); } } /\*\* \* Vérifie que Divi est bien actif. \*/ private
function check_divi_active(): void { if ( ! \$this-\>is_divi_active() )
{ \$this-\>issues\[\] = sprintf( \'Le module Divi de Techrappy SEO
nécessite le thème ou le plugin Divi Builder. \' . \'Divi n\\\'a pas été
détecté sur ce site.\' ); } } /\*\* \* Vérifie si le thème actif est
Divi ou un enfant de Divi. \* \* \@return bool \*/ private function
is_divi_theme_active(): bool { \$theme = wp_get_theme(); return (
\'Divi\' === \$theme-\>get( \'Name\' ) \|\| \'Divi\' === \$theme-\>get(
\'Template\' ) // Thème enfant ); } /\*\* \* Vérifie si le plugin Divi
Builder (standalone) est actif. \* \* \@return bool \*/ private function
is_divi_plugin_active(): bool { if ( ! function_exists(
\'is_plugin_active\' ) ) { require_once ABSPATH .
\'wp-admin/includes/plugin.php\'; } \$divi_plugins = \[
\'divi-builder/divi-builder.php\',
\'elegant-themes-divi-builder/elegant-themes-divi-builder.php\', \];
foreach ( \$divi_plugins as \$plugin ) { if ( is_plugin_active( \$plugin
) ) { return true; } } return false; } /\*\* \* Retourne la version de
Divi si disponible. \* \* \@return string\|null \*/ public function
get_divi_version(): ?string { if ( defined( self::DIVI_CONSTANT ) ) {
return constant( self::DIVI_CONSTANT ); } return null; } } \`\`\` \-\--
\## 3. DiviPreviewBuilder.php \`\`\`php \<?php /\*\* \*
DiviPreviewBuilder \* \* Construit un aperçu HTML du contenu qui sera
injecté dans le template Divi, \* AVANT la création réelle du post.
Permet à l\'utilisateur de valider \* l\'injection avant de persister.
\* \* Le preview extrait les zones clés du contenu Divi (post_content
simulé) \* et les présente sous forme de tableau de substitution. \* \*
\@package TechrappySEO\\Modules\\Divi \*/ namespace
TechrappySEO\\Modules\\Divi; defined( \'ABSPATH\' ) \|\| exit; class
DiviPreviewBuilder { /\*\* \* Service de template Divi. \* \* \@var
DiviTemplateService \*/ private DiviTemplateService \$template_service;
/\*\* \* Données SEO. \* \* \@var array \*/ private array \$seo_data;
/\*\* \* \@param array \$seo_data Données SEO standardisées. \*/ public
function \_\_construct( array \$seo_data ) { \$this-\>seo_data =
\$seo_data; \$this-\>template_service = new DiviTemplateService(); }
/\*\* \* Génère un aperçu des substitutions qui seront effectuées. \*
N\'écrit RIEN en base de données. \* \* \@param int \$template_post_id
ID du template source. \* \@return array { \* \@type bool \$success \*
\@type array \$preview_items Liste d\'items \[ token, value_preview,
source \] \* \@type string \$content_preview Extrait du contenu traité
(300 chars) \* \@type int \$token_count Nombre total de tokens qui
seront remplacés \* \@type int \$missing_count Tokens sans valeur dans
seo_data \* \@type bool \$has_repeatable \* \@type int \$sections_count
Nombre de sections répétables \* \@type array \$errors \* } \*/ public
function build_preview( int \$template_post_id ): array { \$errors =
\[\]; // \-\-- 1. Analyse du template \-\-- \$analysis =
\$this-\>template_service-\>analyze_template( \$template_post_id ); if (
! \$analysis\[\'success\'\] ) { return \[ \'success\' =\> false,
\'errors\' =\> \$analysis\[\'errors\'\], \]; } // \-\-- 2. Simulation de
la substitution (dry-run) \-\-- \$post = get_post( \$template_post_id );
\$post_content = \$post ? \$post-\>post_content : \'\'; // Simulation
sections répétables \$repeatable = new DiviRepeatableSection(
\$this-\>seo_data ); \$simulated = \$repeatable-\>process(
\$post_content ); \$errors = array_merge( \$errors,
\$repeatable-\>get_error_log() ); // Simulation token replacement
\$replacer = new DiviTokenReplacer( \$this-\>seo_data ); \$simulated =
\$replacer-\>replace( \$simulated ); \$errors = array_merge( \$errors,
\$replacer-\>get_error_log() ); // \-\-- 3. Construction des items de
preview \-\-- \$preview_items = \$this-\>build_preview_items(
\$analysis\[\'tokens\'\] ); // Compte les tokens manquants
\$missing_count = count( array_filter( \$preview_items, fn( \$item ) =\>
\$item\[\'status\'\] === \'missing\' ) ); // Extrait du contenu simulé
(nettoyage shortcodes Divi pour lisibilité) \$content_preview =
\$this-\>extract_readable_preview( \$simulated ); // Nombre de sections
\$sections_count = isset( \$this-\>seo_data\[\'sections\'\] ) ? count(
\$this-\>seo_data\[\'sections\'\] ) : 0; return \[ \'success\' =\> true,
\'preview_items\' =\> \$preview_items, \'content_preview\' =\>
\$content_preview, \'token_count\' =\> count( \$analysis\[\'tokens\'\]
), \'missing_count\' =\> \$missing_count, \'has_repeatable\' =\>
\$analysis\[\'has_repeatable\'\], \'sections_count\' =\>
\$sections_count, \'errors\' =\> \$errors, \]; } /\*\* \* Construit la
liste des items de preview (token → valeur). \* \* \@param string\[\]
\$tokens Liste des tokens détectés. \* \@return array \*/ private
function build_preview_items( array \$tokens ): array { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data ); \$items = \[\]; foreach (
\$tokens as \$token ) { // Simule le remplacement d\'un token isolé
\$test_string = \'{{\' . \$token . \'}}\'; \$replaced =
\$replacer-\>replace( \$test_string ); \$was_replaced = \$replaced !==
\$test_string; \$items\[\] = \[ \'token\' =\> \$token, \'value\' =\>
\$was_replaced ? \$this-\>truncate( \$replaced, 120 ) : null, \'status\'
=\> \$was_replaced ? \'ok\' : \'missing\', \]; } // Tokens de section
(dynamiques, non dans la liste statique) \$section_tokens = \[
\'SECTION_TITLE\', \'SECTION_BODY\', \'SECTION_INDEX\' \]; foreach (
\$section_tokens as \$st ) { \$already = array_filter( \$items, fn( \$i
) =\> \$i\[\'token\'\] === \$st ); if ( ! empty( \$already ) ) {
continue; } // Ajoute avec exemple de la première section si disponible
\$first_section = \$this-\>seo_data\[\'sections\'\]\[0\] ?? null; \$map
= \[ \'SECTION_TITLE\' =\> \$first_section\[\'title\'\] ?? null,
\'SECTION_BODY\' =\> \$first_section\[\'body\'\] ?? null,
\'SECTION_INDEX\' =\> \'1\', \]; if ( null !== \$map\[ \$st \] ) {
\$items\[\] = \[ \'token\' =\> \$st, \'value\' =\> \$this-\>truncate(
\$map\[ \$st \], 120 ), \'status\' =\> \'ok\', \]; } } return \$items; }
/\*\* \* Extrait un aperçu lisible du contenu simulé. \* Supprime les
shortcodes Divi et tronque. \* \* \@param string \$content \* \@return
string \*/ private function extract_readable_preview( string \$content
): string { // Supprime les shortcodes Divi (ex: \[et_pb_section
\...\]\[/et_pb_section\]) \$content = preg_replace(
\'/\\\[et_pb\_\[\^\\\]\]\*\\\]/\', \'\', \$content ); \$content =
preg_replace( \'/\\\[\\/et_pb\_\[\^\\\]\]\*\\\]/\', \'\', \$content );
// Supprime les commentaires HTML (marqueurs TECHRAPPY) \$content =
preg_replace( \'/\<!\--.\*?\--\>/s\', \'\', \$content ); // Supprime les
balises HTML pour la preview texte \$content = wp_strip_all_tags(
\$content ); // Normalise les espaces \$content = preg_replace(
\'/\\s+/\', \' \', trim( \$content ) ); return \$this-\>truncate(
\$content, 400 ); } /\*\* \* Tronque une chaîne à N caractères avec
ellipse. \* \* \@param string \$str \* \@param int \$length \* \@return
string \*/ private function truncate( string \$str, int \$length ):
string { if ( mb_strlen( \$str ) \<= \$length ) { return \$str; } return
mb_substr( \$str, 0, \$length ) . \'...\'; } } \`\`\` \-\-- \## 4.
DiviPostCleaner.php \`\`\`php \<?php /\*\* \* DiviPostCleaner \* \*
Nettoie les metas temporaires créées pendant le processus de génération
Divi. \* - Supprime les metas de travail (\_techrappy_temp\_\*) \* -
Normalise les metas Divi post-injection \* - Vide le cache du post \* \*
\@package TechrappySEO\\Modules\\Divi \*/ namespace
TechrappySEO\\Modules\\Divi; defined( \'ABSPATH\' ) \|\| exit; class
DiviPostCleaner { /\*\* \* Préfixe des metas temporaires à supprimer.
\*/ const TEMP_META_PREFIX = \'\_techrappy_temp\_\'; /\*\* \* Metas Divi
à forcer après injection \* (s\'assure que Divi reconnaît la page comme
\"Builder enabled\"). \* \* \@var array\<string, string\> \*/ private
const DIVI_REQUIRED_METAS = \[ \'\_et_pb_use_builder\' =\> \'on\',
\'\_et_pb_page_layout\' =\> \'et_no_sidebar\', \'\_et_pb_side_nav\' =\>
\'off\', \]; /\*\* \* ID du post à nettoyer. \* \* \@var int \*/ private
int \$post_id; /\*\* \* Log des actions effectuées. \* \* \@var array
\*/ private array \$clean_log = \[\]; /\*\* \* \@param int \$post_id \*/
public function \_\_construct( int \$post_id ) { \$this-\>post_id =
\$post_id; } /\*\* \* Lance le nettoyage complet. \* \* \@return array
Log des actions effectuées. \*/ public function run(): array {
\$this-\>clean_temp_metas(); \$this-\>ensure_divi_metas();
\$this-\>flush_cache(); \$this-\>stamp_generation(); return
\$this-\>clean_log; } /\*\* \* Supprime toutes les metas temporaires
(\_techrappy_temp\_\*). \*/ private function clean_temp_metas(): void {
global \$wpdb; // Récupère les clés temporaires via requête préparée
\$temp_keys = \$wpdb-\>get_col( \$wpdb-\>prepare( \"SELECT DISTINCT
meta_key FROM {\$wpdb-\>postmeta} WHERE post_id = %d AND meta_key LIKE
%s\", \$this-\>post_id, \$wpdb-\>esc_like( self::TEMP_META_PREFIX ) .
\'%\' ) ); if ( empty( \$temp_keys ) ) { \$this-\>clean_log\[\] =
\'Aucune meta temporaire à supprimer.\'; return; } foreach ( \$temp_keys
as \$meta_key ) { delete_post_meta( \$this-\>post_id, \$meta_key );
\$this-\>clean_log\[\] = sprintf( \'Meta temporaire supprimée : %s\',
\$meta_key ); } } /\*\* \* S\'assure que les metas Divi requises sont
présentes et correctes. \*/ private function ensure_divi_metas(): void {
foreach ( self::DIVI_REQUIRED_METAS as \$meta_key =\> \$expected_value )
{ \$current = get_post_meta( \$this-\>post_id, \$meta_key, true ); if (
\$current !== \$expected_value ) { update_post_meta( \$this-\>post_id,
\$meta_key, \$expected_value ); \$this-\>clean_log\[\] = sprintf( \'Meta
Divi mise à jour : %s = %s\', \$meta_key, \$expected_value ); } } }
/\*\* \* Vide le cache WordPress du post. \*/ private function
flush_cache(): void { clean_post_cache( \$this-\>post_id );
\$this-\>clean_log\[\] = sprintf( \'Cache vidé pour le post ID %d.\',
\$this-\>post_id ); } /\*\* \* Ajoute un tampon de génération avec
horodatage. \*/ private function stamp_generation(): void {
update_post_meta( \$this-\>post_id, \'\_techrappy_cleaned_at\',
current_time( \'mysql\' ) ); \$this-\>clean_log\[\] = \'Tampon de
génération appliqué.\'; } } \`\`\` \-\-- \## 5. DiviAjaxHandler.php ---
Version complète avec preview \`\`\`php \<?php /\*\* \* DiviAjaxHandler
--- Version complète \* \* Ajoute l\'action AJAX de preview aux 3
actions du run précédent. \* Les 3 actions existantes sont reconduites
telles quelles. \* \* \@package TechrappySEO\\Modules\\Divi \*/
namespace TechrappySEO\\Modules\\Divi; defined( \'ABSPATH\' ) \|\| exit;
class DiviAjaxHandler { const REQUIRED_CAP = \'manage_options\'; /\*\*
\* Enregistre tous les hooks AJAX. \*/ public function register(): void
{ add_action( \'wp_ajax_techrappy_divi_get_templates\', \[ \$this,
\'ajax_get_templates\' \] ); add_action(
\'wp_ajax_techrappy_divi_analyze_template\', \[ \$this,
\'ajax_analyze_template\' \] ); add_action(
\'wp_ajax_techrappy_divi_create_from_template\', \[ \$this,
\'ajax_create_from_template\' \] ); add_action(
\'wp_ajax_techrappy_divi_preview\', \[ \$this, \'ajax_preview\' \] ); //
🆕 } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// AJAX : Liste des templates //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
public function ajax_get_templates(): void { \$this-\>verify_nonce(
\'techrappy_divi_nonce\' ); \$this-\>check_capability(); \$service = new
DiviTemplateService(); \$templates =
\$service-\>get_available_templates(); wp_send_json_success( \[
\'templates\' =\> \$templates \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// AJAX : Analyse du template //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
public function ajax_analyze_template(): void { \$this-\>verify_nonce(
\'techrappy_divi_nonce\' ); \$this-\>check_capability(); \$template_id =
isset( \$\_POST\[\'template_id\'\] ) ? absint(
\$\_POST\[\'template_id\'\] ) : 0; if ( ! \$template_id ) {
wp_send_json_error( \[ \'message\' =\> \'template_id manquant ou
invalide.\' \], 400 ); } \$service = new DiviTemplateService(); \$result
= \$service-\>analyze_template( \$template_id ); if ( !
\$result\[\'success\'\] ) { wp_send_json_error( \$result, 422 ); }
wp_send_json_success( \$result ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// AJAX : Preview (dry-run) 🆕 //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* AJAX : Génère un aperçu des substitutions SANS créer de post.
\* \* POST params: \* - nonce string Nonce. \* - template_id int ID du
template. \* - seo_data string JSON SEO. \*/ public function
ajax_preview(): void { \$this-\>verify_nonce( \'techrappy_divi_nonce\'
); \$this-\>check_capability(); \$template_id = isset(
\$\_POST\[\'template_id\'\] ) ? absint( \$\_POST\[\'template_id\'\] ) :
0; if ( ! \$template_id ) { wp_send_json_error( \[ \'message\' =\>
\'template_id manquant.\' \], 400 ); } \$seo_data_raw = isset(
\$\_POST\[\'seo_data\'\] ) ? wp_unslash( \$\_POST\[\'seo_data\'\] ) :
\'\'; \$seo_data = json_decode( \$seo_data_raw, true ); if ( ! is_array(
\$seo_data ) ) { wp_send_json_error( \[ \'message\' =\> \'seo_data
invalide.\' \], 400 ); } \$preview_builder = new DiviPreviewBuilder(
\$seo_data ); \$result = \$preview_builder-\>build_preview(
\$template_id ); if ( ! \$result\[\'success\'\] ) { wp_send_json_error(
\$result, 422 ); } wp_send_json_success( \$result ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// AJAX : Création depuis template //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
public function ajax_create_from_template(): void {
\$this-\>verify_nonce( \'techrappy_divi_nonce\' );
\$this-\>check_capability(); \$template_id = isset(
\$\_POST\[\'template_id\'\] ) ? absint( \$\_POST\[\'template_id\'\] ) :
0; if ( ! \$template_id ) { wp_send_json_error( \[ \'message\' =\>
\'template_id manquant.\' \], 400 ); } \$seo_data_raw = isset(
\$\_POST\[\'seo_data\'\] ) ? wp_unslash( \$\_POST\[\'seo_data\'\] ) :
\'\'; \$seo_data = json_decode( \$seo_data_raw, true ); if ( ! is_array(
\$seo_data ) \|\| empty( \$seo_data ) ) { wp_send_json_error( \[
\'message\' =\> \'seo_data invalide ou vide.\' \], 400 ); }
\$allowed_statuses = \[ \'draft\', \'publish\', \'private\' \];
\$post_status = isset( \$\_POST\[\'post_status\'\] ) ? sanitize_key(
\$\_POST\[\'post_status\'\] ) : \'draft\'; if ( ! in_array(
\$post_status, \$allowed_statuses, true ) ) { \$post_status = \'draft\';
} \$service = new DiviTemplateService(); \$result =
\$service-\>create_from_template( \$template_id, \$seo_data, \[
\'post_status\' =\> \$post_status, \'source_template_id\' =\>
\$template_id, \] ); if ( ! \$result\[\'success\'\] ) {
wp_send_json_error( \$result, 422 ); } // Déclenche le hook
post-création do_action( \'techrappy_seo_after_post_created\',
\$result\[\'post_id\'\], \[ \'method\' =\> \'divi_template\',
\'source_template_id\' =\> \$template_id, \] ); wp_send_json_success(
\$result ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Sécurité //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
private function verify_nonce( string \$action ): void { \$nonce =
isset( \$\_POST\[\'nonce\'\] ) ? sanitize_text_field( wp_unslash(
\$\_POST\[\'nonce\'\] ) ) : \'\'; if ( ! wp_verify_nonce( \$nonce,
\$action ) ) { wp_send_json_error( \[ \'message\' =\> \'Nonce
invalide.\' \], 403 ); } } private function check_capability(): void {
if ( ! current_user_can( self::REQUIRED_CAP ) ) { wp_send_json_error( \[
\'message\' =\> \'Permission refusée.\' \], 403 ); } } } \`\`\` \-\--
\## 6. Partial --- \`divi-preview-modal.php\` \`\`\`php \<?php /\*\* \*
Partial : Modal de preview des substitutions Divi \* \* Affiché avant la
création du post, en mode \"dry-run\". \* Rempli dynamiquement via JS
(renderPreviewModal). \* \* \@package TechrappySEO\\Admin \*/ defined(
\'ABSPATH\' ) \|\| exit; ?\> \<div id=\"techrappy-divi-preview-modal\"
class=\"techrappy-modal\" role=\"dialog\" aria-modal=\"true\"
aria-labelledby=\"techrappy-modal-title\" style=\"display:none;\" \>
\<div class=\"techrappy-modal\_\_overlay\"
id=\"techrappy-modal-overlay\"\>\</div\> \<div
class=\"techrappy-modal\_\_container\"\> \<!\-- Header \--\> \<div
class=\"techrappy-modal\_\_header\"\> \<h2 id=\"techrappy-modal-title\"
class=\"techrappy-modal\_\_title\"\> \<?php esc_html_e( \'👁️
Prévisualisation de l\\\'injection\', \'techrappy-seo\' ); ?\> \</h2\>
\<button type=\"button\" class=\"techrappy-modal\_\_close\"
id=\"techrappy-modal-close\" aria-label=\"\<?php esc_attr_e( \'Fermer\',
\'techrappy-seo\' ); ?\>\" \>✕\</button\> \</div\> \<!\-- Stats rapides
\--\> \<div class=\"techrappy-modal\_\_stats\"
id=\"techrappy-modal-stats\"\> \<!\-- Rempli via JS \--\> \</div\>
\<!\-- Table des tokens \--\> \<div class=\"techrappy-modal\_\_body\"\>
\<h3\>\<?php esc_html_e( \'Substitutions de tokens\', \'techrappy-seo\'
); ?\>\</h3\> \<table class=\"techrappy-preview-table wp-list-table
widefat fixed striped\"\> \<thead\> \<tr\> \<th\>\<?php esc_html_e(
\'Token\', \'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php esc_html_e(
\'Valeur injectée\', \'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php
esc_html_e( \'Statut\', \'techrappy-seo\' ); ?\>\</th\> \</tr\>
\</thead\> \<tbody id=\"techrappy-preview-table-body\"\> \<!\-- Rempli
via JS \--\> \</tbody\> \</table\> \<!\-- Aperçu texte du contenu \--\>
\<h3\>\<?php esc_html_e( \'Extrait du contenu traité\',
\'techrappy-seo\' ); ?\>\</h3\> \<div
class=\"techrappy-content-preview\" id=\"techrappy-content-preview\"\>
\<!\-- Rempli via JS \--\> \</div\> \</div\> \<!\-- Footer actions \--\>
\<div class=\"techrappy-modal\_\_footer\"\> \<button type=\"button\"
id=\"techrappy-modal-cancel\" class=\"button button-secondary\" \>
\<?php esc_html_e( \'← Modifier\', \'techrappy-seo\' ); ?\> \</button\>
\<button type=\"button\" id=\"techrappy-modal-confirm\" class=\"button
button-primary\" data-post-status=\"draft\" \> \<?php esc_html_e( \'🚀
Confirmer et créer\', \'techrappy-seo\' ); ?\> \</button\> \</div\>
\</div\>\<!\-- /.techrappy-modal\_\_container \--\> \</div\>\<!\--
/#techrappy-divi-preview-modal \--\> \`\`\` \-\-- \## 7. JavaScript
complet --- \`divi-template-admin.js\` \`\`\`javascript /\*\* \*
divi-template-admin.js --- Version complète \* \* Gestion complète de
l\'UI Divi Builder dans l\'admin Techrappy SEO : \* - Sélection du
template \* - Analyse des tokens \* - Preview dry-run (modal) \* -
Création du post avec feedback \* \* \@package TechrappySEO \*/ /\*
global jQuery, techrappyDiviData \*/ (function (\$) { \'use strict\'; //
=========================================================================
// Configuration //
=========================================================================
const CFG = { ajaxUrl : techrappyDiviData.ajaxUrl, nonce :
techrappyDiviData.nonce, actions : { getTemplates :
\'techrappy_divi_get_templates\', analyze :
\'techrappy_divi_analyze_template\', preview :
\'techrappy_divi_preview\', create :
\'techrappy_divi_create_from_template\', }, i18n :
techrappyDiviData.i18n \|\| {}, diviActive :
techrappyDiviData.diviActive === \'1\', }; //
=========================================================================
// Sélecteurs DOM //
=========================================================================
const SEL = { section : \'#techrappy-divi-section\', templateSelect :
\'#techrappy-divi-template-select\', analyzeBtn :
\'#techrappy-divi-analyze-btn\', previewBtn :
\'#techrappy-divi-preview-btn\', analysisResult :
\'#techrappy-divi-analysis-result\', tokenList :
\'#techrappy-divi-token-list\', repeatableNotice :
\'#techrappy-divi-repeatable-notice\', createOptions :
\'#techrappy-divi-create-options\', createBtn :
\'#techrappy-divi-create-btn\', postStatus :
\'#techrappy-divi-post-status\', createResult :
\'#techrappy-divi-create-result\', loader : \'#techrappy-divi-loader\',
nonce : \'#techrappy-divi-nonce\', // Modal modal :
\'#techrappy-divi-preview-modal\', modalOverlay :
\'#techrappy-modal-overlay\', modalClose : \'#techrappy-modal-close\',
modalCancel : \'#techrappy-modal-cancel\', modalConfirm :
\'#techrappy-modal-confirm\', modalStats : \'#techrappy-modal-stats\',
modalTableBody : \'#techrappy-preview-table-body\', modalContentPrev :
\'#techrappy-content-preview\', // Badges dynamiques tokenCountBadge :
\'#techrappy-token-count\', missingBadge : \'#techrappy-missing-count\',
}; //
=========================================================================
// État interne //
=========================================================================
const state = { selectedTemplateId : null, analysisData : null, seoData
: null, previewData : null, isLoading : false, }; //
=========================================================================
// Init //
=========================================================================
function init() { if ( ! CFG.diviActive ) { showDiviInactiveNotice();
return; } bindEvents(); exposePublicAPI(); } //
=========================================================================
// Bind events //
=========================================================================
function bindEvents() { // Sélection d\'un template → active le bouton
Analyser \$(document).on(\'change\', SEL.templateSelect,
onTemplateChange); // Bouton Analyser \$(document).on(\'click\',
SEL.analyzeBtn, onAnalyzeClick); // Bouton Preview
\$(document).on(\'click\', SEL.previewBtn, onPreviewClick); // Bouton
Créer (direct, sans preview) \$(document).on(\'click\', SEL.createBtn,
onCreateClick); // Modal : fermeture \$(document).on(\'click\',
SEL.modalClose, closeModal); \$(document).on(\'click\', SEL.modalCancel,
closeModal); \$(document).on(\'click\', SEL.modalOverlay, closeModal);
// Modal : confirmation création \$(document).on(\'click\',
SEL.modalConfirm, onModalConfirm); // Fermeture modal avec Escape
\$(document).on(\'keydown\', function (e) { if (e.key === \'Escape\' &&
isModalOpen()) { closeModal(); } }); } //
=========================================================================
// Handlers //
=========================================================================
function onTemplateChange() { const templateId =
\$(SEL.templateSelect).val(); state.selectedTemplateId = templateId ?
parseInt(templateId, 10) : null; const hasTemplate = !!
state.selectedTemplateId; \$(SEL.analyzeBtn).prop(\'disabled\', !
hasTemplate); // Reset complet si on change de template
resetAnalysisUI(); resetCreateUI(); } function onAnalyzeClick() { if
(state.isLoading \|\| ! state.selectedTemplateId) return;
analyzeTemplate(state.selectedTemplateId); } function onPreviewClick() {
if (state.isLoading \|\| ! state.selectedTemplateId) return; if (!
state.seoData) { showInlineError(CFG.i18n.noSeoData \|\| \'Données SEO
manquantes.\'); return; } buildPreview(state.selectedTemplateId,
state.seoData); } function onCreateClick() { if (state.isLoading \|\| !
state.selectedTemplateId) return; if (! state.seoData) {
showInlineError(CFG.i18n.noSeoData \|\| \'Données SEO manquantes.\');
return; } const postStatus = \$(SEL.postStatus).val() \|\| \'draft\';
createFromTemplate(state.selectedTemplateId, state.seoData, postStatus);
} function onModalConfirm() { const postStatus =
\$(SEL.postStatus).val() \|\| \'draft\'; closeModal();
createFromTemplate(state.selectedTemplateId, state.seoData, postStatus);
} //
=========================================================================
// Analyse template //
=========================================================================
function analyzeTemplate(templateId) { setLoading(true); \$.ajax({ url :
CFG.ajaxUrl, type : \'POST\', data : { action : CFG.actions.analyze,
nonce : getNonce(), template_id : templateId, }, success : function
(res) { setLoading(false); if (res.success) { state.analysisData =
res.data; renderAnalysisResult(res.data); maybeShowCreateOptions(); }
else { showInlineError(res.data?.message \|\| CFG.i18n.errorGeneric); }
}, error : function (xhr, status, error) { setLoading(false);
showInlineError(CFG.i18n.errorGeneric);
console.error(\'\[TechrappyDivi\] analyzeTemplate:\', error,
xhr.responseText); }, }); } function renderAnalysisResult(data) { const
\$list = \$(SEL.tokenList); \$list.empty(); if (!data.tokens \|\|
data.tokens.length === 0) { \$list.html(buildNotice(\'warning\', \'⚠️
Aucun token {{TOKEN}} détecté dans ce template. \' + \'Vérifiez la
syntaxe des tokens dans votre template Divi.\' )); } else { const \$ul =
\$(\'\<ul class=\"techrappy-token-tags\"\>\</ul\>\');
data.tokens.forEach(function (token) { \$ul.append(
\$(\'\<li\>\</li\>\').append( \$(\'\<code
class=\"techrappy-token-tag\"\>\</code\>\') .text(\'{{\' + token +
\'}}\') ) ); }); // Compteur const count = data.tokens.length;
\$list.append( \$(\'\<p class=\"techrappy-token-count\"\>\</p\>\')
.text(count + \' token\' + (count \> 1 ? \'s\' : \'\') + \' détecté\' +
(count \> 1 ? \'s\' : \'\') + \'.\') ); \$list.append(\$ul); } // Notice
bloc répétable if (data.has_repeatable) {
\$(SEL.repeatableNotice).slideDown(150); } else {
\$(SEL.repeatableNotice).hide(); }
\$(SEL.analysisResult).slideDown(200); } //
=========================================================================
// Preview (dry-run) //
=========================================================================
function buildPreview(templateId, seoData) { setLoading(true); \$.ajax({
url : CFG.ajaxUrl, type : \'POST\', data : { action :
CFG.actions.preview, nonce : getNonce(), template_id : templateId,
seo_data : JSON.stringify(seoData), }, success : function (res) {
setLoading(false); if (res.success) { state.previewData = res.data;
openPreviewModal(res.data); } else { showInlineError(res.data?.message
\|\| CFG.i18n.errorGeneric); } }, error : function (xhr, status, error)
{ setLoading(false); showInlineError(CFG.i18n.errorGeneric);
console.error(\'\[TechrappyDivi\] buildPreview:\', error,
xhr.responseText); }, }); } //
=========================================================================
// Modal de preview //
=========================================================================
function openPreviewModal(data) { renderModalStats(data);
renderModalTable(data.preview_items \|\| \[\]);
renderModalContentPreview(data.content_preview \|\| \'\');
\$(SEL.modal).fadeIn(200);
\$(\'body\').addClass(\'techrappy-modal-open\'); // Focus trap sur le
modal \$(SEL.modalClose).focus(); } function closeModal() {
\$(SEL.modal).fadeOut(150);
\$(\'body\').removeClass(\'techrappy-modal-open\'); } function
isModalOpen() { return \$(SEL.modal).is(\':visible\'); } function
renderModalStats(data) { const tokenCount = data.token_count \|\| 0;
const missingCount = data.missing_count \|\| 0; const sections =
data.sections_count \|\| 0; const okCount = tokenCount - missingCount;
const statsHtml = \` \<div class=\"techrappy-stats-grid\"\> \<div
class=\"techrappy-stat techrappy-stat\--ok\"\> \<span
class=\"techrappy-stat\_\_value\"\>\${okCount}\</span\> \<span
class=\"techrappy-stat\_\_label\"\>Tokens résolus\</span\> \</div\>
\<div class=\"techrappy-stat \${missingCount \> 0 ?
\'techrappy-stat\--warn\' : \'techrappy-stat\--ok\'}\"\> \<span
class=\"techrappy-stat\_\_value\"\>\${missingCount}\</span\> \<span
class=\"techrappy-stat\_\_label\"\>Tokens manquants\</span\> \</div\>
\${data.has_repeatable ? \` \<div class=\"techrappy-stat
techrappy-stat\--info\"\> \<span
class=\"techrappy-stat\_\_value\"\>\${sections}\</span\> \<span
class=\"techrappy-stat\_\_label\"\>Section(s) répétable(s)\</span\>
\</div\>\` : \'\'} \</div\> \`; \$(SEL.modalStats).html(statsHtml); }
function renderModalTable(items) { const \$tbody =
\$(SEL.modalTableBody); \$tbody.empty(); if (items.length === 0) {
\$tbody.append( \'\<tr\>\<td colspan=\"3\"
style=\"text-align:center;\"\>Aucun token à afficher.\</td\>\</tr\>\' );
return; } items.forEach(function (item) { const statusClass =
item.status === \'ok\' ? \'techrappy-badge techrappy-badge\--ok\' :
\'techrappy-badge techrappy-badge\--error\'; const statusLabel =
item.status === \'ok\' ? \'✅ Résolu\' : \'❌ Manquant\'; const
valueCell = item.value ? \'\<code
class=\"techrappy-preview-value\"\>\' + escHtml(item.value) +
\'\</code\>\' : \'\<em class=\"techrappy-muted\"\>---\</em\>\';
\$tbody.append(\` \<tr class=\"\${item.status === \'missing\' ?
\'techrappy-row\--warn\' : \'\'}\"\> \<td\>\<code
class=\"techrappy-token-tag\"\>{{\${escHtml(item.token)}}}\</code\>\</td\>
\<td\>\${valueCell}\</td\> \<td\>\<span
class=\"\${statusClass}\"\>\${statusLabel}\</span\>\</td\> \</tr\> \`);
}); } function renderModalContentPreview(preview) { const \$el =
\$(SEL.modalContentPrev); if (preview) { \$el.html(\'\<blockquote
class=\"techrappy-blockquote\"\>\' + escHtml(preview) +
\'\</blockquote\>\'); } else { \$el.html(\'\<p
class=\"techrappy-muted\"\>Aperçu non disponible.\</p\>\'); } } //
=========================================================================
// Création depuis template //
=========================================================================
function createFromTemplate(templateId, seoData, postStatus) {
setLoading(true); resetCreateUI(); \$.ajax({ url : CFG.ajaxUrl, type :
\'POST\', data : { action : CFG.actions.create, nonce : getNonce(),
template_id : templateId, seo_data : JSON.stringify(seoData),
post_status : postStatus, }, success : function (res) {
setLoading(false); if (res.success) { renderCreateSuccess(res.data); }
else { const msg = res.data?.message \|\| (res.data?.errors \|\|
\[\]).join(\' \| \') \|\| CFG.i18n.errorGeneric; showInlineError(msg); }
}, error : function (xhr, status, error) { setLoading(false);
showInlineError(CFG.i18n.errorGeneric);
console.error(\'\[TechrappyDivi\] createFromTemplate:\', error,
xhr.responseText); }, }); } function renderCreateSuccess(data) { let
html = buildNotice(\'success\', \`\<strong\>✅
\${CFG.i18n.successCreated \|\| \'Page créée avec succès
!\'}\</strong\>\<br/\> \<div class=\"techrappy-action-links\"\> \<a
href=\"\${escHtml(data.edit_url)}\" target=\"\_blank\" rel=\"noopener\"
class=\"button button-secondary\"\> ✏️ \${CFG.i18n.editInDivi \|\|
\'Éditer dans Divi\'} \</a\> &nbsp; \<a
href=\"\${escHtml(data.preview_url)}\" target=\"\_blank\"
rel=\"noopener\" class=\"button\"\> 👁️ \${CFG.i18n.preview \|\|
\'Prévisualiser\'} \</a\> \</div\>\` ); if (data.warnings &&
data.warnings.length \> 0) { const warnItems = data.warnings.map(w =\>
\`\<li\>\${escHtml(w)}\</li\>\`).join(\'\'); html +=
buildNotice(\'warning\', \`\<strong\>⚠️ Avertissements
:\</strong\>\<ul\>\${warnItems}\</ul\>\` ); } if (data.errors &&
data.errors.length \> 0) { const errItems = data.errors.map(e =\>
\`\<li\>\${escHtml(e)}\</li\>\`).join(\'\'); html +=
buildNotice(\'error\', \`\<strong\>❌ Erreurs non-bloquantes
:\</strong\>\<ul\>\${errItems}\</ul\>\` ); }
\$(SEL.createResult).html(html).slideDown(200); // Scroll vers le
résultat \$(\'html, body\').animate({ scrollTop:
\$(SEL.createResult).offset().top - 40, }, 400); } //
=========================================================================
// API publique (interface avec l\'écran principal) //
=========================================================================
function exposePublicAPI() { window.TechrappyDivi = { /\*\* \* Pousse
les données SEO générées par le moteur principal. \* À appeler depuis
l\'écran \"Générer\" après une génération réussie. \* \* \@param
{Object} seoData Données SEO au format JSON standardisé. \*/ setSeoData
: function (seoData) { state.seoData = seoData;
maybeShowCreateOptions(); console.info(\'\[TechrappyDivi\] seoData reçu
:\', seoData); }, /\*\* \* Réinitialise complètement le module (ex:
nouvelle génération). \*/ reset : function () { state.seoData = null;
state.analysisData = null; state.previewData = null;
state.selectedTemplateId = null; \$(SEL.templateSelect).val(\'\');
\$(SEL.analyzeBtn).prop(\'disabled\', true); resetAnalysisUI();
resetCreateUI(); }, /\*\* \* Retourne l\'état courant (debug). \*/
getState : () =\> ({ \...state }), }; } //
=========================================================================
// Helpers UI //
=========================================================================
function maybeShowCreateOptions() { if (state.seoData &&
state.analysisData) { \$(SEL.createOptions).slideDown(200); } } function
resetAnalysisUI() { \$(SEL.analysisResult).hide();
\$(SEL.tokenList).empty(); \$(SEL.repeatableNotice).hide();
state.analysisData = null; } function resetCreateUI() {
\$(SEL.createResult).hide().empty(); \$(SEL.createOptions).hide(); }
function setLoading(active) { state.isLoading = active; if (active) {
\$(SEL.loader).show(); \$(SEL.analyzeBtn).prop(\'disabled\', true);
\$(SEL.createBtn).prop(\'disabled\', true);
\$(SEL.previewBtn).prop(\'disabled\', true); } else {
\$(SEL.loader).hide(); const hasTemplate = !! state.selectedTemplateId;
\$(SEL.analyzeBtn).prop(\'disabled\', ! hasTemplate);
\$(SEL.createBtn).prop(\'disabled\', false);
\$(SEL.previewBtn).prop(\'disabled\', ! state.seoData); } } function
showInlineError(message) { \$(SEL.createResult)
.html(buildNotice(\'error\', \'❌ \' + escHtml(message)))
.slideDown(200); } function showDiviInactiveNotice() {
\$(SEL.section).append( buildNotice(\'warning\', \'⚠️ \' +
(CFG.i18n.diviNotActive \|\| \'Divi n\\\'est pas actif sur ce site.\') )
); } /\*\* \* Construit un bloc notice HTML. \* \* \@param {string} type
\'success\' \| \'warning\' \| \'error\' \| \'info\' \* \@param {string}
content Contenu HTML (déjà échappé si nécessaire). \* \@returns {string}
\*/ function buildNotice(type, content) { return \`\<div
class=\"techrappy-notice
techrappy-notice\--\${type}\"\>\${content}\</div\>\`; } /\*\* \* Échappe
les caractères HTML. \* \* \@param {string} str \* \@returns {string}
\*/ function escHtml(str) { if (str == null) return \'\'; return
String(str) .replace(/&/g, \'&amp;\') .replace(/\</g, \'&lt;\')
.replace(/\>/g, \'&gt;\') .replace(/\"/g, \'&quot;\') .replace(/\'/g,
\'&#039;\'); } function getNonce() { return \$(SEL.nonce).val() \|\|
CFG.nonce; } //
=========================================================================
// Bootstrap //
=========================================================================
\$(document).ready(init); }(jQuery)); \`\`\` \-\-- \## 8. CSS complet
--- \`divi-admin.css\` \`\`\`css /\*\* \* divi-admin.css \* Styles du
module Divi Builder --- Techrappy SEO Admin \* \* Namespacing :
.techrappy-\* pour éviter les collisions WordPress \* \* \@package
TechrappySEO \*/ /\*
============================================================ Variables
CSS ============================================================ \*/
:root { \--tr-primary : #2271b1; \--tr-primary-dark : #135e96;
\--tr-success : #00a32a; \--tr-success-bg : #edfaef; \--tr-warning :
#dba617; \--tr-warning-bg : #fef8ee; \--tr-error : #d63638;
\--tr-error-bg : #fef0f0; \--tr-info : #2271b1; \--tr-info-bg : #f0f6fc;
\--tr-border : #c3c4c7; \--tr-border-light : #e0e0e0; \--tr-bg-light :
#f6f7f7; \--tr-text : #1d2327; \--tr-text-muted : #646970; \--tr-radius
: 6px; \--tr-radius-sm : 3px; \--tr-shadow : 0 1px 3px rgba(0, 0, 0,
.12); \--tr-shadow-modal : 0 8px 32px rgba(0, 0, 0, .22);
\--tr-font-mono : \'SFMono-Regular\', Consolas, \'Liberation Mono\',
Menlo, monospace; \--tr-transition : .18s ease; } /\*
============================================================ Carte
principale ============================================================
\*/ .techrappy-divi-section { background : #fff; border : 1px solid
var(\--tr-border); border-left : 4px solid var(\--tr-primary);
border-radius : var(\--tr-radius); padding : 24px; margin : 20px 0;
box-shadow : var(\--tr-shadow); } .techrappy-card\_\_title { font-size :
1.1rem; font-weight : 600; color : var(\--tr-text); margin : 0 0 8px;
display : flex; align-items : center; gap : 6px; }
.techrappy-card\_\_description { color : var(\--tr-text-muted);
font-size : .9rem; margin : 0 0 20px; line-height : 1.5; } /\*
============================================================ Formulaire
============================================================ \*/
.techrappy-form-group { margin-bottom : 16px; } .techrappy-form-group
label { display : block; font-weight : 500; margin-bottom : 6px;
font-size : .9rem; color : var(\--tr-text); } .techrappy-select { width
: 100%; max-width : 480px; padding : 8px 10px; border : 1px solid
var(\--tr-border); border-radius : var(\--tr-radius-sm); font-size :
.9rem; background : #fff; color : var(\--tr-text); transition :
border-color var(\--tr-transition); } .techrappy-select:focus {
border-color : var(\--tr-primary); outline : 2px solid rgba(34, 113,
177, .25); outline-offset : 1px; } .techrappy-form-actions { display :
flex; gap : 10px; flex-wrap : wrap; margin-top : 16px; } /\*
============================================================ Séparateur
============================================================ \*/
.techrappy-divider { border : none; border-top : 1px solid
var(\--tr-border-light); margin : 24px 0; } /\*
============================================================ Notices
============================================================ \*/
.techrappy-notice { padding : 12px 16px; border-radius :
var(\--tr-radius-sm); border-left : 4px solid transparent; margin : 12px
0; font-size : .9rem; line-height : 1.55; } .techrappy-notice\--success
{ background : var(\--tr-success-bg); border-color : var(\--tr-success);
color : #1a5e28; } .techrappy-notice\--warning { background :
var(\--tr-warning-bg); border-color : var(\--tr-warning); color :
#6b4e00; } .techrappy-notice\--error { background : var(\--tr-error-bg);
border-color : var(\--tr-error); color : #7b1315; }
.techrappy-notice\--info { background : var(\--tr-info-bg); border-color
: var(\--tr-info); color : #0a3a5c; } .techrappy-notice ul { margin :
6px 0 0 16px; padding : 0; list-style : disc; } .techrappy-notice li {
margin-bottom : 3px; } /\*
============================================================ Zone
résultat analyse
============================================================ \*/
.techrappy-analysis-result { background : var(\--tr-bg-light); border :
1px solid var(\--tr-border-light); border-radius : var(\--tr-radius);
padding : 16px 20px; margin-top : 16px; } .techrappy-analysis-result h4
{ margin : 0 0 10px; font-size : .9rem; font-weight : 600; color :
var(\--tr-text); } .techrappy-token-count { font-size : .85rem; color :
var(\--tr-text-muted); margin : 0 0 8px; } /\*
============================================================ Tags de
tokens ============================================================ \*/
.techrappy-token-tags { display : flex; flex-wrap : wrap; gap : 6px;
list-style : none; margin : 0; padding : 0; } .techrappy-token-tags li {
display : block; } .techrappy-token-tag { display : inline-block;
background : #e8f0fa; color : var(\--tr-primary-dark); border : 1px
solid #b8d0ec; border-radius : 3px; padding : 2px 7px; font-family :
var(\--tr-font-mono); font-size : .8rem; cursor : default; transition :
background var(\--tr-transition); } .techrappy-token-tag:hover {
background : #c8dcf5; } /\*
============================================================ Loader
============================================================ \*/
.techrappy-loader { display : flex; align-items : center; gap : 10px;
padding : 12px 0; font-size : .9rem; color : var(\--tr-text-muted); }
.techrappy-loader .spinner { float : none; margin : 0; } /\*
============================================================ Liens
d\'action (après création)
============================================================ \*/
.techrappy-action-links { display : flex; flex-wrap : wrap; gap : 8px;
margin-top : 10px; } /\*
============================================================ Modal
============================================================ \*/
.techrappy-modal { position : fixed; inset : 0; z-index : 999999;
display : flex; align-items : center; justify-content : center; }
.techrappy-modal\_\_overlay { position : absolute; inset : 0; background
: rgba(0, 0, 0, .55); cursor : pointer; } body.techrappy-modal-open {
overflow : hidden; } .techrappy-modal\_\_container { position :
relative; z-index : 1; background : #fff; border-radius :
var(\--tr-radius); box-shadow : var(\--tr-shadow-modal); width : 90%;
max-width : 860px; max-height : 90vh; display : flex; flex-direction :
column; overflow : hidden; } /\* Header \*/ .techrappy-modal\_\_header {
display : flex; align-items : center; justify-content : space-between;
padding : 18px 24px; border-bottom : 1px solid var(\--tr-border-light);
background : var(\--tr-bg-light); flex-shrink : 0; }
.techrappy-modal\_\_title { font-size : 1rem; font-weight : 600; margin
: 0; color : var(\--tr-text); } .techrappy-modal\_\_close { background :
none; border : 1px solid transparent; border-radius :
var(\--tr-radius-sm); font-size : 1.1rem; cursor : pointer; color :
var(\--tr-text-muted); padding : 2px 8px; line-height : 1; transition :
background var(\--tr-transition), color var(\--tr-transition); }
.techrappy-modal\_\_close:hover { background : var(\--tr-error-bg);
color : var(\--tr-error); border-color : var(\--tr-error); } /\* Stats
\*/ .techrappy-modal\_\_stats { padding : 16px 24px; border-bottom : 1px
solid var(\--tr-border-light); flex-shrink : 0; } .techrappy-stats-grid
{ display : flex; gap : 16px; flex-wrap : wrap; } .techrappy-stat { flex
: 1; min-width : 100px; text-align : center; padding : 12px;
border-radius : var(\--tr-radius); border : 1px solid transparent; }
.techrappy-stat\--ok { background : var(\--tr-success-bg); border-color
: #b3e6bf; } .techrappy-stat\--warn { background :
var(\--tr-warning-bg); border-color : #f0d57b; } .techrappy-stat\--info
{ background : var(\--tr-info-bg); border-color : #b3cfe6; }
.techrappy-stat\_\_value { display : block; font-size : 1.8rem;
font-weight : 700; color : var(\--tr-text); line-height : 1; }
.techrappy-stat\_\_label { display : block; font-size : .78rem; color :
var(\--tr-text-muted); margin-top : 4px; } /\* Body scrollable \*/
.techrappy-modal\_\_body { padding : 20px 24px; overflow-y : auto; flex
: 1; } .techrappy-modal\_\_body h3 { font-size : .9rem; font-weight :
600; color : var(\--tr-text); margin : 0 0 10px; }
.techrappy-modal\_\_body h3 + \* { margin-top : 0; } /\* Table preview
\*/ .techrappy-preview-table { width : 100%; font-size : .85rem;
margin-bottom : 20px; border-collapse : collapse; }
.techrappy-preview-table th { background : var(\--tr-bg-light);
font-weight : 600; font-size : .8rem; text-transform : uppercase;
letter-spacing : .05em; padding : 8px 12px; \-\-- \## 8. CSS (suite) ---
\`divi-admin.css\` \`\`\`css color : var(\--tr-text-muted);
border-bottom : 2px solid var(\--tr-border); } .techrappy-preview-table
td { padding : 8px 12px; vertical-align : top; border-bottom : 1px solid
var(\--tr-border-light); } .techrappy-row\--warn td { background :
var(\--tr-warning-bg); } .techrappy-preview-value { font-family :
var(\--tr-font-mono); font-size : .8rem; background :
var(\--tr-bg-light); border : 1px solid var(\--tr-border-light);
border-radius : var(\--tr-radius-sm); padding : 2px 6px; word-break :
break-word; display : inline-block; max-width : 320px; } /\*
============================================================ Badges
statut ============================================================ \*/
.techrappy-badge { display : inline-flex; align-items : center; gap :
4px; padding : 2px 8px; border-radius : 20px; font-size : .78rem;
font-weight : 600; white-space : nowrap; } .techrappy-badge\--ok {
background : var(\--tr-success-bg); color : #1a5e28; border : 1px solid
#b3e6bf; } .techrappy-badge\--error { background : var(\--tr-error-bg);
color : var(\--tr-error); border : 1px solid #f5b8b8; }
.techrappy-badge\--warn { background : var(\--tr-warning-bg); color :
#6b4e00; border : 1px solid #f0d57b; } /\*
============================================================ Aperçu
contenu ============================================================ \*/
.techrappy-blockquote { background : var(\--tr-bg-light); border-left :
3px solid var(\--tr-border); border-radius : 0 var(\--tr-radius-sm)
var(\--tr-radius-sm) 0; padding : 12px 16px; margin : 0; font-size :
.88rem; color : var(\--tr-text-muted); line-height : 1.6; font-style :
italic; word-break : break-word; } /\*
============================================================ Footer
modal ============================================================ \*/
.techrappy-modal\_\_footer { display : flex; justify-content : flex-end;
gap : 10px; padding : 16px 24px; border-top : 1px solid
var(\--tr-border-light); background : var(\--tr-bg-light); flex-shrink :
0; } /\* ============================================================
Texte muted ============================================================
\*/ .techrappy-muted { color : var(\--tr-text-muted); font-style :
italic; font-size : .85rem; } /\*
============================================================ Zone
résultat création
============================================================ \*/
.techrappy-create-result { margin-top : 16px; } .techrappy-create-result
.button { margin-top : 8px; } /\*
============================================================ Options
création ============================================================
\*/ .techrappy-create-options { background : var(\--tr-bg-light); border
: 1px solid var(\--tr-border-light); border-radius : var(\--tr-radius);
padding : 16px 20px; margin-top : 16px; } .techrappy-create-options
.techrappy-form-group:last-child { margin-bottom : 0; } /\*
============================================================ Responsive
============================================================ \*/ \@media
screen and (max-width: 782px) { .techrappy-modal\_\_container { width :
96%; max-height : 95vh; } .techrappy-modal\_\_header,
.techrappy-modal\_\_body, .techrappy-modal\_\_footer,
.techrappy-modal\_\_stats { padding : 14px 16px; } .techrappy-stats-grid
{ flex-direction : column; gap : 8px; } .techrappy-stat { flex : none;
display : flex; align-items : center; gap : 12px; text-align : left;
padding : 10px 14px; } .techrappy-stat\_\_value { font-size : 1.4rem; }
.techrappy-select { max-width : 100%; } .techrappy-form-actions {
flex-direction : column; } .techrappy-form-actions .button { width :
100%; text-align : center; } .techrappy-preview-table { font-size :
.8rem; } .techrappy-preview-value { max-width : 180px; }
.techrappy-modal\_\_footer { flex-direction : column-reverse;
align-items : stretch; } .techrappy-modal\_\_footer .button { text-align
: center; } .techrappy-token-tags { gap : 4px; } } /\*
============================================================ Animations
============================================================ \*/
\@keyframes techrappy-fade-in { from { opacity: 0; transform:
translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
.techrappy-notice { animation : techrappy-fade-in .2s ease forwards; }
.techrappy-modal\_\_container { animation : techrappy-fade-in .18s ease
forwards; } /\*
============================================================ États
disabled ============================================================
\*/ .techrappy-divi-section .button:disabled, .techrappy-divi-section
.button\[disabled\] { opacity : .5; cursor : not-allowed; pointer-events
: none; } /\*
============================================================ Mode sombre
WordPress (wp-admin dark mode futur)
============================================================ \*/ \@media
(prefers-color-scheme: dark) { /\* Réservé pour usage futur --- non
appliqué par défaut dans WP admin \*/ } \`\`\` \-\-- \## 9. Tests
PHPUnit --- \`DiviTemplateAnalyzerTest.php\` \`\`\`php \<?php /\*\* \*
DiviTemplateAnalyzerTest \* \* Tests unitaires pour
DiviTemplateAnalyzer. \* Framework : PHPUnit (intégré WP via wp-phpunit
ou Brain\\Monkey pour isolation). \* \* Exécution : \*
./vendor/bin/phpunit tests/divi/DiviTemplateAnalyzerTest.php \* \*
\@package TechrappySEO\\Tests\\Divi \*/ namespace
TechrappySEO\\Tests\\Divi; use PHPUnit\\Framework\\TestCase; use
TechrappySEO\\Modules\\Divi\\DiviTemplateAnalyzer; /\*\* \* Utilise
Brain\\Monkey pour mocker les fonctions WordPress \* sans démarrer
WordPress complet. \* \* Si le projet utilise WP_Mock ou Brain\\Monkey,
adapter setUp/tearDown. \*/ class DiviTemplateAnalyzerTest extends
TestCase { //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// extract_tokens_from_string //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers
DiviTemplateAnalyzer::extract_tokens_from_string \*/ public function
it_extracts_single_token(): void { \$content = \'Bonjour {{H1}}
monde.\'; \$tokens = DiviTemplateAnalyzer::extract_tokens_from_string(
\$content ); \$this-\>assertSame( \[ \'H1\' \], \$tokens ); } /\*\* \*
\@test \* \@covers DiviTemplateAnalyzer::extract_tokens_from_string \*/
public function it_extracts_multiple_tokens(): void { \$content =
\'{{H1}} --- {{METATITLE}} --- {{SLUG}}\'; \$tokens =
DiviTemplateAnalyzer::extract_tokens_from_string( \$content );
\$this-\>assertSame( \[ \'H1\', \'METATITLE\', \'SLUG\' \], \$tokens );
} /\*\* \* \@test \* \@covers
DiviTemplateAnalyzer::extract_tokens_from_string \*/ public function
it_extracts_dotted_notation_token(): void { \$content =
\'{{sections.0.title}} et {{sections.1.body}}\'; \$tokens =
DiviTemplateAnalyzer::extract_tokens_from_string( \$content );
\$this-\>assertSame( \[ \'sections.0.title\', \'sections.1.body\' \],
\$tokens ); } /\*\* \* \@test \* \@covers
DiviTemplateAnalyzer::extract_tokens_from_string \*/ public function
it_returns_empty_array_when_no_tokens(): void { \$content = \'Aucun
token ici, juste du texte brut.\'; \$tokens =
DiviTemplateAnalyzer::extract_tokens_from_string( \$content );
\$this-\>assertSame( \[\], \$tokens ); } /\*\* \* \@test \* \@covers
DiviTemplateAnalyzer::extract_tokens_from_string \*/ public function
it_returns_empty_for_empty_string(): void { \$tokens =
DiviTemplateAnalyzer::extract_tokens_from_string( \'\' );
\$this-\>assertSame( \[\], \$tokens ); } /\*\* \* \@test \* \@covers
DiviTemplateAnalyzer::extract_tokens_from_string \*/ public function
it_ignores_malformed_tokens(): void { // Accolades simples ou
incomplètes ne doivent pas matcher \$content = \'{H1} ou {{{DOUBLE}}} ou
{{ SPACE }} ou {{}}\'; \$tokens =
DiviTemplateAnalyzer::extract_tokens_from_string( \$content ); //
{{DOUBLE}} est valide (triple accolade → capture le milieu) // {{ SPACE
}} ne matche pas (espace non autorisé dans le regex) // {{}} ne matche
pas (aucun char) \$this-\>assertNotContains( \'H1\', \$tokens ); // {H1}
ne matche pas \$this-\>assertNotContains( \'\', \$tokens ); // {{}} ne
matche pas } /\*\* \* \@test \* \@covers
DiviTemplateAnalyzer::extract_tokens_from_string \*/ public function
it_handles_tokens_in_divi_shortcode(): void { \$content = \'\[et_pb_text
admin_label=\"Titre\"\]\<h1\>{{H1}}\</h1\>\[/et_pb_text\]\'; \$tokens =
DiviTemplateAnalyzer::extract_tokens_from_string( \$content );
\$this-\>assertContains( \'H1\', \$tokens ); } /\*\* \* \@test \*
\@covers DiviTemplateAnalyzer::extract_tokens_from_string \*/ public
function it_handles_tokens_in_html_attributes(): void { \$content =
\'\<div data-slug=\"{{SLUG}}\" class=\"test\"\>{{H1}}\</div\>\';
\$tokens = DiviTemplateAnalyzer::extract_tokens_from_string( \$content
); \$this-\>assertContains( \'SLUG\', \$tokens );
\$this-\>assertContains( \'H1\', \$tokens ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// detect_repeatable_block //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers
DiviTemplateAnalyzer::detect_repeatable_block \*/ public function
it_detects_repeatable_block_when_both_markers_present(): void {
\$content = \' \<p\>Intro\</p\> \<!\-- TECHRAPPY_REPEAT_START \--\>
\<h2\>{{SECTION_TITLE}}\</h2\> \<!\-- TECHRAPPY_REPEAT_END \--\>
\<p\>Footer\</p\> \'; \$this-\>assertTrue(
DiviTemplateAnalyzer::detect_repeatable_block( \$content ) ); } /\*\* \*
\@test \* \@covers DiviTemplateAnalyzer::detect_repeatable_block \*/
public function it_returns_false_when_only_start_marker_present(): void
{ \$content = \'\<!\-- TECHRAPPY_REPEAT_START
\--\>\<h2\>{{SECTION_TITLE}}\</h2\>\'; \$this-\>assertFalse(
DiviTemplateAnalyzer::detect_repeatable_block( \$content ) ); } /\*\* \*
\@test \* \@covers DiviTemplateAnalyzer::detect_repeatable_block \*/
public function it_returns_false_when_only_end_marker_present(): void {
\$content = \'\<h2\>{{SECTION_TITLE}}\</h2\>\<!\-- TECHRAPPY_REPEAT_END
\--\>\'; \$this-\>assertFalse(
DiviTemplateAnalyzer::detect_repeatable_block( \$content ) ); } /\*\* \*
\@test \* \@covers DiviTemplateAnalyzer::detect_repeatable_block \*/
public function it_returns_false_when_no_markers(): void { \$content =
\'\<p\>Aucun marqueur ici.\</p\>\'; \$this-\>assertFalse(
DiviTemplateAnalyzer::detect_repeatable_block( \$content ) ); } /\*\* \*
\@test \* \@covers DiviTemplateAnalyzer::detect_repeatable_block \*/
public function it_returns_false_for_empty_string(): void {
\$this-\>assertFalse( DiviTemplateAnalyzer::detect_repeatable_block(
\'\' ) ); } } \`\`\` \-\-- \## 10. Tests PHPUnit ---
\`DiviTokenReplacerTest.php\` \`\`\`php \<?php /\*\* \*
DiviTokenReplacerTest \* \* Tests unitaires pour DiviTokenReplacer. \*
\* \@package TechrappySEO\\Tests\\Divi \*/ namespace
TechrappySEO\\Tests\\Divi; use PHPUnit\\Framework\\TestCase; use
TechrappySEO\\Modules\\Divi\\DiviTokenReplacer; class
DiviTokenReplacerTest extends TestCase { /\*\* \* Données SEO de test
standard. \* \* \@var array \*/ private array \$seo_data_full = \[
\'H1\' =\> \'Ostéopathe à Toulouse\', \'intro_html\' =\>
\'\<p\>Bienvenue à Toulouse.\</p\>\', \'metatitle\' =\> \'Ostéopathe
Toulouse \| Cabinet Dupont\', \'metadescription\' =\> \'Consultez un
ostéopathe à Toulouse.\', \'slug\' =\> \'osteopathe-toulouse\',
\'faq_block_html\' =\> \'\<section class=\"faq\"\>\...\</section\>\',
\'cta_block_html\' =\> \'\<div class=\"cta\"\>\...\</div\>\',
\'focus_keyword\' =\> \'ostéopathe toulouse\', \'sections\' =\> \[ \[
\'title\' =\> \'Section 1\', \'body\' =\> \'Corps 1\' \], \[ \'title\'
=\> \'Section 2\', \'body\' =\> \'Corps 2\' \], \], \]; //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// replace() --- Cas nominaux //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/ public
function it_replaces_h1_token(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$result =
\$replacer-\>replace( \'\<h1\>{{H1}}\</h1\>\' ); \$this-\>assertSame(
\'\<h1\>Ostéopathe à Toulouse\</h1\>\', \$result ); } /\*\* \* \@test \*
\@covers DiviTokenReplacer::replace \*/ public function
it_replaces_slug_token(): void { \$replacer = new DiviTokenReplacer(
\$this-\>seo_data_full ); \$result = \$replacer-\>replace( \'{{SLUG}}\'
); \$this-\>assertSame( \'osteopathe-toulouse\', \$result ); } /\*\* \*
\@test \* \@covers DiviTokenReplacer::replace \*/ public function
it_replaces_multiple_tokens_in_one_pass(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$content =
\'\<title\>{{METATITLE}}\</title\>\<meta name=\"description\"
content=\"{{METADESCRIPTION}}\"\>\'; \$result = \$replacer-\>replace(
\$content ); \$this-\>assertStringContainsString( \'Ostéopathe Toulouse
\| Cabinet Dupont\', \$result ); \$this-\>assertStringContainsString(
\'Consultez un ostéopathe à Toulouse.\', \$result ); } /\*\* \* \@test
\* \@covers DiviTokenReplacer::replace \*/ public function
it_replaces_intro_html_with_html_content(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$result =
\$replacer-\>replace( \'{{INTRO_HTML}}\' ); \$this-\>assertSame(
\'\<p\>Bienvenue à Toulouse.\</p\>\', \$result ); } /\*\* \* \@test \*
\@covers DiviTokenReplacer::replace \*/ public function
it_replaces_faq_block_token(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$result =
\$replacer-\>replace( \'{{FAQ_BLOCK}}\' );
\$this-\>assertStringContainsString( \'faq\', \$result ); } /\*\* \*
\@test \* \@covers DiviTokenReplacer::replace \*/ public function
it_replaces_cta_block_token(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$result =
\$replacer-\>replace( \'{{CTA_BLOCK}}\' );
\$this-\>assertStringContainsString( \'cta\', \$result ); } /\*\* \*
\@test \* \@covers DiviTokenReplacer::replace \*/ public function
it_replaces_same_token_multiple_times(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$result =
\$replacer-\>replace( \'{{H1}} --- {{H1}}\' ); \$this-\>assertSame(
\'Ostéopathe à Toulouse --- Ostéopathe à Toulouse\', \$result ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// replace() --- Tokens manquants (comportement non-destructif) //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/ public
function it_preserves_unknown_token_intact(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$result =
\$replacer-\>replace( \'{{TOKEN_INCONNU}}\' ); // Le token inconnu doit
rester intact \$this-\>assertSame( \'{{TOKEN_INCONNU}}\', \$result ); }
/\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/ public
function it_logs_missing_token_error(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$replacer-\>replace(
\'{{TOKEN_ABSENT}}\' ); \$log = \$replacer-\>get_error_log();
\$this-\>assertNotEmpty( \$log ); \$this-\>assertStringContainsString(
\'TOKEN_ABSENT\', \$log\[0\] ); } /\*\* \* \@test \* \@covers
DiviTokenReplacer::replace \*/ public function
it_does_not_log_error_for_resolved_token(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$replacer-\>replace(
\'{{H1}}\' ); \$log = \$replacer-\>get_error_log();
\$this-\>assertEmpty( \$log ); } /\*\* \* \@test \* \@covers
DiviTokenReplacer::replace \*/ public function
it_continues_after_missing_token(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); // Un token valide + un
token manquant dans le même contenu \$result = \$replacer-\>replace(
\'{{H1}} --- {{INEXISTANT}}\' ); \$this-\>assertStringContainsString(
\'Ostéopathe à Toulouse\', \$result );
\$this-\>assertStringContainsString( \'{{INEXISTANT}}\', \$result ); }
//
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// replace() --- Edge cases //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/ public
function it_returns_empty_string_unchanged(): void { \$replacer = new
DiviTokenReplacer( \$this-\>seo_data_full ); \$result =
\$replacer-\>replace( \'\' ); \$this-\>assertSame( \'\', \$result ); }
/\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/ public
function it_returns_content_without_tokens_unchanged(): void {
\$replacer = new DiviTokenReplacer( \$this-\>seo_data_full ); \$content
= \'\<p\>Texte sans aucun token.\</p\>\'; \$result =
\$replacer-\>replace( \$content ); \$this-\>assertSame( \$content,
\$result ); } /\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/
public function it_handles_empty_seo_data(): void { \$replacer = new
DiviTokenReplacer( \[\] ); \$result = \$replacer-\>replace( \'{{H1}}\'
); // Tous les tokens restent intacts avec seo_data vide
\$this-\>assertSame( \'{{H1}}\', \$result ); \$this-\>assertNotEmpty(
\$replacer-\>get_error_log() ); } /\*\* \* \@test \* \@covers
DiviTokenReplacer::replace \*/ public function
it_handles_special_characters_in_value(): void { \$seo_data = \[ \'H1\'
=\> \'Ostéopathe & Thérapeute \<Beauzelle\>\', \'intro_html\' =\>
\'\<p\>Bio & santé\</p\>\', \]; \$replacer = new DiviTokenReplacer(
\$seo_data ); \$result = \$replacer-\>replace( \'{{H1}}\' ); // La
valeur doit être retournée telle quelle (pas d\'échappement côté
service) \$this-\>assertSame( \'Ostéopathe & Thérapeute \<Beauzelle\>\',
\$result ); } /\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/
public function it_resolves_dotted_notation_directly(): void {
\$seo_data = \[ \'sections\' =\> \[ \[ \'title\' =\> \'Ma Section\',
\'body\' =\> \'Mon corps\' \], \], \]; \$replacer = new
DiviTokenReplacer( \$seo_data ); \$result = \$replacer-\>replace(
\'{{sections.0.title}}\' ); \$this-\>assertSame( \'Ma Section\',
\$result ); } /\*\* \* \@test \* \@covers DiviTokenReplacer::replace \*/
public function it_handles_deeply_nested_dotted_notation(): void {
\$seo_data = \[ \'meta\' =\> \[ \'seo\' =\> \[ \'title\' =\> \'Titre
imbriqué\', \], \], \]; \$replacer = new DiviTokenReplacer( \$seo_data
); \$result = \$replacer-\>replace( \'{{meta.seo.title}}\' );
\$this-\>assertSame( \'Titre imbriqué\', \$result ); } /\*\* \* \@test
\* \@covers DiviTokenReplacer::replace \*/ public function
it_handles_token_in_multiline_divi_content(): void { \$content =
\'\[et_pb_text\] \<h1\>{{H1}}\</h1\> \<p\>{{INTRO_HTML}}\</p\>
\[/et_pb_text\]\'; \$replacer = new DiviTokenReplacer(
\$this-\>seo_data_full ); \$result = \$replacer-\>replace( \$content );
\$this-\>assertStringContainsString( \'Ostéopathe à Toulouse\', \$result
); \$this-\>assertStringContainsString( \'Bienvenue à Toulouse\',
\$result ); \$this-\>assertStringNotContainsString( \'{{H1}}\', \$result
); \$this-\>assertStringNotContainsString( \'{{INTRO_HTML}}\', \$result
); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// get_error_log() //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviTokenReplacer::get_error_log \*/ public
function it_accumulates_errors_across_multiple_replace_calls(): void {
\$replacer = new DiviTokenReplacer( \[\] ); \$replacer-\>replace(
\'{{TOKEN_A}}\' ); \$replacer-\>replace( \'{{TOKEN_B}}\' ); \$log =
\$replacer-\>get_error_log(); \$this-\>assertCount( 2, \$log ); } }
\`\`\` \-\-- \## 11. Tests PHPUnit --- \`DiviRepeatableSectionTest.php\`
\`\`\`php \<?php /\*\* \* DiviRepeatableSectionTest \* \* Tests
unitaires pour DiviRepeatableSection. \* \* \@package
TechrappySEO\\Tests\\Divi \*/ namespace TechrappySEO\\Tests\\Divi; use
PHPUnit\\Framework\\TestCase; use
TechrappySEO\\Modules\\Divi\\DiviRepeatableSection; class
DiviRepeatableSectionTest extends TestCase { /\*\* \* Template de
contenu de base avec bloc répétable. \*/ private string \$base_content =
\' \<p\>Introduction\</p\> \<!\-- TECHRAPPY_REPEAT_START \--\>
\<section\> \<h2\>{{SECTION_TITLE}}\</h2\> \<p\>{{SECTION_BODY}}\</p\>
\</section\> \<!\-- TECHRAPPY_REPEAT_END \--\> \<p\>Conclusion\</p\>\';
//
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// process() --- Cas nominaux //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviRepeatableSection::process \*/ public
function it_duplicates_block_for_two_sections(): void { \$seo_data = \[
\'sections\' =\> \[ \[ \'title\' =\> \'Section Un\', \'body\' =\>
\'Corps Un\' \], \[ \'title\' =\> \'Section Deux\', \'body\' =\> \'Corps
Deux\' \], \], \]; \$processor = new DiviRepeatableSection( \$seo_data
); \$result = \$processor-\>process( \$this-\>base_content ); // Les
deux titres doivent apparaître \$this-\>assertStringContainsString(
\'Section Un\', \$result ); \$this-\>assertStringContainsString(
\'Section Deux\', \$result ); // Les deux corps doivent apparaître
\$this-\>assertStringContainsString( \'Corps Un\', \$result );
\$this-\>assertStringContainsString( \'Corps Deux\', \$result ); } /\*\*
\* \@test \* \@covers DiviRepeatableSection::process \*/ public function
it_generates_single_block_for_one_section(): void { \$seo_data = \[
\'sections\' =\> \[ \[ \'title\' =\> \'Section Unique\', \'body\' =\>
\'Corps Unique\' \], \], \]; \$processor = new DiviRepeatableSection(
\$seo_data ); \$result = \$processor-\>process( \$this-\>base_content );
\$this-\>assertStringContainsString( \'Section Unique\', \$result );
\$this-\>assertStringContainsString( \'Corps Unique\', \$result ); //
Vérification qu\'il n\'y a qu\'une seule occurrence du titre \$count =
substr_count( \$result, \'Section Unique\' ); \$this-\>assertSame( 1,
\$count ); } /\*\* \* \@test \* \@covers DiviRepeatableSection::process
\*/ public function it_generates_n_blocks_for_n_sections(): void {
\$seo_data = \[ \'sections\' =\> \[ \[ \'title\' =\> \'A\', \'body\' =\>
\'B1\' \], \[ \'title\' =\> \'B\', \'body\' =\> \'B2\' \], \[ \'title\'
=\> \'C\', \'body\' =\> \'B3\' \], \[ \'title\' =\> \'D\', \'body\' =\>
\'B4\' \], \], \]; \$processor = new DiviRepeatableSection( \$seo_data
); \$result = \$processor-\>process( \$this-\>base_content ); // Chaque
titre doit apparaître foreach ( \[ \'A\', \'B\', \'C\', \'D\' \] as
\$title ) { \$this-\>assertStringContainsString( \$title, \$result ); }
// 4 occurrences de \<section\> (une par bloc répété)
\$this-\>assertSame( 4, substr_count( \$result, \'\<section\>\' ) ); }
/\*\* \* \@test \* \@covers DiviRepeatableSection::process \*/ public
function it_replaces_section_index_token(): void { \$content_with_index
= \' \<!\-- TECHRAPPY_REPEAT_START \--\> \<h2\>{{SECTION_INDEX}}.
{{SECTION_TITLE}}\</h2\> \<!\-- TECHRAPPY_REPEAT_END \--\>\'; \$seo_data
= \[ \'sections\' =\> \[ \[ \'title\' =\> \'Intro\', \'body\' =\> \'\'
\], \[ \'title\' =\> \'Suite\', \'body\' =\> \'\' \], \], \];
\$processor = new DiviRepeatableSection( \$seo_data ); \$result =
\$processor-\>process( \$content_with_index );
\$this-\>assertStringContainsString( \'1. Intro\', \$result );
\$this-\>assertStringContainsString( \'2. Suite\', \$result ); } /\*\*
\* \@test \* \@covers DiviRepeatableSection::process \*/ public function
it_preserves_content_outside_markers(): void { \$seo_data = \[
\'sections\' =\> \[ \[ \'title\' =\> \'Section A\', \'body\' =\> \'Corps
A\' \], \], \]; \$processor = new DiviRepeatableSection( \$seo_data );
\$result = \$processor-\>process( \$this-\>base_content ); // Le contenu
avant et après les marqueurs est préservé
\$this-\>assertStringContainsString( \'\<p\>Introduction\</p\>\',
\$result ); \$this-\>assertStringContainsString(
\'\<p\>Conclusion\</p\>\', \$result ); } /\*\* \* \@test \* \@covers
DiviRepeatableSection::process \*/ public function
it_removes_markers_from_output(): void { \$seo_data = \[ \'sections\'
=\> \[ \[ \'title\' =\> \'T\', \'body\' =\> \'B\' \], \], \];
\$processor = new DiviRepeatableSection( \$seo_data ); \$result =
\$processor-\>process( \$this-\>base_content ); // Les marqueurs
eux-mêmes ne doivent plus apparaître dans le contenu final
\$this-\>assertStringNotContainsString( \'TECHRAPPY_REPEAT_START\',
\$result ); \$this-\>assertStringNotContainsString(
\'TECHRAPPY_REPEAT_END\', \$result ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// process() --- Cas limites sections vides/absentes //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviRepeatableSection::process \*/ public
function it_removes_repeatable_block_when_sections_empty(): void {
\$seo_data = \[ \'sections\' =\> \[\] \]; \$processor = new
DiviRepeatableSection( \$seo_data ); \$result = \$processor-\>process(
\$this-\>base_content ); // Le bloc répétable doit être supprimé
\$this-\>assertStringNotContainsString( \'SECTION_TITLE\', \$result );
\$this-\>assertStringNotContainsString( \'SECTION_BODY\', \$result );
\$this-\>assertStringNotContainsString( \'{{\', \$result ); // Le reste
du contenu est préservé \$this-\>assertStringContainsString(
\'Introduction\', \$result ); \$this-\>assertStringContainsString(
\'Conclusion\', \$result ); } /\*\* \* \@test \* \@covers
DiviRepeatableSection::process \*/ public function
it_removes_repeatable_block_when_sections_key_missing(): void {
\$seo_data = \[ \'H1\' =\> \'Test\' \]; // Pas de clé \"sections\"
\$processor = new DiviRepeatableSection( \$seo_data ); \$result =
\$processor-\>process( \$this-\>base_content );
\$this-\>assertStringNotContainsString( \'SECTION_TITLE\', \$result );
\$this-\>assertStringNotContainsString( \'{{\', \$result ); } /\*\* \*
\@test \* \@covers DiviRepeatableSection::process \*/ public function
it_logs_warning_when_sections_empty(): void { \$seo_data = \[
\'sections\' =\> \[\] \]; \$processor = new DiviRepeatableSection(
\$seo_data ); \$processor-\>process( \$this-\>base_content ); \$log =
\$processor-\>get_error_log(); \$this-\>assertNotEmpty( \$log ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// process() --- Sans marqueurs //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviRepeatableSection::process \*/ public
function it_returns_content_unchanged_when_no_markers(): void {
\$content = \'\<p\>Pas de marqueurs ici.\</p\>\'; \$seo_data = \[
\'sections\' =\> \[ \[ \'title\' =\> \'T\', \'body\' =\> \'B\' \] \] \];
\$processor = new DiviRepeatableSection( \$seo_data ); \$result =
\$processor-\>process( \$content ); \$this-\>assertSame( \$content,
\$result ); } /\*\* \* \@test \* \@covers DiviRepeatableSection::process
\*/ public function it_returns_empty_content_unchanged(): void {
\$seo_data = \[ \'sections\' =\> \[\] \]; \$processor = new
DiviRepeatableSection( \$seo_data ); \$result = \$processor-\>process(
\'\' ); \$this-\>assertSame( \'\', \$result ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// process() --- Sections avec données manquantes //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@test \* \@covers DiviRepeatableSection::process \*/ public
function it_handles_section_without_body_key(): void { \$seo_data = \[
\'sections\' =\> \[ \[ \'title\' =\> \'Section Sans Body\' \], // Pas de
clé \'body\' \], \]; \$processor = new DiviRepeatableSection( \$seo_data
); // Ne doit pas lancer d\'exception \$result = \$processor-\>process(
\$this-\>base_content ); \$this-\>assertStringContainsString( \'Section
Sans Body\', \$result ); // Le token SECTION_BODY reste intact
(non-destructif) \$this-\>assertStringContainsString(
\'{{SECTION_BODY}}\', \$result ); // Une erreur doit être loggée \$log =
\$processor-\>get_error_log(); \$this-\>assertNotEmpty( \$log ); } /\*\*
\* \@test \* \@covers DiviRepeatableSection::process \*/ public function
it_skips_non_array_section_entry(): void { \$seo_data = \[ \'sections\'
=\> \[ \[ \'title\' =\> \'Valide\', \'body\' =\> \'Corps\' \],
\'ceci-est-une-chaine\', // Invalide, doit être ignorée \[ \'title\' =\>
\'Aussi valide\', \'body\' =\> \'Corps 2\' \], \], \]; \$processor = new
DiviRepeatableSection( \$seo_data ); // Ne doit pas planter \$result =
\$processor-\>process( \$this-\>base_content );
\$this-\>assertStringContainsString( \'Valide\', \$result );
\$this-\>assertStringContainsString( \'Aussi valide\', \$result ); //
Une erreur doit être loggée pour l\'entrée invalide \$log =
\$processor-\>get_error_log(); \$this-\>assertNotEmpty( \$log ); } /\*\*
\* \@test \* \@covers DiviRepeatableSection::process \*/ public function
it_handles_sections_with_html_values(): void { \$seo_data = \[
\'sections\' =\> \[ \[ \'title\' =\> \'\<strong\>Titre
HTML\</strong\>\', \'body\' =\> \'\<p\>Corps avec
\<em\>emphase\</em\>\</p\>\', \], \], \]; \$processor = new
DiviRepeatableSection( \$seo_data ); \$result = \$processor-\>process(
\$this-\>base_content ); \$this-\>assertStringContainsString(
\'\<strong\>Titre HTML\</strong\>\', \$result );
\$this-\>assertStringContainsString( \'\<p\>Corps avec
\<em\>emphase\</em\>\</p\>\', \$result ); } } \`\`\` \-\-- \## 12.
Intégration dans le plugin principal --- \`techrappy-seo.php\` \`\`\`php
\<?php /\*\* \* Extrait à intégrer dans le fichier principal du plugin
(techrappy-seo.php) \* ou dans la classe de bootstrap existante. \* \* À
ajouter dans la méthode load_modules() ou équivalent \* de
l\'architecture existante validée. \* \* NE PAS modifier l\'architecture
existante. \* Ajouter uniquement le bloc marqué 🆕 ci-dessous. \*/ //
Dans la méthode load_modules() ou init() du plugin principal : private
function load_modules(): void { // \... modules existants validés (ne
pas modifier) \... // 🆕 Module Divi Builder // Chargement conditionnel
: le module s\'enregistre toujours, // mais les fonctionnalités Divi
sont désactivées proprement // si Divi n\'est pas présent
(DiviCompatibilityChecker). \$divi_module = new
\\TechrappySEO\\Modules\\Divi\\DiviModule(); \$divi_module-\>register();
} \`\`\` \-\-- \## 13. Autoloader --- \`composer.json\` (extrait à
compléter) \`\`\`json { \"autoload\": { \"psr-4\": {
\"TechrappySEO\\\\\": \"includes/\",
\"TechrappySEO\\\\Modules\\\\Divi\\\\\": \"modules/divi/\",
\"TechrappySEO\\\\Tests\\\\\": \"tests/\" } }, \"require-dev\": {
\"phpunit/phpunit\": \"\^10.0\", \"brain/monkey\": \"\^2.6\" },
\"scripts\": { \"test\" : \"./vendor/bin/phpunit \--testdox\",
\"test:divi\" : \"./vendor/bin/phpunit tests/divi/ \--testdox\" } }
\`\`\` \-\-- \## 14. Configuration PHPUnit --- \`phpunit.xml\` \`\`\`xml
\<?xml version=\"1.0\" encoding=\"UTF-8\"?\> \<!\-- phpunit.xml
Configuration PHPUnit pour Techrappy SEO Exécution :
./vendor/bin/phpunit \--\> \<phpunit
xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
xsi:noNamespaceSchemaLocation=\"https://schema.phpunit.de/10.0/phpunit.xsd\"
bootstrap=\"tests/bootstrap.php\" colors=\"true\"
displayDetailsOnTestsThatTriggerWarnings=\"true\"
displayDetailsOnTestsThatTriggerDeprecations=\"true\" \> \<testsuites\>
\<testsuite name=\"Techrappy SEO --- All\"\>
\<directory\>tests/\</directory\> \</testsuite\> \<testsuite
name=\"Techrappy SEO --- Divi\"\> \<directory\>tests/divi/\</directory\>
\</testsuite\> \</testsuites\> \<source\> \<include\>
\<directory\>modules/\</directory\> \</include\> \<exclude\>
\<directory\>vendor/\</directory\> \<directory\>tests/\</directory\>
\</exclude\> \</source\> \<coverage\> \<report\> \<html
outputDirectory=\"tests/coverage/html\"/\> \<text
outputFile=\"tests/coverage/coverage.txt\"/\> \</report\> \</coverage\>
\</phpunit\> \`\`\` \-\-- \## 15. Bootstrap tests ---
\`tests/bootstrap.php\` \`\`\`php \<?php /\*\* \* Bootstrap PHPUnit \*
\* Initialise l\'environnement de test sans démarrer WordPress. \*
Utilise Brain\\Monkey pour mocker les fonctions WP. \* \* \@package
TechrappySEO\\Tests \*/ declare( strict_types=1 ); // Autoloader
Composer require_once dirname( \_\_DIR\_\_ ) . \'/vendor/autoload.php\';
// Constantes WordPress simulées pour les tests d\'isolation if ( !
defined( \'ABSPATH\' ) ) { define( \'ABSPATH\', dirname( \_\_DIR\_\_ ) .
\'/\' ); } if ( ! defined( \'TECHRAPPY_SEO_VERSION\' ) ) { define(
\'TECHRAPPY_SEO_VERSION\', \'1.0.0-test\' ); } if ( ! defined(
\'TECHRAPPY_SEO_PATH\' ) ) { define( \'TECHRAPPY_SEO_PATH\', dirname(
\_\_DIR\_\_ ) . \'/\' ); } if ( ! defined( \'TECHRAPPY_SEO_URL\' ) ) {
define( \'TECHRAPPY_SEO_URL\',
\'https://example.com/wp-content/plugins/techrappy-seo/\' ); } //
Brain\\Monkey setup global (si utilisé) // Les tests individuels
appelleront Monkey::setUp() / tearDown() // via un trait ou une classe
de base. /\*\* \* Classe de base optionnelle pour les tests nécessitant
Brain\\Monkey. \* Étendre cette classe dans les tests qui mockent des
fonctions WP. \*/ abstract class TechrappyTestCase extends
\\PHPUnit\\Framework\\TestCase { protected function setUp(): void {
parent::setUp(); \\Brain\\Monkey\\setUp(); // Mocks des fonctions WP les
plus courantes \\Brain\\Monkey\\Functions\\when( \'error_log\'
)-\>justReturn( true ); \\Brain\\Monkey\\Functions\\when(
\'sanitize_text_field\' )-\>returnArg();
\\Brain\\Monkey\\Functions\\when( \'sanitize_title\' )-\>returnArg();
\\Brain\\Monkey\\Functions\\when( \'absint\' )-\>alias( \'abs\' );
\\Brain\\Monkey\\Functions\\when( \'esc_html\' )-\>returnArg();
\\Brain\\Monkey\\Functions\\when( \'esc_attr\' )-\>returnArg();
\\Brain\\Monkey\\Functions\\when( \'wp_strip_all_tags\' )-\>alias(
\'strip_tags\' ); \\Brain\\Monkey\\Functions\\when( \'current_time\'
)-\>justReturn( \'2024-01-01 00:00:00\' ); } protected function
tearDown(): void { \\Brain\\Monkey\\tearDown(); parent::tearDown(); } }
\`\`\` \-\-- \## Récapitulatif final des deux runs \`\`\`
┌─────────────────────────────────────────────────────────────────┐ │
MODULE DIVI BUILDER --- LIVRAISON COMPLÈTE │
├─────────────────────────────────────────────────────────────────┤ │ │
│ SERVICES (modules/divi/) │ │ ├── DiviTemplateAnalyzer.php Détection
tokens + répétables │ │ ├── DiviTokenReplacer.php Remplacement {{TOKEN}}
→ valeur│ │ ├── DiviRepeatableSection.php Duplication/suppression blocs
│ │ ├── DiviTemplateService.php Orchestrateur principal │ │ ├──
DiviAjaxHandler.php Handlers AJAX (4 actions) │ │ ├── DiviModule.php
Point d\'entrée + hooks WP │ │ ├── DiviCompatibilityChecker.php
Vérification Divi actif │ │ ├── DiviPreviewBuilder.php Preview dry-run
sans DB write │ │ └── DiviPostCleaner.php Nettoyage post-génération │ │
│ │ UI ADMIN (admin/partials/) │ │ ├── divi-template-selector.php
Sélecteur + analyse + création │ │ └── divi-preview-modal.php Modal
preview des tokens │ │ │ │ ASSETS │ │ ├──
assets/js/divi-template-admin.js JS complet (ES5 jQuery) │ │ └──
assets/css/divi-admin.css CSS complet responsive │ │ │ │ TESTS
(tests/divi/) │ │ ├── DiviTemplateAnalyzerTest.php 14 tests │ │ ├──
DiviTokenReplacerTest.php 18 tests │ │ └── DiviRepeatableSectionTest.php
15 tests │ │ │ │ CONFIG │ │ ├── phpunit.xml │ │ ├── tests/bootstrap.php
│ │ └── composer.json (extrait) │ │ │ │ FLUX COMPLET │ │ 1. Sélectionner
template Divi │ │ 2. Analyser → afficher tokens détectés │ │ 3.
(optionnel) Preview dry-run → modal de validation │ │ 4. Créer →
duplication + injection + metas Yoast + slug │ │ 5. Résultat → lien edit
Divi + lien preview │ │ 6. Nettoyage automatique post-création
(DiviPostCleaner) │ │ │ │ SÉCURITÉ │ │ ✅ Nonce wp_verify_nonce sur
toutes les actions AJAX │ │ ✅ current_user_can(\'manage_options\') sur
toutes les actions │ │ ✅ absint() sur tous les IDs │ │ ✅ sanitize\_\*
sur toutes les entrées texte │ │ ✅ \$wpdb-\>prepare() dans
DiviPostCleaner │ │ ✅ wp_unslash() sur \$\_POST │ │ ✅ Tokens manquants
: non-destructif (conservés + log) │
└─────────────────────────────────────────────────────────────────┘
\`\`\`
