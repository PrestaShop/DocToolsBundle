# DocToolsBundle
Helper tools for documentation

## Installation

As usual, there are a few steps required to install this bundle:

1.a **Add this bundle to your project as a composer dependency**:

```javascript
    // composer.json
    {
        // ...
        require-dev: {
            // ...
            "prestashop/doc-tools-bundle": "dev-main"
        }
    }
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

1.c **Run composer require to update your vendor folder**

```bash
$ composer require prestashop/doc-tools-bundle
```

2 **Add this bundle to your application kernel**:

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

3 **How to use**

This bundle includes a few commands that you can use to generate documentations. The PrestaShop dev documentation is
versioned in this repository https://github.com/PrestaShop/docs so you will have to clone it as well in order to export
the generation documentation.