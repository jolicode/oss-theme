<?php

namespace JoliCode\OssTheme\Castor;

use Castor\Attribute\AsTask;
use Castor\Helper\PathHelper;

use function Castor\context;
use function Castor\fs;
use function Castor\io;
use function Castor\run;
use function JoliCode\OssTheme\config_dir;
use function JoliCode\OssTheme\docker_build;
use function JoliCode\OssTheme\install_assets;
use function JoliCode\OssTheme\mike;
use function JoliCode\OssTheme\zensical;

#[AsTask(name: 'assets', namespace: 'doc', description: 'Install the JoliCode theme assets and templates in the documentation')]
function assets(): void
{
    install_assets();
}

#[AsTask(name: 'docker-build', namespace: 'doc', description: 'Build the documentation Docker image')]
function docker_image(): void
{
    docker_build();
}

#[AsTask(name: 'build', namespace: 'doc', description: 'Build the documentation website')]
function build(): void
{
    install_assets();

    io()->title('Building the documentation');

    zensical(['build', '--strict']);
}

#[AsTask(name: 'serve', namespace: 'doc', description: 'Serve the documentation and rebuild it on every change')]
function serve(int $port = 8000): void
{
    install_assets();

    io()->title('Serving the documentation');
    io()->writeln(\sprintf('  <info>http://127.0.0.1:%d</info>', $port));

    zensical(['serve', '--dev-addr', \sprintf('0.0.0.0:%d', $port)]);
}

#[AsTask(name: 'build-versions', namespace: 'doc', description: 'Build every version of the documentation with mike, one per tag since --min-version, in site/ like doc:build')]
function build_versions(string $minVersion = '0.0.0'): void
{
    // The tags are checked out with --force, over the files installed by install_assets(): nothing else must be lost
    if ('' !== trim(run('git status --porcelain', context: context()->withQuiet())->getOutput())) {
        throw new \RuntimeException('The working tree is not clean, commit or stash the changes before building the versions.');
    }

    // The branch, or the commit when detached (the CI), to come back to after the tags
    $head = run('git symbolic-ref --quiet --short HEAD', context: context()->withQuiet()->withAllowFailure());
    $head = $head->isSuccessful() ? trim($head->getOutput()) : trim(run('git rev-parse HEAD', context: context()->withQuiet())->getOutput());

    install_assets();

    // mike commits the versions in a gh-pages branch, checked out in site-versions/ while zensical builds
    // each version in site/, then moved to site/ to end up where doc:build writes
    $dir = rtrim(PathHelper::getRoot() . '/' . config_dir(), '/');
    $site = $dir . '/site';
    $versions = $dir . '/site-versions';
    fs()->remove($versions);
    fs()->mkdir($versions);
    run('git config user.name ci-bot');
    run('git config user.email ci-bot@example.org');
    run('git remote remove gh-pages', context: context()->withAllowFailure()->withQuiet());
    // Relative: mike pushes from the container, where the project is mounted elsewhere
    run(['git', 'remote', 'add', 'gh-pages', './' . ltrim(config_dir() . '/site-versions', '/')]);

    $context = context()->withWorkingDirectory($versions);

    run('git init', context: $context);
    run('git checkout -b gh-pages', context: $context);
    run('git config receive.denyCurrentBranch ignore', context: $context);

    mike(['deploy', '--push', '--remote', 'gh-pages', 'dev']);

    $tags = array_filter(array_map('trim', explode("\n", run('git tag --list', context: context()->withQuiet())->getOutput())));
    $latestVersion = null;
    $buildTags = [];

    foreach ($tags as $tag) {
        if (!version_compare($tag, $minVersion, '>=')) {
            continue;
        }

        // Only the last X.Y.Z of each X.Y
        $parts = explode('.', $tag);
        if (3 !== \count($parts)) {
            continue;
        }

        $majorMinor = $parts[0] . '.' . $parts[1];

        if (isset($buildTags[$majorMinor]) && version_compare($tag, $buildTags[$majorMinor], '<=')) {
            continue;
        }

        if (null === $latestVersion || version_compare($tag, $latestVersion, '>')) {
            $latestVersion = $tag;
        }

        $buildTags[$majorMinor] = $tag;
    }

    if (null === $latestVersion) {
        mike(['deploy', '--push', '--remote', 'gh-pages', 'dev', 'latest']);
    }

    foreach ($buildTags as $tag) {
        // A tag may track a file installed by install_assets() (an old lock, the theme CSS), or not track it anymore
        run('git checkout --force tags/' . $tag);
        install_assets();

        if ($tag === $latestVersion) {
            mike(['deploy', '--push', '--remote', 'gh-pages', $tag, 'latest']);
        } else {
            mike(['deploy', '--push', '--remote', 'gh-pages', '--update-aliases', $tag]);
        }
    }

    run('git checkout --force ' . $head);
    run('git reset --hard gh-pages', context: $context);
    fs()->remove($site);
    fs()->rename($versions, $site);
}
