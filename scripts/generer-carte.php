<?php
/**
 * Générateur de la CARTE DE LA PLATEFORME TechRappy.
 * Lit tous les mu-plugins + crons + sites, et produit PLATEFORME.md (groupé par domaine).
 * Relancer :  wp eval-file generer-carte.php --allow-root   (depuis /var/www/cabinet-visible)
 * Puis committer PLATEFORME.md dans le dépôt GitHub.
 */
if ( ! defined( 'ABSPATH' ) ) { echo "À lancer via wp eval-file.\n"; return; }

$dir = WPMU_PLUGIN_DIR;
$files = glob( $dir . '/*.php' );
sort( $files );

// --- Règles de regroupement par domaine (1er match gagne) ---
$DOMAINES = array(
	'SEO · Génération · Contenu' => array( 'cv-seo', 'cv-sm', 'cv-redacteur', 'cv-atelier', 'cv-ap-', 'cv-outil-ligne-edito', 'cv-outil-newsletter', 'cv-newsletter', 'cv-infograph', 'cv-studio', 'cv-outil-atelier' ),
	'CRM · Compta · Trésorerie' => array( 'cv-crm', 'cv-compta', 'cv-tresor' ),
	'Espace Coach · Coaching' => array( 'cv-coach', 'cv-espace-coach', 'cv-topo', 'cv-workbook', 'cv-diag', 'cv-sfiche' ),
	'Clientes · Tâches · Formulaires' => array( 'cv-taches', 'cv-outil-site-modifs', 'cv-depot', 'cv-brief', 'cv-mon-site', 'cv-admin-clientes', 'cv-site', 'cv-client' ),
	'Fiche cliente · Contexte · Entreprise' => array( 'cv-fiche', 'cv-contexte', 'cv-entreprise', 'cv-espace-entreprise', 'cv-outil-analyse', 'cv-outil-google-ads' ),
	'Plateforme payante · Accès · Hub' => array( 'cv-acces', 'cv-plateforme', 'cv-plat', 'cv-equipe', 'cv-pilotage' ),
	'Sommet' => array( 'sommet', 'cv-sommet', 'ss26', 'cv-croisement' ),
	'Mariage · Divers clients' => array( 'cv-ln', 'cv-wedding', 'cv-ads' ),
	'Moteur · Infra · Technique' => array( 'cv-le-', 'cv-404', 'cv-api-usage', 'cv-health', 'cv-oauth', 'cv-credentials', 'cv-pont', 'cv-gmb' ),
);

function cv_carte_domaine( $base, $DOMAINES ) {
	foreach ( $DOMAINES as $dom => $prefs ) {
		foreach ( $prefs as $p ) { if ( strpos( $base, $p ) === 0 ) { return $dom; } }
	}
	return 'Autres';
}
function cv_carte_header( $content, $key ) {
	if ( preg_match( '/' . preg_quote( $key, '/' ) . ':\s*(.+)/', $content, $m ) ) {
		return trim( preg_replace( '/\s*\*\/.*$/s', '', trim( $m[1] ) ) );
	}
	return '';
}

$groupes = array(); $total_lignes = 0; $n = 0;
foreach ( $files as $f ) {
	$base = basename( $f );
	if ( strpos( $base, '.bak' ) !== false ) { continue; }
	$head = file_get_contents( $f, false, null, 0, 1200 );
	$name = cv_carte_header( $head, 'Plugin Name' );
	$desc = cv_carte_header( $head, 'Description' );
	$lignes = count( file( $f ) );
	$total_lignes += $lignes; $n++;
	$dom = cv_carte_domaine( $base, $DOMAINES );
	$groupes[ $dom ][] = array( 'file' => $base, 'name' => $name, 'desc' => $desc, 'lignes' => $lignes );
}

// --- Crons WordPress ---
$wp_crons = array();
foreach ( (array) _get_cron_array() as $ts => $hooks ) {
	foreach ( (array) $hooks as $hook => $ev ) {
		if ( strpos( $hook, 'wp_' ) === 0 || in_array( $hook, array( 'recovery_mode_clean_expired_keys', 'delete_expired_transients', 'update_network_counts' ), true ) ) { continue; }
		$rec = ''; foreach ( (array) $ev as $sig => $d ) { $rec = $d['schedule'] ?? 'unique'; break; }
		$wp_crons[ $hook ] = $rec;
	}
}
// --- Crons système (root) ---
$sys = @shell_exec( 'crontab -l 2>/dev/null' );

// ================= MARKDOWN =================
$d = date( 'd/m/Y à H:i' );
$md  = "# 🗺️ Carte de la plateforme TechRappy\n\n";
$md .= "> **Généré automatiquement le $d.** Ce fichier est la source de vérité de toute l'infrastructure.\n";
$md .= "> Pour le régénérer (après des changements) : `wp eval-file generer-carte.php --allow-root` depuis `/var/www/cabinet-visible`, puis committer ce fichier.\n\n";

$md .= "## En bref\n\n";
$md .= "- **$n modules** (mu-plugins), **" . number_format( $total_lignes, 0, ',', ' ' ) . " lignes de code**\n";
$md .= "- Serveur central **VPS** `89.116.110.43` (accès SSH par clé `~/.ssh/cabinet_visible_ed25519`)\n";
$md .= "- Les sites des clientes sont chez **o2switch** (pas d'accès serveur) — on leur parle via la **brique « TechRappy Connect »** (plugin posé sur leur site, connexion par clé)\n\n";

