<?php

namespace Deployer;

host('51.159.18.14')
    ->user('root')
    ->set('deploy_path', '/var/www/vhosts/itstacks.net/staging-coiffme-api.itstacks.net/');

task('deploy', function () {

    cd('/var/www/vhosts/itstacks.net/staging-coiffme-api.itstacks.net');

    writeln('Pulling master branch..');

    try {
        run('git pull origin master');
    } catch (\Exception $e) {
        $notGit = 'not a git repository';
        if (preg_match("/{$notGit}/i", $e->getMessage())) {
            run('git clone git@bitbucket.org:ITStacks/coiff-me-api-platform.git .');
        }
    }

    writeln('Changes pulled successfully');

    writeln('composer install');

    run('composer install');

    writeln('php bin/console doctrine:schema:update --force');
    run('php bin/console doctrine:schema:update --force');
});
