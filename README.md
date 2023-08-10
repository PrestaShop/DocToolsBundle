# DocToolsBundle
Helper tools for documentation

## Installation

As usual, there are a few steps required to install this bundle:

1.a **Add this bundle to your project using composer**:

```bash
$ composer require prestashop/doc-tools-bundle
```

1.b **Add this bundle to your project as a composer dependency (from your forked repository)**:

```javascript
    // composer.json
    {
        // ...
        require-dev: {
            // ...
            "prestashop/doc-tools-bundle": "dev-my-branch"
        },
        // ...
        "repositories": [
            // ...
            {
                "type": "vcs",
                "url": "https://github.com/myfork/DocToolsBundle",
                "canonical": false
            },
            // ...
        ],
    }
```

```bash
$ composer require prestashop/doc-tools-bundle
```

2. **Add this bundle to your application kernel**:

```php
    // app/AppKernel.php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new PrestaShop\DocToolsBundle\DocToolsBundle(),
        );

        return $bundles;
    }
```

3. **Configure the bundle via app/config**

```yml
# app/config/config.yml
doc_tools:
  docs_src_path: /root/path/to/docs/project/src
  cqrs_folder: development/architecture/domain/references
  partial_folder: content/8/development/architecture/domain/references
```

4. **How to use**

This bundle includes a few commands that you can use to generate documentations. The PrestaShop dev documentation is
versioned in this repository https://github.com/PrestaShop/docs, so you will have to clone it as well in order to export
the generated documentation.

## Command list

```bash
prestashop
  prestashop:doc-tools:list-commands-and-queries      Lists available CQRS commands and queries
  prestashop:doc-tools:print-commands-and-queries     Prints available CQRS commands and queries to a file prepared for documentation
```

### prestashop:doc-tools:print-commands-and-queries

When you generate CQRS commands documentation details you should export them into the `content/1.7/development/architecture/domain/references` folder of the Docs project.

```bash
php ./bin/console prestashop:doc-tools:print-commands-and-queries --dir=/path/to/doc_project/src
```

Or if you correctly set the `docs_src_path` config you can simply run:

```bash
php ./bin/console prestashop:doc-tools:print-commands-and-queries
```

5. **How to test**

This bundle includes automatic tests which are run automatically by GitHub actions. You can run them locally by using:

Unit tests:
```bash
composer run unit-tests
```

Integration tests:
```bash
composer run integration-tests
```

Test all:
```bash
composer run test-all
```
