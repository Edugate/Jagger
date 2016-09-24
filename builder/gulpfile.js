var gulp = require('gulp');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var clean = require('gulp-clean');
var copy = require('gulp-copy');
var copyfonts = require('gulp-copy');
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
    'bower_components/font-awesome/scss',
    'bower_components/foundation-font-awesome-buttons/src'
];

gulp.task('copyfonts', function () {
    return gulp.src([
        'bower_components/font-awesome/fonts/*'
    ])
        .pipe(copy('../styles/fontawesome/fonts/', {prefix: 3}));

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
        //.pipe(addsrc.append('bower_components/highlight/src/highlight.js'))
        .pipe(addsrc.append('src/js/highlight.pack.js'))
        .pipe(concat('f6-thirdpartylibs.min.js', {newLine: '\r\n'}))
        .pipe(uglify())
        .pipe(gulp.dest('build/minified/'));
});

gulp.task('sass-f6-default-theme', function () { // preparing for foundation6

    console.log('RUN::::sass-f6-default');
    return gulp
        .src('scss/f6-app.scss')
        .pipe(sass({includePaths: sassFoundation6Paths}))
        .pipe(rename('default.css'))
        .pipe(gulp.dest('../styles/'));
});
gulp.task('sass-f6-theme01', function () { // preparing for foundation6

    console.log('RUN::::sass-f6-theme01');
    return gulp
        .src('scss/f6-app-theme01.scss')
        .pipe(sass({includePaths: sassFoundation6Paths}))
        .pipe(rename('theme01.css'))
        .pipe(gulp.dest('../styles/'));
});
gulp.task('sass-f6-theme02', function () { // preparing for foundation6

    console.log('RUN::::sass-f6-theme02');
    return gulp
        .src('scss/f6-app-theme02.scss')
        .pipe(sass({includePaths: sassFoundation6Paths}))
        .pipe(rename('theme02.css'))
        .pipe(gulp.dest('../styles/'));
});

gulp.task('sass', gulp.parallel('copyfonts',  'sass-f6-default-theme', 'sass-f6-theme01', 'sass-f6-theme02'));

gulp.task('clean', function () {
    return gulp
        .src(['js/*', 'css/*', 'tmpdist/*', 'build/src/*', 'build/minified/*'], {read: false})
        .pipe(clean());
});


gulp.task('copy', function () {
    return gulp.src([
        'tmpdist/*',
        'bower_components/jquery.cookie/jquery.cookie.js',
        'bower_components/jquery-placeholder/jquery.placeholder.js',
        'bower_components/modernizr/modernizr.js',
        'src/js/local-f6.js'
    ])
        .pipe(copy('build/src/', {prefix: 2}));

});

gulp.task('uglify', function () {
    return gulp.src(['build/src/jquery.cookie.js', 'build/src/jquery.placeholder.js', 'build/src/modernizr.js', 'build/src/local-f6.js'])
        .pipe(uglify())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest('build/minified/'));
});


gulp.task('filerev', function () {
    return gulp.src('build/minified/*.min.js')
        .pipe(filerev())
        .pipe(gulp.dest('../js/'));
});


gulp.task('publish', gulp.series('clean', 'sass', 'concatf6',  'copy', 'uglify', 'filerev'));


