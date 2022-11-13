# How to Set Up Magento 2 Enterprise Edition (EE)

## Step 1: Clone the repositories

Clone the `magento2ce` and `magento2ee` repositories. It's easiest if you clone both under your web server document root but it's up to you.

## Step 2: Link the repositories

Magento enables you to build EE from these cloned repositories in any of the following ways:

- Copy contents of the `magento2ee` repository to the `magento2ce`, replacing all duplicated files. Although this way is simpler, it doesn't allow you to updated code from the upstream repositories in the future.

- Use the `dev/tools/build-ee.php` script from the `magento2ee` repository. It creates *symlinks* to *EE code* in  the`magento2ee` repository.

To link the repositories using `dev/tools/build-ee.php`, run the following commands:

```shell
php magento2ee/dev/tools/build-ee.php <options>
cp magento2ee/composer.json magento2ce/
rm -rf magento2ce/composer.lock
```

where `<options>` are defined as follows:

```console
     --command <link>|<unlink>  Link or Unlink EE code      Default: link
     --ce-source <path/to/ce>   Path to CE clone            Default: magento2ce (change this value if you cloned the repository to a directory with a different name or location)
     --ee-source <path/to/ee>   Path to EE clone            Default: magento2ee (change this value if you cloned the repository to a directory with a different name or location)
     --exclude <true>|<false>   Exclude EE files from CE    Default: false
     --help                     This help
```

This script works in two ways:

- Create/delete symlinks from the EE repository to the CE repository and create/delete the exclude file (*magento2ce/.git/info/exclude*).
- Only create/delete symlinks.

Normally you should link CE and EE; however, you can unlink to update code from the repositories for example.

### Examples

Link with creating the exclude file:

```shell
php ./dev/tools/build-ee.php -- --command link --exclude true
```

Link without creating the exclude file:

```shell
php ./dev/tools/build-ee.php -- --command link
```

Unlink:

```shell
php ./dev/tools/build-ee.php -- --command unlink
```

## Step 3: Install the Magento software from the `magento2ce` repository

***Note:***

When `magento2ee` is cloned and linked outside the `magento2ce` folder, by default EE modules will not be registered in your Magento installation. To get them registered, you need to change the path to them in `ComponentRegistrar`. For this, add the following code to the `register()` method in `magento2ce/lib/internal/Magento/Framework/Component/ComponentRegistrar.php`:

```php
$path = str_replace('magento2ee', 'magento2ce', $path);
```
