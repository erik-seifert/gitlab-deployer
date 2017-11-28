<?php
namespace Deployer;

require 'recipe/common.php';

if (!getenv('DEPLOY_HOST_PATH')) {
  writeln('Please add DEPLOY_HOST_PATH');
  exit(-1);
}

// Project name
set('application', (getenv('DEPLOY_APP_NAME')) ? getenv('DEPLOY_APP_NAME') : 'app');

// Project repository
set('repository', (getenv('DEPLOY_REPOSITORY')) ? getenv('DEPLOY_REPOSITORY') : getenv('CI_REPOSITORY_URL'));

$hostname = getenv('DEPLOY_HOSTNAME');
$hostname = parse_url($hostname,  PHP_URL_HOST);

$hostname_docksal =  getenv('CI_ENVIRONMENT_URL');
$hostname_docksal = parse_url($hostname_docksal,  PHP_URL_HOST);

writeln('HOSTNAME IS : {{hostname}}');

// Set hostname
set('hostname', $hostname);
set('hostname_docksal', $hostname_docksal);

// Set hostname
set('user', (getenv('DEPLOY_USERNAME')) ? getenv('DEPLOY_USERNAME') : 'root');


set('hostpath', getenv('DEPLOY_HOST_PATH'));

// Set alias
set('alias', (getenv('DEPLOY_ALIAS')) ? getenv('DEPLOY_ALIAS') : '');

host($hostname)
  ->set('deploy_path','/{{hostpath}}/{{CI_ENVIRONMENT_SLUG}}')
  ->user('{{user}}')
  ->addSshOption('UserKnownHostsFile', '/dev/null')
  ->addSshOption('StrictHostKeyChecking', 'no');

// Shared files/dirs between deploys
set('shared_files', [
  'sites/{{drupal_site}}/settings.php',
  'sites/{{drupal_site}}/services.yml',
  '.docksal/docksal-local.env'
]);

set('shared_dirs', [
  'sites/{{drupal_site}}/files',
]);

set('hostnames', function() {
  if (get('alias')) {
    return get('hostname_docksal') . ',' . get('alias');
  }
  return get('hostname_docksal');
});

set('keep_releases', 1);
set('drupal_site', 'default');

task('deploy', [
  'deploy:info',
  'deploy:prepare',
  'deploy:lock',
  'deploy:release',
  'deploy:update_code',
  'deploy:shared',
  'deploy:symlink',
  'docksal:setup',
  'docksal:up',
  'deploy:unlock',
  'cleanup'
]);

task('docksal:up', function() {
  cd('{{release_path}}');
  run('fin up');
});

task('docksal:setup', function() {
  if (test('[ ! -f {{release_path}}/.docksal/docksal-local.env ]')) {
    run('touch {{release_path}}/.docksal/docksal-local.env');
    run('echo "VIRTUAL_HOST={{hostnames}}" > {{deploy_path}}/.docksal/docksal-local.env');
  }
});

task('drush:install', function() {
  if (test('[ ! -f {{release_path}}/.docksal/docksal-local.env ]')) {
    run('echo "VIRTUAL_HOST={{hostnames}}\n COMPOSE_PROJECT_NAME={{hostname_docksal}}" > {{deploy_path}}/.docksal/docksal-local.env');
  }
});