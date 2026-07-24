/**
 * AppetitQR settings screen.
 *
 * Two admin-only tools: validate an API key against the live endpoint, and drop every
 * cached menu. Both go through admin-ajax with the nonce localized alongside this file.
 */
(function ($) {
    'use strict';

    var settings = window.APPETITQR_ADMIN_SETTINGS || {};
    var labels = settings.labels || {};

    function showResult(type, html) {
        $('#appetitqr-test-result')
            .removeClass('apq-result-success apq-result-error apq-result-info')
            .addClass('apq-result-' + type)
            .html(html)
            .prop('hidden', false);
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function testConnection() {
        var $button = $('#appetitqr-test-connection');
        var apiKey = $.trim($('#appetitqr-test-key').val());

        $button.prop('disabled', true).text(labels.testing || 'Testing…');

        $.post(settings.ajax_url, {
            action: 'appetitqr_test_connection',
            nonce: settings.nonce,
            api_key: apiKey
        })
            .done(function (response) {
                if (response && response.success) {
                    var data = response.data || {};
                    var rows = [
                        '<strong>' + escapeHtml(data.message) + '</strong>',
                        '<ul class="apq-result-list">',
                        '<li>' + escapeHtml(labels.location || 'Location') + ': ' + escapeHtml(data.location) + '</li>',
                        '<li>' + escapeHtml(labels.template || 'Template') + ': ' + escapeHtml(data.template) + '</li>',
                        '<li>' + escapeHtml(labels.categories || 'Categories') + ': ' + escapeHtml(data.categories) + '</li>',
                        '<li>' + escapeHtml(labels.products || 'Products') + ': ' + escapeHtml(data.products) + '</li>',
                        '</ul>'
                    ];
                    showResult('success', rows.join(''));
                } else {
                    showResult('error', escapeHtml((response && response.data && response.data.message) || labels.requestFailed));
                }
            })
            .fail(function () {
                showResult('error', escapeHtml(labels.requestFailed || 'The request failed.'));
            })
            .always(function () {
                $button.prop('disabled', false).text(labels.testConnection || 'Test connection');
            });
    }

    function clearCache() {
        if (!window.confirm(labels.confirmClear || 'Clear every cached menu?')) {
            return;
        }

        var $button = $('#appetitqr-clear-cache');
        $button.prop('disabled', true).text(labels.clearing || 'Clearing…');

        $.post(settings.ajax_url, {
            action: 'appetitqr_clear_cache',
            nonce: settings.nonce
        })
            .done(function (response) {
                if (response && response.success) {
                    showResult('info', escapeHtml(response.data.message));
                } else {
                    showResult('error', escapeHtml((response && response.data && response.data.message) || labels.requestFailed));
                }
            })
            .fail(function () {
                showResult('error', escapeHtml(labels.requestFailed || 'The request failed.'));
            })
            .always(function () {
                $button.prop('disabled', false).text(labels.clearCache || 'Clear menu cache');
            });
    }

    $(function () {
        $('#appetitqr-test-connection').on('click', testConnection);
        $('#appetitqr-clear-cache').on('click', clearCache);
    });
})(jQuery);
