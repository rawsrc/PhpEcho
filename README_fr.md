# **PhpEcho**

`2023-09-26` `PHP 8.0+` `6.1.1`

## **Un moteur de rendu en PHP natif : Une classe pour les gouverner tous**
## **UNIQUEMENT POUR PHP VERSION 8 ET SUPÉRIEURE**

Quand vous codez une application web, le rendu des vues peut être un véritable défi surtout si vous souhaitez 
n'utiliser que du PHP natif et éviter de faire appel à un langage externe de génération de code. 

C'est l'unique objectif de `PhpEcho` : fournir un moteur de rendu en PHP natif sans aucune autre dépendance.<br>

`PhpEcho` est très simple à utiliser, il colle parfaitement à la syntaxe utilisée par PHP pour le rendu du HTML/CSS/JS.<br>
Il est basé sur une approche objet n'utilisant qu'une seule et unique classe pour accomplir la tâche.<br>
Comme vous pouvez vous en douter, l'utilisation du PHP natif offre des performances inégalées. 
Besoin d'aucun cache pour avoir des performances stratosphériques.<br>
Pas de parsage additionnel, pas de nouvelle syntaxe à apprendre !<br>
Si vous avez déjà quelques bases en PHP, c'est amplement suffisant pour l'utiliser.<br> 

Pour résumer, vous n'avez qu'à pointer vers un fichier de vue et passer à l'instance un tableau clé-valeur disponible au rendu.

La classe gère :
* l'inclusion de fichiers
* l'extraction et l'échappement des valeurs stockées dans l'instance courante
* l'échappement de n'importe quelle valeur sur demande (y compris clé-valeur des tableaux multidimensionnels)
* le rendu brut et sans échappement d'une valeur sur demande
* la possibilité d'écrire directement du code HTML au lieu de passer par des inclusions de fichier
* la gestion et le rendu de toutes les instances de classe implémentant la fonction magique `__toString()`
* l'accès à la balise globale `<head></head>` de n'importe quel bloc enfant
* détection d'inclusion infinie sur option

Vous serez également en mesure d'étendre les fonctionnalités du moteur en créant vos propres assistants
tout en laissant votre EDI les lister rien qu'en utilisant la syntaxe PHPDoc.  

