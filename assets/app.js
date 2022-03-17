import './styles/app.css';

const $ = require('jquery');
require('bootstrap');

$(document).ready(function() {
    $('[data-toggle="popover"]').popover();
});

//stimulus
//import 'bootstrap';
