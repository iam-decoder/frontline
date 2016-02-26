var gulp = require("gulp");
var elixir = require("laravel-elixir");

elixir.config.assetsDir = '';
elixir.config.sourcemaps = false;
elixir.config.registerWatcher("default", "js/**");

elixir(function (mix)
{

    // build SASS
    mix.sass(['skin.scss'], '../assets/css/skin.css', {});

    // js for entire site
    mix.scripts([
        'vendor/jquery/jquery-2.2.1.js',
        'vendor/jquery/serializeObject.js',
        'vendor/JSBN/jsbn.js',
        'vendor/JSBN/rsa.js',
        'vendor/JSBN/prng4.js',
        'vendor/JSBN/rng.js'
    ], '../public_html/assets/js/lib.js');

    // For sections, pages, and other site-wide javascript (that's not a library)
    mix.scripts([
        'controller.js'
    ], '../public_html/assets/js/frontline.js');

});
