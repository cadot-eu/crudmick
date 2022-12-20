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

Timetrait est d'office partout, il garde les dates de création, mise à jour et suppression

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

# Textarea => TEXTE

- vide
- simple
- simplelanguage
- normal possibilité d'ajouter un filtre 
`* attr:{"data-base--suneditor--upload-value":"article/hd"}` pour choisir un répertoire de destination pour les images  (pas obligatoire)
- full

Suneditor est en cours de finition

# JSON =>ARRAY/JSON

quand tu chois array avec sc m:e, en fait il te cré un array. Donc pour gérer ça en json

` *json `

# liste de choix

## une liste de string définis

le plus simple => STRING

```php
* choice
* options:["question","réponse"]
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
```

Pour imposer un seul choix
`ATTR:{"data-controller":"base--onecheckbox"}`

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

Utiliser MANYTOMANY ou MANYTOONE comme relation

```php
* entity
* options:{"label":"nom"} //pour choisir le champ affiché dans la sélection de l'enfant
* twig:json_encode
* OPT:{"required":false} //pour permttre un choix vide
* OPT:{"empty_data":null} //idem, peut-être pas obligatoire (merci de me dire)
* OPT:{"help":"multiple sélection et retirer une sélection avec CTRL + click"} //pour many to many
```

# mot de passe => STRING

[doc](https://symfony.com/doc/current/reference/forms/types/repeated.html)

Crudmick, affiche deux inputs et oblige à ce que les mots de passe soit identique
`* password`

# ajout d'un fichier unique=>STRING

[doc](https://symfony.com/doc/current/reference/forms/types/file.html)

`* fichier `

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

Utilise le stimulus hiddenroot et se sert de l'email m@cadot.eu

```php
*hiddenroot
*TPL:no_index
```

# affiche en lecture seule sauf pour root

Utilise le stimulus readonlyroot et se sert de l'email m@cadot.eu*

```php
*readonlyroot
*TPL:no_index
```

# un champ type money => STRING

[doc](https://symfony.com/doc/current/reference/forms/types/money.html)

`money`

# ajout d'une image unique=>STRING

[doc](https://symfony.com/doc/current/reference/forms/types/file.html)

```php
* image 
* tpl:index_FileImage (miniature) ou index_FileImageNom (minature et nom) sinon retourne que le nom de fichier
```
Crudmick met d'office les extensions de images les plus classiques comme possible.(image/*)

# permettre d'ajouter ou supprimer une entité dans une autre (collection)

[doc](https://symfony.com/doc/current/reference/forms/types/collection.html)

Utiliser MANYTOMANY

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

# ID

## Fortement conseillé:

- search: pour donner les champs de recherche pour knp ex: *  SEARCH:['id','titre','article']

## Optionnels:

- hide:{"roles[0]":"ROLE_SUPERADMIN"} //cache sauf pour
- tpl:no_created
- tpl:no_deleted
- tpl:no_updated
- nocrud //pour proétger une entité des modificatio nde crudmick
- slug:champ (sinon généré automatiquement)
- onlytype (crud mick génère que le fichier form, sert pour les entités que l'on veut juste mettre dans une connection par exmple)
- order:ordre (champ de rangement) et ajouter OrdreTrait ou créer un champ int ex:* ORDRE:{"id":"DESC"}
- filter pour donner un filtre liip au envoie de fichier par les inupt file tpl:{"filter":"petitcarree"}

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
TWIG=JsonPretty\|raw 
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

- An invalid form control with name='' is not focusable est du à un champ qui est required et `hiddden ` ou  `display:none `, suneditor cache le champ et donc si il est required crè cette erreur. On peut utiliser `* OPT:{"required":false}`

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
