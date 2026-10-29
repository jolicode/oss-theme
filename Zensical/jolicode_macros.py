"""Macros used by the documentation, see https://zensical.org/docs/compatibility/mkdocs/plugins/#macros

Installed next to zensical.toml by the Castor task doc:assets. The paths given
to the macros are relative to the project root, the working directory of the build.
"""

import os
import re

ROOT = os.getcwd()


def define_env(env):
    def read(path):
        file = os.path.join(ROOT, path)
        if not os.path.isfile(file):
            raise FileNotFoundError(f'The file "{path}" included in "{env.page.path}" does not exist.')

        with open(file) as f:
            return f.read()

    @env.macro
    def php(path, strip_header=True, strip_namespace=True):
        """Include a PHP example, without the opening tag and the namespace declaration."""
        content = read(path)
        if strip_header:
            content = re.sub(r"\A<\?php\n\n", "", content)
            if strip_namespace:
                content = re.sub(r"\Anamespace [^;]+;\n\n", "", content)

        return content

    @env.macro
    def markdown(path, start=None, end=None):
        """Include a Markdown file, or only the part between the start and end markers."""
        content = read(path)
        for marker in (start, end):
            if marker and marker not in content:
                raise ValueError(f'The marker "{marker}" was not found in "{path}", included in "{env.page.path}".')
        if start:
            content = content.split(start, 1)[1]
        if end:
            content = content.split(end, 1)[0]

        return content
