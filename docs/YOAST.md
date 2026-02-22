\# V6 --- Intégration Yoast SEO \-\-- \## Fichier 1 :
\`includes/SEO/YoastIntegration.php\` --- Service principal \`\`\`php
\<?php /\*\* \* Intégration Yoast SEO. \* \* Responsabilité : détecter
Yoast SEO, écrire les métadonnées \* (meta title, meta description,
focus keyword) sur un post WordPress \* à partir des données du moteur
SEO. \* \* Conçu pour être non-bloquant : \* - Si Yoast n\'est pas
installé → log warning, aucune exception. \* - Si une clé meta échoue →
log warning, continue. \* \*
────────────────────────────────────────────────────────────── \* Clés
meta Yoast utilisées (documentées) : \* \* \_yoast_wpseo_title \* Meta
title affiché dans Google. \* Supporte les variables Yoast (%%title%%,
%%sep%%, etc.) \* mais on injecte une valeur brute générée par l\'IA. \*
Disponible : Yoast SEO Free ≥ 1.0 \* \* \_yoast_wpseo_metadesc \* Meta
description affichée dans les SERPs. \* Disponible : Yoast SEO Free ≥
1.0 \* \* \_yoast_wpseo_focuskw \* Mot-clé de focus (focus keyphrase en
Yoast ≥ 14.0). \* Disponible : Yoast SEO Free ≥ 1.0 \* Note : depuis
Yoast 14.0, le champ UI s\'appelle \"Focus keyphrase\" \* mais la clé
meta reste \_yoast_wpseo_focuskw pour compatibilité. \* \*
\_yoast_wpseo_metakeywords (DEPRECATED) \* Supprimé depuis Yoast SEO
6.3. Non utilisé ici. \* \* yoast_wpseo_primary\_{taxonomy} (Yoast
Premium) \* Catégorie principale --- non utilisé en V1. \*
────────────────────────────────────────────────────────────── \* \*
\@package TechrappySEO\\SEO \*/ declare( strict_types=1 ); namespace
TechrappySEO\\SEO; use TechrappySEO\\Utils\\Logger; if ( ! defined(
\'ABSPATH\' ) ) { exit; } /\*\* \* Class YoastIntegration \*/ class
YoastIntegration { // ───────────────────────────────────────── // Clés
meta Yoast (centralisées) // ─────────────────────────────────────────
/\*\* \* Clé meta : titre SEO Yoast. \* \* \@var string \*/ const
META_TITLE = \'\_yoast_wpseo_title\'; /\*\* \* Clé meta : meta
description Yoast. \* \* \@var string \*/ const META_DESC =
\'\_yoast_wpseo_metadesc\'; /\*\* \* Clé meta : mot-clé de focus Yoast.
\* Stable depuis Yoast SEO 1.0, utilisée même en v14+ (focus keyphrase).
\* \* \@var string \*/ const META_FOCUSKW = \'\_yoast_wpseo_focuskw\';
// ───────────────────────────────────────── // Propriétés //
───────────────────────────────────────── /\*\* \* Logger du job/requête
courant. \* \* \@var Logger \*/ private Logger \$logger; /\*\* \* Cache
du statut de détection Yoast (évite des appels répétés). \* \* \@var
bool\|null \*/ private static ?bool \$yoast_active_cache = null; //
───────────────────────────────────────── // Constructeur //
───────────────────────────────────────── /\*\* \* Constructeur. \* \*
\@param Logger\|null \$logger Logger externe (null = logger standalone).
\*/ public function \_\_construct( ?Logger \$logger = null ) {
\$this-\>logger = \$logger ?? new Logger( \'yoast\_\' . time() ); } //
───────────────────────────────────────── // Détection Yoast //
───────────────────────────────────────── /\*\* \* Vérifie si Yoast SEO
(Free ou Premium) est installé et actif. \* \* Méthode de détection
robuste (3 niveaux) : \* 1. Classe WPSEO_Options (Yoast Free ≥ 1.0) \*
2. Classe Yoast\\WP\\SEO\\Main (Yoast ≥ 17.0 namespace refactorisé) \*
3. Constante WPSEO_VERSION \* \* \@return bool True si Yoast est actif.
\*/ public static function is_active(): bool { // Utiliser le cache pour
éviter des vérifications répétées. if ( null !==
self::\$yoast_active_cache ) { return self::\$yoast_active_cache; }
self::\$yoast_active_cache = ( class_exists( \'WPSEO_Options\' ) \|\|
class_exists( \'Yoast\\WP\\SEO\\Main\' ) \|\| defined( \'WPSEO_VERSION\'
) ); return self::\$yoast_active_cache; } /\*\* \* Retourne la version
de Yoast SEO détectée. \* \* \@return string Version ou chaîne vide si
non détecté. \*/ public static function get_version(): string { if ( !
self::is_active() ) { return \'\'; } return defined( \'WPSEO_VERSION\' )
? (string) WPSEO_VERSION : \'unknown\'; } /\*\* \* Invalide le cache de
détection Yoast. \* Utile dans les tests unitaires. \* \* \@return void
\*/ public static function invalidate_detection_cache(): void {
self::\$yoast_active_cache = null; } //
───────────────────────────────────────── // Écriture des métadonnées //
───────────────────────────────────────── /\*\* \* Applique toutes les
métadonnées Yoast sur un post. \* \* Point d\'entrée principal ---
appelé par PostCreator après wp_insert_post(). \* \* \@param int
\$post_id ID du post créé. \* \@param YoastMetaData \$meta_data Données
à appliquer. \* \@param bool \$enabled Si false : aucune écriture
(désactivé par l\'UI). \* \* \@return YoastWriteResult Résultat détaillé
de l\'opération. \*/ public function apply_to_post( int \$post_id,
YoastMetaData \$meta_data, bool \$enabled = true ): YoastWriteResult {
// ── Vérification activation Yoast ────────────────── if ( !
self::is_active() ) { \$message = \_\_( \'Yoast SEO non détecté ---
métadonnées Yoast non appliquées.\', \'techrappy-seo\' );
\$this-\>logger-\>warning( \'yoast\', \$message ); return
YoastWriteResult::skipped( reason: \$message, post_id: \$post_id ); } //
── Option désactivée par l\'utilisateur ──────────── if ( ! \$enabled )
{ \$message = \_\_( \'Injection Yoast désactivée par
l\\\'utilisateur.\', \'techrappy-seo\' ); \$this-\>logger-\>info(
\'yoast\', \$message ); return YoastWriteResult::skipped( reason:
\$message, post_id: \$post_id ); } // ── Vérifier que le post existe
──────────────────── if ( ! \$this-\>post_exists( \$post_id ) ) {
\$message = sprintf( \_\_( \'Post ID %d introuvable --- injection Yoast
annulée.\', \'techrappy-seo\' ), \$post_id ); \$this-\>logger-\>error(
\'yoast\', \$message ); return YoastWriteResult::error( message:
\$message, post_id: \$post_id ); } \$this-\>logger-\>info( \'yoast\',
sprintf( \'Injection Yoast sur post #%d --- Yoast v%s\', \$post_id,
self::get_version() ) ); // ── Écrire les métadonnées
───────────────────────── \$written = \[\]; \$failures = \[\]; // Meta
title. if ( \$meta_data-\>has_title() ) { \$ok = \$this-\>write_meta(
\$post_id, self::META_TITLE, \$meta_data-\>get_title() ); if ( \$ok ) {
\$written\[\] = self::META_TITLE; } else { \$failures\[\] =
self::META_TITLE; } } // Meta description. if (
\$meta_data-\>has_description() ) { \$ok = \$this-\>write_meta(
\$post_id, self::META_DESC, \$meta_data-\>get_description() ); if ( \$ok
) { \$written\[\] = self::META_DESC; } else { \$failures\[\] =
self::META_DESC; } } // Focus keyword. if (
\$meta_data-\>has_focus_keyword() ) { \$ok = \$this-\>write_meta(
\$post_id, self::META_FOCUSKW, \$meta_data-\>get_focus_keyword() ); if (
\$ok ) { \$written\[\] = self::META_FOCUSKW; } else { \$failures\[\] =
self::META_FOCUSKW; } } // ── Log du résultat
──────────────────────────────── \$this-\>logger-\>info( \'yoast\',
sprintf( \'Injection terminée --- %d champ(s) écrits : \[%s\]%s\',
count( \$written ), implode( \', \', \$written ), ! empty( \$failures )
? \' --- Échecs : \[\' . implode( \', \', \$failures ) . \'\]\' : \'\' )
); return YoastWriteResult::success( post_id: \$post_id, written:
\$written, failures: \$failures ); } //
───────────────────────────────────────── // Écriture d\'une méta
individuelle // ───────────────────────────────────────── /\*\* \* Écrit
une métadonnée Yoast sur un post via update_post_meta(). \* \* Note : on
utilise update_post_meta() et non les méthodes internes \* de Yoast
(WPSEO_Meta::set_value) pour éviter la dépendance à \* l\'API interne
non publique de Yoast qui peut changer entre versions. \* \* \@param int
\$post_id ID du post. \* \@param string \$key Clé meta Yoast (ex:
\_yoast_wpseo_title). \* \@param string \$value Valeur à écrire. \* \*
\@return bool True si l\'écriture a réussi. \*/ private function
write_meta( int \$post_id, string \$key, string \$value ): bool { if (
empty( \$value ) ) { \$this-\>logger-\>warning( \'yoast\', sprintf(
\'Valeur vide pour la clé \"%s\" --- non écrite.\', \$key ) ); return
false; } \$result = update_post_meta( \$post_id, \$key, \$value ); /\*
\* update_post_meta() retourne : \* - int : meta_id si la méta a été
CRÉÉE \* - true : si la méta a été MISE À JOUR (valeur différente) \* -
false : si la valeur est IDENTIQUE à l\'existante (pas d\'update
nécessaire) \* OU si une erreur DB s\'est produite. \* \* On considère
false comme succès si la valeur était déjà correcte. \* Pour détecter
une vraie erreur DB, on vérifie avec get_post_meta(). \*/ if ( false ===
\$result ) { // Vérifier si la valeur est déjà correctement enregistrée.
\$stored = get_post_meta( \$post_id, \$key, true ); if ( \$stored ===
\$value ) { // Valeur déjà en place --- pas d\'erreur.
\$this-\>logger-\>info( \'yoast\', sprintf( \'Méta \"%s\" : valeur déjà
à jour.\', \$key ) ); return true; } \$this-\>logger-\>error( \'yoast\',
sprintf( \'Échec update_post_meta() pour la clé \"%s\" sur post #%d.\',
\$key, \$post_id ) ); return false; } \$this-\>logger-\>info( \'yoast\',
sprintf( \'Méta \"%s\" écrite sur post #%d.\', \$key, \$post_id ) );
return true; } // ───────────────────────────────────────── // Lecture
(pour vérification) // ───────────────────────────────────────── /\*\*
\* Lit les métadonnées Yoast actuellement enregistrées sur un post. \*
Utile pour vérification post-création ou débogage. \* \* \@param int
\$post_id ID du post. \* \* \@return array{title: string, description:
string, focus_keyword: string} \*/ public function read_from_post( int
\$post_id ): array { return \[ \'title\' =\> (string) get_post_meta(
\$post_id, self::META_TITLE, true ), \'description\' =\> (string)
get_post_meta( \$post_id, self::META_DESC, true ), \'focus_keyword\' =\>
(string) get_post_meta( \$post_id, self::META_FOCUSKW, true ), \]; }
/\*\* \* Supprime toutes les métadonnées Yoast d\'un post. \* Utile pour
régénérer proprement les metas. \* \* \@param int \$post_id ID du post.
\* \* \@return void \*/ public function clear_from_post( int \$post_id
): void { delete_post_meta( \$post_id, self::META_TITLE );
delete_post_meta( \$post_id, self::META_DESC ); delete_post_meta(
\$post_id, self::META_FOCUSKW ); \$this-\>logger-\>info( \'yoast\',
sprintf( \'Métadonnées Yoast supprimées sur post #%d.\', \$post_id ) );
} // ───────────────────────────────────────── // Helper //
───────────────────────────────────────── /\*\* \* Vérifie qu\'un post
WordPress existe. \* \* \@param int \$post_id ID du post. \* \* \@return
bool \*/ private function post_exists( int \$post_id ): bool { if (
\$post_id \<= 0 ) { return false; } \$post = get_post( \$post_id );
return ( \$post instanceof \\WP_Post && \'trash\' !==
\$post-\>post_status ); } } \`\`\` \-\-- \## Fichier 2 :
\`includes/SEO/YoastMetaData.php\` --- DTO données Yoast \`\`\`php
\<?php /\*\* \* Données Yoast à injecter sur un post (DTO). \* \* Value
Object immuable --- construit depuis GenerationResult \* ou depuis un
tableau de données brutes. \* \* \@package TechrappySEO\\SEO \*/
declare( strict_types=1 ); namespace TechrappySEO\\SEO; use
TechrappySEO\\Generation\\GenerationResult; if ( ! defined( \'ABSPATH\'
) ) { exit; } /\*\* \* Class YoastMetaData \*/ final class YoastMetaData
{ /\*\* \@var string Meta title (55-65 chars recommandé) \*/ private
string \$title = \'\'; /\*\* \@var string Meta description (140-160
chars recommandé) \*/ private string \$description = \'\'; /\*\* \*
Focus keyword / keyphrase. \* Correspond au champ \"Focus keyphrase\"
dans l\'UI Yoast. \* \* \@var string \*/ private string \$focus_keyword
= \'\'; // ───────────────────────────────────────── // Constructeur
privé // ───────────────────────────────────────── private function
\_\_construct() {} // ───────────────────────────────────────── //
Factory methods // ───────────────────────────────────────── /\*\* \*
Crée un YoastMetaData depuis un GenerationResult. \* C\'est la factory
principale utilisée dans le flux de création. \* \* \@param
GenerationResult \$result Résultat du moteur SEO. \* \@param string
\$mot_cle Mot-clé principal (pour focus keyword). \* \* \@return self
\*/ public static function from_generation_result( GenerationResult
\$result, string \$mot_cle = \'\' ): self { \$meta = new self();
\$meta-\>title = self::sanitize_meta_value( \$result-\>get_metatitle()
); \$meta-\>description = self::sanitize_meta_value(
\$result-\>get_metadescription() ); \$meta-\>focus_keyword =
self::sanitize_meta_value( \$mot_cle ); return \$meta; } /\*\* \* Crée
un YoastMetaData depuis un tableau de données brutes. \* \* \@param
array\<string, string\> \$data Données brutes. \* \* \@return self \*/
public static function from_array( array \$data ): self { \$meta = new
self(); \$meta-\>title = self::sanitize_meta_value( \$data\[\'title\'\]
?? \'\' ); \$meta-\>description = self::sanitize_meta_value(
\$data\[\'description\'\] ?? \'\' ); \$meta-\>focus_keyword =
self::sanitize_meta_value( \$data\[\'focus_keyword\'\] ?? \'\' ); return
\$meta; } /\*\* \* Crée un YoastMetaData vide (utile pour tests). \* \*
\@return self \*/ public static function empty(): self { return new
self(); } // ───────────────────────────────────────── // Accesseurs //
───────────────────────────────────────── public function get_title():
string { return \$this-\>title; } public function get_description():
string { return \$this-\>description; } public function
get_focus_keyword(): string { return \$this-\>focus_keyword; } public
function has_title(): bool { return \'\' !== \$this-\>title; } public
function has_description(): bool { return \'\' !== \$this-\>description;
} public function has_focus_keyword(): bool { return \'\' !==
\$this-\>focus_keyword; } /\*\* \* Vérifie si au moins une valeur est
présente. \* \* \@return bool \*/ public function has_any(): bool {
return \$this-\>has_title() \|\| \$this-\>has_description() \|\|
\$this-\>has_focus_keyword(); } /\*\* \* Retourne un rapport de
validation des longueurs SEO recommandées. \* \* \@return array{ \*
title_length: int, \* title_ok: bool, \* desc_length: int, \* desc_ok:
bool, \* warnings: string\[\] \* } \*/ public function
validate_lengths(): array { \$title_len = mb_strlen( \$this-\>title,
\'UTF-8\' ); \$desc_len = mb_strlen( \$this-\>description, \'UTF-8\' );
\$warnings = \[\]; \$title_ok = ( \$title_len \>= 55 && \$title_len \<=
65 ); \$desc_ok = ( \$desc_len \>= 140 && \$desc_len \<= 160 ); if (
\$this-\>has_title() && ! \$title_ok ) { \$warnings\[\] = sprintf(
\'Meta title : %d caractères (recommandé 55-65).\', \$title_len ); } if
( \$this-\>has_description() && ! \$desc_ok ) { \$warnings\[\] =
sprintf( \'Meta description : %d caractères (recommandé 140-160).\',
\$desc_len ); } return \[ \'title_length\' =\> \$title_len, \'title_ok\'
=\> \$title_ok, \'desc_length\' =\> \$desc_len, \'desc_ok\' =\>
\$desc_ok, \'warnings\' =\> \$warnings, \]; } /\*\* \* Sérialise en
tableau (pour logs et débogage). \* \* \@return array\<string, string\>
\*/ public function to_array(): array { return \[ \'title\' =\>
\$this-\>title, \'description\' =\> \$this-\>description,
\'focus_keyword\' =\> \$this-\>focus_keyword, \]; } //
───────────────────────────────────────── // Helper sanitization //
───────────────────────────────────────── /\*\* \* Sanitize une valeur
de meta Yoast. \* Supprime les balises HTML, normalise les espaces. \*
\* \@param string \$value Valeur brute. \* \* \@return string Valeur
sanitizée. \*/ private static function sanitize_meta_value( string
\$value ): string { // Supprimer les balises HTML résiduelles. \$value =
wp_strip_all_tags( \$value ); // Décoder les entités HTML. \$value =
html_entity_decode( \$value, ENT_QUOTES \| ENT_HTML5, \'UTF-8\' ); //
Normaliser les espaces multiples. \$value = preg_replace( \'/\\s+/\', \'
\', \$value ) ?? \$value; return trim( \$value ); } } \`\`\` \-\-- \##
Fichier 3 : \`includes/SEO/YoastWriteResult.php\` --- DTO résultat
injection \`\`\`php \<?php /\*\* \* Résultat de l\'injection Yoast sur
un post (DTO). \* \* \@package TechrappySEO\\SEO \*/ declare(
strict_types=1 ); namespace TechrappySEO\\SEO; if ( ! defined(
\'ABSPATH\' ) ) { exit; } /\*\* \* Class YoastWriteResult \* \* Value
Object --- retourné par YoastIntegration::apply_to_post(). \*/ final
class YoastWriteResult { // États possibles. const STATE_SUCCESS =
\'success\'; const STATE_SKIPPED = \'skipped\'; const STATE_ERROR =
\'error\'; /\*\* \@var string \*/ private string \$state; /\*\* \@var
int \*/ private int \$post_id; /\*\* \@var string\[\] Clés meta écrites
avec succès \*/ private array \$written; /\*\* \@var string\[\] Clés
meta en échec \*/ private array \$failures; /\*\* \@var string Message
(skip ou erreur) \*/ private string \$message; //
───────────────────────────────────────── // Factory methods //
───────────────────────────────────────── private function
\_\_construct() {} /\*\* \* Création réussie (au moins partiellement).
\* \* \@param int \$post_id ID du post. \* \@param string\[\] \$written
Clés écrites. \* \@param string\[\] \$failures Clés en échec. \* \*
\@return self \*/ public static function success( int \$post_id, array
\$written, array \$failures = \[\] ): self { \$r = new self();
\$r-\>state = self::STATE_SUCCESS; \$r-\>post_id = \$post_id;
\$r-\>written = \$written; \$r-\>failures = \$failures; \$r-\>message =
\'\'; return \$r; } /\*\* \* Injection ignorée (Yoast inactif ou option
désactivée). \* \* \@param string \$reason Raison du skip. \* \@param
int \$post_id ID du post. \* \* \@return self \*/ public static function
skipped( string \$reason, int \$post_id = 0 ): self { \$r = new self();
\$r-\>state = self::STATE_SKIPPED; \$r-\>post_id = \$post_id;
\$r-\>written = \[\]; \$r-\>failures = \[\]; \$r-\>message = \$reason;
return \$r; } /\*\* \* Erreur bloquante (post inexistant...). \* \*
\@param string \$message Message d\'erreur. \* \@param int \$post_id ID
du post. \* \* \@return self \*/ public static function error( string
\$message, int \$post_id = 0 ): self { \$r = new self(); \$r-\>state =
self::STATE_ERROR; \$r-\>post_id = \$post_id; \$r-\>written = \[\];
\$r-\>failures = \[\]; \$r-\>message = \$message; return \$r; } //
───────────────────────────────────────── // Accesseurs //
───────────────────────────────────────── public function is_success():
bool { return self::STATE_SUCCESS === \$this-\>state; } public function
is_skipped(): bool { return self::STATE_SKIPPED === \$this-\>state; }
public function is_error(): bool { return self::STATE_ERROR ===
\$this-\>state; } public function get_post_id(): int { return
\$this-\>post_id; } public function get_written(): array { return
\$this-\>written; } public function get_failures(): array { return
\$this-\>failures; } public function get_message(): string { return
\$this-\>message; } public function get_state(): string { return
\$this-\>state; } /\*\* \* Vérifie si toutes les clés demandées ont été
écrites. \* \* \@return bool \*/ public function is_fully_written():
bool { return \$this-\>is_success() && empty( \$this-\>failures ); }
/\*\* \* Sérialise en tableau (pour réponse AJAX et logs). \* \*
\@return array\<string, mixed\> \*/ public function to_array(): array {
return \[ \'state\' =\> \$this-\>state, \'post_id\' =\>
\$this-\>post_id, \'written\' =\> \$this-\>written, \'failures\' =\>
\$this-\>failures, \'message\' =\> \$this-\>message, \]; } } \`\`\`
\-\-- \## Fichier 4 : \`includes/WordPress/PostCreator.php\` --- Mise à
jour (delta Yoast) \`\`\`php \<?php /\*\* \* Delta V6 : ajout de
l\'injection Yoast dans PostCreator. \* \* Modification minimale :
ajouter le paramètre \$yoast_enabled \* et appeler
YoastIntegration::apply_to_post() après wp_insert_post(). \* \* Les
lignes modifiées sont signalées par le commentaire // \[V6\]. \* \*
\@package TechrappySEO\\WordPress \*/ declare( strict_types=1 );
namespace TechrappySEO\\WordPress; use
TechrappySEO\\Generation\\GenerationResult; use
TechrappySEO\\SEO\\YoastIntegration; // \[V6\] use
TechrappySEO\\SEO\\YoastMetaData; // \[V6\] use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class PostCreator (V6 --- avec injection Yoast) \*/ class
PostCreator { /\*\* \@var ContentAssembler \*/ private ContentAssembler
\$assembler; /\*\* \@var SlugManager \*/ private SlugManager
\$slug_manager; /\*\* \@var Logger \*/ private Logger \$logger; /\*\*
\@var YoastIntegration \[V6\] \*/ private YoastIntegration \$yoast; //
\[V6\] public function \_\_construct( ?Logger \$logger = null ) {
\$this-\>assembler = new ContentAssembler(); \$this-\>slug_manager = new
SlugManager(); \$this-\>logger = \$logger ?? new Logger(
\'post_creator\_\' . time() ); \$this-\>yoast = new YoastIntegration(
\$this-\>logger ); // \[V6\] } //
───────────────────────────────────────── // Point d\'entrée principal
(mis à jour V6) // ───────────────────────────────────────── /\*\* \*
Crée un post WordPress depuis un GenerationResult. \* \* \@param
GenerationResult \$result Résultat du moteur SEO. \* \@param
PostCreationSettings \$settings Paramètres WordPress. \* \@param string
\$mot_cle Mot-clé principal (pour focus keyword Yoast). \[V6\] \*
\@param bool \$yoast_enabled Activer l\'injection Yoast. \[V6\] \* \*
\@return PostCreationResult \*/ public function create_post_from_result(
GenerationResult \$result, PostCreationSettings \$settings, string
\$mot_cle = \'\', // \[V6\] bool \$yoast_enabled = true // \[V6\] ):
PostCreationResult { \$this-\>logger-\>info( \'post_creator\', sprintf(
\'Création post --- type: %s --- status: %s --- H1: \"%s\" --- Yoast:
%s\', // \[V6\] \$settings-\>get_post_type(),
\$settings-\>get_post_status(), \$result-\>get_h1(), \$yoast_enabled ?
\'activé\' : \'désactivé\' // \[V6\] ) ); // ── 1. Slug unique
───────────────────────────────── \$slug_data =
\$this-\>slug_manager-\>generate_unique( \$result-\>get_slug(),
\$settings-\>get_post_type() ); if ( \$slug_data\[\'was_modified\'\] ) {
\$this-\>logger-\>warning( \'post_creator\', sprintf( \'Slug \"%s\" déjà
utilisé --- remplacé par \"%s\".\', \$slug_data\[\'original\'\],
\$slug_data\[\'slug\'\] ) ); } \$final_slug = \$slug_data\[\'slug\'\];
// ── 2. Assembler le post_content ─────────────────── \$post_content =
\$this-\>assembler-\>assemble( \$result, \$settings ); // ── 3. Post
title ────────────────────────────────── \$post_title = ! empty(
\$result-\>get_h1() ) ? \$result-\>get_h1() : \$final_slug; // ── 4.
Arguments wp_insert_post ──────────────────── \$post_args = \[
\'post_title\' =\> sanitize_text_field( \$post_title ), \'post_content\'
=\> \$post_content, \'post_name\' =\> \$final_slug, \'post_status\' =\>
\$settings-\>get_post_status(), \'post_type\' =\>
\$settings-\>get_post_type(), \'post_author\' =\>
\$settings-\>get_post_author(), \]; if ( \$settings-\>is_page() &&
\$settings-\>get_post_parent() \> 0 ) { \$parent = get_post(
\$settings-\>get_post_parent() ); if ( \$parent && \'page\' ===
\$parent-\>post_type ) { \$post_args\[\'post_parent\'\] =
\$settings-\>get_post_parent(); } else { \$this-\>logger-\>warning(
\'post_creator\', \'post_parent invalide --- ignoré.\' ); } }
\$post_args = apply_filters( \'techrappy_seo_post_content_before\',
\$post_args, \$result, \$settings ); // ── 5. Insérer le post
───────────────────────────── \$this-\>logger-\>info( \'post_creator\',
\'Appel wp_insert_post()...\' ); \$post_id = wp_insert_post(
\$post_args, true ); if ( is_wp_error( \$post_id ) ) { \$error_msg =
\$post_id-\>get_error_message(); \$this-\>logger-\>error(
\'post_creator\', \'wp_insert_post() échec : \' . \$error_msg ); return
PostCreationResult::error( message: \$error_msg, logs:
\$this-\>logger-\>get_logs() ); } \$this-\>logger-\>info(
\'post_creator\', sprintf( \'Post créé --- ID: %d\', \$post_id ) ); //
── 6. Taxonomies ────────────────────────────────── if (
\$settings-\>is_post() ) { \$this-\>set_categories( \$post_id,
\$settings-\>get_category_ids() ); \$this-\>set_tags( \$post_id,
\$settings-\>get_tag_names() ); } // ── 7. Métadonnées plugin
────────────────────────── \$this-\>store_plugin_meta( \$post_id,
\$result ); // ── 8. Injection Yoast \[V6\] ────────────────────────
\$yoast_result = \$this-\>apply_yoast_meta( post_id: \$post_id, result:
\$result, mot_cle: \$mot_cle, enabled: \$yoast_enabled ); // Stocker le
résultat Yoast dans les metas du plugin. update_post_meta( \$post_id,
\'\_techrappy_seo_yoast_state\', \$yoast_result-\>get_state() ); // ──
9. Filtre post-insertion ─────────────────────── do_action(
\'techrappy_seo_post_content_after\', \$post_id, \$result, \$settings );
// ── 10. Résultat final ───────────────────────────── \$permalink =
get_permalink( \$post_id ); \$this-\>logger-\>info( \'post_creator\',
sprintf( \'Post créé avec succès --- ID: %d --- URL: %s\', \$post_id,
\$permalink ?: \'(non disponible)\' ) ); // Compiler les warnings
(slug + Yoast). \$warnings = \[\]; if ( \$slug_data\[\'was_modified\'\]
) { \$warnings\[\] = sprintf( \'Slug modifié : \"%s\" → \"%s\"\',
\$slug_data\[\'original\'\], \$final_slug ); } // \[V6\] Ajouter le
message Yoast si skip ou erreur. if ( \$yoast_result-\>is_skipped() \|\|
\$yoast_result-\>is_error() ) { \$warnings\[\] =
\$yoast_result-\>get_message(); } if ( ! empty(
\$yoast_result-\>get_failures() ) ) { \$warnings\[\] = sprintf( \'Yoast
: champs non écrits : %s\', implode( \', \',
\$yoast_result-\>get_failures() ) ); } return
PostCreationResult::success( post_id: \$post_id, permalink: \$permalink
?: \'\', slug: \$final_slug, post_type: \$settings-\>get_post_type(),
status: \$settings-\>get_post_status(), warnings: \$warnings, logs:
\$this-\>logger-\>get_logs(), yoast_result: \$yoast_result-\>to_array()
// \[V6\] ); } // ───────────────────────────────────────── // Méthode
statique alias (mise à jour V6) //
───────────────────────────────────────── /\*\* \* Interface statique
demandée par le cahier des charges. \* \* \@param array\<string, mixed\>
\$seo_json Tableau issu de GenerationResult::to_array(). \* \@param
array\<string, mixed\> \$settings Paramètres PostCreationSettings. \* \*
\@return PostCreationResult \*/ public static function
create_post_from_seo_json( array \$seo_json, array \$settings ):
PostCreationResult { try { \$result = GenerationResult::from_array(
\$seo_json ); \$creation_settings = PostCreationSettings::from_array(
\$settings ); } catch ( \\InvalidArgumentException \$e ) { return
PostCreationResult::error( message: \$e-\>getMessage(), logs: \[\] ); }
\$mot_cle = (string) ( \$settings\[\'mot_cle\'\] ?? \'\' ); // \[V6\]
\$yoast_enabled = (bool) ( \$settings\[\'yoast_enabled\'\] ?? true ); //
\[V6\] \$creator = new self(); return
\$creator-\>create_post_from_result( \$result, \$creation_settings,
\$mot_cle, // \[V6\] \$yoast_enabled // \[V6\] ); } //
───────────────────────────────────────── // Injection Yoast (méthode
dédiée) \[V6\] // ───────────────────────────────────────── /\*\* \*
Construit les données Yoast et les applique sur le post. \* \* \@param
int \$post_id ID du post. \* \@param GenerationResult \$result Résultat
du moteur SEO. \* \@param string \$mot_cle Mot-clé principal. \* \@param
bool \$enabled Injection activée. \* \* \@return
\\TechrappySEO\\SEO\\YoastWriteResult \*/ private function
apply_yoast_meta( int \$post_id, GenerationResult \$result, string
\$mot_cle, bool \$enabled ): \\TechrappySEO\\SEO\\YoastWriteResult {
\$meta_data = YoastMetaData::from_generation_result( \$result, \$mot_cle
); // Logger un résumé avant injection. if ( \$enabled &&
\$meta_data-\>has_any() ) { \$validation =
\$meta_data-\>validate_lengths(); foreach ( \$validation\[\'warnings\'\]
as \$w ) { \$this-\>logger-\>warning( \'yoast\', \$w ); } } return
\$this-\>yoast-\>apply_to_post( post_id: \$post_id, meta_data:
\$meta_data, enabled: \$enabled ); } //
───────────────────────────────────────── // Helpers inchangés (V5) //
───────────────────────────────────────── private function
set_categories( int \$post_id, array \$category_ids ): void { if (
empty( \$category_ids ) ) { return; } \$result = wp_set_post_categories(
\$post_id, \$category_ids, false ); if ( is_wp_error( \$result ) ) {
\$this-\>logger-\>warning( \'post_creator\', \'Erreur catégories : \' .
\$result-\>get_error_message() ); } else { \$this-\>logger-\>info(
\'post_creator\', \'Catégories assignées : \' . implode( \', \',
\$category_ids ) ); } } private function set_tags( int \$post_id, array
\$tag_names ): void { if ( empty( \$tag_names ) ) { return; } \$result =
wp_set_post_tags( \$post_id, \$tag_names, false ); if ( is_wp_error(
\$result ) ) { \$this-\>logger-\>warning( \'post_creator\', \'Erreur
tags : \' . \$result-\>get_error_message() ); } else {
\$this-\>logger-\>info( \'post_creator\', \'Tags assignés : \' .
implode( \', \', \$tag_names ) ); } } private function
store_plugin_meta( int \$post_id, GenerationResult \$result ): void {
update_post_meta( \$post_id, \'\_techrappy_seo_generated\', \'1\' );
update_post_meta( \$post_id, \'\_techrappy_seo_request_id\',
\$result-\>get_request_id() ); update_post_meta( \$post_id,
\'\_techrappy_seo_generated_at\', current_time( \'mysql\' ) ); \$qa =
\$result-\>get_qa_score(); if ( ! empty( \$qa\[\'score_seo\'\] ) ) {
update_post_meta( \$post_id, \'\_techrappy_seo_score_seo\', (int)
\$qa\[\'score_seo\'\] ); update_post_meta( \$post_id,
\'\_techrappy_seo_score_humain\', (int) \$qa\[\'score_humain\'\] ); } }
} \`\`\` \-\-- \## Fichier 5 :
\`includes/WordPress/PostCreationResult.php\` --- Delta V6 \`\`\`php
\<?php /\*\* \* Delta V6 : ajout du champ yoast_result dans
PostCreationResult. \* \* Seule la factory success() et to_array() sont
modifiées. \* \* \@package TechrappySEO\\WordPress \*/ declare(
strict_types=1 ); namespace TechrappySEO\\WordPress; if ( ! defined(
\'ABSPATH\' ) ) { exit; } final class PostCreationResult { private bool
\$success; private int \$post_id; private string \$permalink; private
string \$slug; private string \$post_type; private string \$post_status;
private string \$error_message; private array \$warnings; private array
\$logs; /\*\* \@var array\<string, mixed\> Résultat de l\'injection
Yoast \[V6\] \*/ private array \$yoast_result = \[\]; // \[V6\] private
function \_\_construct() {} /\*\* \* Factory succès --- avec
yoast_result optionnel \[V6\]. \*/ public static function success( int
\$post_id, string \$permalink, string \$slug, string \$post_type, string
\$status, array \$warnings = \[\], array \$logs = \[\], array
\$yoast_result = \[\] // \[V6\] ): self { \$r = new self();
\$r-\>success = true; \$r-\>post_id = \$post_id; \$r-\>permalink =
\$permalink; \$r-\>slug = \$slug; \$r-\>post_type = \$post_type;
\$r-\>post_status = \$status; \$r-\>error_message = \'\'; \$r-\>warnings
= \$warnings; \$r-\>logs = \$logs; \$r-\>yoast_result = \$yoast_result;
// \[V6\] return \$r; } public static function error( string \$message,
array \$logs = \[\] ): self { \$r = new self(); \$r-\>success = false;
\$r-\>post_id = 0; \$r-\>permalink = \'\'; \$r-\>slug = \'\';
\$r-\>post_type = \'\'; \$r-\>post_status = \'\'; \$r-\>error_message =
\$message; \$r-\>warnings = \[\]; \$r-\>logs = \$logs;
\$r-\>yoast_result = \[\]; return \$r; } public function is_success():
bool { return \$this-\>success; } public function is_error(): bool {
return ! \$this-\>success; } public function get_post_id(): int { return
\$this-\>post_id; } public function get_permalink(): string { return
\$this-\>permalink; } public function get_slug(): string { return
\$this-\>slug; } public function get_post_type(): string { return
\$this-\>post_type; } public function get_status(): string { return
\$this-\>post_status; } public function get_error(): string { return
\$this-\>error_message; } public function get_warnings(): array { return
\$this-\>warnings; } public function get_logs(): array { return
\$this-\>logs; } public function get_yoast_result(): array { return
\$this-\>yoast_result; } // \[V6\] public function get_edit_url():
string { return \$this-\>post_id \> 0 ? get_edit_post_link(
\$this-\>post_id, \'raw\' ) ?? \'\' : \'\'; } public function
to_array(): array { return \[ \'success\' =\> \$this-\>success,
\'post_id\' =\> \$this-\>post_id, \'permalink\' =\> \$this-\>permalink,
\'slug\' =\> \$this-\>slug, \'post_type\' =\> \$this-\>post_type,
\'post_status\' =\> \$this-\>post_status, \'edit_url\' =\>
\$this-\>get_edit_url(), \'error_message\' =\> \$this-\>error_message,
\'warnings\' =\> \$this-\>warnings, \'yoast_result\' =\>
\$this-\>yoast_result, // \[V6\] \]; } } \`\`\` \-\-- \## Fichier 6 :
\`includes/Admin/Ajax/AjaxWordPress.php\` --- Delta V6 \`\`\`php \<?php
/\*\* \* Delta V6 : récupération des paramètres Yoast dans
handle_create_post(). \* \* Seule la méthode handle_create_post() est
modifiée. \* Les lignes ajoutées sont signalées par // \[V6\]. \* \*
\@package TechrappySEO\\Admin\\Ajax \*/ declare( strict_types=1 );
namespace TechrappySEO\\Admin\\Ajax; use
TechrappySEO\\Generation\\GenerationResult; use
TechrappySEO\\WordPress\\PostCreator; use
TechrappySEO\\WordPress\\PostCreationSettings; use
TechrappySEO\\WordPress\\ContentAssembler; use
TechrappySEO\\SEO\\YoastIntegration; // \[V6\] if ( ! defined(
\'ABSPATH\' ) ) { exit; } class AjaxWordPress { /\*\* \* Crée le post
WordPress depuis le JSON SEO. \* V6 : ajout du paramètre yoast_enabled.
\*/ public function handle_create_post(): void {
\$this-\>verify_request( \'techrappy_seo_generation\' ); \$seo_json_raw
= sanitize_textarea_field( wp_unslash( \$\_POST\[\'seo_json\'\] ?? \'\'
) ); if ( empty( \$seo_json_raw ) ) { wp_send_json_error( \[ \'message\'
=\> \_\_( \'Données SEO manquantes.\', \'techrappy-seo\' ) \], 400 ); }
\$seo_data = json_decode( \$seo_json_raw, true ); if ( JSON_ERROR_NONE
!== json_last_error() \|\| ! is_array( \$seo_data ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'JSON SEO invalide.\',
\'techrappy-seo\' ) \], 400 ); } // ── Paramètre Yoast \[V6\] ──
\$yoast_enabled = filter_var( \$\_POST\[\'yoast_enabled\'\] ?? true,
FILTER_VALIDATE_BOOLEAN ); // Si Yoast demandé mais non actif :
avertissement mais on continue. \[V6\] if ( \$yoast_enabled && !
YoastIntegration::is_active() ) { // Yoast non actif --- sera géré par
YoastIntegration::apply_to_post() // qui retournera un
YoastWriteResult::skipped(). } // Mot-clé pour focus keyword \[V6\]
\$mot_cle = sanitize_text_field( wp_unslash( \$\_POST\[\'mot_cle\'\] ??
\'\' ) ); try { \$settings = PostCreationSettings::from_array( \[
\'post_type\' =\> sanitize_key( wp_unslash( \$\_POST\[\'post_type\'\] ??
\'page\' ) ), \'post_status\' =\> sanitize_key( wp_unslash(
\$\_POST\[\'post_status\'\] ?? \'draft\' ) ), \'post_parent\' =\>
absint( \$\_POST\[\'post_parent\'\] ?? 0 ), \'category_ids\' =\>
sanitize_text_field( wp_unslash( \$\_POST\[\'category_ids\'\] ?? \'\' )
), \'tag_names\' =\> sanitize_text_field( wp_unslash(
\$\_POST\[\'tag_names\'\] ?? \'\' ) ), \'links_position\' =\>
sanitize_key( wp_unslash( \$\_POST\[\'links_position\'\] ??
\'after_last\' ) ), \] ); } catch ( \\InvalidArgumentException \$e ) {
wp_send_json_error( \[ \'message\' =\> \$e-\>getMessage() \], 400 ); }
// Passer mot_cle et yoast_enabled dans les settings \[V6\].
\$creation_result = PostCreator::create_post_from_seo_json( \$seo_data,
array_merge( \$settings-\>to_array(), \[ \'mot_cle\' =\> \$mot_cle, //
\[V6\] \'yoast_enabled\' =\> \$yoast_enabled, // \[V6\] \] ) ); if (
\$creation_result-\>is_error() ) { wp_send_json_error( \[ \'message\'
=\> \$creation_result-\>get_error(), \'warnings\' =\>
\$creation_result-\>get_warnings(), \], 500 ); } wp_send_json_success(
\$creation_result-\>to_array() ); } /\*\* \* Prévisualisation HTML sans
création (inchangée V5). \*/ public function handle_preview_content():
void { \$this-\>verify_request( \'techrappy_seo_generation\' );
\$seo_json_raw = sanitize_textarea_field( wp_unslash(
\$\_POST\[\'seo_json\'\] ?? \'\' ) ); if ( empty( \$seo_json_raw ) ) {
wp_send_json_error( \[ \'message\' =\> \_\_( \'seo_json manquant.\',
\'techrappy-seo\' ) \], 400 ); } \$seo_data = json_decode(
\$seo_json_raw, true ); if ( JSON_ERROR_NONE !== json_last_error() \|\|
! is_array( \$seo_data ) ) { wp_send_json_error( \[ \'message\' =\>
\_\_( \'JSON invalide.\', \'techrappy-seo\' ) \], 400 ); } try {
\$result = GenerationResult::from_array( \$seo_data ); \$settings =
PostCreationSettings::from_array( \[ \'post_type\' =\> sanitize_key(
wp_unslash( \$\_POST\[\'post_type\'\] ?? \'page\' ) ), \'post_status\'
=\> \'draft\', \'links_position\' =\> sanitize_key( wp_unslash(
\$\_POST\[\'links_position\'\] ?? \'after_last\' ) ), \] ); } catch (
\\InvalidArgumentException \$e ) { wp_send_json_error( \[ \'message\'
=\> \$e-\>getMessage() \], 400 ); } \$assembler = new
ContentAssembler(); \$html = \$assembler-\>assemble( \$result,
\$settings ); // \[V6\] Inclure le statut Yoast dans la réponse preview.
\$yoast_active = YoastIntegration::is_active(); \$yoast_version =
YoastIntegration::get_version(); wp_send_json_success( \[ \'html\' =\>
\$html, \'metatitle\' =\> \$result-\>get_metatitle(),
\'metadescription\' =\> \$result-\>get_metadescription(), \'slug\' =\>
\$result-\>get_slug(), \'h1\' =\> \$result-\>get_h1(),
\'sections_count\' =\> count( \$result-\>get_sections() ),
\'faq_items_count\' =\> count( \$result-\>get_faq_items() ),
\'warnings\' =\> \$result-\>get_warnings(), \'yoast_active\' =\>
\$yoast_active, // \[V6\] \'yoast_version\' =\> \$yoast_version, //
\[V6\] \] ); } private function verify_request( string \$action ): void
{ check_ajax_referer( \$action, \'nonce\' ); if ( ! current_user_can(
TECHRAPPY_SEO_CAPABILITY ) ) { wp_send_json_error( \[ \'message\' =\>
\_\_( \'Accès non autorisé.\', \'techrappy-seo\' ) \], 403 ); } } }
\`\`\` \-\-- \## Fichier 7 : Delta UI
\`views/admin/wizard/step-1-mode.php\` --- Checkbox Yoast \`\`\`php
\<?php // Ajouter ce bloc dans la vue step-1-mode.php, // APRÈS le champ
\"Statut après création\" et AVANT le bouton submit. // Les deux
variables PHP nécessaires sont déjà disponibles via AdminAssets. ?\>
\<?php // ── Option Yoast SEO \[V6\] ── ?\> \<div
class=\"techrappy-field-group\" id=\"block-yoast-option\"\> \<?php
\$yoast_active = \\TechrappySEO\\SEO\\YoastIntegration::is_active();
\$yoast_version = \\TechrappySEO\\SEO\\YoastIntegration::get_version();
?\> \<label class=\"techrappy-label\"\> \<?php esc_html_e( \'Yoast
SEO\', \'techrappy-seo\' ); ?\> \</label\> \<?php if ( \$yoast_active )
: ?\> \<label class=\"techrappy-checkbox-label\"\> \<input
type=\"checkbox\" id=\"gen-yoast-enabled\" name=\"yoast_enabled\"
value=\"1\" checked\> \<span\> \<?php esc_html_e( \'Renseigner Yoast
(meta title + meta description + focus keyword)\', \'techrappy-seo\' );
?\> \</span\> \</label\> \<p class=\"description
techrappy-yoast-detected\"\> ✅ \<?php printf( /\* translators: %s :
version Yoast \*/ esc_html\_\_( \'Yoast SEO détecté (v%s) --- les
métadonnées seront injectées automatiquement.\', \'techrappy-seo\' ),
esc_html( \$yoast_version ) ); ?\> \</p\> \<?php else : ?\> \<label
class=\"techrappy-checkbox-label techrappy-checkbox-label\--disabled\"\>
\<input type=\"checkbox\" id=\"gen-yoast-enabled\"
name=\"yoast_enabled\" value=\"1\" disabled\> \<span
class=\"techrappy-disabled-text\"\> \<?php esc_html_e( \'Renseigner
Yoast (meta title + meta description)\', \'techrappy-seo\' ); ?\>
\</span\> \</label\> \<p class=\"description
techrappy-yoast-not-detected\"\> ⚠️ \<?php esc_html_e( \'Yoast SEO non
détecté --- installez et activez le plugin Yoast SEO pour activer cette
option.\', \'techrappy-seo\' ); ?\> \<a href=\"\<?php echo esc_url(
admin_url( \'plugin-install.php?s=yoast&tab=search&type=term\' ) );
?\>\" target=\"\_blank\"\> \<?php esc_html_e( \'Installer Yoast SEO\',
\'techrappy-seo\' ); ?\> \</a\> \</p\> \<?php endif; ?\> \</div\> \`\`\`
\-\-- \## Fichier 8 : Delta CSS \`assets/css/admin.css\` --- Styles
Yoast option \`\`\`css /\* ─────────────────────────────────────────
YOAST OPTION --- V6 ───────────────────────────────────────── \*/
.techrappy-checkbox-label { display: flex; align-items: flex-start; gap:
8px; cursor: pointer; font-size: 13px; line-height: 1.5; padding: 6px 0;
} .techrappy-checkbox-label input\[type=\"checkbox\"\] { margin-top:
2px; flex-shrink: 0; } .techrappy-checkbox-label\--disabled { cursor:
not-allowed; opacity: 0.6; } .techrappy-disabled-text { color:
var(\--tr-text-light); } .techrappy-yoast-detected { color:
var(\--tr-success) !important; font-size: 12px !important; margin-top:
4px !important; } .techrappy-yoast-not-detected { color:
var(\--tr-warning) !important; font-size: 12px !important; margin-top:
4px !important; } .techrappy-yoast-not-detected a { color:
var(\--tr-warning); text-decoration: underline; } /\* Badge Yoast dans
le résultat de création \*/ .techrappy-yoast-badge { display:
inline-flex; align-items: center; gap: 4px; font-size: 11px; padding:
2px 8px; border-radius: 10px; margin-left: 8px; }
.techrappy-yoast-badge\--ok { background: #d1fae5; color: #065f46;
border: 1px solid #34d399; } .techrappy-yoast-badge\--skip { background:
#fef3c7; color: #92400e; border: 1px solid #f59e0b; }
.techrappy-yoast-badge\--error { background: #fee2e2; color: #991b1b;
border: 1px solid #f87171; } \`\`\` \-\-- \## Fichier 9 : Delta JS
\`assets/js/wizard.js\` --- Gestion Yoast côté client \`\`\`javascript
// ───────────────────────────────────────── // DELTA V6 --- Yoast dans
wizard.js // Ajouter ces fonctions et les intégrer dans // le flux
existant. // ───────────────────────────────────────── /\*\* \* Récupère
l\'état de la checkbox Yoast. \* Retourne false si désactivée ou non
cochée. \* \* \@returns {boolean} \*/ function isYoastEnabled() { const
\$cb = \$( \'#gen-yoast-enabled\' ); return \$cb.length && ! \$cb.prop(
\'disabled\' ) && \$cb.prop( \'checked\' ); } /\*\* \* Lit le statut
Yoast depuis la réponse de preview \* et affiche la notice appropriée.
\* \* \@param {boolean} yoastActive Yoast est-il actif ? \* \@param
{string} yoastVersion Version Yoast détectée. \*/ function
renderYoastStatus( yoastActive, yoastVersion ) { // Afficher
dynamiquement le statut Yoast détecté // (utile si Yoast a été
activé/désactivé depuis l\'ouverture de la page). const \$block = \$(
\'#block-yoast-option\' ); if ( ! \$block.length ) { return; } if (
yoastActive ) { \$block.find( \'.techrappy-yoast-not-detected\'
).hide(); \$block.find( \'.techrappy-yoast-detected\' ).show();
\$block.find( \'#gen-yoast-enabled\' ).prop( \'disabled\', false ); } }
/\*\* \* Affiche le badge de résultat Yoast dans le panel de création.
\* \* \@param {Object} yoastResult Données de
YoastWriteResult::to_array(). \*/ function renderYoastBadge( yoastResult
) { if ( ! yoastResult ) { return; } const \$title = \$(
\'#creation-result-title\' ); const state = yoastResult.state \|\|
\'skipped\'; let badgeClass = \'techrappy-yoast-badge\--skip\'; let
badgeText = \'⚠️ Yoast : non appliqué\'; if ( state === \'success\' ) {
const written = ( yoastResult.written \|\| \[\] ).length; badgeClass =
\'techrappy-yoast-badge\--ok\'; badgeText = \'✅ Yoast : \' + written +
\' champ(s)\'; } else if ( state === \'error\' ) { badgeClass =
\'techrappy-yoast-badge\--error\'; badgeText = \'❌ Yoast : erreur\'; }
\$title.append( \$( \'\<span\>\' ) .addClass( \'techrappy-yoast-badge
\' + badgeClass ) .text( badgeText ) .attr( \'title\',
yoastResult.message \|\| \'\' ) ); } //
───────────────────────────────────────── // Intégration dans les
fonctions existantes // ───────────────────────────────────────── //
Dans requestHtmlPreview() --- ajouter dans le .done() : // if (
response.data.yoast_active !== undefined ) { // renderYoastStatus(
response.data.yoast_active, response.data.yoast_version ); // } // Dans
createWordPressPost() --- ajouter yoast_enabled dans postData : //
yoast_enabled : isYoastEnabled() ? \'1\' : \'0\', // mot_cle : \$(
\'#gen-mot-cle\' ).val().trim(), // Dans renderCreationSuccess() ---
ajouter après affichage : // if ( data.yoast_result ) { //
renderYoastBadge( data.yoast_result ); // } \`\`\` \-\-- \##
Récapitulatif V6 --- Intégration Yoast SEO \`\`\` includes/SEO/ ├──
YoastIntegration.php ✅ Service principal │ is_active() --- détection 3
niveaux robuste │ get_version() --- version Yoast │ apply_to_post() ---
injection non-bloquante │ read_from_post() --- lecture pour vérification
│ clear_from_post() --- suppression pour régénération │ ├──
YoastMetaData.php ✅ DTO données Yoast │ from_generation_result() ←
factory principale │ from_array() ← depuis données brutes │
validate_lengths() ← rapport longueurs SEO │ └── YoastWriteResult.php ✅
DTO résultat injection success / skipped / error get_written() /
get_failures() Clés meta documentées : \_yoast_wpseo_title → meta title
(Yoast Free ≥ 1.0) \_yoast_wpseo_metadesc → meta desc (Yoast Free ≥ 1.0)
\_yoast_wpseo_focuskw → focus kw (Yoast Free ≥ 1.0, stable en v14+)
\`\`\` \*\*Flux complet V6 :\*\* \`\`\` \[Bouton Créer\] →
createWordPressPost() → postData.yoast_enabled = isYoastEnabled() →
postData.mot_cle = \$(\'#gen-mot-cle\').val() →
wp_ajax_techrappy_create_post → AjaxWordPress::handle_create_post() →
PostCreator::create_post_from_seo_json() ├── wp_insert_post() ├──
set_categories() + set_tags() ├── store_plugin_meta() └──
apply_yoast_meta() ├── YoastMetaData::from_generation_result() │ title =
result-\>get_metatitle() │ desc = result-\>get_metadescription() │
focuskw = mot_cle └── YoastIntegration::apply_to_post() ├── \[Yoast
inactif\] → YoastWriteResult::skipped() ├── \[option disabled\] →
YoastWriteResult::skipped() └── \[Yoast actif\] ├──
update_post_meta(\_yoast_wpseo_title) ├──
update_post_meta(\_yoast_wpseo_metadesc) └──
update_post_meta(\_yoast_wpseo_focuskw) →
YoastWriteResult::success(written: \[3 clés\]) → renderCreationSuccess()
└── renderYoastBadge() → ✅ Yoast : 3 champ(s) \`\`\`
