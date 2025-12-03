/**
 * CertBuilder Pro Admin JavaScript
 */

(function($) {
	'use strict';

	const CertBuilderAdmin = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Delete template
			$(document).on('click', '.certbuilder-delete-template', this.deleteTemplate);

			// Duplicate template
			$(document).on('click', '.certbuilder-duplicate-template', this.duplicateTemplate);

			// Revoke certificate confirmation
			$(document).on('click', '.certbuilder-revoke-btn', this.confirmRevoke);
		},

		deleteTemplate: function(e) {
			e.preventDefault();

			if (!confirm(certbuilderAdmin.strings.confirmDelete)) {
				return;
			}

			const $button = $(this);
			const templateId = $button.data('template-id');

			$button.prop('disabled', true).text(certbuilderAdmin.strings.loading);

			$.ajax({
				url: certbuilderAdmin.restUrl + 'templates/' + templateId,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', certbuilderAdmin.nonce);
				},
				success: function() {
					$button.closest('.certbuilder-template-card').fadeOut(300, function() {
						$(this).remove();
					});
				},
				error: function(xhr) {
					const message = xhr.responseJSON?.message || certbuilderAdmin.strings.error;
					alert(message);
					$button.prop('disabled', false).text('Delete');
				}
			});
		},

		duplicateTemplate: function(e) {
			e.preventDefault();

			const $button = $(this);
			const templateId = $button.data('template-id');

			$button.prop('disabled', true).text(certbuilderAdmin.strings.loading);

			$.ajax({
				url: certbuilderAdmin.restUrl + 'templates/' + templateId + '/duplicate',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', certbuilderAdmin.nonce);
				},
				success: function(response) {
					// Redirect to edit the duplicated template
					window.location.href = certbuilderAdmin.pluginUrl.replace('/certbuilder-pro/', '') +
						'/wp-admin/admin.php?page=certbuilder-builder&template_id=' + response.id;
				},
				error: function(xhr) {
					const message = xhr.responseJSON?.message || certbuilderAdmin.strings.error;
					alert(message);
					$button.prop('disabled', false).text('Duplicate');
				}
			});
		},

		confirmRevoke: function(e) {
			if (!confirm(certbuilderAdmin.strings.confirmRevoke)) {
				e.preventDefault();
			}
		}
	};

	$(document).ready(function() {
		CertBuilderAdmin.init();
	});

})(jQuery);
