<?php

namespace JoliCode\OssTheme;

use Castor\Helper\PathHelper;
use Symfony\Component\Process\Process;

use function Castor\context;
use function Castor\finder;
use function Castor\fs;
use function Castor\io;
use function Castor\run;
use function Castor\slug;

/**
 * The directory of zensical.toml, relative to the project root ("" when at the root).
 */
function config_dir(): string
{
    static $dir = null;

    if (null !== $dir) {
        return $dir;
    }

    $root = PathHelper::getRoot();

    if (file_exists($root . '/zensical.toml')) {
        return $dir = '';
    }

    $files = iterator_to_array(finder()->files()->in($root)->name('zensical.toml')->depth('< 3')->exclude(['.castor', 'node_modules', 'vendor']), false);

    if (1 !== \count($files)) {
        throw new \RuntimeException(\sprintf('Expected one zensical.toml in the project, found %d.', \count($files)));
    }

    return $dir = $files[0]->getRelativePath();
}

function config_path(): string
{
    return ltrim(config_dir() . '/zensical.toml', '/');
}

/**
 * A string value of zensical.toml, read with a regex: good enough for the keys the plugin needs.
 *
 * ponytail: PHP has no TOML parser, a real one if a key ever needs more than a quoted string
 */
function config_value(string $key, ?string $default = null): string
{
    $toml = file_get_contents(PathHelper::getRoot() . '/' . config_path());

    if (preg_match(\sprintf('/^\s*%s\s*=\s*["\']([^"\']+)["\']/m', preg_quote($key, '/')), (string) $toml, $matches)) {
        return $matches[1];
    }

    return $default ?? throw new \RuntimeException(\sprintf('The key "%s" is missing in %s.', $key, config_path()));
}

/**
 * Install the theme assets, the macros and the templates in the project, next to its own files.
 */
function install_assets(): void
{
    io()->title('Installing the JoliCode theme in the documentation');

    // The versions build checks out old tags, which may lack zensical.toml: the paths are resolved once, on the current branch
    static $dirs = null;
    $dirs ??= [
        rtrim(PathHelper::getRoot() . '/' . config_dir(), '/'),
        config_value('docs_dir', 'docs'),
        config_value('custom_dir'),
    ];
    [$dir, $docsDir, $customDir] = $dirs;
    $docsDir = $dir . '/' . $docsDir;
    $customDir = $dir . '/' . $customDir;
    $theme = \dirname(__DIR__) . '/Zensical';

    fs()->copy($theme . '/extra.css', $docsDir . '/assets/stylesheets/jolicode.css');
    fs()->copy($theme . '/jolicode_macros.py', $dir . '/jolicode_macros.py');
    fs()->copy($theme . '/overrides/jolicode.html', $customDir . '/jolicode.html');
    fs()->copy($theme . '/overrides/partials/header.html', $customDir . '/partials/header.html');

    // The footer snippet is static HTML with placeholders (see the README): make it a template reading zensical.toml
    $subtitle = <<<'HTML'
        {{ config.extra.short_name | d(config.site_name) }} is licensed under
        <a href="{{ config.repo_url }}/blob/main/{{ config.extra.license_file | d('LICENSE') }}" target="_blank" rel="noreferrer noopener" class="jf-link">
          MIT license
        </a>
        HTML;
    $footer = (string) file_get_contents(\dirname(__DIR__) . '/snippet-joli-footer.html');
    $footer = '{% set github_repo = config.repo_url | replace("https://github.com/", "") %}' . "\n" . $footer;
    $footer = str_replace('#GITHUB_REPO', '{{ github_repo }}', $footer);
    $footer = str_replace('<!-- #SUBTITLE -->', $subtitle, $footer);

    fs()->dumpFile($customDir . '/jolicode-footer.html', $footer);
}

function docker_image(): string
{
    // A project with its own Dockerfile next to zensical.toml gets its own image (extra tools)
    if (file_exists(PathHelper::getRoot() . '/' . config_dir() . '/Dockerfile')) {
        return 'jolicode-zensical-' . slug(basename(PathHelper::getRoot()));
    }

    return 'jolicode-zensical';
}

function docker_build(): void
{
    $root = PathHelper::getRoot();
    $dockerfile = file_exists($root . '/' . config_dir() . '/Dockerfile') ? $root . '/' . config_dir() . '/Dockerfile' : \dirname(__DIR__) . '/Zensical/Dockerfile';

    io()->title('Building the documentation Docker image');

    run(['docker', 'build', '--tag', docker_image(), '--file', $dockerfile, \dirname($dockerfile)]);
}

/**
 * Run a command in the documentation image, from the project root mounted at /app.
 *
 * @param list<string> $command
 */
function docker_run(array $command): Process
{
    $image = docker_image();

    if (!run(['docker', 'image', 'inspect', $image], context: context()->withAllowFailure()->withQuiet())->isSuccessful()) {
        docker_build();
    }

    return run([
        'docker', 'run', '--init', '--rm', '--network=host',
        '--user', getmyuid() . ':' . getmygid(),
        // Writable home for the caches (social cards fonts...)
        '--env', 'HOME=/tmp',
        '--volume', PathHelper::getRoot() . ':/app',
        '--workdir', '/app',
        $image,
        ...$command,
    ]);
}

/**
 * @param list<string> $arguments
 */
function zensical(array $arguments): Process
{
    return docker_run(['zensical', ...$arguments, '--config-file', config_path()]);
}

/**
 * No --config-file: mike builds the old tags too, which may still have a mkdocs.yml, it finds the config itself.
 *
 * @param list<string> $arguments
 */
function mike(array $arguments): Process
{
    return docker_run(['mike', ...$arguments]);
}
