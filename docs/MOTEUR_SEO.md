\# V4 --- Moteur de génération SEO \-\-- \## Fichier 1 :
\`includes/Generation/GenerationRequest.php\` --- DTO d\'entrée
\`\`\`php \<?php /\*\* \* Objet de requête de génération (DTO
d\'entrée). \* \* Encapsule et valide toutes les entrées nécessaires au
moteur \* de génération SEO avant de lancer le pipeline. \* \* \@package
TechrappySEO\\Generation \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Generation; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class GenerationRequest \* \* Value Object immuable après
construction. \* Toutes les propriétés sont sanitizées à la création.
\*/ final class GenerationRequest { //
───────────────────────────────────────── // Constantes //
───────────────────────────────────────── /\*\* Types de contenu
supportés. \*/ const TYPE_PAGE_SEO = \'page_seo\'; const
TYPE_ARTICLE_SEO = \'article_seo\'; const ALLOWED_TYPES = \[
self::TYPE_PAGE_SEO, self::TYPE_ARTICLE_SEO, \]; //
───────────────────────────────────────── // Propriétés //
───────────────────────────────────────── /\*\* \@var string Type de
contenu : \'page_seo\' \| \'article_seo\' \*/ private string
\$type_contenu; /\*\* \@var string Mot-clé principal (ex: \"ostéopathe
Beauzelle\") \*/ private string \$mot_cle; /\*\* \@var string Profession
/ activité (ex: \"ostéopathe\") \*/ private string \$profession; /\*\*
\@var string Ville ciblée (optionnel, pour pages locales) \*/ private
string \$city; /\*\* \* Pages existantes du site pour le maillage
interne. \* \* \@var array\<int, array{title: string, url: string}\> \*/
private array \$pages_site_liste; /\*\* \* Contraintes de génération
optionnelles. \* Clés possibles : ton, longueur, interdit, cta_url, etc.
\* \* \@var array\<string, mixed\> \*/ private array \$contraintes;
/\*\* \@var string Identifiant unique de cette requête (UUID) \*/
private string \$request_id; //
───────────────────────────────────────── // Constructeur & factory //
───────────────────────────────────────── /\*\* \* Constructeur privé
--- utiliser ::from_array(). \*/ private function \_\_construct() {}
/\*\* \* Crée une GenerationRequest depuis un tableau de données brutes.
\* Sanitize et valide toutes les entrées. \* \* \@param array\<string,
mixed\> \$data Données brutes (POST ou tableau direct). \* \* \@return
self \* \* \@throws \\InvalidArgumentException Si les données sont
invalides. \*/ public static function from_array( array \$data ): self {
\$req = new self(); // ── Type de contenu (obligatoire) ── \$type =
sanitize_key( \$data\[\'type_contenu\'\] ?? \'\' ); if ( ! in_array(
\$type, self::ALLOWED_TYPES, true ) ) { throw new
\\InvalidArgumentException( sprintf( \'type_contenu invalide : \"%s\".
Valeurs acceptées : %s\', \$type, implode( \', \', self::ALLOWED_TYPES )
) ); } \$req-\>type_contenu = \$type; // ── Mot-clé (obligatoire) ──
\$mot_cle = sanitize_text_field( trim( \$data\[\'mot_cle\'\] ?? \'\' )
); if ( empty( \$mot_cle ) ) { throw new \\InvalidArgumentException(
\'Le mot-clé principal (mot_cle) est obligatoire.\' ); } \$req-\>mot_cle
= \$mot_cle; // ── Profession (optionnel) ── \$req-\>profession =
sanitize_text_field( trim( \$data\[\'profession\'\] ?? \'professionnel
de santé\' ) ); // ── Ville (optionnel) ── \$req-\>city =
sanitize_text_field( trim( \$data\[\'city\'\] ?? \'\' ) ); // ── Pages
site liste (optionnel) ── \$pages_raw = \$data\[\'pages_site_liste\'\]
?? \[\]; if ( is_string( \$pages_raw ) ) { \$pages_raw = json_decode(
\$pages_raw, true ) ?? \[\]; } \$req-\>pages_site_liste =
self::sanitize_pages_list( is_array( \$pages_raw ) ? \$pages_raw : \[\]
); // ── Contraintes (optionnel) ── \$contraintes_raw =
\$data\[\'contraintes\'\] ?? \[\]; if ( is_string( \$contraintes_raw ) )
{ \$contraintes_raw = json_decode( \$contraintes_raw, true ) ?? \[\]; }
\$req-\>contraintes = self::sanitize_contraintes( is_array(
\$contraintes_raw ) ? \$contraintes_raw : \[\] ); // ── ID unique de la
requête ── \$req-\>request_id =
\\TechrappySEO\\Utils\\UuidGenerator::generate(); return \$req; } //
───────────────────────────────────────── // Accesseurs //
───────────────────────────────────────── public function
get_type_contenu(): string { return \$this-\>type_contenu; } public
function get_mot_cle(): string { return \$this-\>mot_cle; } public
function get_profession(): string { return \$this-\>profession; } public
function get_city(): string { return \$this-\>city; } public function
get_request_id(): string { return \$this-\>request_id; } public function
get_contraintes(): array { return \$this-\>contraintes; } /\*\* \*
\@return array\<int, array{title: string, url: string}\> \*/ public
function get_pages_site_liste(): array { return
\$this-\>pages_site_liste; } /\*\* \* Vérifie si des pages de site sont
disponibles pour le maillage. \* \* \@return bool \*/ public function
has_pages_site(): bool { return ! empty( \$this-\>pages_site_liste ); }
/\*\* \* Retourne une contrainte spécifique. \* \* \@param string \$key
Clé de la contrainte. \* \@param mixed \$default Valeur par défaut. \*
\* \@return mixed \*/ public function get_contrainte( string \$key,
mixed \$default = null ): mixed { return \$this-\>contraintes\[ \$key \]
?? \$default; } /\*\* \* Sérialise la requête en tableau (pour logs et
stockage). \* \* \@return array\<string, mixed\> \*/ public function
to_array(): array { return \[ \'request_id\' =\> \$this-\>request_id,
\'type_contenu\' =\> \$this-\>type_contenu, \'mot_cle\' =\>
\$this-\>mot_cle, \'profession\' =\> \$this-\>profession, \'city\' =\>
\$this-\>city, \'pages_site_count\' =\> count( \$this-\>pages_site_liste
), \'contraintes\' =\> \$this-\>contraintes, \]; } //
───────────────────────────────────────── // Sanitizers internes //
───────────────────────────────────────── /\*\* \* Sanitize la liste des
pages du site. \* \* \@param array\<mixed\> \$raw Liste brute. \* \*
\@return array\<int, array{title: string, url: string}\> \*/ private
static function sanitize_pages_list( array \$raw ): array { \$clean =
\[\]; foreach ( \$raw as \$page ) { if ( ! is_array( \$page ) ) {
continue; } \$title = sanitize_text_field( \$page\[\'title\'\] ?? \'\'
); \$url = esc_url_raw( \$page\[\'url\'\] ?? \'\' ); if ( ! empty(
\$title ) && ! empty( \$url ) ) { \$clean\[\] = \[ \'title\' =\>
\$title, \'url\' =\> \$url \]; } } // Limiter à 50 pages pour ne pas
surcharger le prompt. return array_slice( \$clean, 0, 50 ); } /\*\* \*
Sanitize les contraintes de génération. \* \* \@param array\<mixed\>
\$raw Contraintes brutes. \* \* \@return array\<string, mixed\> \*/
private static function sanitize_contraintes( array \$raw ): array {
\$allowed_keys = \[ \'ton\', \'longueur\', \'interdit\', \'cta_url\',
\'cta_texte\', \'style\', \'niveau_technicite\', \]; \$clean = \[\];
foreach ( \$allowed_keys as \$key ) { if ( isset( \$raw\[ \$key \] ) ) {
\$clean\[ \$key \] = sanitize_text_field( (string) \$raw\[ \$key \] ); }
} return \$clean; } } \`\`\` \-\-- \## Fichier 2 :
\`includes/Generation/GenerationResult.php\` --- DTO de sortie \`\`\`php
\<?php /\*\* \* Objet résultat de génération SEO (DTO de sortie). \* \*
Encapsule le contenu généré sous forme structurée et immuable. \* Sert
de contrat d\'interface avec les modules aval : \* WordPress (création
post), Yoast (meta), Divi (injection tokens). \* \* \@package
TechrappySEO\\Generation \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Generation; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class GenerationResult \* \* Value Object --- construit
uniquement par GenerationEngine ou via ::from_array(). \*/ final class
GenerationResult { // ───────────────────────────────────────── //
Propriétés du contenu généré //
───────────────────────────────────────── /\*\* \@var string Meta title
SEO (55-65 chars) \*/ private string \$metatitle = \'\'; /\*\* \@var
string Meta description SEO (140-160 chars) \*/ private string
\$metadescription = \'\'; /\*\* \@var string Slug SEO-friendly \*/
private string \$slug = \'\'; /\*\* \@var string Titre H1 \*/ private
string \$h1 = \'\'; /\*\* \@var string Introduction HTML \*/ private
string \$intro_html = \'\'; /\*\* \* Sections de contenu (blocs H2). \*
\* \@var array\<int, array{h2: string, html: string,
internal_links_html: string}\> \*/ private array \$sections = \[\];
/\*\* \* Bloc FAQ. \* \* \@var array{ \* items: array\<int, array{q:
string, a_html: string}\>, \* schema_ld_json: string \* } \*/ private
array \$faq = \[ \'items\' =\> \[\], \'schema_ld_json\' =\> \'\', \];
/\*\* \@var string Bloc CTA HTML \*/ private string \$cta_block_html =
\'\'; // ───────────────────────────────────────── // Métadonnées de
génération // ───────────────────────────────────────── /\*\* \@var
string Identifiant de la requête source \*/ private string \$request_id
= \'\'; /\*\* \@var bool Indique si la génération est complète et valide
\*/ private bool \$is_valid = false; /\*\* \* Score QA final (0-100). \*
\* \@var array{score_seo: int, score_humain: int, problemes: array,
fixes_rapides: array} \*/ private array \$qa_score = \[ \'score_seo\'
=\> 0, \'score_humain\' =\> 0, \'problemes\' =\> \[\],
\'fixes_rapides\'=\> \[\], \]; /\*\* \* Statistiques d\'exécution
(tokens, durée, coût). \* \* \@var array\<string, mixed\> \*/ private
array \$stats = \[ \'total_input_tokens\' =\> 0, \'total_output_tokens\'
=\> 0, \'total_duration_ms\' =\> 0, \'estimated_cost_usd\' =\> 0.0,
\'steps_completed\' =\> 0, \'steps_failed\' =\> 0, \]; /\*\* \@var
string\[\] Avertissements non-bloquants collectés \*/ private array
\$warnings = \[\]; // ───────────────────────────────────────── //
Constructeur privé // ───────────────────────────────────────── private
function \_\_construct() {} /\*\* \* Crée un GenerationResult depuis le
tableau de données du moteur. \* \* \@param array\<string, mixed\>
\$data Données brutes assemblées par GenerationEngine. \* \* \@return
self \*/ public static function from_array( array \$data ): self {
\$result = new self(); \$result-\>metatitle = (string) (
\$data\[\'metatitle\'\] ?? \'\' ); \$result-\>metadescription = (string)
( \$data\[\'metadescription\'\] ?? \'\' ); \$result-\>slug = (string) (
\$data\[\'slug\'\] ?? \'\' ); \$result-\>h1 = (string) (
\$data\[\'H1\'\] ?? \'\' ); \$result-\>intro_html = (string) (
\$data\[\'intro_html\'\] ?? \'\' ); \$result-\>sections =
self::normalize_sections( \$data\[\'sections\'\] ?? \[\] );
\$result-\>faq = self::normalize_faq( \$data\[\'faq\'\] ?? \[\] );
\$result-\>cta_block_html = (string) ( \$data\[\'cta_block_html\'\] ??
\'\' ); \$result-\>request_id = (string) ( \$data\[\'request_id\'\] ??
\'\' ); \$result-\>qa_score = self::normalize_qa_score(
\$data\[\'qa_score\'\] ?? \[\] ); \$result-\>stats = array_merge(
\$result-\>stats, \$data\[\'stats\'\] ?? \[\] ); \$result-\>warnings =
array_values( array_filter( (array) ( \$data\[\'warnings\'\] ?? \[\] ),
\'is_string\' ) ); \$result-\>is_valid =
\$result-\>validate_completeness(); return \$result; } //
───────────────────────────────────────── // Accesseurs //
───────────────────────────────────────── public function
get_metatitle(): string { return \$this-\>metatitle; } public function
get_metadescription(): string { return \$this-\>metadescription; }
public function get_slug(): string { return \$this-\>slug; } public
function get_h1(): string { return \$this-\>h1; } public function
get_intro_html(): string { return \$this-\>intro_html; } public function
get_cta_block_html(): string { return \$this-\>cta_block_html; } public
function get_request_id(): string { return \$this-\>request_id; } public
function is_valid(): bool { return \$this-\>is_valid; } public function
get_warnings(): array { return \$this-\>warnings; } public function
get_stats(): array { return \$this-\>stats; } public function
get_qa_score(): array { return \$this-\>qa_score; } /\*\* \* \@return
array\<int, array{h2: string, html: string, internal_links_html:
string}\> \*/ public function get_sections(): array { return
\$this-\>sections; } /\*\* \* \@return array{items: array,
schema_ld_json: string} \*/ public function get_faq(): array { return
\$this-\>faq; } /\*\* \* Retourne les items FAQ. \* \* \@return
array\<int, array{q: string, a_html: string}\> \*/ public function
get_faq_items(): array { return \$this-\>faq\[\'items\'\] ?? \[\]; }
/\*\* \* Retourne le schema JSON-LD de la FAQ. \* \* \@return string \*/
public function get_faq_schema(): string { return
\$this-\>faq\[\'schema_ld_json\'\] ?? \'\'; } /\*\* \* Construit le HTML
complet assemblé (pour preview ou QA). \* \* \@return string \*/ public
function get_full_html(): string { \$parts = \[\]; if ( \$this-\>h1 ) {
\$parts\[\] = \'\<h1\>\' . esc_html( \$this-\>h1 ) . \'\</h1\>\'; } if (
\$this-\>intro_html ) { \$parts\[\] = \$this-\>intro_html; } foreach (
\$this-\>sections as \$section ) { if ( ! empty( \$section\[\'h2\'\] ) )
{ \$parts\[\] = \'\<h2\>\' . esc_html( \$section\[\'h2\'\] ) .
\'\</h2\>\'; } if ( ! empty( \$section\[\'html\'\] ) ) { \$parts\[\] =
\$section\[\'html\'\]; } if ( ! empty(
\$section\[\'internal_links_html\'\] ) ) { \$parts\[\] =
\$section\[\'internal_links_html\'\]; } } if ( \$this-\>cta_block_html )
{ \$parts\[\] = \$this-\>cta_block_html; } if ( ! empty(
\$this-\>faq\[\'items\'\] ) ) { \$parts\[\] = \$this-\>build_faq_html();
} if ( \$this-\>faq\[\'schema_ld_json\'\] ) { \$parts\[\] = \'\<script
type=\"application/ld+json\"\>\' . \$this-\>faq\[\'schema_ld_json\'\] .
\'\</script\>\'; } return implode( \"\\n\\n\", array_filter( \$parts )
); } /\*\* \* Sérialise le résultat complet en tableau (pour stockage
JSON en DB). \* \* \@return array\<string, mixed\> \*/ public function
to_array(): array { return \[ \'metatitle\' =\> \$this-\>metatitle,
\'metadescription\' =\> \$this-\>metadescription, \'slug\' =\>
\$this-\>slug, \'H1\' =\> \$this-\>h1, \'intro_html\' =\>
\$this-\>intro_html, \'sections\' =\> \$this-\>sections, \'faq\' =\>
\$this-\>faq, \'cta_block_html\' =\> \$this-\>cta_block_html,
\'request_id\' =\> \$this-\>request_id, \'is_valid\' =\>
\$this-\>is_valid, \'qa_score\' =\> \$this-\>qa_score, \'stats\' =\>
\$this-\>stats, \'warnings\' =\> \$this-\>warnings, \]; } /\*\* \*
Sérialise en JSON strict (format de sortie final du moteur). \* \*
\@return string JSON encodé ou chaîne vide si erreur. \*/ public
function to_json(): string { // Sortie JSON publique : uniquement le
contenu, sans métadonnées internes. \$output = \[ \'metatitle\' =\>
\$this-\>metatitle, \'metadescription\' =\> \$this-\>metadescription,
\'slug\' =\> \$this-\>slug, \'H1\' =\> \$this-\>h1, \'intro_html\' =\>
\$this-\>intro_html, \'sections\' =\> \$this-\>sections, \'faq\' =\>
\$this-\>faq, \'cta_block_html\' =\> \$this-\>cta_block_html, \]; return
wp_json_encode( \$output, JSON_UNESCAPED_UNICODE \| JSON_PRETTY_PRINT )
?: \'\'; } // ───────────────────────────────────────── // Normalisation
interne // ───────────────────────────────────────── /\*\* \* Normalise
le tableau des sections. \* \* \@param array\<mixed\> \$raw \* \*
\@return array\<int, array{h2: string, html: string,
internal_links_html: string}\> \*/ private static function
normalize_sections( array \$raw ): array { \$sections = \[\]; foreach (
\$raw as \$item ) { if ( ! is_array( \$item ) ) { continue; }
\$sections\[\] = \[ \'h2\' =\> (string) ( \$item\[\'h2\'\] ??
\$item\[\'H2\'\] ?? \'\' ), \'html\' =\> (string) ( \$item\[\'html\'\]
?? \'\' ), \'internal_links_html\' =\> (string) (
\$item\[\'internal_links_html\'\] ?? \'\' ), \]; } return \$sections; }
/\*\* \* Normalise le tableau FAQ. \* \* \@param array\<mixed\> \$raw \*
\* \@return array{items: array, schema_ld_json: string} \*/ private
static function normalize_faq( array \$raw ): array { \$items = \[\];
foreach ( \$raw\[\'items\'\] ?? \[\] as \$item ) { if ( ! is_array(
\$item ) ) { continue; } \$items\[\] = \[ \'q\' =\> (string) (
\$item\[\'q\'\] ?? \'\' ), \'a_html\'=\> (string) ( \$item\[\'a_html\'\]
?? \$item\[\'a\'\] ?? \'\' ), \]; } return \[ \'items\' =\> \$items,
\'schema_ld_json\' =\> (string) ( \$raw\[\'schema_ld_json\'\] ?? \'\' ),
\]; } /\*\* \* Normalise le score QA. \* \* \@param array\<mixed\> \$raw
\* \* \@return array{score_seo: int, score_humain: int, problemes:
array, fixes_rapides: array} \*/ private static function
normalize_qa_score( array \$raw ): array { return \[ \'score_seo\' =\>
(int) ( \$raw\[\'score_seo\'\] ?? 0 ), \'score_humain\' =\> (int) (
\$raw\[\'score_humain\'\] ?? 0 ), \'problemes\' =\> (array) (
\$raw\[\'problemes\'\] ?? \[\] ), \'fixes_rapides\' =\> (array) (
\$raw\[\'fixes_rapides\'\]?? \[\] ), \]; } /\*\* \* Vérifie que les
champs obligatoires sont présents et non vides. \* \* \@return bool \*/
private function validate_completeness(): bool { return ! empty(
\$this-\>metatitle ) && ! empty( \$this-\>metadescription ) && ! empty(
\$this-\>slug ) && ! empty( \$this-\>h1 ) && ! empty(
\$this-\>intro_html ) && ! empty( \$this-\>sections ); } /\*\* \*
Construit le HTML visible de la FAQ depuis les items. \* \* \@return
string \*/ private function build_faq_html(): string { if ( empty(
\$this-\>faq\[\'items\'\] ) ) { return \'\'; } \$items_html = \'\';
foreach ( \$this-\>faq\[\'items\'\] as \$item ) { \$items_html .=
sprintf( \'\<details\>\<summary\>%s\</summary\>%s\</details\>\',
esc_html( \$item\[\'q\'\] ), wp_kses_post( \$item\[\'a_html\'\] ) ); }
return sprintf( \'\<section
class=\"techrappy-faq\"\>\<h2\>%s\</h2\>%s\</section\>\', esc_html\_\_(
\'Questions fréquentes\', \'techrappy-seo\' ), \$items_html ); } }
\`\`\` \-\-- \## Fichier 3 :
\`includes/Generation/ContentValidator.php\` --- Validation & nettoyage
\`\`\`php \<?php /\*\* \* Validateur et nettoyeur des sorties IA. \* \*
Responsabilité : \* - Valider que chaque réponse IA est un JSON
parsable. \* - Forcer les champs obligatoires manquants. \* - Détecter
les promesses médicales / contenu interdit. \* - Générer un prompt de
correction si le JSON est invalide. \* \* \@package
TechrappySEO\\Generation \*/ declare( strict_types=1 ); namespace
TechrappySEO\\Generation; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class ContentValidator \*/ class ContentValidator { //
───────────────────────────────────────── // Patterns interdits
(promesses médicales) // ───────────────────────────────────────── /\*\*
\* Expressions interdites (promesses médicales, garanties, etc.). \* Ces
patterns déclenchent un warning dans les logs. \* \* \@var string\[\]
\*/ private array \$forbidden_patterns = \[
\'/guér(ir\|ison\|it\|issez)/ui\', \'/traitement\\s+définitif/ui\',
\'/remède\\s+(miracle\|infaillible)/ui\',
\'/résultats?\\s+garantis?/ui\', \'/cure\\s+définitive/ui\',
\'/soigne(r\|z)?\\s+(définitivement\|complètement)/ui\', \]; /\*\* \*
Champs obligatoires dans la réponse JSON finale. \* \* \@var string\[\]
\*/ private array \$required_fields = \[ \'metatitle\',
\'metadescription\', \'slug\', \'H1\', \'intro_html\', \'sections\', \];
// ───────────────────────────────────────── // Validation JSON brut //
───────────────────────────────────────── /\*\* \* Tente de parser un
texte brut en JSON valide. \* Nettoie les blocs markdown si nécessaire.
\* \* \@param string \$raw_text Texte brut retourné par l\'IA. \* \*
\@return array{ok: bool, data: array\|null, error: string} \*/ public
function parse_json( string \$raw_text ): array { \$clean =
\$this-\>strip_markdown( \$raw_text ); \$decoded = json_decode( \$clean,
true ); if ( JSON_ERROR_NONE !== json_last_error() \|\| ! is_array(
\$decoded ) ) { return \[ \'ok\' =\> false, \'data\' =\> null, \'error\'
=\> json_last_error_msg(), \]; } return \[ \'ok\' =\> true, \'data\' =\>
\$decoded, \'error\' =\> \'\', \]; } /\*\* \* Génère un prompt de
correction à envoyer à l\'IA \* lorsque la réponse n\'est pas un JSON
valide. \* \* \@param string \$original_prompt Prompt original envoyé.
\* \@param string \$bad_response Réponse invalide reçue. \* \@param
string \$json_error Message d\'erreur JSON. \* \* \@return string Prompt
de correction. \*/ public function build_correction_prompt( string
\$original_prompt, string \$bad_response, string \$json_error ): string
{ return sprintf( \"Ta réponse précédente n\'était pas un JSON valide
(erreur : %s).\\n\\n\" . \"Réponse invalide reçue :\\n%s\\n\\n\" .
\"INSTRUCTION STRICTE : Réponds UNIQUEMENT avec un objet JSON valide, \"
. \"sans texte avant ni après, sans balises markdown.\\n\\n\" . \"Prompt
original :\\n%s\", \$json_error, substr( \$bad_response, 0, 500 ),
\$original_prompt ); } // ───────────────────────────────────────── //
Validation des champs requis par étape //
───────────────────────────────────────── /\*\* \* Valide la structure
minimale d\'une réponse d\'étape. \* \* \@param string \$step_name Nom
de l\'étape. \* \@param array \$parsed Données parsées. \* \* \@return
array{valid: bool, missing: string\[\], warnings: string\[\]} \*/ public
function validate_step_response( string \$step_name, array \$parsed ):
array { \$required = \$this-\>get_required_keys_for_step( \$step_name );
\$missing = \[\]; \$warnings = \[\]; foreach ( \$required as \$key ) {
if ( ! array_key_exists( \$key, \$parsed ) ) { \$missing\[\] = \$key; }
} // Vérification des patterns interdits sur les valeurs string.
\$flat_text = \$this-\>flatten_to_text( \$parsed ); foreach (
\$this-\>forbidden_patterns as \$pattern ) { if ( preg_match( \$pattern,
\$flat_text ) ) { \$warnings\[\] = sprintf( \'Contenu potentiellement
interdit détecté (pattern : %s).\', \$pattern ); } } return \[ \'valid\'
=\> empty( \$missing ), \'missing\' =\> \$missing, \'warnings\' =\>
\$warnings, \]; } /\*\* \* Retourne les clés JSON requises par étape du
pipeline. \* \* \@param string \$step_name Nom de l\'étape. \* \*
\@return string\[\] \*/ private function get_required_keys_for_step(
string \$step_name ): array { \$schema = \[ \'intent\' =\> \[
\'intent_principale\', \'must_have_topics\', \'keywords_secondaires\'
\], \'plan\' =\> \[ \'H1\', \'slug_suggere\', \'sections\' \],
\'blocks_list\' =\> \[ \'nb_blocs_repetables\', \'blocs\' \], \'intro\'
=\> \[ \'intro_longue_html\' \], \'block_write\' =\> \[ \'H2\', \'html\'
\], \'conclusion_cta\' =\> \[ \'cta_html\' \], \'meta\' =\> \[
\'meta_title_1\', \'meta_desc_1\' \], \'faq\' =\> \[
\'faq_visible_html\', \'faq_jsonld\' \], \'internal_links\' =\> \[\],
\'anti_duplicate\' =\> \[ \'intro_finale_html\' \], \'qa\' =\> \[
\'score_seo\', \'score_humain\', \'problemes\' \], \]; return \$schema\[
\$step_name \] ?? \[\]; } // ─────────────────────────────────────────
// Nettoyage des valeurs // ─────────────────────────────────────────
/\*\* \* Nettoie et force les valeurs par défaut sur le résultat final
assemblé. \* \* \@param array\<string, mixed\> \$data Données
assemblées. \* \@param string \$mot_cle Mot-clé (fallback pour champs
vides). \* \* \@return array{data: array, warnings: string\[\]} \*/
public function sanitize_final_output( array \$data, string \$mot_cle ):
array { \$warnings = \[\]; // ── Slug ── if ( empty( \$data\[\'slug\'\]
) ) { \$data\[\'slug\'\] = sanitize_title( \$mot_cle ); \$warnings\[\] =
\'slug vide --- généré depuis le mot-clé.\'; } else { \$data\[\'slug\'\]
= sanitize_title( \$data\[\'slug\'\] ); } // ── H1 ── if ( empty(
\$data\[\'H1\'\] ) ) { \$data\[\'H1\'\] = \$mot_cle; \$warnings\[\] =
\'H1 vide --- remplacé par le mot-clé.\'; } // ── Metatitle ── if (
empty( \$data\[\'metatitle\'\] ) ) { \$data\[\'metatitle\'\] =
\$data\[\'H1\'\]; \$warnings\[\] = \'metatitle vide --- copié depuis
H1.\'; } // ── Metadescription ── if ( empty(
\$data\[\'metadescription\'\] ) ) { \$data\[\'metadescription\'\] =
\'\'; \$warnings\[\] = \'metadescription vide --- champ non rempli.\'; }
// ── intro_html ── if ( empty( \$data\[\'intro_html\'\] ) ) {
\$data\[\'intro_html\'\] = \'\'; \$warnings\[\] = \'intro_html vide.\';
} // ── sections ── if ( empty( \$data\[\'sections\'\] ) \|\| !
is_array( \$data\[\'sections\'\] ) ) { \$data\[\'sections\'\] = \[\];
\$warnings\[\] = \'sections vides --- aucun bloc H2 généré.\'; } // ──
faq ── if ( empty( \$data\[\'faq\'\] ) \|\| ! is_array(
\$data\[\'faq\'\] ) ) { \$data\[\'faq\'\] = \[ \'items\' =\> \[\],
\'schema_ld_json\' =\> \'\' \]; } // ── cta_block_html ── if ( empty(
\$data\[\'cta_block_html\'\] ) ) { \$data\[\'cta_block_html\'\] = \'\';
} return \[ \'data\' =\> \$data, \'warnings\' =\> \$warnings, \]; } //
───────────────────────────────────────── // Helpers //
───────────────────────────────────────── /\*\* \* Supprime les blocs
markdown \`\`\`json \... \`\`\` du texte brut. \* \* \@param string
\$text Texte brut. \* \* \@return string Texte nettoyé. \*/ public
function strip_markdown( string \$text ): string { \$text = trim( \$text
); if ( preg_match(
\'/\^\`\`\`(?:json)?\\s\*(\[\\s\\S\]\*?)\\s\*\`\`\`\$/m\', \$text,
\$matches ) ) { return trim( \$matches\[1\] ); } return \$text; } /\*\*
\* Aplatit un tableau en texte plat pour les vérifications de patterns.
\* \* \@param array\<mixed\> \$data Données à aplatir. \* \* \@return
string \*/ private function flatten_to_text( array \$data ): string {
\$parts = \[\]; array_walk_recursive( \$data, function ( \$value ) use (
&\$parts ): void { if ( is_string( \$value ) ) { \$parts\[\] = \$value;
} } ); return implode( \' \', \$parts ); } } \`\`\` \-\-- \## Fichier 4
: \`includes/Generation/GenerationLogger.php\` --- Logger dédié
\`\`\`php \<?php /\*\* \* Logger dédié au moteur de génération SEO. \*
\* Étend les fonctionnalités du Logger de base pour tracer \* chaque
étape du pipeline avec durée, tokens, coût. \* Ne logue jamais les clés
API. \* \* \@package TechrappySEO\\Generation \*/ declare(
strict_types=1 ); namespace TechrappySEO\\Generation; use
TechrappySEO\\Utils\\Logger; use TechrappySEO\\Utils\\CostEstimator; use
TechrappySEO\\Settings\\SettingsRepository; if ( ! defined( \'ABSPATH\'
) ) { exit; } /\*\* \* Class GenerationLogger \*/ class GenerationLogger
{ /\*\* \* Logger de base. \* \* \@var Logger \*/ private Logger
\$logger; /\*\* \* Statistiques globales accumulées. \* \* \@var
array\<string, mixed\> \*/ private array \$stats = \[
\'total_input_tokens\' =\> 0, \'total_output_tokens\' =\> 0,
\'total_duration_ms\' =\> 0, \'estimated_cost_usd\' =\> 0.0,
\'steps_completed\' =\> 0, \'steps_failed\' =\> 0, \]; /\*\* \* Log
structuré par étape. \* \* \@var array\<string, array\<string, mixed\>\>
\*/ private array \$step_log = \[\]; /\*\* \* Horodatage de démarrage de
chaque étape en cours. \* \* \@var array\<string, float\> \*/ private
array \$step_start_times = \[\]; /\*\* \* Constructeur. \* \* \@param
string \$request_id Identifiant de la requête. \*/ public function
\_\_construct( string \$request_id ) { \$this-\>logger = new Logger(
\$request_id ); } // ───────────────────────────────────────── // Cycle
de vie d\'une étape // ───────────────────────────────────────── /\*\*
\* Marque le démarrage d\'une étape. \* \* \@param string \$step Nom de
l\'étape. \* \* \@return void \*/ public function step_start( string
\$step ): void { \$this-\>step_start_times\[ \$step \] = microtime( true
); \$this-\>step_log\[ \$step \] = \[ \'status\' =\> \'running\',
\'started_at\' =\> time(), \'input_tokens\' =\> 0, \'output_tokens\' =\>
0, \'duration_ms\' =\> 0, \'cost_usd\' =\> 0.0, \'error\' =\> \'\',
\'warnings\' =\> \[\], \]; \$this-\>logger-\>info( \$step,
\'Démarrage.\' ); } /\*\* \* Marque la réussite d\'une étape avec ses
métriques. \* \* \@param string \$step Nom de l\'étape. \* \@param int
\$input_tokens Tokens en entrée. \* \@param int \$output_tokens Tokens
en sortie. \* \@param int \$duration_ms Durée en ms (0 = calculé auto).
\* \* \@return void \*/ public function step_success( string \$step, int
\$input_tokens = 0, int \$output_tokens = 0, int \$duration_ms = 0 ):
void { \$duration_ms = \$duration_ms \> 0 ? \$duration_ms :
\$this-\>calc_duration( \$step ); \$model = SettingsRepository::get(
\'openai_model\', \'gpt-4o\' ); \$cost_usd = CostEstimator::estimate(
\$model, \$input_tokens, \$output_tokens ); // Mettre à jour le log de
l\'étape. if ( isset( \$this-\>step_log\[ \$step \] ) ) {
\$this-\>step_log\[ \$step \]\[\'status\'\] = \'ok\';
\$this-\>step_log\[ \$step \]\[\'input_tokens\'\] = \$input_tokens;
\$this-\>step_log\[ \$step \]\[\'output_tokens\'\] = \$output_tokens;
\$this-\>step_log\[ \$step \]\[\'duration_ms\'\] = \$duration_ms;
\$this-\>step_log\[ \$step \]\[\'cost_usd\'\] = \$cost_usd; } //
Accumuler les stats globales. \$this-\>stats\[\'total_input_tokens\'\]
+= \$input_tokens; \$this-\>stats\[\'total_output_tokens\'\] +=
\$output_tokens; \$this-\>stats\[\'total_duration_ms\'\] +=
\$duration_ms; \$this-\>stats\[\'estimated_cost_usd\'\] += \$cost_usd;
\$this-\>stats\[\'steps_completed\'\]++; \$this-\>logger-\>info( \$step,
sprintf( \'Succès --- %dms --- %d tokens (in:%d / out:%d) --- \$%.4f\',
\$duration_ms, \$input_tokens + \$output_tokens, \$input_tokens,
\$output_tokens, \$cost_usd ) ); } /\*\* \* Marque l\'échec d\'une
étape. \* \* \@param string \$step Nom de l\'étape. \* \@param string
\$error Message d\'erreur (sans clé API). \* \@param bool \$is_blocking
Si true, le pipeline doit s\'arrêter. \* \* \@return void \*/ public
function step_error( string \$step, string \$error, bool \$is_blocking =
true ): void { \$duration_ms = \$this-\>calc_duration( \$step ); //
Masquer toute clé API potentiellement dans le message. \$safe_error =
\$this-\>sanitize_error_message( \$error ); if ( isset(
\$this-\>step_log\[ \$step \] ) ) { \$this-\>step_log\[ \$step
\]\[\'status\'\] = \'error\'; \$this-\>step_log\[ \$step
\]\[\'duration_ms\'\] = \$duration_ms; \$this-\>step_log\[ \$step
\]\[\'error\'\] = \$safe_error; } \$this-\>stats\[\'steps_failed\'\]++;
\$this-\>logger-\>error( \$step, sprintf( \'Échec (%s)%s : %s\',
\$is_blocking ? \'bloquant\' : \'non-bloquant\', \$duration_ms \> 0 ? \'
--- \' . \$duration_ms . \'ms\' : \'\', \$safe_error ) ); } /\*\* \*
Ajoute un avertissement à une étape. \* \* \@param string \$step Nom de
l\'étape. \* \@param string \$warning Message d\'avertissement. \* \*
\@return void \*/ public function step_warning( string \$step, string
\$warning ): void { if ( isset( \$this-\>step_log\[ \$step \] ) ) {
\$this-\>step_log\[ \$step \]\[\'warnings\'\]\[\] = \$warning; }
\$this-\>logger-\>warning( \$step, \$warning ); } /\*\* \* Log une
information libre. \* \* \@param string \$step Étape concernée. \*
\@param string \$message Message. \* \* \@return void \*/ public
function info( string \$step, string \$message ): void {
\$this-\>logger-\>info( \$step, \$message ); } //
───────────────────────────────────────── // Accesseurs //
───────────────────────────────────────── /\*\* \* Retourne les
statistiques globales accumulées. \* \* \@return array\<string, mixed\>
\*/ public function get_stats(): array { return \$this-\>stats; } /\*\*
\* Retourne les logs bruts du Logger de base. \* \* \@return array\<int,
array{t: int, step: string, msg: string, level: string}\> \*/ public
function get_raw_logs(): array { return \$this-\>logger-\>get_logs(); }
/\*\* \* Retourne le log structuré par étape. \* \* \@return
array\<string, array\<string, mixed\>\> \*/ public function
get_step_log(): array { return \$this-\>step_log; } /\*\* \* Retourne
tous les avertissements collectés (toutes étapes). \* \* \@return
string\[\] \*/ public function get_all_warnings(): array { \$warnings =
\[\]; foreach ( \$this-\>step_log as \$step =\> \$log ) { foreach (
\$log\[\'warnings\'\] ?? \[\] as \$w ) { \$warnings\[\] = \"\[{\$step}\]
{\$w}\"; } } return \$warnings; } //
───────────────────────────────────────── // Helpers //
───────────────────────────────────────── /\*\* \* Calcule la durée
d\'une étape depuis son démarrage. \* \* \@param string \$step Nom de
l\'étape. \* \* \@return int Durée en ms. \*/ private function
calc_duration( string \$step ): int { if ( ! isset(
\$this-\>step_start_times\[ \$step \] ) ) { return 0; } return (int)
round( ( microtime( true ) - \$this-\>step_start_times\[ \$step \] ) \*
1000 ); } /\*\* \* Masque les informations sensibles dans un message
d\'erreur. \* Supprime les patterns de clés API (sk-\...). \* \* \@param
string \$message Message brut. \* \* \@return string Message sécurisé.
\*/ private function sanitize_error_message( string \$message ): string
{ // Masquer les clés OpenAI (sk-\...) \$message = preg_replace(
\'/sk-\[a-zA-Z0-9\\-\_\]{20,}/\', \'\[API_KEY_HIDDEN\]\', \$message ) ??
\$message; // Masquer les tokens Bearer \$message = preg_replace(
\'/Bearer\\s+\[a-zA-Z0-9\\-\_\\.\]{20,}/\', \'Bearer \[HIDDEN\]\',
\$message ) ?? \$message; return \$message; } } \`\`\` \-\-- \## Fichier
5 : \`includes/Generation/GenerationEngine.php\` --- Moteur principal
\`\`\`php \<?php /\*\* \* Moteur de génération SEO --- orchestrateur du
pipeline complet. \* \* Responsabilité : exécuter les 9 étapes du
pipeline dans l\'ordre, \* valider chaque sortie, assembler le
GenerationResult final. \* \* Ce module est SANS EFFET DE BORD WordPress
: \* - Pas de wp_insert_post() \* - Pas de update_post_meta() \* - Pas
d\'Action Scheduler \* \* \@package TechrappySEO\\Generation \*/
declare( strict_types=1 ); namespace TechrappySEO\\Generation; use
TechrappySEO\\AI\\AIClient; use TechrappySEO\\AI\\AIResponse; use
TechrappySEO\\AI\\PromptManager; if ( ! defined( \'ABSPATH\' ) ) { exit;
} /\*\* \* Class GenerationEngine \*/ class GenerationEngine { //
───────────────────────────────────────── // Dépendances //
───────────────────────────────────────── /\*\* \@var AIClient \*/
private AIClient \$ai_client; /\*\* \@var PromptManager \*/ private
PromptManager \$prompt_manager; /\*\* \@var ContentValidator \*/ private
ContentValidator \$validator; /\*\* \@var GenerationLogger \*/ private
GenerationLogger \$logger; // ─────────────────────────────────────────
// Constructeur // ───────────────────────────────────────── /\*\* \*
Constructeur. \* \* \@param GenerationLogger\|null \$logger Logger
externe (null = auto-créé). \*/ public function \_\_construct(
?GenerationLogger \$logger = null ) { \$this-\>prompt_manager = new
PromptManager(); \$this-\>validator = new ContentValidator(); // Le
logger est créé ici avec un ID temporaire si non fourni. // Il sera
réinitialisé avec le request_id réel lors de run(). \$this-\>logger =
\$logger ?? new GenerationLogger(
\\TechrappySEO\\Utils\\UuidGenerator::generate() ); } //
───────────────────────────────────────── // Point d\'entrée principal
// ───────────────────────────────────────── /\*\* \* Lance le pipeline
complet de génération SEO. \* \* \@param GenerationRequest \$request
Requête de génération validée. \* \* \@return GenerationResult Résultat
structuré complet. \*/ public function run( GenerationRequest \$request
): GenerationResult { // Réinitialiser le logger avec le request_id.
\$this-\>logger = new GenerationLogger( \$request-\>get_request_id() );
\$this-\>logger-\>info( \'engine\', sprintf( \'Démarrage du pipeline ---
mot-clé: \"%s\" --- type: %s\', \$request-\>get_mot_cle(),
\$request-\>get_type_contenu() ) ); // Instancier le client IA (le
logger est passé pour traçage). \$this-\>ai_client = new AIClient(); //
── Contexte partagé entre toutes les étapes ── \$ctx = \[ \'request\'
=\> \$request, \'mot_cle\' =\> \$request-\>get_mot_cle(), \'profession\'
=\> \$request-\>get_profession(), \'city\' =\> \$request-\>get_city(),
\'type\' =\> \$request-\>get_type_contenu(), \]; // ── Résultat brut
accumulé ── \$output = \[ \'request_id\' =\>
\$request-\>get_request_id(), \]; //
───────────────────────────────────── // ÉTAPE 1 --- Intent //
───────────────────────────────────── \$intent =
\$this-\>run_step_intent( \$ctx ); if ( null === \$intent ) { return
\$this-\>abort( \$output, \'Échec étape intent.\' ); }
\$ctx\[\'intent\'\] = \$intent; // ─────────────────────────────────────
// ÉTAPE 2 --- Plan // ───────────────────────────────────── \$plan =
\$this-\>run_step_plan( \$ctx ); if ( null === \$plan ) { return
\$this-\>abort( \$output, \'Échec étape plan.\' ); } \$ctx\[\'plan\'\] =
\$plan; // Extraire H1 + slug depuis le plan. \$output\[\'H1\'\] =
\$plan\[\'H1\'\] ?? \$request-\>get_mot_cle(); \$output\[\'slug\'\] =
sanitize_title( \$plan\[\'slug_suggere\'\] ?? \$request-\>get_mot_cle()
); // ───────────────────────────────────── // ÉTAPE 2B --- Blocks list
// ───────────────────────────────────── \$blocks_list =
\$this-\>run_step_blocks_list( \$ctx ); if ( null === \$blocks_list ) {
return \$this-\>abort( \$output, \'Échec étape blocks_list.\' ); }
\$ctx\[\'blocks_list\'\] = \$blocks_list; //
───────────────────────────────────── // ÉTAPE 3 --- Intro //
───────────────────────────────────── \$intro = \$this-\>run_step_intro(
\$ctx ); \$output\[\'intro_html\'\] = \$intro ?? \'\'; if ( empty(
\$intro ) ) { \$this-\>logger-\>step_warning( \'intro\', \'Intro vide
--- pipeline continue.\' ); } // ─────────────────────────────────────
// ÉTAPE 4 --- Rédaction blocs H2 (×N) //
───────────────────────────────────── \$sections =
\$this-\>run_step_write_blocks( \$ctx ); \$output\[\'sections\'\] =
\$sections; // ───────────────────────────────────── // ÉTAPE 5 ---
Liens internes (si pages dispo) // ─────────────────────────────────────
if ( \$request-\>has_pages_site() ) { \$links =
\$this-\>run_step_internal_links( \$ctx ); // Injecter les liens dans la
dernière section si disponible. if ( ! empty( \$links ) && ! empty(
\$output\[\'sections\'\] ) ) { \$last_idx = count(
\$output\[\'sections\'\] ) - 1; \$output\[\'sections\'\]\[ \$last_idx
\]\[\'internal_links_html\'\] = \$links; } } //
───────────────────────────────────── // ÉTAPE 6 --- Meta //
───────────────────────────────────── \$meta = \$this-\>run_step_meta(
\$ctx ); \$output\[\'metatitle\'\] = \$meta\[\'metatitle\'\] ??
\$output\[\'H1\'\] ?? \'\'; \$output\[\'metadescription\'\] =
\$meta\[\'metadescription\'\] ?? \'\'; //
───────────────────────────────────── // ÉTAPE 7 --- FAQ //
───────────────────────────────────── \$faq = \$this-\>run_step_faq(
\$ctx ); \$output\[\'faq\'\] = \$faq ?? \[ \'items\' =\> \[\],
\'schema_ld_json\' =\> \'\' \]; // ─────────────────────────────────────
// ÉTAPE 8 --- Conclusion + CTA // ─────────────────────────────────────
\$cta = \$this-\>run_step_conclusion_cta( \$ctx );
\$output\[\'cta_block_html\'\] = \$cta ?? \'\'; //
───────────────────────────────────── // ÉTAPE 9 --- QA //
───────────────────────────────────── \$qa_result =
\$this-\>run_step_qa( \$output, \$ctx ); \$output\[\'qa_score\'\] =
\$qa_result; // ───────────────────────────────────── // ASSEMBLAGE
FINAL // ───────────────────────────────────── \$validated =
\$this-\>validator-\>sanitize_final_output( \$output,
\$request-\>get_mot_cle() ); foreach ( \$validated\[\'warnings\'\] as
\$w ) { \$this-\>logger-\>step_warning( \'assembly\', \$w ); } \$output
= \$validated\[\'data\'\]; \$output\[\'stats\'\] =
\$this-\>logger-\>get_stats(); \$output\[\'warnings\'\]=
\$this-\>logger-\>get_all_warnings(); \$this-\>logger-\>info(
\'engine\', sprintf( \'Pipeline terminé --- %d étapes OK / %d erreurs
--- coût total : \$%.4f\',
\$this-\>logger-\>get_stats()\[\'steps_completed\'\],
\$this-\>logger-\>get_stats()\[\'steps_failed\'\],
\$this-\>logger-\>get_stats()\[\'estimated_cost_usd\'\] ) ); return
GenerationResult::from_array( \$output ); } //
───────────────────────────────────────── // Étapes individuelles //
───────────────────────────────────────── /\*\* \* ÉTAPE 1 --- Analyse
de l\'intention de recherche. \* \* \@param array\<string, mixed\> \$ctx
Contexte. \* \* \@return array\<string, mixed\>\|null Données intent ou
null si échec. \*/ private function run_step_intent( array \$ctx ):
?array { \$step = \'intent\'; \$this-\>logger-\>step_start( \$step );
\$response = \$this-\>call_ai( \$step, \[ \'mot_cle\' =\>
\$ctx\[\'mot_cle\'\], \'type_contenu\' =\> \$ctx\[\'type\'\],
\'profession\' =\> \$ctx\[\'profession\'\], \] ); if ( null ===
\$response ) { return null; } \$parsed = \$response-\>get_parsed();
\$validation = \$this-\>validator-\>validate_step_response( \$step,
\$parsed ?? \[\] ); foreach ( \$validation\[\'warnings\'\] as \$w ) {
\$this-\>logger-\>step_warning( \$step, \$w ); } if ( !
\$validation\[\'valid\'\] ) { \$this-\>logger-\>step_error( \$step,
\'Champs manquants : \' . implode( \', \', \$validation\[\'missing\'\] )
); return null; } \$this-\>logger-\>step_success( \$step,
\$response-\>get_input_tokens(), \$response-\>get_output_tokens(),
\$response-\>get_duration_ms() ); return \$parsed; } /\*\* \* ÉTAPE 2
--- Génération du plan SEO. \* \* \@param array\<string, mixed\> \$ctx
Contexte. \* \* \@return array\<string, mixed\>\|null \*/ private
function run_step_plan( array \$ctx ): ?array { \$step = \'plan\';
\$this-\>logger-\>step_start( \$step ); \$response = \$this-\>call_ai(
\$step, \[ \'mot_cle\' =\> \$ctx\[\'mot_cle\'\], \'profession\' =\>
\$ctx\[\'profession\'\], \'intent_json\' =\> \$ctx\[\'intent\'\], \] );
if ( null === \$response ) { return null; } \$parsed =
\$response-\>get_parsed(); \$validation =
\$this-\>validator-\>validate_step_response( \$step, \$parsed ?? \[\] );
foreach ( \$validation\[\'warnings\'\] as \$w ) {
\$this-\>logger-\>step_warning( \$step, \$w ); } if ( !
\$validation\[\'valid\'\] ) { \$this-\>logger-\>step_error( \$step,
\'Champs manquants : \' . implode( \', \', \$validation\[\'missing\'\] )
); return null; } \$this-\>logger-\>step_success( \$step,
\$response-\>get_input_tokens(), \$response-\>get_output_tokens(),
\$response-\>get_duration_ms() ); return \$parsed; } /\*\* \* ÉTAPE 2B
--- Liste ordonnée des blocs à rédiger. \* \* \@param array\<string,
mixed\> \$ctx Contexte. \* \* \@return array\<string, mixed\>\|null \*/
private function run_step_blocks_list( array \$ctx ): ?array { \$step =
\'blocks_list\'; \$this-\>logger-\>step_start( \$step ); \$response =
\$this-\>call_ai( \$step, \[ \'mot_cle\' =\> \$ctx\[\'mot_cle\'\],
\'plan_json\' =\> \$ctx\[\'plan\'\], \] ); if ( null === \$response ) {
return null; } \$parsed = \$response-\>get_parsed(); \$validation =
\$this-\>validator-\>validate_step_response( \$step, \$parsed ?? \[\] );
if ( ! \$validation\[\'valid\'\] ) { \$this-\>logger-\>step_error(
\$step, \'Champs manquants : \' . implode( \', \',
\$validation\[\'missing\'\] ) ); return null; }
\$this-\>logger-\>step_success( \$step, \$response-\>get_input_tokens(),
\$response-\>get_output_tokens(), \$response-\>get_duration_ms() );
return \$parsed; } /\*\* \* ÉTAPE 3 --- Rédaction de l\'introduction
SEO. \* \* \@param array\<string, mixed\> \$ctx Contexte. \* \* \@return
string\|null HTML de l\'intro ou null si échec. \*/ private function
run_step_intro( array \$ctx ): ?string { \$step = \'intro\';
\$this-\>logger-\>step_start( \$step ); \$h1 =
\$ctx\[\'plan\'\]\[\'H1\'\] ?? \$ctx\[\'mot_cle\'\]; \$response =
\$this-\>call_ai( \$step, \[ \'mot_cle\' =\> \$ctx\[\'mot_cle\'\],
\'H1\' =\> \$h1, \'plan_json\' =\> \$ctx\[\'plan\'\], \] ); if ( null
=== \$response ) { // Non bloquant : l\'intro vide est gérée en aval.
\$this-\>logger-\>step_error( \$step, \'Erreur intro --- non
bloquant.\', false ); return null; } \$parsed =
\$response-\>get_parsed(); \$intro = (string) (
\$parsed\[\'intro_longue_html\'\] ?? \'\' );
\$this-\>logger-\>step_success( \$step, \$response-\>get_input_tokens(),
\$response-\>get_output_tokens(), \$response-\>get_duration_ms() );
return \$intro ?: null; } /\*\* \* ÉTAPE 4 --- Rédaction de chaque bloc
H2 (loop). \* \* \@param array\<string, mixed\> \$ctx Contexte. \* \*
\@return array\<int, array{h2: string, html: string,
internal_links_html: string}\> \*/ private function
run_step_write_blocks( array \$ctx ): array { \$step = \'block_write\';
\$blocs = \$ctx\[\'blocks_list\'\]\[\'blocs\'\] ?? \[\]; \$written =
\[\]; if ( empty( \$blocs ) ) { \$this-\>logger-\>step_warning( \$step,
\'Aucun bloc à rédiger.\' ); return \[\]; } \$this-\>logger-\>info(
\$step, sprintf( \'%d bloc(s) à rédiger.\', count( \$blocs ) ) );
foreach ( \$blocs as \$index =\> \$bloc ) { \$bloc_step = \$step .
\'\_\' . ( \$index + 1 ); \$this-\>logger-\>step_start( \$bloc_step );
\$response = \$this-\>call_ai( \$step, \[ \'mot_cle\' =\>
\$ctx\[\'mot_cle\'\], \'profession\' =\> \$ctx\[\'profession\'\],
\'bloc_json\' =\> \$bloc, \] ); if ( null === \$response ) { // Bloc en
erreur : on insère un placeholder et on continue.
\$this-\>logger-\>step_error( \$bloc_step, \'Bloc \' . ( \$index + 1 ) .
\' en erreur.\', false ); \$written\[\] = \[ \'h2\' =\> (string) (
\$bloc\[\'H2\'\] ?? \'\' ), \'html\' =\> \'\', \'internal_links_html\'
=\> \'\', \]; continue; } \$parsed = \$response-\>get_parsed();
\$written\[\] = \[ \'h2\' =\> (string) ( \$parsed\[\'H2\'\] ??
\$bloc\[\'H2\'\] ?? \'\' ), \'html\' =\> (string) ( \$parsed\[\'html\'\]
?? \'\' ), \'internal_links_html\' =\> \'\', \];
\$this-\>logger-\>step_success( \$bloc_step,
\$response-\>get_input_tokens(), \$response-\>get_output_tokens(),
\$response-\>get_duration_ms() ); } return \$written; } /\*\* \* ÉTAPE 5
--- Suggestion de liens internes. \* \* \@param array\<string, mixed\>
\$ctx Contexte. \* \* \@return string HTML du bloc de liens ou chaîne
vide. \*/ private function run_step_internal_links( array \$ctx ):
string { \$step = \'internal_links\'; \$this-\>logger-\>step_start(
\$step ); /\*\* \@var GenerationRequest \$request \*/ \$request =
\$ctx\[\'request\'\]; \$response = \$this-\>call_ai( \$step, \[
\'mot_cle\' =\> \$ctx\[\'mot_cle\'\], \'pages_site_json\' =\>
\$request-\>get_pages_site_liste(), \] ); if ( null === \$response ) {
\$this-\>logger-\>step_error( \$step, \'Échec liens internes --- non
bloquant.\', false ); return \'\'; } // La réponse est un tableau de
liens JSON. \$parsed = \$response-\>get_parsed(); \$links = is_array(
\$parsed ) ? \$parsed : \[\]; // Normaliser : \[{url, anchor}, \...\] if
( isset( \$links\[0\] ) && is_array( \$links\[0\] ) ) { // Format
tableau direct. } elseif ( isset( \$links\[\'links\'\] ) ) { \$links =
\$links\[\'links\'\]; } else { \$links = \[\]; } \$links = array_slice(
\$links, 0, 5 ); \$html = \$this-\>build_internal_links_html( \$links );
\$this-\>logger-\>step_success( \$step, \$response-\>get_input_tokens(),
\$response-\>get_output_tokens(), \$response-\>get_duration_ms() );
return \$html; } /\*\* \* ÉTAPE 6 --- Génération des metas SEO. \* \*
\@param array\<string, mixed\> \$ctx Contexte. \* \* \@return
array{metatitle: string, metadescription: string} \*/ private function
run_step_meta( array \$ctx ): array { \$step = \'meta\';
\$this-\>logger-\>step_start( \$step ); \$plan = \$ctx\[\'plan\'\] ??
\[\]; \$intent = \$ctx\[\'intent\'\] ?? \[\]; \$h1 = \$plan\[\'H1\'\] ??
\$ctx\[\'mot_cle\'\]; \$intent_principale =
\$intent\[\'intent_principale\'\] ?? \'\'; \$response =
\$this-\>call_ai( \$step, \[ \'mot_cle\' =\> \$ctx\[\'mot_cle\'\],
\'H1\' =\> \$h1, \'intent_principale\' =\> \$intent_principale,
\'profession\' =\> \$ctx\[\'profession\'\], \] ); if ( null ===
\$response ) { \$this-\>logger-\>step_error( \$step, \'Échec meta ---
non bloquant.\', false ); return \[ \'metatitle\' =\> \$h1,
\'metadescription\' =\> \'\' \]; } \$parsed = \$response-\>get_parsed()
?? \[\]; \$metatitle = (string) ( \$parsed\[\'meta_title_1\'\] ?? \$h1
); \$metadescription = (string) ( \$parsed\[\'meta_desc_1\'\] ?? \'\' );
// Validation longueurs SEO. \$title_len = strlen( \$metatitle );
\$desc_len = strlen( \$metadescription ); if ( \$title_len \< 55 \|\|
\$title_len \> 65 ) { \$this-\>logger-\>step_warning( \$step, sprintf(
\'meta_title hors limites (%d chars, attendu 55-65).\', \$title_len ) );
} if ( \$desc_len \< 140 \|\| \$desc_len \> 160 ) {
\$this-\>logger-\>step_warning( \$step, sprintf( \'meta_desc hors
limites (%d chars, attendu 140-160).\', \$desc_len ) ); }
\$this-\>logger-\>step_success( \$step, \$response-\>get_input_tokens(),
\$response-\>get_output_tokens(), \$response-\>get_duration_ms() );
return \[ \'metatitle\' =\> \$metatitle, \'metadescription\' =\>
\$metadescription, \]; } /\*\* \* ÉTAPE 7 --- Génération de la FAQ
(HTML + JSON-LD). \* \* \@param array\<string, mixed\> \$ctx Contexte.
\* \* \@return array{items: array, schema_ld_json: string}\|null \*/
private function run_step_faq( array \$ctx ): ?array { \$step = \'faq\';
\$this-\>logger-\>step_start( \$step ); \$response = \$this-\>call_ai(
\$step, \[ \'mot_cle\' =\> \$ctx\[\'mot_cle\'\], \'plan_json\' =\>
\$ctx\[\'plan\'\], \'profession\' =\> \$ctx\[\'profession\'\], \] ); if
( null === \$response ) { \$this-\>logger-\>step_error( \$step, \'Échec
FAQ --- non bloquant.\', false ); return null; } \$parsed =
\$response-\>get_parsed() ?? \[\]; \$faq_visible = (string) (
\$parsed\[\'faq_visible_html\'\] ?? \'\' ); \$faq_jsonld_raw = (string)
( \$parsed\[\'faq_jsonld\'\] ?? \'\' ); // Parser la FAQ HTML en items
structurés. \$items = \$this-\>parse_faq_items_from_html( \$faq_visible
); // Extraire le JSON-LD. \$schema_ld_json =
\$this-\>extract_jsonld_content( \$faq_jsonld_raw );
\$this-\>logger-\>step_success( \$step, \$response-\>get_input_tokens(),
\$response-\>get_output_tokens(), \$response-\>get_duration_ms() );
return \[ \'items\' =\> \$items, \'schema_ld_json\' =\>
\$schema_ld_json, \]; } /\*\* \* ÉTAPE 8 --- Conclusion et CTA. \* \*
\@param array\<string, mixed\> \$ctx Contexte. \* \* \@return
string\|null HTML du CTA ou null si échec. \*/ private function
run_step_conclusion_cta( array \$ctx ): ?string { \$step =
\'conclusion_cta\'; \$this-\>logger-\>step_start( \$step ); \$plan =
\$ctx\[\'plan\'\] ?? \[\]; \$h1 = \$plan\[\'H1\'\] ??
\$ctx\[\'mot_cle\'\]; \$response = \$this-\>call_ai( \$step, \[
\'mot_cle\' =\> \$ctx\[\'mot_cle\'\], \'H1\' =\> \$h1, \'plan_json\' =\>
\$ctx\[\'plan\'\], \'profession\' =\> \$ctx\[\'profession\'\], \] ); if
( null === \$response ) { \$this-\>logger-\>step_error( \$step, \'Échec
conclusion/CTA --- non bloquant.\', false ); return null; } \$parsed =
\$response-\>get_parsed() ?? \[\]; \$cta_html = (string) (
\$parsed\[\'cta_html\'\] ?? \'\' ); // Injecter l\'URL de CTA si fournie
dans les contraintes. /\*\* \@var GenerationRequest \$request \*/
\$request = \$ctx\[\'request\'\]; \$cta_url =
\$request-\>get_contrainte( \'cta_url\', \'\' ); if ( ! empty( \$cta_url
) ) { \$cta_html = str_replace( \'/prendre-rendez-vous\', esc_url(
\$cta_url ), \$cta_html ); } \$this-\>logger-\>step_success( \$step,
\$response-\>get_input_tokens(), \$response-\>get_output_tokens(),
\$response-\>get_duration_ms() ); return \$cta_html ?: null; } /\*\* \*
ÉTAPE 9 --- QA scoring et vérification finale. \* Non bloquant --- le
pipeline se termine même en cas d\'échec. \* \* \@param array\<string,
mixed\> \$output Données assemblées jusqu\'ici. \* \@param
array\<string, mixed\> \$ctx Contexte. \* \* \@return array{score_seo:
int, score_humain: int, problemes: array, fixes_rapides: array} \*/
private function run_step_qa( array \$output, array \$ctx ): array {
\$step = \'qa\'; \$this-\>logger-\>step_start( \$step ); // Assembler le
HTML complet pour le QA. \$full_html = \$this-\>build_qa_html( \$output
); if ( empty( \$full_html ) ) { \$this-\>logger-\>step_warning( \$step,
\'HTML vide pour QA --- étape ignorée.\' ); return \[ \'score_seo\' =\>
0, \'score_humain\' =\> 0, \'problemes\' =\> \[\], \'fixes_rapides\' =\>
\[\] \]; } \$response = \$this-\>call_ai( \$step, \[ \'mot_cle\' =\>
\$ctx\[\'mot_cle\'\], \'full_content_html\' =\> \$full_html, \] ); if (
null === \$response ) { \$this-\>logger-\>step_error( \$step, \'Échec QA
--- non bloquant.\', false ); return \[ \'score_seo\' =\> 0,
\'score_humain\' =\> 0, \'problemes\' =\> \[\], \'fixes_rapides\' =\>
\[\] \]; } \$parsed = \$response-\>get_parsed() ?? \[\];
\$this-\>logger-\>step_success( \$step, \$response-\>get_input_tokens(),
\$response-\>get_output_tokens(), \$response-\>get_duration_ms() );
\$this-\>logger-\>info( \$step, sprintf( \'Score SEO: %d/100 --- Score
humain: %d/100 --- %d problème(s).\', (int) ( \$parsed\[\'score_seo\'\]
?? 0 ), (int) ( \$parsed\[\'score_humain\'\] ?? 0 ), count(
\$parsed\[\'problemes\'\] ?? \[\] ) ) ); return \[ \'score_seo\' =\>
(int) ( \$parsed\[\'score_seo\'\] ?? 0 ), \'score_humain\' =\> (int) (
\$parsed\[\'score_humain\'\] ?? 0 ), \'problemes\' =\> (array) (
\$parsed\[\'problemes\'\] ?? \[\] ), \'fixes_rapides\' =\> (array) (
\$parsed\[\'fixes_rapides\'\]?? \[\] ), \]; } //
───────────────────────────────────────── // Appel IA centralisé avec
retry JSON // ───────────────────────────────────────── /\*\* \* Appelle
le client IA pour une étape donnée. \* Intègre la logique de retry si la
réponse JSON est invalide. \* \* \@param string \$step_key Clé du prompt
à utiliser. \* \@param array\<string, mixed\> \$variables Variables à
injecter dans le prompt. \* \* \@return AIResponse\|null Réponse valide
ou null si échec définitif. \*/ private function call_ai( string
\$step_key, array \$variables ): ?AIResponse { try { \$prompt_cfg =
\$this-\>prompt_manager-\>get_rendered( \$step_key, \$variables ); }
catch ( \\RuntimeException \$e ) { \$this-\>logger-\>step_error(
\$step_key, \'Prompt introuvable : \' . \$e-\>getMessage() ); return
null; } // Premier appel. \$response = \$this-\>ai_client-\>generate(
prompt: \$prompt_cfg\[\'prompt\'\], system_prompt:
\$prompt_cfg\[\'system\'\], response_format: \$prompt_cfg\[\'format\'\]
); if ( \$response-\>is_error() ) { \$this-\>logger-\>step_error(
\$step_key, \$response-\>get_error_message() ); return null; } // Si
format JSON : valider la parseabilité. if ( \'json_object\' ===
\$prompt_cfg\[\'format\'\] ) { \$validation =
\$this-\>validator-\>parse_json( \$response-\>get_content() ); // JSON
invalide → retry avec prompt de correction. if ( !
\$validation\[\'ok\'\] ) { \$this-\>logger-\>step_warning( \$step_key,
\'JSON invalide --- tentative de correction. Erreur : \' .
\$validation\[\'error\'\] ); \$correction_prompt =
\$this-\>validator-\>build_correction_prompt(
\$prompt_cfg\[\'prompt\'\], \$response-\>get_content(),
\$validation\[\'error\'\] ); \$response = \$this-\>ai_client-\>generate(
prompt: \$correction_prompt, system_prompt: \$prompt_cfg\[\'system\'\],
response_format: \'json_object\' ); if ( \$response-\>is_error() ) {
\$this-\>logger-\>step_error( \$step_key, \'Retry correction échoué : \'
. \$response-\>get_error_message() ); return null; } // Vérifier une
dernière fois. \$validation2 = \$this-\>validator-\>parse_json(
\$response-\>get_content() ); if ( ! \$validation2\[\'ok\'\] ) {
\$this-\>logger-\>step_error( \$step_key, \'JSON toujours invalide après
correction.\' ); return null; } } } return \$response; \`\`\`php //
───────────────────────────────────────── // Helpers internes //
───────────────────────────────────────── /\*\* \* Construit le HTML du
bloc de liens internes depuis le tableau de liens. \* \* \@param
array\<int, array{url: string, anchor: string}\> \$links Liens suggérés
par l\'IA. \* \* \@return string HTML
\<ul\>\<li\>\<a\>\...\</a\>\</li\>\</ul\> \*/ private function
build_internal_links_html( array \$links ): string { if ( empty( \$links
) ) { return \'\'; } \$items = \'\'; foreach ( \$links as \$link ) {
\$url = esc_url( \$link\[\'url\'\] ?? \'\' ); \$anchor = esc_html(
\$link\[\'anchor\'\] ?? \$url ); if ( empty( \$url ) ) { continue; }
\$items .= sprintf( \'\<li\>\<a href=\"%s\"\>%s\</a\>\</li\>\', \$url,
\$anchor ); } if ( empty( \$items ) ) { return \'\'; } return \'\<ul
class=\"techrappy-internal-links\"\>\' . \$items . \'\</ul\>\'; } /\*\*
\* Parse les items Q/R depuis le HTML visible de la FAQ. \* Extrait les
paires \<summary\>question\</summary\>\<p\>réponse\</p\> \* depuis les
balises \<details\>. \* \* \@param string \$faq_html HTML de la FAQ
généré par l\'IA. \* \* \@return array\<int, array{q: string, a_html:
string}\> \*/ private function parse_faq_items_from_html( string
\$faq_html ): array { if ( empty( \$faq_html ) ) { return \[\]; }
\$items = \[\]; // Extraire chaque bloc \<details\>\...\</details\>.
preg_match_all(
\'/\<details\[\^\>\]\*\>(\[\\s\\S\]\*?)\<\\/details\>/i\', \$faq_html,
\$details_matches ); if ( empty( \$details_matches\[1\] ) ) { return
\[\]; } foreach ( \$details_matches\[1\] as \$detail_content ) { //
Extraire la question depuis \<summary\>. \$question = \'\'; if (
preg_match( \'/\<summary\[\^\>\]\*\>(\[\\s\\S\]\*?)\<\\/summary\>/i\',
\$detail_content, \$q_match ) ) { \$question = wp_strip_all_tags(
\$q_match\[1\] ); \$question = trim( \$question ); } // Extraire la
réponse (tout ce qui suit le \</summary\>). \$answer_html = \'\'; if (
preg_match( \'/\<\\/summary\>(\[\\s\\S\]\*?)\$/i\', \$detail_content,
\$a_match ) ) { \$answer_html = trim( \$a_match\[1\] ); // Conserver
uniquement les balises autorisées dans la réponse. \$answer_html =
wp_kses( \$answer_html, \[ \'p\' =\> \[\], \'strong\' =\> \[\], \'em\'
=\> \[\], \'ul\' =\> \[\], \'ol\' =\> \[\], \'li\' =\> \[\], \'a\' =\>
\[ \'href\' =\> \[\], \'title\' =\> \[\] \], \] ); } if ( ! empty(
\$question ) && ! empty( \$answer_html ) ) { \$items\[\] = \[ \'q\' =\>
\$question, \'a_html\' =\> \$answer_html, \]; } } return \$items; }
/\*\* \* Extrait le contenu JSON depuis une balise \<script
type=\"application/ld+json\"\>. \* \* \@param string \$raw Contenu brut
retourné par l\'IA (peut contenir la balise script). \* \* \@return
string JSON brut extrait, ou chaîne vide si invalide. \*/ private
function extract_jsonld_content( string \$raw ): string { if ( empty(
\$raw ) ) { return \'\'; } // Cas 1 : la réponse contient une balise
\<script type=\"application/ld+json\"\>. if ( preg_match(
\'/\<script\[\^\>\]\*type=\[\"\\\'\]application\\/ld\\+json\[\"\\\'\]\[\^\>\]\*\>(\[\\s\\S\]\*?)\<\\/script\>/i\',
\$raw, \$matches ) ) { \$json_str = trim( \$matches\[1\] ); } else { //
Cas 2 : la réponse est directement du JSON. \$json_str = trim( \$raw );
} // Valider que c\'est du JSON parsable. \$decoded = json_decode(
\$json_str, true ); if ( JSON_ERROR_NONE !== json_last_error() \|\| !
is_array( \$decoded ) ) { \$this-\>logger-\>step_warning( \'faq\',
\'JSON-LD FAQ invalide : \' . json_last_error_msg() ); return \'\'; } //
S\'assurer que c\'est bien un FAQPage. if ( ( \$decoded\[\'@type\'\] ??
\'\' ) !== \'FAQPage\' ) { \$this-\>logger-\>step_warning( \'faq\',
\'JSON-LD : \@type FAQPage manquant.\' ); } return \$json_str; } /\*\*
\* Assemble le HTML complet pour le QA depuis les données de sortie. \*
\* \@param array\<string, mixed\> \$output Données partiellement
assemblées. \* \* \@return string HTML complet. \*/ private function
build_qa_html( array \$output ): string { \$parts = \[\]; if ( ! empty(
\$output\[\'H1\'\] ) ) { \$parts\[\] = \'\<h1\>\' . esc_html(
\$output\[\'H1\'\] ) . \'\</h1\>\'; } if ( ! empty(
\$output\[\'intro_html\'\] ) ) { \$parts\[\] =
\$output\[\'intro_html\'\]; } foreach ( \$output\[\'sections\'\] ?? \[\]
as \$section ) { if ( ! empty( \$section\[\'h2\'\] ) ) { \$parts\[\] =
\'\<h2\>\' . esc_html( \$section\[\'h2\'\] ) . \'\</h2\>\'; } if ( !
empty( \$section\[\'html\'\] ) ) { \$parts\[\] = \$section\[\'html\'\];
} } if ( ! empty( \$output\[\'cta_block_html\'\] ) ) { \$parts\[\] =
\$output\[\'cta_block_html\'\]; } return implode( \"\\n\", array_filter(
\$parts ) ); } /\*\* \* Crée un GenerationResult d\'échec et logue
l\'interruption du pipeline. \* \* \@param array\<string, mixed\>
\$partial_output Données partiellement assemblées. \* \@param string
\$reason Raison de l\'abandon. \* \* \@return GenerationResult \*/
private function abort( array \$partial_output, string \$reason ):
GenerationResult { \$this-\>logger-\>info( \'engine\', \'Pipeline
interrompu : \' . \$reason ); \$partial_output\[\'stats\'\] =
\$this-\>logger-\>get_stats(); \$partial_output\[\'warnings\'\] =
\$this-\>logger-\>get_all_warnings(); return
GenerationResult::from_array( \$partial_output ); } } \`\`\` \-\-- \##
Fichier 6 : \`includes/Admin/Ajax/AjaxGeneration.php\` --- Handler AJAX
\`\`\`php \<?php /\*\* \* Handler AJAX : Génération de contenu SEO
(moteur pur). \* \* Expose deux actions : \* - techrappy_run_pipeline :
lance le pipeline complet, retourne GenerationResult \* -
techrappy_run_step : lance une étape unique (pour reprise manuelle) \*
\* \@package TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 );
namespace TechrappySEO\\Admin\\Ajax; use
TechrappySEO\\Generation\\GenerationEngine; use
TechrappySEO\\Generation\\GenerationRequest; if ( ! defined( \'ABSPATH\'
) ) { exit; } /\*\* \* Class AjaxGeneration \*/ class AjaxGeneration {
// ───────────────────────────────────────── // Pipeline complet //
───────────────────────────────────────── /\*\* \* Lance le pipeline de
génération SEO complet. \* \* POST params : \* - nonce :
\'techrappy_seo_generation\' \* - type_contenu : \'page_seo\' \|
\'article_seo\' \* - mot_cle : string \* - profession : string
(optionnel) \* - city : string (optionnel) \* - pages_site_liste : JSON
string \[{title, url}, \...\] (optionnel) \* - contraintes : JSON string
{ton, cta_url, \...} (optionnel) \* \* \@return void \*/ public function
handle_run_pipeline(): void { \$this-\>verify_request(
\'techrappy_seo_generation\' ); // Construire la requête depuis les
données POST. try { \$request = GenerationRequest::from_array( \[
\'type_contenu\' =\> sanitize_key( wp_unslash(
\$\_POST\[\'type_contenu\'\] ?? \'page_seo\' ) ), \'mot_cle\' =\>
sanitize_text_field( wp_unslash( \$\_POST\[\'mot_cle\'\] ?? \'\' ) ),
\'profession\' =\> sanitize_text_field( wp_unslash(
\$\_POST\[\'profession\'\] ?? \'\' ) ), \'city\' =\>
sanitize_text_field( wp_unslash( \$\_POST\[\'city\'\] ?? \'\' ) ),
\'pages_site_liste\' =\> sanitize_textarea_field( wp_unslash(
\$\_POST\[\'pages_site_liste\'\] ?? \'\[\]\' ) ), \'contraintes\' =\>
sanitize_textarea_field( wp_unslash( \$\_POST\[\'contraintes\'\] ??
\'{}\' ) ), \] ); } catch ( \\InvalidArgumentException \$e ) {
wp_send_json_error( \[ \'message\' =\> \$e-\>getMessage(), \'code\' =\>
\'invalid_request\', \], 400 ); } // Vérifier la clé API avant de
lancer. if ( empty(
\\TechrappySEO\\Settings\\SettingsRepository::get_api_key() ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'Clé API OpenAI non
configurée.\', \'techrappy-seo\' ), \'code\' =\> \'missing_api_key\',
\], 400 ); } // Estimer le coût avant de lancer (garde-fou).
\$cost_check = \$this-\>check_cost_threshold( \$request ); if (
\$cost_check\[\'exceeds\'\] && ! \$this-\>user_confirmed_cost() ) {
wp_send_json_error( \[ \'message\' =\> sprintf( \_\_( \'Coût estimé
(%.2f\$) supérieur au seuil configuré (%.2f\$). Confirmez pour
continuer.\', \'techrappy-seo\' ), \$cost_check\[\'estimated\'\],
\$cost_check\[\'threshold\'\] ), \'code\' =\>
\'cost_threshold_exceeded\', \'estimated_cost\' =\>
\$cost_check\[\'estimated\'\], \'threshold\' =\>
\$cost_check\[\'threshold\'\], \'requires_confirm\' =\> true, \], 402 );
} // Lancer le moteur. \$engine = new GenerationEngine(); \$result =
\$engine-\>run( \$request ); // Réponse JSON complète.
wp_send_json_success( \[ \'result\' =\> \$result-\>to_array(),
\'is_valid\' =\> \$result-\>is_valid(), \'warnings\' =\>
\$result-\>get_warnings(), \'stats\' =\> \$result-\>get_stats(),
\'qa_score\' =\> \$result-\>get_qa_score(), \'json_output\' =\>
\$result-\>to_json(), \] ); } //
───────────────────────────────────────── // Étape unique (reprise
manuelle) // ───────────────────────────────────────── /\*\* \* Lance
une étape unique du pipeline (pour reprise sur erreur ou test). \* \*
POST params : \* - nonce : \'techrappy_seo_generation\' \* - step_key :
clé de l\'étape (ex: \'intent\', \'meta\') \* - mot_cle : string \* -
profession : string (optionnel) \* - context : JSON string --- données
des étapes précédentes (optionnel) \* \* \@return void \*/ public
function handle_run_step(): void { \$this-\>verify_request(
\'techrappy_seo_generation\' ); \$step_key = sanitize_key( wp_unslash(
\$\_POST\[\'step_key\'\] ?? \'\' ) ); \$mot_cle = sanitize_text_field(
wp_unslash( \$\_POST\[\'mot_cle\'\] ?? \'\' ) ); \$profession =
sanitize_text_field( wp_unslash( \$\_POST\[\'profession\'\] ??
\'thérapeute\' ) ); \$context_raw= sanitize_textarea_field( wp_unslash(
\$\_POST\[\'context\'\] ?? \'{}\' ) ); if ( empty( \$step_key ) \|\|
empty( \$mot_cle ) ) { wp_send_json_error( \[ \'message\' =\> \_\_(
\'step_key et mot_cle sont obligatoires.\', \'techrappy-seo\' ), \], 400
); } // Décoder le contexte des étapes précédentes. \$context = \[\]; if
( ! empty( \$context_raw ) ) { \$decoded = json_decode( \$context_raw,
true ); if ( JSON_ERROR_NONE === json_last_error() && is_array(
\$decoded ) ) { \$context = \$decoded; } } // Vérifier la clé API. if (
empty( \\TechrappySEO\\Settings\\SettingsRepository::get_api_key() ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'Clé API OpenAI non
configurée.\', \'techrappy-seo\' ), \], 400 ); } // Utiliser le
PromptManager + AIClient directement pour l\'étape unique.
\$prompt_manager = new \\TechrappySEO\\AI\\PromptManager(); \$validator
= new \\TechrappySEO\\Generation\\ContentValidator(); \$logger = new
\\TechrappySEO\\Generation\\GenerationLogger( \'step\_\' .
\\TechrappySEO\\Utils\\UuidGenerator::generate() ); // Préparer les
variables de base. \$variables = array_merge( \[ \'mot_cle\' =\>
\$mot_cle, \'profession\' =\> \$profession, \], \$context ); try {
\$prompt_cfg = \$prompt_manager-\>get_rendered( \$step_key, \$variables
); } catch ( \\RuntimeException \$e ) { wp_send_json_error( \[
\'message\' =\> \'Prompt introuvable : \' . \$e-\>getMessage(), \], 400
); } \$start_time = microtime( true ); \$client = new
\\TechrappySEO\\AI\\AIClient(); \$response = \$client-\>generate(
prompt: \$prompt_cfg\[\'prompt\'\], system_prompt:
\$prompt_cfg\[\'system\'\], response_format: \$prompt_cfg\[\'format\'\]
); \$duration_ms = (int) round( ( microtime( true ) - \$start_time ) \*
1000 ); if ( \$response-\>is_error() ) { wp_send_json_error( \[
\'message\' =\> \$response-\>get_error_message(), \'error_code\' =\>
\$response-\>get_error_code(), \'duration_ms\' =\> \$duration_ms, \],
500 ); } // Valider le JSON si nécessaire. \$json_valid = null;
\$json_error = \'\'; \$parsed_data = \$response-\>get_parsed(); if (
\'json_object\' === \$prompt_cfg\[\'format\'\] ) { \$validation =
\$validator-\>parse_json( \$response-\>get_content() ); \$json_valid =
\$validation\[\'ok\'\]; \$json_error = \$validation\[\'error\'\]; }
wp_send_json_success( \[ \'step_key\' =\> \$step_key, \'content\' =\>
\$response-\>get_content(), \'parsed\' =\> \$parsed_data, \'json_valid\'
=\> \$json_valid, \'json_error\' =\> \$json_error, \'input_tokens\' =\>
\$response-\>get_input_tokens(), \'output_tokens\' =\>
\$response-\>get_output_tokens(), \'duration_ms\' =\> \$duration_ms, \]
); } // ───────────────────────────────────────── // Helpers //
───────────────────────────────────────── /\*\* \* Estime le coût de la
génération et vérifie le seuil configuré. \* \* \@param
GenerationRequest \$request Requête de génération. \* \* \@return
array{exceeds: bool, estimated: float, threshold: float} \*/ private
function check_cost_threshold( GenerationRequest \$request ): array {
\$model = \\TechrappySEO\\Settings\\SettingsRepository::get(
\'openai_model\', \'gpt-4o\' ); \$threshold = (float)
\\TechrappySEO\\Settings\\SettingsRepository::get(
\'cost_alert_threshold\', 1.00 ); // Estimation conservatrice : \~9
appels × \~2000 tokens moyen. \$nb_blocs = 5; \$estimated_calls = 9 +
\$nb_blocs; \$estimated_input = \$estimated_calls \* 800;
\$estimated_output = \$estimated_calls \* 1200; \$estimated_cost =
\\TechrappySEO\\Utils\\CostEstimator::estimate( \$model,
\$estimated_input, \$estimated_output ); return \[ \'exceeds\' =\>
\$estimated_cost \>= \$threshold, \'estimated\' =\> \$estimated_cost,
\'threshold\' =\> \$threshold, \]; } /\*\* \* Vérifie si l\'utilisateur
a explicitement confirmé le dépassement de coût. \* \* \@return bool \*/
private function user_confirmed_cost(): bool { return filter_var(
\$\_POST\[\'confirmed_cost\'\] ?? false, FILTER_VALIDATE_BOOLEAN ); }
/\*\* \* Vérifie le nonce et la capacité de l\'utilisateur. \* \*
\@param string \$nonce_action Action du nonce. \* \* \@return void \*/
private function verify_request( string \$nonce_action ): void {
check_ajax_referer( \$nonce_action, \'nonce\' ); if ( !
current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[
\'message\' =\> \_\_( \'Accès non autorisé.\', \'techrappy-seo\' ), \],
403 ); } } } \`\`\` \-\-- \## Fichier 7 :
\`includes/Generation/GenerationService.php\` --- Façade publique
\`\`\`php \<?php /\*\* \* Façade publique du moteur de génération SEO.
\* \* Point d\'entrée simplifié pour les modules aval (WordPress, bulk,
Divi). \* Évite de manipuler GenerationEngine et GenerationRequest
directement. \* \* Usage depuis un module aval : \* \$service = new
GenerationService(); \* \$result = \$service-\>generate( \'ostéopathe
Beauzelle\', \'page_seo\' ); \* if ( \$result-\>is_valid() ) { \* \$slug
= \$result-\>get_slug(); \* \$h1 = \$result-\>get_h1(); \* \... \* } \*
\* \@package TechrappySEO\\Generation \*/ declare( strict_types=1 );
namespace TechrappySEO\\Generation; if ( ! defined( \'ABSPATH\' ) ) {
exit; } /\*\* \* Class GenerationService \*/ class GenerationService {
/\*\* \* Instance du moteur de génération. \* \* \@var GenerationEngine
\*/ private GenerationEngine \$engine; /\*\* \* Constructeur. \*/ public
function \_\_construct() { \$this-\>engine = new GenerationEngine(); }
/\*\* \* Lance une génération SEO complète. \* \* \@param string
\$mot_cle Mot-clé principal. \* \@param string \$type \'page_seo\' \|
\'article_seo\'. \* \@param array\<string, mixed\> \$options Options
optionnelles : \* - profession (string) \* - city (string) \* -
pages_site_liste (array) \* - contraintes (array) \* \* \@return
GenerationResult \* \* \@throws \\InvalidArgumentException Si les
paramètres sont invalides. \*/ public function generate( string
\$mot_cle, string \$type = GenerationRequest::TYPE_PAGE_SEO, array
\$options = \[\] ): GenerationResult { \$request =
GenerationRequest::from_array( array_merge( \[ \'mot_cle\' =\>
\$mot_cle, \'type_contenu\' =\> \$type, \], \$options ) ); return
\$this-\>engine-\>run( \$request ); } /\*\* \* Vérifie que le moteur est
prêt à être utilisé \* (clé API configurée, prompts présents). \* \*
\@return array{ready: bool, errors: string\[\]} \*/ public function
check_readiness(): array { \$errors = \[\]; // Vérifier la clé API. if (
empty( \\TechrappySEO\\Settings\\SettingsRepository::get_api_key() ) ) {
\$errors\[\] = \_\_( \'Clé API OpenAI non configurée.\',
\'techrappy-seo\' ); } // Vérifier que les prompts essentiels existent.
\$required_prompts = \[ \'intent\', \'plan\', \'blocks_list\',
\'block_write\', \'meta\' \]; \$prompt_manager = new
\\TechrappySEO\\AI\\PromptManager(); foreach ( \$required_prompts as
\$key ) { \$template = \$prompt_manager-\>get_raw_template( \$key ); if
( empty( \$template ) ) { \$errors\[\] = sprintf( \_\_( \'Prompt
manquant : \"%s\".\', \'techrappy-seo\' ), \$key ); } } return \[
\'ready\' =\> empty( \$errors ), \'errors\' =\> \$errors, \]; } } \`\`\`
\-\-- \## Fichier 8 : Enregistrement dans \`Plugin.php\` (delta)
\`\`\`php // Dans includes/Core/Plugin.php // Remplacer le bloc
AjaxGeneration existant dans define_ajax_hooks() : \$ajax_generation =
new \\TechrappySEO\\Admin\\Ajax\\AjaxGeneration();
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_run_pipeline\',
\$ajax_generation, \'handle_run_pipeline\' );
\$this-\>loader-\>add_action( \'wp_ajax_techrappy_run_step\',
\$ajax_generation, \'handle_run_step\' ); \`\`\` \-\-- \## Récapitulatif
V4 --- Moteur de génération SEO \`\`\` includes/Generation/ ├──
GenerationRequest.php ✅ DTO entrée --- validation + sanitization │
from_array(), accesseurs, to_array() │ ├── GenerationResult.php ✅ DTO
sortie --- contrat interface modules aval │ get_h1(), get_slug(),
get_sections(), │ get_faq(), get_full_html(), to_json() │ ├──
ContentValidator.php ✅ Validation JSON + schémas par étape │
parse_json(), build_correction_prompt(), │ validate_step_response(),
sanitize_final_output() │ + détection patterns interdits (promesses
médicales) │ ├── GenerationLogger.php ✅ Logger dédié pipeline │
step_start/success/error/warning() │ stats accumulées, masquage clé API
│ ├── GenerationEngine.php ✅ Orchestrateur 9 étapes │ run() →
GenerationResult │ retry JSON automatique sur réponse invalide │ étapes
non-bloquantes (intro, faq, cta, qa) │ └── GenerationService.php ✅
Façade publique (modules aval : WP, bulk, Divi) generate(),
check_readiness() includes/Admin/Ajax/ └── AjaxGeneration.php ✅ 2
handlers AJAX sécurisés handle_run_pipeline() --- pipeline complet
handle_run_step() --- étape unique (reprise) garde-fou coût +
confirmation utilisateur \`\`\` \*\*Flux complet :\*\* \`\`\` POST
wp_ajax_techrappy_run_pipeline → AjaxGeneration::handle_run_pipeline() →
GenerationRequest::from_array() \[validation + sanitization\] →
GenerationService::check_readiness() \[clé API + prompts\] →
GenerationEngine::run( \$request ) ├── call_ai(\'intent\') → AIClient →
PromptManager → OpenAI ├── call_ai(\'plan\') → retry JSON si invalide
├── call_ai(\'blocks_list\') ├── call_ai(\'block_write\') × N blocs ├──
call_ai(\'internal_links\') \[si pages dispo\] ├── call_ai(\'meta\') ├──
call_ai(\'faq\') ├── call_ai(\'conclusion_cta\') └── call_ai(\'qa\')
\[non bloquant\] → ContentValidator::sanitize_final_output() →
GenerationResult::from_array() \[DTO immuable\] → wp_send_json_success(
result.to_array() ) \`\`\` \*\*Contrat \`GenerationResult\` pour les
modules aval :\*\* \| Méthode \| Retour \| Utilisé par \|
\|\-\-\-\-\-\-\-\--\|\-\-\-\-\-\-\--\|\-\-\-\-\-\-\-\-\-\-\-\--\| \|
\`get_slug()\` \| \`string\` \| WordPress (post_name) \| \| \`get_h1()\`
\| \`string\` \| WordPress (post_title) + Divi token \| \|
\`get_metatitle()\` \| \`string\` \| Yoast (\_yoast_wpseo_title) \| \|
\`get_metadescription()\` \| \`string\` \| Yoast
(\_yoast_wpseo_metadesc) \| \| \`get_intro_html()\` \| \`string\` \|
Divi token {{intro}} \| \| \`get_sections()\` \| \`array\` \| Divi
sections répétables \| \| \`get_faq()\` \| \`array\` \| Divi token
{{faq_block}} \| \| \`get_cta_block_html()\` \| \`string\` \| Divi token
{{cta_block}} \| \| \`get_full_html()\` \| \`string\` \| Preview + QA \|
\| \`to_json()\` \| \`string\` \| Stockage job + API externe \|
