\# V2 --- Client IA Centralisé (AIClient + PromptManager +
PromptRenderer) \-\-- \## Fichier 1 : \`includes/AI/AIClient.php\` ---
Client centralisé OpenAI \`\`\`php \<?php /\*\* \* Client IA centralisé
--- OpenAI Responses API. \* \* Responsabilité unique : envoyer des
requêtes à l\'API OpenAI (endpoint \* /v1/responses), gérer les erreurs,
timeouts, retries et logs. \* \* Conçu pour être extensible vers
d\'autres providers (Anthropic, etc.) \* via une interface commune
AIProviderInterface. \* \* \@package TechrappySEO\\AI \*/ declare(
strict_types=1 ); namespace TechrappySEO\\AI; use
TechrappySEO\\Settings\\SettingsRepository; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Class AIClient \* \* Client HTTP centralisé pour tous les
appels IA du plugin. \* Tous les appels IA du pipeline DOIVENT passer
par cette classe. \* \* Usage : \* \$client = new AIClient( \$logger );
\* \$response = \$client-\>generate( \$prompt, \$system_prompt,
\'json_object\' ); \*/ class AIClient { //
───────────────────────────────────────── // Constantes //
───────────────────────────────────────── /\*\* \* Endpoint OpenAI
Responses API. \*/ const OPENAI_ENDPOINT =
\'https://api.openai.com/v1/responses\'; /\*\* \* Nombre de tentatives
maximum avant d\'abandonner. \*/ const MAX_RETRIES = 2; /\*\* \* Délai
en secondes entre deux tentatives (exponentiel : 1s, 2s). \*/ const
RETRY_DELAY_BASE = 1; /\*\* \* Provider actif (extensibilité future).
\*/ const PROVIDER_OPENAI = \'openai\'; const PROVIDER_ANTHROPIC =
\'anthropic\'; // Prévu V2. // ─────────────────────────────────────────
// Propriétés // ───────────────────────────────────────── /\*\* \*
Instance du logger liée au job courant. \* \* \@var Logger\|null \*/
private ?Logger \$logger; /\*\* \* Provider IA actif. \* \* \@var string
\*/ private string \$provider; /\*\* \* Modèle utilisé (ex :
\'gpt-4o\'). \* \* \@var string \*/ private string \$model; /\*\* \*
Température de génération (0.0 à 2.0). \* \* \@var float \*/ private
float \$temperature; /\*\* \* Nombre maximum de tokens en sortie. \* \*
\@var int \*/ private int \$max_tokens; /\*\* \* Timeout HTTP en
secondes. \* \* \@var int \*/ private int \$timeout; /\*\* \* Mode debug
activé. \* \* \@var bool \*/ private bool \$debug; /\*\* \* Dernière
réponse brute reçue (pour debug/logs). \* \* \@var array\<string,
mixed\>\|null \*/ private ?array \$last_raw_response = null; /\*\* \*
Dernière erreur rencontrée. \* \* \@var string \*/ private string
\$last_error = \'\'; // ───────────────────────────────────────── //
Constructeur // ───────────────────────────────────────── /\*\* \*
Constructeur. \* \* \@param Logger\|null \$logger Logger du job courant
(null = pas de log job). \* \@param string \$provider Provider IA
(\'openai\' par défaut). \*/ public function \_\_construct( ?Logger
\$logger = null, string \$provider = self::PROVIDER_OPENAI ) {
\$this-\>logger = \$logger; \$this-\>provider = \$provider; // Charger
la configuration depuis SettingsRepository. \$this-\>model = (string)
SettingsRepository::get( \'openai_model\', \'gpt-4o\' );
\$this-\>temperature = (float) SettingsRepository::get(
\'openai_temperature\', 0.7 ); \$this-\>max_tokens = (int)
SettingsRepository::get( \'openai_max_tokens\', 4096 ); \$this-\>timeout
= (int) SettingsRepository::get( \'openai_timeout\', 60 );
\$this-\>debug = (bool) SettingsRepository::get( \'debug_mode\', false
); } // ───────────────────────────────────────── // API publique //
───────────────────────────────────────── /\*\* \* Point d\'entrée
principal --- génère une réponse IA. \* \* \@param string \$prompt
Prompt utilisateur (contenu de la requête). \* \@param string
\$system_prompt Instructions système (règles absolues, ton...). \*
\@param string \$response_format Format attendu : \'json_object\' ou
\'text\'. \* \@param array\<string, mixed\> \$overrides Surcharge
ponctuelle des paramètres (model, temperature...). \* \* \@return
AIResponse Objet réponse standardisé. \*/ public function generate(
string \$prompt, string \$system_prompt = \'\', string \$response_format
= \'json_object\', array \$overrides = \[\] ): AIResponse { // Vérifier
la clé API avant tout appel. \$api_key =
SettingsRepository::get_api_key(); if ( empty( \$api_key ) ) { return
\$this-\>make_error_response( \'missing_api_key\', \_\_( \'Clé API
OpenAI non configurée. Rendez-vous dans Techrappy SEO → Réglages.\',
\'techrappy-seo\' ) ); } // Appliquer les surcharges ponctuelles.
\$model = (string) ( \$overrides\[\'model\'\] ?? \$this-\>model );
\$temperature = (float) ( \$overrides\[\'temperature\'\] ??
\$this-\>temperature ); \$max_tokens = (int) (
\$overrides\[\'max_tokens\'\] ?? \$this-\>max_tokens ); // Construire le
payload selon le provider. \$payload = \$this-\>build_payload( \$prompt,
\$system_prompt, \$response_format, \$model, \$temperature, \$max_tokens
); // Logger le démarrage de l\'appel. \$this-\>log( \'ai_client\',
sprintf( \'Appel %s --- modèle: %s --- format: %s --- prompt: %d
chars\', strtoupper( \$this-\>provider ), \$model, \$response_format,
strlen( \$prompt ) ) ); // Exécuter avec retry. return
\$this-\>execute_with_retry( \$payload, \$api_key, \$response_format );
} /\*\* \* Surcharge du modèle pour un appel spécifique (fluent API). \*
\* \@param string \$model Nom du modèle OpenAI. \* \* \@return static
Retourne \$this pour chaînage. \*/ public function with_model( string
\$model ): static { \$clone = clone \$this; \$clone-\>model = \$model;
return \$clone; } /\*\* \* Surcharge de la température pour un appel
spécifique (fluent API). \* \* \@param float \$temperature Valeur entre
0.0 et 2.0. \* \* \@return static \*/ public function with_temperature(
float \$temperature ): static { \$clone = clone \$this;
\$clone-\>temperature = max( 0.0, min( 2.0, \$temperature ) ); return
\$clone; } /\*\* \* Retourne la dernière réponse brute reçue de l\'API
(pour debug). \* \* \@return array\<string, mixed\>\|null \*/ public
function get_last_raw_response(): ?array { return
\$this-\>last_raw_response; } /\*\* \* Retourne le message de la
dernière erreur rencontrée. \* \* \@return string \*/ public function
get_last_error(): string { return \$this-\>last_error; } //
───────────────────────────────────────── // Construction du payload //
───────────────────────────────────────── /\*\* \* Construit le payload
JSON pour l\'API OpenAI Responses API. \* \* Structure cible : \* { \*
\"model\": \"gpt-4o\", \* \"input\": \[ \* { \"role\": \"system\",
\"content\": \"\...\" }, \* { \"role\": \"user\", \"content\": \"\...\"
} \* \], \* \"text\": { \"format\": { \"type\": \"json_object\" } }, \*
\"max_output_tokens\": 4096, \* \"temperature\": 0.7 \* } \* \* \@param
string \$prompt Prompt utilisateur. \* \@param string \$system_prompt
Prompt système. \* \@param string \$response_format \'json_object\' ou
\'text\'. \* \@param string \$model Modèle à utiliser. \* \@param float
\$temperature Température. \* \@param int \$max_tokens Tokens max en
sortie. \* \* \@return array\<string, mixed\> \*/ private function
build_payload( string \$prompt, string \$system_prompt, string
\$response_format, string \$model, float \$temperature, int \$max_tokens
): array { // Construction des messages d\'entrée. \$input = \[\]; if (
! empty( \$system_prompt ) ) { \$input\[\] = \[ \'role\' =\> \'system\',
\'content\' =\> \$system_prompt, \]; } \$input\[\] = \[ \'role\' =\>
\'user\', \'content\' =\> \$prompt, \]; // Format de sortie texte
(Responses API). \$text_format = ( \'json_object\' === \$response_format
) ? \[ \'format\' =\> \[ \'type\' =\> \'json_object\' \] \] : \[
\'format\' =\> \[ \'type\' =\> \'text\' \] \]; return \[ \'model\' =\>
\$model, \'input\' =\> \$input, \'text\' =\> \$text_format,
\'max_output_tokens\' =\> \$max_tokens, \'temperature\' =\>
\$temperature, \]; } // ───────────────────────────────────────── //
Exécution HTTP avec retry // ─────────────────────────────────────────
/\*\* \* Exécute la requête HTTP avec mécanisme de retry exponentiel. \*
\* \@param array\<string, mixed\> \$payload Payload JSON de la requête.
\* \@param string \$api_key Clé API en clair. \* \@param string
\$response_format Format attendu. \* \* \@return AIResponse \*/ private
function execute_with_retry( array \$payload, string \$api_key, string
\$response_format ): AIResponse { \$attempt = 0; \$last_error = \'\';
while ( \$attempt \<= self::MAX_RETRIES ) { // Délai exponentiel entre
les tentatives (pas sur le premier essai). if ( \$attempt \> 0 ) {
\$delay = self::RETRY_DELAY_BASE \* \$attempt; \$this-\>log(
\'ai_client\', sprintf( \'Tentative %d/%d --- attente %ds avant
retry.\', \$attempt, self::MAX_RETRIES, \$delay ), Logger::LEVEL_WARNING
); sleep( \$delay ); // phpcs:ignore
WordPress.WP.AlternativeFunctions.rand_rand } \$start_time = microtime(
true ); \$http_response = \$this-\>do_http_request( \$payload, \$api_key
); \$duration_ms = (int) round( ( microtime( true ) - \$start_time ) \*
1000 ); // Erreur WordPress HTTP (réseau, timeout...). if ( is_wp_error(
\$http_response ) ) { \$last_error =
\$http_response-\>get_error_message(); \$this-\>log( \'ai_client\',
sprintf( \'Erreur HTTP (tentative %d) : %s\', \$attempt + 1,
\$last_error ), Logger::LEVEL_ERROR ); \$attempt++; continue; } //
Analyser la réponse HTTP. \$result = \$this-\>parse_http_response(
\$http_response, \$response_format, \$duration_ms ); // Succès :
retourner immédiatement. if ( \$result-\>is_success() ) { \$this-\>log(
\'ai_client\', sprintf( \'Réponse reçue en %dms --- input: %d tokens ---
output: %d tokens.\', \$duration_ms, \$result-\>get_input_tokens(),
\$result-\>get_output_tokens() ) ); return \$result; } // Erreur API
non-retriable (authentification, quota dépassé...). if (
\$this-\>is_non_retriable_error( \$result-\>get_error_code() ) ) {
\$this-\>log( \'ai_client\', sprintf( \'Erreur non-retriable (%s) :
%s\', \$result-\>get_error_code(), \$result-\>get_error_message() ),
Logger::LEVEL_ERROR ); return \$result; } // Erreur retriable.
\$last_error = \$result-\>get_error_message(); \$this-\>log(
\'ai_client\', sprintf( \'Erreur API retriable (tentative %d) : %s\',
\$attempt + 1, \$last_error ), Logger::LEVEL_WARNING ); \$attempt++; }
// Toutes les tentatives ont échoué. \$this-\>last_error = \$last_error;
return \$this-\>make_error_response( \'max_retries_exceeded\', sprintf(
\_\_( \'Échec après %d tentatives. Dernière erreur : %s\',
\'techrappy-seo\' ), self::MAX_RETRIES + 1, \$last_error ) ); } //
───────────────────────────────────────── // Requête HTTP WordPress //
───────────────────────────────────────── /\*\* \* Effectue la requête
HTTP via wp_remote_post(). \* \* \@param array\<string, mixed\>
\$payload Payload JSON. \* \@param string \$api_key Clé API OpenAI. \*
\* \@return array\<string, mixed\>\|\\WP_Error Réponse WordPress ou
WP_Error. \*/ private function do_http_request( array \$payload, string
\$api_key ): array\|\\WP_Error { \$endpoint = \$this-\>get_endpoint();
\$args = \[ \'method\' =\> \'POST\', \'timeout\' =\> \$this-\>timeout,
\'headers\' =\> \[ \'Content-Type\' =\> \'application/json\',
\'Authorization\' =\> \'Bearer \' . \$api_key, \], \'body\' =\>
wp_json_encode( \$payload ), // Désactiver le SSL verify en dev si debug
activé. \'sslverify\' =\> ! ( defined( \'WP_DEBUG\' ) && WP_DEBUG &&
\$this-\>debug ), \]; // Permettre la modification des args via filtre
(extensibilité). \$args = apply_filters(
\'techrappy_seo_ai_request_args\', \$args, \$payload, \$this-\>provider
); if ( \$this-\>debug ) { \$this-\>log( \'ai_client\', \'Payload envoyé
: \' . wp_json_encode( \$payload ), Logger::LEVEL_DEBUG ); } return
wp_remote_post( \$endpoint, \$args ); } /\*\* \* Retourne l\'endpoint
API selon le provider actif. \* \* \@return string URL de l\'endpoint.
\*/ private function get_endpoint(): string { return match (
\$this-\>provider ) { self::PROVIDER_OPENAI =\> self::OPENAI_ENDPOINT,
// Prévu V2 : // self::PROVIDER_ANTHROPIC =\>
\'https://api.anthropic.com/v1/messages\', default =\>
self::OPENAI_ENDPOINT, }; } // ─────────────────────────────────────────
// Parsing de la réponse HTTP //
───────────────────────────────────────── /\*\* \* Parse et valide la
réponse HTTP de l\'API OpenAI. \* \* \@param array\<string, mixed\>
\$http_response Réponse wp_remote_post. \* \@param string
\$response_format Format attendu. \* \@param int \$duration_ms Durée de
l\'appel en ms. \* \* \@return AIResponse \*/ private function
parse_http_response( array \$http_response, string \$response_format,
int \$duration_ms ): AIResponse { \$http_code = (int)
wp_remote_retrieve_response_code( \$http_response ); \$body =
wp_remote_retrieve_body( \$http_response ); // Logger la réponse brute
en mode debug. if ( \$this-\>debug ) { \$this-\>log( \'ai_client\',
sprintf( \'HTTP %d --- Body (500 chars) : %s\', \$http_code, substr(
\$body, 0, 500 ) ), Logger::LEVEL_DEBUG ); } // Décoder le JSON de la
réponse. \$data = json_decode( \$body, true ); if ( JSON_ERROR_NONE !==
json_last_error() \|\| ! is_array( \$data ) ) { return
\$this-\>make_error_response( \'invalid_json_response\', \_\_( \'La
réponse de l\\\'API n\\\'est pas un JSON valide.\', \'techrappy-seo\' ),
\$http_code ); } // Stocker la réponse brute pour debug.
\$this-\>last_raw_response = \$data; // Erreur API côté serveur. if (
\$http_code \>= 400 ) { return \$this-\>parse_api_error( \$data,
\$http_code ); } // Extraire le contenu selon la structure Responses
API. return \$this-\>extract_content( \$data, \$response_format,
\$duration_ms ); } /\*\* \* Parse une erreur retournée par l\'API
OpenAI. \* \* Structure d\'erreur OpenAI : \* { \"error\": {
\"message\": \"\...\", \"type\": \"\...\", \"code\": \"\...\" } } \* \*
\@param array\<string, mixed\> \$data Corps de la réponse décodé. \*
\@param int \$http_code Code HTTP reçu. \* \* \@return AIResponse \*/
private function parse_api_error( array \$data, int \$http_code ):
AIResponse { \$error_message = \$data\[\'error\'\]\[\'message\'\] ??
\_\_( \'Erreur API inconnue.\', \'techrappy-seo\' ); \$error_code =
\$data\[\'error\'\]\[\'code\'\] ?? (string) \$http_code; \$error_type =
\$data\[\'error\'\]\[\'type\'\] ?? \'api_error\'; \$this-\>last_error =
\$error_message; \$this-\>log( \'ai_client\', sprintf( \'Erreur API
OpenAI \[%s/%s\] HTTP %d : %s\', \$error_type, \$error_code,
\$http_code, \$error_message ), Logger::LEVEL_ERROR ); return
\$this-\>make_error_response( \$error_code, \$error_message, \$http_code
); } /\*\* \* Extrait le contenu textuel depuis la réponse Responses
API. \* \* Structure de réponse OpenAI Responses API : \* { \*
\"output\": \[ \* { \* \"type\": \"message\", \* \"content\": \[ \* {
\"type\": \"output_text\", \"text\": \"\...\" } \* \] \* } \* \], \*
\"usage\": { \* \"input_tokens\": 150, \* \"output_tokens\": 300 \* } \*
} \* \* \@param array\<string, mixed\> \$data Réponse décodée. \*
\@param string \$response_format Format attendu. \* \@param int
\$duration_ms Durée appel. \* \* \@return AIResponse \*/ private
function extract_content( array \$data, string \$response_format, int
\$duration_ms ): AIResponse { // Extraire le texte depuis
output\[0\].content\[0\].text \$raw_text =
\$this-\>extract_text_from_output( \$data ); if ( null === \$raw_text )
{ return \$this-\>make_error_response( \'empty_output\', \_\_( \'La
réponse de l\\\'API ne contient aucun texte exploitable.\',
\'techrappy-seo\' ) ); } // Extraction des tokens de facturation.
\$input_tokens = (int) ( \$data\[\'usage\'\]\[\'input_tokens\'\] ?? 0 );
\$output_tokens = (int) ( \$data\[\'usage\'\]\[\'output_tokens\'\] ?? 0
); // Si JSON attendu : valider + décoder. if ( \'json_object\' ===
\$response_format ) { return \$this-\>parse_json_content( \$raw_text,
\$input_tokens, \$output_tokens, \$duration_ms ); } // Format texte :
retourner tel quel. return AIResponse::success( content: \$raw_text,
parsed: null, input_tokens: \$input_tokens, output_tokens:
\$output_tokens, duration_ms: \$duration_ms, raw:
\$this-\>last_raw_response ?? \[\] ); } /\*\* \* Extrait la chaîne de
texte depuis la structure output de Responses API. \* \* \@param
array\<string, mixed\> \$data Réponse API décodée. \* \* \@return
string\|null Texte extrait ou null si structure inattendue. \*/ private
function extract_text_from_output( array \$data ): ?string { //
Responses API : output est un tableau de blocs. if ( ! isset(
\$data\[\'output\'\] ) \|\| ! is_array( \$data\[\'output\'\] ) ) {
return null; } foreach ( \$data\[\'output\'\] as \$output_block ) { if (
! is_array( \$output_block ) ) { continue; } // Type \"message\"
contient les réponses textuelles. if ( ( \$output_block\[\'type\'\] ??
\'\' ) !== \'message\' ) { continue; } if ( ! isset(
\$output_block\[\'content\'\] ) \|\| ! is_array(
\$output_block\[\'content\'\] ) ) { continue; } foreach (
\$output_block\[\'content\'\] as \$content_block ) { if ( ! is_array(
\$content_block ) ) { continue; } // Type \"output_text\" contient le
texte généré. if ( ( \$content_block\[\'type\'\] ?? \'\' ) ===
\'output_text\' && isset( \$content_block\[\'text\'\] ) ) { return
(string) \$content_block\[\'text\'\]; } } } return null; } /\*\* \*
Parse et valide le contenu JSON d\'une réponse. \* \* \@param string
\$raw_text Texte brut retourné par l\'API. \* \@param int \$input_tokens
Tokens d\'entrée. \* \@param int \$output_tokens Tokens de sortie. \*
\@param int \$duration_ms Durée. \* \* \@return AIResponse \*/ private
function parse_json_content( string \$raw_text, int \$input_tokens, int
\$output_tokens, int \$duration_ms ): AIResponse { // Nettoyer les
éventuels blocs markdown \`\`\`json \... \`\`\`. \$clean_text =
\$this-\>strip_markdown_code_blocks( \$raw_text ); \$parsed =
json_decode( \$clean_text, true ); if ( JSON_ERROR_NONE !==
json_last_error() \|\| ! is_array( \$parsed ) ) { \$this-\>log(
\'ai_client\', sprintf( \'JSON invalide reçu : %s\', substr( \$raw_text,
0, 300 ) ), Logger::LEVEL_ERROR ); return \$this-\>make_error_response(
\'invalid_json_content\', sprintf( \_\_( \'Le contenu retourné par
l\\\'IA n\\\'est pas un JSON valide : %s\', \'techrappy-seo\' ),
json_last_error_msg() ) ); } return AIResponse::success( content:
\$clean_text, parsed: \$parsed, input_tokens: \$input_tokens,
output_tokens: \$output_tokens, duration_ms: \$duration_ms, raw:
\$this-\>last_raw_response ?? \[\] ); } //
───────────────────────────────────────── // Helpers //
───────────────────────────────────────── /\*\* \* Supprime les blocs
markdown \`\`\`json \... \`\`\` du texte. \* Les modèles LLM ajoutent
parfois des balises markdown même en mode JSON. \* \* \@param string
\$text Texte brut. \* \* \@return string Texte nettoyé. \*/ private
function strip_markdown_code_blocks( string \$text ): string { \$text =
trim( \$text ); // Supprimer \`\`\`json \... \`\`\` ou \`\`\` \...
\`\`\`. if ( preg_match(
\'/\^\`\`\`(?:json)?\\s\*(\[\\s\\S\]\*?)\\s\*\`\`\`\$/m\', \$text,
\$matches ) ) { return trim( \$matches\[1\] ); } return \$text; } /\*\*
\* Détermine si une erreur API est non-retriable. \* Évite les retries
inutiles sur des erreurs permanentes. \* \* \@param string \$error_code
Code d\'erreur OpenAI. \* \* \@return bool True si l\'erreur ne doit pas
être retentée. \*/ private function is_non_retriable_error( string
\$error_code ): bool { \$non_retriable = \[ \'invalid_api_key\',
\'insufficient_quota\', \'invalid_request_error\', \'model_not_found\',
\'401\', \'403\', \'404\', \]; return in_array( \$error_code,
\$non_retriable, true ); } /\*\* \* Crée un AIResponse d\'erreur
standardisé. \* \* \@param string \$code Code d\'erreur. \* \@param
string \$message Message d\'erreur lisible. \* \@param int \$http_code
Code HTTP (optionnel). \* \* \@return AIResponse \*/ private function
make_error_response( string \$code, string \$message, int \$http_code =
0 ): AIResponse { \$this-\>last_error = \$message; return
AIResponse::error( error_code: \$code, error_message: \$message,
http_code: \$http_code ); } /\*\* \* Écrit un message de log via le
Logger du job ou error_log en fallback. \* \* \@param string \$step
Étape concernée. \* \@param string \$message Message. \* \@param string
\$level Niveau de log. \* \* \@return void \*/ private function log(
string \$step, string \$message, string \$level = Logger::LEVEL_INFO ):
void { if ( \$this-\>logger instanceof Logger ) { \$this-\>logger-\>log(
\$step, \$message, \$level ); return; } // Fallback si pas de logger de
job (appel standalone / test). if ( \$this-\>debug ) { // phpcs:ignore
WordPress.PHP.DevelopmentFunctions.error_log_error_log error_log(
\"\[TechrappySEO\]\[{\$level}\]\[{\$step}\] {\$message}\" ); } } }
\`\`\` \-\-- \## Fichier 2 : \`includes/AI/AIResponse.php\` --- Objet
réponse standardisé \`\`\`php \<?php /\*\* \* Objet de réponse
standardisé retourné par AIClient::generate(). \* \* Encapsule : contenu
texte, données parsées (JSON), tokens, durée, \* état succès/erreur,
code et message d\'erreur. \* \* \@package TechrappySEO\\AI \*/ declare(
strict_types=1 ); namespace TechrappySEO\\AI; if ( ! defined(
\'ABSPATH\' ) ) { exit; } /\*\* \* Class AIResponse \* \* Value Object
--- immuable après création. \*/ final class AIResponse { //
───────────────────────────────────────── // Propriétés (readonly PHP
8.1+) // ───────────────────────────────────────── /\*\* \* Indique si
l\'appel a réussi. \* \* \@var bool \*/ private bool \$success; /\*\* \*
Contenu textuel brut retourné par l\'IA. \* \* \@var string \*/ private
string \$content; /\*\* \* Données JSON parsées (null si format texte ou
si erreur). \* \* \@var array\<string, mixed\>\|null \*/ private ?array
\$parsed; /\*\* \* Nombre de tokens en entrée (prompt). \* \* \@var int
\*/ private int \$input_tokens; /\*\* \* Nombre de tokens en sortie
(completion). \* \* \@var int \*/ private int \$output_tokens; /\*\* \*
Durée de l\'appel API en millisecondes. \* \* \@var int \*/ private int
\$duration_ms; /\*\* \* Réponse brute complète de l\'API (pour debug).
\* \* \@var array\<string, mixed\> \*/ private array \$raw; /\*\* \*
Code d\'erreur (vide si succès). \* \* \@var string \*/ private string
\$error_code; /\*\* \* Message d\'erreur lisible (vide si succès). \* \*
\@var string \*/ private string \$error_message; /\*\* \* Code HTTP reçu
(0 si non applicable). \* \* \@var int \*/ private int \$http_code; //
───────────────────────────────────────── // Constructeur privé ---
factory methods // ───────────────────────────────────────── /\*\* \*
Constructeur privé --- utiliser les factory methods. \*/ private
function \_\_construct() {} /\*\* \* Factory : crée une réponse de
succès. \* \* \@param string \$content Texte brut. \* \@param
array\<string,mixed\>\|null \$parsed JSON parsé ou null. \* \@param int
\$input_tokens Tokens entrée. \* \@param int \$output_tokens Tokens
sortie. \* \@param int \$duration_ms Durée appel. \* \@param
array\<string, mixed\> \$raw Réponse brute API. \* \* \@return self \*/
public static function success( string \$content, ?array \$parsed, int
\$input_tokens, int \$output_tokens, int \$duration_ms, array \$raw =
\[\] ): self { \$instance = new self(); \$instance-\>success = true;
\$instance-\>content = \$content; \$instance-\>parsed = \$parsed;
\$instance-\>input_tokens = \$input_tokens; \$instance-\>output_tokens =
\$output_tokens; \$instance-\>duration_ms = \$duration_ms;
\$instance-\>raw = \$raw; \$instance-\>error_code = \'\';
\$instance-\>error_message = \'\'; \$instance-\>http_code = 200; return
\$instance; } /\*\* \* Factory : crée une réponse d\'erreur. \* \*
\@param string \$error_code Code d\'erreur machine. \* \@param string
\$error_message Message d\'erreur lisible. \* \@param int \$http_code
Code HTTP (0 si N/A). \* \* \@return self \*/ public static function
error( string \$error_code, string \$error_message, int \$http_code = 0
): self { \$instance = new self(); \$instance-\>success = false;
\$instance-\>content = \'\'; \$instance-\>parsed = null;
\$instance-\>input_tokens = 0; \$instance-\>output_tokens = 0;
\$instance-\>duration_ms = 0; \$instance-\>raw = \[\];
\$instance-\>error_code = \$error_code; \$instance-\>error_message =
\$error_message; \$instance-\>http_code = \$http_code; return
\$instance; } // ───────────────────────────────────────── // Accesseurs
// ───────────────────────────────────────── /\*\* \@return bool \*/
public function is_success(): bool { return \$this-\>success; } /\*\*
\@return bool \*/ public function is_error(): bool { return !
\$this-\>success; } /\*\* \@return string \*/ public function
get_content(): string { return \$this-\>content; } /\*\* \* Retourne les
données JSON parsées. \* \* \@return array\<string, mixed\>\|null \*/
public function get_parsed(): ?array { return \$this-\>parsed; } /\*\*
\* Accès à une clé spécifique du JSON parsé. \* \* \@param string \$key
Clé à lire. \* \@param mixed \$default Valeur par défaut. \* \* \@return
mixed \*/ public function get( string \$key, mixed \$default = null ):
mixed { return \$this-\>parsed\[ \$key \] ?? \$default; } /\*\* \@return
int \*/ public function get_input_tokens(): int { return
\$this-\>input_tokens; } /\*\* \@return int \*/ public function
get_output_tokens(): int { return \$this-\>output_tokens; } /\*\*
\@return int \*/ public function get_total_tokens(): int { return
\$this-\>input_tokens + \$this-\>output_tokens; } /\*\* \@return int \*/
public function get_duration_ms(): int { return \$this-\>duration_ms; }
/\*\* \@return string \*/ public function get_error_code(): string {
return \$this-\>error_code; } /\*\* \@return string \*/ public function
get_error_message(): string { return \$this-\>error_message; } /\*\*
\@return int \*/ public function get_http_code(): int { return
\$this-\>http_code; } /\*\* \* Retourne la réponse brute complète (pour
debug/logs). \* \* \@return array\<string, mixed\> \*/ public function
get_raw(): array { return \$this-\>raw; } /\*\* \* Sérialise la réponse
en tableau (pour stockage en DB dans steps_data). \* \* \@return
array\<string, mixed\> \*/ public function to_array(): array { return \[
\'success\' =\> \$this-\>success, \'content\' =\> \$this-\>content,
\'parsed\' =\> \$this-\>parsed, \'input_tokens\' =\>
\$this-\>input_tokens, \'output_tokens\' =\> \$this-\>output_tokens,
\'duration_ms\' =\> \$this-\>duration_ms, \'error_code\' =\>
\$this-\>error_code, \'error_message\' =\> \$this-\>error_message,
\'http_code\' =\> \$this-\>http_code, \]; } } \`\`\` \-\-- \## Fichier 3
: \`includes/AI/PromptRenderer.php\` --- Injection des variables
\`\`\`php \<?php /\*\* \* Moteur de rendu des templates de prompts. \*
\* Responsabilité : remplacer les variables {{nom_variable}} dans un \*
template de prompt par leurs valeurs effectives avant envoi à l\'IA. \*
\* \@package TechrappySEO\\AI \*/ declare( strict_types=1 ); namespace
TechrappySEO\\AI; if ( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \*
Class PromptRenderer \* \* Remplace les tokens {{variable}} dans les
templates de prompts. \* \* Usage : \* \$renderer = new
PromptRenderer(); \* \$prompt = \$renderer-\>render( \$template,
\[\'mot_cle\' =\> \'ostéopathe Beauzelle\'\] ); \*/ class PromptRenderer
{ /\*\* \* Pattern regex de détection des variables dans un template. \*
Correspond à {{nom_variable}} avec lettres, chiffres, underscores,
tirets. \*/ const VARIABLE_PATTERN =
\'/\\{\\{(\[a-zA-Z0-9\_\\-\]+)\\}\\}/\'; /\*\* \* Rend un template de
prompt en substituant toutes les variables. \* \* \@param string
\$template Template brut contenant des {{variables}}. \* \@param
array\<string, mixed\> \$variables Tableau associatif variable =\>
valeur. \* \@param bool \$strict Si true : lève une exception si
variable manquante. \* \* \@return string Prompt rendu, prêt à l\'envoi.
\* \* \@throws \\InvalidArgumentException Si strict=true et variable
manquante. \*/ public function render( string \$template, array
\$variables = \[\], bool \$strict = false ): string { // Normaliser
toutes les valeurs en string. \$normalized =
\$this-\>normalize_variables( \$variables ); // Remplacer chaque
{{variable}} dans le template. \$rendered = preg_replace_callback(
self::VARIABLE_PATTERN, function ( array \$matches ) use ( \$normalized,
\$strict, \$template ): string { \$var_name = \$matches\[1\]; if ( !
array_key_exists( \$var_name, \$normalized ) ) { if ( \$strict ) { throw
new \\InvalidArgumentException( sprintf( \'Variable de prompt manquante
: {{%s}} dans le template.\', \$var_name ) ); } // Mode non-strict :
laisser le placeholder tel quel (visible pour debug). return
\$matches\[0\]; } return \$normalized\[ \$var_name \]; }, \$template );
return \$rendered ?? \$template; } /\*\* \* Extrait la liste des noms de
variables présentes dans un template. \* \* Utile pour le Prompt Studio
(affichage des variables disponibles \* et génération automatique des
champs de test). \* \* \@param string \$template Template de prompt. \*
\* \@return string\[\] Liste unique des noms de variables (sans les {{
}}). \*/ public function extract_variables( string \$template ): array {
\$matches = \[\]; preg_match_all( self::VARIABLE_PATTERN, \$template,
\$matches ); // Dédupliquer et retourner. return array_values(
array_unique( \$matches\[1\] ?? \[\] ) ); } /\*\* \* Vérifie qu\'un
template contient toutes les variables requises. \* \* \@param string
\$template Template à vérifier. \* \@param string\[\]
\$required_variables Variables attendues. \* \* \@return array{missing:
string\[\], found: string\[\]} Rapport de validation. \*/ public
function validate_template( string \$template, array
\$required_variables ): array { \$found = \$this-\>extract_variables(
\$template ); \$missing = array_diff( \$required_variables, \$found );
return \[ \'found\' =\> \$found, \'missing\' =\> array_values( \$missing
), \]; } /\*\* \* Normalise toutes les valeurs du tableau de variables
en chaînes de caractères. \* Les tableaux et objets sont sérialisés en
JSON. \* \* \@param array\<string, mixed\> \$variables Variables brutes.
\* \* \@return array\<string, string\> Variables normalisées en strings.
\*/ private function normalize_variables( array \$variables ): array {
\$normalized = \[\]; foreach ( \$variables as \$key =\> \$value ) {
\$normalized\[ (string) \$key \] = \$this-\>value_to_string( \$value );
} return \$normalized; } /\*\* \* Convertit une valeur quelconque en
chaîne de caractères. \* \* \@param mixed \$value Valeur à convertir. \*
\* \@return string \*/ private function value_to_string( mixed \$value
): string { if ( is_string( \$value ) ) { return \$value; } if (
is_bool( \$value ) ) { return \$value ? \'true\' : \'false\'; } if (
is_null( \$value ) ) { return \'\'; } if ( is_array( \$value ) \|\|
is_object( \$value ) ) { \$encoded = wp_json_encode( \$value,
JSON_UNESCAPED_UNICODE \| JSON_PRETTY_PRINT ); return \$encoded !==
false ? \$encoded : \'\'; } return (string) \$value; } } \`\`\` \-\--
\## Fichier 4 : \`includes/AI/PromptManager.php\` --- Chargement des
prompts \`\`\`php \<?php /\*\* \* Gestionnaire de prompts --- charge,
assemble et rend les prompts IA. \* \* Responsabilité : être le point
d\'entrée unique pour obtenir un prompt \* prêt à l\'envoi. Combine
PromptRepository + PromptRenderer. \* \* \@package TechrappySEO\\AI \*/
declare( strict_types=1 ); namespace TechrappySEO\\AI; use
TechrappySEO\\Prompts\\PromptRepository; use
TechrappySEO\\Prompts\\DefaultPrompts; if ( ! defined( \'ABSPATH\' ) ) {
exit; } /\*\* \* Class PromptManager \* \* Usage : \* \$manager = new
PromptManager(); \* \$result = \$manager-\>get_rendered( \'intent\',
\[\'mot_cle\' =\> \'ostéopathe Beauzelle\'\] ); \* //
\$result\[\'prompt\'\] → string prêt à envoyer \* //
\$result\[\'system\'\] → system prompt rendu \* //
\$result\[\'format\'\] → \'json_object\' ou \'text\' \*/ class
PromptManager { /\*\* \* Instance du repository de prompts. \* \* \@var
PromptRepository \*/ private PromptRepository \$repository; /\*\* \*
Instance du moteur de rendu. \* \* \@var PromptRenderer \*/ private
PromptRenderer \$renderer; /\*\* \* Cache en mémoire des prompts chargés
(évite N requêtes DB). \* \* \@var array\<string, array\<string,
mixed\>\> \*/ private array \$cache = \[\]; /\*\* \* Constructeur. \*/
public function \_\_construct() { \$this-\>repository = new
PromptRepository(); \$this-\>renderer = new PromptRenderer(); } //
───────────────────────────────────────── // API publique //
───────────────────────────────────────── /\*\* \* Retourne un prompt
rendu (variables injectées) et le system prompt. \* \* \@param string
\$prompt_key Clé du prompt (ex: \'intent\', \'plan\'...). \* \@param
array\<string, mixed\> \$variables Variables à injecter dans le
template. \* \@param bool \$strict Lever une exception si variable
manquante. \* \* \@return array{prompt: string, system: string, format:
string} \* \* \@throws \\RuntimeException Si le prompt n\'existe pas en
DB ni dans les defaults. \*/ public function get_rendered( string
\$prompt_key, array \$variables = \[\], bool \$strict = false ): array {
// Charger le template du prompt. \$prompt_data = \$this-\>load_prompt(
\$prompt_key ); if ( null === \$prompt_data ) { throw new
\\RuntimeException( sprintf( \'Prompt introuvable : \"%s\". Vérifiez la
table wp_techrappy_prompts.\', \$prompt_key ) ); } // Charger le system
prompt. \$system_data = \$this-\>load_prompt( \'system\' ); \$system_raw
= \$system_data\[\'content\'\] ?? \'\'; // Rendre le prompt utilisateur
avec les variables. \$rendered_prompt = \$this-\>renderer-\>render(
\$prompt_data\[\'content\'\], \$variables, \$strict ); // Rendre aussi
le system prompt (peut contenir des variables globales).
\$rendered_system = \$this-\>renderer-\>render( \$system_raw,
\$variables, false // Jamais strict pour le system prompt. ); return \[
\'prompt\' =\> \$rendered_prompt, \'system\' =\> \$rendered_system,
\'format\' =\> \$prompt_data\[\'response_format\'\] ?? \'json_object\',
\]; } /\*\* \* Retourne les variables détectées dans un template de
prompt. \* Utilisé par le Prompt Studio pour afficher les champs de
test. \* \* \@param string \$prompt_key Clé du prompt. \* \* \@return
string\[\] Liste des noms de variables {{\...}}. \*/ public function
get_variables_for( string \$prompt_key ): array { \$prompt_data =
\$this-\>load_prompt( \$prompt_key ); if ( null === \$prompt_data ) {
return \[\]; } return \$this-\>renderer-\>extract_variables(
\$prompt_data\[\'content\'\] ); } /\*\* \* Retourne tous les prompts
actifs (pour le Prompt Studio). \* \* \@return array\<string,
array\<string, mixed\>\> \* Tableau indexé par prompt_key. \*/ public
function get_all(): array { \$rows = \$this-\>repository-\>find_all();
\$result = \[\]; foreach ( \$rows as \$row ) { \$key =
\$row\[\'prompt_key\'\] ?? \'\'; \$result\[ \$key \] = \$row; } return
\$result; } /\*\* \* Retourne le template brut d\'un prompt (non rendu).
\* Utile pour l\'affichage dans l\'éditeur Prompt Studio. \* \* \@param
string \$prompt_key Clé du prompt. \* \* \@return string Template brut
ou chaîne vide si non trouvé. \*/ public function get_raw_template(
string \$prompt_key ): string { \$prompt_data = \$this-\>load_prompt(
\$prompt_key ); return \$prompt_data\[\'content\'\] ?? \'\'; } /\*\* \*
Invalide le cache en mémoire (après mise à jour d\'un prompt). \* \*
\@param string\|null \$prompt_key Clé spécifique ou null pour tout
invalider. \* \* \@return void \*/ public function invalidate_cache(
?string \$prompt_key = null ): void { if ( null === \$prompt_key ) {
\$this-\>cache = \[\]; return; } unset( \$this-\>cache\[ \$prompt_key \]
); } // ───────────────────────────────────────── // Chargement avec
fallback // ───────────────────────────────────────── /\*\* \* Charge un
prompt depuis la DB avec fallback sur les defaults. \* \* Ordre de
résolution : \* 1. Cache mémoire (évite la DB) \* 2. Table
wp_techrappy_prompts (DB) \* 3. DefaultPrompts::get_defaults() (fallback
hardcodé) \* \* \@param string \$prompt_key Clé du prompt. \* \*
\@return array\<string, mixed\>\|null Données du prompt ou null si
introuvable. \*/ private function load_prompt( string \$prompt_key ):
?array { // 1. Cache mémoire. if ( isset( \$this-\>cache\[ \$prompt_key
\] ) ) { return \$this-\>cache\[ \$prompt_key \]; } // 2. Base de
données. \$db_prompt = \$this-\>repository-\>find( \$prompt_key ); if (
null !== \$db_prompt ) { \$this-\>cache\[ \$prompt_key \] = \$db_prompt;
return \$db_prompt; } // 3. Fallback sur les prompts par défaut
hardcodés. \$defaults = DefaultPrompts::get_defaults(); if ( isset(
\$defaults\[ \$prompt_key \] ) ) { \$fallback = \[ \'prompt_key\' =\>
\$prompt_key, \'content\' =\> \$defaults\[ \$prompt_key
\]\[\'content\'\], \'response_format\' =\> \$defaults\[ \$prompt_key
\]\[\'response_format\'\] ?? \'json_object\', \'version\' =\> 0, // 0 =
fallback non persisté. \]; \$this-\>cache\[ \$prompt_key \] =
\$fallback; return \$fallback; } return null; } } \`\`\` \-\-- \##
Fichier 5 : \`includes/AI/AIProviderInterface.php\` --- Interface
d\'extensibilité \`\`\`php \<?php /\*\* \* Interface commune pour les
providers IA (extensibilité V2). \* \* Permet d\'ajouter un provider
Anthropic (Claude), Mistral, etc. \* sans modifier le code des étapes du
pipeline. \* \* \@package TechrappySEO\\AI \*/ declare( strict_types=1
); namespace TechrappySEO\\AI; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Interface AIProviderInterface \* \* Contrat que doit respecter
tout provider IA. \* AIClient implémentera cette interface en V2. \*/
interface AIProviderInterface { /\*\* \* Envoie un prompt et retourne
une réponse standardisée. \* \* \@param string \$prompt Prompt
utilisateur. \* \@param string \$system_prompt Prompt système. \*
\@param string \$response_format Format : \'json_object\' ou \'text\'.
\* \@param array\<string, mixed\> \$overrides Surcharges ponctuelles. \*
\* \@return AIResponse \*/ public function generate( string \$prompt,
string \$system_prompt, string \$response_format, array \$overrides ):
AIResponse; /\*\* \* Retourne le nom du provider (ex: \'openai\',
\'anthropic\'). \* \* \@return string \*/ public function
get_provider_name(): string; /\*\* \* Retourne la dernière erreur
rencontrée. \* \* \@return string \*/ public function get_last_error():
string; } \`\`\` \-\-- \## Fichier 6 :
\`includes/AI/Pipeline/StepInterface.php\` --- Contrat des étapes
\`\`\`php \<?php /\*\* \* Contrat commun à toutes les étapes du pipeline
IA. \* \* \@package TechrappySEO\\AI\\Pipeline \*/ declare(
strict_types=1 ); namespace TechrappySEO\\AI\\Pipeline; use
TechrappySEO\\AI\\AIClient; use TechrappySEO\\Utils\\Logger; if ( !
defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Interface StepInterface \*/
interface StepInterface { /\*\* \* Nom machine de l\'étape (ex:
\'intent\', \'plan\', \'intro\'). \* \* \@return string \*/ public
function get_name(): string; /\*\* \* Exécute l\'étape du pipeline. \*
\* \@param array\<string, mixed\> \$context Contexte du job (keyword,
city, steps précédentes...). \* \@param AIClient \$client Client IA
centralisé. \* \@param Logger \$logger Logger du job. \* \* \@return
array{status: string, data: array\<string, mixed\>} \* - status : \'ok\'
\| \'error\' \* - data : données produites par l\'étape (à stocker dans
steps_data) \*/ public function execute( array \$context, AIClient
\$client, Logger \$logger ): array; /\*\* \* Retourne les clés de
contexte requises pour exécuter cette étape. \* Utilisé pour valider que
le contexte est complet avant exécution. \* \* \@return string\[\] Clés
requises dans \$context. \*/ public function
get_required_context_keys(): array; } \`\`\` \-\-- \## Fichier 7 :
\`includes/AI/Pipeline/PipelineRunner.php\` --- Orchestrateur (squelette
étendu) \`\`\`php \<?php /\*\* \* Orchestrateur du pipeline IA en 10
étapes. \* \* Responsabilité : exécuter séquentiellement les étapes, \*
gérer l\'état du job, la persistance et les erreurs. \* \* \@package
TechrappySEO\\AI\\Pipeline \*/ declare( strict_types=1 ); namespace
TechrappySEO\\AI\\Pipeline; use TechrappySEO\\AI\\AIClient; use
TechrappySEO\\Jobs\\JobRepository; use TechrappySEO\\Utils\\Logger; if (
! defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class PipelineRunner \*/
class PipelineRunner { /\*\* \* Données du job en cours. \* \* \@var
array\<string, mixed\> \*/ private array \$job; /\*\* \* Client IA
centralisé. \* \* \@var AIClient \*/ private AIClient \$client; /\*\* \*
Logger du job. \* \* \@var Logger \*/ private Logger \$logger; /\*\* \*
Étapes à exécuter dans l\'ordre. \* \* \@var StepInterface\[\] \*/
private array \$steps; /\*\* \* Constructeur. \* \* \@param
array\<string, mixed\> \$job Données du job depuis JobRepository. \*
\@param Logger \$logger Logger du job. \*/ public function
\_\_construct( array \$job, Logger \$logger ) { \$this-\>job = \$job;
\$this-\>logger = \$logger; \$this-\>client = new AIClient( \$logger );
\$this-\>steps = \$this-\>build_steps(); } /\*\* \* Exécute le pipeline
complet. \* \* \@return bool True si toutes les étapes ont réussi. \*/
public function run(): bool { \$job_id = \$this-\>job\[\'job_id\'\];
\$steps_data = \$this-\>job\[\'steps_data\'\] ?? \[\]; \$context =
\$this-\>build_initial_context(); \$all_ok = true; foreach (
\$this-\>steps as \$step ) { \$step_name = \$step-\>get_name(); //
Sauter les étapes déjà complétées (reprise sur erreur). if ( isset(
\$steps_data\[ \$step_name \]\[\'status\'\] ) && \'ok\' ===
\$steps_data\[ \$step_name \]\[\'status\'\] ) { \$this-\>logger-\>info(
\$step_name, \'Étape déjà complétée --- skip.\' ); // Réinjecter les
données dans le contexte pour les étapes suivantes. \$context\[
\$step_name \] = \$steps_data\[ \$step_name \]\[\'data\'\] ?? \[\];
continue; } // Marquer l\'étape comme en cours. \$steps_data\[
\$step_name \] = \[ \'status\' =\> \'running\', \'data\' =\> \[\] \];
JobRepository::update_steps( \$job_id, \$steps_data,
\$this-\>logger-\>get_logs() ); \$this-\>logger-\>info( \$step_name,
\"Démarrage de l\'étape.\" ); // Vérifier que le contexte est complet.
\$missing = \$this-\>check_context( \$step, \$context ); if ( ! empty(
\$missing ) ) { \$error_msg = sprintf( \'Contexte incomplet pour
l\\\'étape \"%s\". Manquant : %s\', \$step_name, implode( \', \',
\$missing ) ); \$this-\>logger-\>error( \$step_name, \$error_msg );
\$steps_data\[ \$step_name \] = \[ \'status\' =\> \'error\', \'data\'
=\> \[\], \'error\' =\> \$error_msg, \]; \$all_ok = false; // Arrêter le
pipeline sur erreur de contexte (bloquant). break; } // Exécuter
l\'étape. \$result = \$step-\>execute( \$context, \$this-\>client,
\$this-\>logger ); // Stocker le résultat. \$steps_data\[ \$step_name \]
= \$result; \$context\[ \$step_name \] = \$result\[\'data\'\] ?? \[\];
// Persister en DB après chaque étape. JobRepository::update_steps(
\$job_id, \$steps_data, \$this-\>logger-\>get_logs() ); if ( \'error\'
=== \$result\[\'status\'\] ) { \$this-\>logger-\>error( \$step_name,
\'Étape en erreur --- pipeline interrompu.\' ); \$all_ok = false; break;
} \$this-\>logger-\>info( \$step_name, \'Étape complétée avec succès.\'
); } return \$all_ok; } /\*\* \* Construit le contexte initial depuis
les données du job. \* \* \@return array\<string, mixed\> \*/ private
function build_initial_context(): array { return \[ \'keyword\' =\>
\$this-\>job\[\'keyword\'\] ?? \'\', \'city\' =\>
\$this-\>job\[\'city\'\] ?? \'\', \'type\' =\> \$this-\>job\[\'type\'\]
?? \'page\', \'template_post_id\' =\>
\$this-\>job\[\'template_post_id\'\] ?? 0, \'slug_rule\' =\>
\$this-\>job\[\'slug_rule\'\] ?? \'from_keyword\', \'wp_params\' =\>
\$this-\>job\[\'wp_params\'\] ?? \[\], \]; } /\*\* \* Construit la liste
ordonnée des étapes du pipeline. \* Permet la surcharge via filtre
WordPress. \* \* \@return StepInterface\[\] \*/ private function
build_steps(): array { \$steps = \[ new Steps\\StepIntent(), new
Steps\\StepPlan(), new Steps\\StepBlocksList(), new Steps\\StepIntro(),
new Steps\\StepWriteBlock(), new Steps\\StepConclusion(), new
Steps\\StepMeta(), new Steps\\StepFaq(), new Steps\\StepInternalLinks(),
new Steps\\StepAntiDuplicate(), new Steps\\StepQA(), \]; // Permettre
l\'ajout/suppression d\'étapes via filtre. return apply_filters(
\'techrappy_seo_pipeline_steps\', \$steps, \$this-\>job ); } /\*\* \*
Vérifie que le contexte contient toutes les clés requises par une étape.
\* \* \@param StepInterface \$step Étape à valider. \* \@param
array\<string, mixed\> \$context Contexte courant. \* \* \@return
string\[\] Clés manquantes (vide si contexte complet). \*/ private
function check_context( StepInterface \$step, array \$context ): array {
\$required = \$step-\>get_required_context_keys(); \$missing = \[\];
foreach ( \$required as \$key ) { if ( ! isset( \$context\[ \$key \] )
\|\| \'\' === \$context\[ \$key \] ) { \$missing\[\] = \$key; } } return
\$missing; } } \`\`\` \-\-- \## Fichier 8 :
\`includes/AI/Pipeline/Steps/StepIntent.php\` --- Étape 1 (exemple
complet) \`\`\`php \<?php /\*\* \* Étape 1 du pipeline : Analyse de
l\'intention de recherche (SERP). \* \* Prompt 1 --- retourne un JSON
avec intent_principale, must_have_topics, \* paa_questions,
keywords_secondaires, ton_recommande, risques_a_eviter. \* \* \@package
TechrappySEO\\AI\\Pipeline\\Steps \*/ declare( strict_types=1 );
namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\AIClient; use TechrappySEO\\AI\\PromptManager; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\CostEstimator; use TechrappySEO\\Utils\\Logger; if
( ! defined( \'ABSPATH\' ) ) { exit; } /\*\* \* Class StepIntent \*/
class StepIntent implements StepInterface { /\*\* \* {@inheritdoc} \*/
public function get_name(): string { return \'intent\'; } /\*\* \*
{@inheritdoc} \*/ public function get_required_context_keys(): array {
return \[ \'keyword\' \]; } /\*\* \* {@inheritdoc} \*/ public function
execute( array \$context, AIClient \$client, Logger \$logger ): array {
\$keyword = (string) \$context\[\'keyword\'\]; \$type_contenu = (
\'page\' === ( \$context\[\'type\'\] ?? \'page\' ) ) ? \'page_seo\' :
\'article_blog\'; // Récupérer et rendre le prompt. try { \$manager =
new PromptManager(); \$prompt_cfg = \$manager-\>get_rendered(
\'intent\', \[ \'mot_cle\' =\> \$keyword, \'type_contenu\' =\>
\$type_contenu, \'profession\' =\> (string) (
\$context\[\'profession\'\] ?? \'thérapeute\' ), \] ); } catch (
\\RuntimeException \$e ) { return \$this-\>error_result(
\$e-\>getMessage() ); } // Appel IA. \$response = \$client-\>generate(
prompt: \$prompt_cfg\[\'prompt\'\], system_prompt:
\$prompt_cfg\[\'system\'\], response_format: \$prompt_cfg\[\'format\'\]
); // Gérer l\'erreur API. if ( \$response-\>is_error() ) {
\$logger-\>error( \$this-\>get_name(), \'Erreur API : \' .
\$response-\>get_error_message() ); return \$this-\>error_result(
\$response-\>get_error_message() ); } // Valider la structure JSON
retournée. \$parsed = \$response-\>get_parsed(); if ( !
\$this-\>is_valid_response( \$parsed ) ) { \$logger-\>error(
\$this-\>get_name(), \'Structure JSON invalide reçue.\' ); return
\$this-\>error_result( \'Structure de réponse IA invalide pour
l\\\'étape intent.\' ); } // Logger le coût estimé. \$cost =
CostEstimator::estimate(
\\TechrappySEO\\Settings\\SettingsRepository::get( \'openai_model\',
\'gpt-4o\' ), \$response-\>get_input_tokens(),
\$response-\>get_output_tokens() ); \$logger-\>info(
\$this-\>get_name(), sprintf( \'Succès --- %d tokens --- coût estimé :
\$%.4f\', \$response-\>get_total_tokens(), \$cost ) ); return \[
\'status\' =\> \'ok\', \'data\' =\> \$parsed, \'meta\' =\> \[
\'input_tokens\' =\> \$response-\>get_input_tokens(), \'output_tokens\'
=\> \$response-\>get_output_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \'cost_usd\' =\> \$cost, \], \]; } /\*\*
\* Valide la structure minimale de la réponse JSON. \* \* \@param
array\<string, mixed\>\|null \$parsed Données parsées. \* \* \@return
bool \*/ private function is_valid_response( ?array \$parsed ): bool {
if ( ! is_array( \$parsed ) ) { return false; } \$required_keys = \[
\'intent_principale\', \'must_have_topics\', \'keywords_secondaires\',
\]; foreach ( \$required_keys as \$key ) { if ( ! array_key_exists(
\$key, \$parsed ) ) { return false; } } return true; } /\*\* \* Crée un
résultat d\'erreur standardisé. \* \* \@param string \$message Message
d\'erreur. \* \* \@return array{status: string, data: array, error:
string} \*/ private function error_result( string \$message ): array {
return \[ \'status\' =\> \'error\', \'data\' =\> \[\], \'error\' =\>
\$message, \]; } } \`\`\` \-\-- \## Fichiers 9--17 : Étapes 2--10
(squelettes uniformes) \`\`\`php \<?php //
includes/AI/Pipeline/Steps/StepPlan.php declare( strict_types=1 );
namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
class StepPlan implements StepInterface { public function get_name():
string { return \'plan\'; } public function get_required_context_keys():
array { return \[ \'keyword\', \'intent\' \]; } public function execute(
array \$context, AIClient \$client, Logger \$logger ): array { try {
\$manager = new PromptManager(); \$prompt_cfg =
\$manager-\>get_rendered( \'plan\', \[ \'mot_cle\' =\>
\$context\[\'keyword\'\], \'intent_json\' =\> \$context\[\'intent\'\],
\'profession\' =\> \$context\[\'profession\'\] ?? \'thérapeute\', \] );
} catch ( \\RuntimeException \$e ) { return \[ \'status\' =\> \'error\',
\'data\' =\> \[\], \'error\' =\> \$e-\>getMessage() \]; } \$response =
\$client-\>generate( \$prompt_cfg\[\'prompt\'\],
\$prompt_cfg\[\'system\'\], \$prompt_cfg\[\'format\'\] ); if (
\$response-\>is_error() ) { return \[ \'status\' =\> \'error\', \'data\'
=\> \[\], \'error\' =\> \$response-\>get_error_message() \]; } // TODO :
valider structure (H1, sections, slug_suggere). return \[ \'status\' =\>
\'ok\', \'data\' =\> \$response-\>get_parsed() ?? \[\] \]; } } \`\`\`
\`\`\`php \<?php // includes/AI/Pipeline/Steps/StepBlocksList.php
declare( strict_types=1 ); namespace TechrappySEO\\AI\\Pipeline\\Steps;
use TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
class StepBlocksList implements StepInterface { public function
get_name(): string { return \'blocks_list\'; } public function
get_required_context_keys(): array { return \[ \'keyword\', \'plan\' \];
} public function execute( array \$context, AIClient \$client, Logger
\$logger ): array { try { \$manager = new PromptManager(); \$prompt_cfg
= \$manager-\>get_rendered( \'blocks_list\', \[ \'mot_cle\' =\>
\$context\[\'keyword\'\], \'plan_json\' =\> \$context\[\'plan\'\], \] );
} catch ( \\RuntimeException \$e ) { return \[ \'status\' =\> \'error\',
\'data\' =\> \[\], \'error\' =\> \$e-\>getMessage() \]; } \$response =
\$client-\>generate( \$prompt_cfg\[\'prompt\'\],
\$prompt_cfg\[\'system\'\], \$prompt_cfg\[\'format\'\] ); if (
\$response-\>is_error() ) { return \[ \'status\' =\> \'error\', \'data\'
=\> \[\], \'error\' =\> \$response-\>get_error_message() \]; } return \[
\'status\' =\> \'ok\', \'data\' =\> \$response-\>get_parsed() ?? \[\]
\]; } } \`\`\` \`\`\`php \<?php //
includes/AI/Pipeline/Steps/StepIntro.php declare( strict_types=1 );
namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
class StepIntro implements StepInterface { public function get_name():
string { return \'intro\'; } public function
get_required_context_keys(): array { return \[ \'keyword\', \'plan\' \];
} public function execute( array \$context, AIClient \$client, Logger
\$logger ): array { \$plan = \$context\[\'plan\'\] ?? \[\]; \$h1 =
is_array( \$plan ) ? ( \$plan\[\'H1\'\] ?? \$context\[\'keyword\'\] ) :
\$context\[\'keyword\'\]; try { \$manager = new PromptManager();
\$prompt_cfg = \$manager-\>get_rendered( \'intro\', \[ \'mot_cle\' =\>
\$context\[\'keyword\'\], \'H1\' =\> \$h1, \'plan_json\' =\>
\$context\[\'plan\'\], \] ); } catch ( \\RuntimeException \$e ) { return
\[ \'status\' =\> \'error\', \'data\' =\> \[\], \'error\' =\>
\$e-\>getMessage() \]; } \$response = \$client-\>generate(
\$prompt_cfg\[\'prompt\'\], \$prompt_cfg\[\'system\'\],
\$prompt_cfg\[\'format\'\] ); if ( \$response-\>is_error() ) { return \[
\'status\' =\> \'error\', \'data\' =\> \[\], \'error\' =\>
\$response-\>get_error_message() \]; } return \[ \'status\' =\> \'ok\',
\'data\' =\> \$response-\>get_parsed() ?? \[\] \]; } } \`\`\` \`\`\`php
\<?php // includes/AI/Pipeline/Steps/StepWriteBlock.php declare(
strict_types=1 ); namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Exécute Prompt 4 pour chaque bloc listé par StepBlocksList. \*
Stocke un tableau de blocs rédigés dans steps_data\[\'blocks\'\]. \*/
class StepWriteBlock implements StepInterface { public function
get_name(): string { return \'blocks\'; } public function
get_required_context_keys(): array { return \[ \'keyword\',
\'blocks_list\' \]; } public function execute( array \$context, AIClient
\$client, Logger \$logger ): array { \$blocks_meta =
\$context\[\'blocks_list\'\]\[\'blocs\'\] ?? \[\]; \$written = \[\];
foreach ( \$blocks_meta as \$bloc ) { try { \$manager = new
PromptManager(); \$prompt_cfg = \$manager-\>get_rendered(
\'block_write\', \[ \'mot_cle\' =\> \$context\[\'keyword\'\],
\'bloc_json\' =\> \$bloc, \'profession\' =\> \$context\[\'profession\'\]
?? \'thérapeute\', \] ); } catch ( \\RuntimeException \$e ) {
\$logger-\>error( \$this-\>get_name(), \'Erreur prompt bloc : \' .
\$e-\>getMessage() ); continue; } \$response = \$client-\>generate(
\$prompt_cfg\[\'prompt\'\], \$prompt_cfg\[\'system\'\],
\$prompt_cfg\[\'format\'\] ); if ( \$response-\>is_error() ) {
\$logger-\>error( \$this-\>get_name(), \'Erreur API bloc \' . (
\$bloc\[\'ordre\'\] ?? \'?\' ) . \' : \' .
\$response-\>get_error_message() ); \$written\[\] = \[ \'ordre\' =\>
\$bloc\[\'ordre\'\] ?? 0, \'status\' =\> \'error\', \'data\' =\> \[\]
\]; continue; } \$written\[\] = \[ \'ordre\' =\> \$bloc\[\'ordre\'\] ??
0, \'status\' =\> \'ok\', \'data\' =\> \$response-\>get_parsed() ??
\[\], \]; \$logger-\>info( \$this-\>get_name(), sprintf( \'Bloc %d
rédigé.\', \$bloc\[\'ordre\'\] ?? 0 ) ); } return \[ \'status\' =\>
\'ok\', \'data\' =\> \$written \]; } } \`\`\` \`\`\`php \<?php //
includes/AI/Pipeline/Steps/StepConclusion.php declare( strict_types=1 );
namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
class StepConclusion implements StepInterface { public function
get_name(): string { return \'conclusion\'; } public function
get_required_context_keys(): array { return \[ \' \`\`\`php \<?php //
includes/AI/Pipeline/Steps/StepConclusion.php declare( strict_types=1 );
namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Étape 5 --- Génération de la conclusion + CTA. \* Prompt 5 ---
retourne h2_fin_suggestions, conclusion_douce_html, \*
conclusion_pro_html, cta_html. \*/ class StepConclusion implements
StepInterface { public function get_name(): string { return
\'conclusion\'; } public function get_required_context_keys(): array {
return \[ \'keyword\', \'plan\' \]; } public function execute( array
\$context, AIClient \$client, Logger \$logger ): array { \$plan =
\$context\[\'plan\'\] ?? \[\]; \$h1 = is_array( \$plan ) ? (
\$plan\[\'H1\'\] ?? \$context\[\'keyword\'\] ) :
\$context\[\'keyword\'\]; try { \$manager = new PromptManager();
\$prompt_cfg = \$manager-\>get_rendered( \'conclusion_cta\', \[
\'mot_cle\' =\> \$context\[\'keyword\'\], \'H1\' =\> \$h1, \'plan_json\'
=\> \$context\[\'plan\'\], \'profession\' =\>
\$context\[\'profession\'\] ?? \'thérapeute\', \] ); } catch (
\\RuntimeException \$e ) { return \$this-\>error_result(
\$e-\>getMessage() ); } \$response = \$client-\>generate(
\$prompt_cfg\[\'prompt\'\], \$prompt_cfg\[\'system\'\],
\$prompt_cfg\[\'format\'\] ); if ( \$response-\>is_error() ) {
\$logger-\>error( \$this-\>get_name(), \$response-\>get_error_message()
); return \$this-\>error_result( \$response-\>get_error_message() ); }
\$parsed = \$response-\>get_parsed(); if ( ! \$this-\>is_valid_response(
\$parsed ) ) { \$logger-\>error( \$this-\>get_name(), \'Structure JSON
invalide pour conclusion.\' ); return \$this-\>error_result( \'Structure
de réponse IA invalide pour l\\\'étape conclusion.\' ); }
\$logger-\>info( \$this-\>get_name(), sprintf( \'Conclusion générée ---
%d tokens.\', \$response-\>get_total_tokens() ) ); return \[ \'status\'
=\> \'ok\', \'data\' =\> \$parsed, \'meta\' =\> \[ \'input_tokens\' =\>
\$response-\>get_input_tokens(), \'output_tokens\' =\>
\$response-\>get_output_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \], \]; } /\*\* \* Valide la structure
minimale attendue. \* \* \@param array\<string, mixed\>\|null \$parsed
\* \@return bool \*/ private function is_valid_response( ?array \$parsed
): bool { if ( ! is_array( \$parsed ) ) { return false; } return isset(
\$parsed\[\'h2_fin_suggestions\'\],
\$parsed\[\'conclusion_douce_html\'\], \$parsed\[\'cta_html\'\] ); }
private function error_result( string \$message ): array { return \[
\'status\' =\> \'error\', \'data\' =\> \[\], \'error\' =\> \$message \];
} } \`\`\` \-\-- \`\`\`php \<?php //
includes/AI/Pipeline/Steps/StepMeta.php declare( strict_types=1 );
namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Étape 6 --- Génération du meta title et de la meta description.
\* Prompt 6 --- retourne meta_title_1/2, meta_desc_1/2. \*/ class
StepMeta implements StepInterface { public function get_name(): string {
return \'meta\'; } public function get_required_context_keys(): array {
return \[ \'keyword\', \'plan\', \'intent\' \]; } public function
execute( array \$context, AIClient \$client, Logger \$logger ): array {
\$plan = \$context\[\'plan\'\] ?? \[\]; \$intent =
\$context\[\'intent\'\] ?? \[\]; \$h1 = is_array( \$plan ) ? (
\$plan\[\'H1\'\] ?? \$context\[\'keyword\'\] ) :
\$context\[\'keyword\'\]; \$intent_principale = is_array( \$intent ) ? (
\$intent\[\'intent_principale\'\] ?? \'\' ) : \'\'; try { \$manager =
new PromptManager(); \$prompt_cfg = \$manager-\>get_rendered( \'meta\',
\[ \'mot_cle\' =\> \$context\[\'keyword\'\], \'H1\' =\> \$h1,
\'intent_principale\' =\> \$intent_principale, \'profession\' =\>
\$context\[\'profession\'\] ?? \'thérapeute\', \] ); } catch (
\\RuntimeException \$e ) { return \$this-\>error_result(
\$e-\>getMessage() ); } \$response = \$client-\>generate(
\$prompt_cfg\[\'prompt\'\], \$prompt_cfg\[\'system\'\],
\$prompt_cfg\[\'format\'\] ); if ( \$response-\>is_error() ) {
\$logger-\>error( \$this-\>get_name(), \$response-\>get_error_message()
); return \$this-\>error_result( \$response-\>get_error_message() ); }
\$parsed = \$response-\>get_parsed(); if ( ! \$this-\>is_valid_response(
\$parsed ) ) { \$logger-\>error( \$this-\>get_name(), \'Structure JSON
invalide pour meta.\' ); return \$this-\>error_result( \'Structure de
réponse IA invalide pour l\\\'étape meta.\' ); } // Validation des
longueurs des métas. \$warnings = \$this-\>check_lengths( \$parsed );
foreach ( \$warnings as \$warning ) { \$logger-\>warning(
\$this-\>get_name(), \$warning ); } \$logger-\>info(
\$this-\>get_name(), sprintf( \'Métas générées --- title1: %d chars ---
desc1: %d chars.\', strlen( \$parsed\[\'meta_title_1\'\] ?? \'\' ),
strlen( \$parsed\[\'meta_desc_1\'\] ?? \'\' ) ) ); return \[ \'status\'
=\> \'ok\', \'data\' =\> \$parsed, \'meta\' =\> \[ \'input_tokens\' =\>
\$response-\>get_input_tokens(), \'output_tokens\' =\>
\$response-\>get_output_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \], \]; } /\*\* \* Valide la structure
minimale de la réponse. \* \* \@param array\<string, mixed\>\|null
\$parsed \* \@return bool \*/ private function is_valid_response( ?array
\$parsed ): bool { if ( ! is_array( \$parsed ) ) { return false; }
return isset( \$parsed\[\'meta_title_1\'\], \$parsed\[\'meta_desc_1\'\]
); } /\*\* \* Vérifie les longueurs recommandées des meta title et
description. \* Retourne des messages d\'avertissement si hors limites.
\* \* \@param array\<string, mixed\> \$parsed \* \@return string\[\] \*/
private function check_lengths( array \$parsed ): array { \$warnings =
\[\]; \$title_len = strlen( \$parsed\[\'meta_title_1\'\] ?? \'\' );
\$desc_len = strlen( \$parsed\[\'meta_desc_1\'\] ?? \'\' ); if (
\$title_len \< 55 \|\| \$title_len \> 65 ) { \$warnings\[\] = sprintf(
\'meta_title_1 hors limites SEO (%d chars, attendu 55-65).\',
\$title_len ); } if ( \$desc_len \< 140 \|\| \$desc_len \> 160 ) {
\$warnings\[\] = sprintf( \'meta_desc_1 hors limites SEO (%d chars,
attendu 140-160).\', \$desc_len ); } return \$warnings; } private
function error_result( string \$message ): array { return \[ \'status\'
=\> \'error\', \'data\' =\> \[\], \'error\' =\> \$message \]; } } \`\`\`
\-\-- \`\`\`php \<?php // includes/AI/Pipeline/Steps/StepFaq.php
declare( strict_types=1 ); namespace TechrappySEO\\AI\\Pipeline\\Steps;
use TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Étape 7 --- Génération du bloc FAQ (HTML visible + JSON-LD
FAQPage). \* Prompt 7 --- retourne faq_visible_html + faq_jsonld. \*/
class StepFaq implements StepInterface { public function get_name():
string { return \'faq\'; } public function get_required_context_keys():
array { return \[ \'keyword\', \'plan\' \]; } public function execute(
array \$context, AIClient \$client, Logger \$logger ): array { try {
\$manager = new PromptManager(); \$prompt_cfg =
\$manager-\>get_rendered( \'faq\', \[ \'mot_cle\' =\>
\$context\[\'keyword\'\], \'plan_json\' =\> \$context\[\'plan\'\],
\'profession\' =\> \$context\[\'profession\'\] ?? \'thérapeute\', \] );
} catch ( \\RuntimeException \$e ) { return \$this-\>error_result(
\$e-\>getMessage() ); } \$response = \$client-\>generate(
\$prompt_cfg\[\'prompt\'\], \$prompt_cfg\[\'system\'\],
\$prompt_cfg\[\'format\'\] ); if ( \$response-\>is_error() ) {
\$logger-\>error( \$this-\>get_name(), \$response-\>get_error_message()
); return \$this-\>error_result( \$response-\>get_error_message() ); }
\$parsed = \$response-\>get_parsed(); if ( ! \$this-\>is_valid_response(
\$parsed ) ) { \$logger-\>error( \$this-\>get_name(), \'Structure JSON
invalide pour FAQ.\' ); return \$this-\>error_result( \'Structure de
réponse IA invalide pour l\\\'étape faq.\' ); } // Valider que le
JSON-LD contient bien du JSON parsable. \$jsonld_warning =
\$this-\>validate_jsonld( \$parsed\[\'faq_jsonld\'\] ?? \'\' ); if (
\$jsonld_warning ) { \$logger-\>warning( \$this-\>get_name(),
\$jsonld_warning ); } \$logger-\>info( \$this-\>get_name(), \'FAQ
générée avec succès.\' ); return \[ \'status\' =\> \'ok\', \'data\' =\>
\$parsed, \'meta\' =\> \[ \'input_tokens\' =\>
\$response-\>get_input_tokens(), \'output_tokens\' =\>
\$response-\>get_output_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \], \]; } /\*\* \* Valide la structure
minimale de la réponse. \* \* \@param array\<string, mixed\>\|null
\$parsed \* \@return bool \*/ private function is_valid_response( ?array
\$parsed ): bool { if ( ! is_array( \$parsed ) ) { return false; }
return isset( \$parsed\[\'faq_visible_html\'\],
\$parsed\[\'faq_jsonld\'\] ); } /\*\* \* Tente d\'extraire et valider le
JSON embarqué dans la balise script JSON-LD. \* \* \@param string
\$jsonld_raw Contenu brut retourné par l\'IA. \* \@return string\|null
Message d\'avertissement ou null si valide. \*/ private function
validate_jsonld( string \$jsonld_raw ): ?string { // Extraire le JSON
entre les balises \<script type=\"application/ld+json\"\> if (
preg_match( \'/\<script\[\^\>\]\*\>(\[\\s\\S\]\*?)\<\\/script\>/i\',
\$jsonld_raw, \$matches ) ) { \$json_str = trim( \$matches\[1\] );
\$decoded = json_decode( \$json_str, true ); if ( JSON_ERROR_NONE !==
json_last_error() ) { return sprintf( \'JSON-LD FAQ invalide : %s\',
json_last_error_msg() ); } // Vérifier la présence du type FAQPage. if (
( \$decoded\[\'@type\'\] ?? \'\' ) !== \'FAQPage\' ) { return \'JSON-LD
FAQ : \@type FAQPage manquant.\'; } return null; } return \'JSON-LD FAQ
: balise \<script\> introuvable dans faq_jsonld.\'; } private function
error_result( string \$message ): array { return \[ \'status\' =\>
\'error\', \'data\' =\> \[\], \'error\' =\> \$message \]; } } \`\`\`
\-\-- \`\`\`php \<?php //
includes/AI/Pipeline/Steps/StepInternalLinks.php declare( strict_types=1
); namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Étape 8 --- Suggestions de maillage interne. \* Prompt 8 ---
retourne un tableau de liens {url, anchor, placement, why}. \*/ class
StepInternalLinks implements StepInterface { public function get_name():
string { return \'links\'; } public function
get_required_context_keys(): array { return \[ \'keyword\' \]; } public
function execute( array \$context, AIClient \$client, Logger \$logger ):
array { // Récupérer les pages existantes du site pour le maillage.
\$pages_site = \$this-\>get_site_pages(); if ( empty( \$pages_site ) ) {
\$logger-\>warning( \$this-\>get_name(), \'Aucune page trouvée sur le
site --- maillage interne ignoré.\' ); return \[ \'status\' =\> \'ok\',
\'data\' =\> \[\] \]; } try { \$manager = new PromptManager();
\$prompt_cfg = \$manager-\>get_rendered( \'internal_links\', \[
\'mot_cle\' =\> \$context\[\'keyword\'\], \'pages_site_json\' =\>
\$pages_site, \] ); } catch ( \\RuntimeException \$e ) { return
\$this-\>error_result( \$e-\>getMessage() ); } \$response =
\$client-\>generate( \$prompt_cfg\[\'prompt\'\],
\$prompt_cfg\[\'system\'\], \$prompt_cfg\[\'format\'\] ); if (
\$response-\>is_error() ) { \$logger-\>error( \$this-\>get_name(),
\$response-\>get_error_message() ); return \$this-\>error_result(
\$response-\>get_error_message() ); } // La réponse peut être un tableau
JSON direct (pas un objet). \$parsed = \$response-\>get_parsed(); //
Normaliser : si tableau numérique direct, l\'encapsuler. \$links = \[\];
if ( is_array( \$parsed ) ) { // Cas 1 : \[{url, anchor, placement,
why}, \...\] if ( isset( \$parsed\[0\] ) ) { \$links = \$parsed; } //
Cas 2 : {\"links\": \[\...\]} elseif ( isset( \$parsed\[\'links\'\] ) )
{ \$links = \$parsed\[\'links\'\]; } } // Limiter à 5 liens maximum
(garde-fou). \$links = array_slice( \$links, 0, 5 ); // Valider la
structure de chaque lien. \$links = array_filter( \$links, fn( \$link )
=\> isset( \$link\[\'url\'\], \$link\[\'anchor\'\] ) ); \$logger-\>info(
\$this-\>get_name(), sprintf( \'%d lien(s) interne(s) suggéré(s).\',
count( \$links ) ) ); return \[ \'status\' =\> \'ok\', \'data\' =\>
array_values( \$links ), \'meta\' =\> \[ \'input_tokens\' =\>
\$response-\>get_input_tokens(), \'output_tokens\' =\>
\$response-\>get_output_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \], \]; } /\*\* \* Récupère les pages
publiées du site pour le maillage interne. \* Limitées aux 50 plus
récentes pour ne pas surcharger le prompt. \* \* \@return array\<int,
array{title: string, url: string}\> \*/ private function
get_site_pages(): array { \$posts = get_posts( \[ \'post_type\' =\> \[
\'page\', \'post\' \], \'post_status\' =\> \'publish\',
\'posts_per_page\' =\> 50, \'orderby\' =\> \'modified\', \'order\' =\>
\'DESC\', \'fields\' =\> \'ids\', \] ); if ( empty( \$posts ) ) { return
\[\]; } \$pages = \[\]; foreach ( \$posts as \$post_id ) { \$pages\[\] =
\[ \'title\' =\> get_the_title( \$post_id ), \'url\' =\>
wp_make_link_relative( get_permalink( \$post_id ) ), \]; } return
\$pages; } private function error_result( string \$message ): array {
return \[ \'status\' =\> \'error\', \'data\' =\> \[\], \'error\' =\>
\$message \]; } } \`\`\` \-\-- \`\`\`php \<?php //
includes/AI/Pipeline/Steps/StepAntiDuplicate.php declare( strict_types=1
); namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; if ( ! defined( \'ABSPATH\' ) ) { exit; }
/\*\* \* Étape 9 --- Anti-duplicate pour génération en masse. \* \*
Génère des variantes d\'intro et des formulations locales uniques \*
pour éviter le contenu dupliqué entre pages de villes. \* \* Cette étape
est SKIPPÉE en mode single (non-bulk). \*/ class StepAntiDuplicate
implements StepInterface { public function get_name(): string { return
\'anti_duplicate\'; } public function get_required_context_keys(): array
{ // Pas de contexte requis --- l\'étape se skip si non-bulk. return
\[\]; } public function execute( array \$context, AIClient \$client,
Logger \$logger ): array { // Skip si mode single ou si pas de ville.
\$mode = \$context\[\'mode\'\] ?? \'single\'; \$city =
\$context\[\'city\'\] ?? \'\'; if ( \'bulk\' !== \$mode \|\| empty(
\$city ) ) { \$logger-\>info( \$this-\>get_name(), \'Mode single ou
ville absente --- étape anti-duplicate ignorée.\' ); return \[
\'status\' =\> \'ok\', \'data\' =\> \[\] \]; } // Extraire le keyword de
base (sans le nom de ville). \$keyword_base =
\$this-\>extract_keyword_base( (string) \$context\[\'keyword\'\], \$city
); try { \$manager = new PromptManager(); \$prompt_cfg =
\$manager-\>get_rendered( \'anti_duplicate\', \[ \'keyword_base\' =\>
\$keyword_base, \'city\' =\> \$city, \] ); } catch ( \\RuntimeException
\$e ) { return \$this-\>error_result( \$e-\>getMessage() ); } \$response
= \$client-\>generate( \$prompt_cfg\[\'prompt\'\],
\$prompt_cfg\[\'system\'\], \$prompt_cfg\[\'format\'\] ); if (
\$response-\>is_error() ) { // Non-bloquant : on loggue l\'erreur mais
le pipeline continue. \$logger-\>warning( \$this-\>get_name(), \'Erreur
anti-duplicate (non bloquant) : \' . \$response-\>get_error_message() );
return \[ \'status\' =\> \'ok\', \'data\' =\> \[\] \]; } \$parsed =
\$response-\>get_parsed(); if ( ! \$this-\>is_valid_response( \$parsed )
) { \$logger-\>warning( \$this-\>get_name(), \'Structure anti-duplicate
invalide --- ignoré.\' ); return \[ \'status\' =\> \'ok\', \'data\' =\>
\[\] \]; } \$logger-\>info( \$this-\>get_name(), sprintf(
\'Anti-duplicate généré --- %d angles --- %d variantes locales.\',
count( \$parsed\[\'angles\'\] ?? \[\] ), count(
\$parsed\[\'variantes_locales\'\] ?? \[\] ) ) ); return \[ \'status\'
=\> \'ok\', \'data\' =\> \$parsed, \'meta\' =\> \[ \'input_tokens\' =\>
\$response-\>get_input_tokens(), \'output_tokens\' =\>
\$response-\>get_output_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \], \]; } /\*\* \* Extrait le mot-clé de
base en retirant le nom de ville. \* \* \@param string \$keyword Mot-clé
complet (ex: \"osteopathe beauzelle\"). \* \@param string \$city Nom de
la ville (ex: \"Beauzelle\"). \* \@return string Mot-clé de base (ex:
\"osteopathe\"). \*/ private function extract_keyword_base( string
\$keyword, string \$city ): string { \$base = str_ireplace( \$city,
\'\', \$keyword ); return trim( \$base ); } /\*\* \* Valide la structure
minimale de la réponse. \* \* \@param array\<string, mixed\>\|null
\$parsed \* \@return bool \*/ private function is_valid_response( ?array
\$parsed ): bool { if ( ! is_array( \$parsed ) ) { return false; }
return isset( \$parsed\[\'angles\'\], \$parsed\[\'intro_finale_html\'\],
\$parsed\[\'variantes_locales\'\] ); } private function error_result(
string \$message ): array { return \[ \'status\' =\> \'error\', \'data\'
=\> \[\], \'error\' =\> \$message \]; } } \`\`\` \-\-- \`\`\`php \<?php
// includes/AI/Pipeline/Steps/StepQA.php declare( strict_types=1 );
namespace TechrappySEO\\AI\\Pipeline\\Steps; use
TechrappySEO\\AI\\{AIClient, PromptManager}; use
TechrappySEO\\AI\\Pipeline\\StepInterface; use
TechrappySEO\\Utils\\Logger; use
TechrappySEO\\Settings\\SettingsRepository; if ( ! defined( \'ABSPATH\'
) ) { exit; } /\*\* \* Étape 10 --- QA : scoring SEO + humain +
suggestions de correction. \* Prompt 10 --- retourne score_seo,
score_humain, problemes, fixes_rapides, \* rewrite_intro_suggeree_html.
\* \* Cette étape est optionnelle selon le réglage \'qa_gate_enabled\'.
\*/ class StepQA implements StepInterface { public function get_name():
string { return \'qa\'; } public function get_required_context_keys():
array { return \[ \'keyword\' \]; } public function execute( array
\$context, AIClient \$client, Logger \$logger ): array { // Vérifier si
le QA gate est activé dans les settings. \$qa_enabled = (bool)
SettingsRepository::get( \'qa_gate_enabled\', false ); if ( !
\$qa_enabled ) { \$logger-\>info( \$this-\>get_name(), \'QA gate
désactivé dans les réglages --- étape ignorée.\' ); return \[ \'status\'
=\> \'ok\', \'data\' =\> \[\] \]; } // Assembler le contenu HTML complet
à évaluer. \$full_html = \$this-\>build_full_content_html( \$context );
if ( empty( \$full_html ) ) { \$logger-\>warning( \$this-\>get_name(),
\'Contenu HTML vide --- QA ignoré.\' ); return \[ \'status\' =\> \'ok\',
\'data\' =\> \[\] \]; } try { \$manager = new PromptManager();
\$prompt_cfg = \$manager-\>get_rendered( \'qa\', \[ \'mot_cle\' =\>
\$context\[\'keyword\'\], \'full_content_html\' =\> \$full_html, \] ); }
catch ( \\RuntimeException \$e ) { return \$this-\>error_result(
\$e-\>getMessage() ); } \$response = \$client-\>generate(
\$prompt_cfg\[\'prompt\'\], \$prompt_cfg\[\'system\'\],
\$prompt_cfg\[\'format\'\] ); if ( \$response-\>is_error() ) { // QA
non-bloquant : on loggue mais on ne stoppe pas le pipeline.
\$logger-\>warning( \$this-\>get_name(), \'Erreur QA (non bloquant) : \'
. \$response-\>get_error_message() ); return \[ \'status\' =\> \'ok\',
\'data\' =\> \[\] \]; } \$parsed = \$response-\>get_parsed(); if ( !
\$this-\>is_valid_response( \$parsed ) ) { \$logger-\>warning(
\$this-\>get_name(), \'Structure QA invalide --- ignorée.\' ); return \[
\'status\' =\> \'ok\', \'data\' =\> \[\] \]; } \$logger-\>info(
\$this-\>get_name(), sprintf( \'QA terminé --- score SEO: %d/100 ---
score humain: %d/100 --- %d problème(s) détecté(s).\', (int) (
\$parsed\[\'score_seo\'\] ?? 0 ), (int) ( \$parsed\[\'score_humain\'\]
?? 0 ), count( \$parsed\[\'problemes\'\] ?? \[\] ) ) ); return \[
\'status\' =\> \'ok\', \'data\' =\> \$parsed, \'meta\' =\> \[
\'input_tokens\' =\> \$response-\>get_input_tokens(), \'output_tokens\'
=\> \$response-\>get_output_tokens(), \'duration_ms\' =\>
\$response-\>get_duration_ms(), \], \]; } /\*\* \* Assemble le contenu
HTML complet depuis les étapes précédentes \* pour l\'évaluation QA. \*
\* \@param array\<string, mixed\> \$context Contexte complet du job. \*
\@return string HTML assemblé. \*/ private function
build_full_content_html( array \$context ): string { \$parts = \[\]; //
H1 depuis le plan. \$plan = \$context\[\'plan\'\] ?? \[\]; if ( ! empty(
\$plan\[\'H1\'\] ) ) { \$parts\[\] = \'\<h1\>\' . esc_html(
\$plan\[\'H1\'\] ) . \'\</h1\>\'; } // Introduction. \$intro =
\$context\[\'intro\'\]\[\'intro_longue_html\'\] ?? \'\'; if ( ! empty(
\$intro ) ) { \$parts\[\] = \$intro; } // Blocs H2 rédigés. \$blocks =
\$context\[\'blocks\'\] ?? \[\]; if ( is_array( \$blocks ) ) { foreach (
\$blocks as \$block_entry ) { \$block_data = \$block_entry\[\'data\'\]
?? \[\]; if ( ! empty( \$block_data\[\'H2\'\] ) ) { \$parts\[\] =
\'\<h2\>\' . esc_html( \$block_data\[\'H2\'\] ) . \'\</h2\>\'; } if ( !
empty( \$block_data\[\'html\'\] ) ) { \$parts\[\] =
\$block_data\[\'html\'\]; } } } // Conclusion. \$conclusion =
\$context\[\'conclusion\'\] ?? \[\]; if ( ! empty(
\$conclusion\[\'conclusion_douce_html\'\] ) ) { \$parts\[\] =
\$conclusion\[\'conclusion_douce_html\'\]; } return implode( \"\\n\",
\$parts ); } /\*\* \* Valide la structure minimale de la réponse QA. \*
\* \@param array\<string, mixed\>\|null \$parsed \* \@return bool \*/
private function is_valid_response( ?array \$parsed ): bool { if ( !
is_array( \$parsed ) ) { return false; } return isset(
\$parsed\[\'score_seo\'\], \$parsed\[\'score_humain\'\],
\$parsed\[\'problemes\'\] ); } private function error_result( string
\$message ): array { return \[ \'status\' =\> \'error\', \'data\' =\>
\[\], \'error\' =\> \$message \]; } } \`\`\` \-\-- \## Récapitulatif ---
V2 client IA complet \`\`\` includes/AI/ ├── AIClient.php ✅ Client HTTP
centralisé (OpenAI Responses API) │ retry ×2, timeout, logs, filtres WP
├── AIResponse.php ✅ Value Object réponse (success/error, tokens, │
parsed JSON, durée, raw) ├── AIProviderInterface.php ✅ Interface
extensibilité V2 (Anthropic...) ├── PromptRenderer.php ✅ Injection
{{variables}} dans templates ├── PromptManager.php ✅ Chargement DB +
fallback defaults + cache │ └── Pipeline/ ├── StepInterface.php ✅
Contrat commun toutes étapes ├── PipelineRunner.php ✅ Orchestrateur 10
étapes + reprise sur erreur └── Steps/ ├── StepIntent.php ✅ Étape 1 ---
intention SERP ├── StepPlan.php ✅ Étape 2 --- plan H1/H2/H3 ├──
StepBlocksList.php ✅ Étape 2B --- liste blocs ├── StepIntro.php ✅
Étape 3 --- introduction SEO ├── StepWriteBlock.php ✅ Étape 4 ---
rédaction blocs (×N) ├── StepConclusion.php ✅ Étape 5 --- conclusion +
CTA ├── StepMeta.php ✅ Étape 6 --- meta title + description │ +
validation longueurs SEO ├── StepFaq.php ✅ Étape 7 --- FAQ HTML +
JSON-LD validé ├── StepInternalLinks.php ✅ Étape 8 --- maillage interne
(pages WP) ├── StepAntiDuplicate.php ✅ Étape 9 --- anti-dup bulk (skip
si single) └── StepQA.php ✅ Étape 10 --- QA scoring (skip si désactivé)
\`\`\` \*\*Points clés de la V2 :\*\* - \`AIClient\` est le \*\*seul
point d\'entrée\*\* vers OpenAI --- aucune étape n\'appelle
wp_remote_post directement - \`AIResponse\` est \*\*immuable\*\* --- les
étapes ne peuvent pas modifier la réponse - Chaque étape est
\*\*indépendante\*\* et \*\*testable unitairement\*\* via
\`StepInterface\` - \`PipelineRunner\` gère la \*\*reprise sur
erreur\*\* : les étapes \`ok\` ne sont pas rejouées - Les étapes 9 et 10
sont \*\*non-bloquantes\*\* --- une erreur ne stoppe pas le pipeline
