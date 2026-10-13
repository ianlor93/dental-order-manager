<?php

/**
 * Blocklist di domini email disposable / temporanei / throwaway.
 *
 * Usata da App\Http\Controllers\LeadController per rifiutare pre-registrazioni
 * provenienti da indirizzi non riconducibili a un destinatario reale.
 *
 * La lista è volutamente conservativa (~80 domini molto noti). Aggiornare
 * periodicamente — esistono migliaia di servizi disposable; coprire il long-tail
 * darebbe falsa sicurezza. Per protezione reale serve double-opt-in via email.
 *
 * Tutti i domini in lowercase, senza www, senza protocollo.
 * Match esatto sul dominio dell'email.
 */

return [
    // Mailinator family
    'mailinator.com',
    'mailinator.net',
    'mailinator.org',
    'mailinator2.com',
    'binkmail.com',
    'reallymymail.com',

    // Guerrilla Mail family
    'guerrillamail.com',
    'guerrillamail.net',
    'guerrillamail.org',
    'guerrillamail.biz',
    'guerrillamailblock.com',
    'sharklasers.com',
    'grr.la',
    'pokemail.net',
    'spam4.me',

    // 10minutemail family
    '10minutemail.com',
    '10minutemail.net',
    '10minutemail.org',
    '10minemail.com',
    '20minutemail.com',
    '30minutemail.com',
    '60minutemail.com',

    // Yopmail family
    'yopmail.com',
    'yopmail.net',
    'yopmail.fr',
    'cool.fr.nf',
    'jetable.fr.nf',
    'nospam.ze.tc',
    'nomail.xl.cx',
    'mega.zik.dj',
    'speed.1s.fr',

    // TempMail family
    'tempmail.com',
    'tempmail.net',
    'tempmail.org',
    'tempmailo.com',
    'temp-mail.org',
    'temp-mail.io',
    'temp-mail.ru',
    'tempinbox.com',
    'tempemail.com',
    'tempemail.net',

    // Throwaway family
    'throwaway.email',
    'throwawaymail.com',
    'fakeinbox.com',
    'fakemailgenerator.com',
    'maildrop.cc',
    'getairmail.com',
    'mintemail.com',
    'spamgourmet.com',
    'trashmail.com',
    'trashmail.net',
    'trashmail.de',
    'trashmail.io',
    'trashmail.me',
    'dispostable.com',
    'mytrashmail.com',

    // Email-as-a-service / bot-friendly
    'getnada.com',
    'nada.email',
    'inboxbear.com',
    'boximail.com',
    'mohmal.com',
    'mailbox52.ga',
    'tafmail.com',
    'mailcatch.com',
    'emailondeck.com',
    'mailnesia.com',
    'mytemp.email',
    'instant-mail.de',
    'tmpmail.org',
    'tmpmail.net',
    'tmpeml.com',
    'mailtemp.info',
    'getmail.online',

    // Italian / multi-lingual disposable
    'mailtemporanee.it',
    'emailtemporanee.com',
    'mailusato.com',

    // Other notable disposable services
    'discard.email',
    'discardmail.com',
    'fakemail.net',
    'spambox.us',
    'spambog.com',
    'tempr.email',
    'tempm.com',
    'tempmailaddress.com',
    'wegwerfmail.de',
    'wegwerfmail.net',
];
