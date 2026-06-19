'use strict';

const gulp   = require('gulp');
const sass   = require('gulp-sass')(require('sass'));
const rename = require('gulp-rename');
const uglify = require('gulp-uglify');
const concat = require('gulp-concat');

const paths = {
  js_backend : ['js/doliopi.js', 'js/modules/*.js']
};

gulp.task('js_backend', function() {
  return gulp.src(paths.js_backend)
    .pipe(concat('doliopi.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest('./js/'));
});

/** Watch */
gulp.task('default', function() {
  gulp.watch(paths.js_backend[1], gulp.series('js_backend'));
});
