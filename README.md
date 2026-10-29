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

Add the file [extra.css](Zensical/extra.css) to the `mkdocs.yml` (or `zensical.toml`) configuration:

```yaml
extra_css:
    - assets/stylesheets/extra.css
```

Works with both the `classic` and `modern` theme variants.
