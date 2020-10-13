<?php
/**
 * Setările de bază pentru WordPress.
 *
 * Acest fișier este folosit la crearea wp-config.php în timpul procesului de instalare.
 * Folosirea interfeței web nu este obligatorie, acest fișier poate fi copiat
 * sub numele de „wp-config.php”, iar apoi populate toate detaliile.
 *
 * Acest fișier conține următoarele configurări:
 *
 * * setările MySQL
 * * cheile secrete
 * * prefixul pentru tabele
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** Setările MySQL: aceste informații pot fi obținute de la serviciile de găzduire ** //
/** Numele bazei de date pentru WordPress */
define( 'DB_NAME', 'bogdanpro_db' );

/** Numele de utilizator MySQL */
define( 'DB_USER', 'root' );

/** Parola utilizatorului MySQL */
define( 'DB_PASSWORD', '' );

/** Adresa serverului MySQL */
define( 'DB_HOST', 'localhost' );

/** Setul de caractere pentru tabelele din baza de date. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Schema pentru unificare. Nu face modificări dacă nu ești sigur. */
define('DB_COLLATE', '');

/**#@+
 * Cheile unice pentru autentificare
 *
 * Modifică conținutul fiecărei chei pentru o frază unică.
 * Acestea pot fi generate folosind {@link https://api.wordpress.org/secret-key/1.1/salt/ serviciul pentru chei de pe WordPress.org}
 * Pentru a invalida toate cookie-urile poți schimba aceste valori în orice moment. Aceasta va forța toți utilizatorii să se autentifice din nou.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '(+@%c1wYRmh&#5T@GI{+#ia1hS}Rs18Y$i~Q=[-dA62`orlv!/ 0!ez@[~mHhfon' );
define( 'SECURE_AUTH_KEY',  '*Q#?!li#Bye`Ii>0P upS=#pH8PbP0W+e<&3NQC3<.sP0?[L^OIGOac(~DR1v[KR' );
define( 'LOGGED_IN_KEY',    'K:$%O67lElcTIf=#rkE[,eZ)!ZLDx$O{}up--8TQeP(ZEFSZh[~;QQ2doNV5`7av' );
define( 'NONCE_KEY',        'ZG)]vWK,;?*qkqW27!4S9fFEFo{v Aua+/Rig}lRt6Q618U=NusJi_ .6;;f@|O#' );
define( 'AUTH_SALT',        '~VP!F5u1t-/VsJ&Z][Y4L[&z_j&m*m7~!Q4d7x8yMF4U6GysbJ-<,Zq9+o=49FqG' );
define( 'SECURE_AUTH_SALT', 'm$KXVo?_/gvb?%Gq{GBL>gF]$6 =sP.s[5zH7f8o>*Y2lH|noh2oGv7:anejq 8>' );
define( 'LOGGED_IN_SALT',   'Cu qvN07mr|Im,Yt@:!2TZ=2/wd`I;Fl1C85 o >13W_Rb8.=DE2m9DF piT>}Ai' );
define( 'NONCE_SALT',       'T_m@9l{u.nTGD*vU}EGo0TM? azJk@*< zDJ{3Yn<-?qv%!hLXh)?f!QFJ/P7=1I' );

/**#@-*/

/**
 * Prefixul tabelelor din MySQL
 *
 * Acest lucru permite instalarea mai multor instanțe WordPress folosind aceeași bază de date
 * dacă prefixul este diferit pentru fiecare instanță. Sunt permise doar cifre, litere și caracterul liniuță de subliniere.
 */
$table_prefix = 'wp_';

/**
 * Pentru dezvoltatori: WordPress în mod de depanare.
 *
 * Setează cu true pentru a permite afișarea notificărilor în timpul dezvoltării.
 * Este recomadată folosirea modului WP_DEBUG în timpul dezvoltării modulelor și
 * a șabloanelor/temelor în mediile personale de dezvoltare.
 *
 * Pentru detalii despre alte constante ce pot fi utilizate în timpul depanării,
 * vizitează Codex-ul.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* Asta e tot, am terminat cu editarea. Spor! */

/** Calea absolută spre directorul WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Setează variabilele WordPress și fișierele incluse. */
require_once(ABSPATH . 'wp-settings.php');
