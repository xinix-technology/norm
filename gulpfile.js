'use strict';

const gulp = require('gulp');
const spawn = require('child_process').spawn;
const fs = require('fs-promise');

gulp.task('test', () => {
  spawn('./vendor/bin/phpunit', ['test'], { stdio: 'inherit' });
});

gulp.task('coverage', () => {
  fs.remove('./coverage')
    .then(() => fs.mkdirp('./coverage'))
    .then(() => {
      spawn('./vendor/bin/phpunit', ['--whitelist=./src/', '--coverage-html=./coverage', 'test'], { stdio: 'inherit' });
    });
});

gulp.task('default', () => {
  console.log('Run:');
  console.log('  test');
  console.log('  coverage');
});