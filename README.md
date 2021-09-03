# Sweetchuck/composer-repo-path

[![CircleCI](https://circleci.com/gh/Sweetchuck/composer-repo-path/tree/1.x.svg?style=svg)](https://circleci.com/gh/Sweetchuck/composer-repo-path/?branch=1.x)
[![codecov](https://codecov.io/gh/Sweetchuck/composer-repo-path/branch/1.x/graph/badge.svg?token=J4L164YSGO)](https://app.codecov.io/gh/Sweetchuck/composer-repo-path/branch/1.x)

Composer plugin to download packages which are referenced in
`composer.json#/repositories` with type "path".

Problem is that when there is a composer.json like the one below, then the
`../../foo/bar` directory has to be exists _before_ the `composer update` or
`composer install` commands are invoked. This plugin tries to download them in
an early phase by subscribing to the "pre command" events.
```json
{
    "repositories": {
        "foo/bar": {
            "type": "path",
            "url": "../../foo/bar-1.x"
        }
    },
    "require": {
        "foo/bar": "*"
    }
}
```


## Usage - global (recommended)

1. `composer global require sweetchuck/composer-repo-path`
2. `cd somewhere/my/project-01`  
   Edit your `composer.json` according to the following example:
   ```json
   {
      "name": "my/project-01",
      "minimum-stability": "dev",
      "prefer-stable": true,
      "config": {
          "preferred-install": "dist"
      },
      "repositories": {
          "foo/bar": {
               "type": "path",
               "url": "../../foo/bar-1.x",
               "options": {
                    "repo-path": {
                        "url": "git@example.com:foo/bar.git",
                        "branch": "1.x"
                    }
               } 
          }
      },
      "require": {
          "foo/bar": "*"
      }
   }
   ```
3. `composer update`
4. `ls -la '../../foo/bar-1.x'`
   >  Should be a regular directory.
5. `ls -la 'vendor/foo'`
   >  "bar" should be a symlink.


## Usage - project

Without a globally pre-installed `sweetchuck/composer-repo-path` plugin things
are a little more complicated.  
When `composer update` command runs first time in a clean project, then the
`sweetchuck/composer-repo-path` plugin is not available, and if the
`../../foo/bar-1.x` directory is not exists, then the Composer will throw an
error.

The [composer-suite](https://github.com/Sweetchuck/composer-suite) plugin helps
to solve this problem.

1. `cd somewhere/my/project-01`
2. composer.json
   ```json
   {
       "name": "my/project-01",
       "minimum-stability": "dev",
       "prefer-stable": true,
       "config": {
           "preferred-install": "dist"
       },
       "repositories": {},
       "require": {
           "foo/bar": "^1.0"
       },
       "require-dev": {
           "sweetchuck/composer-repo-path": "1.x-dev",
           "sweetchuck/composer-suite": "1.x-dev"
       },
       "extra": {
          "composer-suite": {
              "local": [
                  {
                      "type": "prepend",
                      "config": {
                          "parents": ["repositories"],
                          "items": {
                              "foo/bar": {
                                  "type": "path",
                                  "url": "",
                                  "options": {
                                      "repo-path": {
                                          "url": "git@example.com:foo/bar.git",
                                          "remote": "upstream",
                                          "branch": "1.x"
                                      }
                                  }
                              }
                          }
                      }
                  },
                  {
                      "type": "replaceRecursive",
                      "config": {
                          "parents": ["require"],
                          "items": {
                              "foo/bar": "*"
                          }
                      }
                  }
              ]
          }
       }
   }
   ```
3. `composer update`
4. `composer suite:generate`
5. optional: `COMPOSER='composer.local.json' composer repo-path:download`
6. `COMPOSER='composer.local.json' composer update`


## @TODO


### Command - composer repo-path:list

List all the packages that are installed by symlinks.

Example: `composer repo-path:list

Example output: space separated package names.

> foo/bar baz/doo tab/fal

--format='json|yaml|csv' option would be handy.

Together with [Sweetchuck/composer-suite](https://github.com/Sweetchuck/composer-suite)
```json
{
  "scripts": {
    "suite:install:local": [
      "rm composer.local.json composer.local.lock",
      "composer suite:generate",
      "cp composer.lock composer.local.lock",
      "COMPOSER='composer.local.lock' composer update $(composer repo-path:list)"
    ],
    "suite:reset": [
      "rm $(COMPOSER='composer.local.lock' composer repo-path:list --format=json | jq 'find install dirs of the symlinked packages')",
      "composer install"
    ]
  }
}
```
