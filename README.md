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

# MkDocs theme

Add the file [extra.css](MkDocs/extra.css) to the `mkdocs.yml` configuration:

```yaml
extra_css:
    - assets/stylesheets/extra.css
```
