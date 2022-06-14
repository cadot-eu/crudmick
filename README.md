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
- entity
- collection
- color
- password
- hidden
- image (possibilité d'ajouter tpl index_FileImage ou index_FileImageNom)
- money
- array (options=>separation et label)
  

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

### TWIG

```php
date('d/m à H:i', "Europe/Paris")
TWIG=JsonPretty\|raw //use cadot.info/twigbundle
TWIG=u.truncate(8, '...')
split('¤')[1]
```

### Erreurs fréquentes

- An invalid form control with name='' is not focusable est du à un champ qui est required et ` hiddden ` ou  ` display:none `, ckeditor cache le champ et donc si il est required crè cette erreur. On peut utiliser ` * OPT=required=>false`