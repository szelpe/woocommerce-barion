/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({
		// Generate POT files.
		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: 'languages',
				potHeaders: {
					'report-msgid-bugs-to': 'https://github.com/szelpe/woocommerce-barion/issues',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			dist: {
				options: {
					potFilename: 'pay-via-barion-for-woocommerce.pot'
				}
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	// Register tasks
	grunt.registerTask( 'default', [
		'makepot'
	]);
};