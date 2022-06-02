# generate

TWIG:join(',')
OPT:{"multiple":true,"expanded":true}
ATTR:{"data-controller":"onecheckbox"}

Possibilités de surchargé les attr et opt .

## ALIAS

- simple
- simplelanguage
- normal
- choice (options:["ROLE_USER","ROLE_ADMIN","ROLE_EDITEUR"] ou options:{"client":"ROLE_USER","administrateur":"ROLE_ADMIN"} et possibilté d'imposé un choix seul sur un json ou array ATTR:{"data-controller":"base--onecheckbox"})
- choiceenplace

```php
     * xtra:{"champ":"situation"}
     * options:{"inactif":"<i class=\"bi bi-toggle-off\"></i>","actif":"<i class=\"bi bi-toggle-on\"></i>"}
     * OPT:{"expanded":true}
```

- entity

```php
     * label:nom
     * OPT:{"help":"multiple sélection avec CTRL"}
     * OPT:{"required":false}
     * tpl:no_index
```

- collection
- color
- password
- hidden
- image (possibilité d'ajouter tpl index_FileImage ou index_FileImageNom)
- money

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

- hide:{"roles[0]":"ROLE_SUPERADMIN"}
- no_created
- no_deleted
- no_updated
- nocrud

### TPL

- no_action_add
- no_access_deleted
- ORDRE=nom=>ASC

