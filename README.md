# Traits

Les traits s'ajoutent dans les entités par exemple:

```php
use App\Entity\base\CategoriesTrait;
class Article
{
use CategoriesTrait;
...
```

- CategoriesTrait: permet de sélectionner dess catégories pour une entité
- DevantTrait: cré dans l'index un switch qui permet de mettre un boolan devant à true et tous les autres en false
- EtatTrait: cré un bouton dans l'index avec l'état: brouillon, en ligne, à vérifier
- OrdreTrait: affiche des boutons pour modifier l'ordre dans index (ajouter dans ID order:ordre (champ ordre, doit exister))
- SituationTrait: Affiche un switch avec actif/inactif dans index
- SlugTrait: permet d'avoir un slug (il est généré à chaque enregistrement ou modif d'une entité. Pour une ancienne base on peut se servir de sc setslug), de plus le slug est peut être généré automatiquement (name,nom,titre,title,label ou id) si on indique pas de slugs
- VerifedTrait: permet d'avoir un switch on/off dans index
- VuesTrait: ne se voit nul part, sert à enregistrer le nombres de vues par exmple
- UseridTrait: ajout un champ userid avec la valeur de l'user.id en cours

Les traits sont interessants, c'est des bonnes base pour voir comment utiliser crudmick dedans.

# pour tous les champs

quelques exemples:

- TWIG:join(',')
- OPT:{"multiple":true,"expanded":true}
- ATTR:{"data-controller":"onecheckbox"}