1. [Installation](#installation)
2. [Configuration](#configuration)
   1. [Répertoire racine de toutes les vues](#répertoire-racine-de-toutes-les-vues)
   2. [Recherche des valeurs](#recherche-des-valeurs)
   3. [Détection des boucles d'inclusions infinies](#détection-des-boucles-dinclusions-infinies)
3. [Paramètres](#paramètres)
4. [Principes and généralités](#principes-et-généralités)
5. [Démarrage](#démarrage)
   1. [Exemple rapide](#exemple-rapide)
   2. [Codage standard](#codage-standard)
   3. [Contexte HTML](#contexte-html)
      1. [Mise en page - Layout](#mise-en-page---layout)
      2. [Formulaire](#formulaire)
      3. [Page](#page)
6. [Blocs enfants](#blocs-enfants)
7. [Accès à la balise HEAD](#accès-à-la-balise-head)
8. [Valeurs utilisateurs](#valeurs-utilisateur)
   1. [Recherche de clés](#recherche-de-clés)
   2. [Clé non trouvée](#clé-non-trouvée)
9. [Échappement automatique des valeurs](#échappement-automatique-des-valeurs)
10. [Tableau d'instances de PhpEcho](#tableau-dinstances-de-phpecho)
11. [Utilisation d'une vue par défaut](#utilisation-dune-vue-par-défaut)
12. [HTML au format HEREDOC](#html-au-format-heredoc)
13. [Utilisation de l'id de bloc auto-généré](#utilisation-de-lid-de-bloc-auto-généré)
14. [Utilisation du composant `ViewBuilder`](#utilisation-du-composant-viewbuilder)
15. [Utilisation avancée : création de ses propres assistants](#utilisation-avancée-création-de-ses-propres-assistants)
    1. [Assistants](#assistants)
    2. [Étude : l'assistant autonome `$checked`](#étude--lassistant-autonome-checked)
    3. [Étude : l'assistant lié `$raw`](#étude--lassistant-lié-raw)
    4. [Création d'un assistant et liaison complexe](#création-dun-assistant-et-liaison-complexe)
16. [Étudions quelques assistants](#étudions-quelques-assistants)

## **INSTALLATION**
```bash
composer require rawsrc/phpecho
```

## **CONFIGURATION**
### **RÉPERTOIRE RACINE DE TOUTES LES VUES**
Pour l'utiliser, une fois que vous avez déclaré la classe en utilisant `include_once` ou 
n'importe quel autoloader, vous devez indiquer au moteur avant toute chose le répertoire 
racine de toutes les vues (chemin résolu).<br>
Veuillez noter que le seul séparateur de répertoire autorisé est `/` (slash).
```php
<?php

use rawsrc\PhpEcho\PhpEcho;

// eg: from the webroot directory
PhpEcho::setTemplateDirRoot(__DIR__.DIRECTORY_SEPARATOR.'View');
```

### **RECHERCHE DES VALEURS**
Par défaut, le moteur recherche en premier les valeurs demandées dans le tableau 
local des valeurs attachées à chaque instance de `PhpEcho`, puis si la clé demandée 
n'est pas trouvée, il grimpe tous les blocs parents jusqu'à la racine.
Veuillez noter que la recherche n'est limitée qu'au premier niveau de chaque 
tableau de valeurs.

Suite : [Valeurs utilisateur](#valeurs-utilisateur)

### **DÉTECTION DES BOUCLES D'INCLUSIONS INFINIES**
Par défaut le moteur est en mode production et n'interceptera pas les boucles infinies
d'inclusion de blocs vues. Si vous souhaitez détecter la présence de ces boucles, il suffit
de définir l'option ainsi : 
```php
<?php

use rawsrc\PhpEcho\PhpEcho;

PhpEcho::setDetectInfiniteLoop(true);
```
La détection de ces boucles consomme du temps et des ressources serveur, cette option
n'est à utiliser qu'en mode développement et doit être désactivée en production.

## **PARAMÈTRES**

Pour assurer le pilotage du rendu, il est possible de stocker dans n'importe quelle
instance de `PhpEcho` autant de paramètres que nécessaire. Il y a deux niveaux de
paramètres : local et global.<br>
Notez bien que les paramètres ne sont jamais échappés.

Si le paramètre est inexistant, le moteur déclenchera une `Exception`.
```php
// pour un bloc spécifique (paramètre local)
$this->setParam('document.isPopup', true);
$is_popup = $this->getParam('document.isPopup'); // true
$has = $this->hasParam('document.isPopup'); // true
$this->unsetParam('document.isPopup'); 
```
```php
// pour tous les blocs (paramètre global)
PhpEcho::setGlobalParam('document.isPopup', true);
$is_popup = PhpEcho::getGlobalParam('document.isPopup'); // true
$has = PhpEcho::hasGlobalParam('document.isPopup');
PhpEcho::unsetGlobalParam('document.isPopup');;
```

Si vous souhaitez la valeur du paramètre local en premier et ensuite global si inexistant : 
```php
$is_popup = $this->getAnyParam(name: 'document.isPopup', seek_order: 'local');
```
Si vous souhaitez la valeur du paramètre global en premier et ensuite local si inexistant :
```php
$is_popup = $this->getAnyParam(name: 'document.isPopup', seek_order: 'global');
```
Pour vérifier l'existence d'un paramètre dans les deux contextes : 
```php
$this->hasAnyParam('document.isPopup'); // contexte local puis global
```
Définition d'un paramètre dans les deux contextes simultanément : 
```php
$this->setAnyParam('document.isPopup', true); // the value is available in both contexts (local and global)
```
Suppression d'un paramètre dans les deux contextes simultanément :
```php
$this->unsetAnyParam('document.isPopup');
```

## **PRINCIPES ET GÉNÉRALITÉS**

1. Toutes les valeurs extraites d'une instance de `PhpEcho` sont échappées et sûres dans un contexte HTML
2. Dans un fichier vue ou dans un assistant de code, l'instance courante de `PhpEcho` est accessible via `$this`
3. Pour des vues complexes, la classe `ViewBuilder` est fournie avec le moteur
4. `PhpEcho` est fourni avec plusieurs générateurs de code appelés assistants pour vous rendre la vie meilleure.

En tant que développeur, vous savez que la complexité et la taille des applications web vont croissants.
Pour y arriver, vous devez diviser les vues en petits blocs qui composeront un rendu plus complexe.  
Avec `PhpEcho`, les blocs s'injectent et se lient les uns aux autres et composent des blocs plus vastes réutilisables.
<br><br>
Vous devez bien comprendre la structure d'une page HTML, c'est un arbre gigantesque. `PhpEcho` suit exactement la même approche.
<br><br>
Il est fortement recommandé de garder les fichiers de rendu dans un répertoire séparé (gabarit, pages, blocs).<br>
Habituellement, l'architecture est générique et assez simple :
- une page est basée sur un gabarit,
- une page contient autant de blocs que nécessaire,
- un bloc peut être composé d'autres blocs et ainsi de suite.

Notez : l'unité de travail de `PhpEcho` est le bloc. Les autres composants sont 
construits sur des blocs et sont eux-mêmes vus comme des blocs.

Simple, n'est-ce pas ?

## **DÉMARRAGE**

Voici la partie classique de la section vue d'une application web :
```txt
www
 |--- Controller
 |--- Model
 |--- View
 |     |--- block
 |     |     |--- contact.php
 |     |     |--- err404.php
 |     |     |--- footer.php
 |     |     |--- header.php
 |     |     |--- home.php
 |     |     |--- navbar.php
 |     |     |--- login.php
 |     |     |--- ...
 |     |--- layout
 |     |     |--- err.php
 |     |     |--- main.php
 |     |     |--- ...
 |     |--- page
 |     |     |--- about.php
 |     |     |--- cart.php
 |     |     |--- err.php
 |     |     |--- homepage.php
 |     |     |--- login.php
 |     |     |--- ...
 |--- bootstrap.php
 |--- index.php
```

## **EXEMPLE RAPIDE**
```php
<?php

use rawsrc\PhpEcho\PhpEcho;

$block = new PhpEcho();
$block['foo'] = 'abc " < >';   // définition d'une paire clé-valeur dans l'instance

// pour obtenir la valeur échappée suffit de la demander
$x = $block['foo'];   // $x = 'abc &quot; &lt; &gt;'

// échappement à la demande en utilisant un assistant
$y = $block('hsc', 'any value to escape'); // ou
$y = $block->hsc('any value to escape');  // utilisation de la saisie assistée    

// récupération de la valeur brut (non échappée)
$z = $block->raw('foo');   // $z = 'abc " < >' 

// le type de la valeur est préservé, sont échappés toutes les chaînes de caractères et instances avec la méthode magique __toString()
$block['bar'] = new stdClass();
$bar = $block['bar'];
```

### **CODAGE STANDARD**

Génération de la page d'accueil en utilisant plusieurs blocs `PhpEcho` séparés en plusieurs fichiers.
Pour bien comprendre comment les fichiers sont trouvés, le chemin partiel de chaque bloc est préfixé
par le chemin complet du répertoire racine des vues défini avec `PhpEcho::setTemplateDirRoot()`.
```php
<?php declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$homepage = new PhpEcho('layout/main.php', [
    'header' => new PhpEcho('block/header.php', [
        'user' => 'rawsrc',
        'navbar' => new PhpEcho('block/navbar.php'),
    ]),
    'body' => new PhpEcho('block/home.php'),
    'footer' => new PhpEcho('block/footer.php'),      
]);

echo $homepage;
```
Comme vous pouvez le voir, vous composez votre vue qu'avec des blocs que vous 
devez garder indépendants le plus possible les uns des autres. Dans le contexte des vues, 
absolument tous les composants ne sont que des instances de `PhpEcho`.
Tout est automatiquement câblé en arrière-plan et échappé par le moteur quand cela est nécessaire.
Comme `PhpEcho` est très souple, vous composez votre vue bloc par bloc.

### **CONTEXTE HTML**
### **MISE EN PAGE - LAYOUT**
On va créer un simple formulaire de connexion basé sur la description ci-dessus.<br>
En premier, création d'un fichier de mise en page appelé `main.php` dans `View/layout` avec 
des valeurs requises :
* une description (texte)
* un titre (texte)
* un bloc `PhpEcho` en charge du rendu du corps de la page
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // MAIN LAYOUT ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="description" content="<?= $this['description'] ?>">
    <title><?= $this['title'] ?></title>
</head>
<body>
<?= $this['body'] ?>
</body>
</html>
```
Comme toutes les instances de `PhpEcho` sont préservées et transformées en texte que quand
cela est nécessaire, vous pouvez les appeler directement dans le code comme indiqué ci-dessus.

### **FORMULAIRE**

Ensuite, on créé un bloc vue appelé `login.php` dans le répertoire `View/block` contenant le code
HTML du formulaire :<br>
Notez bien que `$this['url_submit']` et `$this['login']` sont automatiquement échappées<br>

```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= $this['login'] ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
```

### **PAGE**

Enfin, on code une page `page/login.php` basée sur `layout/main.php` et on y injecte
le corps de la page en utilisant le bloc `block/login.php`.<br>
```php
<?php declare(strict_types=1); // LOGIN PAGE

use rawsrc\PhpEcho\PhpEcho;

echo new PhpEcho('layout/main.php', [
    'title' => 'My first use case of PhpEcho',
    'description' => 'PhpEcho, PHP template engine, easy to learn and use',
    'body' => new PhpEcho('block/login.php', [
        'login' => 'rawsrc',
        'url_submit' => 'any/path/for/connection',
    ]),
]);
```
Code équivalent :<br>
```php
<?php declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

$page = new PhpEcho('layout/main.php');
$page['title'] = 'My first use case of PhpEcho';
$page['description'] = 'PhpEcho, PHP template engine, easy to learn and use';

$body = new PhpEcho('block/login.php');
$body['login'] = 'rawsrc';
$body['url_submit'] = 'any/path/for/connection';

$page['body'] = $body;

echo $page;
```
Comme vous pouvez le constater, `PhpEcho` est super flexible. Plein de moyens sont 
disponibles pour générer le rendu HTML/CSS/JS. La syntaxe est toujours claire, lisible et
facilement compréhensible.

## **BLOCS ENFANTS**

Pour composer une vue à base de blocs enfants, il y a plusieurs moyens pour les déclarer :
* `$this->renderBlock()`: le bloc enfant est anonyme dans le contexte parental et n'est plus manipulable une fois rendu
* `$this->addBlock()`: le bloc enfant est nommé et peut être manipulable dans le contexte parental directement par son nom
* `$this->renderByDefault()`: le bloc enfant est nommé et si le bloc parent ne fournit aucun bloc spécifique avec le même nom
alors le moteur utilisera celui défini par défaut

Notez bien encore que la vue complète doit être perçue comme un énorme arbre et que tous les blocs sont tous reliés entre eux.
Vous ne devez jamais déclarer un bloc totalement indépendant au sein d'un autre bloc.
Ceci n'est pas autorisé :<br> 
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
use rawsrc\PhpEcho\PhpEcho; // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= new PhpEcho('block/login_input_text.php') ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
```
cela doit être remplacé par une des méthodes décrites ci-dessus :<br>
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ // LOGIN FORM BLOCK ?>
<p>Please login : </p>
<form method=post action="<?= $this['url_submit'] ?>">
    <label>User</label>
    <input type="text" name="login" value="<?= $this->renderBlock('block/login_input_text.php') ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
```
Ainsi, vous ne coupez par l'arbre ;-)

## **MANIPULATION ET ACCÈS À LA BALISE HEAD**

Quand vous codez les vues d'un site, vous allez utiliser plein de petits blocs qui seront insérés à la 
bonne place au moment du rendu. Comme beaucoup le savent, la meilleure architecture est de s'efforcer 
de garder les blocs indépendants les uns des autres. Parfois, vous aurez le besoin d'ajouter des dépendances
directement dans l'en-tête de la page. Dans toutes les instances de `PhpEcho`, vous disposez d'une méthode
nommée `addhead()` qui est prévue pour.

Imaginez que vous êtes dans les tréfonds du DOM, vous avez besoin de déclarer un lien vers votre librairie.
Dans le code de votre bloc, vous n'avez qu'à rajouter : 
```php
<?php $this->addHead('<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous">') ?>
```  
ou utiliser un assistant `script` qui va sécuriser toutes vos valeurs :
```php
<?php $this->addHead('script', [
    'src'         => "https://code.jquery.com/jquery-3.4.1.min.js", 
    'integrity'   => "sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=", 
    'crossorigin' => "anonymous"]) ?>
```
Maintenant dans le bloc qui gère le rendu de l'en-tête, il suffit juste de coder : 
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<head>
    <?= $this->getHead(); ?>
</head>
```
Le moteur va compiler les données en provenance de tous les bloc enfants et compléter l'en-tête en conséquence.

## **VALEURS UTILISATEUR**
### **RECHERCHE DE CLÉS**

Le moteur est capable de rechercher des valeurs selon différentes méthodes.
Quand vous demandez le rendu d'une valeur spécifique identifiée par une clé, le moteur va essayer
de la chercher dans les valeurs stockées localement, puis si rien n'est trouvé, il va chercher dans
les blocs parents en grimpant ainsi de suite jusqu'à atteindre la racine (le tout premier bloc `PhpEcho`).

Ce fonctionnement est paramétrable en utilisant la méthode `setSeekValueMode(string $mode)`.<br>
Par défaut, le mode est défini sur `parents`.<br>
Les différents modes sont : 
- `current`: le moteur va chercher uniquement la clé dans le tableau local des valeurs
- `parents`: le moteur va chercher la clé dans le tableau local des valeurs et si non trouvée, il va grimper 
les blocs un à un jusqu'à la racine
- `root`: le moteur va chercher la clé dans le tableau local des valeurs et si non trouvée, il va la chercher 
dans le bloc racine uniquement

Veuillez noter que la recherche de la clé ne se fait qu'au premier niveau du tableau des variables.
```php
// ex : on a un tableau de valeurs déclaré dans un bloc
[
    'k.abc' => 'v.abc', 
    'k.def' => [
        'k.ghi' => 'v.ghi', // second niveau
        'k.jkl' => 'v.jkl', // second niveau
    ],
];
// les clés visibles pour le bloc sont 'k.abc' et 'k.def' (premier niveau),
// les clés dans le sous-tableau ne sont pas automatiquement accessibles à moins d'utiliser foreach()
// si vous demandez directement $block['k.ghi'] le moteur ne cherchera pas dans le sous-tableau 
```
Voici comment cela fonctionne : 
```php
<?php declare(strict_types=1);

use rawsrc\PhpEcho\PhpEcho;

// $page is the root of the tree
$page = new PhpEcho('layout/main.php');
$page['title'] = 'Mon premier projet PhpEcho';
$page['description'] = 'PhpEcho, un moteur de rendu PHP, facile à apprendre et à utiliser';
$page['login'] = 'rawsrc';
$page['url_submit'] = 'une/url/de/connexion';

$body = new PhpEcho('block/login.php');  // ce bloc attend deux valeurs (login et url_submit)

$page['body'] = $body; // $body['login'] and $body['url_submit'] sont bien définies
// les deux sont extraites de bloc racine : $page

echo $page;
```

### **CLÉ NON TROUVÉE**
Par défaut, si la clé est inexistante, le moteur déclenchera une `Exception`.
Il est possible de changer ce comportement, en indiquant au moteur de retourner `null` à la place
en utilisant la méthode `setNullIfNotExists(bool $p)`.

## **ÉCHAPPEMENT AUTOMATIQUE DES VALEURS**

L'échappement automatique des valeurs fonctionne aussi bien pour les clés que pour les valeurs.
Soyez attentifs :
```php
<?php
// supposons que nous avons ce genre de données :
$data = ['"name"' => 'rawsrc'];
// on injecte ces données dans une instance de PhpEcho
$block = new PhpEcho('dummy_block.php', ['my_data' => $data]);
// dans un contexte HTML nous devons tester la valeur de la clé
// un truc dans ce genre : 
?>
<?php foreach ($this['my_data'] as $key => $value) {
    // code erroné
    if ($key === '"name"') { // ceci ne sera jamais vrai, la clé ayant été échappée
        echo $value; // $value est automatiquement échappé
    }
    // code correct
    if ($key === '&quot;name&quot;') {  
        echo $value; // $value est automatiquement échappé
    }    
}
```
Ou vous le faites manuellement en utilisant l'assistant `raw()` et pensez bien à échapper la valeur :
```php
foreach ($this->raw('my_data') as $key => $value) {
    if ($key === '"name"') { 
        echo $this->hsc($value); // $value est manuellement échappée
    }   
}
```
Ou vous pouvez encore créer un assistant en charge de ne pas échapper les clés mais juste les valeurs.
Cela sera vu dans la section "Usage avance de `PhpEcho`", plus bas.

## **TABLEAU D'INSTANCES DE PhpEcho**

Il est possible de définir plusieurs stratégies pour le rendu des vues, surtout en ce qui concerne le
niveau de détail (la granularité) des mises en pages complexes.
Supposons un code de rendu de la sorte : 
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<body>
<?= $this['body'] ?>
</body>
```
ou de cette sorte :
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<body>
<?= $this['preloader'] ?>
<?= $this['top_header'] ?>
<?= $this['navbar'] ?>
<?= $this['navbar_mobile'] ?>
<?= $this['body'] ?>
<?= $this['footer'] ?>
<?= $this['copyright'] ?>
</body>
```
Le premier est très concis et le second très détaillé.
Pour préserver une certaine flexibilité tout en gardant le code concis, il est 
possible d'utiliser un tableau d'instances de `PhpEcho` pour une clé : 
```php

use rawsrc\PhpEcho\PhpEcho;

$page['body'] = [
    new PhpEcho('block/preloader.php'),
    new PhpEcho('block/top_header.php'),
    new PhpEcho('block/navbar.php'),
    new PhpEcho('block/navbar_mobile.php'),
    new PhpEcho('block/body.php'),
    new PhpEcho('block/footer.php'),
    new PhpEcho('block/copyright.php'),
];
```
Les blocs sont rendus dans l'ordre dans lequel ils apparaissent. Depuis la version
6.1.0, le moteur est capable de rendre les blocs dans les tableaux multidimensionnels 
dans l'ordre dans lequel ils sont stockés.

## **UTILISATION D'UNE VUE PAR DÉFAUT**

Il est possible de définir un bloc `PhpEcho` par défaut qui sera rendu si aucun
bloc n'est explicitement défini pour la clé visée : 
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<body>
<?= $this->renderByDefault('preloader', 'block/preloader.php') ?>
<?= $this->renderByDefault('top_header', 'block/top_header.php') ?>
<?= $this->renderByDefault('navbar', 'block/navbar.php') ?>
<?= $this->renderByDefault('navbar_mobile', 'block/navbar_mobile.php') ?>
<?= $this['body'] ?>
<?= $this->renderByDefault('footer', 'block/footer.php') ?>
<?= $this->renderByDefault('copyright', 'block/copyright.php') ?>
</body>
```
Toutes les clés sont facultatives sauf `body`.

## **HTML AU FORMAT HEREDOC**

Il est aussi possible d'écrire directement du code HTML au format HEREDOC en lieu 
et place du mécanisme d'inclusion de fichiers. Il est obligatoire de définir les valeurs
à passer au code html en amont de la génération du rendu.

Reprenons le code précédent et injectons directement le code HTML du fichier du 
formulaire de connexion dans `layout/main.php` en utilisant la notation HEREDOC.

```php
<?php

use rawsrc\PhpEcho\PhpEcho;

$page = new PhpEcho('layout/main.php', [
    'title' => 'My first use case of PhpEcho',
    'description' => 'PhpEcho, PHP template engine, easy to learn and use',
]);

// ATTENTION: on définit les valeurs avant de les passer au bloc HTML 
$body = new PhpEcho(vars: [
    'login' => 'rawsrc',
    'url_submit' => 'any/path/for/connection',
]);

// on définit directement le code du formulaire 
$body->setCode(<<<html
<p>Please login : </p>
<form method=post action="{$body['url_submit']}>">
    <label>User</label>
    <input type="text" name="login" value="{$body['login']}"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
</form>
html
    );
$page['body'] = $body;

echo $page;
// Notez le codage dans ce cas : `$body` remplace `$this`
```

## **UTILISATION DE L'ID DE BLOC AUTO-GÉNÉRÉ**

Chaque instance de `PhpEcho` possède un id auto-généré qui peut être utilisé dans 
n'importe quel balise html. Ceci va ainsi définir un contexte fermé qui va permettre
de travailler uniquement sur ce bloc sans interférer avec les autres.

On souhaiterait tester du code CSS sur le bloc sans interférer avec les autres 
parties de la page.
```php
<?php /** @var rawsrc\PhpEcho\PhpEcho $this */ ?>
<?php $id = $this->getId() ?>
<style>
#<?= $id ?> label {
    color: blue;
    float: left;
    font-weight: bold;
    width: 30%;
}
#<?= $id ?> input {
    float: right;
}
</style>
<div id="<?= $id ?>">
  <p>Please login</p>
  <form method="post" action="<?= $this['url_submit'] ?>>">
    <label>Login</label>
    <input type="text" name="login" value="<?= $this['login'] ?>"><br>
    <label>Password</label>
    <input type="password" name="pwd" value=""><br>
    <input type="submit" name="submit" value="CONNECT">
  </form>
</div>
```
Nous avons maintenant un contexte fermé défini par `<div id="<?= $id ?>">`.

## **UTILISATION DU COMPOSANT `ViewBuilder`**

Dans un contexte orienté objet, il est souvent plus facile de manipuler la vue comme un objet aussi.
Revenons à l'exemple de la page de connexion. 
Il est possible de la reprendre avec le composant `ViewBuilder` :
```php
<?php

namespace YourProject\View\Page;

use rawsrc\PhpEcho\PhpEcho;
use rawsrc\PhpEcho\ViewBuilder;

class Login extends ViewBuilder
{
    public function build(): PhpEcho
    {
         
        $layout = new PhpEcho('layout/main.php');
        $layout['description'] = 'dummy.description';
        $layout['title'] = 'dummy.title';
        $layout['body'] = new PhpEcho('block/login.php', [
            'login' => 'rawsrc',
            'url_submit' => 'any/path/for/connection',
            /**
             * Notez que le ViewBuilder implémente l'interface ArrayAccess,
             * vous avez plein de moyens pour passer vos valeurs à la vue. 
             */
        ]);
        
        return $layout;
    } 
}
```
Dans un contrôleur qui doit renvoyer la page de connexion, vous pouvez coder quelque chose dans ce style :
```php
<?php declare(strict_types=1);

namespace YourProject\Controller\Login;

use YourProject\View\Page\Login;

class Login
extends YourAbstractController 
{
    public function invoke(array $url_data = []): void
    {
        $page = new YourProject\View\Page\Login;
        // on passe les valeurs attendues par la vue
        $page['name'] = 'rawsrc';
        $page['postal.code'] = 'foo.bar';
        
        // un exemple de fin de traitement tiré d'un framework
        $this->task->setResponse(Response::html($page));
    }
}
```

## **UTILISATION AVANCÉE: CRÉATION DE SES PROPRES ASSISTANTS**
Vous avez la possibilité de générer vos propres assistants aussi simplement qu'une fonction contextuelle.<br>
`PhpEcho` est fourni avec une petite bibliothèque d'assistants : `stdPhpEchoHelpers.php`<br>

### **ASSISTANTS**
Chaque assistant est une fonction contextuelle (`Closure`) qui peut retourner absolument ce que vous voulez.<br>
Chaque assistant peut être lié à une instance de `PhpEcho` ou totalement autonome.<br>
Si le lien est avérée, à l'intérieur du code de la fonction contextuelle, vous disposez d'un accès au contexte
d'exécution de l'appelant en utilisant directement `$this`. Si l'assistant est autonome, alors ce n'est qu'une 
simple fonction paramétrée.

Si l'assistant doit disposer d'un accès au contexte de l'instance `PhpEchp` qui l'appelle, alors il faut le déclarer
avec `PhpEcho::addBindableHelper()` sinon `PhpEcho::addHelper()`.<br>

### **ÉTUDE : L'ASSISTANT AUTONOME `$checked`**
Cet assistant compare deux valeurs scalaires et renvoie `" checked "` si elles sont identiques.
```php
$checked = function($p, $ref) use ($is_scalar): string {
    return $is_scalar($p) && $is_scalar($ref) && ((string)$p === (string)$ref) ? ' checked ' : '';
};
PhpEcho::addHelper(name: 'checked', helper: $checked, result_escaped: true);
```
Cet assistant est une fonction contextuelle autonome, elle n'a pas besoin d'un accès à l'instance de `PhpEcho` qui l'appelle.
Comme tout est échappé par défaut dans `PhpEcho`, on peut considérer que le mot `" checked "` est inoffensif et n'as pas besoin 
d'être échappé en plus d'où le troisième paramètre : `result_escaped: true`.<br>
Pour utiliser cet assistant dans vos vues, 2 manières :
* `$this('checked', 'your value', 'ref value'); // based on __invoke`
* `$this->checked('your value', 'ref value'); // based on __call`

### **ÉTUDE : L'ASSISTANT LIÉ `$raw`**
Cet assistant renvoie la valeur brut d'une clé du tableau clé-valeurs présente dans chaque instance de `PhpEcho`.
```php
$raw = function(string $key) {
    /** @var PhpEcho $this */
    return $this->getOffsetRawValue($key);
};
PhpEcho::addBindableHelper(name: 'raw', closure: $raw, result_escpaed: true);
```
Comme cet assistant extrait une valeur contenue dans l'instance `PhpEcho` qui l'appelle, il a besoin d'un
accès au contexte d'exécution du parent. C'est pourquoi il est déclaré en utilisant `PhpEcho::addBindableHelper()`.<br>
Comme nous voulons une valeur non échappée, on doit indiquer explicitement au moteur qu'elle est déjà échappée.
Bien sûr elle ne l'est pas, mais c'est ce que nous recherchons.
Pour utiliser cet assistant dans vos vues, 2 manières :
* `$this('raw', 'key');`
* `$this->raw('key');`

### **CRÉATION D'UN ASSISTANT ET LIAISON COMPLEXE**
Il y a deux manières pour définir un assistant :
* `PhpEcho::addHelper(string $name, Closure $helper, bool $result_escaped = false)`
* `PhpEcho::addBindableHelper(string $name, Closure $helper, bool $result_escaped = false)`

Quand vous codez un assistant lié qui fera lui-même appel à un autre assistant lié et pour être sûr
que les deux assistants partagent le même contexte, vous devez utiliser cette syntaxe :
`$existing_helper = $this->bound_helpers['$existing_helper_name'];`<br>
Reportez-vous à l'assistant `$root_var` (regardez comment le lien vers un autre assistant lié `$root` est créé).

## **ÉTUDIONS QUELQUES ASSISTANTS**

Comme annoncé ci-dessus, la bibliothèque standard `stdPhpEchoHelpers.php` contient des assistants 
pour le traitement des données ainsi que pour la génération du HTML. Comme les assistants sont des petits
bouts de code, la lecture de leur code source vous aidera facilement à saisir leur utilité.

Exemples :
* Création d'un `<input>` en utilisant l'assistant `voidTag()` :
```php
$this->voidTag('input', ['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']);
```
Pas d'inquiétude concernant les caractères dangereux, tous sont échappés. Voici le code HTML gténéré :<br>
```html
<input type="text" name="name" required value=" &lt; &gt; &quot; &lt;script&gt;&lt;/script&gt;">
```
Il est aussi possible de le faire en utilisant l'assistant `attributes()` :
```php
<input <?= $this->attributes(['type' => 'text', 'name' => 'name', 'required', 'value' => ' < > " <script></script>']) ?>>
```
Comme vous pouvez le voir, il y a une tonne de méthodes pour arriver au résultat souhaité.<br>

Revenons au problème précédent des clés échappées. Voici le code d'un assistant qui préserve les clés et
échappe les valeurs en une seule fois.
```php
<?php 

use rawsrc\PhpEcho\PhpEcho;

/**
 * Return an array of raw keys and escaped values for HTML
 * Careful: keys are not safe in HTML context
 * 
 * @param array $part
 * @return array
 */
$hsc_array_values = function(array $part) use (&$hsc_array_values): array {
    $hsc = PhpEcho::getHelperBase('hsc');
    $to_escape = PhpEcho::getHelperBase('toEscape')    
    $data = [];
    foreach ($part as $k => $v) {
        if ($to_escape($v)) {
            if (is_array($v)) {
                $data[$k] = $hsc_array_values($v);
            } else {
                $data[$k] = $hsc($v);
            }
        } else {
            $data[$k] = $v;
        }
    }

    return $data;
};
PhpEcho::addBindableHelper('hscArrayValues', $hsc_array_values, true);
```

**rawsrc**