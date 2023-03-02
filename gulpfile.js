const gulp = require( 'gulp' );
const fs = require( 'fs' );
const $ = require( 'gulp-load-plugins' )();

let plumber = true;

// Sassのタスク
gulp.task( 'sass', function () {

	return gulp.src( [ './assets/scss/**/*.scss' ] )
		.pipe( $.plumber( {
			errorHandler: $.notify.onError( '<%= error.message %>' )
		} ) )
		.pipe( $.sassGlob() )
		.pipe( $.sourcemaps.init() )
		.pipe( $.sass( {
			errLogToConsole: true,
			outputStyle: 'compressed',
			sourceComments: false,
			sourcemap: true,
		} ) )
		.pipe( $.autoprefixer() )
		.pipe( $.sourcemaps.write( './map' ) )
		.pipe( gulp.dest( './dist/css' ) );
} );

// Style lint.
gulp.task( 'stylelint', function () {
	let task = gulp.src( [ './assets/scss/**/*.scss' ] );
	if ( plumber ) {
		task = task.pipe( $.plumber() );
	}
	return task.pipe( $.stylelint( {
		reporters: [
			{
				formatter: 'string',
				console: true,
			},
		],
	} ) );
} );

// watch
gulp.task( 'watch', function ( done ) {
	// Make SASS
	gulp.watch( 'assets/scss/**/*.scss', gulp.parallel( 'sass', 'stylelint' ) );
	done();
} );

// Toggle plumber.
gulp.task( 'noplumber', ( done ) => {
	plumber = false;
	done();
} );

// Build
gulp.task( 'build', gulp.series( gulp.parallel( 'sass' ) ) );

// Default Tasks
gulp.task( 'default', gulp.series( 'watch' ) );

// Lint
gulp.task( 'lint', gulp.series( 'noplumber', gulp.parallel( 'stylelint' ) ) );
