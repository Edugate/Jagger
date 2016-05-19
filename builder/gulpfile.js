var gulp = require('gulp');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var clean = require('gulp-clean');
var copy = require('gulp-copy');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var filerev = require('gulp-file-rev');
var gulpIf = require('gulp-if');
var filesize = require('gulp-filesize');
var merge = require('merge-stream');
var order = require('gulp-order');
var addsrc = require('gulp-add-src');

// foundation6
var sassFoundation6Paths = [
  'bower_components/foundation-sites/scss',
  'bower_components/motion-ui/src',
  'bower_components/foundation-icon-fonts'
];



gulp.task('concat', function () {
 return gulp
    .src('bower_components/jquery/dist/jquery.js')
    .pipe(addsrc.append('bower_components/jquery-ui/jquery-ui.js'))
    .pipe(addsrc.append('bower_components/jquery-searcher/dist/jquery.searcher.js'))
    .pipe(addsrc.append('src/js/jquery.jqplot.js'))
    .pipe(addsrc.append('src/js/jqplot.dateAxisRenderer.js'))
    .pipe(addsrc.append('src/js/jqplot.cursor.js'))
    .pipe(addsrc.append('src/js/jqplot.highlighter.js'))
    .pipe(addsrc.append('src/js/jquery.tablesorter.js'))
    .pipe(addsrc.append('bower_components/fastclick/lib/fastclick.js'))
    .pipe(addsrc.append('bower_components/foundation/js/foundation.js'))
    .pipe(addsrc.append('bower_components/Chart.js/Chart.js'))
    .pipe(addsrc.append('bower_components/select2/dist/js/select2.js'))
    .pipe(addsrc.append('src/js/dataTables/datatables.js'))
    .pipe(addsrc.append('src/js/dataTables/dataTables.foundation.js'))
    .pipe(concat('thirdpartylibs.min.js',{newLine: '\r\n'}))
    .pipe(uglify())
    .pipe(gulp.dest('build/minified/'));
});
gulp.task('concatf6', function () {
 return gulp
    .src('bower_components/jquery/dist/jquery.js')
    .pipe(addsrc.append('bower_components/jquery-ui/jquery-ui.js'))
    .pipe(addsrc.append('bower_components/jquery-searcher/dist/jquery.searcher.js'))
    .pipe(addsrc.append('src/js/jquery.jqplot.js'))
    .pipe(addsrc.append('src/js/jqplot.dateAxisRenderer.js'))
    .pipe(addsrc.append('src/js/jqplot.cursor.js'))
    .pipe(addsrc.append('src/js/jqplot.highlighter.js'))
    .pipe(addsrc.append('src/js/jquery.tablesorter.js'))
    .pipe(addsrc.append('bower_components/motion-ui/motion-ui.js')) // foundation6
    .pipe(addsrc.append('bower_components/what-input/what-input.js')) // foundation6
    .pipe(addsrc.append('bower_components/foundation-sites/dist/foundation.js')) // foundation6
    .pipe(addsrc.append('bower_components/Chart.js/Chart.js'))
    .pipe(addsrc.append('bower_components/select2/dist/js/select2.js'))
    .pipe(addsrc.append('src/js/dataTables/datatables.js'))
    .pipe(addsrc.append('src/js/dataTables/dataTables.foundation.js'))
    .pipe(concat('f6-thirdpartylibs.min.js',{newLine: '\r\n'}))
    .pipe(uglify())
    .pipe(gulp.dest('build/minified/'));
});


gulp.task('sass-default-theme', function () { // WARNING: potential duplicate task
  return gulp
    .src('scss/app.scss')
    .pipe(sass({includePaths:['bower_components/foundation/scss'] }))
    .pipe(rename('default.css'))
    .pipe(gulp.dest('../styles/')) ;
});

gulp.task('sass-theme01',  function () { // WARNING: potential duplicate task
  
  console.log('RUN::::sass-theme01');
  return gulp
    .src('scss/app-theme01.scss')
    .pipe(sass({includePaths:['bower_components/foundation/scss'] }))
    .pipe(rename('theme01.css'))
    .pipe(gulp.dest('../styles/'))
  ;
});

gulp.task('sass-theme02',  function () { // preparing for foundation6
  
  console.log('RUN::::sass-theme02');
  return gulp
    .src('scss/app-theme02.scss')
    .pipe(sass({includePaths: sassFoundation6Paths }))
    .pipe(rename('theme02.css'))
    .pipe(gulp.dest('../styles/'))
  ;
});

gulp.task('sass-f6-default',  function () { // preparing for foundation6
  
  console.log('RUN::::sass-f6-default');
  return gulp
    .src('scss/f6-app.scss')
    .pipe(sass({includePaths: sassFoundation6Paths }))
    .pipe(rename('f6-default.css'))
    .pipe(gulp.dest('../styles/'))
  ;
});

gulp.task('sass',gulp.parallel('sass-default-theme','sass-theme01', 'sass-f6-default'));

gulp.task('clean', function () {
  return gulp
    .src(['js/*','css/*', 'tmpdist/*', 'build/src/*', 'build/minified/*'], { read: false })
	       .pipe(clean());
  ;
});


gulp.task('copy',function() {
  return gulp.src([
    'tmpdist/*',
    'bower_components/jquery.cookie/jquery.cookie.js',
    'bower_components/jquery-placeholder/jquery.placeholder.js',
    'bower_components/modernizr/modernizr.js',
    'src/js/local.js',
    'src/js/local-f6.js'
  ])
    .pipe(copy('build/src/',{ prefix: 2 }));

});


gulp.task('uglify', function () {
  return gulp.src(['build/src/jquery.cookie.js','build/src/jquery.placeholder.js','build/src/modernizr.js','build/src/local.js','build/src/local-f6.js'])
     .pipe(uglify())
     .pipe(rename({suffix: '.min' }))
     .pipe(gulp.dest('build/minified/'));
});


gulp.task('filerev', function () {
  return gulp.src('build/minified/*.min.js')
    .pipe(filerev())
    .pipe(gulp.dest('../js/'));
});





gulp.task('publish', gulp.series('clean','sass','concatf6','concat','copy','uglify','filerev'));


