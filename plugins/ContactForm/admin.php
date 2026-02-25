<?php

Flight::route('/admin/contact-form', function () {
    echo Flight::get('blade')->render('plugins.ContactForm.views.admin.index');
});
