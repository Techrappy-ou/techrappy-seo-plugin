\# Techrappy SEO --- Module Génération de Masse + Action Scheduler \##
Architecture des fichiers \`\`\` techrappy-seo/ ├── modules/ │ └── bulk/
│ ├── BulkModule.php \# Point d\'entrée du module │ ├──
BulkController.php \# UI + handlers AJAX │ ├── BulkQueueService.php \#
Planification Action Scheduler │ ├── BulkBatchRepository.php \#
Persistance DB (table dédiée) │ ├── BulkJobProcessor.php \# Callback
Action Scheduler │ └── BulkBatchValidator.php \# Validation des entrées
├── admin/ │ └── partials/ │ └── bulk-generator.php \# Vue admin ├──
assets/ │ ├── js/ │ │ └── bulk-admin.js \# JS polling + UI │ └── css/ │
└── bulk-admin.css \# Styles └── migrations/ └── CreateBulkTables.php \#
Installation des tables DB \`\`\` \-\-- \## 1. Migration DB ---
\`CreateBulkTables.php\` \`\`\`php \<?php /\*\* \* CreateBulkTables \*
\* Crée les tables nécessaires au module de génération de masse. \* \*
Tables créées : \* {prefix}\_techrappy_bulk_batches : un enregistrement
par \"batch\" lancé \* {prefix}\_techrappy_bulk_jobs : un enregistrement
par keyword/job \* \* Appelée lors de l\'activation du plugin via
register_activation_hook. \* \* \@package TechrappySEO\\Migrations \*/
namespace TechrappySEO\\Migrations; defined( \'ABSPATH\' ) \|\| exit;
class CreateBulkTables { /\*\* \* Version du schéma --- incrémenter si
la structure change. \*/ const SCHEMA_VERSION = \'1.0\'; /\*\* \* Option
WP stockant la version installée. \*/ const SCHEMA_VERSION_OPTION =
\'techrappy_bulk_schema_version\'; /\*\* \* Lance la migration si
nécessaire. \* Idempotente : peut être appelée plusieurs fois sans effet
secondaire. \*/ public static function run(): void { \$installed =
get_option( self::SCHEMA_VERSION_OPTION, \'0\' ); if ( version_compare(
\$installed, self::SCHEMA_VERSION, \'\>=\' ) ) { return; // Déjà à jour
} self::create_batches_table(); self::create_jobs_table();
update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION ); }
/\*\* \* Supprime les tables (appelé lors de la désinstallation). \*
ATTENTION : destructif. \*/ public static function drop_tables(): void {
global \$wpdb; // Désactive temporairement le mode strict pour DROP //
phpcs:disable WordPress.DB.DirectDatabaseQuery \$wpdb-\>query( \'DROP
TABLE IF EXISTS \' . self::batches_table() ); \$wpdb-\>query( \'DROP
TABLE IF EXISTS \' . self::jobs_table() ); // phpcs:enable
delete_option( self::SCHEMA_VERSION_OPTION ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Création des tables //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Crée la table des batches. \* \* Colonnes : \* id int
AUTO_INCREMENT Identifiant unique du batch \* batch_uuid varchar(36)
UUID v4 pour référence externe \* post_type varchar(50) page_seo \|
article_seo \* post_status varchar(20) draft \| publish \| future \*
status varchar(20) pending \| running \| done \| failed \* total int
Nombre total de keywords \* done_count int Nombre de jobs réussis \*
failed_count int Nombre de jobs échoués \* created_at datetime Date de
création \* updated_at datetime Date de dernière mise à jour \*
created_by bigint ID utilisateur WordPress \*/ private static function
create_batches_table(): void { global \$wpdb; \$table =
self::batches_table(); \$charset = \$wpdb-\>get_charset_collate(); \$sql
= \"CREATE TABLE IF NOT EXISTS {\$table} ( id BIGINT(20) UNSIGNED NOT
NULL AUTO_INCREMENT, batch_uuid VARCHAR(36) NOT NULL DEFAULT \'\',
post_type VARCHAR(50) NOT NULL DEFAULT \'page_seo\', post_status
VARCHAR(20) NOT NULL DEFAULT \'draft\', status VARCHAR(20) NOT NULL
DEFAULT \'pending\', total INT(11) NOT NULL DEFAULT 0, done_count
INT(11) NOT NULL DEFAULT 0, failed_count INT(11) NOT NULL DEFAULT 0,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at
DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (id),
UNIQUE KEY batch_uuid (batch_uuid), KEY status (status), KEY created_at
(created_at) ) {\$charset};\"; require_once ABSPATH .
\'wp-admin/includes/upgrade.php\'; dbDelta( \$sql ); } /\*\* \* Crée la
table des jobs individuels. \* \* Colonnes : \* id int AUTO_INCREMENT
Identifiant unique du job \* batch_id bigint FK vers batches.id \*
keyword varchar(255) Le mot-clé à traiter \* status varchar(20) pending
\| running \| done \| failed \| retrying \* attempts tinyint Nombre de
tentatives (max 2) \* post_id bigint ID du post créé (null si échec) \*
error_message text Message d\'erreur si échec \* scheduled_at datetime
Date de planification AS \* started_at datetime Date de début de
traitement \* completed_at datetime Date de fin de traitement \*
as_action_id bigint ID de l\'action Action Scheduler \*/ private static
function create_jobs_table(): void { global \$wpdb; \$table =
self::jobs_table(); \$charset = \$wpdb-\>get_charset_collate(); \$sql =
\"CREATE TABLE IF NOT EXISTS {\$table} ( id BIGINT(20) UNSIGNED NOT NULL
AUTO_INCREMENT, batch_id BIGINT(20) UNSIGNED NOT NULL, keyword
VARCHAR(255) NOT NULL DEFAULT \'\', status VARCHAR(20) NOT NULL DEFAULT
\'pending\', attempts TINYINT(3) UNSIGNED NOT NULL DEFAULT 0, post_id
BIGINT(20) UNSIGNED DEFAULT NULL, error_message TEXT DEFAULT NULL,
scheduled_at DATETIME DEFAULT NULL, started_at DATETIME DEFAULT NULL,
completed_at DATETIME DEFAULT NULL, as_action_id BIGINT(20) UNSIGNED
DEFAULT NULL, PRIMARY KEY (id), KEY batch_id (batch_id), KEY status
(status), KEY keyword (keyword(100)) ) {\$charset};\"; require_once
ABSPATH . \'wp-admin/includes/upgrade.php\'; dbDelta( \$sql ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Helpers noms de tables //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* \@return string Nom complet de la table batches. \*/ public
static function batches_table(): string { global \$wpdb; return
\$wpdb-\>prefix . \'techrappy_bulk_batches\'; } /\*\* \* \@return string
Nom complet de la table jobs. \*/ public static function jobs_table():
string { global \$wpdb; return \$wpdb-\>prefix .
\'techrappy_bulk_jobs\'; } } \`\`\` \-\-- \## 2. BulkBatchRepository.php
\`\`\`php \<?php /\*\* \* BulkBatchRepository \* \* Couche d\'accès aux
données pour les tables : \* - techrappy_bulk_batches \* -
techrappy_bulk_jobs \* \* Toutes les requêtes utilisent
\$wpdb-\>prepare(). \* Aucune logique métier ici : uniquement CRUD +
requêtes. \* \* \@package TechrappySEO\\Modules\\Bulk \*/ namespace
TechrappySEO\\Modules\\Bulk; use
TechrappySEO\\Migrations\\CreateBulkTables; defined( \'ABSPATH\' ) \|\|
exit; class BulkBatchRepository { /\*\* \* \@var \\wpdb \*/ private
\\wpdb \$db; /\*\* \* Nom de la table batches. \* \* \@var string \*/
private string \$batches_table; /\*\* \* Nom de la table jobs. \* \*
\@var string \*/ private string \$jobs_table; public function
\_\_construct() { global \$wpdb; \$this-\>db = \$wpdb;
\$this-\>batches_table = CreateBulkTables::batches_table();
\$this-\>jobs_table = CreateBulkTables::jobs_table(); } //
=========================================================================
// BATCHES --- CRUD //
=========================================================================
/\*\* \* Crée un nouveau batch et retourne son ID. \* \* \@param array
\$data { \* \@type string \$post_type page_seo\|article_seo \* \@type
string \$post_status draft\|publish\|future \* \@type int \$total Nombre
de keywords \* \@type int \$created_by ID utilisateur \* } \* \@return
int\|\\WP_Error ID du batch créé ou WP_Error. \*/ public function
create_batch( array \$data ): int\|\\WP_Error { \$uuid =
\$this-\>generate_uuid(); \$now = current_time( \'mysql\' ); \$user_id =
absint( \$data\[\'created_by\'\] ?? get_current_user_id() ); \$inserted
= \$this-\>db-\>insert( \$this-\>batches_table, \[ \'batch_uuid\' =\>
\$uuid, \'post_type\' =\> sanitize_key( \$data\[\'post_type\'\] ??
\'page_seo\' ), \'post_status\' =\> sanitize_key(
\$data\[\'post_status\'\] ?? \'draft\' ), \'status\' =\> \'pending\',
\'total\' =\> absint( \$data\[\'total\'\] ?? 0 ), \'done_count\' =\> 0,
\'failed_count\'=\> 0, \'created_at\' =\> \$now, \'updated_at\' =\>
\$now, \'created_by\' =\> \$user_id, \], \[ \'%s\', \'%s\', \'%s\',
\'%s\', \'%d\', \'%d\', \'%d\', \'%s\', \'%s\', \'%d\' \] ); if ( false
=== \$inserted ) { return new \\WP_Error( \'techrappy_bulk_db_error\',
\'Impossible de créer le batch : \' . \$this-\>db-\>last_error ); }
return (int) \$this-\>db-\>insert_id; } /\*\* \* Récupère un batch par
son ID. \* \* \@param int \$batch_id \* \@return object\|null \*/ public
function get_batch( int \$batch_id ): ?object { return
\$this-\>db-\>get_row( \$this-\>db-\>prepare( \"SELECT \* FROM
{\$this-\>batches_table} WHERE id = %d LIMIT 1\", \$batch_id ) ); }
/\*\* \* Récupère un batch par son UUID. \* \* \@param string \$uuid \*
\@return object\|null \*/ public function get_batch_by_uuid( string
\$uuid ): ?object { return \$this-\>db-\>get_row( \$this-\>db-\>prepare(
\"SELECT \* FROM {\$this-\>batches_table} WHERE batch_uuid = %s LIMIT
1\", \$uuid ) ); } /\*\* \* Met à jour le statut d\'un batch. \* \*
\@param int \$batch_id \* \@param string \$status
pending\|running\|done\|failed \* \@return bool \*/ public function
update_batch_status( int \$batch_id, string \$status ): bool { \$allowed
= \[ \'pending\', \'running\', \'done\', \'failed\' \]; if ( ! in_array(
\$status, \$allowed, true ) ) { return false; } return (bool)
\$this-\>db-\>update( \$this-\>batches_table, \[ \'status\' =\>
\$status, \'updated_at\' =\> current_time( \'mysql\' ), \], \[ \'id\'
=\> \$batch_id \], \[ \'%s\', \'%s\' \], \[ \'%d\' \] ); } /\*\* \*
Incrémente done_count ou failed_count d\'un batch de façon atomique. \*
\* \@param int \$batch_id \* \@param string \$counter \'done_count\' \|
\'failed_count\' \* \@return bool \*/ public function
increment_batch_counter( int \$batch_id, string \$counter ): bool {
\$allowed = \[ \'done_count\', \'failed_count\' \]; if ( ! in_array(
\$counter, \$allowed, true ) ) { return false; } // Requête atomique
pour éviter les race conditions \$result = \$this-\>db-\>query(
\$this-\>db-\>prepare( // phpcs:ignore
WordPress.DB.PreparedSQLPlaceholders \"UPDATE {\$this-\>batches_table}
SET {\$counter} = {\$counter} + 1, updated_at = %s WHERE id = %d\",
current_time( \'mysql\' ), \$batch_id ) ); return false !== \$result; }
/\*\* \* Vérifie si tous les jobs d\'un batch sont terminés et met à
jour le statut. \* Appelé après chaque fin de job. \* \* \@param int
\$batch_id \*/ public function maybe_complete_batch( int \$batch_id ):
void { \$batch = \$this-\>get_batch( \$batch_id ); if ( ! \$batch ) {
return; } \$completed = (int) \$batch-\>done_count + (int)
\$batch-\>failed_count; if ( \$completed \>= (int) \$batch-\>total ) {
// Détermine le statut final \$final_status = ( (int)
\$batch-\>failed_count === (int) \$batch-\>total ) ? \'failed\' :
\'done\'; \$this-\>update_batch_status( \$batch_id, \$final_status ); }
} /\*\* \* Retourne la liste des batches récents (pour l\'UI). \* \*
\@param int \$limit Nombre max de résultats. \* \@param int \$offset
Offset pour pagination. \* \@return array \*/ public function
get_recent_batches( int \$limit = 20, int \$offset = 0 ): array { return
\$this-\>db-\>get_results( \$this-\>db-\>prepare( \"SELECT \* FROM
{\$this-\>batches_table} ORDER BY created_at DESC LIMIT %d OFFSET %d\",
\$limit, \$offset ) ) ?: \[\]; } //
=========================================================================
// JOBS --- CRUD //
=========================================================================
/\*\* \* Insère plusieurs jobs en une seule requête (bulk insert). \* \*
\@param int \$batch_id \* \@param string\[\] \$keywords Tableau de
mots-clés. \* \@return int Nombre de lignes insérées. \*/ public
function create_jobs_bulk( int \$batch_id, array \$keywords ): int { if
( empty( \$keywords ) ) { return 0; } \$now = current_time( \'mysql\' );
\$values = \[\]; \$placeholders = \[\]; foreach ( \$keywords as
\$keyword ) { \$keyword = sanitize_text_field( \$keyword );
\$placeholders\[\] = \'(%d, %s, %s, %d, %s)\'; \$values\[\] =
\$batch_id; \$values\[\] = \$keyword; \$values\[\] = \'pending\';
\$values\[\] = 0; \$values\[\] = \$now; } \$sql = \$this-\>db-\>prepare(
\"INSERT INTO {\$this-\>jobs_table} (batch_id, keyword, status,
attempts, scheduled_at) VALUES \" . implode( \', \', \$placeholders ),
\$values ); \$this-\>db-\>query( \$sql ); return (int)
\$this-\>db-\>rows_affected; } /\*\* \* Récupère un job par son ID. \*
\* \@param int \$job_id \* \@return object\|null \*/ public function
get_job( int \$job_id ): ?object { return \$this-\>db-\>get_row(
\$this-\>db-\>prepare( \"SELECT \* FROM {\$this-\>jobs_table} WHERE id =
%d LIMIT 1\", \$job_id ) ); } /\*\* \* Récupère tous les jobs d\'un
batch. \* \* \@param int \$batch_id \* \@param string \$status Filtre
optionnel sur le statut. \* \@return array \*/ public function
get_jobs_by_batch( int \$batch_id, string \$status = \'\' ): array { if
( \$status ) { return \$this-\>db-\>get_results( \$this-\>db-\>prepare(
\"SELECT \* FROM {\$this-\>jobs_table} WHERE batch_id = %d AND status =
%s ORDER BY id ASC\", \$batch_id, \$status ) ) ?: \[\]; } return
\$this-\>db-\>get_results( \$this-\>db-\>prepare( \"SELECT \* FROM
{\$this-\>jobs_table} WHERE batch_id = %d ORDER BY id ASC\", \$batch_id
) ) ?: \[\]; } /\*\* \* Met à jour le statut d\'un job. \* \* \@param
int \$job_id \* \@param string \$status
pending\|running\|done\|failed\|retrying \* \@param array \$extra_data
Données supplémentaires à mettre à jour. \* \@return bool \*/ public
function update_job_status( int \$job_id, string \$status, array
\$extra_data = \[\] ): bool { \$allowed = \[ \'pending\', \'running\',
\'done\', \'failed\', \'retrying\' \]; if ( ! in_array( \$status,
\$allowed, true ) ) { return false; } \$data = array_merge( \[
\'status\' =\> \$status \], \$extra_data ); \$formats = \[\]; //
Construit les formats selon les clés \$format_map = \[ \'status\' =\>
\'%s\', \'attempts\' =\> \'%d\', \'post_id\' =\> \'%d\',
\'error_message\' =\> \'%s\', \'started_at\' =\> \'%s\',
\'completed_at\' =\> \'%s\', \'as_action_id\' =\> \'%d\', \]; foreach (
\$data as \$key =\> \$value ) { \$formats\[\] = \$format_map\[ \$key \]
?? \'%s\'; } return (bool) \$this-\>db-\>update( \$this-\>jobs_table,
\$data, \[ \'id\' =\> \$job_id \], \$formats, \[ \'%d\' \] ); } /\*\* \*
Enregistre l\'ID de l\'action Action Scheduler pour un job. \* \*
\@param int \$job_id \* \@param int \$as_action_id \* \@return bool \*/
public function set_job_as_action_id( int \$job_id, int \$as_action_id
): bool { return (bool) \$this-\>db-\>update( \$this-\>jobs_table, \[
\'as_action_id\' =\> \$as_action_id \], \[ \'id\' =\> \$job_id \], \[
\'%d\' \], \[ \'%d\' \] ); } /\*\* \* Compte les jobs d\'un batch par
statut. \* \* \@param int \$batch_id \* \@return array\<string, int\> \[
\'pending\' =\> N, \'done\' =\> N, \... \] \*/ public function
count_jobs_by_status( int \$batch_id ): array { \$rows =
\$this-\>db-\>get_results( \$this-\>db-\>prepare( \"SELECT status,
COUNT(\*) as count FROM {\$this-\>jobs_table} WHERE batch_id = %d GROUP
BY status\", \$batch_id ) ) ?: \[\]; \$counts = \[ \'pending\' =\> 0,
\'running\' =\> 0, \'done\' =\> 0, \'failed\' =\> 0, \'retrying\' =\> 0,
\]; foreach ( \$rows as \$row ) { if ( isset( \$counts\[ \$row-\>status
\] ) ) { \$counts\[ \$row-\>status \] = (int) \$row-\>count; } } return
\$counts; } /\*\* \* Retourne un résumé paginé des jobs pour
l\'affichage UI. \* \* \@param int \$batch_id \* \@param int \$limit \*
\@param int \$offset \* \@return array \*/ public function
get_jobs_summary( int \$batch_id, int \$limit = 50, int \$offset = 0 ):
array { return \$this-\>db-\>get_results( \$this-\>db-\>prepare(
\"SELECT id, keyword, status, attempts, post_id, error_message,
completed_at FROM {\$this-\>jobs_table} WHERE batch_id = %d ORDER BY id
ASC LIMIT %d OFFSET %d\", \$batch_id, \$limit, \$offset ) ) ?: \[\]; }
//
=========================================================================
// HELPERS //
=========================================================================
/\*\* \* Génère un UUID v4. \* \* \@return string \*/ private function
generate_uuid(): string { \$data = random_bytes( 16 ); \$data\[6\] =
chr( ord( \$data\[6\] ) & 0x0f \| 0x40 ); \$data\[8\] = chr( ord(
\$data\[8\] ) & 0x3f \| 0x80 ); return vsprintf(
\'%s%s-%s-%s-%s-%s%s%s\', str_split( bin2hex( \$data ), 4 ) ); } }
\`\`\` \-\-- \## 3. BulkBatchValidator.php \`\`\`php \<?php /\*\* \*
BulkBatchValidator \* \* Valide et nettoie les données d\'entrée d\'un
batch avant traitement. \* \* \@package TechrappySEO\\Modules\\Bulk \*/
namespace TechrappySEO\\Modules\\Bulk; defined( \'ABSPATH\' ) \|\| exit;
class BulkBatchValidator { /\*\* \* Nombre maximum de keywords par
batch. \*/ const MAX_KEYWORDS = 500; /\*\* \* Nombre minimum de
caractères par keyword. \*/ const MIN_KEYWORD_LENGTH = 2; /\*\* \*
Nombre maximum de caractères par keyword. \*/ const MAX_KEYWORD_LENGTH =
200; /\*\* \* Post types autorisés. \*/ const ALLOWED_POST_TYPES = \[
\'page_seo\', \'article_seo\' \]; /\*\* \* Statuts de publication
autorisés. \*/ const ALLOWED_STATUSES = \[ \'draft\', \'publish\',
\'future\' \]; /\*\* \* Erreurs de validation. \* \* \@var string\[\]
\*/ private array \$errors = \[\]; /\*\* \* Keywords validés et
nettoyés. \* \* \@var string\[\] \*/ private array \$clean_keywords =
\[\]; /\*\* \* Valide le payload complet d\'un batch. \* \* \@param
array \$input { \* \@type string \$keywords_raw Textarea brut (1
keyword/ligne). \* \@type string \$post_type page_seo\|article_seo \*
\@type string \$post_status draft\|publish\|future \* } \* \@return bool
True si valide. \*/ public function validate( array \$input ): bool {
\$this-\>errors = \[\]; \$this-\>clean_keywords = \[\];
\$this-\>validate_post_type( \$input\[\'post_type\'\] ?? \'\' );
\$this-\>validate_post_status( \$input\[\'post_status\'\] ?? \'\' );
\$this-\>validate_keywords( \$input\[\'keywords_raw\'\] ?? \'\' );
return empty( \$this-\>errors ); } /\*\* \* \@return string\[\] Erreurs
de validation. \*/ public function get_errors(): array { return
\$this-\>errors; } /\*\* \* \@return string\[\] Keywords validés et
nettoyés. \*/ public function get_clean_keywords(): array { return
\$this-\>clean_keywords; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
private function validate_post_type( string \$post_type ): void { if ( !
in_array( \$post_type, self::ALLOWED_POST_TYPES, true ) ) {
\$this-\>errors\[\] = sprintf( \'Type invalide : \"%s\". Valeurs
autorisées : %s.\', esc_html( \$post_type ), implode( \', \',
self::ALLOWED_POST_TYPES ) ); } } private function validate_post_status(
string \$status ): void { if ( ! in_array( \$status,
self::ALLOWED_STATUSES, true ) ) { \$this-\>errors\[\] = sprintf(
\'Statut invalide : \"%s\". Valeurs autorisées : %s.\', esc_html(
\$status ), implode( \', \', self::ALLOWED_STATUSES ) ); } } private
function validate_keywords( string \$raw ): void { if ( empty( trim(
\$raw ) ) ) { \$this-\>errors\[\] = \'La liste de mots-clés est vide.\';
return; } // Découpe par saut de ligne, nettoie chaque entrée \$lines =
explode( \"\\n\", str_replace( \"\\r\\n\", \"\\n\", \$raw ) ); \$seen =
\[\]; \$valid = \[\]; \$skipped = 0; foreach ( \$lines as \$line ) {
\$kw = sanitize_text_field( trim( \$line ) ); // Ignore les lignes vides
if ( \'\' === \$kw ) { continue; } // Longueur minimale if ( mb_strlen(
\$kw ) \< self::MIN_KEYWORD_LENGTH ) { \$skipped++; continue; } //
Longueur maximale if ( mb_strlen( \$kw ) \> self::MAX_KEYWORD_LENGTH ) {
\$kw = mb_substr( \$kw, 0, self::MAX_KEYWORD_LENGTH ); } //
Dédoublonnage (insensible à la casse) \$key = mb_strtolower( \$kw ); if
( isset( \$seen\[ \$key \] ) ) { \$skipped++; continue; } \$seen\[ \$key
\] = true; \$valid\[\] = \$kw; } if ( empty( \$valid ) ) {
\$this-\>errors\[\] = \'Aucun mot-clé valide trouvé après nettoyage.\';
return; } if ( count( \$valid ) \> self::MAX_KEYWORDS ) {
\$this-\>errors\[\] = sprintf( \'Trop de mots-clés : %d fournis, maximum
%d autorisé.\', count( \$valid ), self::MAX_KEYWORDS ); return; } if (
\$skipped \> 0 ) { // Warning non-bloquant : log seulement (pas
d\'erreur) error_log( sprintf( \'\[TechrappySEO\]\[BulkBatchValidator\]
%d ligne(s) ignorée(s) (vides/doublons/trop courtes).\', \$skipped ) );
} \$this-\>clean_keywords = \$valid; } } \`\`\` \-\-- \## 4.
BulkQueueService.php \`\`\`php \<?php /\*\* \* BulkQueueService \* \*
Planifie les jobs de génération via Action Scheduler. \* Un job = un
keyword = une action AS. \* \* Dépendance : Action Scheduler doit être
actif. \* Vérification via
function_exists(\'as_schedule_single_action\'). \* \* \@package
TechrappySEO\\Modules\\Bulk \*/ namespace TechrappySEO\\Modules\\Bulk;
defined( \'ABSPATH\' ) \|\| exit; class BulkQueueService { /\*\* \* Nom
du hook Action Scheduler pour les jobs. \*/ const AS_HOOK =
\'techrappy_bulk_process_job\'; /\*\* \* Nom du hook AS pour les
retries. \*/ const AS_RETRY_HOOK = \'techrappy_bulk_retry_job\'; /\*\*
\* Groupe AS pour ce plugin (facilite le suivi dans l\'UI AS). \*/ const
AS_GROUP = \'techrappy-seo-bulk\'; /\*\* \* Délai initial entre les jobs
(en secondes). \* Évite de saturer le serveur. \*/ const
JOB_DELAY_SECONDS = 5; /\*\* \* Délai avant retry (en secondes). \*/
const RETRY_DELAY_SECONDS = 60; /\*\* \* \@var BulkBatchRepository \*/
private BulkBatchRepository \$repo; public function \_\_construct() {
\$this-\>repo = new BulkBatchRepository(); } /\*\* \* Vérifie si Action
Scheduler est disponible. \* \* \@return bool \*/ public function
is_action_scheduler_available(): bool { return function_exists(
\'as_schedule_single_action\' ) && function_exists(
\'as_has_scheduled_action\' ); } /\*\* \* Planifie tous les jobs d\'un
batch. \* Chaque job est planifié avec un délai échelonné pour éviter \*
la saturation serveur. \* \* \@param int \$batch_id \* \@param
string\[\] \$keywords \* \@return array { \* \@type int \$scheduled
Nombre de jobs planifiés. \* \@type array \$errors Erreurs éventuelles.
\* } \*/ public function schedule_batch( int \$batch_id, array
\$keywords ): array { if ( ! \$this-\>is_action_scheduler_available() )
{ return \[ \'scheduled\' =\> 0, \'errors\' =\> \[ \'Action Scheduler
non disponible. Vérifiez que WooCommerce ou le plugin Action Scheduler
est installé.\' \], \]; } // Met le batch en \"running\"
\$this-\>repo-\>update_batch_status( \$batch_id, \'running\' ); \$jobs =
\$this-\>repo-\>get_jobs_by_batch( \$batch_id, \'pending\' ); if (
empty( \$jobs ) ) { return \[ \'scheduled\' =\> 0, \'errors\' =\> \[
\'Aucun job pending trouvé pour ce batch.\' \] \]; } \$scheduled = 0;
\$errors = \[\]; foreach ( \$jobs as \$index =\> \$job ) { // Délai
échelonné : job 0 → dans 5s, job 1 → dans 10s, etc. \$delay =
self::JOB_DELAY_SECONDS \* ( \$index + 1 ); \$timestamp = time() +
\$delay; try { \$as_action_id = as_schedule_single_action( \$timestamp,
self::AS_HOOK, \[ \'job_id\' =\> (int) \$job-\>id, \'batch_id\' =\>
\$batch_id, \], self::AS_GROUP ); // Enregistre l\'ID AS dans le job
\$this-\>repo-\>set_job_as_action_id( (int) \$job-\>id, (int)
\$as_action_id ); \$this-\>repo-\>update_job_status( (int) \$job-\>id,
\'pending\', \[ \'scheduled_at\' =\> current_time( \'mysql\' ), \] );
\$scheduled++; } catch ( \\Exception \$e ) { \$errors\[\] = sprintf(
\'Échec planification job ID %d (keyword: %s) : %s\', \$job-\>id,
\$job-\>keyword, \$e-\>getMessage() ); error_log(
\'\[TechrappySEO\]\[BulkQueueService\] \' . end( \$errors ) ); } }
return compact( \'scheduled\', \'errors\' ); } /\*\* \* Planifie un
retry pour un job échoué. \* Délai fixe de RETRY_DELAY_SECONDS. \* \*
\@param int \$job_id \* \@param int \$batch_id \* \@return bool \*/
public function schedule_retry( int \$job_id, int \$batch_id ): bool {
if ( ! \$this-\>is_action_scheduler_available() ) { return false; }
\$timestamp = time() + self::RETRY_DELAY_SECONDS; try { \$as_action_id =
as_schedule_single_action( \$timestamp, self::AS_RETRY_HOOK, \[
\'job_id\' =\> \$job_id, \'batch_id\' =\> \$batch_id, \], self::AS_GROUP
); \$this-\>repo-\>set_job_as_action_id( \$job_id, (int) \$as_action_id
); \$this-\>repo-\>update_job_status( \$job_id, \'retrying\', \[
\'scheduled_at\' =\> current_time( \'mysql\' ), \] ); return true; }
catch ( \\Exception \$e ) { error_log( sprintf(
\'\[TechrappySEO\]\[BulkQueueService\] Échec planification retry job %d
: %s\', \$job_id, \$e-\>getMessage() ) ); return false; } } /\*\* \*
Annule toutes les actions AS en attente pour un batch. \* Utilisé lors
de l\'annulation manuelle d\'un batch. \* \* \@param int \$batch_id \*
\@return int Nombre d\'actions annulées. \*/ public function
cancel_batch_actions( int \$batch_id ): int { if ( !
\$this-\>is_action_scheduler_available() ) { return 0; } \$cancelled =
0; \$jobs = \$this-\>repo-\>get_jobs_by_batch( \$batch_id ); foreach (
\$jobs as \$job ) { // Annule uniquement les jobs en attente if ( !
in_array( \$job-\>status, \[ \'pending\', \'retrying\' \], true ) ) {
continue; } // Annule via le hook et les args as_unschedule_action(
self::AS_HOOK, \[ \'job_id\' =\> (int) \$job-\>id, \'batch_id\' =\>
\$batch_id, \], self::AS_GROUP ); as_unschedule_action(
self::AS_RETRY_HOOK, \[ \'job_id\' =\> (int) \$job-\>id, \'batch_id\'
=\> \$batch_id, \], self::AS_GROUP ); \$this-\>repo-\>update_job_status(
(int) \$job-\>id, \'failed\', \[ \'error_message\' =\> \'Annulé
manuellement.\', \'completed_at\' =\> current_time( \'mysql\' ), \] );
\$cancelled++; } \$this-\>repo-\>update_batch_status( \$batch_id,
\'failed\' ); return \$cancelled; } } \`\`\` \-\-- \## 5.
BulkJobProcessor.php \`\`\`php \<?php /\*\* \* BulkJobProcessor \* \*
Callback exécuté par Action Scheduler pour chaque job. \* Enregistre les
hooks AS, gère l\'exécution et le retry. \* \* Pipeline appelé :
techrappy_seo_create_post_from_keyword() \* (placeholder --- brancher
sur le pipeline existant) \* \* \@package TechrappySEO\\Modules\\Bulk
\*/ namespace TechrappySEO\\Modules\\Bulk; defined( \'ABSPATH\' ) \|\|
exit; class BulkJobProcessor { /\*\* \* Nombre maximum de tentatives par
job (1 initial + 1 retry). \*/ const MAX_ATTEMPTS = 2; /\*\* \* \@var
BulkBatchRepository \*/ private BulkBatchRepository \$repo; /\*\* \*
\@var BulkQueueService \*/ private BulkQueueService \$queue; public
function \_\_construct() { \$this-\>repo = new BulkBatchRepository();
\$this-\>queue = new BulkQueueService(); } /\*\* \* Enregistre les hooks
Action Scheduler. \* Appelé depuis BulkModule::register(). \*/ public
function register_hooks(): void { add_action( BulkQueueService::AS_HOOK,
\[ \$this, \'process_job\' \], 10, 2 ); add_action(
BulkQueueService::AS_RETRY_HOOK, \[ \$this, \'process_job\' \], 10, 2 );
} /\*\* \* Traite un job individuel. \* Appelé par Action Scheduler avec
les args planifiés. \* \* \@param int \$job_id ID du job en DB. \*
\@param int \$batch_id ID du batch parent. \*/ public function
process_job( int \$job_id, int \$batch_id ): void { \$job =
\$this-\>repo-\>get_job( \$job_id ); // \-\-- Validation du job \-\-- if
( ! \$job ) { error_log( sprintf( \'\[TechrappySEO\]\[BulkJobProcessor\]
Job ID %d introuvable.\', \$job_id ) ); return; } // Évite le double
traitement (ex: AS lance 2x) if ( \'done\' === \$job-\>status ) {
return; } // Récupère le batch pour connaître post_type et post_status
\$batch = \$this-\>repo-\>get_batch( \$batch_id ); if ( ! \$batch ) {
\$this-\>fail_job( \$job_id, \$batch_id, \'Batch parent introuvable.\'
); return; } // \-\-- Marque le job comme \"running\" \-\-- \$attempts =
(int) \$job-\>attempts + 1; \$this-\>repo-\>update_job_status( \$job_id,
\'running\', \[ \'attempts\' =\> \$attempts, \'started_at\' =\>
current_time( \'mysql\' ), \] ); // \-\-- Exécution du pipeline \-\--
try { \$result = \$this-\>call_pipeline( \$job-\>keyword,
\$batch-\>post_type, \$batch-\>post_status ); if ( is_wp_error( \$result
) ) { throw new \\RuntimeException( \$result-\>get_error_message() ); }
// \-\-- Succès \-\-- \$post_id = is_int( \$result ) ? \$result : null;
\$this-\>complete_job( \$job_id, \$batch_id, \$post_id ); } catch (
\\Throwable \$e ) { \$error_message = sprintf( \'\[Tentative %d/%d\]
%s\', \$attempts, self::MAX_ATTEMPTS, \$e-\>getMessage() ); error_log(
sprintf( \'\[TechrappySEO\]\[BulkJobProcessor\] Job %d FAILED : %s\',
\$job_id, \$error_message ) ); // \-\-- Retry si attempts \< MAX \-\--
if ( \$attempts \< self::MAX_ATTEMPTS ) {
\$this-\>repo-\>update_job_status( \$job_id, \'retrying\', \[
\'attempts\' =\> \$attempts, \'error_message\' =\> \$error_message, \]
); \$this-\>queue-\>schedule_retry( \$job_id, \$batch_id ); } else { //
Échec définitif \$this-\>fail_job( \$job_id, \$batch_id, \$error_message
); } } } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Pipeline //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Appelle le pipeline de création de post à partir d\'un keyword.
\* \* C\'est le point de branchement avec le pipeline existant. \*
Remplacer le corps de cette méthode par l\'appel réel au pipeline. \* \*
Contrat : \* - Retourne un int (post_id) si succès \* - Retourne
WP_Error si échec métier \* - Lance une exception si erreur système \*
\* \@param string \$keyword Mot-clé à traiter. \* \@param string
\$post_type page_seo\|article_seo \* \@param string \$post_status
draft\|publish\|future \* \@return int\|\\WP_Error Post ID créé ou
WP_Error. \*/ private function call_pipeline( string \$keyword, string
\$post_type, string \$post_status ): int\|\\WP_Error { /\*\* \*
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
\* POINT DE BRANCHEMENT --- Remplacer ce bloc par l\'appel réel. \*
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
\* Exemple d\'intégration avec le pipeline existant : \* \* \$pipeline =
new \\TechrappySEO\\Pipeline\\PostCreationPipeline(); \* return
\$pipeline-\>run(\[ \* \'keyword\' =\> \$keyword, \* \'post_type\' =\>
\$post_type, \* \'post_status\' =\> \$post_status, \* \]); \*
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
\* \* Filtre permettant aux autres modules de brancher leur pipeline.
\*/ \$result = apply_filters(
\'techrappy_seo_create_post_from_keyword\', null, \$keyword,
\$post_type, \$post_status ); // Si aucun handler n\'a répondu au filtre
: erreur explicite if ( null === \$result ) { return new \\WP_Error(
\'techrappy_pipeline_not_implemented\', sprintf( \'Aucun handler pour le
filtre techrappy_seo_create_post_from_keyword. \' . \'Keyword:
\"%s\".\', \$keyword ) ); } return \$result; } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
// Helpers état //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* Marque un job comme réussi et met à jour les compteurs du
batch. \* \* \@param int \$job_id \* \@param int \$batch_id \* \@param
int\|null \$post_id ID du post créé. \*/ private function complete_job(
int \$job_id, int \$batch_id, ?int \$post_id ): void {
\$this-\>repo-\>update_job_status( \$job_id, \'done\', \[ \'post_id\'
=\> \$post_id, \'completed_at\' =\> current_time( \'mysql\' ), \] );
\$this-\>repo-\>increment_batch_counter( \$batch_id, \'done_count\' );
\$this-\>repo-\>maybe_complete_batch( \$batch_id ); do_action(
\'techrappy_bulk_job_completed\', \$job_id, \$batch_id, \$post_id ); }
/\*\* \* Marque un job comme échoué définitivement et met à jour les
compteurs. \* \* \@param int \$job_id \* \@param int \$batch_id \*
\@param string \$error_message \*/ private function fail_job( int
\$job_id, int \$batch_id, string \$error_message ): void {
\$this-\>repo-\>update_job_status( \$job_id, \'failed\', \[
\'error_message\' =\> \$error_message, \'completed_at\' =\>
current_time( \'mysql\' ), \] );
\$this-\>repo-\>increment_batch_counter( \$batch_id, \'failed_count\' );
\$this-\>repo-\>maybe_complete_batch( \$batch_id ); do_action(
\'techrappy_bulk_job_failed\', \$job_id, \$batch_id, \$error_message );
} } \`\`\` \-\-- \## 6. BulkController.php \`\`\`php \<?php /\*\* \*
BulkController \* \* Gère : \* - L\'affichage de l\'UI admin
(formulaire + tableau de bord) \* - Les handlers AJAX (lancer batch,
polling statut, annuler) \* \* \@package TechrappySEO\\Modules\\Bulk \*/
namespace TechrappySEO\\Modules\\Bulk; defined( \'ABSPATH\' ) \|\| exit;
class BulkController { /\*\* \* Capability requise. \*/ const
REQUIRED_CAP = \'manage_options\'; /\*\* \* \@var BulkBatchRepository
\*/ private BulkBatchRepository \$repo; /\*\* \* \@var BulkQueueService
\*/ private BulkQueueService \$queue; /\*\* \* \@var BulkBatchValidator
\*/ private BulkBatchValidator \$validator; public function
\_\_construct() { \$this-\>repo = new BulkBatchRepository();
\$this-\>queue = new BulkQueueService(); \$this-\>validator = new
BulkBatchValidator(); } /\*\* \* Enregistre les hooks AJAX. \*/ public
function register(): void { add_action(
\'wp_ajax_techrappy_bulk_launch\', \[ \$this, \'ajax_launch_batch\' \]
); add_action( \'wp_ajax_techrappy_bulk_poll_status\', \[ \$this,
\'ajax_poll_status\' \] ); add_action(
\'wp_ajax_techrappy_bulk_cancel\', \[ \$this, \'ajax_cancel_batch\' \]
); add_action( \'wp_ajax_techrappy_bulk_get_jobs\', \[ \$this,
\'ajax_get_jobs\' \] ); } //
=========================================================================
// AJAX Handlers //
=========================================================================
/\*\* \* AJAX : Lance un nouveau batch de génération. \* \* POST params:
\* nonce string Nonce techrappy_bulk_nonce \* keywords string Textarea
(1 keyword/ligne) \* post_type string page_seo\|article_seo \*
post_status string draft\|publish\|future \*/ public function
ajax_launch_batch(): void { \$this-\>verify_nonce(
\'techrappy_bulk_nonce\' ); \$this-\>check_capability(); // Vérifie
Action Scheduler if ( ! \$this-\>queue-\>is_action_scheduler_available()
) { wp_send_json_error( \[ \'message\' =\> \'Action Scheduler n\\\'est
pas disponible. \' . \'Installez WooCommerce ou le plugin Action
Scheduler.\', \], 503 ); } // Récupère et nettoie les inputs
\$keywords_raw = isset( \$\_POST\[\'keywords\'\] ) ? wp_unslash(
\$\_POST\[\'keywords\'\] ) : \'\'; \$post_type = isset(
\$\_POST\[\'post_type\'\] ) ? sanitize_key( wp_unslash(
\$\_POST\[\'post_type\'\] ) ) : \'\'; \$post_status = isset(
\$\_POST\[\'post_status\'\] ) ? sanitize_key( wp_unslash(
\$\_POST\[\'post_status\'\] ) ) : \'\'; // Validation \$is_valid =
\$this-\>validator-\>validate( \[ \'keywords_raw\' =\> \$keywords_raw,
\'post_type\' =\> \$post_type, \'post_status\' =\> \$post_status, \] );
if ( ! \$is_valid ) { wp_send_json_error( \[ \'message\' =\> \'Données
invalides.\', \'errors\' =\> \$this-\>validator-\>get_errors(), \], 422
); } \$keywords = \$this-\>validator-\>get_clean_keywords(); \$total =
count( \$keywords ); // \-\-- Crée le batch en DB \-\-- \$batch_id =
\$this-\>repo-\>create_batch( \[ \'post_type\' =\> \$post_type,
\'post_status\' =\> \$post_status, \'total\' =\> \$total, \'created_by\'
=\> get_current_user_id(), \] ); if ( is_wp_error( \$batch_id ) ) {
wp_send_json_error( \[ \'message\' =\> \'Impossible de créer le batch :
\' . \$batch_id-\>get_error_message(), \], 500 ); } // \-\-- Crée les
jobs en bulk \-\-- \$inserted = \$this-\>repo-\>create_jobs_bulk(
\$batch_id, \$keywords ); if ( \$inserted === 0 ) { wp_send_json_error(
\[ \'message\' =\> \'Aucun job n\\\'a pu être créé.\', \], 500 ); } //
\-\-- Planifie via Action Scheduler \-\-- \$schedule_result =
\$this-\>queue-\>schedule_batch( \$batch_id, \$keywords );
wp_send_json_success( \[ \'batch_id\' =\> \$batch_id, \'total\' =\>
\$total, \'scheduled\' =\> \$schedule_result\[\'scheduled\'\],
\'warnings\' =\> \$schedule_result\[\'errors\'\], \'message\' =\>
sprintf( \'%d keyword(s) planifié(s) avec succès.\',
\$schedule_result\[\'scheduled\'\] ), \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* AJAX : Polling du statut d\'un batch (appelé toutes les N
secondes). \* \* POST params: \* nonce string Nonce \* batch_id int ID
du batch \*/ public function ajax_poll_status(): void {
\$this-\>verify_nonce( \'techrappy_bulk_nonce\' );
\$this-\>check_capability(); \$batch_id = isset(
\$\_POST\[\'batch_id\'\] ) ? absint( \$\_POST\[\'batch_id\'\] ) : 0; if
( ! \$batch_id ) { wp_send_json_error( \[ \'message\' =\> \'batch_id
invalide.\' \], 400 ); } \$batch = \$this-\>repo-\>get_batch( \$batch_id
); if ( ! \$batch ) { wp_send_json_error( \[ \'message\' =\> \'Batch
introuvable.\' \], 404 ); } \$counts =
\$this-\>repo-\>count_jobs_by_status( \$batch_id ); \$total = (int)
\$batch-\>total; \$done = (int) \$batch-\>done_count; \$failed = (int)
\$batch-\>failed_count; \$progress = \$total \> 0 ? (int) round( ( (
\$done + \$failed ) / \$total ) \* 100 ) : 0; wp_send_json_success( \[
\'batch_id\' =\> \$batch_id, \'status\' =\> \$batch-\>status, \'total\'
=\> \$total, \'done_count\' =\> \$done, \'failed_count\' =\> \$failed,
\'pending\' =\> \$counts\[\'pending\'\] + \$counts\[\'running\'\] +
\$counts\[\'retrying\'\], \'progress\' =\> \$progress, \'is_complete\'
=\> in_array( \$batch-\>status, \[ \'done\', \'failed\' \], true ),
\'updated_at\' =\> \$batch-\>updated_at, \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* AJAX : Annule un batch en cours. \* \* POST params: \* nonce
string \* batch_id int \*/ public function ajax_cancel_batch(): void {
\$this-\>verify_nonce( \'techrappy_bulk_nonce\' );
\$this-\>check_capability(); \$batch_id = isset(
\$\_POST\[\'batch_id\'\] ) ? absint( \$\_POST\[\'batch_id\'\] ) : 0; if
( ! \$batch_id ) { wp_send_json_error( \[ \'message\' =\> \'batch_id
invalide.\' \], 400 ); } \$batch = \$this-\>repo-\>get_batch( \$batch_id
); if ( ! \$batch ) { wp_send_json_error( \[ \'message\' =\> \'Batch
introuvable.\' \], 404 ); } if ( in_array( \$batch-\>status, \[
\'done\', \'failed\' \], true ) ) { wp_send_json_error( \[ \'message\'
=\> \'Ce batch est déjà terminé.\' \], 409 ); } \$cancelled =
\$this-\>queue-\>cancel_batch_actions( \$batch_id );
wp_send_json_success( \[ \'cancelled\' =\> \$cancelled, \'message\' =\>
sprintf( \'%d action(s) annulée(s).\', \$cancelled ), \] ); } //
\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\-\--
/\*\* \* AJAX : Retourne les détails des jobs d\'un batch (avec
pagination). \* \* POST params: \* nonce string \* batch_id int \* page
int (défaut 1) \*/ public function ajax_get_jobs(): void {
\$this-\>verify_nonce( \'techrappy_bulk_nonce\' );
\$this-\>check_capability(); \$batch_id = isset(
\$\_POST\[\'batch_id\'\] ) ? absint( \$\_POST\[\'batch_id\'\] ) : 0;
\$page = isset( \$\_POST\[\'page\'\] ) ? max( 1, absint(
\$\_POST\[\'page\'\] ) ) : 1; \$limit = 50; \$offset = ( \$page - 1 ) \*
\$limit; if ( ! \$batch_id ) { wp_send_json_error( \[ \'message\' =\>
\'batch_id invalide.\' \], 400 ); } \$jobs =
\$this-\>repo-\>get_jobs_summary( \$batch_id, \$limit, \$offset ); //
Sanitize pour la sortie \$jobs_clean = array_map( function ( \$job ) {
return \[ \'id\' =\> (int) \$job-\>id, \'keyword\' =\> esc_html(
\$job-\>keyword ), \'status\' =\> esc_html( \$job-\>status ),
\'attempts\' =\> (int) \$job-\>attempts, \'post_id\' =\> \$job-\>post_id
? (int) \$job-\>post_id : null, \'post_edit_url\' =\> \$job-\>post_id ?
get_edit_post_link( (int) \$job-\>post_id, \'raw\' ) : null,
\'error_message\' =\> \$job-\>error_message ? esc_html(
\$job-\>error_message ) : null, \'completed_at\' =\>
\$job-\>completed_at, \]; }, \$jobs ); wp_send_json_success( \[ \'jobs\'
=\> \$jobs_clean, \'page\' =\> \$page, \'has_more\'=\> count( \$jobs )
=== \$limit, \] ); } //
=========================================================================
// Rendu admin //
=========================================================================
/\*\* \* Retourne le HTML de la page admin Bulk Generator. \* \*
\@return string \*/ public function render_page(): string { // Données
pour la vue \$recent_batches = \$this-\>repo-\>get_recent_batches( 10 );
\$nonce = wp_create_nonce( \'techrappy_bulk_nonce\' ); \$as_available =
\$this-\>queue-\>is_action_scheduler_available(); \$validator =
\$this-\>validator; ob_start(); include TECHRAPPY_SEO_PATH .
\'admin/partials/bulk-generator.php\'; return ob_get_clean(); } //
=========================================================================
// Sécurité //
=========================================================================
private function verify_nonce( string \$action ): void { \$nonce =
isset( \$\_POST\[\'nonce\'\] ) ? sanitize_text_field( wp_unslash(
\$\_POST\[\'nonce\'\] ) ) : \'\'; if ( ! wp_verify_nonce( \$nonce,
\$action ) ) { wp_send_json_error( \[ \'message\' =\> \'Nonce
invalide.\' \], 403 ); } } private function check_capability(): void {
if ( ! current_user_can( self::REQUIRED_CAP ) ) { wp_send_json_error( \[
\'message\' =\> \'Permission refusée.\' \], 403 ); } } } \`\`\` \-\--
\## 7. BulkModule.php \`\`\`php \<?php /\*\* \* BulkModule \* \* Point
d\'entrée du module de génération de masse. \* Instancier depuis le
plugin principal : ( new BulkModule() )-\>register(); \* \* \@package
TechrappySEO\\Modules\\Bulk \*/ namespace TechrappySEO\\Modules\\Bulk;
use TechrappySEO\\Migrations\\CreateBulkTables; defined( \'ABSPATH\' )
\|\| exit; class BulkModule { const SCRIPT_HANDLE =
\'techrappy-bulk-admin\'; const STYLE_HANDLE =
\'techrappy-bulk-admin-css\'; const ADMIN_PAGE = \'techrappy-seo\';
/\*\* \* \@var BulkController \*/ private BulkController \$controller;
/\*\* \* \@var BulkJobProcessor \*/ private BulkJobProcessor
\$processor; public function \_\_construct() { \$this-\>controller = new
BulkController(); \$this-\>processor = new BulkJobProcessor(); } /\*\*
\* Enregistre tous les hooks du module. \*/ public function register():
void { // Migration DB au besoin add_action( \'init\', \[ \$this,
\'maybe_run_migration\' \], 1 ); // Hooks AJAX
\$this-\>controller-\>register(); // Hooks Action Scheduler
\$this-\>processor-\>register_hooks(); // Assets admin add_action(
\'admin_enqueue_scripts\', \[ \$this, \'enqueue_assets\' \] ); //
Intégration dans le menu/filtre du plugin principal add_filter(
\'techrappy_seo_admin_tabs\', \[ \$this, \'register_admin_tab\' \] ); //
Notice si AS absent add_action( \'admin_notices\', \[ \$this,
\'maybe_show_as_notice\' \] ); } /\*\* \* Lance la migration si
nécessaire. \*/ public function maybe_run_migration(): void {
CreateBulkTables::run(); } /\*\* \* Enqueue assets uniquement sur la
page admin Techrappy. \* \* \@param string \$hook \*/ public function
enqueue_assets( string \$hook ): void { if ( ! str_contains( \$hook,
self::ADMIN_PAGE ) ) { return; } wp_enqueue_style( self::STYLE_HANDLE,
TECHRAPPY_SEO_URL . \'assets/css/bulk-admin.css\', \[\],
TECHRAPPY_SEO_VERSION ); wp_enqueue_script( self::SCRIPT_HANDLE,
TECHRAPPY_SEO_URL . \'assets/js/bulk-admin.js\', \[ \'jquery\' \],
TECHRAPPY_SEO_VERSION, true ); wp_localize_script( self::SCRIPT_HANDLE,
\'techrappyBulkData\', \[ \'ajaxUrl\' =\> admin_url( \'admin-ajax.php\'
), \'nonce\' =\> wp_create_nonce( \'techrappy_bulk_nonce\' ),
\'pollInterval\' =\> 4000, // ms entre chaque polling \'i18n\' =\> \[
\'launching\' =\> \_\_( \'Lancement en cours...\', \'techrappy-seo\' ),
\'running\' =\> \_\_( \'Génération en cours...\', \'techrappy-seo\' ),
\'done\' =\> \_\_( \'Terminé !\', \'techrappy-seo\' ), \'failed\' =\>
\_\_( \'Terminé avec erreurs.\', \'techrappy-seo\' ), \'cancelled\' =\>
\_\_( \'Batch annulé.\', \'techrappy-seo\' ), \'confirmCancel\'=\> \_\_(
\'Annuler ce batch ? Les jobs en cours seront arrêtés.\',
\'techrappy-seo\' ), \'errorGeneric\' =\> \_\_( \'Une erreur est
survenue.\', \'techrappy-seo\' ), \'noKeywords\' =\> \_\_( \'Saisissez
au moins un mot-clé.\', \'techrappy-seo\' ), \], \] ); } /\*\* \*
Enregistre l\'onglet \"Génération de masse\" dans l\'UI du plugin. \* \*
\@param array \$tabs \* \@return array \*/ public function
register_admin_tab( array \$tabs ): array { \$tabs\[\'bulk\'\] = \[
\'label\' =\> \_\_( \'⚡ Génération de masse\', \'techrappy-seo\' ),
\'callback\' =\> \[ \$this-\>controller, \'render_page\' \], \]; return
\$tabs; } /\*\* \* Affiche une notice admin si Action Scheduler n\'est
pas disponible. \*/ public function maybe_show_as_notice(): void {
\$screen = get_current_screen(); if ( ! \$screen \|\| ! str_contains(
\$screen-\>id, self::ADMIN_PAGE ) ) { return; } \$queue = new
BulkQueueService(); if ( \$queue-\>is_action_scheduler_available() ) {
return; } echo \'\<div class=\"notice notice-error\"\>\<p\>\'; echo
\'\<strong\>Techrappy SEO --- Génération de masse :\</strong\> \';
esc_html_e( \'Action Scheduler n\\\'est pas disponible. Le module de
génération de masse est désactivé. \' . \'Installez WooCommerce ou le
plugin standalone Action Scheduler.\', \'techrappy-seo\' ); echo
\'\</p\>\</div\>\'; } } \`\`\` \-\-- \## 8. Vue admin ---
\`bulk-generator.php\` \`\`\`php \<?php /\*\* \* Partial : Bulk
Generator \* \* Variables attendues : \* \$nonce string Nonce
techrappy_bulk_nonce \* \$recent_batches array Batches récents \*
\$as_available bool Action Scheduler actif ? \* \$validator
BulkBatchValidator \* \* \@package TechrappySEO\\Admin \*/ defined(
\'ABSPATH\' ) \|\| exit; ?\> \<div class=\"techrappy-bulk-wrap\"
id=\"techrappy-bulk-wrap\"\> \<!\--
================================================================ HEADER
================================================================ \--\>
\<div class=\"techrappy-bulk-header\"\> \<h2
class=\"techrappy-bulk-title\"\> ⚡ \<?php esc_html_e( \'Génération de
masse\', \'techrappy-seo\' ); ?\> \</h2\> \<p
class=\"techrappy-bulk-subtitle\"\> \<?php esc_html_e( \'Générez
automatiquement plusieurs pages ou articles SEO depuis une liste de
mots-clés.\', \'techrappy-seo\' ); ?\> \</p\> \</div\> \<?php if ( !
\$as_available ) : ?\> \<div class=\"techrappy-notice
techrappy-notice\--error\"\> ❌ \<?php esc_html_e( \'Action Scheduler
n\\\'est pas disponible. Ce module nécessite WooCommerce ou le plugin
Action Scheduler.\', \'techrappy-seo\' ); ?\> \</div\> \<?php else : ?\>
\<!\-- ================================================================
FORMULAIRE DE LANCEMENT
================================================================ \--\>
\<div class=\"techrappy-bulk-card\" id=\"techrappy-bulk-form-card\"\>
\<h3 class=\"techrappy-bulk-card-title\"\> 📋 \<?php esc_html_e(
\'Nouveau batch\', \'techrappy-seo\' ); ?\> \</h3\> \<div
class=\"techrappy-bulk-form\"\> \<!\-- Keywords \--\> \<div
class=\"techrappy-bulk-field\"\> \<label
for=\"techrappy-bulk-keywords\"\> \<?php esc_html_e( \'Mots-clés\',
\'techrappy-seo\' ); ?\> \<span
class=\"techrappy-required\"\>\*\</span\> \</label\> \<textarea
id=\"techrappy-bulk-keywords\" name=\"keywords\" rows=\"10\"
placeholder=\"\<?php esc_attr_e( \'Un mot-clé par ligne...\\nostéopathe
toulouse\\nostéopathe bordeaux\\n...\', \'techrappy-seo\' ); ?\>\"
class=\"techrappy-bulk-textarea large-text\" spellcheck=\"false\"
\>\</textarea\> \<p class=\"description\"\> \<?php printf( esc_html\_\_(
\'Maximum %d mots-clés par batch. Les doublons sont ignorés
automatiquement.\', \'techrappy-seo\' ),
\\TechrappySEO\\Modules\\Bulk\\BulkBatchValidator::MAX_KEYWORDS ); ?\>
\</p\> \<div class=\"techrappy-kw-counter\"\> \<span
id=\"techrappy-kw-count\"\>0\</span\> \<?php esc_html_e(
\'mot(s)-clé(s)\', \'techrappy-seo\' ); ?\> \</div\> \</div\> \<!\--
Options en ligne \--\> \<div class=\"techrappy-bulk-options-row\"\>
\<!\-- Type \--\> \<div class=\"techrappy-bulk-field
techrappy-bulk-field\--inline\"\> \<label
for=\"techrappy-bulk-post-type\"\> \<?php esc_html_e( \'Type de
contenu\', \'techrappy-seo\' ); ?\> \</label\> \<select
id=\"techrappy-bulk-post-type\" name=\"post_type\"
class=\"techrappy-bulk-select\"\> \<option value=\"page_seo\"\> \<?php
esc_html_e( \'Page SEO\', \'techrappy-seo\' ); ?\> \</option\> \<option
value=\"article_seo\"\> \<?php esc_html_e( \'Article SEO\',
\'techrappy-seo\' ); ?\> \</option\> \</select\> \</div\> \<!\-- Statut
final \--\> \<div class=\"techrappy-bulk-field
techrappy-bulk-field\--inline\"\> \<label
for=\"techrappy-bulk-post-status\"\> \<?php esc_html_e( \'Statut
final\', \'techrappy-seo\' ); ?\> \</label\> \<select
id=\"techrappy-bulk-post-status\" name=\"post_status\"
class=\"techrappy-bulk-select\"\> \<option value=\"draft\"\> \<?php
esc_html_e( \'Brouillon\', \'techrappy-seo\' ); ?\> \</option\> \<option
value=\"publish\"\> \<?php esc_html_e( \'Publier\', \'techrappy-seo\' );
?\> \</option\> \<option value=\"future\"\> \<?php esc_html_e(
\'Planifié\', \'techrappy-seo\' ); ?\> \</option\> \</select\> \</div\>
\</div\>\<!\-- /.techrappy-bulk-options-row \--\> \<!\-- Bouton Lancer
\--\> \<div class=\"techrappy-bulk-actions\"\> \<button type=\"button\"
id=\"techrappy-bulk-launch-btn\" class=\"button button-primary
button-hero\" \> ⚡ \<?php esc_html_e( \'Lancer la génération\',
\'techrappy-seo\' ); ?\> \</button\> \</div\> \<!\-- Zone erreurs
validation \--\> \<div id=\"techrappy-bulk-form-errors\"
class=\"techrappy-notice techrappy-notice\--error\"
style=\"display:none;\"\>\</div\> \</div\>\<!\-- /.techrappy-bulk-form
\--\> \</div\>\<!\-- /#techrappy-bulk-form-card \--\> \<!\--
================================================================
PROGRESSION DU BATCH ACTIF
================================================================ \--\>
\<div class=\"techrappy-bulk-card\" id=\"techrappy-bulk-progress-card\"
style=\"display:none;\"\> \<div
class=\"techrappy-bulk-progress-header\"\> \<h3
class=\"techrappy-bulk-card-title\"\> 🔄 \<span
id=\"techrappy-bulk-progress-title\"\> \<?php esc_html_e( \'Génération
en cours...\', \'techrappy-seo\' ); ?\> \</span\> \</h3\> \<button
type=\"button\" id=\"techrappy-bulk-cancel-btn\" class=\"button
button-secondary techrappy-btn-danger\" \> 🛑 \<?php esc_html_e(
\'Annuler\', \'techrappy-seo\' ); ?\> \</button\> \</div\> \<!\-- Barre
de progression \--\> \<div class=\"techrappy-progress-bar-wrap\"\> \<div
class=\"techrappy-progress-bar\" id=\"techrappy-progress-bar\"\> \<div
class=\"techrappy-progress-fill\" id=\"techrappy-progress-fill\"
style=\"width:0%;\"\> \<span class=\"techrappy-progress-pct\"
id=\"techrappy-progress-pct\"\>0%\</span\> \</div\> \</div\> \</div\>
\<!\-- Compteurs \--\> \<div class=\"techrappy-bulk-counters\"\> \<div
class=\"techrappy-counter techrappy-counter\--total\"\> \<span
class=\"techrappy-counter-value\"
id=\"techrappy-counter-total\"\>0\</span\> \<span
class=\"techrappy-counter-label\"\>\<?php esc_html_e( \'Total\',
\'techrappy-seo\' ); ?\>\</span\> \</div\> \<div
class=\"techrappy-counter techrappy-counter\--pending\"\> \<span
class=\"techrappy-counter-value\"
id=\"techrappy-counter-pending\"\>0\</span\> \<span
class=\"techrappy-counter-label\"\>\<?php esc_html_e( \'En attente\',
\'techrappy-seo\' ); ?\>\</span\> \</div\> \<div
class=\"techrappy-counter techrappy-counter\--done\"\> \<span
class=\"techrappy-counter-value\"
id=\"techrappy-counter-done\"\>0\</span\> \<span
class=\"techrappy-counter-label\"\>\<?php esc_html_e( \'Réussis\',
\'techrappy-seo\' ); ?\>\</span\> \</div\> \<div
class=\"techrappy-counter techrappy-counter\--failed\"\> \<span
class=\"techrappy-counter-value\"
id=\"techrappy-counter-failed\"\>0\</span\> \<span
class=\"techrappy-counter-label\"\>\<?php esc_html_e( \'Échoués\',
\'techrappy-seo\' ); ?\>\</span\> \</div\> \</div\> \<!\-- Log en temps
réel \--\> \<div class=\"techrappy-bulk-log-wrap\"\> \<h4\>\<?php
esc_html_e( \'Détails des jobs\', \'techrappy-seo\' ); ?\>\</h4\> \<div
id=\"techrappy-bulk-jobs-table-wrap\"\> \<!\-- Rempli dynamiquement
\--\> \</div\> \<button type=\"button\" id=\"techrappy-bulk-load-more\"
class=\"button button-secondary\" style=\"display:none;\" \> \<?php
esc_html_e( \'Charger plus\', \'techrappy-seo\' ); ?\> \</button\>
\</div\> \</div\>\<!\-- /#techrappy-bulk-progress-card \--\> \<?php
endif; // \$as_available ?\> \<!\--
================================================================
HISTORIQUE DES BATCHES
================================================================ \--\>
\<?php if ( ! empty( \$recent_batches ) ) : ?\> \<div
class=\"techrappy-bulk-card\" id=\"techrappy-bulk-history-card\"\> \<h3
class=\"techrappy-bulk-card-title\"\> 📊 \<?php esc_html_e( \'Historique
récent\', \'techrappy-seo\' ); ?\> \</h3\> \<table
class=\"techrappy-bulk-history-table wp-list-table widefat fixed
striped\"\> \<thead\> \<tr\> \<th\>\<?php esc_html_e( \'ID\',
\'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php esc_html_e( \'Type\',
\'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php esc_html_e( \'Statut\',
\'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php esc_html_e(
\'Progression\', \'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php
esc_html_e( \'Créé le\', \'techrappy-seo\' ); ?\>\</th\> \<th\>\<?php
esc_html_e( \'Actions\', \'techrappy-seo\' \-\-- \## 8. Vue admin (suite
et fin) --- \`bulk-generator.php\` \`\`\`php ); ?\>\</th\> \</tr\>
\</thead\> \<tbody\> \<?php foreach ( \$recent_batches as \$batch ) :
\$total = (int) \$batch-\>total; \$done = (int) \$batch-\>done_count;
\$failed = (int) \$batch-\>failed_count; \$progress = \$total \> 0 ?
(int) round( ( ( \$done + \$failed ) / \$total ) \* 100 ) : 0; ?\> \<tr
data-batch-id=\"\<?php echo esc_attr( \$batch-\>id ); ?\>\"\> \<!\-- ID
\--\> \<td\> \<strong\>#\<?php echo esc_html( \$batch-\>id );
?\>\</strong\> \<br/\> \<small class=\"techrappy-muted\"\> \<?php echo
esc_html( substr( \$batch-\>batch_uuid, 0, 8 ) ); ?\>... \</small\>
\</td\> \<!\-- Type + Statut publication \--\> \<td\> \<span
class=\"techrappy-tag\"\> \<?php echo esc_html( \$batch-\>post_type );
?\> \</span\> \<br/\> \<small class=\"techrappy-muted\"\> \<?php echo
esc_html( \$batch-\>post_status ); ?\> \</small\> \</td\> \<!\-- Statut
batch \--\> \<td\> \<?php \$status_class = match ( \$batch-\>status ) {
\'done\' =\> \'techrappy-badge\--ok\', \'failed\' =\>
\'techrappy-badge\--error\', \'running\' =\>
\'techrappy-badge\--running\', default =\>
\'techrappy-badge\--pending\', }; \$status_label = match (
\$batch-\>status ) { \'done\' =\> \'✅ Terminé\', \'failed\' =\> \'❌
Échoué\', \'running\' =\> \'🔄 En cours\', \'pending\' =\> \'⏳ En
attente\', default =\> esc_html( \$batch-\>status ), }; ?\> \<span
class=\"techrappy-badge \<?php echo esc_attr( \$status_class ); ?\>\"\>
\<?php echo esc_html( \$status_label ); ?\> \</span\> \</td\> \<!\--
Progression \--\> \<td\> \<div class=\"techrappy-mini-progress\"\> \<div
class=\"techrappy-mini-progress-fill\" style=\"width: \<?php echo
esc_attr( \$progress ); ?\>%;\" \>\</div\> \</div\> \<small\> \<?php
printf( esc_html\_\_( \'%1\$d/%2\$d (%3\$d%%)\', \'techrappy-seo\' ),
\$done + \$failed, \$total, \$progress ); ?\> \<?php if ( \$failed \> 0
) : ?\> --- \<span class=\"techrappy-text-error\"\> \<?php printf(
esc_html\_\_( \'%d échec(s)\', \'techrappy-seo\' ), \$failed ); ?\>
\</span\> \<?php endif; ?\> \</small\> \</td\> \<!\-- Date \--\> \<td\>
\<small\> \<?php echo esc_html( wp_date( get_option( \'date_format\' ) .
\' \' . get_option( \'time_format\' ), strtotime( \$batch-\>created_at )
) ); ?\> \</small\> \</td\> \<!\-- Actions \--\> \<td\> \<button
type=\"button\" class=\"button button-small techrappy-view-batch-btn\"
data-batch-id=\"\<?php echo esc_attr( \$batch-\>id ); ?\>\" \> 👁️ \<?php
esc_html_e( \'Voir\', \'techrappy-seo\' ); ?\> \</button\> \<?php if (
in_array( \$batch-\>status, \[ \'pending\', \'running\' \], true ) ) :
?\> \<button type=\"button\" class=\"button button-small
techrappy-cancel-batch-btn techrappy-btn-danger\" data-batch-id=\"\<?php
echo esc_attr( \$batch-\>id ); ?\>\" \> 🛑 \<?php esc_html_e(
\'Annuler\', \'techrappy-seo\' ); ?\> \</button\> \<?php endif; ?\>
\</td\> \</tr\> \<?php endforeach; ?\> \</tbody\> \</table\>
\</div\>\<!\-- /#techrappy-bulk-history-card \--\> \<?php endif; ?\>
\<!\-- Nonce caché \--\> \<input type=\"hidden\"
id=\"techrappy-bulk-nonce\" value=\"\<?php echo esc_attr( \$nonce );
?\>\"/\> \<!\-- Batch actif (persisté en JS state) \--\> \<input
type=\"hidden\" id=\"techrappy-active-batch-id\" value=\"\"/\>
\</div\>\<!\-- /#techrappy-bulk-wrap \--\> \`\`\` \-\-- \## 9.
JavaScript --- \`bulk-admin.js\` \`\`\`javascript /\*\* \* bulk-admin.js
\* \* Gestion complète de l\'UI Génération de masse : \* - Compteur de
keywords en temps réel \* - Lancement du batch via AJAX \* - Polling du
statut (barre de progression + compteurs) \* - Affichage du tableau de
jobs \* - Annulation d\'un batch \* - Reprise du polling si batch en
cours au chargement \* \* \@package TechrappySEO \*/ /\* global jQuery,
techrappyBulkData \*/ (function (\$) { \'use strict\'; //
=========================================================================
// Config //
=========================================================================
const CFG = { ajaxUrl : techrappyBulkData.ajaxUrl, nonce :
techrappyBulkData.nonce, pollInterval : techrappyBulkData.pollInterval
\|\| 4000, actions : { launch : \'techrappy_bulk_launch\', poll :
\'techrappy_bulk_poll_status\', cancel : \'techrappy_bulk_cancel\',
getJobs : \'techrappy_bulk_get_jobs\', }, i18n : techrappyBulkData.i18n
\|\| {}, }; //
=========================================================================
// Sélecteurs DOM //
=========================================================================
const SEL = { wrap : \'#techrappy-bulk-wrap\', keywords :
\'#techrappy-bulk-keywords\', kwCount : \'#techrappy-kw-count\',
postType : \'#techrappy-bulk-post-type\', postStatus :
\'#techrappy-bulk-post-status\', launchBtn :
\'#techrappy-bulk-launch-btn\', formErrors :
\'#techrappy-bulk-form-errors\', formCard :
\'#techrappy-bulk-form-card\', progressCard :
\'#techrappy-bulk-progress-card\', progressTitle :
\'#techrappy-bulk-progress-title\', progressFill :
\'#techrappy-progress-fill\', progressPct : \'#techrappy-progress-pct\',
cntTotal : \'#techrappy-counter-total\', cntPending :
\'#techrappy-counter-pending\', cntDone : \'#techrappy-counter-done\',
cntFailed : \'#techrappy-counter-failed\', cancelBtn :
\'#techrappy-bulk-cancel-btn\', jobsTableWrap :
\'#techrappy-bulk-jobs-table-wrap\', loadMoreBtn :
\'#techrappy-bulk-load-more\', nonce : \'#techrappy-bulk-nonce\',
activeBatchId : \'#techrappy-active-batch-id\', historyRows :
\'.techrappy-view-batch-btn\', cancelRows :
\'.techrappy-cancel-batch-btn\', }; //
=========================================================================
// État interne //
=========================================================================
const state = { activeBatchId : null, // ID du batch en cours pollTimer
: null, // setInterval handle isPolling : false, jobsPage : 1,
jobsHasMore : false, isLaunching : false, }; //
=========================================================================
// Init //
=========================================================================
function init() { bindEvents(); initKeywordCounter(); // Reprend le
polling si un batch était en cours (page refresh) const storedBatchId =
parseInt( sessionStorage.getItem( \'techrappy_active_batch\' ), 10 ); if
( storedBatchId ) { state.activeBatchId = storedBatchId; \$(
SEL.activeBatchId ).val( storedBatchId ); showProgressCard();
startPolling(); } } //
=========================================================================
// Events //
=========================================================================
function bindEvents() { // Lancer un batch \$( document ).on( \'click\',
SEL.launchBtn, onLaunchClick ); // Annuler le batch actif \$( document
).on( \'click\', SEL.cancelBtn, onCancelActiveClick ); // Annuler depuis
l\'historique \$( document ).on( \'click\', SEL.cancelRows, function ()
{ const batchId = parseInt( \$( this ).data( \'batch-id\' ), 10 ); if (
batchId ) cancelBatch( batchId ); } ); // Voir les jobs d\'un batch
(historique) \$( document ).on( \'click\', SEL.historyRows, function ()
{ const batchId = parseInt( \$( this ).data( \'batch-id\' ), 10 ); if (
! batchId ) return; state.activeBatchId = batchId; state.jobsPage = 1;
showProgressCard(); loadJobsTable( batchId, 1 ); pollOnce( batchId ); //
snapshot immédiat } ); // Charger plus de jobs \$( document ).on(
\'click\', SEL.loadMoreBtn, function () { if ( ! state.activeBatchId )
return; state.jobsPage++; loadJobsTable( state.activeBatchId,
state.jobsPage, true ); } ); } //
=========================================================================
// Compteur keywords //
=========================================================================
function initKeywordCounter() { \$( document ).on( \'input\',
SEL.keywords, updateKeywordCount ); } function updateKeywordCount() {
const raw = \$( SEL.keywords ).val() \|\| \'\'; const lines = raw.split(
\'\\n\' ).filter( l =\> l.trim().length \>= 2 ); const unique = \[
\...new Set( lines.map( l =\> l.trim().toLowerCase() ) ) \]; \$(
SEL.kwCount ).text( unique.length ); // Colorisation si proche du max
const max = 500; \$( SEL.kwCount ) .toggleClass(
\'techrappy-count-warn\', unique.length \> max \* 0.8 ) .toggleClass(
\'techrappy-count-danger\', unique.length \>= max ); } //
=========================================================================
// Lancement du batch //
=========================================================================
function onLaunchClick() { if ( state.isLaunching ) return; const
keywords = \$( SEL.keywords ).val() \|\| \'\'; const postType = \$(
SEL.postType ).val() \|\| \'\'; const postStatus = \$( SEL.postStatus
).val() \|\| \'\'; hideFormErrors(); // Validation client légère if (
keywords.trim() === \'\' ) { showFormError( CFG.i18n.noKeywords \|\|
\'Saisissez au moins un mot-clé.\' ); return; } state.isLaunching =
true; setLaunchBtnLoading( true ); \$.ajax( { url : CFG.ajaxUrl, type :
\'POST\', data : { action : CFG.actions.launch, nonce : getNonce(),
keywords : keywords, post_type : postType, post_status : postStatus, },
success : function ( res ) { state.isLaunching = false;
setLaunchBtnLoading( false ); if ( ! res.success ) { const errors =
res.data?.errors \|\| \[ res.data?.message \|\| CFG.i18n.errorGeneric
\]; showFormError( errors.join( \'\<br/\>\' ) ); return; } // Succès :
démarre le suivi const batchId = parseInt( res.data.batch_id, 10 );
onBatchLaunched( batchId, res.data ); }, error : function ( xhr, status,
error ) { state.isLaunching = false; setLaunchBtnLoading( false );
showFormError( CFG.i18n.errorGeneric ); console.error(
\'\[TechrappyBulk\] launch error:\', error, xhr.responseText ); }, } );
} /\*\* \* Appelé après un lancement réussi. \* \* \@param {number}
batchId \* \@param {Object} data Réponse serveur. \*/ function
onBatchLaunched( batchId, data ) { state.activeBatchId = batchId;
state.jobsPage = 1; // Persiste en session pour survie au refresh
sessionStorage.setItem( \'techrappy_active_batch\', batchId ); \$(
SEL.activeBatchId ).val( batchId ); // Affiche les warnings éventuels if
( data.warnings && data.warnings.length \> 0 ) { showFormError( \'⚠️
\' + data.warnings.join( \'\<br/\>\' ), \'warning\' ); } // Met à jour
le compteur total immédiatement \$( SEL.cntTotal ).text( data.total \|\|
0 ); \$( SEL.progressTitle ).text( CFG.i18n.running \|\| \'Génération en
cours...\' ); showProgressCard(); startPolling(); } //
=========================================================================
// Polling du statut //
=========================================================================
/\*\* \* Lance le polling périodique. \*/ function startPolling() { if (
state.isPolling ) return; state.isPolling = true; // Premier appel
immédiat pollOnce( state.activeBatchId ); state.pollTimer = setInterval(
function () { if ( state.activeBatchId ) { pollOnce( state.activeBatchId
); } }, CFG.pollInterval ); } /\*\* \* Arrête le polling. \*/ function
stopPolling() { if ( state.pollTimer ) { clearInterval( state.pollTimer
); state.pollTimer = null; } state.isPolling = false; } /\*\* \* Un seul
appel de polling. \* \* \@param {number} batchId \*/ function pollOnce(
batchId ) { \$.ajax( { url : CFG.ajaxUrl, type : \'POST\', data : {
action : CFG.actions.poll, nonce : getNonce(), batch_id : batchId, },
success : function ( res ) { if ( ! res.success ) { console.warn(
\'\[TechrappyBulk\] poll error:\', res.data?.message ); return; }
updateProgressUI( res.data ); // Recharge le tableau de jobs à chaque
poll if ( state.jobsPage === 1 ) { loadJobsTable( batchId, 1 ); } //
Arrête le polling si terminé if ( res.data.is_complete ) {
stopPolling(); onBatchComplete( res.data ); } }, error : function ( xhr,
status, error ) { console.error( \'\[TechrappyBulk\] poll ajax error:\',
error ); }, } ); } /\*\* \* Met à jour la barre de progression et les
compteurs. \* \* \@param {Object} data Réponse du poll. \*/ function
updateProgressUI( data ) { const pct = data.progress \|\| 0; // Barre de
progression \$( SEL.progressFill ).css( \'width\', pct + \'%\' ); \$(
SEL.progressPct ).text( pct + \'%\' ); // Compteurs \$( SEL.cntTotal
).text( data.total \|\| 0 ); \$( SEL.cntPending ).text( data.pending
\|\| 0 ); \$( SEL.cntDone ).text( data.done_count \|\| 0 ); \$(
SEL.cntFailed ).text( data.failed_count \|\| 0 ); // Couleur barre selon
taux d\'échec const failRate = data.total \> 0 ? ( data.failed_count /
data.total ) : 0; \$( SEL.progressFill ) .toggleClass(
\'techrappy-progress-fill\--warn\', failRate \> 0.1 && failRate \<= 0.5
) .toggleClass( \'techrappy-progress-fill\--error\', failRate \> 0.5 );
} /\*\* \* Appelé quand le batch est terminé (done ou failed). \* \*
\@param {Object} data \*/ function onBatchComplete( data ) {
sessionStorage.removeItem( \'techrappy_active_batch\' ); const isDone =
data.status === \'done\'; const title = isDone ? ( CFG.i18n.done \|\|
\'✅ Terminé !\' ) : ( CFG.i18n.failed \|\| \'⚠️ Terminé avec erreurs.\'
); \$( SEL.progressTitle ).text( title ); // Masque le bouton Annuler
\$( SEL.cancelBtn ).hide(); // Recharge la page après 2s pour mettre à
jour l\'historique setTimeout( function () { location.reload(); }, 2000
); } //
=========================================================================
// Tableau de jobs //
=========================================================================
/\*\* \* Charge et affiche le tableau des jobs d\'un batch. \* \*
\@param {number} batchId \* \@param {number} page \* \@param {boolean}
append True = ajouter au tableau existant. \*/ function loadJobsTable(
batchId, page, append ) { \$.ajax( { url : CFG.ajaxUrl, type : \'POST\',
data : { action : CFG.actions.getJobs, nonce : getNonce(), batch_id :
batchId, page : page, }, success : function ( res ) { if ( ! res.success
\|\| ! res.data.jobs ) return; state.jobsHasMore = res.data.has_more;
renderJobsTable( res.data.jobs, append ); \$( SEL.loadMoreBtn ).toggle(
state.jobsHasMore ); }, error : function ( xhr, status, error ) {
console.error( \'\[TechrappyBulk\] getJobs error:\', error ); }, } ); }
/\*\* \* Rend le tableau HTML des jobs. \* \* \@param {Array} jobs \*
\@param {boolean} append \*/ function renderJobsTable( jobs, append ) {
if ( ! append ) { \$( SEL.jobsTableWrap ).empty(); } if ( jobs.length
=== 0 && ! append ) { \$( SEL.jobsTableWrap ).html( \'\<p
class=\"techrappy-muted\"\>\' + escHtml( \'Aucun job à afficher pour le
moment.\' ) + \'\</p\>\' ); return; } // Crée ou récupère le tableau let
\$table = \$( SEL.jobsTableWrap ).find( \'table.techrappy-jobs-table\'
); if ( \$table.length === 0 ) { \$table = \$( \` \<table
class=\"techrappy-jobs-table wp-list-table widefat fixed striped\"\>
\<thead\> \<tr\> \<th\>#\</th\> \<th\>Mot-clé\</th\> \<th\>Statut\</th\>
\<th\>Tentatives\</th\> \<th\>Post créé\</th\> \<th\>Erreur\</th\>
\</tr\> \</thead\> \<tbody class=\"techrappy-jobs-tbody\"\>\</tbody\>
\</table\> \` ); \$( SEL.jobsTableWrap ).append( \$table ); } const
\$tbody = \$table.find( \'.techrappy-jobs-tbody\' ); jobs.forEach(
function ( job ) { // Statut badge const badgeClass = { done :
\'techrappy-badge\--ok\', failed : \'techrappy-badge\--error\', running
: \'techrappy-badge\--running\', retrying : \'techrappy-badge\--warn\',
pending : \'techrappy-badge\--pending\', }\[ job.status \] \|\|
\'techrappy-badge\--pending\'; // Lien post si disponible const postCell
= job.post_id && job.post_edit_url ? \`\<a href=\"\${ escHtml(
job.post_edit_url ) }\" target=\"\_blank\" rel=\"noopener\"\> #\${
escHtml( String( job.post_id ) ) } ✏️ \</a\>\` : \'\<span
class=\"techrappy-muted\"\>---\</span\>\'; // Erreur (tronquée) const
errorCell = job.error_message ? \`\<span class=\"techrappy-error-msg\"
title=\"\${ escHtml( job.error_message ) }\"\> \${ escHtml( truncate(
job.error_message, 60 ) ) } \</span\>\` : \'\<span
class=\"techrappy-muted\"\>---\</span\>\'; // Cherche si la ligne existe
déjà (mise à jour en place) const existingRow = \$tbody.find(
\`tr\[data-job-id=\"\${ job.id }\"\]\` ); const rowHtml = \` \<tr
data-job-id=\"\${ escHtml( String( job.id ) ) }\" class=\"\${ job.status
=== \'failed\' ? \'techrappy-row\--error\' : \'\' }\"\> \<td
class=\"techrappy-muted\"\>\${ escHtml( String( job.id ) ) }\</td\>
\<td\>\<strong\>\${ escHtml( job.keyword ) }\</strong\>\</td\> \<td\>
\<span class=\"techrappy-badge \${ badgeClass }\"\> \${ escHtml(
job.status ) } \</span\> \</td\> \<td class=\"techrappy-muted\"\>\${
escHtml( String( job.attempts ) ) }\</td\> \<td\>\${ postCell }\</td\>
\<td\>\${ errorCell }\</td\> \</tr\> \`; if ( existingRow.length \> 0 )
{ existingRow.replaceWith( rowHtml ); } else { \$tbody.append( rowHtml
); } } ); } //
=========================================================================
// Annulation //
=========================================================================
function onCancelActiveClick() { if ( ! state.activeBatchId ) return;
cancelBatch( state.activeBatchId ); } /\*\* \* Annule un batch après
confirmation. \* \* \@param {number} batchId \*/ function cancelBatch(
batchId ) { if ( ! confirm( CFG.i18n.confirmCancel \|\| \'Annuler ce
batch ?\' ) ) { return; } \$.ajax( { url : CFG.ajaxUrl, type : \'POST\',
data : { action : CFG.actions.cancel, nonce : getNonce(), batch_id :
batchId, }, success : function ( res ) { if ( res.success ) {
stopPolling(); sessionStorage.removeItem( \'techrappy_active_batch\' );
\$( SEL.progressTitle ).text( CFG.i18n.cancelled \|\| \'Batch annulé.\'
); \$( SEL.cancelBtn ).hide(); setTimeout( () =\> location.reload(),
1500 ); } else { alert( res.data?.message \|\| CFG.i18n.errorGeneric );
} }, error : function ( xhr, status, error ) { console.error(
\'\[TechrappyBulk\] cancel error:\', error ); alert(
CFG.i18n.errorGeneric ); }, } ); } //
=========================================================================
// Helpers UI //
=========================================================================
function showProgressCard() { \$( SEL.progressCard ).slideDown( 200 );
\$( SEL.cancelBtn ).show(); } function showFormError( message, type ) {
const cls = type === \'warning\' ? \'techrappy-notice
techrappy-notice\--warning\' : \'techrappy-notice
techrappy-notice\--error\'; \$( SEL.formErrors ) .attr( \'class\', cls )
.html( message ) .slideDown( 200 ); } function hideFormErrors() { \$(
SEL.formErrors ).hide().empty(); } function setLaunchBtnLoading( loading
) { \$( SEL.launchBtn ) .prop( \'disabled\', loading ) .text( loading ?
( \'⏳ \' + ( CFG.i18n.launching \|\| \'Lancement...\' ) ) : ( \'⚡
Lancer la génération\' ) ); } function getNonce() { return \$( SEL.nonce
).val() \|\| CFG.nonce; } function escHtml( str ) { if ( str == null )
return \'\'; return String( str ) .replace( /&/g, \'&amp;\' ) .replace(
/\</g, \'&lt;\' ) .replace( /\>/g, \'&gt;\' ) .replace( /\"/g,
\'&quot;\') .replace( /\'/g, \'&#039;\'); } function truncate( str,
length ) { if ( ! str ) return \'\'; return str.length \> length ?
str.substring( 0, length ) + \'...\' : str; } //
=========================================================================
// Bootstrap //
=========================================================================
\$( document ).ready( init ); }( jQuery ) ); \`\`\` \-\-- \## 10. CSS
complet --- \`bulk-admin.css\` \`\`\`css /\*\* \* bulk-admin.css \*
Styles du module Génération de masse --- Techrappy SEO Admin \* \*
\@package TechrappySEO \*/ /\*
============================================================ Variables
(réutilise les variables du module Divi si dispo)
============================================================ \*/ :root {
\--tb-primary : #2271b1; \--tb-primary-dark : #135e96; \--tb-success :
#00a32a; \--tb-success-bg : #edfaef; \--tb-warning : #dba617;
\--tb-warning-bg : #fef8ee; \--tb-error : #d63638; \--tb-error-bg :
#fef0f0; \--tb-info-bg : #f0f6fc; \--tb-border : #c3c4c7;
\--tb-border-light : #e8e8e8; \--tb-bg-light : #f6f7f7; \--tb-text :
#1d2327; \--tb-text-muted : #646970; \--tb-radius : 6px; \--tb-radius-sm
: 3px; \--tb-shadow : 0 1px 4px rgba(0,0,0,.1); \--tb-font-mono :
\'SFMono-Regular\', Consolas, \'Liberation Mono\', monospace;
\--tb-transition : .18s ease; } /\*
============================================================ Layout
principal ============================================================
\*/ .techrappy-bulk-wrap { max-width : 1100px; margin : 20px 0; } /\*
============================================================ Header
============================================================ \*/
.techrappy-bulk-header { margin-bottom : 24px; } .techrappy-bulk-title {
font-size : 1.4rem; font-weight : 700; color : var(\--tb-text); margin :
0 0 6px; } .techrappy-bulk-subtitle { color : var(\--tb-text-muted);
font-size : .95rem; margin : 0; } /\*
============================================================ Cards
============================================================ \*/
.techrappy-bulk-card { background : #fff; border : 1px solid
var(\--tb-border); border-radius : var(\--tb-radius); padding : 24px;
margin-bottom : 20px; box-shadow : var(\--tb-shadow); }
.techrappy-bulk-card-title { font-size : 1rem; font-weight : 600; color
: var(\--tb-text); margin : 0 0 20px; padding-bottom : 12px;
border-bottom : 1px solid var(\--tb-border-light); display : flex;
align-items : center; gap : 6px; } /\*
============================================================ Formulaire
============================================================ \*/
.techrappy-bulk-form { display : flex; flex-direction : column; gap :
18px; } .techrappy-bulk-field { display : flex; flex-direction : column;
gap : 6px; } .techrappy-bulk-field label { font-weight : 500; font-size
: .9rem; color : var(\--tb-text); } .techrappy-required { color :
var(\--tb-error); margin-left : 2px; } .techrappy-bulk-textarea {
font-family : var(\--tb-font-mono); font-size : .85rem; border : 1px
solid var(\--tb-border); border-radius : var(\--tb-radius-sm); padding :
10px 12px; resize : vertical; min-height : 180px; color :
var(\--tb-text); transition : border-color var(\--tb-transition);
line-height : 1.6; } .techrappy-bulk-textarea:focus { border-color :
var(\--tb-primary); outline : 2px solid rgba(34,113,177,.2);
outline-offset : 1px; } .techrappy-kw-counter { font-size : .85rem;
color : var(\--tb-text-muted); font-weight : 500; } #techrappy-kw-count
{ font-size : 1.1rem; font-weight : 700; color : var(\--tb-primary);
transition : color var(\--tb-transition); }
#techrappy-kw-count.techrappy-count-warn { color : var(\--tb-warning); }
#techrappy-kw-count.techrappy-count-danger { color : var(\--tb-error); }
/\* Options en ligne \*/ .techrappy-bulk-options-row { display : flex;
gap : 20px; flex-wrap : wrap; } .techrappy-bulk-field\--inline { flex :
1; min-width : 180px; } .techrappy-bulk-select { padding : 8px 10px;
border : 1px solid var(\--tb-border); border-radius :
var(\--tb-radius-sm); font-size : .9rem; background : #fff; color :
var(\--tb-text); width : 100%; transition : border-color
var(\--tb-transition); } .techrappy-bulk-select:focus { border-color :
var(\--tb-primary); outline : 2px solid rgba(34,113,177,.2); } /\*
Bouton lancer \*/ .techrappy-bulk-actions { padding-top : 4px; }
.button-hero { height : auto; padding : 10px 28px; font-size : 1rem;
font-weight : 600; line-height : 1.4; } /\* Notices \*/
.techrappy-notice { padding : 12px 16px; border-left : 4px solid
transparent; border-radius : var(\--tb-radius-sm); font-size : .9rem;
line-height : 1.5; margin-top : 4px; } .techrappy-notice\--error {
background : var(\--tb-error-bg); border-color : var(\--tb-error); color
: #7b1315; } .techrappy-notice\--warning { background :
var(\--tb-warning-bg); border-color : var(\--tb-warning); color :
#6b4e00; } .techrappy-notice\--success { background :
var(\--tb-success-bg); border-color : var(\--tb-success); color :
#1a5e28; } /\*
============================================================ Carte
progression ============================================================
\*/ .techrappy-bulk-progress-header { display : flex; align-items :
center; justify-content : space-between; flex-wrap : wrap; gap : 12px;
margin-bottom : 20px; } .techrappy-btn-danger { color : var(\--tb-error)
!important; border-color : var(\--tb-error) !important; }
.techrappy-btn-danger:hover { background : var(\--tb-error-bg)
!important; } /\* Barre de progression \*/ .techrappy-progress-bar-wrap
{ margin-bottom : 20px; } .techrappy-progress-bar { background :
var(\--tb-bg-light); border : 1px solid var(\--tb-border-light);
border-radius : 20px; overflow : hidden; height : 28px; }
.techrappy-progress-fill { height : 100%; background :
var(\--tb-primary); border-radius : 20px; transition : width .5s ease,
background .3s ease; display : flex; align-items : center;
justify-content : flex-end; min-width : 2%; position : relative; }
.techrappy-progress-fill\--warn { background : var(\--tb-warning); }
.techrappy-progress-fill\--error { background : var(\--tb-error); }
.techrappy-progress-pct { font-size : .78rem; font-weight : 700; color :
#fff; padding : 0 10px; white-space : nowrap; text-shadow : 0 1px 2px
rgba(0,0,0,.3); } /\* Compteurs \*/ .techrappy-bulk-counters { display :
flex; gap : 12px; flex-wrap : wrap; margin-bottom : 20px; }
.techrappy-counter { flex : 1; min-width : 90px; background :
var(\--tb-bg-light); border : 1px solid var(\--tb-border-light);
border-radius : var(\--tb-radius); padding : 12px; text-align : center;
} .techrappy-counter\--done { background : var(\--tb-success-bg);
border-color : #b3e6bf; } .techrappy-counter\--failed { background :
var(\--tb-error-bg); border-color : #f5b8b8; }
.techrappy-counter\--pending { background : var(\--tb-info-bg);
border-color : #b3cfe6; } .techrappy-counter-value { display : block;
font-size : 1.8rem; font-weight : 700; color : var(\--tb-text);
line-height : 1; } .techrappy-counter-label { display : block; font-size
: .78rem; color : var(\--tb-text-muted); margin-top : 4px; } /\* Log des
jobs \*/ .techrappy-bulk-log-wrap h4 { font-size : .9rem; font-weight :
600; color : var(\--tb-text); margin : 0 0 10px; } /\*
============================================================ Tables
(jobs + historique)
============================================================ \*/
.techrappy-jobs-table, .techrappy-bulk-history-table { width : 100%;
border-collapse : collapse; font-size : .85rem; margin-bottom : 12px; }
.techrappy-jobs-table th, .techrappy-bulk-history-table th { background
: var(\--tb-bg-light); font-weight : 600; font-size : .8rem;
text-transform : uppercase; letter-spacing : .04em; color :
var(\--tb-text-muted); padding : 8px 12px; border-bottom : 2px solid
var(\--tb-border); white-space : nowrap; } .techrappy-jobs-table td,
.techrappy-bulk-history-table td { padding : 8px 12px; border-bottom :
1px solid var(\--tb-border-light); vertical-align : middle; }
.techrappy-row\--error td { background : var(\--tb-error-bg); }
.techrappy-error-msg { color : var(\--tb-error); font-size : .82rem;
cursor : help; } /\* Mini barre progression (historique) \*/
.techrappy-mini-progress { background : var(\--tb-border-light);
border-radius : 4px; height : 6px; width : 100%; margin-bottom : 4px;
overflow : hidden; } .techrappy-mini-progress-fill { height : 100%;
background : var(\--tb-primary); border-radius : 4px; transition : width
.3s ease; } /\*
============================================================ Badges
============================================================ \*/
.techrappy-badge { display : inline-flex; align-items : center; padding
: 2px 8px; border-radius : 20px; font-size : .78rem; font-weight : 600;
white-space : nowrap; border : 1px solid transparent; }
.techrappy-badge\--ok, .techrappy-badge\--done { background :
var(\--tb-success-bg); color : #1a5e28; border-color : #b3e6bf; }
.techrappy-badge\--error, .techrappy-badge\--failed { background :
var(\--tb-error-bg); color : var(\--tb-error); border-color : #f5b8b8; }
.techrappy-badge\--running { background : var(\--tb-info-bg); color :
var(\--tb-primary-dark); border-color : #b3cfe6; animation :
techrappy-pulse 1.4s ease-in-out infinite; } .techrappy-badge\--warn,
.techrappy-badge\--retrying { background : var(\--tb-warning-bg); color
: #6b4e00; border-color : #f0d57b; } .techrappy-badge\--pending {
background : #f0f0f1; color : var(\--tb-text-muted); border-color :
var(\--tb-border); } /\* Tags type contenu \*/ .techrappy-tag {
background : #e8f0fa; color : var(\--tb-primary-dark); border : 1px
solid #b8d0ec; border-radius : 3px; padding : 1px 6px; font-size :
.78rem; font-weight : 500; } /\*
============================================================ Divers
============================================================ \*/
.techrappy-muted { color : var(\--tb-text-muted); font-style : normal;
font-size : .85rem; } .techrappy-text-error { color : var(\--tb-error);
} /\* ============================================================
Animations ============================================================
\*/ \@keyframes techrappy-pulse { 0%, 100% { opacity: 1; } 50% {
opacity: .55; } } \@keyframes techrappy-fade-in { from { opacity: 0;
transform: translateY(-4px); } to { opacity: 1; transform:
translateY(0); } } .techrappy-bulk-card { animation : techrappy-fade-in
.2s ease forwards; } /\*
============================================================ Responsive
============================================================ \*/ \@media
screen and (max-width: 782px) { .techrappy-bulk-options-row {
flex-direction : column; gap : 12px; } .techrappy-bulk-field\--inline {
min-width : 100%; } .techrappy-bulk-counters { gap : 8px; }
.techrappy-counter { flex : none; width : calc(50% - 4px); min-width :
unset; } .techrappy-counter-value { font-size : 1.4rem; }
.techrappy-bulk-progress-header { flex-direction : column; align-items :
flex-start; } .techrappy-jobs-table th:nth-child(4),
.techrappy-jobs-table td:nth-child(4), .techrappy-jobs-table
th:nth-child(6), .techrappy-jobs-table td:nth-child(6) { display : none;
} .techrappy-bulk-history-table th:nth-child(5),
.techrappy-bulk-history-table td:nth-child(5) { display : none; }
.button-hero { width : 100%; text-align : center; } } \`\`\` \-\-- \##
11. Intégration plugin principal \`\`\`php \<?php /\*\* \* Intégration
du module Bulk dans le plugin principal. \* \* À ajouter dans la méthode
load_modules() du bootstrap existant. \* NE PAS modifier l\'architecture
existante. \* \* \@package TechrappySEO \*/ // ─── Dans load_modules()
────────────────────────────────────────────────────── private function
load_modules(): void { // \... modules existants validés \... // 🆕
Module Génération de masse \$bulk_module = new
\\TechrappySEO\\Modules\\Bulk\\BulkModule(); \$bulk_module-\>register();
} // ─── Dans register_activation_hook
─────────────────────────────────────────── // Dans la fonction
d\'activation du plugin (ou classe Activator existante) :
register_activation_hook( \_\_FILE\_\_, function () { // \... activation
existante \... // 🆕 Tables bulk
\\TechrappySEO\\Migrations\\CreateBulkTables::run(); } ); // ─── Dans
register_deactivation_hook (optionnel) ───────────────────────────── //
NE PAS appeler drop_tables() à la désactivation (perte de données). //
Réserver drop_tables() à la désinstallation complète (uninstall.php). //
─── Dans uninstall.php
────────────────────────────────────────────────────── // if ( defined(
\'WP_UNINSTALL_PLUGIN\' ) ) { //
\\TechrappySEO\\Migrations\\CreateBulkTables::drop_tables(); // } // ───
Branchement pipeline (dans le module SEO existant)
────────────────────── // Ajoute ce filtre dans le module qui gère la
création de post depuis keyword : add_filter(
\'techrappy_seo_create_post_from_keyword\', function ( \$result, string
\$keyword, string \$post_type, string \$post_status ) { // Appelle le
pipeline SEO existant // Remplacer par l\'appel réel à votre service de
génération : // \$pipeline = new
\\TechrappySEO\\Pipeline\\PostCreationPipeline(); // return
\$pipeline-\>run( compact( \'keyword\', \'post_type\', \'post_status\' )
); // Exemple minimal retournant un WP_Error si non implémenté : return
new \\WP_Error( \'not_implemented\', \'Pipeline non implémenté pour : \'
. \$keyword ); }, 10, 4 ); \`\`\` \-\-- \## Récapitulatif complet du
module \`\`\`
┌──────────────────────────────────────────────────────────────────────┐
│ MODULE GÉNÉRATION DE MASSE --- LIVRAISON COMPLÈTE │
├──────────────────────────────────────────────────────────────────────┤
│ │ │ DB (migrations/) │ │ └── CreateBulkTables 2 tables : batches +
jobs │ │ dbDelta() idempotent │ │ │ │ SERVICES (modules/bulk/) │ │ ├──
BulkBatchValidator Nettoyage + validation entrées UI │ │ ├──
BulkBatchRepository CRUD batches + jobs (\$wpdb-\>prepare) │ │ ├──
BulkQueueService Schedule/cancel via Action Scheduler │ │ ├──
BulkJobProcessor Callback AS + retry logic │ │ ├── BulkController AJAX
handlers + render page │ │ └── BulkModule Bootstrap + hooks + assets │ │
│ │ UI (admin/partials/) │ │ └── bulk-generator.php Formulaire +
progression + historique │ │ │ │ ASSETS │ │ ├── bulk-admin.js Polling +
tableau jobs + annulation │ │ └── bulk-admin.css Styles complets
responsive │ │ │ │ FLUX │ │ 1. Saisie keywords + type + statut →
validation BulkBatchValidator │ │ 2. Création batch DB → création jobs
bulk (1 INSERT multi-lignes) │ │ 3. Planification AS : 1 action par job
(délai échelonné +5s/job) │ │ 4. AS exécute
BulkJobProcessor::process_job() │ │ a. Appelle le pipeline via filtre │
│ techrappy_seo_create_post_from_keyword │ │ b. Succès → done_count++,
post_id enregistré │ │ c. Échec → retry (1x, délai 60s) │ │ d. Échec
définitif → failed_count++, error_message enregistré │ │ 5.
maybe_complete_batch() détecte la fin et clôt le batch │ │ 6. UI :
polling toutes les 4s → mise à jour barre + compteurs │ │ │ │ SÉCURITÉ │
│ ✅ wp_verify_nonce() sur toutes les actions AJAX │ │ ✅
current_user_can(\'manage_options\') │ │ ✅ \$wpdb-\>prepare() sur
toutes les requêtes │ │ ✅ sanitize_key() / sanitize_text_field() sur
tous les inputs │ │ ✅ absint() sur tous les IDs │ │ ✅ Retry max 2
tentatives (configurable via MAX_ATTEMPTS) │ │ ✅ Délai échelonné entre
jobs (anti-saturation serveur) │ │ │ │ POINT DE BRANCHEMENT PIPELINE │ │
Filtre : techrappy_seo_create_post_from_keyword │ │ Args : \$result,
\$keyword, \$post_type, \$post_status │ │ Return : int (post_id) \|
WP_Error │
└──────────────────────────────────────────────────────────────────────┘
\`\`\`
