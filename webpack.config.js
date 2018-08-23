var Encore = require('@symfony/webpack-encore');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // uncomment to create hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // uncomment to define the assets of the project
    .addEntry('app', './assets/js/app.js')
    .addEntry('user_area_app', './assets/js/user_area_app.js')
    .addEntry('login-register', './assets/images/login-register.jpg')
    .addEntry('logo-text', './assets/images/logo-text.png')
    .addEntry('logo-light-text', './assets/images/logo-light-text.png')
    .addEntry('logo-icon', './assets/images/logo-icon.png')
    .addEntry('logo-light-icon', './assets/images/logo-light-icon.png')
    .addStyleEntry('login', './assets/css/pages/login-register-lock.scss')

    // uncomment if you use Sass/SCSS files
     .enableSassLoader()

    // uncomment for legacy applications that require $/jQuery as a global variable
     .autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();

