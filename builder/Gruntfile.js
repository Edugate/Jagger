module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            dist: {
                src: [
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
                ],
                dest: 'tmpdist/thirdpartylibs.js'
            }
        },
        sass: {
            options: {
                includePaths: ['bower_components/foundation/scss']
            },
            dist: {
                options: {
                    outputStyle: 'compressed'
                },
                files: {
                    '../styles/default.css': 'scss/app.scss',
                    '../styles/theme01.css': 'scss/app-theme01.scss'
                }
            }
        },
        clean: {
            dist: {
                src: ['js/*', 'css/*', 'tmpdist/*', 'build/src/*', 'build/minified/*']
            }
        },
        copy: {
            dist: {
                files: [
                    {
                        expand: true,
                        flatten: true, // AT THIS LINE
                        src: [
                            'tmpdist/*',
                            'bower_components/jquery.cookie/jquery.cookie.js',
                            'bower_components/jquery-placeholder/jquery.placeholder.js',
                            'bower_components/modernizr/modernizr.js',
                            'src/js/local.js'
                        ],
                        dest: 'build/src/'
                    }
                ]
            }
        },
        uglify: {
            dynamicmapping: {
                files: [
                    {
                        expand: true,
                        cwd: 'build/src/',
                        src: ['*.js'],
                        dest: 'build/minified/',
                        ext: '.min.js',
                        extDot: 'last'
                    }
                ]
            }
        },
        filerev: {
            js: {
                src: ['build/minified/*.js'],
                dest: '../js/'
            }
        },
        watch: {
            grunt: { files: ['Gruntfile.js'] },

            sass: {
                files: 'scss/**/*.scss',
                tasks: ['sass']
            }
        }
    });

    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-filerev');
    grunt.registerTask('build', ['sass']);
    grunt.registerTask('publish', ['clean:dist', 'build', 'copy:dist']);
    grunt.registerTask('devpublish', ['clean:dist', 'build', 'concat:dist', 'copy:dist', 'uglify', 'filerev']);
    grunt.registerTask('default', ['build', 'watch']);
}
