require('../css/style.scss');
global.swal = require('sweetalert2');
var $ = require('jquery');
global.$ = global.jQuery = $;
require('bootstrap');

var routes = require('../../var/sources/fos_js_routes.json');
global.Routing = require('../../public/bundles/fosjsrouting/js/router.min.js');
Routing.setRoutingData(routes);