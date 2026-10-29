# Footer

Two variables should be replaced:

- `#GITHUB_REPO`: `jolicode/castor` — used for the GitHub link and as the
  `utm_source` of the links to jolicode.com, so traffic can be attributed to
  each project in our analytics. The `sed` command below replaces all
  occurrences.
- `<!-- #SUBTITLE -->`: Any sentence. Can include links.
  ```html
  Castor is licensed under
  <a href="https://github.com/jolicode/castor/blob/main/LICENSE" target="_blank" rel="noreferrer noopener" class="jf-link">
    MIT license
  </a>
  ```
  Links must have the `class="jf-link"` attribute.

Example script:

```shell
curl -o joli-footer.html https://raw.githubusercontent.com/jolicode/oss-theme/refs/heads/main/snippet-joli-footer.html
sed -i 's/#GITHUB_REPO/jolicode\/castor/g' joli-footer.html
sed -i 's/<!-- #SUBTITLE -->/Castor is licensed under <a href="https:\/\/github.com\/jolicode\/castor\/blob\/main\/LICENSE" target="_blank" rel="noreferrer noopener" class="jf-link">MIT license<\/a>/' joli-footer.html
```

# MkDocs theme

Add the file [extra.css](MkDocs/extra.css) to the `mkdocs.yml` configuration:

```yaml
extra_css:
    - assets/stylesheets/extra.css
```

# Zensical theme

The repository is a [Castor plugin](https://castor.jolicode.com/going-further/extending-castor/remote-imports/)
that builds a Zensical documentation with the JoliCode theme and footer. Every
project uses the `main` branch, with no lock file: a change here reaches every
documentation at its next build, with nothing to update in the projects.

## Installation

In `castor.composer.json`:

```json
{
    "require": {
        "jolicode/oss-theme": "dev-main"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jolicode/oss-theme"
        }
    ]
}
```

In `castor.php`:

```php
import('composer://jolicode/oss-theme');
```

In `.gitignore`, the lock and the files installed by `doc:assets` (paths for a
`zensical.toml` at the root with `docs_dir = "docs"` and
`custom_dir = "docs/overrides"`):

```gitignore
/castor.composer.lock
/docs/assets/stylesheets/jolicode.css
/docs/overrides/jolicode-footer.html
/docs/overrides/jolicode.html
/docs/overrides/partials/
/jolicode_macros.py
/__pycache__/
```

Without a lock, Castor installs the packages once and keeps them: run
`castor composer update` to get the latest `main`.

## Configuration

Everything is read from `zensical.toml`, found at the root of the project or up
to two directories deep (`tools/doc/zensical.toml` for instance):

```toml
[project]
repo_url = "https://github.com/jolicode/automapper"

[project.extra]
# Suffix of the page titles, defaults to site_name
short_name = "AutoMapper"
# Text next to the logo in the header, defaults to site_name
header_title = "AutoMapper"
# Path of the license in the repository, defaults to LICENSE
license_file = "LICENSE.md"

[project.theme]
custom_dir = "docs/overrides"
```

`custom_dir` is required, with a `main.html` extending the template of the plugin:

```jinja
{% extends "jolicode.html" %}
```

The template sets the page titles and adds the JoliCode footer, the project adds
its own blocks (`extrahead`...) as usual. The footer reads `repo_url`,
`short_name` and `license_file` for its links.

The [macros](https://zensical.org/docs/compatibility/mkdocs/plugins/#macros)
`php()` and `markdown()` of `jolicode_macros.py` include files of the project,
with paths relative to its root:

```toml
[project.plugins.macros]
module_name = "jolicode_macros"
on_error_fail = true
```

A project needing its own macros keeps a `main.py` that loads the module of
the plugin, installed next to `zensical.toml`:

```python
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import jolicode_macros


def define_env(env):
    jolicode_macros.define_env(env)

    @env.macro
    def my_macro():
        ...
```

A `Dockerfile` next to `zensical.toml` replaces the image of the plugin, for a
project needing more tools: start it with the same `pip install` line as
[Zensical/Dockerfile](Zensical/Dockerfile).

## Tasks

- `doc:build`: builds the website in strict mode, after `doc:assets`;
- `doc:serve [--port 8000]`: serves it and rebuilds on every change;
- `doc:assets`: installs the CSS, the macros and the templates in the project;
- `doc:docker-build`: (re)builds the image, done once automatically;
- `doc:build-versions [--min-version X.Y.Z]`: for a versioned documentation (`extra.version.provider = "mike"`), builds the `dev` version and
  the last patch of every minor since `--min-version` with mike, in `site/`
  like `doc:build`, for a `zensical.toml` at the root of the project.

The functions of `src/functions.php` (`zensical()`, `mike()`, `docker_run()`,
`install_assets()`) build custom tasks on top of them.

## Theme only

Add the file [extra.css](Zensical/extra.css) to the `mkdocs.yml` (or `zensical.toml`) configuration:

```yaml
extra_css:
    - assets/stylesheets/extra.css
```

Works with both the `classic` and `modern` theme variants.
