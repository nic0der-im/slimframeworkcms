var gulp = require('gulp');
var minify = require('gulp-clean-css');
var rename = require('gulp-rename');
var gp_uglify = require('gulp-uglify');

gulp.task('minifycss', function () {

    gulp.src('public/css/*.css')
      .pipe(minify({keepBreaks: true}))
      .pipe(rename({
          suffix: '.min'
      }))
      .pipe(gulp.dest('public/dist/css/'));

    gulp.src('public/css/basic/sources/*.css')
      .pipe(minify({keepBreaks: true}))
      .pipe(rename({
          suffix: '.min'
      }))
      .pipe(gulp.dest('public/dist/css/'));

    gulp.src('public/css/0km/sources/*.css')
      .pipe(minify({keepBreaks: true}))
      .pipe(rename({
          suffix: '.min'
      }))
      .pipe(gulp.dest('public/dist/css/'));

    gulp.src('public/css/modules/sources/*.css')
      .pipe(minify({keepBreaks: true}))
      .pipe(rename({
          suffix: '.min'
      }))
      .pipe(gulp.dest('public/dist/css/'));

    gulp.src('public/css/vehiculos/sources/*.css')
      .pipe(minify({keepBreaks: true}))
      .pipe(rename({
          suffix: '.min'
      }))
      .pipe(gulp.dest('public/dist/css/'));
});

gulp.task('minifyjs', function () {
    gulp.src('public/js/*.js')      
      .pipe(rename({
          suffix: '.min'
      }))
      .pipe(gp_uglify())
      .pipe(gulp.dest('public/dist/js/'));

      gulp.src('public/js/pages/*.js')      
      .pipe(rename({
          suffix: '.min'
      }))
      .pipe(gp_uglify())
      .pipe(gulp.dest('public/dist/js/'));
    
});

gulp.task('default', ['minifycss', 'minifyjs'], function() {

});