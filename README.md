# CrudBundle

Generate crud files from doctrine entity by specifics annotations

## installation

- in directory src/Command/ `git clone git@github.com:cadot-eu/crud.git`

## protection against regeneration

- in id add NOCRUD in comment ORM

## Generate

sc crud:

## Environnement for general configurations

```yml
PARTIE=admin #for the security.yml access
EXTEND=admin/base #template extend
SDIR= for create form, repository with subdirectorie exmampe pages
```

## Annotations code In your entity

### OPT & ATTR

```php
OPT=label=>'class'
OPT=help=>'mobile ou fixe'
```

### For hide this field and no rendered

```php
TPL=no_form no_new no_index no_show no_action_add no_access_deleted
```

### TWIG

```php
date('d/m à H:i', "Europe/Paris")
TWIG=JsonPretty\|raw //use cadot.info/twigbundle
TWIG=u.truncate(8, '...')
split('¤')[1]
```

### ID

- `EXTEND=admin/base.html.twig` for modify EXTEND for this entity
- `PARTIE=admin` form modify PARTIE for this entity
- `no_action_show no_action_edit no_action_delete no_action_new no_index_deletedAt no_index_createdAt no_index_updatedAt` for hide col in template
- `ORDRE='id'=>'DESC'` pour déterminer comment les élements s'affichent par défaut dans le controller
- ` HIDE=roles[0]=>ROLE_SUPERADMIN ` ou

  ```php
   HIDE=email=>a@aa.aa
   HIDE=id=>3
     ``` cache la ligne si email=a@@aa.aa ou que l'id==3

### Language

définir dans translation.yaml les langues
`enabled_locales: ["fr", "en", "jp"]`

### Collection

Add the collection by make:entity and add `,cascade={"persist"}`

#### Example collection

```php
     * @ORM\OneToMany(targetEntity=Caracteristique::class,...,cascade={"remove","persist"})  
     * ALIAS=collection;label=>titre
     * XTRA=allow_add=>true
     * XTRA=prototype=>true
     * XTRA=allow_delete=>true
     * XTRA=entry_options=>['label' => false]
     * TWIG=split('/')|last|u.truncate(20, '...')
```

- ALIAS ...; label=> for show the part in template
- XTRA is for configurated the collection
- TWIG is used for show the filename without path or string and limit lentgh

### Entity

```php
  ...,cascade={"remove","persist"}
  * ALIAS=entity;label=>nom
  * OPT=by_reference=> false //or true
  * OPT=multiple=>true
  * OPT=expanded=>true
```

### Choices et choiceenplace

#### Example choice

```php
     * ALIAS=choice;yes=>oui;non
```

- si on a attend un array en rtour mettre * OPT=multiple=>true
- attention si on ajoute un ; à la fin cela permet de donne la possibilité d'un choix vide
- you can add value and key or only value. (first it's the value in list and the second is the value return)

choiceenplace permet de génerer dans l'index un lien pour changer l'etat automatiquement

### Ckeditor

```php
     * ATTR=data-controller=>'ckeditor'
     * ATTR=data-ckeditor-toolbar-value=>'simple'
```

### File

#### Example file

create a string and add ASSERT File
(Assert is add by crud command automatically)

```php
     #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
     #[Assert\File(
        maxSize: "100M",
        maxSizeMessage: "la taille autorisée de 100M est dépassée.",
        mimeTypes: ["image/jpeg", "image/png"],
        mimeTypesMessage: "Votre fichier n'est pas une image."
    )]
    /*
     * ATTR=accept=>"image/*"
     * OPT=mapped=>false
     * OPT=required=>false
     * TPL=index_FileImage
     * TPL=text-center
     */
```

##### Image

- pour les images possibilités de mettre `TPL=index_FileImage` pour faire apparaitre une miniature à la place du nom accompagné d'une **tool**tip (filtre TWIG possible)
- et `TPL=index_FileImageNom` pour les deux sans tooltip

### Color choice

Create a string and add `ALIAS=color`
You have the class boxcolor for change rendered

```css
 .boxcolor{
    width:100%;
  height: 1rem;
}
```

### Hidden

Create a hiddentype for a field

``` php
ALIAS=hidden
```

### Password

```php
  /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\Length(
     *      min = 8,
     *      max = 20,
     *      minMessage = "un minimum de 8 lettres/chiffres",
     *      maxMessage = "20 cratères maxi"
     * )
     * OPT=help=>'entre 8 et 20 caractères'
     * OPT=label=>'Mot de passe'
     * ALIAS=password
     * TPL=no_index
     * TPL=no_new
     */
    private $password;
```

#### astuces

pour cacher deprecated of composer
SYMFONY_DEPRECATIONS_HELPER=weak

### datetime

Quand on met un champ datetime avec no_new (pas visible), il faut le mettre en `nullable=true` dans l'entité et dans son set le ? avant \DateTime

```php
public function set...(?\Datetime ...): self
    {
            $this->date = new DateTime('now');
...
```

## ajouter un template sans trop de modifs

- mettre dans le répertoire public les répertoires css, images...
- ajouter le code de la page html
- modifier les urls en ajoutant /build/
- ajouter defer dans les scripts
- supprimer les bootstrap et jqueryc

### JS

```js
 $('input[type="file"]').on('change', function (e) {
  $(this).parent().find('.asserError').remove();
  let totalInput = 0; // size total input
  $(this.files).each(function (index, element) {
    totalInput += element.size;
    totalForm += totalInput;
    //control one file of input
    if (this.dataset.assert_maxsize)
      if (element.size > this.dataset.assert_maxsize.replace('k', '000').replace('M', '000000')) {
        this.value = "";//remove files
        var span = document.createElement("span");
        span.innerHTML = '<span class="assertError">' + this.dataset.assert_maxsizemessage + '</span>';
        this.parentNode.append(span);
      }
    //control mime type of one file
    if (this.dataset.assert_mimetypes) {
      let input = this
      let mimes = this.dataset.assert_mimetypes
      if (!mimes.split(',').includes(element.type)) {
        var span = document.createElement("span");
        span.innerHTML = '<span class="assertError">' + input.dataset.assert_mimetypesmessage + '</span>';
        input.parentNode.append(span);
        this.value = "";//remove files
      }
    }
  });
});
```

OPT=required=>false retire l'erreur not focusable de google

 /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

### Erreurs fréquentes

- An invalid form control with name='' is not focusable est du à un champ qui est required et ` hiddden ` ou  ` display:none `, ckeditor cache le champ et donc si il est required crè cette erreur. On peut utiliser ` * OPT=required=>false`