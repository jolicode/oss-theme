# Footer

Two variables should be replaced:

- `#GITHUB_REPO`: `jolicode/castor`
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
