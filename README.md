# generate

TWIG:join(',')
OPT:{"multiple":true,"expanded":true}
ATTR:{"data-controller":"onecheckbox"}

Possibilités de surchargé les attr et opt .

## ALIAS

- simple
- choice (options:["ROLE_USER","ROLE_ADMIN","ROLE_EDITEUR"])
- choiceenplace (options:{"0":"<i class=\"bi bi-toggle-off\"></i>","1":"<i class=\"bi bi-toggle-on\"></i>"})
- entity
- collection
- color
- password
- hidden
- image (possibilité d'ajouter tpl index_FileImage ou index_FileImageNom)

## TPL

- no_index
- no_form
- index_FileImage
- index_FileImageNom

## ID

- hide:{"roles[0]":"ROLE_SUPERADMIN"}

### TPL

- no_action_add
- no_access_deleted
- ORDRE=nom=>ASC