# generate

TWIG:join(',')
OPT:{"multiple":true,"expanded":true}
ATTR:{"data-controller":"onecheckbox"}

Possibilités de surchargé les attr et opt .

## ALIAS

- vide
- simple
- simplelanguage
- normal possibilité d'ajouter un filtre * attr:{"data-base--suneditor--upload-value":"article/hd"} ou un autre répertoire de destination pour les images envoyé dans ckeditor
- choice (options:["ROLE_USER","ROLE_ADMIN","ROLE_EDITEUR"] ou options:{"client":"ROLE_USER","administrateur":"ROLE_ADMIN"} et possibilté d'imposé un choix seul sur un json ou array ATTR:{"data-controller":"base--onecheckbox"})
- choiceenplace
  
  ```php
     * choiceenplace
     * xtra:{"champ":"Sur l'accueil"}
     * options:{"0":"<i class=\"bi bi-toggle-off\"></i>","1":"<i class=\"bi bi-toggle-on\"></i>"}
     * TPL:no_form
  ```
_ onechoiceenplace permet de mettre tous les champs à false et un seul à true

- entity
  ` (pour choisir le champ affiché * options:{"label":"nom"} et pour avoir un choix vide possible * OPT:{"required":false} * OPT:{"empty_data":null}) `
- collection
- color
- password
- hidden
- image (possibilité d'ajouter tpl index_FileImage ou index_FileImageNom)
- fichier 
- money
- array (options=>separation et label)
- onetomany: ajouter options:{"champ":"nom"} pour définir le champ à afficher dans l'index
  

## TPL
- no_created
- no_updated
- no_index
- no_form
- index_FileImage
- index_FileImageNom


## OPT et ATTR

  - `OPT:{"multiple":true,"expanded":true}`
  - `ATTR:{"data-controller":"onecheckbox"}`

## ID

Fortement conseillé:

- search: pour donner les champs de recherche pour knp ex: *  SEARCH:['id','titre','article']

Optionnels:

- hide:{"roles[0]":"ROLE_SUPERADMIN"}
- tpl:no_created
- tpl:no_deleted
- tpl:no_updated
- nocrud
- slug:champ 
- onlytype
- order:ordre (champ de rangement) et ajouter OrdreTrait ou créer un champ int ex:* ORDRE:{"id":"DESC"}
- select: pour la boite de recherche ex: * SELECT:{"entitie":"article","affichage":"titre","champs":"titre","copy":"slug","copyurl":"/les-articles/","limit":30}
- viewer: url et champ pour créer le lien pour visionner l'objet dans un nouvel onglet ex:* VIEWER:{"url":"/les-articles","champ":"slug"}
- filter pour donner un filtre liip au envoie de fichier par les inupt file tpl:{"filter":"petitcarree"}
### TWIG

```php
date('d/m à H:i', "Europe/Paris")
TWIG=JsonPretty\|raw 
TWIG=u.truncate(8, '...')
split('¤')[1]
```

### Collection
Pour avoir une liste de choix de sur une entité
```php
/**
     * entity
     * label:nom
     * OPT:{"help":"multiple sélection et retirer une sélection avec CTRL + click"}
     * OPT:{"required":false}
     * twig:json_encode
     */
```

```php
 /**
     * collection
     * options:{"field":"label"}
     * xtra:{"allow_add":true,"prototype":true,"allow_delete":true,"entry_options!":"[\"label\"=>false]"}
     * tpl:no_index
     * opt:{"required":false}
     */
```
### hiddenroot et readonlyroot

Ils permettent d'afficher ou de bloquer l'édition pour les utilisateurs différents de m@cadot.eu
Ils apellent les jscontrollers hiddenroot_controller et readonlyroot_controller

```php
/**
     * readonlyroot ou hiddenroot
     * TPL:no_index
*/
```

### un champ peut accéder à la valeur d'una autre champ

par exemple pour que le champ ckeditor toolbar value prennent la valeur du champ getTypenom

```php
     * attr:{"data-controller" : "base--ckeditor"}
     * attr:{"data-base--suneditor--toolbar-value": "§$AtypeOption[\"data\"]->getTypenom()§"}
```

### Erreurs fréquentes

- An invalid form control with name='' is not focusable est du à un champ qui est required et ` hiddden ` ou  ` display:none `, suneditor cache le champ et donc si il est required crè cette erreur. On peut utiliser ` * OPT=required=>false`

- `Warning: include(/app/vendor/composer/ ... ): Failed to open stream: No such file or directory`
faire un `composer dump-autoload`cela réactualise les fichiers mémorisé par composer

### Trait

- etat: brouillon, en ligne à vérifier par ajax
- situation: actif / inactif
- time: updated, created, deleted
- categories
- vérified: on off
  
```php
    use TimeTrait;
    use EtatTrait;
    use VuesTrait;

```

### Slug

le Slug est généré par SlugTrait et doctrinesubscriber.
il est possible de choisir un champ en mettant dans l'id
slug:champ

