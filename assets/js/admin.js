/* assets/js/admin.js — Techrappy SEO Admin */
/* global TechrappySEO */
(function($) {
    'use strict';

    // Global admin JS — à implémenter.

    /**
     * Utilitaire AJAX générique.
     *
     * @param {string}   action   Nom de l'action AJAX WordPress.
     * @param {Object}   data     Données à envoyer.
     * @param {Function} success  Callback en cas de succès.
     * @param {Function} error    Callback en cas d'erreur.
     */
    window.TechrappySEOAjax = function(action, data, success, error) {
        $.ajax({
            url: TechrappySEO.ajax_url,
            type: 'POST',
            data: Object.assign({ action: action }, data),
            success: function(response) {
                if (response.success) {
                    if (typeof success === 'function') { success(response.data); }
                } else {
                    if (typeof error === 'function') { error(response.data); }
                }
            },
            error: function(xhr, status, err) {
                if (typeof error === 'function') { error({ message: err }); }
            }
        });
    };

})(jQuery);
