# LinkToField plugin for Craft CMS 3.x

Craft plugin that provides a new field type that works like an Entries field type but offers users an easy way to set a different title for the relation than the related page title.

This is particularly useful if you are linking to other content but want to use shorter or different titles in the lists e.g. in menus, submenus or related lists.

## Installation

, follow these steps:

1. Install with Composer via `composer require dolphiq/linktofield` from your project folder
2. Install plugin in the Craft Control Panel under Settings > Plugins
3. The `Link To Field` type will be available when adding a new field - Settings > Fields > Add new field

Link To Field plugin works on Craft 3.x.

## Link To Field


## Using the Link To Field

You can use the field as a normal Entries field type but give the end user the possibility to change te label for each relation/link.

### Usage sample in Twig templates
```
{% for entry in entry.myLinks %}
  {{ entry.title }} - {{ entry.linkFieldLabel }} <br>
{% endfor %}
```

### Usage sample to display the link field if the field is set or use the title field as backup
```
<ul>
{% for entry in entry.menuLinks %}
  <li><a href="{{ entry.url }}" rel="{{ entry.title }}">
  {% if entry.linkFieldLabel != '' %}
    {{ entry.linkFieldLabel }}
  {% else %}
    {{ entry.title }}
  {% endif %}
  </a>
{% endfor %}
</ul>
```

### Contributors & Developers
Johan Zandstra - info@dolphiq.nl
Brought to you by [Dolphiq](https://dolphiq.nl)
