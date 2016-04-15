var gulp = require('gulp');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var clean = require('gulp-clean');
var copy = require('gulp-copy');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var filerev = require('gulp-file-rev');
var gulpsync = require('gulp-sync')(gulp);

gulp.task('concat', function () {
  return gulp
    .src([
		    'bower_components/jquery/dist/jquery.js',
		    'bower_components/jquery-ui/jquery-ui.js',
		    'bower_components/jquery-searcher/dist/jquery.searcher.js',
                    'src/js/jquery.jqplot.js',
                    'src/js/jqplot.dateAxisRenderer.js',
                    'src/js/jqplot.cursor.js',
                    'src/js/jqplot.highlighter.js',
                    'src/js/jquery.tablesorter.js',
                    'bower_components/fastclick/lib/fastclick.js',
                    'bower_components/foundation/js/foundation.js',
                    'bower_components/Chart.js/Chart.js',
                    'bower_components/select2/dist/js/select2.js',
                    'src/js/datatables.js'

    ])
    .pipe(concat('thirdpartylibs.js'))
    .pipe(gulp.dest('tmpdist/'))
  ;
});



gulp.task('sass-default-theme', function () { // WARNING: potential duplicate task
  return gulp
    .src('scss/app.scss')
    .pipe(sass({includePaths:['bower_components/foundation/scss'] }))
    .pipe(rename('default.css'))
    .pipe(gulp.dest('../styles/'))
  ;
});

gulp.task('sass-theme01',  function () { // WARNING: potential duplicate task
  return gulp
    .src('scss/app-theme01.scss')
    .pipe(sass({includePaths:['bower_components/foundation/scss'] }))
    .pipe(rename('theme01.css'))
    .pipe(gulp.dest('../styles/'))
  ;
});

gulp.task('sass',['sass-default-theme','sass-theme01']);

gulp.task('clean-srcs', function () {
  return gulp
    .src(['js/*','css/*', 'tmpdist/*', 'build/src/*', 'build/minified/*'], { read: false })
	       .pipe(clean());
  ;
});

gulp.task('clean',['clean-srcs']);

gulp.task('copy', function() {
  return gulp.src([
    'tmpdist/*',
    'bower_components/jquery.cookie/jquery.cookie.js',
    'bower_components/jquery-placeholder/jquery.placeholder.js',
    'bower_components/modernizr/modernizr.js',
    'src/js/local.js'
  ])
    .pipe(copy('build/src/',{ prefix: 2 }));

});


gulp.task('uglify', function () {
  return gulp.src('build/src/*.js')
     .pipe(uglify())
     .pipe(rename({suffix: '.min' }))
     .pipe(gulp.dest('build/minified/'));
});


gulp.task('filerev', function () {
  return gulp.src('build/minified/*.js')
    .pipe(gulp.dest('../js/'));
});

gulp.task('watch', function () {
  gulp.watch('Gruntfile.js', [ /* dependencies */ ]);
});

gulp.task('watch', function () {
  gulp.watch('scss/**/*.scss', [ /* dependencies */ ]);
});

gulp.task('build', ['sass']);


gulp.task('publish', ['clean','build','copy']);

gulp.task('devpublish', gulpsync.sync(['clean','build','concat','copy','uglify','filerev']));

gulp.task('default', ['build','watch']);

