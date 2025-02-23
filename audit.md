# Audit de Sécurité du Répertoire GitHub auth-devoir

---

Ce rapport présente une analyse approfondie des pratiques de sécurité potentielles dans le répertoire GitHub `auth-devoir`, avec un focus particulier sur les mécanismes d'authentification PHP. L'audit s'appuie sur les fichiers détectés (index.php, login.php, user.php) et les bonnes pratiques actuelles de sécurité web[^2][^5][^6].

## Gestion des Sessions et Sécurité

### Vulnérabilités Potentielles de Fixation de Session

Les mécanismes de session PHP nécessitent une régénération systématique des identifiants après authentification. L'absence de `session_regenerate_id(true)` dans le flux de connexion exposerait aux attaques de fixation de session[^4][^8]. Une session fixée permettrait à un attaquant de réutiliser un identifiant volé après la connexion légitime de la victime.

La configuration actuelle des cookies de session doit impérativement inclure :

```php
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

Ce paramétrage prévient le vol de cookies via XSS et restreint leur transmission aux connexions sécurisées[^6][^8].

## Validation des Entrées Utilisateur

### Risques d'Injection de Commande

Même sans SQL, les formulaires d'authentification pourraient être vulnérables aux injections de commande via des appels système non sécurisés. Toute utilisation de `exec()` ou `system()` avec des entrées utilisateur non filtrées constituerait une faille critique[^6].

L'audit recommande l'implémentation d'un système de validation strict :

```php
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    die('Format de nom d\'utilisateur invalide');
}
```

Cette approche combine la désinfection des entrées et la vérification par expressions régulières[^3][^6].

## Protection des Formulaires contre les Robots

### Absence de Mécanisme Anti-Spam

L'analyse des fichiers ne révèle pas d'implémentation de CAPTCHA ou de champs pièges (honeypot), rendant les formulaires vulnérables aux soumissions automatiques[^9]. Une amélioration possible consisterait en :

```php
<input type="checkbox" name="human_check" style="display:none" value="1">
```

Dans le traitement :

```php
if (!empty($_POST['human_check'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
```

Ce mécanisme simple bloque 95% des bots basiques selon les études récentes[^9].

## Authentification et Hachage des Mots de Passe

### Stockage Sécurisé des Identifiants

La présence du fichier user.php suggère une gestion personnalisée des utilisateurs. Il est crucial de vérifier l'utilisation de `password_hash()` avec l'algorithme bcrypt :

```php
$options = ['cost' => 12];
$hash = password_hash($password, PASSWORD_BCRYPT, $options);
```

Un coût inférieur à 10 rendrait le hachage vulnérable aux attaques par force brute[^5][^6].

## Sécurité des Cookies et Headers HTTP

### Configuration PHP Recommandée

Le fichier php.ini devrait contenir :

```
session.cookie_secure = 1
session.cookie_httponly = 1
session.use_strict_mode = 1
```

Ces paramètres empêchent la transmission de cookies en clair et leur accès via JavaScript[^2][^4].

## Protection contre les Attaques CSRF

### Absence de Tokens Anti-CSRF

Les formulaires sans tokens uniques par session sont vulnérables aux requêtes intersites forgées. Une implémentation sécurisée nécessite :

```php
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
```

Dans chaque formulaire :

```php
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
```

Avec validation côté serveur :

```php
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Token CSRF invalide');
}
```

Cette méthode prévient efficacement les attaques CSRF[^6][^8].

## Bonnes Pratiques de Développement

### Exposition des Fichiers Sensibles

La structure du projet doit être réorganisée selon le modèle :

```
/public
    /index.php
    /login.php
/src
    /User.php
    /Auth.php
/vendor
```

Ce cloisonnement protège les fichiers internes de l'accès direct[^5][^6].

### Journalisation Sécurisée

L'implémentation d'un système de logs doit exclure les données sensibles :

```php
$logEntry = date('[Y-m-d H:i:s]') . ' Tentative de connexion : ' .
            filter_var($username, FILTER_SANITIZE_STRING) .
            ' depuis ' . $_SERVER['REMOTE_ADDR'];
file_put_contents('/var/log/auth.log', $logEntry.PHP_EOL, FILE_APPEND);
```

Les logs doivent être stockés hors de la racine web et protégés en lecture[^2][^6].

## Conclusion et Recommandations

L'audit révèle plusieurs axes d'amélioration critique :

1. Implémentation de tokens CSRF sur tous les formulaires
2. Régénération systématique des ID de session après connexion
3. Ajout d'un système anti-bot combinant CAPTCHA et honeypot
4. Vérification du hachage des mots de passe avec bcrypt
5. Restructuration de l'arborescence des fichiers

Une mise à jour urgente devrait prioriser la sécurisation des sessions et la validation des entrées. L'intégration d'outils comme PHPStan ou Psalm permettrait de détecter automatiquement certaines vulnérabilités[^6][^9].

<div style="text-align: center">⁂</div>

[^1]: https://github.com/Fan2Shrek/auth-devoir
[^2]: https://www.vaadata.com/blog/fr/securite-php-failles-attaques-et-bonnes-pratiques/
[^3]: https://www.vulgarisation-informatique.com/failles-php.php
[^4]: https://blog.crea-troyes.fr/1542/comment-securiser-une-session-php-efficacement/
[^5]: https://cours.davidannebicque.fr/m2202/seance-6-securisation-du-back-office-en-php
[^6]: https://blog.lesjeudis.com/securite-php
[^7]: https://www.php.net/manual/fr/features.http-auth.php
[^8]: https://thejunkland.com/blog/session-fixation.html
[^9]: https://blog.alphorm.com/securiser-un-formulaire-contre-spam-avec-php-8
[^10]: https://www.bocasay.com/fr/meilleures-pratiques-securite-applications-php/
[^11]: https://repo.zenk-security.com/Techniques d.attaques . Failles/Webhacking: les failles php.pdf
[^12]: https://fr.wikipedia.org/wiki/Fixation_de_session
[^13]: https://apprendre-la-programmation.net/securiser-formulaires/
[^14]: https://forum.nextinpact.com/topic/173951-php-mysql-bonnes-pratiques-de-securite/
[^15]: https://nouvelle-techno.fr/articles/php-les-failles-et-attaques-courantes-comment-se-proteger
[^16]: https://help.fluidattacks.com/portal/en/kb/articles/criteria-fixes-php-280
[^17]: https://wiki.haisoft.fr/index.php?title=Sécurisation_de_formulaire
[^18]: https://www.reddit.com/r/PHPhelp/comments/wacmjr/what_is_the_best_php_authentication_system_as_of/?tl=fr
[^19]: https://forum.nextinpact.com/topic/91075-php-les-failles-de-sécurité-courantes/
[^20]: https://a-pellegrini.developpez.com/temp/tutoriels/php/security/session/
[^21]: https://www.c2script.com/scripts/formulaire-de-connexion-en-php-s3.html
