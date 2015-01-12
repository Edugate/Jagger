module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

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
                src: ['js/*', 'css/*']
            },
        },
        copy: {
            dist: {
                files: [
                    {
                        expand: true,
                        flatten: true, // AT THIS LINE
                        src: [
                            'bower_components/foundation/js/foundation/*.*',
                            'other_components/jquery-ui-1.10.4.custom.min.js',
                            'bower_components/foundation/js/foundation.js',
                            'bower_components/foundation/js/foundation.min.js',
                            'bower_components/fastclick/lib/fastclick.js',
                            'bower_components/jquery/dist/*.*',
                            'bower_components/jquery.cookie/jquery.cookie.js',
                            'bower_components/jquery-placeholder/jquery.placeholder.js',
                            'bower_components/modernizr/modernizr.js'
                        ],
                        dest: '../js/'
                    }
                ]
            },
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
    grunt.registerTask('build', ['sass']);
    grunt.registerTask('publish', ['clean:dist', 'build', 'copy:dist']);
    grunt.registerTask('default', ['build', 'watch']);
}
