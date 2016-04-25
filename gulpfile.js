'use strict';

const gulp = require('gulp');
const spawn = require('child_process').spawn;
const exec = require('child_process').exec;
const fs = require('fs-promise');

gulp.task('update', () => {
  spawn('composer', ['update'], { stdio: 'inherit' });
});

gulp.task('test', () => {
  spawn('./vendor/bin/phpunit', ['test'], { stdio: 'inherit' });
});

gulp.task('coverage', () => {
  exec('./vendor/bin/phpunit --version', function(err, result) {
    var args = [];

    var version = result.match(/^PHPUnit ([^ ]+)/i)[1];

    if (version.match(/^5.*/)) {
      args.push('--whitelist=./src/');
    }
    args.push('--coverage-html=./coverage');
    args.push('test');

    fs.remove('./coverage')
      .then(() => fs.mkdirp('./coverage'))
      .then(() => {
        spawn('./vendor/bin/phpunit', args, { stdio: 'inherit' });
      });
  })
});

gulp.task('doc', () => {
  spawn('./vendor/bin/phpdoc.php', ['-d', './src/', '-t', './docs/api/'], { stdio: 'inherit' });
});

gulp.task('default', () => {
  console.log('Run:');
  console.log('  update');
  console.log('  test');
  console.log('  coverage');
  console.log('  doc');
});