\# Techrappy SEO --- Module API Villes-Voisines + BAN \## Architecture
des fichiers \`\`\` techrappy-seo/ ├── modules/ │ └── geo/ │ ├──
GeoModule.php \# Point d\'entrée du module │ ├──
VillesVoisinesClient.php \# Client API Villes-Voisines │ ├──
BanClient.php \# Client API BAN │ ├── CityKeywordBuilder.php \#
Générateur de mots-clés locaux │ ├── GeoAjaxHandler.php \# Handlers AJAX
│ └── GeoCache.php \# Cache transient WordPress ├── admin/ │ └──
partials/ │ └── geo-keyword-preview.php \# Vue admin (formulaire +
preview) ├── assets/ │ ├── js/ │ │ └── geo-admin.js \# JS interactions
UI │ └── css/ │ └── geo-admin.css \# Styles \`\`\` \-\-- \## 1.
GeoCache.php \`\`\`php \<?php /\*\* \* GeoCache \* \* Couche de cache
via WordPress Transients API. \* Centralise la mise en cache des
réponses API externes \* pour éviter les appels répétés et absorber les
indisponibilités. \* \* \@package TechrappySEO\\Modules\\Geo \*/
namespace TechrappySEO\\Modules\\Geo; defined( \'ABSPATH\' ) \|\| exit;
class GeoCache { /\*\* \* Durée de cache par défaut : 24 heures. \* Les
données géographiques changent rarement. \*/ const TTL_DEFAULT =
DAY_IN_SECONDS; /\*\* \* Durée de cache pour les erreurs API : 5
minutes. \* Permet une récupération rapide si l\'API revient. \*/ const
TTL_ERROR = 5 \* MINUTE_IN_SECONDS; /\*\* \* Préfixe de toutes les clés
de cache Techrappy Geo. \*/ const KEY_PREFIX = \'techrappy_geo\_\';
/\*\* \* Récupère une valeur depuis le cache. \* \* \@param string \$key
Clé sans préfixe. \* \@return mixed\|false Valeur ou false si
absente/expirée. \*/ public static function get( string \$key ): mixed {
return get_transient( self::build_key( \$key ) ); } /\*\* \* Stocke une
valeur dans le cache. \* \* \@param string \$key Clé sans préfixe. \*
\@param mixed \$value Valeur à stocker (sera sérialisée par WP). \*
\@param int \$ttl Durée en secondes (défaut : TTL_DEFAULT). \* \@return
bool \*/ public static function set( string \$key, mixed \$value, int
\$ttl = self::TTL_DEFAULT ): bool { return set_transient(
self::build_key( \$key ), \$value, \$ttl ); } /\*\* \* Supprime une
entrée du cache. \* \* \@param string \$key \* \@return bool \*/ public
static function delete( string \$key ): bool { return delete_transient(
self::build_key( \$key ) ); } /\*\* \* Vérifie si une clé est en cache.
\* \* \@param string \$key \* \@return bool \*/ public static function
has( string \$key ): bool { return false !== self::get( \$key ); } /\*\*
\* Génère une clé de cache normalisée et préfixée. \* Limite la longueur
à 172 chars (limite WP transient = 191 chars). \* \* \@param string
\$key \* \@return string \*/ public static function build_key( string
\$key ): string { // Normalise : minuscule + remplacement caractères
spéciaux \$normalized = strtolower( preg_replace( \'/\[\^a-z0-9\_\]/\',
\'\_\', \$key ) ); \$full_key = self::KEY_PREFIX . \$normalized; // Si
trop long : hash MD5 if ( strlen( \$full_key ) \> 172 ) { \$full_key =
self::KEY_PREFIX . md5( \$key ); } return \$full_key; } /\*\* \*
Construit une clé pour Villes-Voisines. \* \* \@param string
\$code_postal \* \@param int \$rayon \* \@return string \*/ public
static function vv_key( string \$code_postal, int \$rayon ): string {
return self::build_key( \"vv\_{\$code_postal}\_{\$rayon}\" ); } /\*\* \*
Construit une clé pour BAN. \* \* \@param string \$code_postal \*
\@return string \*/ public static function ban_key( string \$code_postal
): string { return self::build_key( \"ban\_{\$code_postal}\" ); } /\*\*
\* Invalide tous les transients du module Geo. \* Utilise wpdb car WP
n\'a pas d\'API native pour ça. \* \* \@return int Nombre de transients
supprimés. \*/ public static function flush_all(): int { global \$wpdb;
\$like = \$wpdb-\>esc_like( \'\_transient\_\' . self::KEY_PREFIX ) .
\'%\'; \$result = \$wpdb-\>query( \$wpdb-\>prepare( \"DELETE FROM
{\$wpdb-\>options} WHERE option_name LIKE %s\", \$like ) ); return (int)
\$result; } } \`\`\` \-\-- \## 2. VillesVoisinesClient.php \`\`\`php
\<?php /\*\* \* VillesVoisinesClient \* \* Client pour l\'API
Villes-Voisines. \* Endpoint :
https://www.villes-voisines.fr/getcp.php?cp={cp}&rayon={rayon} \* \*
Réponse attendue (tableau JSON) : \* \[ \* { \"code_postal\": \"31700\",
\"nom_commune\": \"Blagnac\", \... }, \* \... \* \] \* \* \@package
TechrappySEO\\Modules\\Geo \*/ namespace TechrappySEO\\Modules\\Geo;
defined( \'ABSPATH\' ) \|\| exit; class VillesVoisinesClient { /\*\* \*
URL de base de l\'API. \*/ const API_BASE =
\'https://www.villes-voisines.fr/getcp.php\'; /\*\* \* Rayons autorisés
(en km). \*/ const ALLOWED_RADII = \[ 10, 20, 30 \]; /\*\* \* Timeout
HTTP en secondes. \*/ const HTTP_TIMEOUT = 10; /\*\* \* User-Agent
envoyé à l\'API. \*/ const USER_AGENT = \'TechrappySEO/1.0 (WordPress
Plugin)\'; /\*\* \* Récupère les villes dans un rayon donné autour d\'un
code postal. \* \* \@param string \$code_postal Code postal de référence
(ex: \"31700\"). \* \@param int \$rayon Rayon en km (10, 20 ou 30). \*
\@return array { \* \@type bool \$success \* \@type array \$villes Liste
de \[ \'code_postal\', \'nom_commune\', \... \] \* \@type string \$error
Message d\'erreur si success = false. \* \@type bool \$from_cache True
si résultat issu du cache. \* } \*/ public function get_villes_proches(
string \$code_postal, int \$rayon ): array { // \-\-- Validation des
paramètres \-\-- \$code_postal = \$this-\>sanitize_code_postal(
\$code_postal ); if ( empty( \$code_postal ) ) { return
\$this-\>error_result( \'Code postal invalide. Format attendu : 5
chiffres.\' ); } if ( ! in_array( \$rayon, self::ALLOWED_RADII, true ) )
{ return \$this-\>error_result( sprintf( \'Rayon invalide : %d km.
Valeurs autorisées : %s.\', \$rayon, implode( \', \',
self::ALLOWED_RADII ) ) ); } // \-\-- Cache \-\-- \$cache_key =
GeoCache::vv_key( \$code_postal, \$rayon ); \$cached_value =
GeoCache::get( \$cache_key ); if ( false !== \$cached_value ) { return
array_merge( \$cached_value, \[ \'from_cache\' =\> true \] ); } // \-\--
Appel API \-\-- \$response = \$this-\>http_get( \$code_postal, \$rayon
); if ( is_wp_error( \$response ) ) { \$error_msg =
\$response-\>get_error_message(); \$this-\>log_error( sprintf( \'Erreur
HTTP Villes-Voisines \[CP:%s, rayon:%d\] : %s\', \$code_postal, \$rayon,
\$error_msg ) ); // Cache court sur l\'erreur pour éviter de marteler
l\'API GeoCache::set( \$cache_key, \$this-\>error_result( \$error_msg ),
GeoCache::TTL_ERROR ); return \$this-\>error_result( \'API
Villes-Voisines indisponible. Réessayez dans quelques minutes.\' ); } //
\-\-- Parsing de la réponse \-\-- \$body = wp_remote_retrieve_body(
\$response ); \$status = wp_remote_retrieve_response_code( \$response );
if ( 200 !== (int) \$status ) { \$error_msg = sprintf( \'API retourne
HTTP %d.\', \$status ); \$this-\>log_error( \$error_msg ); return
\$this-\>error_result( \$error_msg ); } \$parsed =
\$this-\>parse_response( \$body, \$code_postal ); if ( !
\$parsed\[\'success\'\] ) { \$this-\>log_error( \$parsed\[\'error\'\] );
return \$parsed; } // \-\-- Mise en cache du succès \-\-- GeoCache::set(
\$cache_key, \$parsed, GeoCache::TTL_DEFAULT ); return array_merge(
\$parsed, \[ \'from_cache\' =\> false \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// HTTP //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Effectue la requête HTTP via l\'API WordPress. \* \* \@param
string \$code_postal \* \@param int \$rayon \* \@return
array\|\\WP_Error Réponse WP_HTTP ou WP_Error. \*/ private function
http_get( string \$code_postal, int \$rayon ): array\|\\WP_Error { \$url
= add_query_arg( \[ \'cp\' =\> rawurlencode( \$code_postal ), \'rayon\'
=\> rawurlencode( (string) \$rayon ), \], self::API_BASE ); return
wp_remote_get( \$url, \[ \'timeout\' =\> self::HTTP_TIMEOUT,
\'user-agent\' =\> self::USER_AGENT, \'sslverify\' =\> true, \'headers\'
=\> \[ \'Accept\' =\> \'application/json\', \], \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Parsing //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Parse le body JSON de la réponse API. \* \* Structure réponse
Villes-Voisines : \* Peut être un tableau direct ou un objet avec une
clé de données. \* On gère les deux cas. \* \* \@param string \$body
Corps de la réponse. \* \@param string \$code_postal CP d\'origine (pour
filtrage éventuel). \* \@return array \*/ private function
parse_response( string \$body, string \$code_postal ): array { if (
empty( trim( \$body ) ) ) { return \$this-\>error_result( \'Réponse API
vide.\' ); } \$data = json_decode( \$body, true ); if (
json_last_error() !== JSON_ERROR_NONE ) { return \$this-\>error_result(
sprintf( \'Réponse JSON invalide : %s.\', json_last_error_msg() ) ); }
// L\'API peut retourner un tableau ou un objet selon la version if (
is_object( \$data ) ) { \$data = (array) \$data; } // Certaines versions
retournent { \"data\": \[\...\] } if ( isset( \$data\[\'data\'\] ) &&
is_array( \$data\[\'data\'\] ) ) { \$data = \$data\[\'data\'\]; } if ( !
is_array( \$data ) ) { return \$this-\>error_result( \'Format de réponse
inattendu.\' ); } if ( empty( \$data ) ) { return \[ \'success\' =\>
true, \'villes\' =\> \[\], \'from_cache\' =\> false, \'error\' =\> \'\',
\]; } // Normalise chaque entrée \$villes = \[\]; foreach ( \$data as
\$item ) { \$normalized = \$this-\>normalize_ville( \$item ); if (
\$normalized ) { \$villes\[\] = \$normalized; } } // Tri par nom de
commune usort( \$villes, fn( \$a, \$b ) =\> strcmp(
\$a\[\'nom_commune\'\], \$b\[\'nom_commune\'\] ) ); return \[
\'success\' =\> true, \'villes\' =\> \$villes, \'total\' =\> count(
\$villes ), \'from_cache\' =\> false, \'error\' =\> \'\', \]; } /\*\* \*
Normalise une entrée ville depuis la réponse API. \* \* Champs possibles
selon la version de l\'API : \* code_postal / cp / codePostal \*
nom_commune / commune / libelle \* distance (km) \* lat / lon / latitude
/ longitude \* \* \@param mixed \$item \* \@return array\|null Tableau
normalisé ou null si invalide. \*/ private function normalize_ville(
mixed \$item ): ?array { if ( ! is_array( \$item ) && ! is_object(
\$item ) ) { return null; } \$item = (array) \$item; // Résolution code
postal (plusieurs noms de champ possibles) \$cp =
\$item\[\'code_postal\'\] ?? \$item\[\'cp\'\] ??
\$item\[\'codePostal\'\] ?? \$item\[\'CODE_POSTAL\'\] ?? null; //
Résolution nom commune \$nom = \$item\[\'nom_commune\'\] ??
\$item\[\'commune\'\] ?? \$item\[\'libelle\'\] ??
\$item\[\'NOM_COMMUNE\'\] ?? \$item\[\'nom\'\] ?? null; if ( empty( \$cp
) \|\| empty( \$nom ) ) { return null; } // Nettoyage \$cp =
sanitize_text_field( trim( (string) \$cp ) ); \$nom =
\$this-\>normalize_commune_name( sanitize_text_field( trim( (string)
\$nom ) ) ); if ( ! preg_match( \'/\^\\d{5}\$/\', \$cp ) ) { return
null; // CP français invalide } \$normalized = \[ \'code_postal\' =\>
\$cp, \'nom_commune\' =\> \$nom, \]; // Champs optionnels if ( isset(
\$item\[\'distance\'\] ) ) { \$normalized\[\'distance_km\'\] = round(
(float) \$item\[\'distance\'\], 1 ); } if ( isset( \$item\[\'lat\'\] )
\|\| isset( \$item\[\'latitude\'\] ) ) { \$normalized\[\'lat\'\] =
(float) ( \$item\[\'lat\'\] ?? \$item\[\'latitude\'\] ); } if ( isset(
\$item\[\'lon\'\] ) \|\| isset( \$item\[\'longitude\'\] ) ) {
\$normalized\[\'lon\'\] = (float) ( \$item\[\'lon\'\] ??
\$item\[\'longitude\'\] ); } if ( isset( \$item\[\'insee\'\] ) \|\|
isset( \$item\[\'code_insee\'\] ) ) { \$normalized\[\'code_insee\'\] =
sanitize_text_field( (string) ( \$item\[\'insee\'\] ??
\$item\[\'code_insee\'\] ) ); } return \$normalized; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Helpers //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Valide et normalise un code postal français. \* \* \@param
string \$cp \* \@return string CP nettoyé ou \'\' si invalide. \*/
private function sanitize_code_postal( string \$cp ): string { \$cp =
preg_replace( \'/\\s+/\', \'\', trim( \$cp ) ); // 5 chiffres, DOM-TOM
inclus (97xxx, 98xxx) return preg_match( \'/\^\\d{5}\$/\', \$cp ) ? \$cp
: \'\'; } /\*\* \* Normalise le nom d\'une commune : \* - Capitalise
chaque mot \* - Gère les tirets et apostrophes \* \* \@param string
\$name \* \@return string \*/ private function normalize_commune_name(
string \$name ): string { // Passage en majuscule puis mb_convert_case
\$name = mb_convert_case( mb_strtolower( \$name, \'UTF-8\' ),
MB_CASE_TITLE, \'UTF-8\' ); // Corrige les prépositions après tiret
(Saint-Jean → Saint-Jean) \$name = preg_replace_callback( \'/(-\\w)/u\',
fn( \$m ) =\> mb_strtoupper( \$m\[0\], \'UTF-8\' ), \$name ); return
\$name; } /\*\* \* Construit un résultat d\'erreur standardisé. \* \*
\@param string \$message \* \@return array \*/ private function
error_result( string \$message ): array { return \[ \'success\' =\>
false, \'villes\' =\> \[\], \'total\' =\> 0, \'from_cache\' =\> false,
\'error\' =\> \$message, \]; } /\*\* \* Log une erreur sans planter. \*
\* \@param string \$message \*/ private function log_error( string
\$message ): void { error_log(
\'\[TechrappySEO\]\[VillesVoisinesClient\] \' . \$message ); } } \`\`\`
\-\-- \## 3. BanClient.php \`\`\`php \<?php /\*\* \* BanClient \* \*
Client pour l\'API BAN (Base Adresse Nationale). \* Endpoint recherche
par CP : https://api-adresse.data.gouv.fr/search/ \* \* Utilisé pour :
\* - Résoudre un code postal en nom de commune normalisé \* - Enrichir
les données géographiques (INSEE, lat/lon) \* - Valider l\'existence
d\'un code postal \* \* \@package TechrappySEO\\Modules\\Geo \*/
namespace TechrappySEO\\Modules\\Geo; defined( \'ABSPATH\' ) \|\| exit;
class BanClient { /\*\* \* URL de base de l\'API BAN. \*/ const API_BASE
= \'https://api-adresse.data.gouv.fr/search/\'; /\*\* \* Timeout HTTP.
\*/ const HTTP_TIMEOUT = 8; /\*\* \* User-Agent. \*/ const USER_AGENT =
\'TechrappySEO/1.0 (WordPress Plugin)\'; /\*\* \* Résout un code postal
en informations de commune via l\'API BAN. \* \* Retourne la commune
principale associée au code postal. \* En cas de doublons (plusieurs
communes pour un CP), retourne la première. \* \* \@param string
\$code_postal \* \@return array { \* \@type bool \$success \* \@type
array \$commune { \* \@type string \$nom_commune Nom normalisé. \*
\@type string \$code_postal CP. \* \@type string \$code_insee Code
INSEE. \* \@type float \$lat Latitude (si dispo). \* \@type float \$lon
Longitude (si dispo). \* } \* \@type array \$all_communes Toutes les
communes trouvées pour ce CP. \* \@type string \$error \* \@type bool
\$from_cache \* } \*/ public function resolve_code_postal( string
\$code_postal ): array { \$code_postal = \$this-\>sanitize_code_postal(
\$code_postal ); if ( empty( \$code_postal ) ) { return
\$this-\>error_result( \'Code postal invalide.\' ); } // \-\-- Cache
\-\-- \$cache_key = GeoCache::ban_key( \$code_postal ); \$cached =
GeoCache::get( \$cache_key ); if ( false !== \$cached ) { return
array_merge( \$cached, \[ \'from_cache\' =\> true \] ); } // \-\-- Appel
API BAN \-\-- \$response = \$this-\>http_search( \$code_postal ); if (
is_wp_error( \$response ) ) { \$error_msg =
\$response-\>get_error_message(); \$this-\>log_error( sprintf( \'Erreur
HTTP BAN \[CP:%s\] : %s\', \$code_postal, \$error_msg ) ); // Pas de
mise en cache d\'erreur pour BAN // (utilisé comme fallback, on réessaie
rapidement) return \$this-\>error_result( \'API BAN indisponible : \' .
\$error_msg ); } \$status = wp_remote_retrieve_response_code( \$response
); \$body = wp_remote_retrieve_body( \$response ); if ( 200 !== (int)
\$status ) { return \$this-\>error_result( sprintf( \'API BAN : HTTP
%d.\', \$status ) ); } \$parsed = \$this-\>parse_ban_response( \$body,
\$code_postal ); if ( \$parsed\[\'success\'\] ) { GeoCache::set(
\$cache_key, \$parsed, GeoCache::TTL_DEFAULT ); } return array_merge(
\$parsed, \[ \'from_cache\' =\> false \] ); } /\*\* \* Résout plusieurs
codes postaux en lot. \* Optimisé : utilise le cache pour éviter les
appels redondants. \* \* \@param string\[\] \$codes_postaux \* \@return
array\<string, array\> CP =\> résultat resolve_code_postal() \*/ public
function resolve_batch( array \$codes_postaux ): array { \$results =
\[\]; foreach ( array_unique( \$codes_postaux ) as \$cp ) { \$cp =
sanitize_text_field( trim( \$cp ) ); \$results\[ \$cp \] =
\$this-\>resolve_code_postal( \$cp ); } return \$results; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// HTTP //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Appel API BAN en mode recherche par code postal. \* \* \@param
string \$code_postal \* \@return array\|\\WP_Error \*/ private function
http_search( string \$code_postal ): array\|\\WP_Error { \$url =
add_query_arg( \[ \'q\' =\> rawurlencode( \$code_postal ), \'type\' =\>
\'municipality\', \'limit\' =\> \'10\', \'autocomplete\' =\> \'0\', \],
self::API_BASE ); return wp_remote_get( \$url, \[ \'timeout\' =\>
self::HTTP_TIMEOUT, \'user-agent\' =\> self::USER_AGENT, \'sslverify\'
=\> true, \'headers\' =\> \[ \'Accept\' =\> \'application/json\', \], \]
); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Parsing //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Parse la réponse GeoJSON de l\'API BAN. \* \* Structure BAN
(GeoJSON FeatureCollection) : \* { \* \"features\": \[ \* { \*
\"properties\": { \* \"label\": \"Beauzelle\", \* \"postcode\":
\"31700\", \* \"citycode\": \"31056\", \* \"city\": \"Beauzelle\", \*
\"name\": \"Beauzelle\" \* }, \* \"geometry\": { \* \"coordinates\":
\[1.3434, 43.6764\] \* } \* } \* \] \* } \* \* \@param string \$body \*
\@param string \$code_postal \* \@return array \*/ private function
parse_ban_response( string \$body, string \$code_postal ): array { if (
empty( trim( \$body ) ) ) { return \$this-\>error_result( \'Réponse BAN
vide.\' ); } \$data = json_decode( \$body, true ); if (
json_last_error() !== JSON_ERROR_NONE ) { return \$this-\>error_result(
\'Réponse BAN JSON invalide.\' ); } \$features = \$data\[\'features\'\]
?? \[\]; if ( empty( \$features ) ) { return \$this-\>error_result(
sprintf( \'Aucune commune trouvée pour le code postal %s.\',
\$code_postal ) ); } // Parse toutes les communes \$communes = \[\];
foreach ( \$features as \$feature ) { \$commune =
\$this-\>extract_commune( \$feature, \$code_postal ); if ( \$commune ) {
\$communes\[\] = \$commune; } } if ( empty( \$communes ) ) { return
\$this-\>error_result( \'Parsing BAN : aucune commune valide extraite.\'
); } // Filtre sur le code postal exact si plusieurs résultats
\$filtered = array_filter( \$communes, fn( \$c ) =\>
\$c\[\'code_postal\'\] === \$code_postal ); // Si le filtre strict donne
un résultat, on l\'utilise, sinon on garde tout \$final_communes = !
empty( \$filtered ) ? array_values( \$filtered ) : \$communes; return \[
\'success\' =\> true, \'commune\' =\> \$final_communes\[0\], // Commune
principale \'all_communes\' =\> \$final_communes, \'from_cache\' =\>
false, \'error\' =\> \'\', \]; } /\*\* \* Extrait les données d\'une
commune depuis un Feature GeoJSON BAN. \* \* \@param array \$feature \*
\@param string \$code_postal_ref CP de référence pour le filtre. \*
\@return array\|null \*/ private function extract_commune( array
\$feature, string \$code_postal_ref ): ?array { \$props =
\$feature\[\'properties\'\] ?? \[\]; \$geom = \$feature\[\'geometry\'\]
?? \[\]; \$nom = \$props\[\'city\'\] ?? \$props\[\'label\'\] ??
\$props\[\'name\'\] ?? null; \$cp = \$props\[\'postcode\'\] ??
\$props\[\'citycode\'\] ?? null; // BAN utilise \"citycode\" pour
l\'INSEE \$insee = \$props\[\'citycode\'\] ?? \$props\[\'id\'\] ?? null;
if ( empty( \$nom ) ) { return null; } \$commune = \[ \'nom_commune\'
=\> sanitize_text_field( trim( \$nom ) ), \'code_postal\' =\>
sanitize_text_field( trim( (string) \$cp ) ), \'code_insee\' =\>
sanitize_text_field( trim( (string) ( \$insee ?? \'\' ) ) ), \]; //
Coordonnées depuis geometry.coordinates \[lon, lat\] \$coords =
\$geom\[\'coordinates\'\] ?? null; if ( is_array( \$coords ) && count(
\$coords ) \>= 2 ) { \$commune\[\'lon\'\] = (float) \$coords\[0\];
\$commune\[\'lat\'\] = (float) \$coords\[1\]; } return \$commune; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Helpers //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@param string \$cp \* \@return string \*/ private function
sanitize_code_postal( string \$cp ): string { \$cp = preg_replace(
\'/\\s+/\', \'\', trim( \$cp ) ); return preg_match( \'/\^\\d{5}\$/\',
\$cp ) ? \$cp : \'\'; } /\*\* \* \@param string \$message \* \@return
array \*/ private function error_result( string \$message ): array {
return \[ \'success\' =\> false, \'commune\' =\> \[\], \'all_communes\'
=\> \[\], \'from_cache\' =\> false, \'error\' =\> \$message, \]; } /\*\*
\* \@param string \$message \*/ private function log_error( string
\$message ): void { error_log( \'\[TechrappySEO\]\[BanClient\] \' .
\$message ); } } \`\`\` \-\-- \## 4. CityKeywordBuilder.php \`\`\`php
\<?php /\*\* \* CityKeywordBuilder \* \* Génère une liste de mots-clés
locaux SEO à partir : \* - D\'une profession (ex: \"ostéopathe\") \* -
D\'une liste de villes (issues de VillesVoisinesClient + BanClient) \*
\* Règles de génération : \* - Pattern principal : \"{profession}
{ville}\" \* - Variante accent/sans accent \* - Dédoublonnage
intelligent \* - Slug généré pour chaque keyword \* \* \@package
TechrappySEO\\Modules\\Geo \*/ namespace TechrappySEO\\Modules\\Geo;
defined( \'ABSPATH\' ) \|\| exit; class CityKeywordBuilder { /\*\* \*
Profession de base (ex: \"ostéopathe\"). \* \* \@var string \*/ private
string \$profession; /\*\* \* Liste des variantes de profession. \* Ex:
\[\"ostéopathe\", \"osteopathe\", \"cabinet d\'ostéopathie\"\] \* \*
\@var string\[\] \*/ private array \$profession_variants; /\*\* \*
Options de génération. \* \* \@var array \*/ private array \$options;
/\*\* \* \@param string \$profession Profession de base. \* \@param
array \$profession_variants Variantes supplémentaires (optionnel). \*
\@param array \$options { \* \@type bool \$include_variants Inclure les
variantes de profession. Défaut true. \* \@type bool \$include_slug
Inclure le slug dans le résultat. Défaut true. \* \@type bool
\$deduplicate Dédoublonner (insensible casse). Défaut true. \* \@type
int \$max_keywords Limite max de keywords. Défaut 500. \* } \*/ public
function \_\_construct( string \$profession, array \$profession_variants
= \[\], array \$options = \[\] ) { \$this-\>profession =
\$this-\>clean_profession( \$profession ); \$this-\>profession_variants
= array_map( \[ \$this, \'clean_profession\' \], \$profession_variants
); \$this-\>options = array_merge( \[ \'include_variants\' =\> true,
\'include_slug\' =\> true, \'deduplicate\' =\> true, \'max_keywords\'
=\> 500, \], \$options ); } /\*\* \* Génère les keywords à partir d\'une
liste de villes normalisées. \* \* \@param array \$villes Tableau de
villes : \[ \[\'nom_commune\' =\> \..., \'code_postal\' =\> \...\], \...
\] \* \@return array { \* \@type array \$keywords Liste de keyword
objects. \* \@type int \$total Nombre total de keywords générés. \*
\@type array \$errors Erreurs non-bloquantes. \* } \*/ public function
build( array \$villes ): array { if ( empty( \$this-\>profession ) ) {
return \[ \'keywords\' =\> \[\], \'total\' =\> 0, \'errors\' =\> \[
\'Profession non définie.\' \], \]; } if ( empty( \$villes ) ) { return
\[ \'keywords\' =\> \[\], \'total\' =\> 0, \'errors\' =\> \[ \'Liste de
villes vide.\' \], \]; } \$errors = \[\]; \$keywords = \[\]; \$seen =
\[\]; // Construit la liste des professions à utiliser \$professions =
\[ \$this-\>profession \]; if ( \$this-\>options\[\'include_variants\'\]
&& ! empty( \$this-\>profession_variants ) ) { \$professions =
array_merge( \$professions, \$this-\>profession_variants ); } foreach (
\$villes as \$index =\> \$ville ) { // Validation de l\'entrée if ( !
is_array( \$ville ) \|\| empty( \$ville\[\'nom_commune\'\] ) ) {
\$errors\[\] = sprintf( \'Ville\[%d\] invalide ou nom_commune
manquant.\', \$index ); continue; } \$nom = sanitize_text_field(
\$ville\[\'nom_commune\'\] ); \$cp = sanitize_text_field(
\$ville\[\'code_postal\'\] ?? \'\' ); foreach ( \$professions as \$prof
) { \$kw_entry = \$this-\>build_keyword_entry( \$prof, \$nom, \$cp,
\$ville ); if ( null === \$kw_entry ) { continue; } // Dédoublonnage if
( \$this-\>options\[\'deduplicate\'\] ) { \$dedup_key = mb_strtolower(
\$kw_entry\[\'keyword\'\], \'UTF-8\' ); if ( isset( \$seen\[ \$dedup_key
\] ) ) { continue; } \$seen\[ \$dedup_key \] = true; } \$keywords\[\] =
\$kw_entry; // Limite max if ( count( \$keywords ) \>=
\$this-\>options\[\'max_keywords\'\] ) { \$errors\[\] = sprintf(
\'Limite de %d keywords atteinte. Les suivants sont ignorés.\',
\$this-\>options\[\'max_keywords\'\] ); break 2; // Sort des deux
foreach } } } // Tri alphabétique sur le keyword usort( \$keywords, fn(
\$a, \$b ) =\> strcmp( \$a\[\'keyword\'\], \$b\[\'keyword\'\] ) );
return \[ \'keywords\' =\> \$keywords, \'total\' =\> count( \$keywords
), \'errors\' =\> \$errors, \]; } /\*\* \* Construit un objet keyword
complet pour une paire profession/ville. \* \* \@param string
\$profession \* \@param string \$nom_commune \* \@param string
\$code_postal \* \@param array \$ville_data Données complètes de la
ville. \* \@return array\|null \*/ private function build_keyword_entry(
string \$profession, string \$nom_commune, string \$code_postal, array
\$ville_data ): ?array { if ( empty( \$profession ) \|\| empty(
\$nom_commune ) ) { return null; } // Keyword principal : \"ostéopathe
Beauzelle\" \$keyword = \$profession . \' \' . \$nom_commune; \$entry =
\[ \'keyword\' =\> \$keyword, \'profession\' =\> \$profession, \'ville\'
=\> \$nom_commune, \'code_postal\' =\> \$code_postal, \]; // Slug :
\"osteopathe-beauzelle\" if ( \$this-\>options\[\'include_slug\'\] ) {
\$entry\[\'slug\'\] = \$this-\>build_slug( \$keyword ); } // Métadonnées
géographiques optionnelles if ( ! empty( \$ville_data\[\'distance_km\'\]
) ) { \$entry\[\'distance_km\'\] = \$ville_data\[\'distance_km\'\]; } if
( ! empty( \$ville_data\[\'lat\'\] ) && ! empty( \$ville_data\[\'lon\'\]
) ) { \$entry\[\'lat\'\] = \$ville_data\[\'lat\'\]; \$entry\[\'lon\'\] =
\$ville_data\[\'lon\'\]; } if ( ! empty( \$ville_data\[\'code_insee\'\]
) ) { \$entry\[\'code_insee\'\] = \$ville_data\[\'code_insee\'\]; }
return \$entry; } /\*\* \* Génère un slug SEO à partir d\'un keyword. \*
\"Ostéopathe à Beauzelle\" → \"osteopathe-a-beauzelle\" \* \* \@param
string \$keyword \* \@return string \*/ public function build_slug(
string \$keyword ): string { // Utilise sanitize_title de WordPress qui
gère l\'Unicode return sanitize_title( \$keyword ); } /\*\* \* Méthode
de commodité : génère les keywords depuis un code postal \* en
orchestrant VillesVoisinesClient + BanClient. \* \* \@param string
\$code_postal CP de référence. \* \@param int \$rayon Rayon en km. \*
\@param bool \$include_origin Inclure la ville d\'origine. Défaut true.
\* \@return array { \* \@type bool \$success \* \@type array \$keywords
Résultat de build(). \* \@type array \$villes Villes utilisées. \*
\@type array \$errors \* } \*/ public function build_from_code_postal(
string \$code_postal, int \$rayon, bool \$include_origin = true ): array
{ \$errors = \[\]; // 1. Récupère les villes voisines \$vv_client = new
VillesVoisinesClient(); \$vv_result = \$vv_client-\>get_villes_proches(
\$code_postal, \$rayon ); if ( ! \$vv_result\[\'success\'\] ) { return
\[ \'success\' =\> false, \'keywords\' =\> \[\], \'villes\' =\> \[\],
\'errors\' =\> \[ \'Villes-Voisines : \' . \$vv_result\[\'error\'\] \],
\]; } \$villes = \$vv_result\[\'villes\'\]; // 2. Optionnel : ajoute la
ville d\'origine via BAN if ( \$include_origin ) { \$ban_client = new
BanClient(); \$ban_result = \$ban_client-\>resolve_code_postal(
\$code_postal ); if ( \$ban_result\[\'success\'\] && ! empty(
\$ban_result\[\'commune\'\] ) ) { \$origin_ville =
\$ban_result\[\'commune\'\]; // Vérifie que la ville d\'origine n\'est
pas déjà dans la liste \$already_in = array_filter( \$villes, fn( \$v )
=\> \$v\[\'code_postal\'\] === \$origin_ville\[\'code_postal\'\] ); if (
empty( \$already_in ) ) { // Insère en première position array_unshift(
\$villes, array_merge( \$origin_ville, \[ \'distance_km\' =\> 0.0 \] )
); } } else { \$errors\[\] = \'BAN : impossible de résoudre la ville
d\\\'origine. \' . \$ban_result\[\'error\'\]; } } // 3. Génère les
keywords \$kw_result = \$this-\>build( \$villes ); \$errors =
array_merge( \$errors, \$kw_result\[\'errors\'\] ); return \[
\'success\' =\> true, \'keywords\' =\> \$kw_result\[\'keywords\'\],
\'total\' =\> \$kw_result\[\'total\'\], \'villes\' =\> \$villes,
\'errors\' =\> \$errors, \]; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Helpers //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Nettoie et normalise une profession. \* \* \@param string
\$profession \* \@return string \*/ private function clean_profession(
string \$profession ): string { return trim( sanitize_text_field(
\$profession ) ); } /\*\* \* Retourne la profession principale. \* \*
\@return string \*/ public function get_profession(): string { return
\$this-\>profession; } /\*\* \* Retourne les variantes de profession. \*
\* \@return string\[\] \*/ public function get_variants(): array {
return \$this-\>profession_variants; } } \`\`\` \-\-- \## 5.
GeoAjaxHandler.php \`\`\`php \<?php /\*\* \* GeoAjaxHandler \* \* Gère
les actions AJAX du module Geo : \* - techrappy_geo_preview : preview
villes + keywords \* - techrappy_geo_resolve : résolution BAN seule
(code postal → commune) \* - techrappy_geo_flush_cache : vide le cache
geo \* \* \@package TechrappySEO\\Modules\\Geo \*/ namespace
TechrappySEO\\Modules\\Geo; defined( \'ABSPATH\' ) \|\| exit; class
GeoAjaxHandler { const REQUIRED_CAP = \'manage_options\'; const
NONCE_ACTION = \'techrappy_geo_nonce\'; /\*\* \* Enregistre les hooks
AJAX. \*/ public function register(): void { add_action(
\'wp_ajax_techrappy_geo_preview\', \[ \$this, \'ajax_preview\' \] );
add_action( \'wp_ajax_techrappy_geo_resolve\', \[ \$this,
\'ajax_resolve_cp\' \] ); add_action(
\'wp_ajax_techrappy_geo_flush_cache\', \[ \$this, \'ajax_flush_cache\'
\] ); } //
=========================================================================
// AJAX : Preview villes + keywords //
=========================================================================
/\*\* \* POST params : \* nonce string \* code_postal string (ex:
\"31700\") \* rayon int (10\|20\|30) \* profession string (ex:
\"ostéopathe\") \* variants string JSON array de variantes (optionnel)
\*/ public function ajax_preview(): void { \$this-\>verify_nonce();
\$this-\>check_capability(); // \-\-- Inputs \-\-- \$code_postal =
isset( \$\_POST\[\'code_postal\'\] ) ? sanitize_text_field( wp_unslash(
\$\_POST\[\'code_postal\'\] ) ) : \'\'; \$rayon = isset(
\$\_POST\[\'rayon\'\] ) ? absint( wp_unslash( \$\_POST\[\'rayon\'\] ) )
: 10; \$profession = isset( \$\_POST\[\'profession\'\] ) ?
sanitize_text_field( wp_unslash( \$\_POST\[\'profession\'\] ) ) : \'\';
\$variants_raw = isset( \$\_POST\[\'variants\'\] ) ? wp_unslash(
\$\_POST\[\'variants\'\] ) : \'\[\]\'; \$variants = json_decode(
\$variants_raw, true ); if ( ! is_array( \$variants ) ) { \$variants =
\[\]; } \$variants = array_map( \'sanitize_text_field\', \$variants );
// \-\-- Validation \-\-- if ( empty( \$code_postal ) ) {
wp_send_json_error( \[ \'message\' =\> \'Code postal requis.\' \], 400
); } if ( empty( \$profession ) ) { wp_send_json_error( \[ \'message\'
=\> \'Profession requise.\' \], 400 ); } if ( ! in_array( \$rayon,
VillesVoisinesClient::ALLOWED_RADII, true ) ) { wp_send_json_error( \[
\'message\' =\> sprintf( \'Rayon invalide. Valeurs autorisées : %s.\',
implode( \', \', VillesVoisinesClient::ALLOWED_RADII ) ), \], 400 ); }
// \-\-- Génération \-\-- \$builder = new CityKeywordBuilder(
\$profession, \$variants ); \$result =
\$builder-\>build_from_code_postal( \$code_postal, \$rayon, true ); if (
! \$result\[\'success\'\] ) { wp_send_json_error( \[ \'message\' =\>
\'Erreur lors de la génération.\', \'errors\' =\>
\$result\[\'errors\'\], \], 422 ); } // \-\-- Sanitize output \-\--
\$villes_clean = \$this-\>sanitize_villes_output( \$result\[\'villes\'\]
); \$keywords_clean = \$this-\>sanitize_keywords_output(
\$result\[\'keywords\'\] ); wp_send_json_success( \[ \'villes\' =\>
\$villes_clean, \'keywords\' =\> \$keywords_clean, \'total_villes\' =\>
count( \$villes_clean ), \'total_keywords\' =\> count( \$keywords_clean
), \'warnings\' =\> \$result\[\'errors\'\], \'from_cache\'=\> false, //
Simplifié : serait issu du résultat VV \] ); } //
=========================================================================
// AJAX : Résolution BAN seule //
=========================================================================
/\*\* \* POST params : \* nonce string \* code_postal string \*/ public
function ajax_resolve_cp(): void { \$this-\>verify_nonce();
\$this-\>check_capability(); \$code_postal = isset(
\$\_POST\[\'code_postal\'\] ) ? sanitize_text_field( wp_unslash(
\$\_POST\[\'code_postal\'\] ) ) : \'\'; if ( empty( \$code_postal ) ) {
wp_send_json_error( \[ \'message\' =\> \'Code postal requis.\' \], 400
); } \$ban = new BanClient(); \$result = \$ban-\>resolve_code_postal(
\$code_postal ); if ( ! \$result\[\'success\'\] ) { wp_send_json_error(
\[ \'message\' =\> \$result\[\'error\'\], \], 422 ); }
wp_send_json_success( \[ \'commune\' =\> \$result\[\'commune\'\],
\'all_communes\' =\> \$result\[\'all_communes\'\], \'from_cache\' =\>
\$result\[\'from_cache\'\], \] ); } //
=========================================================================
// AJAX : Flush cache //
=========================================================================
public function ajax_flush_cache(): void { \$this-\>verify_nonce();
\$this-\>check_capability(); \$deleted = GeoCache::flush_all();
wp_send_json_success( \[ \'deleted\' =\> \$deleted, \'message\' =\>
sprintf( \'%d entrée(s) de cache supprimée(s).\', \$deleted ), \] ); }
//
=========================================================================
// Sanitize output //
=========================================================================
/\*\* \* \@param array \$villes \* \@return array \*/ private function
sanitize_villes_output( array \$villes ): array { return array_map(
function ( \$ville ) { return \[ \'nom_commune\' =\> esc_html(
\$ville\[\'nom_commune\'\] ?? \'\' ), \'code_postal\' =\> esc_html(
\$ville\[\'code_postal\'\] ?? \'\' ), \'code_insee\' =\> esc_html(
\$ville\[\'code_insee\'\] ?? \'\' ), \'distance_km\' =\> isset(
\$ville\[\'distance_km\'\] ) ? (float) \$ville\[\'distance_km\'\] :
null, \'lat\' =\> isset( \$ville\[\'lat\'\] ) ? (float)
\$ville\[\'lat\'\] : null, \'lon\' =\> isset( \$ville\[\'lon\'\] ) ?
(float) \$ville\[\'lon\'\] : null, \]; }, \$villes ); } /\*\* \* \@param
array \$keywords \* \@return array \*/ private function
sanitize_keywords_output( array \$keywords ): array { return array_map(
function ( \$kw ) { return \[ \'keyword\' =\> esc_html(
\$kw\[\'keyword\'\] ?? \'\' ), \'profession\' =\> esc_html(
\$kw\[\'profession\'\] ?? \'\' ), \'ville\' =\> esc_html(
\$kw\[\'ville\'\] ?? \'\' ), \'code_postal\' =\> esc_html(
\$kw\[\'code_postal\'\] ?? \'\' ), \'slug\' =\> esc_html(
\$kw\[\'slug\'\] ?? \'\' ), \'distance_km\' =\> isset(
\$kw\[\'distance_km\'\] ) ? (float) \$kw\[\'distance_km\'\] : null, \];
}, \$keywords ); } //
=========================================================================
// Sécurité //
=========================================================================
private function verify_nonce(): void { \$nonce = isset(
\$\_POST\[\'nonce\'\] ) ? sanitize_text_field( wp_unslash(
\$\_POST\[\'nonce\'\] ) ) : \'\'; if ( ! wp_verify_nonce( \$nonce,
self::NONCE_ACTION ) ) { wp_send_json_error( \[ \'message\' =\> \'Nonce
invalide.\' \], 403 ); } } private function check_capability(): void {
if ( ! current_user_can( self::REQUIRED_CAP ) ) { wp_send_json_error( \[
\'message\' =\> \'Permission refusée.\' \], 403 ); } } } \`\`\` \-\--
\## 6. GeoModule.php \`\`\`php \<?php /\*\* \* GeoModule \* \* Point
d\'entrée du module Geo (Villes-Voisines + BAN + Keywords). \*
Instancier depuis le plugin principal : ( new GeoModule()
)-\>register(); \* \* \@package TechrappySEO\\Modules\\Geo \*/ namespace
TechrappySEO\\Modules\\Geo; defined( \'ABSPATH\' ) \|\| exit; class
GeoModule { const SCRIPT_HANDLE = \'techrappy-geo-admin\'; const
STYLE_HANDLE = \'techrappy-geo-admin-css\'; const ADMIN_PAGE =
\'techrappy-seo\'; /\*\* \* \@var GeoAjaxHandler \*/ private
GeoAjaxHandler \$ajax_handler; public function \_\_construct() {
\$this-\>ajax_handler = new GeoAjaxHandler(); } /\*\* \* Enregistre tous
les hooks du module. \*/ public function register(): void { // AJAX
\$this-\>ajax_handler-\>register(); // Assets admin add_action(
\'admin_enqueue_scripts\', \[ \$this, \'enqueue_assets\' \] ); // Onglet
dans l\'UI du plugin principal add_filter( \'techrappy_seo_admin_tabs\',
\[ \$this, \'register_admin_tab\' \] ); } /\*\* \* \@param string \$hook
\*/ public function enqueue_assets( string \$hook ): void { if ( !
str_contains( \$hook, self::ADMIN_PAGE ) ) { return; } wp_enqueue_style(
self::STYLE_HANDLE, TECHRAPPY_SEO_URL . \'assets/css/geo-admin.css\',
\[\], TECHRAPPY_SEO_VERSION ); wp_enqueue_script( self::SCRIPT_HANDLE,
TECHRAPPY_SEO_URL . \'assets/js/geo-admin.js\', \[ \'jquery\' \],
TECHRAPPY_SEO_VERSION, true ); wp_localize_script( self::SCRIPT_HANDLE,
\'techrappyGeoData\', \[ \'ajaxUrl\' =\> admin_url( \'admin-ajax.php\'
), \'nonce\' =\> wp_create_nonce( GeoAjaxHandler::NONCE_ACTION ),
\'i18n\' =\> \[ \'loading\' =\> \_\_( \'Chargement...\',
\'techrappy-seo\' ), \'errorGeneric\' =\> \_\_( \'Une erreur est
survenue.\', \'techrappy-seo\' ), \'noResults\' =\> \_\_( \'Aucun
résultat.\', \'techrappy-seo\' ), \'copySuccess\' =\> \_\_( \'Keywords
copiés !\', \'techrappy-seo\' ), \'fromCache\' =\> \_\_( \'(depuis le
cache)\', \'techrappy-seo\' ), \], \] ); // Nonce disponible pour le
partial \$GLOBALS\[\'techrappy_geo_nonce\'\] = wp_create_nonce(
GeoAjaxHandler::NONCE_ACTION ); } /\*\* \* \@param array \$tabs \*
\@return array \*/ public function register_admin_tab( array \$tabs ):
array { \$tabs\[\'geo\'\] = \[ \'label\' =\> \_\_( \'🗺️ Villes &
Keywords\', \'techrappy-seo\' ), \'callback\' =\> \[ \$this,
\'render_page\' \], \]; return \$tabs; } /\*\* \* Rendu de la page
admin. \* \* \@return string \*/ public function render_page(): string {
\$nonce_value = \$GLOBALS\[\'techrappy_geo_nonce\'\] ?? wp_create_nonce(
GeoAjaxHandler::NONCE_ACTION ); \$allowed_radii =
VillesVoisinesClient::ALLOWED_RADII; ob_start(); include
TECHRAPPY_SEO_PATH . \'admin/partials/geo-keyword-preview.php\'; return
ob_get_clean(); } } \`\`\` \-\-- \## 7. Vue admin ---
\`geo-keyword-preview.php\` \`\`\`php \<?php /\*\* \* Partial : Geo
Keyword Preview \* \* Variables attendues : \* \$nonce_value string
Nonce techrappy_geo_nonce \* \$allowed_radii array \[10, 20, 30\] \* \*
\@package TechrappySEO\\Admin \*/ defined( \'ABSPATH\' ) \|\| exit; ?\>
\<div class=\"techrappy-geo-wrap\" id=\"techrappy-geo-wrap\"\> \<!\--
================================================================ HEADER
================================================================ \--\>
\<div class=\"techrappy-geo-header\"\> \<h2
class=\"techrappy-geo-title\"\> 🗺️ \<?php esc_html_e( \'Villes &
Mots-clés locaux\', \'techrappy-seo\' ); ?\> \</h2\> \<p
class=\"techrappy-geo-subtitle\"\> \<?php esc_html_e( \'Générez des
mots-clés SEO locaux à partir d\\\'un code postal, d\\\'un rayon
géographique et d\\\'une profession.\', \'techrappy-seo\' ); ?\> \</p\>
\</div\> \<!\--
================================================================
FORMULAIRE
================================================================ \--\>
\<div class=\"techrappy-geo-card\" id=\"techrappy-geo-form-card\"\> \<h3
class=\"techrappy-geo-card-title\"\> ⚙️ \<?php esc_html_e(
\'Paramètres\', \'techrappy-seo\' ); ?\> \</h3\> \<div
class=\"techrappy-geo-form\"\> \<div class=\"techrappy-geo-form-row\"\>
\<!\-- Code postal \--\> \<div class=\"techrappy-geo-field\"\> \<label
for=\"techrappy-geo-cp\"\> \<?php esc_html_e( \'Code postal de
référence\', \'techrappy-seo\' ); ?\> \<span
class=\"techrappy-required\"\>\*\</span\> \</label\> \<div
class=\"techrappy-geo-cp-wrap\"\> \<input type=\"text\"
id=\"techrappy-geo-cp\" name=\"code_postal\"
class=\"techrappy-geo-input\" placeholder=\"31700\" maxlength=\"5\"
pattern=\"\\d{5}\" inputmode=\"numeric\" autocomplete=\"postal-code\"
/\> \<span id=\"techrappy-geo-commune-badge\"
class=\"techrappy-commune-badge\" style=\"display:none;\" \>\</span\>
\</div\> \<p class=\"description\"\> \<?php esc_html_e( \'5 chiffres. La
commune sera résolue automatiquement via l\\\'API BAN.\',
\'techrappy-seo\' ); ?\> \</p\> \</div\> \<!\-- Rayon \--\> \<div
class=\"techrappy-geo-field techrappy-geo-field\--sm\"\> \<label
for=\"techrappy-geo-rayon\"\> \<?php esc_html_e( \'Rayon\',
\'techrappy-seo\' ); ?\> \</label\> \<select id=\"techrappy-geo-rayon\"
name=\"rayon\" class=\"techrappy-geo-select\"\> \<?php foreach (
\$allowed_radii as \$r ) : ?\> \<option value=\"\<?php echo esc_attr(
\$r ); ?\>\"\> \<?php printf( esc_html\_\_( \'%d km\', \'techrappy-seo\'
), \$r ); ?\> \</option\> \<?php endforeach; ?\> \</select\> \</div\>
\</div\> \<!\-- Profession \--\> \<div class=\"techrappy-geo-field\"\>
\<label for=\"techrappy-geo-profession\"\> \<?php esc_html_e(
\'Profession\', \'techrappy-seo\' ); ?\> \<span
class=\"techrappy-required\"\>\*\</span\> \</label\> \<input
type=\"text\" id=\"techrappy-geo-profession\" name=\"profession\"
class=\"techrappy-geo-input\" placeholder=\"\<?php esc_attr_e(
\'ostéopathe\', \'techrappy-seo\' ); ?\>\" maxlength=\"100\" /\> \<p
class=\"description\"\> \<?php esc_html_e( \'Ex : ostéopathe,
kinésithérapeute, dentiste...\', \'techrappy-seo\' ); ?\> \</p\>
\</div\> \<!\-- Variantes (optionnel) \--\> \<div
class=\"techrappy-geo-field\"\> \<label for=\"techrappy-geo-variants\"\>
\<?php esc_html_e( \'Variantes de profession\', \'techrappy-seo\' ); ?\>
\<span class=\"techrappy-optional\"\>\<?php esc_html_e( \'(optionnel)\',
\'techrappy-seo\' ); ?\>\</span\> \</label\> \<input type=\"text\"
id=\"techrappy-geo-variants\" name=\"variants\"
class=\"techrappy-geo-input\" placeholder=\"\<?php esc_attr_e(
\'osteopathe, cabinet ostéopathie (séparés par des virgules)\',
\'techrappy-seo\' ); ?\>\" maxlength=\"300\" /\> \<p
class=\"description\"\> \<?php esc_html_e( \'Variantes séparées par des
virgules. Génèrent des keywords supplémentaires.\', \'techrappy-seo\' );
?\> \</p\> \</div\> \<!\-- Actions \--\> \<div
class=\"techrappy-geo-actions\"\> \<button type=\"button\"
id=\"techrappy-geo-preview-btn\" class=\"button button-primary\" \> 🔍
\<?php esc_html_e( \'Prévisualiser\', \'techrappy-seo\' ); ?\>
\</button\> \<button type=\"button\" id=\"techrappy-geo-reset-btn\"
class=\"button button-secondary\" \> ↺ \<?php esc_html_e(
\'Réinitialiser\', \'techrappy-seo\' ); ?\> \</button\> \<button
type=\"button\" id=\"techrappy-geo-flush-btn\" class=\"button
button-link\" title=\"\<?php esc_attr_e( \'Vider le cache des données
géographiques\', \'techrappy-seo\' ); ?\>\" \> 🗑️ \<?php esc_html_e(
\'Vider le cache\', \'techrappy-seo\' ); ?\> \</button\> \</div\> \<!\--
Erreurs formulaire \--\> \<div id=\"techrappy-geo-errors\"
class=\"techrappy-geo-notice techrappy-geo-notice\--error\"
style=\"display:none;\" \>\</div\> \</div\>\<!\-- /.techrappy-geo-form
\--\> \</div\>\<!\-- /#techrappy-geo-form-card \--\> \<!\--
================================================================ LOADER
================================================================ \--\>
\<div id=\"techrappy-geo-loader\" class=\"techrappy-geo-loader\"
style=\"display:none;\"\> \<span class=\"spinner is-active\"\>\</span\>
\<span\>\<?php esc_html_e( \'Recherche des villes et génération des
mots-clés...\', \'techrappy-seo\' ); ?\>\</span\> \</div\> \<!\--
================================================================
RÉSULTATS
================================================================ \--\>
\<div id=\"techrappy-geo-results\" style=\"display:none;\"\> \<!\--
Stats rapides \--\> \<div class=\"techrappy-geo-stats\"
id=\"techrappy-geo-stats\"\> \<div class=\"techrappy-geo-stat\"\> \<span
class=\"techrappy-geo-stat-value\"
id=\"techrappy-stat-villes\"\>0\</span\> \<span
class=\"techrappy-geo-stat-label\"\> \<?php esc_html_e( \'Villes
trouvées\', \'techrappy-seo\' ); ?\> \</span\> \</div\> \<div
class=\"techrappy-geo-stat\"\> \<span class=\"techrappy-geo-stat-value\"
id=\"techrappy-stat-keywords\"\>0\</span\> \<span
class=\"techrappy-geo-stat-label\"\> \<?php esc_html_e( \'Mots-clés
générés\', \'techrappy-seo\' ); ?\> \</span\> \</div\> \<div
class=\"techrappy-geo-stat\" id=\"techrappy-geo-cache-stat\"
style=\"display:none;\"\> \<span
class=\"techrappy-geo-stat-value\"\>💾\</span\> \<span
class=\"techrappy-geo-stat-label\"\> \<?php esc_html_e( \'Données en
cache\', \'techrappy-seo\' ); ?\> \</span\> \</div\> \</div\> \<!\--
Onglets résultats \--\> \<div class=\"techrappy-geo-tabs\"\> \<button
type=\"button\" class=\"techrappy-geo-tab techrappy-geo-tab\--active\"
data-tab=\"villes\" \> 📍 \<?php esc_html_e( \'Villes\',
\'techrappy-seo\' ); ?\> \<span class=\"techrappy-geo-tab-count\"
id=\"techrappy-tab-count-villes\"\>0\</span\> \</button\> \<button
type=\"button\" class=\"techrappy-geo-tab\" data-tab=\"keywords\" \> 🔑
\<?php esc_html_e( \'Mots-clés\', \'techrappy-seo\' ); ?\> \<span
class=\"techrappy-geo-tab-count\"
id=\"techrappy-tab-count-keywords\"\>0\</span\> \</button\> \</div\>
\<!\-- Panel Villes \--\> \<div class=\"techrappy-geo-panel
techrappy-geo-panel\--active\" id=\"techrappy-panel-villes\" \> \<div
class=\"techrappy-geo-panel-actions\"\> \<input type=\"text\"
id=\"techrappy-villes-filter\" placeholder=\"\<?php esc_attr_e(
\'Filtrer les villes...\', \'techrappy-seo\' ); ?\>\"
class=\"techrappy-geo-filter-input\" /\> \</div\> \<div
id=\"techrappy-villes-list\" class=\"techrappy-villes-grid\"\> \<!\--
Rempli via JS \--\> \</div\> \</div\> \<!\-- Panel Keywords \--\> \<div
class=\"techrappy-geo-panel\" id=\"techrappy-panel-keywords\" \> \<div
class=\"techrappy-geo-panel-actions\"\> \<input type=\"text\"
id=\"techrappy-keywords-filter\" placeholder=\"\<?php esc_attr_e(
\'Filtrer les mots-clés...\', \'techrappy-seo\' ); ?\>\"
class=\"techrappy-geo-filter-input\" /\> \<button type=\"button\"
id=\"techrappy-copy-keywords-btn\" class=\"button button-secondary\" \>
📋 \<?php esc_html_e( \'Copier tous les keywords\', \'techrappy-seo\' );
?\> \</button\> \<button type=\"button\" id=\"techrappy-export-csv-btn\"
class=\"button button-secondary\" \> ⬇️ \<?php esc_html_e( \'Exporter
CSV\', \'techrappy-seo\' ); ?\> \</button\> \</div\> \<div
id=\"techrappy-keywords-list\"\> \<!\-- Rempli via JS \--\> \</div\>
\</div\> \<!\-- Warnings \--\> \<div id=\"techrappy-geo-warnings\"
class=\"techrappy-geo-notice techrappy-geo-notice\--warning\"
style=\"display:none;\" \>\</div\> \</div\>\<!\--
/#techrappy-geo-results \--\> \<!\-- Nonce caché \--\> \<input
type=\"hidden\" id=\"techrappy-geo-nonce\" value=\"\<?php echo esc_attr(
\$nonce_value ); ?\>\" /\> \</div\>\<!\-- /#techrappy-geo-wrap \--\>
\`\`\` \-\-- \## 8. JavaScript --- \`geo-admin.js\` \`\`\`javascript
/\*\* \* geo-admin.js \* \* Gestion de l\'UI du module Geo : \* -
Résolution CP → commune en temps réel (debounce) \* - Preview villes +
keywords via AJAX \* - Filtrage en temps réel \* - Copie clipboard +
export CSV \* - Vider le cache \* \* \@package TechrappySEO \*/ /\*
global jQuery, techrappyGeoData \*/ ( function ( \$ ) { \'use strict\';
//
=========================================================================
// Config //
=========================================================================
const CFG = { ajaxUrl : techrappyGeoData.ajaxUrl, nonce :
techrappyGeoData.nonce, actions : { preview : \'techrappy_geo_preview\',
resolve : \'techrappy_geo_resolve\', flushCache :
\'techrappy_geo_flush_cache\', }, i18n : techrappyGeoData.i18n \|\| {},
debounceDelay : 600, // ms }; //
=========================================================================
// DOM //
=========================================================================
const SEL = { wrap : \'#techrappy-geo-wrap\', cpInput :
\'#techrappy-geo-cp\', communeBadge : \'#techrappy-geo-commune-badge\',
rayonSelect : \'#techrappy-geo-rayon\', professionInput:
\'#techrappy-geo-profession\', variantsInput :
\'#techrappy-geo-variants\', previewBtn :
\'#techrappy-geo-preview-btn\', resetBtn : \'#techrappy-geo-reset-btn\',
flushBtn : \'#techrappy-geo-flush-btn\', errors :
\'#techrappy-geo-errors\', loader : \'#techrappy-geo-loader\', results :
\'#techrappy-geo-results\', statVilles : \'#techrappy-stat-villes\',
statKeywords : \'#techrappy-stat-keywords\', cacheStat :
\'#techrappy-geo-cache-stat\', tabs : \'.techrappy-geo-tab\',
panelVilles : \'#techrappy-panel-villes\', panelKeywords :
\'#techrappy-panel-keywords\', villesList : \'#techrappy-villes-list\',
keywordsList : \'#techrappy-keywords-list\', villesFilter :
\'#techrappy-villes-filter\', keywordsFilter :
\'#techrappy-keywords-filter\', copyBtn :
\'#techrappy-copy-keywords-btn\', exportCsvBtn :
\'#techrappy-export-csv-btn\', warnings : \'#techrappy-geo-warnings\',
nonce : \'#techrappy-geo-nonce\', tabCountVilles :
\'#techrappy-tab-count-villes\', tabCountKeywords:
\'#techrappy-tab-count-keywords\', }; //
=========================================================================
// État //
=========================================================================
const state = { villes : \[\], keywords : \[\], loading : false, }; let
resolveTimer = null; let filterVTimer = null; let filterKTimer = null;
//
=========================================================================
// Init //
=========================================================================
function init() { bindEvents(); } //
=========================================================================
// Events //
=========================================================================
function bindEvents() { // Résolution CP en temps réel (debounce) \$(
document ).on( \'input\', SEL.cpInput, function () { clearTimeout(
resolveTimer ); const cp = \$( this ).val().trim(); hideCommuneBadge();
if ( cp.length === 5 && /\^\\d{5}\$/.test( cp ) ) { resolveTimer =
setTimeout( () =\> resolveCP( cp ), CFG.debounceDelay ); } } ); //
Bouton Prévisualiser \$( document ).on( \'click\', SEL.previewBtn,
onPreviewClick ); // Bouton Réinitialiser \$( document ).on( \'click\',
SEL.resetBtn, onResetClick ); // Bouton Vider cache \$( document ).on(
\'click\', SEL.flushBtn, onFlushCacheClick ); // Onglets \$( document
).on( \'click\', SEL.tabs, onTabClick ); // Filtres villes \$( document
).on( \'input\', SEL.villesFilter, function () { clearTimeout(
filterVTimer ); const q = \$( this ).val().toLowerCase(); filterVTimer =
setTimeout( () =\> filterVilles( q ), 200 ); } ); // Filtres keywords
\$( document ).on( \'input\', SEL.keywordsFilter, function () {
clearTimeout( filterKTimer ); const q = \$( this ).val().toLowerCase();
filterKTimer = setTimeout( () =\> filterKeywords( q ), 200 ); } ); //
Copier keywords \$( document ).on( \'click\', SEL.copyBtn,
onCopyKeywords ); // Exporter CSV \$( document ).on( \'click\',
SEL.exportCsvBtn, onExportCsv ); } //
=========================================================================
// Résolution CP → commune (debounce) //
=========================================================================
function resolveCP( cp ) { \$.ajax( { url : CFG.ajaxUrl, type :
\'POST\', data : { action : CFG.actions.resolve, nonce : getNonce(),
code_postal : cp, }, success : function ( res ) { if ( res.success &&
res.data.commune ) { showCommuneBadge( res.data.commune.nom_commune ); }
}, error : function () { // Silencieux : la résolution BAN est un
confort, pas bloquant }, } ); } function showCommuneBadge( nom ) { \$(
SEL.communeBadge ).text( \'📍 \' + nom ).show(); } function
hideCommuneBadge() { \$( SEL.communeBadge ).hide().text( \'\' ); } //
=========================================================================
// Preview //
=========================================================================
function onPreviewClick() { if ( state.loading ) return; const cp = \$(
SEL.cpInput ).val().trim(); const rayon = parseInt( \$( SEL.rayonSelect
).val(), 10 ); const profession = \$( SEL.professionInput
).val().trim(); const variantsRaw= \$( SEL.variantsInput ).val().trim();
// Validation client hideErrors(); const clientErrors = \[\]; if ( ! cp
\|\| ! /\^\\d{5}\$/.test( cp ) ) { clientErrors.push( \'Code postal
invalide (5 chiffres requis).\' ); } if ( ! profession ) {
clientErrors.push( \'Profession requise.\' ); } if ( clientErrors.length
\> 0 ) { showErrors( clientErrors ); return; } // Parse les variantes
const variants = variantsRaw ? variantsRaw.split( \',\' ).map( v =\>
v.trim() ).filter( Boolean ) : \[\]; setLoading( true ); \$.ajax( { url
: CFG.ajaxUrl, type : \'POST,\', type : \'POST\', data : { action :
CFG.actions.preview, nonce : getNonce(), code_postal : cp, rayon :
rayon, profession : profession, variants : JSON.stringify( variants ),
}, success : function ( res ) { setLoading( false ); if ( ! res.success
) { const errors = res.data?.errors \|\| \[ res.data?.message \|\|
CFG.i18n.errorGeneric \]; showErrors( errors ); return; } state.villes =
res.data.villes \|\| \[\]; state.keywords = res.data.keywords \|\| \[\];
renderResults( res.data ); }, error : function ( xhr, status, error ) {
setLoading( false ); showErrors( \[ CFG.i18n.errorGeneric \] );
console.error( \'\[TechrappyGeo\] preview error:\', error,
xhr.responseText ); }, } ); } //
=========================================================================
// Rendu résultats //
=========================================================================
function renderResults \-\-- \## 8. JavaScript --- \`geo-admin.js\`
(suite et fin) \`\`\`javascript //
=========================================================================
// Rendu résultats //
=========================================================================
function renderResults( data ) { // Stats \$( SEL.statVilles ).text(
data.total_villes \|\| 0 ); \$( SEL.statKeywords ).text(
data.total_keywords \|\| 0 ); \$( SEL.tabCountVilles ).text(
data.total_villes \|\| 0 ); \$( SEL.tabCountKeywords ).text(
data.total_keywords \|\| 0 ); // Cache indicator if ( data.from_cache )
{ \$( SEL.cacheStat ).show(); } else { \$( SEL.cacheStat ).hide(); } //
Warnings if ( data.warnings && data.warnings.length \> 0 ) { \$(
SEL.warnings ) .html( \'⚠️ \' + data.warnings.map( escHtml ).join(
\'\<br/\>\' ) ) .show(); } else { \$( SEL.warnings ).hide(); } // Rendu
des panels renderVilles( state.villes ); renderKeywords( state.keywords
); // Affiche la zone résultats \$( SEL.results ).slideDown( 200 ); //
Scroll vers les résultats \$( \'html, body\' ).animate( { scrollTop: \$(
SEL.results ).offset().top - 40, }, 400 ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Rend la grille des villes. \* \* \@param {Array} villes \*/
function renderVilles( villes ) { const \$list = \$( SEL.villesList );
\$list.empty(); if ( ! villes \|\| villes.length === 0 ) { \$list.html(
\'\<p class=\"techrappy-geo-empty\"\>\' + escHtml( CFG.i18n.noResults
) + \'\</p\>\' ); return; } villes.forEach( function ( ville ) { const
distLabel = ville.distance_km !== null ? \`\<span
class=\"techrappy-ville-dist\"\>\${ escHtml( String( ville.distance_km )
) } km\</span\>\` : \'\'; const cpLabel = ville.code_postal ? \`\<span
class=\"techrappy-ville-cp\"\>\${ escHtml( ville.code_postal )
}\</span\>\` : \'\'; const card = \` \<div
class=\"techrappy-ville-card\" data-nom=\"\${ escHtml(
ville.nom_commune.toLowerCase() ) }\"\> \<div
class=\"techrappy-ville-nom\"\> 📍 \${ escHtml( ville.nom_commune ) }
\</div\> \<div class=\"techrappy-ville-meta\"\> \${ cpLabel } \${
distLabel } \</div\> \</div\> \`; \$list.append( card ); } ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Rend le tableau des mots-clés. \* \* \@param {Array} keywords
\*/ function renderKeywords( keywords ) { const \$list = \$(
SEL.keywordsList ); \$list.empty(); if ( ! keywords \|\| keywords.length
=== 0 ) { \$list.html( \'\<p class=\"techrappy-geo-empty\"\>\' +
escHtml( CFG.i18n.noResults ) + \'\</p\>\' ); return; } // Table const
\$table = \$( \` \<table class=\"techrappy-kw-table wp-list-table
widefat fixed striped\"\> \<thead\> \<tr\> \<th
class=\"col-kw\"\>Mot-clé\</th\> \<th class=\"col-ville\"\>Ville\</th\>
\<th class=\"col-cp\"\>Code postal\</th\> \<th
class=\"col-slug\"\>Slug\</th\> \<th class=\"col-dist\"\>Distance\</th\>
\</tr\> \</thead\> \<tbody class=\"techrappy-kw-tbody\"\>\</tbody\>
\</table\> \` ); const \$tbody = \$table.find( \'.techrappy-kw-tbody\'
); keywords.forEach( function ( kw, idx ) { const distCell =
kw.distance_km !== null ? escHtml( String( kw.distance_km ) ) + \' km\'
: \'---\'; \$tbody.append( \` \<tr data-kw=\"\${ escHtml(
kw.keyword.toLowerCase() ) }\" data-idx=\"\${ idx }\"\> \<td\> \<strong
class=\"techrappy-kw-text\"\> \${ escHtml( kw.keyword ) } \</strong\>
\</td\> \<td\>\${ escHtml( kw.ville ) }\</td\> \<td
class=\"techrappy-muted\"\>\${ escHtml( kw.code_postal ) }\</td\> \<td\>
\<code class=\"techrappy-kw-slug\"\> \${ escHtml( kw.slug ) } \</code\>
\</td\> \<td class=\"techrappy-muted\"\>\${ distCell }\</td\> \</tr\> \`
); } ); \$list.append( \$table ); } //
=========================================================================
// Filtrage en temps réel //
=========================================================================
/\*\* \* Filtre les cartes de villes. \* \* \@param {string} query \*/
function filterVilles( query ) { const \$cards = \$( SEL.villesList
).find( \'.techrappy-ville-card\' ); if ( ! query ) { \$cards.show();
return; } \$cards.each( function () { const nom = \$( this ).data(
\'nom\' ) \|\| \'\'; \$( this ).toggle( nom.includes( query ) ); } ); }
/\*\* \* Filtre les lignes du tableau de keywords. \* \* \@param
{string} query \*/ function filterKeywords( query ) { const \$rows = \$(
SEL.keywordsList ).find( \'tbody tr\' ); if ( ! query ) { \$rows.show();
return; } \$rows.each( function () { const kw = \$( this ).data( \'kw\'
) \|\| \'\'; \$( this ).toggle( kw.includes( query ) ); } ); } //
=========================================================================
// Onglets //
=========================================================================
function onTabClick() { const tab = \$( this ).data( \'tab\' ); //
Active l\'onglet cliqué \$( SEL.tabs ).removeClass(
\'techrappy-geo-tab\--active\' ); \$( this ).addClass(
\'techrappy-geo-tab\--active\' ); // Affiche le bon panel \$(
\'#techrappy-panel-villes\' ).hide(); \$( \'#techrappy-panel-keywords\'
).hide(); \$( \`#techrappy-panel-\${ tab }\` ).show(); } //
=========================================================================
// Copie clipboard //
=========================================================================
function onCopyKeywords() { if ( state.keywords.length === 0 ) return;
// Récupère uniquement les keywords visibles (filtrés) const
visibleKeywords = \[\]; \$( SEL.keywordsList ).find( \'tbody
tr:visible\' ).each( function () { const idx = parseInt( \$( this
).data( \'idx\' ), 10 ); if ( ! isNaN( idx ) && state.keywords\[ idx \]
) { visibleKeywords.push( state.keywords\[ idx \].keyword ); } } );
const text = visibleKeywords.join( \'\\n\' ); if ( ! text ) return; //
Utilise l\'API Clipboard moderne avec fallback if ( navigator.clipboard
&& navigator.clipboard.writeText ) { navigator.clipboard.writeText( text
).then( function () { showCopyFeedback(); } ).catch( function () {
fallbackCopy( text ); } ); } else { fallbackCopy( text ); } } /\*\* \*
Fallback copie pour navigateurs sans API Clipboard. \* \* \@param
{string} text \*/ function fallbackCopy( text ) { const \$ta = \$(
\'\<textarea/\>\' ) .val( text ) .css( { position: \'fixed\', top:
\'-9999px\', left: \'-9999px\' } ) .appendTo( \'body\' );
\$ta\[0\].select(); try { document.execCommand( \'copy\' );
showCopyFeedback(); } catch ( e ) { console.error( \'\[TechrappyGeo\]
Copie impossible :\', e ); } \$ta.remove(); } function
showCopyFeedback() { const \$btn = \$( SEL.copyBtn ); const original =
\$btn.text(); \$btn.text( \'✅ \' + ( CFG.i18n.copySuccess \|\| \'Copié
!\' ) ); setTimeout( function () { \$btn.text( original ); }, 2000 ); }
//
=========================================================================
// Export CSV //
=========================================================================
function onExportCsv() { if ( state.keywords.length === 0 ) return; //
Récupère les keywords visibles (filtrés) const rows = \[\]; rows.push(
\[ \'keyword\', \'ville\', \'code_postal\', \'slug\', \'distance_km\' \]
); \$( SEL.keywordsList ).find( \'tbody tr:visible\' ).each( function ()
{ const idx = parseInt( \$( this ).data( \'idx\' ), 10 ); if ( ! isNaN(
idx ) && state.keywords\[ idx \] ) { const kw = state.keywords\[ idx \];
rows.push( \[ kw.keyword \|\| \'\', kw.ville \|\| \'\', kw.code_postal
\|\| \'\', kw.slug \|\| \'\', kw.distance_km !== null ? String(
kw.distance_km ) : \'\', \] ); } } ); const csv = rows.map( r =\> r.map(
csvEscape ).join( \',\' ) ).join( \'\\n\' ); const blob = new Blob( \[
\'\\uFEFF\' + csv \], { type: \'text/csv;charset=utf-8;\' } ); const url
= URL.createObjectURL( blob ); const ts = new
Date().toISOString().slice( 0, 10 ); const filename =
\`techrappy-keywords-\${ ts }.csv\`; const \$a = \$( \'\<a/\>\' ) .attr(
{ href: url, download: filename } ) .appendTo( \'body\' );
\$a\[0\].click(); \$a.remove(); setTimeout( () =\> URL.revokeObjectURL(
url ), 5000 ); } /\*\* \* Échappe une valeur pour CSV (RFC 4180). \* \*
\@param {string} val \* \@returns {string} \*/ function csvEscape( val )
{ const str = String( val ); if ( str.includes( \',\' ) \|\|
str.includes( \'\"\' ) \|\| str.includes( \'\\n\' ) ) { return \'\"\' +
str.replace( /\"/g, \'\"\"\' ) + \'\"\'; } return str; } //
=========================================================================
// Reset //
=========================================================================
function onResetClick() { \$( SEL.cpInput ).val( \'\' ); \$(
SEL.professionInput ).val( \'\' ); \$( SEL.variantsInput ).val( \'\' );
\$( SEL.rayonSelect ).val( \'10\' ); hideCommuneBadge(); hideErrors();
\$( SEL.results ).hide(); \$( SEL.villesFilter ).val( \'\' ); \$(
SEL.keywordsFilter ).val( \'\' ); \$( SEL.warnings ).hide();
state.villes = \[\]; state.keywords = \[\]; } //
=========================================================================
// Flush cache //
=========================================================================
function onFlushCacheClick() { const \$btn = \$( SEL.flushBtn );
\$btn.prop( \'disabled\', true ); \$.ajax( { url : CFG.ajaxUrl, type :
\'POST\', data : { action : CFG.actions.flushCache, nonce : getNonce(),
}, success : function ( res ) { \$btn.prop( \'disabled\', false ); if (
res.success ) { // Feedback inline discret const \$msg = \$( \'\<span
class=\"techrappy-flush-ok\"\> ✅ \' + escHtml( res.data.message ) +
\'\</span\>\' ); \$btn.after( \$msg ); setTimeout( () =\> \$msg.fadeOut(
400, () =\> \$msg.remove() ), 3000 ); } }, error : function () {
\$btn.prop( \'disabled\', false ); }, } ); } //
=========================================================================
// UI Helpers //
=========================================================================
function setLoading( active ) { state.loading = active; \$( SEL.loader
).toggle( active ); \$( SEL.previewBtn ).prop( \'disabled\', active );
if ( active ) { \$( SEL.results ).hide(); } } function showErrors(
errors ) { \$( SEL.errors ) .html( errors.map( e =\> \'❌ \' + escHtml(
e ) ).join( \'\<br/\>\' ) ) .slideDown( 200 ); } function hideErrors() {
\$( SEL.errors ).hide().empty(); } function getNonce() { return \$(
SEL.nonce ).val() \|\| CFG.nonce; } /\*\* \* Échappe les caractères
HTML. \* \* \@param {string} str \* \@returns {string} \*/ function
escHtml( str ) { if ( str == null ) return \'\'; return String( str )
.replace( /&/g, \'&amp;\' ) .replace( /\</g, \'&lt;\' ) .replace( /\>/g,
\'&gt;\' ) .replace( /\"/g, \'&quot;\') .replace( /\'/g, \'&#039;\'); }
//
=========================================================================
// Bootstrap //
=========================================================================
\$( document ).ready( init ); } ( jQuery ) ); \`\`\` \-\-- \## 9. CSS
--- \`geo-admin.css\` \`\`\`css /\*\* \* geo-admin.css \* Styles du
module Geo --- Villes & Keywords --- Techrappy SEO Admin \* \* \@package
TechrappySEO \*/ /\*
============================================================ Variables
============================================================ \*/ :root {
\--tg-primary : #2271b1; \--tg-primary-dark : #135e96; \--tg-success :
#00a32a; \--tg-success-bg : #edfaef; \--tg-warning : #dba617;
\--tg-warning-bg : #fef8ee; \--tg-error : #d63638; \--tg-error-bg :
#fef0f0; \--tg-border : #c3c4c7; \--tg-border-light : #e8e8e8;
\--tg-bg-light : #f6f7f7; \--tg-text : #1d2327; \--tg-text-muted :
#646970; \--tg-radius : 6px; \--tg-radius-sm : 3px; \--tg-shadow : 0 1px
4px rgba(0,0,0,.1); \--tg-font-mono : \'SFMono-Regular\', Consolas,
\'Liberation Mono\', monospace; \--tg-transition : .18s ease; } /\*
============================================================ Layout
============================================================ \*/
.techrappy-geo-wrap { max-width : 1100px; margin : 20px 0; } /\*
============================================================ Header
============================================================ \*/
.techrappy-geo-header { margin-bottom : 24px; } .techrappy-geo-title {
font-size : 1.4rem; font-weight : 700; color : var(\--tg-text); margin :
0 0 6px; } .techrappy-geo-subtitle { color : var(\--tg-text-muted);
font-size : .95rem; margin : 0; } /\*
============================================================ Cards
============================================================ \*/
.techrappy-geo-card { background : #fff; border : 1px solid
var(\--tg-border); border-radius : var(\--tg-radius); padding : 24px;
margin-bottom : 20px; box-shadow : var(\--tg-shadow); }
.techrappy-geo-card-title { font-size : 1rem; font-weight : 600; color :
var(\--tg-text); margin : 0 0 20px; padding-bottom: 12px; border-bottom
: 1px solid var(\--tg-border-light); } /\*
============================================================ Formulaire
============================================================ \*/
.techrappy-geo-form { display : flex; flex-direction : column; gap :
16px; } .techrappy-geo-form-row { display : flex; gap : 16px; flex-wrap
: wrap; align-items : flex-start; } .techrappy-geo-field { display :
flex; flex-direction : column; gap : 6px; flex : 1; min-width : 200px; }
.techrappy-geo-field\--sm { flex : none; min-width : 120px; max-width :
150px; } .techrappy-geo-field label { font-weight : 500; font-size :
.9rem; color : var(\--tg-text); } .techrappy-required { color :
var(\--tg-error); margin-left : 2px; } .techrappy-optional { color :
var(\--tg-text-muted); font-weight : 400; font-size : .85rem;
margin-left : 4px; } .techrappy-geo-input { padding : 8px 10px; border :
1px solid var(\--tg-border); border-radius : var(\--tg-radius-sm);
font-size : .9rem; color : var(\--tg-text); transition : border-color
var(\--tg-transition); width : 100%; box-sizing : border-box; }
.techrappy-geo-input:focus { border-color : var(\--tg-primary); outline
: 2px solid rgba(34,113,177,.2); outline-offset : 1px; }
.techrappy-geo-select { padding : 8px 10px; border : 1px solid
var(\--tg-border); border-radius : var(\--tg-radius-sm); font-size :
.9rem; background : #fff; color : var(\--tg-text); width : 100%; cursor
: pointer; transition : border-color var(\--tg-transition); }
.techrappy-geo-select:focus { border-color : var(\--tg-primary); outline
: 2px solid rgba(34,113,177,.2); } /\* Badge commune résolue \*/
.techrappy-geo-cp-wrap { display : flex; align-items : center; gap :
10px; flex-wrap : wrap; } .techrappy-commune-badge { display :
inline-block; background : var(\--tg-success-bg); color : #1a5e28;
border : 1px solid #b3e6bf; border-radius : 20px; padding : 3px 10px;
font-size : .82rem; font-weight : 600; white-space : nowrap; animation :
tg-fade-in .2s ease; } /\* Actions formulaire \*/ .techrappy-geo-actions
{ display : flex; gap : 10px; align-items : center; flex-wrap : wrap;
padding-top : 4px; } .techrappy-flush-ok { font-size : .85rem; color :
var(\--tg-success); font-weight: 500; } /\*
============================================================ Notices
============================================================ \*/
.techrappy-geo-notice { padding : 12px 16px; border-left : 4px solid
transparent; border-radius : var(\--tg-radius-sm); font-size : .9rem;
line-height : 1.5; } .techrappy-geo-notice\--error { background :
var(\--tg-error-bg); border-color : var(\--tg-error); color : #7b1315; }
.techrappy-geo-notice\--warning { background : var(\--tg-warning-bg);
border-color : var(\--tg-warning); color : #6b4e00; } /\*
============================================================ Loader
============================================================ \*/
.techrappy-geo-loader { display : flex; align-items : center; gap :
10px; padding : 16px 0; font-size : .9rem; color :
var(\--tg-text-muted); } .techrappy-geo-loader .spinner { float : none;
margin : 0; } /\*
============================================================ Stats
============================================================ \*/
.techrappy-geo-stats { display : flex; gap : 16px; flex-wrap : wrap;
margin-bottom : 20px; } .techrappy-geo-stat { flex : 1; min-width :
120px; background : var(\--tg-bg-light); border : 1px solid
var(\--tg-border-light); border-radius : var(\--tg-radius); padding :
14px; text-align : center; } .techrappy-geo-stat-value { display :
block; font-size : 2rem; font-weight : 700; color : var(\--tg-primary);
line-height : 1; } .techrappy-geo-stat-label { display : block;
font-size : .78rem; color : var(\--tg-text-muted); margin-top : 4px; }
/\* ============================================================ Onglets
résultats ============================================================
\*/ .techrappy-geo-tabs { display : flex; gap : 4px; border-bottom : 2px
solid var(\--tg-border-light); margin-bottom : 16px; }
.techrappy-geo-tab { background : none; border : none; border-bottom :
3px solid transparent; margin-bottom : -2px; padding : 10px 18px;
font-size : .9rem; font-weight : 500; color : var(\--tg-text-muted);
cursor : pointer; display : flex; align-items : center; gap : 6px;
transition : color var(\--tg-transition), border-color
var(\--tg-transition); } .techrappy-geo-tab:hover { color :
var(\--tg-primary); border-color : var(\--tg-border); }
.techrappy-geo-tab\--active { color : var(\--tg-primary); border-color :
var(\--tg-primary); font-weight : 600; } .techrappy-geo-tab-count {
background : var(\--tg-primary); color : #fff; border-radius : 20px;
font-size : .72rem; font-weight : 700; padding : 1px 7px; min-width :
20px; text-align : center; transition : background
var(\--tg-transition); }
.techrappy-geo-tab:not(.techrappy-geo-tab\--active)
.techrappy-geo-tab-count { background : var(\--tg-text-muted); } /\*
============================================================ Panels
============================================================ \*/
.techrappy-geo-panel { display : none; } .techrappy-geo-panel\--active {
display : block; } .techrappy-geo-panel-actions { display : flex; gap :
10px; flex-wrap : wrap; align-items : center; margin-bottom : 14px; }
.techrappy-geo-filter-input { flex : 1; min-width : 180px; max-width :
320px; padding : 7px 10px; border : 1px solid var(\--tg-border);
border-radius : var(\--tg-radius-sm); font-size : .88rem; transition :
border-color var(\--tg-transition); } .techrappy-geo-filter-input:focus
{ border-color : var(\--tg-primary); outline : 2px solid
rgba(34,113,177,.2); } /\*
============================================================ Grille des
villes ============================================================ \*/
.techrappy-villes-grid { display : grid; grid-template-columns : repeat(
auto-fill, minmax( 180px, 1fr ) ); gap : 10px; } .techrappy-ville-card {
background : #fff; border : 1px solid var(\--tg-border-light);
border-radius : var(\--tg-radius); padding : 10px 14px; transition :
border-color var(\--tg-transition), box-shadow var(\--tg-transition); }
.techrappy-ville-card:hover { border-color : var(\--tg-primary);
box-shadow : 0 2px 8px rgba(34,113,177,.12); } .techrappy-ville-nom {
font-weight : 600; font-size : .9rem; color : var(\--tg-text);
margin-bottom : 4px; } .techrappy-ville-meta { display : flex; gap :
8px; flex-wrap : wrap; align-items : center; } .techrappy-ville-cp {
background : var(\--tg-bg-light); border : 1px solid
var(\--tg-border-light); border-radius : 3px; padding : 1px 6px;
font-size : .78rem; color : var(\--tg-text-muted); font-family :
var(\--tg-font-mono); } .techrappy-ville-dist { font-size : .78rem;
color : var(\--tg-primary); font-weight: 500; } /\*
============================================================ Table
keywords ============================================================
\*/ .techrappy-kw-table { width : 100%; border-collapse : collapse;
font-size : .875rem; } .techrappy-kw-table th { background :
var(\--tg-bg-light); font-weight : 600; font-size : .78rem;
text-transform : uppercase; letter-spacing : .04em; color :
var(\--tg-text-muted); padding : 8px 12px; border-bottom : 2px solid
var(\--tg-border); white-space : nowrap; } .techrappy-kw-table td {
padding : 8px 12px; border-bottom : 1px solid var(\--tg-border-light);
vertical-align: middle; } .techrappy-kw-table .col-kw { width: 30%; }
.techrappy-kw-table .col-ville { width: 22%; } .techrappy-kw-table
.col-cp { width: 12%; } .techrappy-kw-table .col-slug { width: 26%; }
.techrappy-kw-table .col-dist { width: 10%; } .techrappy-kw-text { color
: var(\--tg-text); } .techrappy-kw-slug { font-family :
var(\--tg-font-mono); font-size : .78rem; background :
var(\--tg-bg-light); border : 1px solid var(\--tg-border-light);
border-radius : 3px; padding : 2px 6px; color : var(\--tg-text-muted);
word-break : break-all; } .techrappy-geo-empty { color :
var(\--tg-text-muted); font-style : italic; padding : 16px 0; }
.techrappy-muted { color : var(\--tg-text-muted); font-size : .85rem; }
/\* ============================================================
Animations ============================================================
\*/ \@keyframes tg-fade-in { from { opacity: 0; transform: translateY(
-3px ); } to { opacity: 1; transform: translateY( 0 ); } }
.techrappy-geo-card, .techrappy-geo-stats, .techrappy-ville-card {
animation : tg-fade-in .2s ease forwards; } /\*
============================================================ Responsive
============================================================ \*/ \@media
screen and (max-width: 782px) { .techrappy-geo-form-row { flex-direction
: column; } .techrappy-geo-field\--sm { max-width : 100%; }
.techrappy-villes-grid { grid-template-columns : repeat( auto-fill,
minmax( 140px, 1fr ) ); } .techrappy-geo-stats { gap : 8px; }
.techrappy-geo-stat { flex : none; width : calc( 50% - 4px ); min-width
: unset; } .techrappy-geo-actions { flex-direction : column; align-items
: stretch; } .techrappy-geo-actions .button { text-align : center; } /\*
Cache les colonnes secondaires sur mobile \*/ .techrappy-kw-table
.col-slug, .techrappy-kw-table .col-dist { display : none; }
.techrappy-kw-table .col-kw { width: 40%; } .techrappy-kw-table
.col-ville { width: 35%; } .techrappy-kw-table .col-cp { width: 25%; }
.techrappy-geo-panel-actions { flex-direction : column; align-items :
stretch; } .techrappy-geo-filter-input { max-width : 100%; } } \`\`\`
\-\-- \## 10. Intégration plugin principal \`\`\`php \<?php /\*\* \*
Intégration du module Geo dans le plugin principal. \* À ajouter dans
load_modules() du bootstrap existant. \* \* \@package TechrappySEO \*/
// ─── Dans load_modules()
───────────────────────────────────────────────────── private function
load_modules(): void { // \... modules existants validés \... // 🆕
Module Geo (Villes-Voisines + BAN + Keywords) \$geo_module = new
\\TechrappySEO\\Modules\\Geo\\GeoModule(); \$geo_module-\>register(); }
\`\`\` \-\-- \## 11. Exemples d\'utilisation PHP \`\`\`php \<?php /\*\*
\* Exemples d\'utilisation du module Geo. \* Prêts à coller dans
n\'importe quelle partie du plugin. \* \* \@package TechrappySEO \*/ use
TechrappySEO\\Modules\\Geo\\VillesVoisinesClient; use
TechrappySEO\\Modules\\Geo\\BanClient; use
TechrappySEO\\Modules\\Geo\\CityKeywordBuilder; use
TechrappySEO\\Modules\\Geo\\GeoCache; // ─── Exemple 1 : Villes voisines
seules ────────────────────────────────────── \$vv = new
VillesVoisinesClient(); \$result = \$vv-\>get_villes_proches( \'31700\',
20 ); if ( \$result\[\'success\'\] ) { foreach ( \$result\[\'villes\'\]
as \$ville ) { echo \$ville\[\'nom_commune\'\] . \' (\' .
\$ville\[\'code_postal\'\] . \')\'; if ( isset(
\$ville\[\'distance_km\'\] ) ) { echo \' --- \' .
\$ville\[\'distance_km\'\] . \' km\'; } echo \"\\n\"; } } else { // Pas
de fatal error : on log et on continue error_log( \'VV error: \' .
\$result\[\'error\'\] ); } // ─── Exemple 2 : Résolution BAN
────────────────────────────────────────────── \$ban = new BanClient();
\$result = \$ban-\>resolve_code_postal( \'31700\' ); if (
\$result\[\'success\'\] ) { \$commune = \$result\[\'commune\'\]; // \[
// \'nom_commune\' =\> \'Beauzelle\', // \'code_postal\' =\> \'31700\',
// \'code_insee\' =\> \'31056\', // \'lat\' =\> 43.6764, // \'lon\' =\>
1.3434, // \] } // ─── Exemple 3 : Keywords depuis code postal
(orchestration complète) ───────── \$builder = new CityKeywordBuilder(
\'ostéopathe\', \[ \'osteopathe\', \'cabinet ostéopathie\' \], //
Variantes \[ \'max_keywords\' =\> 200 \] ); \$result =
\$builder-\>build_from_code_postal( \'31700\', 20 ); if (
\$result\[\'success\'\] ) { // \$result\[\'keywords\'\] = \[ // \[
\'keyword\' =\> \'cabinet ostéopathie Beauzelle\', // \'slug\' =\>
\'cabinet-osteopathie-beauzelle\', // \'ville\' =\> \'Beauzelle\', //
\'code_postal\' =\> \'31700\', \... \], // \[ \'keyword\' =\> \'cabinet
ostéopathie Blagnac\', \... \], // \[ \'keyword\' =\> \'osteopathe
Beauzelle\', \... \], // \... // \] // Extraction des keywords bruts
pour le moteur SEO \$keyword_list = array_column(
\$result\[\'keywords\'\], \'keyword\' ); // Extraction des slugs pour
les URLs \$slug_list = array_column( \$result\[\'keywords\'\], \'slug\'
); // Log des warnings non-bloquants if ( ! empty(
\$result\[\'errors\'\] ) ) { foreach ( \$result\[\'errors\'\] as
\$warning ) { error_log( \'\[TechrappySEO\]\[Geo\] \' . \$warning ); } }
} // ─── Exemple 4 : Build depuis liste de villes existante
────────────────────── \$villes_custom = \[ \[ \'nom_commune\' =\>
\'Toulouse\', \'code_postal\' =\> \'31000\' \], \[ \'nom_commune\' =\>
\'Blagnac\', \'code_postal\' =\> \'31700\', \'distance_km\' =\> 8.5 \],
\[ \'nom_commune\' =\> \'Colomiers\', \'code_postal\' =\> \'31770\',
\'distance_km\' =\> 12.0 \], \]; \$builder = new CityKeywordBuilder(
\'kinésithérapeute\' ); \$result = \$builder-\>build( \$villes_custom );
// \$result\[\'keywords\'\] = \[ // \[ \'keyword\' =\>
\'kinésithérapeute Blagnac\', \'slug\' =\> \'kinesitherapeute-blagnac\',
\... \], // \[ \'keyword\' =\> \'kinésithérapeute Colomiers\', \... \],
// \[ \'keyword\' =\> \'kinésithérapeute Toulouse\', \... \], // \] //
─── Exemple 5 : Branchement avec le module Bulk
───────────────────────────── // Génère les keywords géo et les injecte
dans le flux de génération de masse. add_filter(
\'techrappy_seo_bulk_keywords_source\', function ( array \$keywords,
array \$context ): array { if ( empty( \$context\[\'code_postal\'\] )
\|\| empty( \$context\[\'profession\'\] ) ) { return \$keywords; }
\$builder = new CityKeywordBuilder( \$context\[\'profession\'\],
\$context\[\'variants\'\] ?? \[\] ); \$result =
\$builder-\>build_from_code_postal( \$context\[\'code_postal\'\],
\$context\[\'rayon\'\] ?? 20 ); if ( \$result\[\'success\'\] ) { //
Fusionne avec les keywords existants \$geo_keywords = array_column(
\$result\[\'keywords\'\], \'keyword\' ); \$keywords = array_values(
array_unique( array_merge( \$keywords, \$geo_keywords ) ) ); } return
\$keywords; }, 10, 2 ); // ─── Exemple 6 : Résolution en lot (batch BAN)
─────────────────────────────── \$ban = new BanClient(); \$cps = \[
\'31700\', \'31300\', \'31000\', \'33000\', \'69001\' \]; \$resolved =
\$ban-\>resolve_batch( \$cps ); foreach ( \$resolved as \$cp =\>
\$result ) { if ( \$result\[\'success\'\] ) { echo \$cp . \' → \' .
\$result\[\'commune\'\]\[\'nom_commune\'\] . \"\\n\"; } else { echo \$cp
. \' → ERREUR : \' . \$result\[\'error\'\] . \"\\n\"; } } // ─── Exemple
7 : Vider le cache programmatiquement ─────────────────────────── //
Vide tout le cache Geo \$deleted = GeoCache::flush_all(); error_log(
\"Cache Geo vidé : \$deleted entrées supprimées.\" ); // Vide uniquement
le cache pour un CP/rayon spécifique GeoCache::delete( GeoCache::vv_key(
\'31700\', 20 ) ); GeoCache::delete( GeoCache::ban_key( \'31700\' ) );
\`\`\` \-\-- \## Récapitulatif complet du module \`\`\`
┌──────────────────────────────────────────────────────────────────────┐
│ MODULE GEO --- VILLES-VOISINES + BAN + KEYWORDS │ │ LIVRAISON COMPLÈTE
│
├──────────────────────────────────────────────────────────────────────┤
│ │ │ SERVICES (modules/geo/) │ │ ├── GeoCache Transients WP (TTL 24h /
5min erreur) │ │ ├── VillesVoisinesClient API VV + parsing +
normalisation │ │ ├── BanClient API BAN GeoJSON + resolve batch │ │ ├──
CityKeywordBuilder Génération keywords + slug + variants │ │ ├──
GeoAjaxHandler 3 actions AJAX sécurisées │ │ └── GeoModule Bootstrap +
assets + tab admin │ │ │ │ UI (admin/partials/) │ │ └──
geo-keyword-preview.php Formulaire + 2 panels + filtres │ │ │ │ ASSETS │
│ ├── geo-admin.js Preview AJAX + filtres + CSV + copy │ │ └──
geo-admin.css Styles complets responsive │ │ │ │ FLUX PRINCIPAL │ │ 1.
Saisie CP → debounce 600ms → BAN résolution commune (badge) │ │ 2. Clic
Prévisualiser → AJAX techrappy_geo_preview │ │ a.
VillesVoisinesClient.get_villes_proches(cp, rayon) │ │ b.
BanClient.resolve_code_postal(cp) → ville origine │ │ c.
CityKeywordBuilder.build(villes) → keywords + slugs │ │ 3. Affichage :
grille villes \| tableau keywords │ │ 4. Filtrage temps réel \| Copie
clipboard \| Export CSV │ │ │ │ CACHE │ │ ├── VV : 24h par (CP + rayon)
│ │ ├── BAN : 24h par CP │ │ ├── Erreur VV : 5 min (récupération rapide)
│ │ └── Flush manuel via bouton UI ou GeoCache::flush_all() │ │ │ │
SÉCURITÉ │ │ ✅ wp_verify_nonce() sur toutes les actions AJAX │ │ ✅
current_user_can(\'manage_options\') │ │ ✅ sanitize_text_field() sur
tous les inputs │ │ ✅ absint() / rawurlencode() sur paramètres API
externes │ │ ✅ Pas de fatal error si API indisponible (error_log +
fallback) │ │ ✅ esc_html() sur toutes les sorties JS/PHP │ │ │ │
ROBUSTESSE │ │ ✅ Timeout HTTP : 10s (VV), 8s (BAN) │ │ ✅ Cache erreur
court (5min) évite de marteler l\'API │ │ ✅ Parsing multi-format
(champs API VV variables selon version) │ │ ✅ Fallback résolution BAN
non-bloquant │ │ ✅ Dédoublonnage insensible à la casse │ │ ✅ Limite
max 500 keywords configurable │ │ │ │ POINTS D\'INTÉGRATION │ │ Filtre :
techrappy_seo_bulk_keywords_source(\$keywords, \$context) │ │ → Permet
au module Bulk d\'injecter des keywords géo │
└──────────────────────────────────────────────────────────────────────┘
\`\`\`
