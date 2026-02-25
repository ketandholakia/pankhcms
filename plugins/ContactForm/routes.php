<?php

Flight::route('/contact-form', function () {
    echo Flight::get('blade')->render('plugins.ContactForm.views.form');
});