Pour chaque type on a dans la doc de Symfony la possibilité d'ajouter des attributs (ATTR) ou des oprions (OPT)
Exemple pour [entité](https://symfony.com/doc/current/reference/forms/types/entity.html) on à les [attributs](https://symfony.com/doc/current/reference/forms/types/entity.html#attr) et les tous le reste sont des options ;-)

IMPORTANT par ATTR ou OPT il est possible de modifier les choix de crudmick. Par exemple dans password il met l'option first_option="Mot de passe" si tu veux le changer il te suffit de mettre OPT:{"first_option":"Un super password"}

## TPL (template)

- no_created
- no_updated
- no_index (n'affiche pas dans index)
- no_form (n'affiche pas dans new et edit)
- no_show (na'affiche pas dans voir)
- id (affiche l'id en caché)
- drag (pour déplacer les lignes dans index)

# Textarea => TEXTE

- vide
- simple
- simplelanguage
- normal possibilité d'ajouter un filtre
`* attr:{"data-base--suneditor-upload-value":"article/hd"}` pour choisir un répertoire de destination pour les images  (pas obligatoire)
- full
- annonce
- string
- textarea
- mini

```
/**
  * attr:{"data-controller" : "base--suneditor"}
  * attr:{"data-base--suneditor-toolbar-value": "§$AtypeOption[\"data\"]->getTypevaleur()§"}
  ou
  attr:{"data-base--suneditor-toolbar-value": "normal"}
  */
```

il est possible de modifier l'initialisation par

`* ATTR:{"data-base--suneditor-init-value":"{\"defaultStyle\":\"font-size:22px;\",\"width\":\"100%\"}"}`

`ATTR:{"data-base--suneditor-height-value":"100"}`

# JSON =>ARRAY/JSON

quand tu choisis array avec sc m:e, en fait il te cré un array. Donc pour gérer ça en json

` *json `

# notation par étoiles

Le type peut-être int ou float (l'affichage ne prend que 0,0.5,1:étoile vide, à moitié ou complète)

```php
     * stars
     * options:10
```

- par défaut le système est basé sur 5 étoiles
- 3 class pour la présentation starsTwigFilled, starsTwigEmpty, starsTwigHalf

# liste de choix

## une liste de string définis

le plus simple => STRING

```php
* choice
* options:["question","réponse"]
```

ou un array qui vient d'une méthode dans une entity (attention demande à relancer le crud pour maj du type, vaux mieux se servir de entity)

```php
     * choice
     * xtra:{"entity":"\\App\\Entity\\Supercategorie","method":"getListChoices"}
     * TWIG:join(',')
     * OPT:{"multiple":true,"expanded":true}
     * ATTR:{"class":"d-flex flex-wrap gap-2"}
```

ou choix simple avec retour différents =>STRING

```php
* choice
* options:{"client":"ROLE_USER","administrateur":"ROLE_ADMIN"}
```

ou une choix multiple =>STRING

```php
* choice
* options:{"client":"ROLE_USER","administrateur":"ROLE_ADMIN","partenaire":"ROLE_PARTENAIRE"}
* TWIG:join(',')
* OPT:{"multiple":true,"expanded":true}
* ATTR:{"class":"d-flex flex-wrap gap-2"}
```

Pour imposer un seul choix
`ATTR:{"data-controller":"base--onecheckbox"}`

Pour mettre uyne valeur par défaut passer par
`private string $mitoyen = "non mitoyen";`

## liste de choix en cliquant sur un bouton dans index =>BOOLEAN

```php
* choiceenplace
* xtra:{"champ":"Sur l'accueil"}
* options:{"0":"<i class=\"bi bi-toggle-off\"></i>","1":"<i class=\"bi bi-toggle-on\"></i>"}
* TPL:no_form
```

## liste de choix en cliquant sur un bouton dans index mais avec un possible et désactive les autres => BOOLEAN

```php
* onechoiceenplace
* xtra:{"champ":"mis devant"}
* options:{"0":"<i class=\"bi bi-toggle-off\"></i>","1":"<i class=\"bi bi-toggle-on\"></i>"}
* TPL:no_form
```

## liste pour choisir un enfant d'une entité=>STRING pour MANYTOONE ou json P

[doc](https://symfony.com/doc/current/reference/forms/types/entity.html)

Utiliser MANYTOMANY ou MANYTOONE comme relation!!!!

```php
   * entity
     * label:nom
     * ordre:nom
     * OPT:{"help":"multiple sélection et retirer une sélection avec CTRL + click"}
     * OPT:{"required":false}
     * OPT:{ "group_by":"§function($choice, $key, $value) {return $choice->getSuperCategorie()->getNom();}§"}
     * andwhere:u.pov = 1
     * dql:->leftJoin(\"u.redaction\", \"r\")->where(\"u.deletedAt IS NULL\")->andWhere(\"r.id IS NULL or u.id=\".$AtypeOption[\"data\"]->getEtape()->getId())
     * opt:{"data":"§$AtypeOption[\"data\"]->getEtape()§"}
     * OPT:{"multiple":true,"expanded":true}
     * tpl:no_index
     * 
```

exemple d'un manytoone qui fontionne (créé par un sc m:e ... et relation manytoone) avec ajout manuel du targetentity et rien dans le onetomany

```
 #[ORM\ManyToOne(inversedBy: 'intrigues', targetEntity: Categorie::class)]
    /**
     * entity
     * label:nom
     * ordre:nom
     * OPT:{"required":false}
     * OPT:{"expanded":false}
     * tpl:no_index
     */
    private ?Categorie $categorie = null;
    ```



Dans l'autre entity pour afficher autre chose que l'id

```php
  #[ORM\ManyToOne(inversedBy: 'categories')]
    /**
     * options:{"champ":"nom"}
     */
```

# mot de passe => STRING

[doc](https://symfony.com/doc/current/reference/forms/types/repeated.html)

Crudmick, affiche deux inputs et oblige à ce que les mots de passe soit identique
`* password`

# ajout d'un fichier unique=>STRING

[doc](https://symfony.com/doc/current/reference/forms/types/file.html)

`* fichier`

Crudmick met d'office les extensions de fichiers les plus classiques comme possible.
Il ajoute également un lien quand on clique sur le nom du fichier dans index (limité à 50 caractères)

# ajout d'un champ email => STRING

[doc](https://symfony.com/doc/current/reference/forms/types/email.html)

`email`

# cache un champ

[doc](https://symfony.com/doc/current/reference/forms/types/hidden.html)

```php
*hidden
*TPL:no_index
```

# cache un champ pour tout le monde sauf pour le superadmin

Utilise le stimulus hiddenroot et se sert de l'email <m@cadot.eu>

```php
*hiddenroot
*TPL:no_index
```

# affiche en lecture seule sauf pour root

Utilise le stimulus readonlyroot et se sert de l'email <m@cadot.eu>*

```php
*readonlyroot
*TPL:no_index
```

# un champ type money => STRING

[doc](https://symfony.com/doc/current/reference/forms/types/money.html)

`money`

# un champ du type adresse qui propose une liste d'adresse de openstreetmaps

```php
     * adresse
     * attr:{"data-base--adresse-limit-value":"15"}
     * attr:{"data-base--adresse-proprietes-value":"bien_adresseproprietes"}
     * attr:{"data-base--adresse-latitude-value":"bien_latitude"}
     * attr:{"data-base--adresse-longitude-value":"bien_longitude"}
```

# un champ type téléphone => STRING

[doc](https://symfony.com/doc/current/reference/forms/types/tel.html)

ajouter

# [Assert\Regex(pattern: '/^(\+33|0)[1-9](\d{2}){4}$/', message: 'Le numéro de téléphone doit être au format +33 ou 0 suivi de 9 chiffres')]

`telephone`

# ajout d'une image unique=>STRING

[doc](https://symfony.com/doc/current/reference/forms/types/file.html)

```php
* image 
* tpl:index_FileImage (miniature) ou index_FileImageNom (minature et nom) sinon retourne que le nom de fichier
```

Crudmick met d'office les extensions de images les plus classiques comme possible.(image/*)

# permettre d'ajouter ou supprimer une entité dans une autre (collection)

[doc](https://symfony.com/doc/current/reference/forms/types/collection.html)

Utiliser MANYTOMANY ou ONETOMANY

```php
* collection
* options:{"field":"label"}
* xtra:{"allow_add":true,"prototype":true,"allow_delete":true,"entry_options!":"[\"label\"=>false]"}
* tpl:no_index
* opt:{"required":false}
```

# choix d'une couleur

[doc](https://symfony.com/doc/current/reference/forms/types/color.html)

`color`

# ajout d'une class à la ligne de form

Par emple deux col-6
`* row_attr:{"class":"col-6 m-0"}`
et
`* row_attr:{"class":"col-6 m-0 p-0 mb-3"}`

# ID

## Fortement conseillé

- search: pour donner les champs de recherche pour knp ex: *  SEARCH:['id','titre','article']

## Optionnels

- hide:{"roles[0]":"ROLE_SUPERADMIN"} //cache sauf pour
- tpl:no_created
- tpl:no_deleted
- tpl:no_updated
- tpl:search //laisse la recherche malgré l'ordre
- nocrud //pour protéger une entité des modificatio nde crudmick
- no_action_edit //pour ne pas afficher le bouton éditer
- no_access_deleted //pour ne pas voir les accès pour supprimer
- no_action_add // pour ne pas voir le bouton ajouter
- slug:champ (sinon généré automatiquement)
- onlytype (crud mick génère que le fichier form, sert pour les entités que l'on veut juste mettre dans une connection par exmple)
- ordre:ordre (champ de rangement) et ajouter OrdreTrait ou créer un champ int ex:* ORDRE:{"id":"DESC"}, order est nécessaire ajouter tpl:drag pour autoriser drag and drop
- filter pour donner un filtre liip au envoie de fichier par les inupt file tpl:{"filter":"petitcarree"}
- ajouter des boutons à l'index `* actions:{"edit":{"route":"menu_export","id":"Menu.id","icon":"filetype-pdf"}}`
- limiter la recherche dans les controller `limit:'userid' => $this->getUser()->getId()` pour limiter les recherches si userid est égal à l'id de l'user e n cours (dans ce cas combiner avec useridtrait)

### pour les options des propiétés d'entités

- tpl:row

## Particulier

Créé un sélecteur sur une entité et copy la sélection dans le presse papier

- select: pour la boite de recherche ex: * SELECT:{"entitie":"article","affichage":"titre","champs":"titre","copy":"slug","copyurl":"/les-articles/","limit":30} //utilise le stimulus 'SelectAndCopyElement'
- viewer: url et champ pour créer le lien pour visionner l'objet dans un nouvel onglet ex:* VIEWER:{"url":"/les-articles","champ":"slug"}

```php
* SELECT:{"entitie":"article","affichage":"titre","champs":"titre","copy":"slug","copyurl":"/les-articles/","limit":30}
* VIEWER:{"url":"/les-articles","champ":"slug"}
```

### Quelques exemples TWIG

```php
date('d/m à H:i', "Europe/Paris")
TWIG=TBjsonpretty|raw 
TWIG=u.truncate(8, '...')
split('¤')[1]
```

### un champ peut accéder à la valeur d'un autre champ

par exemple pour que le champ suneditor toolbar value prennent la valeur du champ getTypenom

```php
     * attr:{"data-controller" : "base--suneditor"}
     * attr:{"data-base--suneditor-toolbar-value": "§$AtypeOption[\"data\"]->getTypenom()§"}
```

### Erreurs fréquentes

- An invalid form control with name='' is not focusable est du à un champ qui est required et `hiddden` ou  `display:none`, suneditor cache le champ et donc si il est required crè cette erreur. On peut utiliser `* OPT:{"required":false}`

- `Warning: include(/app/vendor/composer/ ... ): Failed to open stream: No such file or directory`
faire un `composer dump-autoload`cela réactualise les fichiers mémorisé par composer

### Slug

le Slug est généré par SlugTrait et toolshelper appelé dans le controller.
il est possible de choisir un champ en mettant dans l'id
'*slug:champ'

### PASS

Quand tu lances la génération par sc crud, tu peux avoir un message du genre "non géré ..." cela signifie que crudmick n'a rien fait pour ces valeurs. Cela permet de ne pas en oublier.
Si tu veux lui dire de ne pas s'en occuper tu peux mettre

`* pass`

### Relation et recherche

On peut avoir une erreur du type

```php
... Must be a StateFieldPathExpression
```

C'est du au fait qu'il y a une relation, il faut indiquer une recherche par exemple:

#### pour les ManyToOne

```php
* SEARCH:['id','nom','description','supercat.nom']
```

#### pour les OneToMany

il n'est pas possible de les intégrer dans la recherche, donc il faut mettre une recherche snas les cités.

```php
 * SEARCH:['id','nom']
```

### pour retirer les guillemets

utilisation de § exemple "§code sans guillement §"

### pour l'erreur "pas de target entity pour ..."

  ' #[ORM\ManyToOne(targetEntity: Meta::class, inversedBy: 'etapes')]'