$md .= "## 🏗️ Architecture (comment tout se connecte)\n\n";
$md .= "```\n";
$md .= "  TA PLATEFORME (VPS cabinet-visible.fr)\n";
$md .= "   ├─ Les outils (190 modules ci-dessous) : SEO, CRM/Compta, Espace Coach, Sommet...\n";
$md .= "   ├─ Le MOTEUR IA partagé  cv_le_call_claude()  (clé Anthropic dans /etc/cv/credentials.env)\n";
$md .= "   ├─ Clés API centralisées : Anthropic, OpenAI, Serper, DataForSEO, Stripe  (/etc/cv/credentials.env)\n";
$md .= "   └─ Génère du contenu  ─────────────┐\n";
$md .= "                                      ▼\n";
$md .= "  BRIQUE « TechRappy Connect »  (plugin WordPress posé sur chaque site cliente, o2switch)\n";
$md .= "   ├─ Reçoit le contenu via une clé de connexion (route REST /wp-json/techrappy/v1/...)\n";
$md .= "   ├─ Publie en mise en page Divi native (pages SEO locales + articles de blog)\n";
$md .= "   ├─ Se met à jour toute seule (update.json hébergé sur cabinet-visible.fr)\n";
$md .= "   └─ Protège les pages existantes de la cliente (jamais écrasées)\n";
$md .= "```\n\n";
$md .= "**Les 7 sites WordPress du serveur** : `" . trim( implode( '`, `', array_values( array_filter( scandir( '/var/www' ), function ( $x ) { return $x[0] !== '.' && is_dir( '/var/www/' . $x ); } ) ) ) ) . "`\n\n";

$md .= "## 📦 Les modules, par domaine\n\n";
ksort( $groupes );
// ordre : mettre "Autres" à la fin
$autres = $groupes['Autres'] ?? null; unset( $groupes['Autres'] );
if ( $autres ) { $groupes['Autres'] = $autres; }
foreach ( $groupes as $dom => $list ) {
	usort( $list, function ( $a, $b ) { return strcmp( $a['file'], $b['file'] ); } );
	$md .= "### " . $dom . "  _(" . count( $list ) . ")_\n\n";
	$md .= "| Fichier | Rôle | Lignes |\n|---|---|---:|\n";
	foreach ( $list as $it ) {
		$role = $it['desc'] ?: $it['name'] ?: '—';
		$role = str_replace( array( '|', "\n" ), array( '/', ' ' ), $role );
		if ( mb_strlen( $role ) > 160 ) { $role = mb_substr( $role, 0, 157 ) . '…'; }
		$md .= '| `' . $it['file'] . '` | ' . $role . ' | ' . $it['lignes'] . " |\n";
	}
	$md .= "\n";
}

$md .= "## ⏰ Automatisations qui tournent toutes seules\n\n";
$md .= "**Crons WordPress :**\n\n";
if ( $wp_crons ) { foreach ( $wp_crons as $h => $r ) { $md .= "- `$h` — $r\n"; } } else { $md .= "- (aucun personnalisé)\n"; }
$md .= "\n**Crons serveur (crontab) :**\n\n```\n" . trim( (string) $sys ) . "\n```\n\n";

$md .= "## 🧠 Skills (méthodes IA, sur la machine d'Isabelle)\n\n";
$md .= "- **chaine-parfaite** — YouTube (stratégie, titres, scripts, miniatures, algo 2026)\n";
$md .= "- **post-parfait** — le post LinkedIn parfait (copywriting + algo)\n";
$md .= "- **techrappy-content** — voix de marque TechRappy (accroches, pubs, emails)\n";
$md .= "- **seo-parfait** / **page-parfaite** / **page-comparateur-seo** — SEO, landing/Ads, comparateurs\n";
$md .= "- **email-parfait** / **carrousel-parfait** / **infographie-parfaite** — emails, carrousels, visuels\n";
$md .= "- **o2switch-security** — sécurité/virus sur hébergement o2switch\n\n";

$md .= "## 🔑 Infra & accès (OÙ sont les choses — pas les secrets)\n\n";
$md .= "- **Serveur** : VPS `89.116.110.43`, SSH par clé `~/.ssh/cabinet_visible_ed25519` (pas de mot de passe)\n";
$md .= "- **Clés API** : fichier `/etc/cv/credentials.env` sur le VPS (Anthropic, OpenAI, Serper, DataForSEO, Stripe, HMAC…)\n";
$md .= "- **Sites WordPress** : `/var/www/<site>/` — plugins maison dans `wp-content/mu-plugins/`\n";
$md .= "- **Brique cliente** : zip + update.json sur `cabinet-visible.fr/wp-content/uploads/techrappy/`\n";
$md .= "- **Base de données** : tables préfixées `wp_cv_*` et `wp_crmc_*` (clientes, articles, tâches, abonnements, échéances…)\n";
$md .= "- **GitHub** : dépôt `github.com/Techrappy-ou/techrappy-seo-plugin` (ce fichier + `/docs`)\n\n";

$md .= "---\n_Fin de la carte. Régénère ce fichier après chaque gros changement pour qu'il reste vrai._\n";

file_put_contents( '/root/PLATEFORME.md', $md );
echo "OK : /root/PLATEFORME.md écrit (" . strlen( $md ) . " octets, $n modules, " . count( $groupes ) . " domaines).\n";
