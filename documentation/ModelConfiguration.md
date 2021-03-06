# Model Configuration

Models added to the CMS are analyzed to generate sensible defaults. 
The model's code, related (translation) models and database table will be inspected for this.
 
These default settings may be overridden for any model by using cms model configuration files. 
These are simple array-returning files (the same as Laravel config files).
Anything not defined in model configuration will fall back to the defaults generated by analysis.

You can add models to the CMS by adding them to the `cms-models.models` configuration array.


## Configuration Files

If a model is added but no configuration file set up for it, CMS defaults will be used.

To override a model, create a `php` or `json` file with the exact same filename as the model class file name, and place this in the `app/Cms/Models` directory.

The file extension may be anything you like, but only PHP content that returns an array, or JSON data is allowed.
 
**PHP** content:

```php
<?php
return [
    // Model configuration here    
];
```

Any PHP code that reliably returns an array with model configuration data is acceptable.
Keep in mind that it is possible to enable caching for model configuration data, 
in which case the file will not be re-run as long as model configurations are in cache.

**JSON** content:

```
{"reference":"title","form":{"fields":{"title":null,"body":null}}}
```

Note that whitespace does not make a difference, any valid JSON data is acceptable.

## Configuration Namespace

The `app/Cms/Models` directory is not hard-coded. The configuration namespace may be configured by setting the `cms-models.collector.source.dir` value to a directory path.
The base directory and namespace where your application models are located can be configured as well (it is `app/Models`, `App\Models\`, by default).

When overriding a model with a nested namespace, such as `App\Models\Library\Book`, place the configuration file in the same relative path to the CMS models dir as the model is to the default model location. 
With default settings, this would be: `app/Cms/Models/Library/Book.php`.


## Specific Configuration Sections

All sections of a model configuration are optional.
Only the sections and fields included are used, otherwise the CMS defaults are used.

- [General & Meta Data](ModelConfiguration/Meta.md)
- [List columns, filter options, scopes, etc.](ModelConfiguration/List.md)
- [Form fields and layout](ModelConfiguration/Form.md)
- [Show page](ModelConfiguration/Show.md)
- [Export strategies and columns](ModelConfiguration/Export.md)


## Example Model Configuration

```php
<?php

return [

    'meta' => [
        'controller' => '\App\Custom\Controller',
    ],

    'reference' => 'title',

    'list' => [

        'columns' => [
            'id',
            'checked',
            'author.name',
            'title',
            'type',
            'created_at',
        ],

        'filters' => [
            'custom' => [
                'label'    => 'Custom Search',
                'target'   => 'author.first_name,author.last_name',
                'strategy' => 'string-split',
            ],
            'title',
            'any' => [
                'label'    => 'Anything',
                'target'   => '*',
                'strategy' => 'string-split',
            ],
        ],

        'scopes' => false,

        'page_size' => [ 20, 40, 60 ],
    ],

];
```

This configuration would define some columns to be present in the listing, some filters to allow quick searches. 
No tabs would be displayed for model scopes, and users could select some specific page sizes.
